<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Admin\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'general' => $this->group('general', $this->generalDefaults()),
                'storage' => $this->group('storage', $this->storageDefaults()),
                'export' => $this->group('export', $this->exportDefaults()),
                'platform' => $this->group('platform', $this->platformDefaults()),
                'features' => $this->features()->getData(true)['data'],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'group' => ['required', 'in:general,storage,export,platform'],
            'settings' => ['required', 'array'],
        ]);

        $settings = array_merge($this->group($data['group'], []), $data['settings']);
        SiteSetting::put($data['group'], $settings, $data['group']);

        $this->activity->log('settings.updated', null, "Updated {$data['group']} settings.", $data['settings']);

        return response()->json(['data' => $settings]);
    }

    public function features(): JsonResponse
    {
        $saved = SiteSetting::get('features', []);
        $features = collect($this->featureDefaults())->map(function (array $feature, string $key) use ($saved) {
            return [
                'key' => $key,
                'label' => $feature['label'],
                'description' => $feature['description'],
                'enabled' => (bool) ($saved[$key] ?? $feature['enabled']),
            ];
        })->values();

        return response()->json(['data' => $features]);
    }

    public function updateFeatures(Request $request): JsonResponse
    {
        $data = $request->validate([
            'features' => ['required', 'array'],
            'features.*' => ['boolean'],
        ]);

        $allowed = array_keys($this->featureDefaults());
        $features = collect($data['features'])->only($allowed)->map(fn ($value) => (bool) $value)->all();
        SiteSetting::put('features', $features, 'features');
        $this->activity->log('features.updated', null, 'Updated feature availability.', $features);

        return response()->json(['data' => $this->features()->getData(true)['data']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function group(string $group, array $defaults): array
    {
        $saved = SiteSetting::get($group, []);

        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    /**
     * @return array<string, mixed>
     */
    private function generalDefaults(): array
    {
        return [
            'site_name' => 'Logo Design Nepal',
            'site_description' => 'Professional custom logo and brand identity design studio in Nepal.',
            'contact_email' => 'info@logodesignnepal.com',
            'contact_phone' => '+977-9800000000',
            'contact_whatsapp' => '+9779800000000',
            'default_language' => 'en',
            'timezone' => config('app.timezone', 'Asia/Kathmandu'),
            'logo_url' => '/logodesignnepal/logo.png',
            'favicon_url' => '/brand/favicon.ico',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageDefaults(): array
    {
        return [
            'disk' => config('filesystems.default', 'public'),
            'max_upload_mb' => 10,
            'allowed_formats' => ['png', 'jpg', 'jpeg', 'webp', 'svg'],
            'optimize_images' => true,
            'thumbnail_width' => 480,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function exportDefaults(): array
    {
        return [
            'formats' => ['png', 'jpg', 'webp', 'svg', 'pdf'],
            'default_format' => 'png',
            'quality' => 92,
            'max_dimension' => 4096,
            'transparent_backgrounds' => true,
            'svg_export' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function platformDefaults(): array
    {
        return [
            'default_canvas_width' => 1080,
            'default_canvas_height' => 1080,
            'safe_area_percent' => 5,
            'allow_guest_generation' => true,
            'maintenance_message' => null,
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, enabled: bool}>
     */
    private function featureDefaults(): array
    {
        return [
            'logo_generator' => ['label' => 'Logo generator', 'description' => 'AI-assisted logo generation and editing.', 'enabled' => true],
            'social_generator' => ['label' => 'Social media generator', 'description' => 'Social posts, covers, stories and resizing.', 'enabled' => true],
            'tshirt_designer' => ['label' => 'T-shirt designer', 'description' => 'Merchandise and apparel design tools.', 'enabled' => true],
            'poster_generator' => ['label' => 'Poster generator', 'description' => 'Poster, flyer and print design tools.', 'enabled' => true],
            'youtube_thumbnail' => ['label' => 'YouTube thumbnails', 'description' => 'Thumbnail and channel art generation.', 'enabled' => true],
            'ai_image_generation' => ['label' => 'AI image generation', 'description' => 'Generate raster illustrations and backgrounds.', 'enabled' => true],
            'template_variations' => ['label' => 'Template variations', 'description' => 'Generate and review template variants.', 'enabled' => true],
            'brand_kits' => ['label' => 'Brand kits', 'description' => 'Coordinated multi-format brand generation.', 'enabled' => true],
            'web_icons' => ['label' => 'Web icon kit', 'description' => 'Favicon and web icon exports.', 'enabled' => true],
            'mockups' => ['label' => 'Mockups', 'description' => 'Preview designs in realistic contexts.', 'enabled' => true],
        ];
    }
}
