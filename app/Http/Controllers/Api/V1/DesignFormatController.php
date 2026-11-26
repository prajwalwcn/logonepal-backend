<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LogoAsset;
use App\Services\Design\DesignFormats;
use App\Services\Logo\DesignDocument;
use App\Services\Design\FormatVariationEngine;
use App\Services\Logo\ChromeRasterizer;
use App\Services\Logo\SvgRenderingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DesignFormatController extends Controller
{
    public function __construct(
        private readonly FormatVariationEngine $engine,
        private readonly SvgRenderingService $renderer,
        private readonly ChromeRasterizer $rasterizer,
    ) {
    }

    /**
     * Catalogue of every design generator, grouped by category.
     */
    public function index(Request $request): JsonResponse
    {
        $category = is_string($request->query('category')) ? $request->query('category') : null;
        $query = is_string($request->query('q')) ? $request->query('q') : null;
        $cacheKey = 'design_formats.index.'.md5(($category ?? '').'|'.($query ?? ''));

        $payload = Cache::remember($cacheKey, 3600, fn () => [
            'data' => [
                'total' => count(DesignFormats::all()),
                'categories' => DesignFormats::grouped($category, $query, true),
            ],
            'meta' => [
                'palettes' => DesignFormats::PALETTES,
                'fonts' => array_values(DesignFormats::FONTS),
            ],
        ]);

        return response()->json($payload)->header('Cache-Control', 'public, max-age=300');
    }

    public function show(string $slug): JsonResponse
    {
        $format = DesignFormats::find($slug);

        if ($format === null) {
            return response()->json(['message' => 'Design format not found.'], 404);
        }

        $payload = Cache::remember("design_formats.show.{$format->slug}", 3600, fn () => [
            'data' => array_merge($format->toArray(), [
                'options' => [
                    'palettes' => DesignFormats::PALETTES,
                    'fonts' => array_values(DesignFormats::FONTS),
                ],
            ]),
        ]);

        return response()->json($payload)->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * Generate a set of concepts for a format. Public and AI-key free.
     */
    public function generate(Request $request, string $slug): JsonResponse
    {
        $format = DesignFormats::find($slug);

        if ($format === null) {
            return response()->json(['message' => 'Design format not found.'], 404);
        }

        $rules = [
            'brand' => ['required', 'string', 'min:2', 'max:80'],
            'colors' => ['nullable', 'array', 'max:3'],
            'colors.*' => ['string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['nullable', 'string', 'max:60'],
            'style' => ['nullable', 'string', 'max:40'],
            'count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'page' => ['nullable', 'integer', 'min:1', 'max:20'],
            'symbol' => ['nullable', 'string', 'max:80'],
            'keywords' => ['nullable', 'string', 'max:120'],
            'logo_image' => ['nullable', 'string', 'max:1500000'],
            'qr_image' => ['nullable', 'string', 'max:400000'],
            'no_logo' => ['nullable', 'boolean'],
            'card_side' => ['nullable', 'string', 'in:front,back'],
        ];

        foreach ($format->fieldKeys as $key) {
            $definition = DesignFormats::fieldDefinition($key);

            if ($definition === null) {
                continue;
            }

            $rules[$key] = ['nullable', 'string', 'max:'.$definition['max']];
        }

        $validated = $request->validate($rules);

        if (isset($validated['qr_image']) && $validated['qr_image'] !== ''
            && ! preg_match('/^(data:image\/(png|jpeg|webp|svg\+xml);base64,|https:\/\/)/i', $validated['qr_image'])) {
            throw ValidationException::withMessages([
                'qr_image' => 'Provide a data URI or HTTPS image URL.',
            ]);
        }

        $canCache = empty($validated['logo_image']) && empty($validated['qr_image']);
        $payloadCacheKey = $canCache
            ? 'design_formats.json.'.$format->slug.'.'.md5((string) json_encode($validated))
            : null;

        if ($payloadCacheKey !== null) {
            $cachedJson = Cache::get($payloadCacheKey);
            if (is_string($cachedJson)) {
                return JsonResponse::fromJsonString($cachedJson)->header('X-Cache', 'HIT');
            }
        }

        $input = $validated;
        $input['symbol'] = null;
        $input['symbol_pool'] = [];
        $input['logo_image'] = null;
        $noLogo = ! empty($validated['no_logo']);

        if (! $noLogo && ! empty($validated['logo_image']) && preg_match('/^(data:image\/(png|jpeg|jpg|webp|svg\+xml);base64,|\/storage\/|\/logodesignnepal\/)/i', $validated['logo_image'])) {
            $input['logo_image'] = $validated['logo_image'];
        }

        if (! $noLogo && $input['logo_image'] === null) {
            $toSymbolPayload = static fn (LogoAsset $asset): array => [
                'asset_type' => $asset->asset_type ?? 'svg',
                'image_url' => $asset->image_url,
                'view_box' => $asset->view_box,
                'paths' => $asset->paths ?? [],
            ];

            if (! empty($validated['symbol'])) {
                $asset = LogoAsset::query()->active()->where('slug', $validated['symbol'])->first();
                if ($asset !== null) {
                    $input['symbol'] = $toSymbolPayload($asset);
                    $input['symbol_pool'] = [$input['symbol']];
                }
            } else {
                $searchBlob = mb_strtolower(trim(($validated['brand'] ?? '').' '.($validated['keywords'] ?? '')));
                $stopWords = [
                    'your', 'brand', 'company', 'business', 'name', 'the', 'and', 'for', 'inc', 'llc', 'ltd',
                    'corporation', 'coperation', 'cooperation', 'corp', 'enterprises', 'enterprise', 'group',
                    'services', 'service', 'solutions', 'pvt', 'limited', 'international', 'global',
                ];
                $geoWords = [
                    'nepal', 'india', 'usa', 'uk', 'asia', 'europe', 'kathmandu', 'pokhara',
                    'everest', 'annapurna', 'dubai', 'london', 'paris', 'tokyo', 'sydney',
                    'canada', 'australia', 'germany', 'france', 'spain', 'italy',
                ];
                $isTravelIntent = (bool) preg_match('/\b(travel|travels|tour|tours|trek|trekking|himalaya|flight|airline|holiday|hotel|resort|adventure|trip|cruise|beach|vacation)\b/i', $searchBlob);
                $rawTokens = array_values(array_filter(
                    preg_split('/[^a-z0-9]+/i', $searchBlob) ?: [],
                    fn (string $w) => mb_strlen($w) >= 3 && ! in_array($w, $stopWords, true)
                ));
                // Avoid letting a geographic word (e.g. "nepal" in "Nepal Oil Corporation") hijack non-travel brands with tourism icons
                $nonGeoTokens = array_values(array_filter($rawTokens, fn (string $w) => ! in_array($w, $geoWords, true)));
                $tokens = (! $isTravelIntent && $nonGeoTokens !== []) ? $nonGeoTokens : $rawTokens;

                // Expand domain synonyms so brands find crisp emblems and matching assets
                $synonyms = [
                    'oil' => ['drop', 'flame', 'energy', 'fuel', 'leaf', 'wave'],
                    'fuel' => ['flame', 'drop', 'energy', 'bolt'],
                    'energy' => ['bolt', 'flame', 'sun', 'leaf', 'power'],
                    'tech' => ['prism', 'cube', 'circuit', 'code', 'hex'],
                    'web' => ['globe', 'code', 'prism', 'spark'],
                    'finance' => ['shield', 'crown', 'pillar', 'chart', 'diamond'],
                    'food' => ['restaurant', 'cafe', 'leaf', 'fork', 'cup', 'plate', 'asian'],
                    'restaurant' => ['food', 'cafe', 'asian', 'dining', 'cup', 'chef', 'plate'],
                    'cafe' => ['coffee', 'latte', 'espresso', 'cup', 'mug', 'brew', 'food', 'restaurant'],
                    'coffee' => ['cafe', 'latte', 'espresso', 'cup', 'bean', 'mug', 'brew'],
                    'bakery' => ['bread', 'cake', 'pastry', 'wheat', 'cup', 'cafe'],
                    'tea' => ['cup', 'leaf', 'brew', 'teapot'],
                    'bar' => ['cocktail', 'glass', 'bottle', 'night', 'music'],
                    'pizza' => ['slice', 'oven', 'italian', 'restaurant'],
                    'burger' => ['grill', 'restaurant', 'fast', 'food'],
                ];
                $expandedTokens = $tokens;
                foreach ($tokens as $tok) {
                    $stem = str_ends_with($tok, 's') && strlen($tok) > 4 ? substr($tok, 0, -1) : $tok;
                    if (isset($synonyms[$stem])) {
                        $expandedTokens = array_merge($expandedTokens, $synonyms[$stem]);
                    }
                }
                $expandedTokens = array_values(array_unique(array_slice($expandedTokens, 0, 12)));

                if ($expandedTokens !== []) {
                    $matched = LogoAsset::query()
                        ->active()
                        ->when($format->layout === 'stamp', fn ($q) => $q->where('asset_type', 'svg'))
                        ->when(! $isTravelIntent, fn ($q) => $q->where('slug', 'not like', '%travel%')->where('slug', 'not like', '%tour%')->where('slug', 'not like', '%cruise%')->where('slug', 'not like', '%jet%')->where('slug', 'not like', '%airplane%')->where('slug', 'not like', '%beach%')->where('slug', 'not like', '%vacation%'))
                        ->where(function ($q) use ($expandedTokens) {
                            foreach ($expandedTokens as $tok) {
                                $stem = str_ends_with($tok, 's') && strlen($tok) > 4 ? substr($tok, 0, -1) : $tok;
                                $q->orWhere('slug', 'like', "%{$stem}%")
                                    ->orWhere('name', 'like', "%{$stem}%")
                                    ->orWhereJsonContains('keywords', $stem)
                                    ->orWhereJsonContains('industry_slugs', $stem);
                            }
                        })
                        ->reorder()
                        ->orderByRaw("CASE WHEN asset_type = 'png' THEN 0 ELSE 1 END")
                        ->orderByRaw("CASE WHEN slug LIKE 'custom-%' THEN 0 ELSE 1 END")
                        ->orderBy('sort_order')
                        ->limit(16)
                        ->get();

                    if ($matched->isNotEmpty()) {
                        $input['symbol'] = $toSymbolPayload($matched->first());
                        $input['symbol_pool'] = [$input['symbol']];
                    }
                }

                // Always fall back to clean palette-adaptable vector marks (or travel PNGs if travel intent)
                if ($input['symbol_pool'] === []) {
                    $fallbackAssets = LogoAsset::query()
                        ->active()
                        ->when($format->layout === 'stamp', fn ($q) => $q->where('asset_type', 'svg'))
                        ->when(! $isTravelIntent, fn ($q) => $q->where('slug', 'not like', '%travel%')->where('slug', 'not like', '%cable%')->where('slug', 'not like', '%torii%')->where('slug', 'not like', '%kayak%'))
                        ->reorder()
                        ->orderByRaw($isTravelIntent
                            ? "CASE WHEN asset_type = 'png' THEN 0 ELSE 1 END"
                            : "CASE WHEN slug LIKE 'custom-%' THEN 0 ELSE 1 END")
                        ->orderBy('sort_order')
                        ->limit(16)
                        ->get();
                    if ($fallbackAssets->isNotEmpty()) {
                        $input['symbol'] = $toSymbolPayload($fallbackAssets->first());
                        $input['symbol_pool'] = [$input['symbol']];
                    }
                }
            }
        }

        $isGrid = in_array($format->layout, ['card', 'stamp', 'banner', 'social', 'icon', 'story'], true);
        $totalPages = $isGrid ? 16 : 15;
        $page = max(1, min($totalPages, (int) ($validated['page'] ?? 1)));

        if ($isGrid) {
            // Page 1 shows Upload Card + 23 designs (24 tiles = 6 exact 4-col rows); Pages 2..16 show 24 designs each.
            $count = isset($validated['count']) ? (int) $validated['count'] : ($page === 1 ? 23 : 24);
            $offset = $page === 1 ? 0 : 23 + ($page - 2) * 24;
            $totalAvailable = 23 + ($totalPages - 1) * 24; // 383 unique designs
        } else {
            $count = (int) ($validated['count'] ?? 8);
            $offset = ($page - 1) * $count;
            $totalAvailable = $totalPages * $count;
        }

        $input['page'] = $page;
        $input['offset'] = $offset;
        $concepts = $this->engine->concepts($format, $input, $count);

        $payload = [
            'data' => [
                'format' => [
                    'slug' => $format->slug,
                    'name' => $format->name,
                    'width' => $format->width,
                    'height' => $format->height,
                    'category' => $format->category,
                ],
                'concepts' => $concepts,
            ],
            'meta' => [
                'count' => count($concepts),
                'page' => $page,
                'per_page' => $count,
                'from' => $offset + 1,
                'to' => $offset + count($concepts),
                'total_available' => $totalAvailable,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_more' => $page < $totalPages,
                'generated_at' => now()->toIso8601String(),
            ],
        ];

        $json = (string) json_encode($payload);
        if ($payloadCacheKey !== null) {
            Cache::put($payloadCacheKey, $json, 300);
        }

        return JsonResponse::fromJsonString($json)->header('X-Cache', 'MISS');
    }

    /**
     * Download a generated design as SVG, PNG or PDF.
     */
    public function export(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'design_json' => ['required', 'array'],
            'format' => ['required', 'string', 'max:80'],
            'file' => ['nullable', 'string', 'in:svg,png,pdf'],
            'filename' => ['nullable', 'string', 'max:80'],
            'transparent' => ['nullable', 'boolean'],
        ]);

        $document = new DesignDocument($validated['design_json']);
        $errors = $document->validate();

        if ($errors !== []) {
            throw ValidationException::withMessages(['design_json' => $errors]);
        }

        $design = $document->toArray();
        $canvas = $design['canvas'] ?? [];
        $width = (int) ($canvas['width'] ?? 1080);
        $height = (int) ($canvas['height'] ?? 1080);
        $svg = $this->renderer->sanitize($this->renderer->render($design, [
            'transparent' => (bool) ($validated['transparent'] ?? false),
        ]));

        $file = $validated['file'] ?? 'svg';
        $slug = Str::slug($validated['filename'] ?? $validated['format']) ?: 'design';
        $families = $this->fontFamilies($design);

        if ($file === 'svg') {
            return $this->download($svg, 'image/svg+xml', "{$slug}.svg");
        }

        if (! $this->rasterizer->available()) {
            return response()->json([
                'message' => 'Raster export is unavailable because no headless browser is configured. SVG export still works.',
                'svg_available' => true,
            ], 503);
        }

        try {
            if ($file === 'png') {
                $bytes = $this->rasterizer->pngSized($svg, $families, $width, $height, (bool) ($validated['transparent'] ?? false) ? null : '#FFFFFF');

                return $this->download($bytes, 'image/png', "{$slug}.png");
            }

            $bytes = $this->rasterizer->pdfSized($svg, $families, $width, $height, '#FFFFFF');

            return $this->download($bytes, 'application/pdf', "{$slug}.pdf");
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    private function download(string $bytes, string $mime, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * @param  array<string, mixed>  $design
     * @return array<int, string>
     */
    private function fontFamilies(array $design): array
    {
        $families = [];

        $walk = function (array $objects) use (&$walk, &$families): void {
            foreach ($objects as $object) {
                if (($object['type'] ?? null) === 'i-text' && isset($object['fontFamily'])) {
                    $families[] = (string) $object['fontFamily'];
                }

                if (! empty($object['objects'])) {
                    $walk($object['objects']);
                }
            }
        };

        $walk($design['objects'] ?? []);

        return array_values(array_unique($families));
    }
}
