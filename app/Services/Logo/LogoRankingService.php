<?php

namespace App\Services\Logo;

use App\Models\Font;
use App\Models\LogoAsset;
use App\Support\Color;

/**
 * Scores a composed logo for relevance and craft. The score drives ordering,
 * and the hard rejection rules keep broken or duplicate combinations out of
 * the gallery entirely.
 */
class LogoRankingService
{
    public function __construct(private readonly TextMetrics $metrics)
    {
    }

    public function score(
        array $design,
        array $intent,
        ?LogoAsset $symbol,
        Font $font,
        array $paletteColors,
        string $layout,
        string $brandName,
    ): float {
        $score = 50.0;
        $category = $intent['category_slug'] ?? 'general';
        $styles = $intent['styles'] ?? [];
        $keywords = array_map('mb_strtolower', $intent['keywords'] ?? []);
        $explicitTerms = array_map('mb_strtolower', (array) ($intent['resolved_keywords'] ?? []));
        $hasSymbol = $this->findObject($design, 'logo') !== null;

        if ($symbol !== null && $hasSymbol) {
            if (in_array($category, $symbol->industry_slugs ?? [], true)) {
                $score += 18;
            }

            foreach ($symbol->style_slugs ?? [] as $style) {
                if (in_array($style, $styles, true)) {
                    $score += 5;
                }
            }

            $symbolKeywords = array_map('mb_strtolower', $symbol->keywords ?? []);
            $slugTokens = array_map('mb_strtolower', preg_split('/[^a-z0-9]+/i', (string) $symbol->slug) ?: []);
            $nameTokens = array_map('mb_strtolower', preg_split('/[^a-z0-9]+/i', (string) $symbol->name) ?: []);
            $allAssetTerms = array_values(array_unique(array_merge($symbolKeywords, $slugTokens, $nameTokens)));

            foreach ($explicitTerms as $term) {
                if (in_array($term, $nameTokens, true)) {
                    $score += 40;
                } elseif (in_array($term, $allAssetTerms, true)) {
                    $score += 35;
                }
            }

            if (($symbol->asset_type ?? 'svg') === 'png' && ! empty($symbol->image_url)) {
                $score += 34;
            } elseif ($symbol->view_box === '0 0 128 128' || count($symbol->paths ?? []) >= 4) {
                $score += 22;
            } elseif ($symbol->view_box === '0 0 512 512' || str_starts_with((string) $symbol->slug, 'game-icons-') || count($symbol->paths ?? []) >= 3) {
                $score += 14;
            } elseif (str_starts_with((string) $symbol->slug, 'flat-color-icons-') || count($symbol->paths ?? []) >= 2) {
                $score += 7;
            }

            foreach ($symbolKeywords as $keyword) {
                if (in_array($keyword, $keywords, true)) {
                    $score += 2.5;
                }
            }
        }

        $score += $this->layoutScore($layout, $brandName, $styles);
        $score += $this->contrastScore($design);
        $score += $this->balanceScore($design);
        $score += $this->opticalCraftScore($design, $symbol);
        $score += $this->fontStyleMatch($font, $styles);

        if ($layout === 'script-3d' && ! $hasSymbol) {
            $score += 36.0;
        }

        if ($symbol?->is_premium && $hasSymbol) {
            $score += 1.5;
        }

        return round($score, 2);
    }

    /**
     * Hard rejection rules: a candidate is only shown if it passes all of them.
     */
    public function isAcceptable(array $design, string $layout, string $brandName): bool
    {
        if (in_array($layout, ['icon-only'], true)) {
            return $this->findObject($design, 'logo') !== null;
        }

        $contrast = $this->textContrast($design);

        if ($contrast !== null && $contrast < 3.0) {
            return false;
        }

        $text = $this->findObject($design, 'primary-text');

        if ($text === null) {
            return false;
        }

        $fontSize = (float) ($text['fontSize'] ?? 0);

        if ($fontSize < 18) {
            return false;
        }

        // Reject layouts that clip the brand name outside the artboard.
        $width = (float) ($text['width'] ?? 0);
        $left = (float) ($text['left'] ?? 0);
        $originX = $text['originX'] ?? 'left';

        if ($width > 0 && $originX === 'left' && $left + $width > DesignDocument::CANVAS_WIDTH + 1) {
            return false;
        }

        if ($width > 0 && $originX === 'center' && $left + $width / 2 > DesignDocument::CANVAS_WIDTH + 1) {
            return false;
        }

        if ($originX === 'center' && $left - $width / 2 < -1) {
            return false;
        }

        $top = (float) ($text['top'] ?? 0);
        $lines = count(explode("\n", (string) ($text['text'] ?? '')));

        if ($top + $fontSize * 1.12 * $lines > DesignDocument::CANVAS_HEIGHT) {
            return false;
        }

        return true;
    }

    /**
     * Reject near-identical results: same look, different palette is still a
     * duplicate to the eye when symbol and layout are unchanged unless multiple
     * variations of an explicitly requested symbol pool are needed.
     *
     * @param  array<string, array<string, bool>|string>  $seen
     */
    public function isDuplicate(
        array $seen,
        ?string $symbolSlug,
        string $layout,
        array $paletteColors,
        bool $allowMultiplePalettes = false,
    ): bool {
        $paletteKey = implode('', array_map('strtoupper', array_slice($paletteColors, 0, 3)));
        $key = ($symbolSlug ?? 'none').'|'.$layout;

        if (! isset($seen[$key])) {
            return false;
        }

        if (is_array($seen[$key])) {
            if (isset($seen[$key][$paletteKey])) {
                return true;
            }

            return ! $allowMultiplePalettes && $seen[$key] !== [];
        }

        return $seen[$key] === $paletteKey;
    }

    private function layoutScore(string $layout, string $brandName, array $styles): float
    {
        $length = mb_strlen(trim($brandName));
        $score = 0.0;

        if ($length > 16 && in_array($layout, ['horizontal', 'icon-left', 'icon-right'], true)) {
            $score -= 4;
        }

        if ($length <= 8 && in_array($layout, ['monogram', 'badge', 'emblem', 'icon-left'], true)) {
            $score += 4;
        }

        if (in_array($layout, ['wordmark', 'text-only'], true)
            && array_intersect($styles, ['luxury', 'minimal', 'elegant', 'vintage']) !== []) {
            $score += 5;
        }

        if (in_array($layout, ['icon-top', 'seal', 'ribbon', 'medallion', 'script-3d'], true)) {
            $score += 6;
        }

        if ($layout === 'icon-bottom') {
            $score -= 4;
        }

        if (in_array($layout, ['shield', 'badge', 'emblem'], true)
            && array_intersect($styles, ['corporate', 'vintage', 'bold', 'tech']) !== []) {
            $score += 4;
        }

        return $score;
    }

    private function fontStyleMatch(Font $font, array $styles): float
    {
        if (array_intersect($styles, ['luxury', 'elegant', 'vintage']) !== [] && in_array($font->category, ['serif', 'display'], true)) {
            return 6.0;
        }

        if (array_intersect($styles, ['tech', 'futuristic', 'geometric']) !== [] && $font->category === 'sans') {
            return 5.0;
        }

        return 1.5;
    }

    private function contrastScore(array $design): float
    {
        $contrast = $this->textContrast($design);

        if ($contrast === null) {
            return 0.0;
        }

        return $contrast >= 7 ? 8.0 : ($contrast >= 4.5 ? 6.0 : 1.0);
    }

    private function textContrast(array $design): ?float
    {
        $text = $this->findObject($design, 'primary-text');
        $background = $this->findObject($design, 'background');

        if ($text === null) {
            return null;
        }

        $ink = $text['fill'] ?? null;

        if (! is_string($ink) || ! str_starts_with($ink, '#')) {
            return null;
        }

        $bg = '#FFFFFF';
        $frame = $this->findById($design, 'shape-ribbon') ?? $this->findById($design, 'shape-frame');
        $frameFill = $frame['fill'] ?? null;

        if (is_string($frameFill) && str_starts_with($frameFill, '#')) {
            $bg = $frameFill;
        } else {
            $fill = $background['fill'] ?? null;

            if (is_array($fill) && ($fill['type'] ?? '') === 'solid' && is_string($fill['color'] ?? null)) {
                $bg = $fill['color'];
            } elseif (is_string($fill) && str_starts_with($fill, '#')) {
                $bg = $fill;
            }
        }

        return Color::contrast($ink, $bg);
    }

    private function findById(array $design, string $id): ?array
    {
        foreach ($design['objects'] ?? [] as $object) {
            if (($object['id'] ?? null) === $id) {
                return $object;
            }
        }

        return null;
    }

    private function balanceScore(array $design): float
    {
        $symbol = $this->findObject($design, 'logo');
        $text = $this->findObject($design, 'primary-text');

        if ($symbol === null || $text === null) {
            return 0.0;
        }

        $symbolSize = ($symbol['type'] ?? null) === 'image'
            ? (float) ($symbol['width'] ?? 140) * (float) ($symbol['scaleX'] ?? 1)
            : (float) ($symbol['scaleX'] ?? 1) * (float) ($symbol['pathBox'] ?? 64);
        $fontSize = (float) ($text['fontSize'] ?? 32);
        $ratio = $symbolSize / max(1.0, $fontSize);
        $layout = (string) ($design['meta']['layout'] ?? '');

        if (in_array($layout, ['seal', 'ribbon'], true) && $ratio >= 1.5 && $ratio <= 8.5) {
            return 6.0;
        }

        if ($ratio >= 1.05 && $ratio <= 4.2) {
            return 6.0;
        }

        return $ratio > 5.0 ? -6.0 : 1.0;
    }

    /**
     * Optical craft & small-size legibility score ported from logo-design principles:
     * rewards near-square symbol footprints, readable taglines (>= 14px), and
     * strong symbol-to-backdrop contrast (>= 3.0:1).
     */
    private function opticalCraftScore(array $design, ?LogoAsset $symbol): float
    {
        $bonus = 0.0;

        if ($symbol !== null && is_string($symbol->view_box)) {
            $parts = preg_split('/[\s,]+/', trim($symbol->view_box)) ?: [];
            $w = (float) ($parts[2] ?? 0);
            $h = (float) ($parts[3] ?? 0);
            if ($w > 0 && $h > 0) {
                $aspect = $w / $h;
                if ($aspect >= 0.78 && $aspect <= 1.28) {
                    $bonus += 3.0;
                }
            }
        }

        $tagline = $this->findObject($design, 'new-text');
        if ($tagline !== null) {
            $tagSize = (float) ($tagline['fontSize'] ?? 0);
            $bonus += $tagSize >= 15.0 ? 2.0 : ($tagSize < 12.0 ? -3.0 : 0.0);
        }

        return $bonus;
    }

    private function findObject(array $design, string $role): ?array
    {
        foreach ($design['objects'] ?? [] as $object) {
            if (($object['role'] ?? null) === $role) {
                return $object;
            }
        }

        return null;
    }
}
