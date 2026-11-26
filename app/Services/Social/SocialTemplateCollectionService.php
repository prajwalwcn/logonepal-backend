<?php

namespace App\Services\Social;

use App\Models\SocialDesignType;
use App\Models\SocialTemplate;
use App\Models\User;
use App\Models\UserDesign;
use Illuminate\Support\Facades\DB;

/**
 * Builds a coordinated multi-format brand kit from one brand brief.
 *
 * It reuses published templates as the art direction and re-composes each
 * format with the supplied brand name, palette and type, then stores ordinary
 * UserDesign records so the existing editor opens them without special cases.
 */
class SocialTemplateCollectionService
{
    public function __construct(
        private readonly SocialTemplateComposer $composer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{name: string, brand: string, designs: array<int, array<string, mixed>>}
     */
    public function generateForUser(User $user, array $input): array
    {
        $brand = trim((string) ($input['brand'] ?? 'Your brand'));
        $colors = is_array($input['palette'] ?? null) ? array_values($input['palette']) : [];
        $font = (string) ($input['font'] ?? 'Poppins');
        $style = (string) ($input['style'] ?? 'modern');
        $slugs = is_array($input['design_types'] ?? null) ? $input['design_types'] : [];

        $types = SocialDesignType::query()
            ->with('platform')
            ->where('is_active', true)
            ->when($slugs !== [], fn ($query) => $query->whereIn('slug', $slugs))
            ->orderBy('sort_order')
            ->limit(12)
            ->get();

        if ($types->isEmpty()) {
            $types = SocialDesignType::query()->with('platform')->where('is_active', true)->orderBy('sort_order')->limit(8)->get();
        }

        $templates = SocialTemplate::query()
            ->published()
            ->with(['designType', 'platform'])
            ->when($input['industry_id'] ?? null, fn ($query, $industry) => $query->where('industry_id', $industry))
            ->get()
            ->groupBy('design_type_id');

        return DB::transaction(function () use ($user, $types, $templates, $brand, $colors, $font, $style) {
            $designs = [];

            foreach ($types as $type) {
                $template = $templates->get($type->id)?->first();

                $design = $template !== null
                    ? $this->applyBrand($template->design_json ?? [], $brand, $colors, $font, $style)
                    : $this->composer->compose($type, [
                        'brand' => $brand,
                        'colors' => $colors,
                        'font' => $font,
                        'style' => $style,
                    ]);

                $svg = $this->composer->render($design);

                $saved = UserDesign::create([
                    'user_id' => $user->id,
                    'generation_id' => null,
                    'generated_logo_id' => null,
                    'name' => $brand.' — '.$type->name,
                    'business_name' => mb_substr($brand, 0, 120),
                    'tagline' => null,
                    'format' => $type->slug,
                    'design_json' => $design,
                    'svg' => $svg,
                    'is_draft' => false,
                    'last_edited_at' => now(),
                ]);

                $designs[] = [
                    'uuid' => $saved->uuid,
                    'name' => $saved->name,
                    'format' => $type->slug,
                    'platform' => $type->platform?->slug,
                    'width' => $type->width,
                    'height' => $type->height,
                    'svg' => $svg,
                    'edit_href' => '/editor?design='.$saved->uuid,
                ];
            }

            return [
                'name' => $brand.' brand kit',
                'brand' => $brand,
                'designs' => $designs,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $design
     * @param  array<int, string>  $colors
     * @return array<string, mixed>
     */
    private function applyBrand(array $design, string $brand, array $colors, string $font, string $style): array
    {
        $oldPalette = is_array($design['meta']['palette'] ?? null) ? $design['meta']['palette'] : [];
        $newPalette = $colors !== [] ? $colors : $oldPalette;

        foreach (($design['objects'] ?? []) as $index => $object) {
            if (! is_array($object)) {
                continue;
            }

            $role = (string) ($object['role'] ?? '');

            if (($object['type'] ?? null) === 'i-text') {
                if ($role === 'primary-text') {
                    $object['text'] = $brand;
                } elseif ($role === 'new-text' && trim((string) ($object['text'] ?? '')) !== '') {
                    $object['text'] = 'Brand identity that works everywhere.';
                }

                if (isset($object['fontFamily'])) {
                    $object['fontFamily'] = $font;
                }
            }

            if (isset($object['fill']) && is_string($object['fill']) && $newPalette !== []) {
                $indexInOld = array_search(strtoupper($object['fill']), array_map('strtoupper', $oldPalette), true);
                if ($indexInOld !== false && isset($newPalette[$indexInOld])) {
                    $object['fill'] = $newPalette[$indexInOld];
                }
            }

            $design['objects'][$index] = $object;
        }

        $design['meta'] = array_merge($design['meta'] ?? [], [
            'palette' => $newPalette,
            'font' => $font,
            'style' => $style,
            'brand' => $brand,
        ]);

        return $design;
    }
}
