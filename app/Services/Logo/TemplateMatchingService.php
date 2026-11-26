<?php

namespace App\Services\Logo;

use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\LogoAsset;
use App\Support\Color;
use Illuminate\Support\Collection;

/**
 * Scores the design primitives available in the catalogue against a brand's
 * intelligence profile. Everything is loaded once per request and scored in
 * memory so a generation never issues per-candidate queries.
 */
class TemplateMatchingService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, LogoAsset>
     */
    public function symbols(array $intent, array $filters = []): Collection
    {
        return $this->matchSymbols($intent, $filters)['assets'];
    }

    /**
     * Score symbols and interleave direct customer-need keyword matches at the front of the pool.
     *
     * @param  array<string, mixed>  $intent
     * @param  array<string, mixed>  $filters
     * @return array{assets: Collection<int, LogoAsset>, direct_count: int, direct_terms: array<int, string>}
     */
    public function matchSymbols(array $intent, array $filters = []): array
    {
        $assets = LogoAsset::query()->active()->get();
        $category = $intent['category_slug'] ?? 'general';
        $styles = $intent['styles'] ?? [];
        $keywords = array_map('mb_strtolower', $intent['keywords'] ?? []);
        $hints = array_map('mb_strtolower', $intent['symbol_hints'] ?? []);

        $explicitTerms = array_map('mb_strtolower', (array) ($intent['resolved_keywords'] ?? []));
        if (! empty($filters['keywords']) && is_string($filters['keywords'])) {
            $filterParts = array_values(array_filter(array_map(
                fn (string $word) => mb_strtolower(trim($word)),
                preg_split('/[\s,;]+/', $filters['keywords']) ?: []
            )));
            $explicitTerms = array_values(array_unique(array_merge($explicitTerms, $filterParts)));
        }

        $brandName = (string) ($intent['brand_name'] ?? '');
        $brandTokens = array_values(array_unique(array_filter(
            preg_split('/[^a-z0-9]+/i', mb_strtolower($brandName)) ?: [],
            fn (string $w) => mb_strlen($w) >= 3
        )));

        $keywords = array_values(array_unique(array_merge($explicitTerms, $keywords)));

        $filterStyles = array_filter(array_merge(
            isset($filters['style']) && is_string($filters['style']) ? [$filters['style']] : [],
            (array) ($filters['styles'] ?? [])
        ));

        if (in_array('classic', $filterStyles, true)) {
            $filterStyles = array_merge($filterStyles, ['elegant', 'vintage']);
        }

        $styles = array_values(array_unique(array_merge($filterStyles, $styles)));

        $scored = $assets->map(function (LogoAsset $asset) use ($category, $styles, $keywords, $explicitTerms, $brandTokens, $hints) {
            $score = 0.0;
            $assetTokens = $this->assetTokens($asset);
            $matchedTerms = [];

            if (in_array($category, $asset->industry_slugs ?? [], true)) {
                $score += 18;
            }

            foreach ($asset->style_slugs ?? [] as $style) {
                if (in_array($style, $styles, true)) {
                    $score += 7;
                }
            }

            foreach ($asset->keywords ?? [] as $keyword) {
                $kw = mb_strtolower((string) $keyword);

                if (in_array($kw, $explicitTerms, true)) {
                    $score += 40;
                } elseif (in_array($kw, $keywords, true)) {
                    $score += 4;
                } elseif (in_array($kw, $hints, true)) {
                    $score += 6;
                }
            }

            foreach ($explicitTerms as $term) {
                if ($this->assetMatchesTerm($asset, $assetTokens, $term)) {
                    $matchedTerms[] = $term;
                    $score += in_array($term, array_map('mb_strtolower', preg_split('/[^a-z0-9]+/i', $asset->name) ?: []), true) ? 35 : 22;
                }
            }

            foreach ($brandTokens as $bt) {
                if ($this->assetMatchesTerm($asset, $assetTokens, $bt)) {
                    $score += 24;
                }
            }

            if (($asset->asset_type ?? 'svg') === 'png' && ! empty($asset->image_url)) {
                $score += 36;
            } elseif ($asset->view_box === '0 0 128 128' || count($asset->paths ?? []) >= 4) {
                $score += 22;
            } elseif ($asset->view_box === '0 0 512 512' || str_starts_with((string) $asset->slug, 'game-icons-') || count($asset->paths ?? []) >= 3) {
                $score += 14;
            } elseif (str_starts_with((string) $asset->slug, 'flat-color-icons-') || count($asset->paths ?? []) >= 2) {
                $score += 7;
            }

            return [
                'asset' => $asset,
                'score' => $score,
                'matched_terms' => array_values(array_unique($matchedTerms)),
            ];
        })->sortByDesc(fn (array $row) => $row['score'])->values();

        if ($explicitTerms !== []) {
            $byTerm = [];
            foreach ($explicitTerms as $term) {
                $byTerm[$term] = [];
            }

            foreach ($scored as $row) {
                foreach ($row['matched_terms'] as $term) {
                    $byTerm[$term][] = $row['asset'];
                }
            }

            $interleaved = [];
            $seenIds = [];
            $round = 0;

            while (true) {
                $addedInRound = false;
                foreach ($explicitTerms as $term) {
                    if (isset($byTerm[$term][$round])) {
                        $candidate = $byTerm[$term][$round];
                        $addedInRound = true;
                        if (! isset($seenIds[$candidate->id])) {
                            $seenIds[$candidate->id] = true;
                            $interleaved[] = $candidate;
                        }
                    }
                }
                if (! $addedInRound) {
                    break;
                }
                $round++;
            }

            if ($interleaved !== []) {
                $tail = [];
                foreach ($scored as $row) {
                    if (! isset($seenIds[$row['asset']->id])) {
                        $tail[] = $row['asset'];
                    }
                }

                return [
                    'assets' => collect(array_merge($interleaved, $tail))->values(),
                    'direct_count' => count($interleaved),
                    'industry_count' => 0,
                    'direct_terms' => $explicitTerms,
                ];
            }
        }

        if ($category !== 'general') {
            $industryMatches = [];
            $otherMatches = [];

            foreach ($scored as $row) {
                if (in_array($category, $row['asset']->industry_slugs ?? [], true)) {
                    $industryMatches[] = $row['asset'];
                } else {
                    $otherMatches[] = $row['asset'];
                }
            }

            if ($industryMatches !== []) {
                return [
                    'assets' => collect(array_merge($industryMatches, $otherMatches))->values(),
                    'direct_count' => 0,
                    'industry_count' => count($industryMatches),
                    'direct_terms' => $explicitTerms,
                ];
            }
        }

        return [
            'assets' => $scored->pluck('asset')->values(),
            'direct_count' => 0,
            'industry_count' => 0,
            'direct_terms' => $explicitTerms,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function assetTokens(LogoAsset $asset): array
    {
        $parts = array_map(fn ($v) => mb_strtolower((string) $v), $asset->keywords ?? []);

        foreach (preg_split('/[^a-z0-9]+/i', (string) $asset->slug) ?: [] as $seg) {
            if ($seg !== '') {
                $parts[] = mb_strtolower($seg);
            }
        }

        foreach (preg_split('/[^a-z0-9]+/i', (string) $asset->name) ?: [] as $seg) {
            if ($seg !== '') {
                $parts[] = mb_strtolower($seg);
            }
        }

        return array_values(array_unique($parts));
    }

    /**
     * @param  array<int, string>  $assetTokens
     */
    private function assetMatchesTerm(LogoAsset $asset, array $assetTokens, string $term): bool
    {
        $term = mb_strtolower(trim($term));
        if ($term === '') {
            return false;
        }

        if (in_array($term, $assetTokens, true)) {
            return true;
        }

        $singular = str_ends_with($term, 's') && strlen($term) > 3 ? substr($term, 0, -1) : $term;
        if ($singular !== $term && in_array($singular, $assetTokens, true)) {
            return true;
        }

        if (mb_strlen($term) >= 4) {
            foreach ($assetTokens as $token) {
                if (str_starts_with($token, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Font>
     */
    public function fonts(array $intent): Collection
    {
        $direction = mb_strtolower($intent['typography_direction'] ?? '');
        $styles = $intent['styles'] ?? [];

        $preferredCategories = match (true) {
            str_contains($direction, 'serif') && ! str_contains($direction, 'sans') => ['serif', 'display'],
            str_contains($direction, 'rounded') => ['sans', 'display'],
            str_contains($direction, 'grotesque'), str_contains($direction, 'geometric') => ['sans', 'display'],
            default => ['sans', 'display', 'serif'],
        };

        $playful = array_intersect($styles, ['playful', 'organic', 'mascot']) !== [];
        $luxury = array_intersect($styles, ['luxury', 'elegant', 'vintage']) !== [];

        return Font::query()->active()->get()
            ->sortBy(fn (Font $font) => $this->fontScore($font, $preferredCategories, $playful, $luxury))
            ->values();
    }

    private function fontScore(Font $font, array $preferredCategories, bool $playful, bool $luxury): float
    {
        $score = 0.0;
        $position = array_search($font->category, $preferredCategories, true);

        if ($position !== false) {
            $score += 10 - $position * 3;
        }

        if ($playful && $font->category === 'display') {
            $score += 4;
        }

        if ($luxury && in_array($font->category, ['serif'], true)) {
            $score += 4;
        }

        return -$score;
    }

    /**
     * Builds palettes from the brand intelligence first, then tops up with the
     * curated catalogue. Palette colours are ordered ink -> accent -> support.
     *
     * @return array<int, ColorPalette>
     */
    public function palettes(array $intent): array
    {
        $palettes = [];

        foreach ($intent['color_palettes'] ?? [] as $index => $colors) {
            $colors = array_values(array_filter((array) $colors, fn ($color) => is_string($color)));

            if (count($colors) < 2) {
                continue;
            }

            $palettes[] = $this->makePalette('Brand direction '.($index + 1), $colors);
        }

        $catalogue = ColorPalette::query()->active()->get();

        foreach ($catalogue as $palette) {
            $palettes[] = $palette;
        }

        return $this->uniquePalettes($palettes);
    }

    /**
     * @param  array<int, string>  $colors
     */
    public function makePalette(string $name, array $colors): ColorPalette
    {
        $colors = array_values(array_unique($colors));
        $ink = $colors[0] ?? '#111827';

        return new ColorPalette([
            'name' => $name,
            'slug' => 'generated-'.md5(implode('-', $colors)),
            'colors' => $colors,
            'is_dark' => Color::isLight($ink) === false && Color::contrast($ink, '#FFFFFF') < 4.5,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, ColorPalette>  $palettes
     * @return array<int, ColorPalette>
     */
    private function uniquePalettes(array $palettes): array
    {
        $seen = [];
        $unique = [];

        foreach ($palettes as $palette) {
            $key = implode('|', array_map('strtoupper', $palette->colors ?? []));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $palette;
        }

        return $unique;
    }

    /**
     * @return array<int, string>
     */
    public function layouts(array $intent, string $brandName, array $filters = []): array
    {
        $requested = $filters['layout'] ?? null;

        if (is_string($requested) && array_key_exists($requested, Layouts::ALL)) {
            return [$requested];
        }

        $length = mb_strlen(trim($brandName));
        $styles = $intent['styles'] ?? [];

        $order = $length > 14
            ? ['icon-top', 'medallion', 'ribbon', 'seal', 'stacked', 'shield', 'badge', 'icon-left', 'emblem', 'script-3d', 'wordmark', 'icon-bottom', 'monogram', 'horizontal', 'icon-right', 'text-only', 'icon-only', 'vertical']
            : ['icon-top', 'medallion', 'ribbon', 'seal', 'stacked', 'badge', 'shield', 'icon-left', 'emblem', 'script-3d', 'icon-right', 'monogram', 'horizontal', 'wordmark', 'icon-bottom', 'vertical', 'text-only', 'icon-only'];

        if (array_intersect($styles, ['luxury', 'elegant', 'minimal']) !== []) {
            $order = array_values(array_unique(array_merge(['icon-top', 'seal', 'ribbon', 'medallion', 'wordmark', 'monogram', 'emblem'], $order)));
        }

        return $order;
    }
}
