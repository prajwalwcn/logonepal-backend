<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ColorPalette;
use App\Models\DesignPreset;
use App\Models\LayoutPreset;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVersion;
use App\Models\TypographyPreset;
use App\Services\Admin\AdminActivityLogger;
use App\Services\Social\TemplateBeautifierService;
use App\Support\Color;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DesignEngineAdminController extends Controller
{
    public function __construct(
        private readonly TemplateBeautifierService $beautifier,
        private readonly AdminActivityLogger $activity,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'design_presets' => DesignPreset::with(['colorPalette', 'typographyPreset', 'layoutPreset'])
                    ->orderBy('sort_order')
                    ->get(),
                'typography_presets' => TypographyPreset::orderBy('sort_order')->get(),
                'layout_presets' => LayoutPreset::orderBy('sort_order')->get(),
                'color_palettes' => ColorPalette::orderBy('sort_order')->get(),
                'templates' => SocialTemplate::with(['platform:id,name,slug', 'designType:id,name,slug,width,height'])
                    ->orderByDesc('updated_at')
                    ->get(['id', 'name', 'slug', 'platform_id', 'design_type_id', 'width', 'height', 'version', 'quality_score', 'status', 'svg', 'updated_at']),
            ],
        ]);
    }

    public function storePreset(Request $request, string $type): JsonResponse
    {
        $record = match ($type) {
            'design' => $this->saveDesignPreset($request),
            'typography' => $this->saveTypographyPreset($request),
            'layout' => $this->saveLayoutPreset($request),
            'palette' => $this->savePalette($request),
            default => abort(404, 'Unknown preset type.'),
        };

        $this->activity->log("design_engine.{$type}.created", $record, "Created {$type} preset {$record->name}.");

        return response()->json(['data' => $record], 201);
    }

    public function updatePreset(Request $request, string $type, int $id): JsonResponse
    {
        $record = match ($type) {
            'design' => $this->saveDesignPreset($request, DesignPreset::findOrFail($id)),
            'typography' => $this->saveTypographyPreset($request, TypographyPreset::findOrFail($id)),
            'layout' => $this->saveLayoutPreset($request, LayoutPreset::findOrFail($id)),
            'palette' => $this->savePalette($request, ColorPalette::findOrFail($id)),
            default => abort(404, 'Unknown preset type.'),
        };

        $this->activity->log("design_engine.{$type}.updated", $record, "Updated {$type} preset {$record->name}.");

        return response()->json(['data' => $record]);
    }

    public function duplicatePreset(string $type, int $id): JsonResponse
    {
        $source = match ($type) {
            'design' => DesignPreset::findOrFail($id),
            'typography' => TypographyPreset::findOrFail($id),
            'layout' => LayoutPreset::findOrFail($id),
            'palette' => ColorPalette::findOrFail($id),
            default => abort(404, 'Unknown preset type.'),
        };

        $copy = $source->replicate();
        $copy->name = $source->name.' (Copy)';
        $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(4));
        $copy->save();

        return response()->json(['data' => $copy], 201);
    }

    public function destroyPreset(string $type, int $id): JsonResponse
    {
        $record = match ($type) {
            'design' => DesignPreset::findOrFail($id),
            'typography' => TypographyPreset::findOrFail($id),
            'layout' => LayoutPreset::findOrFail($id),
            'palette' => ColorPalette::findOrFail($id),
            default => abort(404, 'Unknown preset type.'),
        };

        $record->delete();

        return response()->json(['message' => 'Preset deleted.']);
    }

    public function beautifyPreview(Request $request, SocialTemplate $template): JsonResponse
    {
        $options = $this->validateBeautifyOptions($request);

        return response()->json([
            'data' => $this->beautifier->preview($template, $options),
        ]);
    }

    public function beautifyApply(Request $request, SocialTemplate $template): JsonResponse
    {
        $options = $this->validateBeautifyOptions($request);
        $updated = $this->beautifier->apply($template, $options, $request->user());

        $this->activity->log('template.beautified', $updated, "Beautified template {$updated->name} (v{$updated->version}).");

        return response()->json(['data' => $updated]);
    }

    public function versions(SocialTemplate $template): JsonResponse
    {
        $versions = SocialTemplateVersion::where('template_id', $template->id)
            ->orderByDesc('version')
            ->get(['id', 'template_id', 'version', 'name', 'changelog', 'quality_report', 'svg', 'created_at']);

        return response()->json(['data' => $versions]);
    }

    public function restoreVersion(Request $request, SocialTemplate $template, SocialTemplateVersion $version): JsonResponse
    {
        if ((int) $version->template_id !== (int) $template->id) {
            abort(404, 'Version does not belong to this template.');
        }

        $restored = $this->beautifier->restoreVersion($template, $version, $request->user());
        $this->activity->log('template.version.restored', $restored, "Restored {$restored->name} from v{$version->version}.");

        return response()->json(['data' => $restored]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBeautifyOptions(Request $request): array
    {
        return $request->validate([
            'design_preset_id' => ['nullable', 'integer', 'exists:design_presets,id'],
            'color_palette_id' => ['nullable', 'integer', 'exists:color_palettes,id'],
            'typography_preset_id' => ['nullable', 'integer', 'exists:typography_presets,id'],
            'layout_preset_id' => ['nullable', 'integer', 'exists:layout_presets,id'],
            'fix_contrast' => ['sometimes', 'boolean'],
            'fit_overflow' => ['sometimes', 'boolean'],
            'enforce_safe_area' => ['sometimes', 'boolean'],
            'prune_decorations' => ['sometimes', 'boolean'],
        ]);
    }

    private function saveDesignPreset(Request $request, ?DesignPreset $existing = null): DesignPreset
    {
        $data = $request->validate([
            'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'style_key' => [$existing ? 'sometimes' : 'required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'color_palette_id' => ['nullable', 'integer', 'exists:color_palettes,id'],
            'typography_preset_id' => ['nullable', 'integer', 'exists:typography_presets,id'],
            'layout_preset_id' => ['nullable', 'integer', 'exists:layout_presets,id'],
            'heading_font' => ['nullable', 'string', 'max:120'],
            'body_font' => ['nullable', 'string', 'max:120'],
            'layout_type' => ['nullable', 'string', 'max:64'],
            'image_placement' => ['nullable', 'string', 'max:64'],
            'background_style' => ['nullable', 'string', 'max:64'],
            'shape_style' => ['nullable', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $existing) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
            return DesignPreset::create($data)->load(['colorPalette', 'typographyPreset', 'layoutPreset']);
        }

        $existing->update($data);
        return $existing->fresh(['colorPalette', 'typographyPreset', 'layoutPreset']);
    }

    private function saveTypographyPreset(Request $request, ?TypographyPreset $existing = null): TypographyPreset
    {
        $data = $request->validate([
            'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'heading_font' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'body_font' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'heading_weight' => ['nullable', 'integer', 'min:100', 'max:900'],
            'body_weight' => ['nullable', 'integer', 'min:100', 'max:900'],
            'scale_ratio' => ['nullable', 'numeric', 'min:1.05', 'max:2.5'],
            'line_height_heading' => ['nullable', 'numeric', 'min:0.8', 'max:2.5'],
            'line_height_body' => ['nullable', 'numeric', 'min:0.9', 'max:2.5'],
            'letter_spacing_heading' => ['nullable', 'integer', 'min:-100', 'max:400'],
            'letter_spacing_body' => ['nullable', 'integer', 'min:-100', 'max:400'],
            'text_alignment' => ['nullable', 'in:left,center,right'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $existing) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
            return TypographyPreset::create($data);
        }

        $existing->update($data);
        return $existing->fresh();
    }

    private function saveLayoutPreset(Request $request, ?LayoutPreset $existing = null): LayoutPreset
    {
        $data = $request->validate([
            'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'layout_type' => [$existing ? 'sometimes' : 'required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:500'],
            'alignment' => ['nullable', 'in:left,center,right'],
            'compatible_formats' => ['nullable', 'array'],
            'proportions' => [$existing ? 'sometimes' : 'required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (! $existing) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
            return LayoutPreset::create($data);
        }

        $existing->update($data);
        return $existing->fresh();
    }

    private function savePalette(Request $request, ?ColorPalette $existing = null): ColorPalette
    {
        $data = $request->validate([
            'name' => [$existing ? 'sometimes' : 'required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:64'],
            'mood' => ['nullable', 'string', 'max:64'],
            'industry_slug' => ['nullable', 'string', 'max:64'],
            'background_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'heading_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'body_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_dark' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $contrast = Color::contrast($data['heading_color'], $data['background_color']);
        if ($contrast < 3.0) {
            throw ValidationException::withMessages([
                'heading_color' => sprintf('Unreadable contrast ratio (%.2f:1). Heading vs background must be at least 3.0:1 (4.5:1 recommended).', $contrast),
            ]);
        }

        $data['colors'] = array_values(array_unique(array_filter([
            $data['heading_color'],
            $data['accent_color'],
            $data['background_color'],
            $data['body_color'] ?? $data['heading_color'],
        ])));
        $data['contrast_ratio'] = round($contrast, 2);

        if (! $existing) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
            return ColorPalette::create($data);
        }

        $existing->update($data);
        return $existing->fresh();
    }
}
