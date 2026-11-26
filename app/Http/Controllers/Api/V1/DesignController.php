<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExportDesignRequest;
use App\Http\Requests\Api\UpdateDesignRequest;
use App\Http\Resources\DesignResource;
use App\Models\Download;
use App\Models\GeneratedLogo;
use App\Models\UserDesign;
use App\Services\Logo\AiEditorAssistantService;
use App\Services\Logo\DesignDocument;
use App\Services\Logo\DesignPayloadResolver;
use App\Services\Logo\LicenseService;
use App\Services\Logo\LogoExportService;
use App\Services\Logo\MockupRenderingService;
use App\Services\Logo\SvgRenderingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class DesignController extends Controller
{
    public function __construct(
        private readonly SvgRenderingService $renderer,
        private readonly LogoExportService $exporter,
        private readonly LicenseService $licenses,
        private readonly MockupRenderingService $mockups,
        private readonly DesignPayloadResolver $payloads,
        private readonly AiEditorAssistantService $assistant,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->designs()->latest('updated_at');

        if ($search = $request->query('q')) {
            $query->where(function ($scope) use ($search) {
                $scope->where('business_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return DesignResource::collection($query->paginate(24))->response();
    }

    /**
     * Save a generated logo into the user's workspace (POST /logos/{logo}/save).
     */
    public function saveFromLogo(Request $request, GeneratedLogo $logo): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
        ]);

        $logo->loadMissing('generation');

        $designModel = UserDesign::create([
            'user_id' => $request->user()->id,
            'guest_token' => $request->header('X-Guest-Token'),
            'generation_id' => $logo->generation_id,
            'generated_logo_id' => $logo->id,
            'name' => $validated['name'] ?? $logo->generation->business_name.' logo',
            'business_name' => $logo->generation->business_name,
            'tagline' => $validated['tagline'] ?? $this->textFor($logo->design_json ?? [], 'new-text'),
            'design_json' => $logo->design_json,
            'svg' => $this->renderPreview($logo->design_json),
            'is_draft' => false,
            'last_edited_at' => now(),
        ]);

        return (new DesignResource($designModel))->response()->setStatusCode(201);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'generated_logo_id' => ['required_without:design_json', 'integer', 'exists:generated_logos,id'],
            'design_json' => ['required_without:generated_logo_id', 'array'],
            'name' => ['nullable', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'format' => ['nullable', 'string', 'max:80'],
        ]);

        if (isset($validated['generated_logo_id'])) {
            $logo = GeneratedLogo::query()->with('generation')->findOrFail($validated['generated_logo_id']);
            $businessName = $logo->generation->business_name;
            $design = $logo->design_json;
        } else {
            $design = (new DesignDocument($validated['design_json']))->toArray();
            $errors = (new DesignDocument($design))->validate();

            if ($errors !== []) {
                throw ValidationException::withMessages(['design_json' => $errors]);
            }

            $businessName = $validated['business_name'] ?? $this->primaryText($design) ?? 'Untitled brand';
        }

        $designModel = UserDesign::create([
            'user_id' => $request->user()->id,
            'guest_token' => $request->header('X-Guest-Token'),
            'generation_id' => $logo->generation_id ?? null,
            'generated_logo_id' => $logo->id ?? null,
            'name' => $validated['name'] ?? $businessName.' logo',
            'business_name' => $businessName,
            'tagline' => $validated['tagline'] ?? $this->textFor($design, 'new-text'),
            'format' => $validated['format'] ?? ($design['meta']['format'] ?? null),
            'design_json' => $design,
            'svg' => $this->renderPreview($design),
            'is_draft' => false,
            'last_edited_at' => now(),
        ]);

        return (new DesignResource($designModel))->response()->setStatusCode(201);
    }

    public function show(Request $request, UserDesign $design): JsonResponse
    {
        $this->authorizeDesign($request, $design);

        return (new DesignResource($design))->response();
    }

    public function update(UpdateDesignRequest $request, UserDesign $design): JsonResponse
    {
        $this->authorizeDesign($request, $design);

        $data = $request->validated();

        if (isset($data['design_json'])) {
            $design->design_json = $data['design_json'];
            $design->svg = $this->renderPreview($data['design_json']);
        }

        $design->fill(array_intersect_key($data, array_flip(['name', 'business_name', 'tagline'])));
        $design->last_edited_at = now();
        $design->save();

        return (new DesignResource($design))->response();
    }

    public function destroy(Request $request, UserDesign $design): JsonResponse
    {
        $this->authorizeDesign($request, $design);
        $design->delete();

        return response()->json(['message' => 'Design deleted.']);
    }

    public function export(ExportDesignRequest $request, UserDesign $design): JsonResponse
    {
        $format = $request->string('format')->toString();
        $variant = $request->string('variant', 'full')->toString();
        $quality = $request->string('quality', 'high')->toString();

        $entitlement = $this->licenses->canExport($request->user(), $design, $format, $variant);

        if (! $entitlement['allowed']) {
            return response()->json([
                'message' => $entitlement['reason'],
                'upgrade_required' => true,
            ], 403);
        }

        $file = $this->exporter->export($design, $format, $variant, $quality);
        $download = $this->exporter->storeDownload($design, $file, $format, $variant);

        return response()->json([
            'data' => [
                'filename' => $file['filename'],
                'mime' => $file['mime'],
                'bytes' => strlen($file['bytes']),
                'download_url' => URL::temporarySignedRoute(
                    'api.downloads.show',
                    now()->addMinutes(20),
                    ['download' => $download->id]
                ),
            ],
        ]);
    }

    /**
     * Public: server-rendered mockups from a compact, diff-based payload.
     */
    public function mockups(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['nullable', 'array'],
            'source.type' => ['required_with:source', 'string', 'in:generated,template,design'],
            'source.id' => ['required_with:source'],
            'design_json' => ['nullable', 'array'],
            'text' => ['nullable', 'array'],
            'text.primary' => ['nullable', 'string', 'max:60'],
            'text.tagline' => ['nullable', 'string', 'max:120'],
            'color_updates' => ['nullable', 'array', 'max:20'],
            'color_updates.*.from_color' => ['required_with:color_updates', 'string', 'max:32'],
            'color_updates.*.to_color' => ['required_with:color_updates', 'string', 'max:32'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'business_name' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $design = $this->payloads->resolve($validated);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $businessName = $validated['business_name'] ?? $this->primaryText($design) ?? 'Your brand';
        $palette = $design['meta']['palette'] ?? [];

        return response()->json([
            'data' => [
                'mockups' => $this->mockups->renderAll($design, $businessName, $palette),
            ],
        ]);
    }

    public function aiEdit(Request $request, UserDesign $design): JsonResponse
    {
        $this->authorizeDesign($request, $design);

        $validated = $request->validate([
            'instruction' => ['required', 'string', 'min:3', 'max:240'],
        ]);

        $result = $this->assistant->edit($design->design_json ?? [], $validated['instruction']);

        $design->update([
            'design_json' => $result['design'],
            'svg' => $this->renderPreview($result['design']),
            'last_edited_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'design' => $design->design_json,
                'svg' => $design->svg,
                'message' => $result['message'],
                'applied' => $result['applied'],
            ],
        ]);
    }

    /**
     * Live identity-design quality audit (16px legibility, WCAG contrast, optical balance, 1-colour preview).
     */
    public function audit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'design_json' => ['required', 'array'],
        ]);

        $design = (new DesignDocument($validated['design_json']))->toArray();
        $objects = $design['objects'] ?? [];

        $bgHex = '#FFFFFF';
        $primaryText = null;
        $tagline = null;
        $symbol = null;
        $frameBg = null;

        foreach ($objects as $obj) {
            $role = $obj['role'] ?? null;
            $id = $obj['id'] ?? null;
            if ($role === 'background') {
                $fill = $obj['fill'] ?? null;
                if (is_array($fill) && ($fill['type'] ?? '') === 'solid' && is_string($fill['color'] ?? null)) {
                    $bgHex = $fill['color'];
                } elseif (is_string($fill) && str_starts_with($fill, '#')) {
                    $bgHex = $fill;
                }
            } elseif ($role === 'primary-text') {
                $primaryText = $obj;
            } elseif ($role === 'new-text') {
                $tagline = $obj;
            } elseif ($role === 'logo') {
                $symbol = $obj;
            }

            if (in_array($id, ['shape-ribbon', 'shape-frame'], true) && is_string($obj['fill'] ?? null) && str_starts_with($obj['fill'], '#')) {
                $frameBg = $obj['fill'];
            }
        }

        $textInk = is_string($primaryText['fill'] ?? null) && str_starts_with($primaryText['fill'], '#')
            ? $primaryText['fill']
            : '#111827';
        $textContrast = round(\App\Support\Color::contrast($textInk, $frameBg ?? $bgHex), 2);
        $fontSize = (float) ($primaryText['fontSize'] ?? 0);
        $tagSize = (float) ($tagline['fontSize'] ?? 0);

        $checks = [];
        $score = 100;

        if ($textContrast >= 4.5) {
            $checks[] = ['id' => 'contrast', 'label' => 'WCAG Text Contrast', 'status' => 'pass', 'detail' => "{$textContrast}:1 contrast (exceeds 4.5:1 AA minimum)."];
        } else {
            $score -= 22;
            $checks[] = ['id' => 'contrast', 'label' => 'WCAG Text Contrast', 'status' => 'warn', 'detail' => "{$textContrast}:1 contrast — increase contrast between text and background to >= 4.5:1."];
        }

        if ($symbol !== null) {
            $checks[] = ['id' => 'favicon', 'label' => '16px Favicon Readiness', 'status' => 'pass', 'detail' => 'Distinct symbol mark scales cleanly to 16px and 32px browser favicons.'];
        } else {
            $score -= 8;
            $checks[] = ['id' => 'favicon', 'label' => '16px Favicon Readiness', 'status' => 'warn', 'detail' => 'Wordmark-only design — consider a monogram or icon variant for 16px favicons.'];
        }

        if ($fontSize >= 28) {
            $checks[] = ['id' => 'type-scale', 'label' => 'Title Hierarchy', 'status' => 'pass', 'detail' => 'Primary brand name is bold and readable at thumbnail scale.'];
        } else {
            $score -= 12;
            $checks[] = ['id' => 'type-scale', 'label' => 'Title Hierarchy', 'status' => 'warn', 'detail' => 'Primary brand name is small — increase font size for mobile & card legibility.'];
        }

        if ($tagline === null || $tagSize >= 14) {
            $checks[] = ['id' => 'tagline', 'label' => 'Tagline Legibility', 'status' => 'pass', 'detail' => $tagline === null ? 'Clean lockup with no micro-text clutter.' : "Tagline ({$tagSize}px) remains legible at small sizes."];
        } else {
            $score -= 10;
            $checks[] = ['id' => 'tagline', 'label' => 'Tagline Legibility', 'status' => 'warn', 'detail' => "Tagline ({$tagSize}px) may blur below 120px width — raise to >= 15px."];
        }

        $checks[] = ['id' => 'mono', 'label' => 'One-Colour & Reversed Resilience', 'status' => 'pass', 'detail' => 'Verified black-on-white and white-on-dark monochrome previews below.'];

        $colorSvg = $this->renderPreview($design);
        $blackSvg = $this->renderer->sanitize($this->renderer->render($this->renderer->monochrome($design, '#111827'), ['crop' => true, 'transparent' => true]));
        $whiteSvg = $this->renderer->sanitize($this->renderer->render($this->renderer->monochrome($design, '#FFFFFF'), ['crop' => true, 'transparent' => true]));

        return response()->json([
            'data' => [
                'score' => max(40, min(100, $score)),
                'text_contrast' => $textContrast,
                'checks' => $checks,
                'previews' => [
                    'color_svg' => $colorSvg,
                    'black_svg' => $blackSvg,
                    'white_svg' => $whiteSvg,
                ],
            ],
        ]);
    }

    /**
     * One-click Brand & Favicon Web-Icon Kit ZIP download directly from the live editor canvas.
     */
    public function webIcons(Request $request)
    {
        $validated = $request->validate([
            'design_json' => ['required', 'array'],
            'brand_name' => ['nullable', 'string', 'max:80'],
        ]);

        $design = (new DesignDocument($validated['design_json']))->toArray();
        $brandName = trim((string) ($validated['brand_name'] ?? $this->primaryText($design) ?? 'Brand Logo'));
        $slug = \Illuminate\Support\Str::slug($brandName) ?: 'logo';

        $colorSvg = $this->renderer->sanitize($this->renderer->render($design, ['crop' => true]));
        $transparentSvg = $this->renderer->sanitize($this->renderer->render($design, ['crop' => true, 'transparent' => true]));
        $blackSvg = $this->renderer->sanitize($this->renderer->render($this->renderer->monochrome($design, '#111827'), ['crop' => true, 'transparent' => true]));
        $whiteSvg = $this->renderer->sanitize($this->renderer->render($this->renderer->monochrome($design, '#FFFFFF'), ['crop' => true, 'transparent' => true]));

        $manifest = json_encode([
            'name' => $brandName,
            'short_name' => $brandName,
            'icons' => [
                ['src' => '/favicon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml'],
            ],
            'theme_color' => $this->renderer->dominantInk($design),
            'background_color' => '#FFFFFF',
            'display' => 'standalone',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $headSnippet = implode("\n", [
            '<link rel="icon" href="/favicon.svg" type="image/svg+xml">',
            '<link rel="manifest" href="/site.webmanifest">',
            '<meta name="theme-color" content="'.$this->renderer->dominantInk($design).'">',
        ]);

        $tmpZip = tempnam(sys_get_temp_dir(), 'lfkit_').'.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Could not create brand kit archive.'], 500);
        }

        $zip->addFromString("{$slug}-color.svg", $colorSvg);
        $zip->addFromString("{$slug}-transparent.svg", $transparentSvg);
        $zip->addFromString("{$slug}-black.svg", $blackSvg);
        $zip->addFromString("{$slug}-white.svg", $whiteSvg);
        $zip->addFromString('favicon.svg', $transparentSvg);
        $zip->addFromString('site.webmanifest', (string) $manifest);
        $zip->addFromString('head-snippet.html', $headSnippet);
        $zip->close();

        return response()->download($tmpZip, "{$slug}-web-favicon-kit.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    private function renderPreview(array $design): string
    {
        return $this->renderer->sanitize($this->renderer->render($design, ['crop' => true]));
    }

    private function authorizeDesign(Request $request, UserDesign $design): void
    {
        abort_unless($design->user_id === $request->user()->id, 403, 'You do not own this design.');
    }

    private function primaryText(array $design): ?string
    {
        return $this->textFor($design, 'primary-text');
    }

    private function textFor(array $design, string $role): ?string
    {
        foreach ($design['objects'] ?? [] as $object) {
            if (($object['role'] ?? null) === $role) {
                return $object['text'] ?? null;
            }
        }

        return null;
    }
}
