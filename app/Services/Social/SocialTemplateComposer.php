<?php

namespace App\Services\Social;

use App\Models\LogoAsset;
use App\Models\SocialDesignType;
use App\Services\Design\DesignFormat;
use App\Services\Design\FormatComposer;
use App\Services\Logo\SvgRenderingService;

/**
 * Turns a social design type plus brand inputs into the canonical editable
 * design document used by the editor, previews and exports.
 *
 * It deliberately delegates layout composition to the existing FormatComposer,
 * so social templates share the same renderer as the logo and design studios.
 */
class SocialTemplateComposer
{
    public function __construct(
        private readonly FormatComposer $composer,
        private readonly SvgRenderingService $renderer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function compose(SocialDesignType $type, array $input = []): array
    {
        $format = $this->format($type);
        $design = $this->composer->compose($format, $this->normalizeInput($input));

        $design['meta'] = array_merge($design['meta'] ?? [], [
            'platform' => $type->platform?->slug,
            'design_type' => $type->slug,
            'safe_area' => $type->safe_area ?? [],
            'engine' => 'social-template-v1',
        ]);

        return $design;
    }

    public function format(SocialDesignType $type): DesignFormat
    {
        return new DesignFormat(
            slug: 'social-'.$type->slug,
            name: $type->name,
            category: $this->category($type),
            width: (int) $type->width,
            height: (int) $type->height,
            layout: $type->layout_family ?: $this->inferLayout($type),
            variant: $type->variant ?: $this->inferVariant($type),
            fieldKeys: ['headline', 'subhead', 'cta', 'handle', 'website', 'tagline', 'date', 'price', 'discount', 'code'],
            defaults: [
                'headline' => 'Grow with confidence',
                'subhead' => 'A short line that explains your offer.',
                'cta' => 'Learn more',
                'handle' => '@yourbrand',
                'website' => 'yourbrand.com',
                'tagline' => 'Independent studio',
                'date' => '12 June 2027',
                'price' => '$49',
                'discount' => '20% OFF',
                'code' => 'SAVE20',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $design
     */
    public function render(array $design): string
    {
        return $this->renderer->sanitize($this->renderer->render($design, ['crop' => true]));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $palette = $input['palette'] ?? $input['colors'] ?? [];
        $colors = is_array($palette)
            ? array_values(array_filter($palette, fn ($color) => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)))
            : [];

        $symbol = $input['symbol'] ?? null;

        if ($symbol instanceof LogoAsset) {
            $symbol = [
                'asset_type' => $symbol->asset_type ?? 'svg',
                'image_url' => $symbol->resolvedImageDataUri() ?? $symbol->image_url,
                'view_box' => $symbol->view_box,
                'paths' => $symbol->paths ?? [],
            ];
        }

        return array_merge($input, [
            'brand' => (string) ($input['brand'] ?? $input['business_name'] ?? $input['name'] ?? 'Your brand'),
            'colors' => $colors,
            'font' => (string) ($input['font'] ?? $input['font_family'] ?? 'Poppins'),
            'style' => (string) ($input['style'] ?? 'modern'),
            'symbol' => is_array($symbol) ? $symbol : null,
            'logo_image' => is_string($input['logo_image'] ?? null) ? $input['logo_image'] : null,
        ]);
    }

    private function category(SocialDesignType $type): string
    {
        $slug = $type->slug;

        if (str_contains($slug, 'profile') || str_contains($slug, 'avatar')) {
            return 'profiles';
        }

        if (str_contains($slug, 'cover') || str_contains($slug, 'banner') || str_contains($slug, 'header') || str_contains($slug, 'event')) {
            return 'covers';
        }

        if ($type->height > $type->width * 1.2) {
            return 'posts';
        }

        return 'social';
    }

    private function inferLayout(SocialDesignType $type): string
    {
        $slug = $type->slug;

        if (preg_match('/story|status|reel|idea-pin|tiktok/', $slug)) {
            return 'story';
        }

        if (preg_match('/cover|banner|header|event|end-screen/', $slug)) {
            return 'banner';
        }

        return 'social';
    }

    private function inferVariant(SocialDesignType $type): string
    {
        $slug = $type->slug;

        return match (true) {
            (bool) preg_match('/story|status|reel|idea-pin|tiktok/', $slug) => 'story',
            (bool) preg_match('/cover|banner|header|event/', $slug) => 'cover',
            str_contains($slug, 'thumbnail') => 'thumbnail',
            str_contains($slug, 'carousel') => 'carousel',
            str_contains($slug, 'advert') || str_contains($slug, '-ad') => 'ad',
            default => 'post',
        };
    }
}
