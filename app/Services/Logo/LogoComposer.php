<?php

namespace App\Services\Logo;

use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\LogoAsset;
use App\Support\Color;

/**
 * Turns a (symbol, font, palette, layout) combination into a complete,
 * editable design document. Pure computation: no database writes, no I/O.
 */
class LogoComposer
{
    private const WIDTH = DesignDocument::CANVAS_WIDTH;

    private const HEIGHT = DesignDocument::CANVAS_HEIGHT;

    /** Accent-heavy styles where uppercase wordmarks read best. */
    private const UPPERCASE_STYLES = [
        'corporate', 'bold', 'luxury', 'vintage', 'retro', 'minimal',
        'tech', 'geometric', 'futuristic', 'badge', 'mascot', 'abstract',
    ];

    public function __construct(private readonly TextMetrics $metrics)
    {
    }

    /**
     * @param  array{
     *     brand_name: string,
     *     tagline?: ?string,
     *     symbol?: ?LogoAsset,
     *     font: Font,
     *     palette: ColorPalette,
     *     layout?: string,
     *     style?: string,
     *     background?: string,
     *     card_background?: ?string,
     *     uppercase?: ?bool
     * }  $spec
     */
    public function compose(array $spec): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $spec['brand_name']) ?? '');
        $layout = $spec['layout'] ?? 'icon-left';
        $style = $spec['style'] ?? 'modern';
        $font = $spec['font'];
        $palette = $spec['palette'];
        $symbol = $spec['symbol'] ?? null;
        $tagline = trim((string) ($spec['tagline'] ?? ''));

        $uppercase = $spec['uppercase'] ?? (
            $layout !== 'script-3d' && (
                in_array($style, self::UPPERCASE_STYLES, true)
                || in_array($layout, ['icon-top', 'icon-bottom', 'stacked', 'badge', 'seal', 'ribbon', 'medallion', 'emblem', 'shield', 'monogram', 'wordmark'], true)
            )
        );

        $display = $uppercase ? mb_strtoupper($name) : ($layout === 'script-3d' ? mb_strtolower($name) : $name);
        [$background, $isDark] = $this->resolveBackground(
            $spec['background'] ?? 'auto',
            $palette,
            $spec['card_background'] ?? null
        );
        // Optical correction (irradiation): light type on dark backgrounds reads heavier,
        // so step weight down slightly when available and open letter-spacing by +18.
        $weight = $this->weightFor($style, $font, $isDark);
        $charSpacing = $this->trackingFor($style, $layout) + ($isDark ? 18.0 : 0.0);
        $ink = $this->inkFor($palette, $background, $isDark);

        $symbolBackdrop = in_array($layout, ['badge', 'shield'], true) ? $this->frameColor($layout) : $background;
        $symbolIsDark = in_array($layout, ['badge', 'shield'], true) ? true : $isDark;

        $toneA = $this->visibleColor($palette->colors[1] ?? $palette->colors[0], $symbolBackdrop, $symbolIsDark);
        $toneB = $this->visibleColor($palette->colors[2] ?? $palette->colors[1] ?? $palette->colors[0], $symbolBackdrop, $symbolIsDark);

        $objects = [
            $this->backgroundObject($background),
        ];

        $geometry = $this->geometry(
            layout: $layout,
            display: $display,
            tagline: $tagline,
            font: $font,
            weight: $weight,
            charSpacing: $charSpacing,
            hasSymbol: $symbol !== null,
            accent: $toneA,
            ink: $ink,
            background: $background ?? '#FFFFFF',
            isDark: $isDark,
        );

        foreach ($geometry['frames'] as $frame) {
            $objects[] = $frame;
        }

        if ($symbol !== null && $geometry['symbol'] !== null) {
            $objects[] = $this->symbolObject(
                $symbol,
                $geometry['symbol']['x'],
                $geometry['symbol']['y'],
                $geometry['symbol']['size'],
                $toneA,
                $toneB,
                $symbolBackdrop,
                $symbolIsDark,
                (bool) ($spec['symbol_gradient'] ?? false),
                (int) ($spec['gradient_index'] ?? 0)
            );
        }

        foreach ($geometry['overlays'] ?? [] as $overlay) {
            $objects[] = $overlay;
        }

        if ($geometry['text'] !== null && $geometry['text']['text'] !== '') {
            $objects[] = $this->textObject(
                id: 'text-logo',
                role: 'primary-text',
                text: $geometry['text']['text'],
                left: $geometry['text']['left'],
                top: $geometry['text']['top'],
                originX: $geometry['text']['originX'],
                font: $font,
                fontSize: $geometry['text']['fontSize'],
                weight: $geometry['text']['weight'],
                charSpacing: $geometry['text']['charSpacing'],
                lineHeight: 1.12,
                fill: $geometry['text']['fill'] ?? $ink,
                align: $geometry['text']['align'],
                width: $geometry['text']['width'],
                uppercase: $uppercase,
                opacity: 1.0,
                extra: $geometry['text']['extra'] ?? [],
            );
        }

        if ($geometry['tagline'] !== null && ($tagline !== '' || ! empty($geometry['tagline']['text']))) {
            $taglineDisplay = $geometry['tagline']['text'] ?? ($uppercase ? mb_strtoupper($tagline) : $tagline);
            $objects[] = $this->textObject(
                id: 'tagline-logo',
                role: 'new-text',
                text: $taglineDisplay,
                left: $geometry['tagline']['left'],
                top: $geometry['tagline']['top'],
                originX: $geometry['tagline']['originX'],
                font: $font,
                fontSize: $geometry['tagline']['fontSize'],
                weight: max(400, min(600, $weight)),
                charSpacing: $geometry['tagline']['charSpacing'] ?? 220,
                lineHeight: 1.2,
                fill: $geometry['tagline']['fill'] ?? $ink,
                align: $geometry['text']['align'] ?? 'left',
                width: null,
                uppercase: $uppercase,
                opacity: $geometry['tagline']['opacity'] ?? 0.78,
                extra: $geometry['tagline']['extra'] ?? [],
            );
        }

        foreach ($geometry['extras'] ?? [] as $extraText) {
            $objects[] = $extraText;
        }

        return DesignDocument::make($objects, [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'background' => $geometry['frameFill'] === null ? 'transparent' : 'solid',
        ], [
            'layout' => $layout,
            'style' => $style,
            'orientation' => Layouts::orientation($layout),
            'font' => $font->slug,
            'palette' => $palette->colors,
            'symbol' => $symbol?->slug,
            'is_dark' => $isDark,
            'bounds' => $this->bounds($objects, $font, $weight),
        ])->toArray();
    }

    /**
     * @return array{frames: array<int, array>, overlays: array<int, array>, extras: array<int, array>, symbol: ?array{x: float, y: float, size: float}, text: ?array, tagline: ?array, frameFill: ?string}
     */
    private function geometry(
        string $layout,
        string $display,
        string $tagline,
        Font $font,
        int $weight,
        float $charSpacing,
        bool $hasSymbol,
        string $accent = '#2563EB',
        string $ink = '#111827',
        string $background = '#FFFFFF',
        bool $isDark = false,
    ): array {
        $fontCategory = $font->category;
        $tracking = fn (float $size): float => $size * $charSpacing / 1000;

        $measure = fn (string $text, float $size): float => $this->metrics->width(
            $text,
            $size,
            $fontCategory,
            $weight,
            $tracking($size)
        );

        $margin = 52.0;
        $bodyWidth = self::WIDTH - 2 * $margin;
        $centerX = self::WIDTH / 2;
        // Optical vertical center sits ~1.5% above geometric center so marks feel poised, not heavy.
        $centerY = (self::HEIGHT / 2) - 8.0;

        $result = [
            'frames' => [],
            'overlays' => [],
            'extras' => [],
            'symbol' => null,
            'text' => null,
            'tagline' => null,
            'frameFill' => null,
        ];

        if ($layout === 'seal') {
            $outerR = 204.0;
            $innerR = 148.0;
            $ringStroke = $isDark ? $accent : $ink;

            $result['frames'][] = $this->circleShape('shape-seal-outer', $centerX, $centerY, $outerR, 'none', $ringStroke, 4.5);
            $result['frames'][] = $this->circleShape('shape-seal-inner', $centerX, $centerY, $innerR, 'none', $accent, 2.5);

            $year = date('Y');
            $yearLeft = substr($year, 0, 2);
            $yearRight = substr($year, 2, 2);
            $pillBg = $isDark ? $background : '#FFFFFF';

            $result['overlays'][] = $this->circleShape('shape-year-left', $centerX - 176, $centerY, 21, $pillBg, $ringStroke, 2.5);
            $result['overlays'][] = $this->circleShape('shape-year-right', $centerX + 176, $centerY, 21, $pillBg, $ringStroke, 2.5);

            $result['extras'][] = $this->textObject(
                id: 'seal-year-left',
                role: 'shape',
                text: $yearLeft,
                left: $centerX - 176,
                top: $centerY - 9,
                originX: 'center',
                font: $font,
                fontSize: 16,
                weight: 700,
                charSpacing: 40,
                lineHeight: 1.0,
                fill: $ink,
                align: 'center',
                width: null,
                uppercase: true,
            );
            $result['extras'][] = $this->textObject(
                id: 'seal-year-right',
                role: 'shape',
                text: $yearRight,
                left: $centerX + 176,
                top: $centerY - 9,
                originX: 'center',
                font: $font,
                fontSize: 16,
                weight: 700,
                charSpacing: 40,
                lineHeight: 1.0,
                fill: $ink,
                align: 'center',
                width: null,
                uppercase: true,
            );

            $titleSize = $this->metrics->fitFontSize($display, 330.0, 34.0, $fontCategory, $weight, 2.0);
            $titleSize = max(21.0, min(34.0, $titleSize));

            $result['symbol'] = ['x' => $centerX, 'y' => $centerY, 'size' => 178.0];
            $result['text'] = [
                'text' => $display,
                'left' => $centerX,
                'top' => $centerY - 174,
                'originX' => 'center',
                'fontSize' => round($titleSize, 2),
                'weight' => max(700, $weight),
                'charSpacing' => 110.0,
                'align' => 'center',
                'width' => null,
                'fill' => $ink,
                'extra' => [
                    'textEffect' => 'arc-top',
                    'arcRadius' => 168.0,
                    'arcCenterX' => $centerX,
                    'arcCenterY' => $centerY,
                ],
            ];

            $sealBottom = $tagline !== '' ? mb_strtoupper($tagline) : 'EST. '.$year;
            $result['tagline'] = [
                'text' => $sealBottom,
                'left' => $centerX,
                'top' => $centerY + 158,
                'originX' => 'center',
                'fontSize' => 17.0,
                'charSpacing' => 220.0,
                'opacity' => 0.9,
                'fill' => $accent,
                'extra' => [
                    'textEffect' => 'arc-bottom',
                    'arcRadius' => 184.0,
                    'arcCenterX' => $centerX,
                    'arcCenterY' => $centerY,
                ],
            ];

            return $result;
        }

        if ($layout === 'ribbon') {
            $emblemY = $centerY - 48.0;
            $ribbonY = $centerY + 94.0;
            $ribbonFill = $isDark ? $accent : '#0F2544';
            $ribbonInk = Color::contrast('#FFFFFF', $ribbonFill) >= 4.5 ? '#FFFFFF' : '#0B1120';

            $result['frames'][] = $this->circleShape('shape-ribbon-ring', $centerX, $emblemY, 134.0, 'none', $ribbonFill, 4.5);
            $result['symbol'] = ['x' => $centerX, 'y' => $emblemY, 'size' => 190.0];
            $result['overlays'][] = $this->ribbonShape($centerX, $ribbonY, $ribbonFill, $isDark ? '#FFFFFF' : $accent);

            $ribbonTextSize = $this->metrics->fitFontSize($display, 320.0, 38.0, $fontCategory, $weight, 2.0);
            $ribbonTextSize = max(22.0, min(38.0, $ribbonTextSize));

            $result['text'] = [
                'text' => $display,
                'left' => $centerX,
                'top' => $ribbonY - ($ribbonTextSize * 0.62),
                'originX' => 'center',
                'fontSize' => round($ribbonTextSize, 2),
                'weight' => max(700, $weight),
                'charSpacing' => 70.0,
                'align' => 'center',
                'width' => round($this->metrics->width($display, $ribbonTextSize, $fontCategory, $weight, 2.0), 2),
                'fill' => $ribbonInk,
                'extra' => [
                    'textEffect' => 'curve',
                    'effectStrength' => 0.38,
                ],
            ];

            if ($tagline !== '') {
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $ribbonY + 52.0,
                    'originX' => 'center',
                    'fontSize' => 16.0,
                    'fill' => $ink,
                ];
            }

            return $result;
        }

        if ($layout === 'medallion') {
            $emblemY = $centerY - 76.0;
            $badgeY = $emblemY + 96.0;
            $initial = mb_strtoupper(mb_substr(trim($display), 0, 1)) ?: 'A';
            $medallionFill = $isDark ? $accent : '#0F2544';
            $medallionInk = Color::contrast('#FFFFFF', $medallionFill) >= 4.5 ? '#FFFFFF' : '#0B1120';

            $result['frames'][] = $this->circleShape('shape-medallion-ring', $centerX, $emblemY, 108.0, 'none', $medallionFill, 4.0);
            $result['symbol'] = ['x' => $centerX, 'y' => $emblemY - 4.0, 'size' => 182.0];
            $result['overlays'][] = $this->circleShape('shape-initial-badge', $centerX, $badgeY, 24.0, $medallionFill, $isDark ? $background : '#FFFFFF', 4.0);
            $result['extras'][] = $this->textObject(
                id: 'medallion-initial',
                role: 'shape',
                text: $initial,
                left: $centerX,
                top: $badgeY - 14.0,
                originX: 'center',
                font: $font,
                fontSize: 25.0,
                weight: 800,
                charSpacing: 0,
                lineHeight: 1.0,
                fill: $medallionInk,
                align: 'center',
                width: null,
                uppercase: true,
            );

            $titleSize = $this->metrics->fitFontSize($display, $bodyWidth - 40, 56.0, $fontCategory, $weight, $tracking(56.0));
            $titleSize = max(26.0, min(56.0, $titleSize));

            $result['text'] = [
                'text' => $display,
                'left' => $centerX,
                'top' => $badgeY + 40.0,
                'originX' => 'center',
                'fontSize' => round($titleSize, 2),
                'weight' => max(700, $weight),
                'charSpacing' => 60.0,
                'align' => 'center',
                'width' => round($this->metrics->width($display, $titleSize, $fontCategory, $weight, $tracking($titleSize)), 2),
                'fill' => $ink,
            ];

            if ($tagline !== '') {
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $badgeY + 40.0 + $titleSize * 1.18 + 8.0,
                    'originX' => 'center',
                    'fontSize' => 17.0,
                    'fill' => $accent,
                ];
            }

            return $result;
        }

        if ($layout === 'script-3d') {
            $preferred = 104.0;
            $available = $bodyWidth - 24;
            $fontSize = $this->metrics->fitFontSize($display, $available, $preferred, $fontCategory, $weight, 0);
            $fontSize = max(36.0, min(104.0, $fontSize));
            $blockWidth = $this->metrics->width($display, $fontSize, $fontCategory, $weight, 0);
            $blockHeight = $fontSize * 1.15;

            $shadowColor = $isDark ? 'rgba(0,0,0,0.65)' : Color::shade($accent, -0.35);
            $strokeColor = $isDark ? '#0B1120' : '#FFFFFF';
            $scriptFill = $isDark ? $accent : $this->visibleColor($accent, $background, false);

            $result['text'] = [
                'text' => $display,
                'left' => $centerX,
                'top' => $centerY - $blockHeight / 2 - ($tagline !== '' ? 14.0 : 0.0),
                'originX' => 'center',
                'fontSize' => round($fontSize, 2),
                'weight' => max(600, $weight),
                'charSpacing' => 10.0,
                'align' => 'center',
                'width' => round($blockWidth, 2),
                'fill' => $scriptFill,
                'extra' => [
                    'stroke' => $strokeColor,
                    'strokeWidth' => 3.5,
                    'shadow' => [
                        'color' => $shadowColor,
                        'blur' => 0,
                        'offsetX' => 5,
                        'offsetY' => 5,
                    ],
                    'textEffect' => 'angle',
                    'effectStrength' => 0.45,
                ],
            ];

            if ($tagline !== '') {
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $centerY + $blockHeight / 2 + 10.0,
                    'originX' => 'center',
                    'fontSize' => 18.0,
                    'fill' => $ink,
                ];
            }

            return $result;
        }

        if (in_array($layout, ['icon-only'], true)) {
            if ($hasSymbol) {
                $result['symbol'] = ['x' => $centerX, 'y' => $centerY, 'size' => 290.0];
            }

            return $result;
        }

        if (in_array($layout, ['monogram'], true)) {
            $initials = $this->initials($display);
            $fontSize = 200.0;
            $initials = mb_strtoupper($initials);
            $fontSize = min($fontSize, $this->metrics->fitFontSize($initials, 300, $fontSize, $fontCategory, $weight, 0));

            $result['frames'][] = $this->circleShape('shape-frame', $centerX, $centerY, 210, 'none', $accent, 5);
            $result['text'] = [
                'text' => $initials,
                'left' => $centerX,
                'top' => $centerY - ($fontSize * 1.12) / 2,
                'originX' => 'center',
                'fontSize' => round($fontSize, 2),
                'weight' => $weight,
                'charSpacing' => $charSpacing,
                'align' => 'center',
                'width' => null,
            ];

            return $result;
        }

        if (in_array($layout, ['badge', 'emblem', 'shield'], true)) {
            $radius = 198.0;

            if ($layout === 'badge') {
                $result['frames'][] = $this->circleShape('shape-frame', $centerX, $centerY, $radius, $this->frameColor($layout));
                $result['frames'][] = $this->circleShape('shape-ring', $centerX, $centerY, $radius - 14, 'none', $accent, 3);
                $ink = '#FFFFFF';
            } elseif ($layout === 'emblem') {
                $result['frames'][] = $this->circleShape('shape-frame', $centerX, $centerY, $radius, 'none', $accent, 6);
                $ink = null;
            } else {
                $result['frames'][] = $this->shieldShape($centerX, $centerY, $accent);
                $ink = '#FFFFFF';
            }

            $result['frameFill'] = $this->frameColor($layout);

            $textSize = 46.0;
            $fitted = $this->metrics->fitFontSize($display, $radius * 1.45, $textSize, $fontCategory, $weight, $tracking($textSize));

            $result['symbol'] = ['x' => $centerX, 'y' => $centerY - 66, 'size' => 165.0];
            $result['text'] = [
                'text' => $display,
                'left' => $centerX,
                'top' => $centerY + 46,
                'originX' => 'center',
                'fontSize' => round($fitted, 2),
                'weight' => $weight,
                'charSpacing' => $charSpacing,
                'align' => 'center',
                'width' => null,
                'fill' => $ink,
            ];

            if ($tagline !== '') {
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $centerY + 46 + $fitted * 1.25,
                    'originX' => 'center',
                    'fontSize' => round(min(18, $fitted * 0.42), 2),
                    'fill' => $ok = $ink,
                ];
                $result['tagline']['fill'] = $ok;
            }

            return $result;
        }

        if (in_array($layout, ['wordmark', 'text-only'], true)) {
            $preferred = $layout === 'wordmark' ? 108.0 : 96.0;
            $available = $layout === 'wordmark' ? $bodyWidth : $bodyWidth - 40;
            $fontSize = $this->metrics->fitFontSize($display, $available, $preferred, $fontCategory, $weight, $tracking($preferred));
            $lines = $this->metrics->wrap($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize));

            if (count($lines) > 1) {
                $fontSize *= 0.78;
                $lines = $this->metrics->wrap($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize));
            }

            $lineHeight = $fontSize * 1.12;
            $blockHeight = count($lines) * $lineHeight;
            $guard = 0;

            while ($fontSize > 22 && $guard++ < 10) {
                $widest = max(array_map(
                    fn (string $line) => $this->metrics->width($line, $fontSize, $fontCategory, $weight, $tracking($fontSize)),
                    $lines
                ));

                if ($widest <= $available) {
                    break;
                }

                $fontSize *= 0.9;
                $lines = $this->metrics->wrap($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize));
                $lineHeight = $fontSize * 1.12;
                $blockHeight = count($lines) * $lineHeight;
            }

            $result['text'] = [
                'text' => implode("\n", $lines),
                'left' => $centerX,
                'top' => $centerY - $blockHeight / 2,
                'originX' => 'center',
                'fontSize' => round($fontSize, 2),
                'weight' => $weight,
                'charSpacing' => $charSpacing,
                'align' => 'center',
                'width' => round($this->metrics->width(
                    implode(' ', $lines),
                    $fontSize,
                    $fontCategory,
                    $weight,
                    $tracking($fontSize)
                ), 2),
            ];

            if ($tagline !== '') {
                $tagSize = max(12.0, min(26.0, $fontSize * 0.26));
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $centerY + $blockHeight / 2 + 14,
                    'originX' => 'center',
                    'fontSize' => round($tagSize, 2),
                ];
            }

            return $result;
        }

        $isVertical = in_array($layout, ['icon-top', 'icon-bottom', 'stacked', 'vertical'], true);

        if ($isVertical) {
            $fontSize = 84.0;
            $symbolSize = 215.0;

            for ($i = 0; $i < 4; $i++) {
                $available = $bodyWidth - 32;
                $fontSize = min($fontSize, $this->metrics->fitFontSize($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize)));
                $fontSize = max($fontSize, 26.0);
            }

            $lines = $this->metrics->wrap($display, $bodyWidth - 32, $fontSize, $fontCategory, $weight, $tracking($fontSize));

            if (count($lines) > 1) {
                $fontSize *= 0.82;
                $lines = $this->metrics->wrap($display, $bodyWidth - 32, $fontSize, $fontCategory, $weight, $tracking($fontSize));
            }

            $blockWidth = max(array_map(fn (string $line) => $this->metrics->width($line, $fontSize, $fontCategory, $weight, $tracking($fontSize)), $lines));
            $lineHeight = $fontSize * 1.12;
            $blockHeight = count($lines) * $lineHeight;
            $taglineSize = $tagline !== '' ? max(16.0, min(22.0, $fontSize * 0.34)) : 0.0;
            $taglineBlock = $tagline !== '' ? $taglineSize * 1.65 : 0.0;
            $gap = max(14.0, min(22.0, $fontSize * 0.32));
            $guard = 0;

            while ($blockWidth > $bodyWidth - 32 && $fontSize > 22 && $guard++ < 10) {
                $fontSize *= 0.9;
                $lines = $this->metrics->wrap($display, $bodyWidth - 32, $fontSize, $fontCategory, $weight, $tracking($fontSize));
                $blockWidth = max(array_map(
                    fn (string $line) => $this->metrics->width($line, $fontSize, $fontCategory, $weight, $tracking($fontSize)),
                    $lines
                ));
                $lineHeight = $fontSize * 1.12;
                $blockHeight = count($lines) * $lineHeight;
            }

            $symbolSize = max(185.0, min(238.0, $fontSize * 3.2));
            $maxAllowedSymbol = max(125.0, self::HEIGHT - 56.0 - $gap - $blockHeight - $taglineBlock);
            $symbolSize = min($symbolSize, $maxAllowedSymbol);
            $totalHeight = $symbolSize + $gap + $blockHeight + $taglineBlock;
            $top = $centerY - $totalHeight / 2;

            $symbolY = $top + $symbolSize / 2;
            $textTop = $top + $symbolSize + $gap;

            if ($layout === 'icon-bottom') {
                $textTop = $top;
                $symbolY = $top + $blockHeight + $taglineBlock + $gap + $symbolSize / 2;
            }

            $result['symbol'] = ['x' => $centerX, 'y' => $symbolY, 'size' => $symbolSize];
            $result['text'] = [
                'text' => implode("\n", $lines),
                'left' => $centerX,
                'top' => $textTop,
                'originX' => 'center',
                'fontSize' => round($fontSize, 2),
                'weight' => $weight,
                'charSpacing' => $charSpacing,
                'align' => 'center',
                'width' => $blockWidth,
            ];

            if ($tagline !== '') {
                $result['tagline'] = [
                    'left' => $centerX,
                    'top' => $textTop + $blockHeight + 10,
                    'originX' => 'center',
                    'fontSize' => round($taglineSize, 2),
                ];
            }

            return $result;
        }

        // Horizontal layouts: icon-left, icon-right, horizontal.
        $fontSize = 92.0;
        $symbolSize = 138.0;
        $gap = 34.0;
        $available = $bodyWidth;

        for ($i = 0; $i < 4; $i++) {
            $symbolSize = max(120.0, min(215.0, $fontSize * 2.1));
            $gap = max(20.0, $fontSize * 0.38);
            $available = $bodyWidth - $symbolSize - $gap;
            $fontSize = min($fontSize, $this->metrics->fitFontSize($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize)));
            $fontSize = max($fontSize, 24.0);
        }

        $lines = $this->metrics->wrap($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize));

        if (count($lines) > 1) {
            $fontSize *= 0.82;
            $lines = $this->metrics->wrap($display, $available, $fontSize, $fontCategory, $weight, $tracking($fontSize));
        }

        $blockWidth = max(array_map(fn (string $line) => $this->metrics->width($line, $fontSize, $fontCategory, $weight, $tracking($fontSize)), $lines));
        $lineHeight = $fontSize * 1.12;
        $blockHeight = count($lines) * $lineHeight;
        $taglineSize = $tagline !== '' ? max(15.0, min(22.0, $fontSize * 0.32)) : 0.0;
        $taglineBlock = $tagline !== '' ? $taglineSize * 1.6 : 0.0;

        $symbolSize = max(120.0, min(215.0, $fontSize * 2.1));
        $gap = max(20.0, $fontSize * 0.38);
        $guard = 0;

        // Guarantee the composed block fits the artboard, shrinking type and
        // symbol together rather than allowing clipped text.
        while ($symbolSize + $gap + $blockWidth > $bodyWidth && $fontSize > 18 && $guard++ < 12) {
            $fontSize *= 0.92;
            $lines = $this->metrics->wrap($display, $bodyWidth - 90, $fontSize, $fontCategory, $weight, $tracking($fontSize));
            $blockWidth = max(array_map(
                fn (string $line) => $this->metrics->width($line, $fontSize, $fontCategory, $weight, $tracking($fontSize)),
                $lines
            ));
            $lineHeight = $fontSize * 1.12;
            $blockHeight = count($lines) * $lineHeight;
            $taglineSize = $tagline !== '' ? max(12.0, min(22.0, $fontSize * 0.26)) : 0.0;
            $taglineBlock = $tagline !== '' ? $taglineSize * 1.6 : 0.0;
            $symbolSize = max(90.0, min(215.0, $fontSize * 1.75));
            $gap = max(20.0, $fontSize * 0.38);
        }

        $totalWidth = $symbolSize + $gap + $blockWidth;
        $startX = $centerX - $totalWidth / 2;

        $symbolX = $startX + $symbolSize / 2;
        $textLeft = $startX + $symbolSize + $gap;
        $textAlign = 'left';

        if ($layout === 'icon-right') {
            $symbolX = $startX + $blockWidth + $gap + $symbolSize / 2;
            $textLeft = $startX;
        }

        $contentHeight = $blockHeight + $taglineBlock;
        $textTop = $centerY - $contentHeight / 2;

        $result['symbol'] = ['x' => $symbolX, 'y' => $centerY, 'size' => $symbolSize];
        $result['text'] = [
            'text' => implode("\n", $lines),
            'left' => $textLeft,
            'top' => $textTop,
            'originX' => 'left',
            'fontSize' => round($fontSize, 2),
            'weight' => $weight,
            'charSpacing' => $charSpacing,
            'align' => $textAlign,
            'width' => $blockWidth,
        ];

        if ($tagline !== '') {
            $result['tagline'] = [
                'left' => $layout === 'icon-right' ? $startX : $textLeft,
                'top' => $textTop + $blockHeight + 8,
                'originX' => 'left',
                'fontSize' => round($taglineSize, 2),
            ];
        }

        return $result;
    }

    /**
     * Tight content bounds, used to crop gallery previews so every logo fills
     * its card instead of floating in a mostly empty artboard.
     *
     * @param  array<int, array>  $objects
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function bounds(array $objects, Font $font, int $weight): array
    {
        $minX = self::WIDTH;
        $minY = self::HEIGHT;
        $maxX = 0.0;
        $maxY = 0.0;

        foreach ($objects as $object) {
            if (($object['role'] ?? null) === 'background') {
                continue;
            }

            $type = $object['type'] ?? '';
            $left = (float) ($object['left'] ?? 0);
            $top = (float) ($object['top'] ?? 0);

            if ($type === 'circle') {
                $radius = (float) ($object['radius'] ?? 0) + (float) ($object['strokeWidth'] ?? 0);
                $boxMinX = $left - $radius;
                $boxMaxX = $left + $radius;
                $boxMinY = $top - $radius;
                $boxMaxY = $top + $radius;
            } elseif ($type === 'group') {
                $size = (float) ($object['scaleX'] ?? 1) * (float) ($object['pathBox'] ?? 64);
                $boxMinX = $left - $size / 2;
                $boxMaxX = $left + $size / 2;
                $boxMinY = $top - $size / 2;
                $boxMaxY = $top + $size / 2;
            } elseif ($type === 'image') {
                $w = (float) ($object['width'] ?? 180) * (float) ($object['scaleX'] ?? 1);
                $h = (float) ($object['height'] ?? $w) * (float) ($object['scaleY'] ?? 1);
                $boxMinX = $left - $w / 2;
                $boxMaxX = $left + $w / 2;
                $boxMinY = $top - $h / 2;
                $boxMaxY = $top + $h / 2;
            } elseif ($type === 'i-text') {
                $fontSize = (float) ($object['fontSize'] ?? 32);
                $effect = (string) ($object['textEffect'] ?? 'none');
                if (in_array($effect, ['arc-top', 'arc-bottom'], true)) {
                    $arcCx = (float) ($object['arcCenterX'] ?? $left);
                    $arcCy = (float) ($object['arcCenterY'] ?? 260.0);
                    $arcR = (float) ($object['arcRadius'] ?? 176.0) + $fontSize;
                    $boxMinX = $arcCx - $arcR;
                    $boxMaxX = $arcCx + $arcR;
                    $boxMinY = $arcCy - $arcR;
                    $boxMaxY = $arcCy + $arcR;
                } else {
                    $lines = explode("\n", (string) ($object['text'] ?? ''));
                    $width = $object['width'] ?? null;
                    $width = is_numeric($width) && (float) $width > 0
                        ? (float) $width
                        : $this->metrics->width(
                            implode(' ', $lines),
                            $fontSize,
                            $font->category,
                            (int) ($object['fontWeight'] ?? $weight),
                            $fontSize * (float) ($object['charSpacing'] ?? 0) / 1000
                        );
                    $height = count($lines) * $fontSize * (float) ($object['lineHeight'] ?? 1.12);
                    $anchorLeft = ($object['originX'] ?? 'left') === 'center' ? $left - $width / 2 : $left;
                    $boxMinX = $anchorLeft;
                    $boxMaxX = $anchorLeft + $width;
                    $boxMinY = $top;
                    $boxMaxY = $top + $height;
                }
            } elseif (($object['id'] ?? null) === 'shape-ribbon') {
                $boxMinX = $left - 215;
                $boxMaxX = $left + 215;
                $boxMinY = $top - 42;
                $boxMaxY = $top + 48;
            } else {
                $boxMinX = $left - 170;
                $boxMaxX = $left + 170;
                $boxMinY = $top - 190;
                $boxMaxY = $top + 210;
            }

            $minX = min($minX, $boxMinX);
            $minY = min($minY, $boxMinY);
            $maxX = max($maxX, $boxMaxX);
            $maxY = max($maxY, $boxMaxY);
        }

        if ($maxX <= $minX || $maxY <= $minY) {
            return ['x' => 0.0, 'y' => 0.0, 'width' => (float) self::WIDTH, 'height' => (float) self::HEIGHT];
        }

        $padding = 36.0;
        $x = max(0.0, $minX - $padding);
        $y = max(0.0, $minY - $padding);
        $width = min((float) self::WIDTH, $maxX + $padding) - $x;
        $height = min((float) self::HEIGHT, $maxY + $padding) - $y;

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($width, 2),
            'height' => round($height, 2),
        ];
    }

    private function backgroundObject(?string $color): array
    {
        return [
            'id' => 'background-logo',
            'role' => 'background',
            'type' => 'rect',
            'left' => 0,
            'top' => 0,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'fill' => $color === null
                ? ['type' => 'none']
                : ['type' => 'solid', 'color' => $color],
            'selectable' => false,
            'locked' => true,
        ];
    }

    private function symbolObject(
        LogoAsset $asset,
        float $x,
        float $y,
        float $size,
        string $toneA,
        string $toneB,
        ?string $symbolBackdrop = null,
        bool $symbolIsDark = false,
        bool $useGradient = false,
        int $gradientIndex = 0,
    ): array {
        if (($asset->asset_type ?? 'svg') === 'png' && $asset->image_url) {
            return [
                'id' => 'logo-logo',
                'role' => 'logo',
                'type' => 'image',
                'left' => round($x, 2),
                'top' => round($y, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($size, 2),
                'height' => round($size, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => $asset->image_url,
                'asset_slug' => $asset->slug,
            ];
        }

        $paths = [];
        $parts = preg_split('/[\s,]+/', trim((string) ($asset->view_box ?: '0 0 64 64'))) ?: [];
        $boxWidth = (float) ($parts[2] ?? 64);
        $boxHeight = (float) ($parts[3] ?? $boxWidth);
        $box = max(1.0, $boxWidth, $boxHeight);

        $toneC = $this->visibleColor(Color::shade($toneA, $symbolIsDark ? 0.28 : -0.22), $symbolBackdrop, $symbolIsDark);
        $toneD = $this->visibleColor(Color::shade($toneB, $symbolIsDark ? 0.24 : -0.18), $symbolBackdrop, $symbolIsDark);

        $gradientPairsLightBg = [
            ['#1E3A8A', '#06B6D4'],
            ['#7C3AED', '#EC4899'],
            ['#D97706', '#92400E'],
            ['#059669', '#0284C7'],
            ['#E11D48', '#F97316'],
        ];
        $gradientPairsDarkBg = [
            ['#FDE68A', '#F59E0B'],
            ['#38BDF8', '#818CF8'],
            ['#F472B6', '#FB923C'],
            ['#34D399', '#22D3EE'],
            ['#E2E8F0', '#94A3B8'],
        ];

        $pairPool = $symbolIsDark ? $gradientPairsDarkBg : $gradientPairsLightBg;
        $pair = $pairPool[$gradientIndex % count($pairPool)];

        $primaryGradient = [
            'type' => 'linear',
            'angle' => 135,
            'stops' => [
                ['offset' => 0, 'color' => $gradientIndex % 2 === 0 ? $toneA : $pair[0]],
                ['offset' => 1, 'color' => $gradientIndex % 2 === 0
                    ? $this->visibleColor($toneB, $symbolBackdrop, $symbolIsDark)
                    : $pair[1]],
            ],
        ];

        // If gradient start and end resolve identically (monochrome fallback), keep crisp two-step metallic gradient.
        if (strcasecmp($primaryGradient['stops'][0]['color'], $primaryGradient['stops'][1]['color']) === 0) {
            $primaryGradient['stops'] = [
                ['offset' => 0, 'color' => $pair[0]],
                ['offset' => 1, 'color' => $pair[1]],
            ];
        }

        foreach ($asset->paths as $index => $path) {
            $tone = (int) ($path['tone'] ?? 1);

            if (isset($path['gradient']) && is_array($path['gradient']) && ! empty($path['gradient']['stops'])) {
                $fill = $path['gradient'];
            } elseif (! empty($path['preserve_color']) && ! empty($path['color'])) {
                $native = (string) $path['color'];
                $bg = $symbolBackdrop ?? '#FFFFFF';
                $fill = Color::contrast($native, $bg) >= 1.8
                    ? $native
                    : $this->visibleColor($native, $bg, $symbolIsDark);
            } elseif ($useGradient && $tone === 1) {
                $fill = $primaryGradient;
            } else {
                $fill = match ($tone) {
                    1 => $toneA,
                    2 => $toneB,
                    3 => $toneC,
                    default => $toneD,
                };
            }

            $entry = [
                'id' => 'symbol-path-'.($index + 1),
                'type' => 'path',
                'role' => 'logo-path',
                'd' => $path['d'],
                'fill' => $fill,
                'tone' => $tone,
                'fill_rule' => $path['fill_rule'] ?? null,
            ];

            if (isset($path['fill_opacity']) && (float) $path['fill_opacity'] < 0.99) {
                $entry['fill_opacity'] = (float) $path['fill_opacity'];
            }

            $paths[] = $entry;
        }

        return [
            'id' => 'logo-logo',
            'role' => 'logo',
            'type' => 'group',
            'left' => round($x, 2),
            'top' => round($y, 2),
            'originX' => 'center',
            'originY' => 'center',
            'scaleX' => round($size / $box, 5),
            'scaleY' => round($size / $box, 5),
            'angle' => 0,
            'pathBox' => $box,
            'asset_slug' => $asset->slug,
            'objects' => $paths,
        ];
    }

    private function textObject(
        string $id,
        string $role,
        string $text,
        float $left,
        float $top,
        string $originX,
        Font $font,
        float $fontSize,
        int $weight,
        float $charSpacing,
        float $lineHeight,
        string $fill,
        string $align,
        ?float $width,
        bool $uppercase,
        float $opacity = 1.0,
        array $extra = [],
    ): array {
        return array_merge([
            'id' => $id,
            'role' => $role,
            'type' => 'i-text',
            'text' => $text,
            'left' => round($left, 2),
            'top' => round($top, 2),
            'originX' => $originX,
            'originY' => 'top',
            'fontFamily' => $font->family,
            'fontWeight' => $weight,
            'fontSize' => round($fontSize, 2),
            'charSpacing' => round($charSpacing, 2),
            'lineHeight' => $lineHeight,
            'fill' => $fill,
            'textAlign' => $align,
            'width' => $width !== null ? round($width, 2) : null,
            'isUppercase' => $uppercase,
            'opacity' => $opacity,
        ], $extra);
    }

    private function ribbonShape(float $cx, float $cy, string $fill, ?string $stroke = null): array
    {
        $xL = (int) ($cx - 210);
        $xR = (int) ($cx + 210);
        $xNotchL = (int) ($cx - 190);
        $xNotchR = (int) ($cx + 190);
        $yTop = (int) ($cy - 24);
        $yMid = (int) ($cy - 44);
        $yBot = (int) ($cy + 34);
        $yBotMid = (int) ($cy + 14);
        $yNotch = (int) ($cy + 5);

        $d = sprintf(
            'M %d %d Q %d %d %d %d L %d %d L %d %d Q %d %d %d %d L %d %d Z',
            $xL, $yTop,
            (int) $cx, $yMid,
            $xR, $yTop,
            $xNotchR, $yNotch,
            $xR, $yBot,
            (int) $cx, $yBotMid,
            $xL, $yBot,
            $xNotchL, $yNotch,
        );

        return [
            'id' => 'shape-ribbon',
            'role' => 'shape',
            'type' => 'path',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'originX' => 'center',
            'originY' => 'center',
            'd' => $d,
            'fill' => $fill,
            'stroke' => $stroke,
            'strokeWidth' => $stroke !== null ? 2.5 : 0,
        ];
    }

    private function circleShape(string $id, float $cx, float $cy, float $radius, string $fill, ?string $stroke = null, float $strokeWidth = 0): array
    {
        return [
            'id' => $id,
            'role' => 'shape',
            'type' => 'circle',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'originX' => 'center',
            'originY' => 'center',
            'radius' => round($radius, 2),
            'fill' => $fill,
            'stroke' => $stroke,
            'strokeWidth' => $strokeWidth,
            'locked' => false,
        ];
    }

    private function shieldShape(float $cx, float $cy, ?string $stroke = null): array
    {
        $d = sprintf(
            'M%1$d %2$d L%3$d %4$d L%3$d %5$d C%3$d %6$d %7$d %8$d %1$d %9$d C%10$d %8$d %11$d %6$d %11$d %5$d L%11$d %4$d Z',
            (int) $cx,
            (int) ($cy - 180),
            (int) ($cx + 165),
            (int) ($cy - 130),
            (int) ($cy + 10),
            (int) ($cy + 110),
            (int) ($cx + 90),
            (int) ($cy + 175),
            (int) ($cy + 200),
            (int) ($cx - 90),
            (int) ($cx - 165),
        );

        return [
            'id' => 'shape-frame',
            'role' => 'shape',
            'type' => 'path',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'originX' => 'center',
            'originY' => 'center',
            'd' => $d,
            'fill' => $this->frameColor('shield'),
            'stroke' => $stroke,
            'strokeWidth' => $stroke !== null ? 3 : 0,
        ];
    }

    private function frameColor(string $layout): string
    {
        return match ($layout) {
            'badge' => '#050748',
            'shield' => '#050748',
            default => 'none',
        };
    }

    /**
     * @return array{0: ?string, 1: bool}
     */
    private function resolveBackground(string $mode, ColorPalette $palette, ?string $cardBackground = null): array
    {
        return match ($mode) {
            'transparent' => [null, false],
            'dark' => [Color::shade($palette->colors[0] ?? '#0B0B14', -0.35), true],
            'light' => ['#FFFFFF', false],
            default => $cardBackground !== null
                ? [$cardBackground, ! Color::isLight($cardBackground)]
                : ($palette->is_dark
                    ? [Color::shade($palette->colors[0] ?? '#0B0B14', -0.35), true]
                    : [null, false]),
        };
    }

    private function inkFor(ColorPalette $palette, ?string $background, bool $isDark): string
    {
        $preferred = $palette->colors[0] ?? '#111827';
        $bg = $background ?? '#FFFFFF';

        if (Color::contrast($preferred, $bg) >= 4.5) {
            return $preferred;
        }

        if ($isDark) {
            $support = $palette->colors[2] ?? null;

            if (is_string($support) && Color::contrast($support, $bg) >= 4.5) {
                return $support;
            }

            return '#FFFFFF';
        }

        return Color::shade($preferred, -0.45);
    }

    private function visibleColor(?string $color, ?string $background, bool $isDark): string
    {
        $color ??= '#111827';
        $bg = $background ?? '#FFFFFF';

        // WCAG / identity-design non-text minimum contrast >= 3.1:1 (prevents cyan/yellow on white).
        if (Color::contrast($color, $bg) >= 3.1) {
            return $color;
        }

        if ($isDark) {
            $lightened = Color::shade($color, 0.72);

            return Color::contrast($lightened, $bg) >= 3.1 ? $lightened : '#FFFFFF';
        }

        $darkened = Color::shade($color, -0.42);

        return Color::contrast($darkened, $bg) >= 3.1 ? $darkened : '#111827';
    }

    private function weightFor(string $style, Font $font, bool $isDark = false): int
    {
        $preferred = match (true) {
            in_array($style, ['bold', 'mascot', 'retro', 'tech'], true) => 800,
            in_array($style, ['minimal', 'elegant', 'luxury'], true) => 500,
            in_array($style, ['vintage', 'corporate', 'geometric'], true) => 600,
            default => 700,
        };

        if ($isDark && $preferred >= 700) {
            $preferred -= 100;
        }

        $weights = $font->weights ?? [400, 700];
        $closest = $weights[0];
        $bestDiff = PHP_INT_MAX;

        foreach ($weights as $weight) {
            $diff = abs($weight - $preferred);

            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $closest = $weight;
            }
        }

        return (int) $closest;
    }

    private function trackingFor(string $style, string $layout): float
    {
        if ($layout === 'wordmark') {
            return 160.0;
        }

        return match (true) {
            in_array($style, ['luxury', 'elegant', 'minimal'], true) => 120.0,
            in_array($style, ['playful', 'organic', 'mascot'], true) => 0.0,
            default => 30.0,
        };
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $words = array_values(array_filter($words, fn (string $word) => $word !== ''));

        if (count($words) >= 2) {
            return mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1);
        }

        return mb_substr($words[0] ?? $name, 0, 2);
    }
}
