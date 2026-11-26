<?php

namespace App\Services\Logo;

use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\LogoAsset;
use Illuminate\Support\Str;

/**
 * Assembles logo candidates from the catalogue.
 *
 * Generation is deterministic per brand name: the same name always yields the
 * same first page, which makes results cacheable and shareable. Combinations
 * are produced lazily in rotating strides rather than by enumerating the full
 * symbol x font x palette x layout space.
 */
class VariationEngineService
{
    /** Curated warm studio card backdrops (white, warm cream, sand, blush, ice blue, burgundy, midnight navy). */
    private const CARD_BACKGROUNDS = [
        '#FFFFFF',
        '#FFFBF0',
        '#080C24',
        '#EAE6D7',
        '#240606',
        '#F2F8FF',
        '#FFFFFF',
        '#FDF4F5',
        '#111827',
        '#F8F5EE',
        '#06202B',
        '#FFFFFF',
        '#1C1917',
    ];

    public function __construct(
        private readonly TemplateMatchingService $matcher,
        private readonly LogoComposer $composer,
        private readonly LogoRankingService $ranking,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array{design: array, svg: string, symbol: ?LogoAsset, font: Font, palette: ColorPalette, layout: string, style: string, score: float, is_premium: bool}>
     */
    public function candidates(array $intent, string $brandName, array $filters, int $limit = 108): array
    {
        $symbolMatch = $this->matcher->matchSymbols($intent, $filters);
        $allSymbols = $symbolMatch['assets']->all();
        $directCount = $symbolMatch['direct_count'];
        $industryCount = $symbolMatch['industry_count'] ?? 0;
        $focusedIndustry = $directCount === 0 && $industryCount >= 6;

        $fonts = $this->matcher->fonts($intent);
        $palettes = $this->matcher->palettes($intent);
        $layouts = $this->matcher->layouts($intent, $brandName, $filters);

        $styleFilter = $filters['style'] ?? null;
        $stylesFilter = $this->expandStyles((array) ($filters['styles'] ?? ($styleFilter ? [$styleFilter] : [])));
        $layoutFilter = $filters['layout'] ?? null;

        if (($directCount > 0 || $focusedIndustry) && $layoutFilter === null && array_intersect($stylesFilter, ['wordmark', 'text-only', 'monogram']) === []) {
            $drop = $directCount > 0
                ? ['wordmark', 'text-only', 'monogram', 'script-3d']
                : ['wordmark', 'text-only', 'monogram'];
            $layouts = array_values(array_diff($layouts, $drop));
        }

        $colorsFilter = array_values(array_filter(array_merge(
            isset($filters['color']) && is_string($filters['color']) ? [$filters['color']] : [],
            (array) ($filters['colors'] ?? [])
        )));
        $premiumFilter = $filters['premium'] ?? null;
        $explicitBackground = $filters['background'] ?? 'auto';

        if ($colorsFilter !== []) {
            if (in_array('grayscale', $colorsFilter, true) || in_array('black', $colorsFilter, true)) {
                $palettes = array_merge($palettes, [
                    $this->matcher->makePalette('Monochrome Crisp', ['#111827', '#334155', '#94A3B8']),
                    $this->matcher->makePalette('Charcoal Ink', ['#18181B', '#52525B', '#D4D4D8']),
                    $this->matcher->makePalette('Zinc Minimal', ['#27272A', '#71717A', '#E4E4E7']),
                ]);
            }

            $palettes = array_values(array_filter(
                $palettes,
                fn (ColorPalette $palette) => $this->paletteMatchesAnyColor($palette, $colorsFilter)
            ));
        }

        if ($palettes === []) {
            $palettes = [$this->matcher->makePalette('Fallback', ['#111827', '#2563EB', '#06B6D4'])];
        }

        if ($fonts->isEmpty()) {
            return [];
        }

        $styles = $stylesFilter !== [] ? $stylesFilter : ($intent['styles'] ?? ['modern']);
        $seed = crc32(mb_strtolower($brandName));

        $primaryPool = $directCount > 0
            ? array_slice($allSymbols, 0, $directCount)
            : ($focusedIndustry
                ? array_slice($allSymbols, 0, min(36, $industryCount))
                : array_slice($allSymbols, 0, min(64, count($allSymbols))));

        if ($primaryPool === []) {
            $primaryPool = [null];
        }

        $fontPool = $fonts->all();
        $scriptFonts = array_values(array_filter(
            $fontPool,
            fn (Font $f) => in_array($f->category, ['script', 'display'], true)
        ));
        $fontCount = max(1, count($fontPool));
        $paletteCount = max(1, count($palettes));
        $layoutCount = max(1, count($layouts));

        $candidates = [];
        $seen = [];
        $symbolUsage = [];
        $attempts = 0;
        $isFocused = $directCount > 0 || $focusedIndustry;
        $maxAttempts = $limit * ($directCount > 0 ? 32 : ($focusedIndustry ? 26 : 18));
        $poolCount = count($primaryPool);
        $maxPerSymbol = $isFocused
            ? max(12, (int) ceil(($limit * 1.4) / max(1, $poolCount)))
            : max(6, (int) ceil($limit / max(1, $poolCount)) + 2);
        $sinceTagline = 'SINCE '.date('Y');
        $allowMultiplePalettes = $directCount > 0 || ($focusedIndustry && $poolCount < 32);

        while (count($candidates) < $limit && $attempts < $maxAttempts) {
            $index = $attempts++;
            $cycle = intdiv($index, $poolCount);

            $symbol = $primaryPool[($index + $seed) % $poolCount];
            $layout = $layouts[($index + $cycle + intdiv($seed, 11)) % $layoutCount];
            $palette = $palettes[($cycle + ($index * 3) + intdiv($seed, 7)) % $paletteCount];
            $font = $layout === 'script-3d' && $scriptFonts !== []
                ? $scriptFonts[($index + intdiv($seed, 3)) % count($scriptFonts)]
                : $fontPool[(($index * 5) + $cycle + intdiv($seed, 3)) % $fontCount];

            if ($layoutFilter !== null && $layout !== $layoutFilter) {
                continue;
            }

            $style = $styles[$index % max(1, count($styles))];
            $isPremium = (bool) ($symbol?->is_premium || $font->is_premium);

            if ($premiumFilter === 'premium' && ! $isPremium) {
                continue;
            }

            if ($premiumFilter === 'free' && $isPremium) {
                continue;
            }

            $symbolKey = $symbol?->slug ?? 'none';

            if (($symbolUsage[$symbolKey] ?? 0) >= $maxPerSymbol) {
                continue;
            }

            if ($this->ranking->isDuplicate($seen, $symbolKey, $layout, $palette->colors ?? [], $allowMultiplePalettes)) {
                continue;
            }

            $cardBg = null;

            if ($explicitBackground === 'auto') {
                if (in_array('grayscale', $colorsFilter, true)) {
                    $cardBg = $index % 3 === 1 ? '#111827' : '#FFFFFF';
                } elseif (preg_match('/(silver-gold|bevel-ring|gold-globe|neon-globe)/i', $symbolKey)) {
                    $darkPool = ['#240606', '#080C24', '#06202B', '#111827'];
                    $cardBg = $darkPool[($index + $seed) % count($darkPool)];
                } elseif (preg_match('/(coral-sunburst|mint-tropical|sunset-plane|cruise-sunset|wave-island|mappin-beach|explorer-mascot|mountain-plane)/i', $symbolKey)) {
                    $lightPool = ['#FFFFFF', '#FFFBF0', '#FDF4F5', '#F2F8FF', '#EAE6D7', '#F8F5EE'];
                    $cardBg = $lightPool[($index + $seed) % count($lightPool)];
                } else {
                    $cardBg = self::CARD_BACKGROUNDS[($index + $seed) % count(self::CARD_BACKGROUNDS)];
                }
            }

            $autoTagline = $filters['tagline'] ?? (
                $layout === 'icon-top'
                || (in_array($layout, ['stacked', 'badge', 'emblem', 'seal', 'ribbon', 'medallion'], true) && $index % 2 === 0)
                    ? $sinceTagline
                    : null
            );

            $useGradient = ! in_array('grayscale', $colorsFilter, true) && ($index % 2 === 0);

            $design = $this->composer->compose([
                'brand_name' => $brandName,
                'tagline' => $autoTagline,
                'symbol' => $symbol,
                'font' => $font,
                'palette' => $palette,
                'layout' => $layout,
                'style' => $style,
                'background' => $explicitBackground,
                'card_background' => $cardBg,
                'symbol_gradient' => $useGradient,
                'gradient_index' => $index,
            ]);

            if (! $this->ranking->isAcceptable($design, $layout, $brandName)) {
                continue;
            }

            $score = $this->ranking->score($design, $intent, $symbol, $font, $palette->colors ?? [], $layout, $brandName);

            // Deterministic micro-jitter keeps equal scores from clumping by palette order.
            $score += (crc32($symbolKey.$layout.$font->slug) % 100) / 400;

            $candidates[] = [
                'design' => $design,
                'svg' => '',
                'symbol' => $symbol,
                'font' => $font,
                'palette' => $palette,
                'layout' => $layout,
                'style' => $style,
                'score' => round($score, 2),
                'is_premium' => $isPremium,
            ];

            $paletteKey = implode('', array_map('strtoupper', array_slice($palette->colors ?? [], 0, 3)));
            $seen[$symbolKey.'|'.$layout][$paletteKey] = true;
            $symbolUsage[$symbolKey] = ($symbolUsage[$symbolKey] ?? 0) + 1;
        }

        usort($candidates, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        $diversityCap = $isFocused
            ? max(12, (int) ceil($limit / max(1, $poolCount)) + 6)
            : max(4, (int) ceil($limit / max(1, $poolCount)) + 1);

        return $this->enforceDiversity($candidates, $limit, $diversityCap);
    }

    /**
     * Final pass: never more than N results share a symbol, and never more than
     * two consecutive results share a layout.
     *
     * @param  array<int, array>  $candidates
     * @return array<int, array>
     */
    private function enforceDiversity(array $candidates, int $limit, int $maxPerSymbol = 4): array
    {
        $perSymbol = [];
        $perLayout = [];
        $result = [];
        $pickedIndices = [];
        $lastLayout = null;
        $layoutRun = 0;

        // Pass 1: maximize unique symbols and balanced layout variety on Page 1.
        foreach ($candidates as $idx => $candidate) {
            $symbolKey = $candidate['symbol']?->slug ?? 'none';
            $layoutKey = (string) $candidate['layout'];

            if ($layoutKey !== 'script-3d' && ($perSymbol[$symbolKey] ?? 0) >= 1) {
                continue;
            }

            $layoutCap = match ($layoutKey) {
                'icon-top' => 10,
                'ribbon', 'seal', 'medallion' => 4,
                'script-3d' => 2,
                default => 3,
            };
            if (($perLayout[$layoutKey] ?? 0) >= $layoutCap) {
                continue;
            }

            if ($layoutKey === $lastLayout && $layoutRun >= 2) {
                continue;
            }

            if ($layoutKey === $lastLayout) {
                $layoutRun++;
            } else {
                $layoutRun = 1;
                $lastLayout = $layoutKey;
            }

            if ($layoutKey !== 'script-3d') {
                $perSymbol[$symbolKey] = 1;
            }
            $perLayout[$layoutKey] = ($perLayout[$layoutKey] ?? 0) + 1;
            $pickedIndices[$idx] = true;
            $result[] = $candidate;

            if (count($result) >= $limit) {
                return $result;
            }
        }

        // Pass 2: fill remaining pages respecting per-symbol diversity cap and layout runs.
        foreach ($candidates as $idx => $candidate) {
            if (isset($pickedIndices[$idx])) {
                continue;
            }

            $symbolKey = $candidate['symbol']?->slug ?? 'none';

            if (($perSymbol[$symbolKey] ?? 0) >= $maxPerSymbol) {
                continue;
            }

            if ($candidate['layout'] === $lastLayout) {
                $layoutRun++;

                if ($layoutRun > 2) {
                    continue;
                }
            } else {
                $layoutRun = 0;
                $lastLayout = $candidate['layout'];
            }

            $perSymbol[$symbolKey] = ($perSymbol[$symbolKey] ?? 0) + 1;
            $result[] = $candidate;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $styles
     * @return array<int, string>
     */
    private function expandStyles(array $styles): array
    {
        $expanded = [];

        foreach ($styles as $style) {
            $slug = mb_strtolower(trim($style));

            if ($slug === '') {
                continue;
            }

            if ($slug === 'classic') {
                $expanded[] = 'elegant';
                $expanded[] = 'vintage';
            } else {
                $expanded[] = $slug;
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @param  array<int, string>  $filters
     */
    private function paletteMatchesAnyColor(ColorPalette $palette, array $filters): bool
    {
        foreach ($filters as $filter) {
            if ($this->paletteMatchesColor($palette, $filter)) {
                return true;
            }
        }

        return false;
    }

    private function paletteMatchesColor(ColorPalette $palette, string $filter): bool
    {
        $normalized = mb_strtolower($filter);

        $family = match ($normalized) {
            'blue' => ['#1D4ED8', '#2563EB', '#0EA5E9', '#0284C7', '#1E40AF', '#0F766E', '#0369A1'],
            'green' => ['#047857', '#15803D', '#16A34A', '#0D9488', '#14532D', '#059669'],
            'red' => ['#B91C1C', '#DC2626', '#E11D48', '#A21CAF'],
            'orange' => ['#EA580C', '#F97316', '#B45309', '#CA8A04'],
            'purple' => ['#7C3AED', '#9333EA', '#6366F1', '#A855F7', '#4338CA'],
            'pink' => ['#DB2777', '#EC4899', '#F472B6', '#E60072'],
            'black', 'grayscale' => ['#111827', '#111111', '#0F172A', '#0B0B14', '#1C1917', '#334155', '#4B5563', '#475569', '#52525B', '#71717A'],
            default => [],
        };

        foreach ($palette->colors ?? [] as $color) {
            foreach ($family as $candidate) {
                if (Str::lower($color) === Str::lower($candidate)) {
                    return true;
                }
            }
        }

        return $family === [];
    }
}
