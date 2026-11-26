<?php

namespace App\Services\Social;

use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\LogoAsset;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVariation;
use App\Models\User;
use App\Support\Color;
use Illuminate\Support\Collection;

/**
 * Generates professional variations from a master template.
 *
 * Variations only swap compatible palettes, fonts, styles and symbols, then
 * re-run the same composer. Similarity detection prevents near-duplicates.
 */
class SocialTemplateVariationService
{
    public function __construct(
        private readonly SocialTemplateComposer $composer,
        private readonly SocialTemplateQualityService $quality,
    ) {
    }

    /**
     * @return Collection<int, SocialTemplateVariation>
     */
    public function generate(SocialTemplate $master, int $count = 6, ?User $creator = null): Collection
    {
        $master->loadMissing(['designType.platform']);
        $type = $master->designType;

        if ($type === null) {
            return collect();
        }

        $count = max(1, min(24, $count));
        $palettes = ColorPalette::query()->active()->get();
        $fonts = Font::query()->active()->get();
        $styles = is_array($master->styles) && $master->styles !== []
            ? array_values($master->styles)
            : ['minimal', 'modern', 'editorial', 'bold', 'luxury', 'gradient'];

        $copy = $this->copyFrom($master->design_json ?? []);
        $symbol = $this->symbolFor($master);
        $existing = SocialTemplateVariation::query()
            ->where('template_id', $master->id)
            ->pluck('fingerprint')
            ->all();
        $existing[] = (string) $master->fingerprint;

        $created = collect();
        $attempts = 0;
        $maxAttempts = max($count * 6, 24);

        while ($created->count() < $count && $attempts < $maxAttempts) {
            $palette = $palettes->isNotEmpty() ? $palettes[$attempts % $palettes->count()] : null;
            $font = $fonts->isNotEmpty() ? $fonts[($attempts * 3) % $fonts->count()] : null;
            $style = $styles[$attempts % count($styles)];

            $colors = $palette?->colors ?? ($master->palette ?? []);
            $fontFamily = $font?->family ?? ($master->fonts[0] ?? 'Poppins');

            $attempts++;

            if ($this->incompatible($copy['brand'] ?? '', $fontFamily, $colors)) {
                continue;
            }

            $design = $this->composer->compose($type, array_merge($copy, [
                'colors' => $colors,
                'font' => $fontFamily,
                'style' => $style,
                'symbol' => $symbol,
            ]));

            $fingerprint = SocialTemplateFingerprint::for($design);

            if (in_array($fingerprint, $existing, true)) {
                continue;
            }

            $report = $this->quality->inspect($design, $type);

            if ($report['score'] < 55) {
                continue;
            }

            $existing[] = $fingerprint;

            $created->push(SocialTemplateVariation::create([
                'template_id' => $master->id,
                'name' => $master->name.' — '.ucfirst($style),
                'design_json' => $design,
                'svg' => $this->composer->render($design),
                'fingerprint' => $fingerprint,
                'status' => 'draft',
                'meta' => [
                    'palette' => $colors,
                    'font' => $fontFamily,
                    'style' => $style,
                    'quality' => $report,
                ],
                'quality_score' => $report['score'],
                'created_by' => $creator?->id,
            ]));
        }

        return $created;
    }

    public function approve(SocialTemplateVariation $variation): SocialTemplateVariation
    {
        $variation->update(['status' => 'approved']);

        return $variation->fresh();
    }

    /**
     * Promote an approved variation into a standalone template.
     */
    public function publish(SocialTemplateVariation $variation, SocialTemplateService $templates, ?User $creator = null): SocialTemplate
    {
        $master = $variation->template;

        return $templates->create([
            'name' => $variation->name,
            'description' => $master->description,
            'design_type_id' => $master->design_type_id,
            'industry_id' => $master->industry_id,
            'category_id' => $master->category_id,
            'styles' => $master->styles,
            'keywords' => $master->keywords,
            'tags' => $master->tags,
            'palette' => $variation->meta['palette'] ?? $master->palette,
            'fonts' => [$variation->meta['font'] ?? ($master->fonts[0] ?? 'Poppins')],
            'design_json' => $variation->design_json,
            'svg' => $variation->svg,
            'license_type' => $master->license_type,
            'is_premium' => $master->is_premium,
            'status' => SocialTemplate::STATUS_PUBLISHED,
            'source' => 'variation',
            'ai_meta' => $variation->meta,
        ], $creator);
    }

    /**
     * @param  array<string, mixed>  $design
     * @return array<string, string>
     */
    private function copyFrom(array $design): array
    {
        $copy = [
            'brand' => 'Your brand',
            'headline' => 'Grow with confidence',
            'subhead' => 'A short line that explains your offer.',
            'cta' => 'Learn more',
            'handle' => '@yourbrand',
            'website' => 'yourbrand.com',
            'tagline' => 'Independent studio',
        ];

        foreach (($design['objects'] ?? []) as $object) {
            if (($object['type'] ?? null) !== 'i-text') {
                continue;
            }

            $role = (string) ($object['role'] ?? '');
            $text = trim((string) ($object['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            if ($role === 'primary-text') {
                $copy['brand'] = $text;
                $copy['headline'] = $text;
            } elseif ($role === 'new-text') {
                $copy['subhead'] = $text;
            }
        }

        return $copy;
    }

    private function symbolFor(SocialTemplate $master): ?array
    {
        $slug = $master->asset_refs['symbol_slug'] ?? null;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $asset = LogoAsset::query()->active()->where('slug', $slug)->first();

        if ($asset === null) {
            return null;
        }

        return [
            'asset_type' => $asset->asset_type ?? 'svg',
            'image_url' => $asset->resolvedImageDataUri() ?? $asset->image_url,
            'view_box' => $asset->view_box,
            'paths' => $asset->paths ?? [],
        ];
    }

    /**
     * @param  array<int, string>  $colors
     */
    private function incompatible(string $brand, string $fontFamily, array $colors): bool
    {
        $font = Font::query()->where('family', $fontFamily)->first();

        if ($font?->category === 'script' && mb_strlen($brand) > 14) {
            return true;
        }

        $ink = $colors[0] ?? '#111827';
        $accent = $colors[2] ?? $colors[1] ?? '#2563EB';

        return Color::contrast($ink, $accent) < 2.6;
    }
}
