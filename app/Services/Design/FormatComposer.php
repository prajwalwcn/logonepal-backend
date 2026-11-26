<?php

namespace App\Services\Design;

use App\Services\Design\Concerns\ComposesBrand;
use App\Services\Design\Concerns\ComposesCreative;
use App\Services\Design\Concerns\ComposesMarketing;
use App\Services\Logo\DesignDocument;
use App\Services\Logo\TextMetrics;
use App\Support\Color;

/**
 * Composes any design format into the canonical, editable design document.
 *
 * The composer is pure computation: no database, no I/O. Each layout family
 * receives a resolved context (canvas, palette, type, copy) and returns
 * Fabric-compatible objects, which the existing SVG renderer and export
 * pipeline already understand.
 */
class FormatComposer
{
    use ComposesBrand;
    use ComposesCreative;
    use ComposesMarketing;

    public function __construct(private readonly TextMetrics $metrics)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function compose(DesignFormat $format, array $input): array
    {
        $context = $this->context($format, $input);
        $method = 'layout'.ucfirst($format->layout);

        if (! method_exists($this, $method)) {
            $method = 'layoutSocial';
        }

        $objects = [$this->backgroundObject($context)];
        $objects = array_merge($objects, $this->{$method}($context));

        return DesignDocument::make($objects, [
            'width' => $format->width,
            'height' => $format->height,
            'background' => $context['transparent'] ? 'transparent' : 'solid',
        ], [
            'layout' => $format->layout,
            'variant' => $format->variant,
            'format' => $format->slug,
            'category' => $format->category,
            'style' => $context['style'],
            'font' => $context['font'],
            'palette' => $context['colors'],
            'is_dark' => $context['dark'],
            'bounds' => ['x' => 0, 'y' => 0, 'width' => $format->width, 'height' => $format->height],
        ])->toArray();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function context(DesignFormat $format, array $input): array
    {
        $width = $format->width;
        $height = $format->height;
        $unit = min($width, $height) / 1000;

        $colors = array_values(array_filter(
            array_map(fn ($color) => is_string($color) ? trim($color) : '', $input['colors'] ?? []),
            fn (string $color) => (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $color)
        ));

        $fallback = DesignFormats::suggestedColors($format->category);

        while (count($colors) < 3) {
            $colors[] = $fallback[count($colors)] ?? '#111827';
        }

        $colors = array_slice($colors, 0, 3);
        $rawStyle = (string) ($input['style'] ?? 'editorial');
        $style = preg_match('/^[a-z0-9-]+$/', $rawStyle) ? $rawStyle : 'editorial';

        $forceDark = in_array($format->variant, ['neon', 'overlay', 'album', 'episode', 'twitch', 'intro', 'dashboard'], true)
            || $format->category === 'video';

        $primary = $colors[0];
        $accentSeed = $colors[1];
        $supportSeed = $colors[2];

        $bgFill = ['type' => 'solid', 'color' => '#FFFFFF'];
        $bg = '#FFFFFF';
        $dark = false;

        if ($style === 'bold') {
            $bg = $primary;
            $dark = ! Color::isLight($primary);
            $bgFill = ['type' => 'solid', 'color' => $bg];
        } elseif ($style === 'spotlight') {
            $bg = '#080C24';
            $dark = true;
            $bgFill = [
                'type' => 'linear',
                'angle' => 160,
                'stops' => [
                    ['offset' => 0, 'color' => '#080C24'],
                    ['offset' => 1, 'color' => Color::shade($primary, -0.35)],
                ],
            ];
        } elseif ($style === 'retro') {
            $bg = '#EAE6D7';
            $dark = false;
            $bgFill = ['type' => 'solid', 'color' => $bg];
        } elseif ($style === 'editorial') {
            $bg = $forceDark ? '#0B1120' : '#FFFBF0';
            $dark = $forceDark;
            $bgFill = ['type' => 'solid', 'color' => $bg];
        } elseif ($style === 'kinetic') {
            $bg = $forceDark ? '#0F172A' : '#F8FAFC';
            $dark = $forceDark;
            $bgFill = ['type' => 'solid', 'color' => $bg];
        } elseif ($style === 'gradient') {
            $bg = $primary;
            $dark = ! Color::isLight($primary);
            $bgFill = [
                'type' => 'linear',
                'angle' => 135,
                'stops' => [
                    ['offset' => 0, 'color' => $primary],
                    ['offset' => 1, 'color' => $accentSeed],
                ],
            ];
        } else {
            $bg = $forceDark ? '#0B1120' : '#FFFFFF';
            $dark = $forceDark;
            $bgFill = ['type' => 'solid', 'color' => $bg];
        }

        $ink = $dark ? '#FFFFFF' : (Color::contrast($primary, $bg) >= 7 ? $primary : '#0F172A');
        $accent = $this->visible($accentSeed, $bg);
        $support = $this->visible($supportSeed, $bg);

        $fontName = (string) ($input['font'] ?? DesignFormats::suggestedFont($format->category));
        $font = DesignFormats::FONTS[$fontName] ?? [
            'family' => $fontName !== '' ? $fontName : 'Poppins',
            'category' => 'sans',
            'weight' => 700,
        ];

        $copy = [];

        foreach (DesignFormats::FIELD_DEFAULTS as $key => $value) {
            $copy[$key] = $format->defaults[$key] ?? $value;
        }

        foreach ($input as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $copy[$key] = $this->clean($value, $key === 'body' || $key === 'items' || $key === 'steps' ? 600 : 200);
            }
        }

        $brand = $copy['brand'] !== '' ? $copy['brand'] : 'Your brand';
        $transparent = in_array($format->layout, ['apparel', 'lineart'], true)
            || in_array($format->variant, ['craft', 'tattoo'], true);

        return array_merge($copy, [
            'width' => $width,
            'height' => $height,
            'unit' => $unit,
            'style' => $style,
            'variant' => $format->variant,
            'layout' => $format->layout,
            'category' => $format->category,
            'colors' => $colors,
            'bg' => $bg,
            'bg_fill' => $bgFill,
            'dark' => $dark,
            'ink' => $ink,
            'accent' => $accent,
            'support' => $support,
            'muted' => Color::shade($ink, $dark ? -0.38 : 0.52),
            'font' => $font['family'],
            'font_category' => $font['category'],
            'weight' => (int) $font['weight'],
            'brand' => $brand,
            'initials' => $this->initials($brand, (string) ($input['initials'] ?? '')),
            'no_logo' => ! empty($input['no_logo']),
            'symbol' => empty($input['no_logo']) && is_array($input['symbol'] ?? null) ? $input['symbol'] : null,
            'logo_image' => empty($input['no_logo']) && is_string($input['logo_image'] ?? null)
                ? $input['logo_image']
                : (empty($input['no_logo']) && in_array($format->slug ?? '', ['instagram-post', 'facebook-cover', 'instagram-story', 'social-post', 'youtube-cover', 'linkedin-banner'], true) ? '/logodesignnepal/logo.png' : null),
            'qr_image' => is_string($input['qr_image'] ?? null) ? $input['qr_image'] : null,
            'transparent' => $transparent,
            'card_side' => ($input['card_side'] ?? 'front') === 'back' ? 'back' : 'front',
            'card_theme' => is_array($input['card_theme'] ?? null) ? $input['card_theme'] : null,
            'stamp_theme' => is_array($input['stamp_theme'] ?? null) ? $input['stamp_theme'] : null,
            'variant_seed' => (int) ($input['variant_seed'] ?? 0),
            'body_font' => (string) ($input['body_font'] ?? $font['family']),
            'headline_weight' => (int) ($input['headline_weight'] ?? $font['weight']),
            'headline_case' => (string) ($input['headline_case'] ?? 'upper'),
            'headline_tracking' => (float) ($input['headline_tracking'] ?? 25),
            'headline_scale' => (float) ($input['headline_scale'] ?? 1.0),
        ]);
    }

    private function clean(string $value, int $max): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<string, mixed>
     */
    private function backgroundObject(array $c): array
    {
        return [
            'id' => 'background',
            'role' => 'background',
            'type' => 'rect',
            'left' => 0,
            'top' => 0,
            'width' => $c['width'],
            'height' => $c['height'],
            'fill' => $c['transparent'] ? ['type' => 'none'] : $c['bg_fill'],
            'selectable' => false,
            'locked' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rect(string $id, float $x, float $y, float $w, float $h, mixed $fill, float $rx = 0, float $opacity = 1.0, float $angle = 0.0, ?string $stroke = null, float $strokeWidth = 0.0, ?array $dash = null, ?float $ry = null): array
    {
        $obj = [
            'id' => $id,
            'role' => 'shape',
            'type' => 'rect',
            'left' => round($x, 2),
            'top' => round($y, 2),
            'width' => round($w, 2),
            'height' => round($h, 2),
            'rx' => round($rx, 2),
            'fill' => is_string($fill) ? ['type' => 'solid', 'color' => $fill] : $fill,
            'opacity' => $opacity,
            'angle' => $angle,
        ];

        if ($ry !== null) {
            $obj['ry'] = round($ry, 2);
        }

        if ($stroke !== null) {
            $obj['stroke'] = $stroke;
            $obj['strokeWidth'] = round($strokeWidth, 2);
        }

        if ($dash !== null) {
            $obj['strokeDashArray'] = $dash;
        }

        return $obj;
    }

    /**
     * @return array<string, mixed>
     */
    protected function circle(string $id, float $cx, float $cy, float $r, mixed $fill, ?string $stroke = null, float $strokeWidth = 0.0, float $opacity = 1.0, ?array $dash = null): array
    {
        $obj = [
            'id' => $id,
            'role' => 'shape',
            'type' => 'circle',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'radius' => round($r, 2),
            'fill' => is_string($fill) ? ['type' => 'solid', 'color' => $fill] : $fill,
            'stroke' => $stroke,
            'strokeWidth' => round($strokeWidth, 2),
            'opacity' => $opacity,
        ];

        if ($dash !== null) {
            $obj['strokeDashArray'] = $dash;
        }

        return $obj;
    }

    /**
     * @return array<string, mixed>
     */
    protected function imageObject(string $id, string $role, string $src, float $left, float $top, float $width, float $height): array
    {
        return [
            'id' => $id,
            'role' => $role,
            'type' => 'image',
            'left' => round($left, 2),
            'top' => round($top, 2),
            'originX' => 'center',
            'originY' => 'center',
            'width' => round($width, 2),
            'height' => round($height, 2),
            'scaleX' => 1,
            'scaleY' => 1,
            'angle' => 0,
            'src' => $src,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function oval(string $id, float $cx, float $cy, float $rx, float $ry, mixed $fill = 'none', ?string $stroke = null, float $strokeWidth = 0.0, float $opacity = 1.0, ?array $dash = null): array
    {
        return $this->rect($id, $cx - $rx, $cy - $ry, $rx * 2, $ry * 2, $fill, $rx, $opacity, 0.0, $stroke, $strokeWidth, $dash, $ry);
    }

    /**
     * @return array<string, mixed>
     */
    protected function path(string $id, string $d, mixed $fill = 'none', ?string $stroke = null, float $strokeWidth = 0.0, float $opacity = 1.0): array
    {
        $obj = [
            'id' => $id,
            'role' => 'shape',
            'type' => 'path',
            'd' => $d,
            'fill' => is_string($fill) ? ['type' => 'solid', 'color' => $fill] : $fill,
            'opacity' => $opacity,
        ];

        if ($stroke !== null) {
            $obj['stroke'] = $stroke;
            $obj['strokeWidth'] = round($strokeWidth, 2);
        }

        return $obj;
    }

    /**
     * Generate an authentic N-point scalloped rosette path for corporate and common seals.
     */
    protected function rosettePath(float $cx, float $cy, float $rIn, float $rOut, int $points = 24): string
    {
        $d = '';
        for ($i = 0; $i < $points; $i++) {
            $a1 = ($i * 2 * M_PI) / $points;
            $aMid = (($i + 0.5) * 2 * M_PI) / $points;
            $a2 = ((($i + 1) % $points) * 2 * M_PI) / $points;

            $x1 = round($cx + $rIn * cos($a1), 2);
            $y1 = round($cy + $rIn * sin($a1), 2);

            $xc = round($cx + $rOut * cos($aMid), 2);
            $yc = round($cy + $rOut * sin($aMid), 2);

            $x2 = round($cx + $rIn * cos($a2), 2);
            $y2 = round($cy + $rIn * sin($a2), 2);

            if ($i === 0) {
                $d .= "M $x1 $y1 ";
            }
            $d .= "Q $xc $yc $x2 $y2 ";
        }

        return $d.'Z';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rule(string $id, float $x, float $y, float $w, string $color, float $thickness = 2.0, float $opacity = 1.0): array
    {
        return $this->rect($id, $x, $y, $w, max(1.0, $thickness), $color, 0, $opacity);
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<string, mixed>
     */
    protected function text(string $id, string $role, string $value, float $left, float $top, float $size, string $fill, ?array $c = null, int $weight = 0, float $charSpacing = 0.0, string $align = 'left', float $opacity = 1.0, array $extra = []): array
    {
        $font = !empty($c['font']) ? (is_array($c['font']) ? ($c['font']['family'] ?? 'Inter') : (string) $c['font']) : 'Inter';
        $fontWeight = $weight ?: (!empty($c['weight']) ? (int) $c['weight'] : 400);

        return array_merge([
            'id' => $id,
            'role' => $role,
            'type' => 'i-text',
            'text' => $value,
            'left' => round($left, 2),
            'top' => round($top, 2),
            'originX' => match ($align) {
                'center' => 'center',
                'right' => 'right',
                default => 'left',
            },
            'originY' => 'top',
            'fontFamily' => $font,
            'fontWeight' => $fontWeight,
            'fontSize' => round($size, 2),
            'charSpacing' => round($charSpacing, 1),
            'lineHeight' => 1.12,
            'fill' => $fill,
            'textAlign' => $align,
            'width' => null,
            'isUppercase' => false,
            'opacity' => $opacity,
        ], $extra);
    }

    /**
     * Fit copy to a width and line budget.
     *
     * @param  array<string, mixed>  $c
     * @return array{text: string, size: float, width: float, lines: array<int, string>}
     */
    protected function block(string $value, float $maxWidth, float $preferred, int $maxLines, array $c, int $weight = 0, float $charSpacing = 0.0, bool $uppercase = false): array
    {
        $value = trim($value);

        if ($uppercase) {
            $value = mb_strtoupper($value);
        }

        if ($value === '') {
            return ['text' => '', 'size' => $preferred, 'width' => 0.0, 'lines' => []];
        }

        $weight = $weight ?: $c['weight'];
        $size = max(6.0, $preferred);
        $lines = [$value];
        $widest = $this->lineWidth($value, $size, $c, $weight, $charSpacing);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $lines = $this->wrapText($value, $maxWidth, $size, $c, $weight, $charSpacing);
            $widest = max(array_map(
                fn (string $line) => $this->lineWidth($line, $size, $c, $weight, $charSpacing),
                $lines
            ));

            if ($widest <= $maxWidth + 0.5 && count($lines) <= $maxLines) {
                break;
            }

            if ($size <= 7) {
                break;
            }

            $size *= 0.92;
        }

        return [
            'text' => implode("\n", $lines),
            'size' => round($size, 2),
            'width' => round($widest, 2),
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, mixed>  $c
     */
    protected function fitOne(string $value, float $maxWidth, float $preferred, array $c, int $weight = 0, float $charSpacing = 0.0): float
    {
        if ($value === '') {
            return $preferred;
        }

        $weight = $weight ?: $c['weight'];
        $atHundred = $this->lineWidth($value, 100.0, $c, $weight, $charSpacing);

        if ($atHundred <= 0) {
            return $preferred;
        }

        return round(max(5.0, min($preferred, $maxWidth / $atHundred * 100)), 2);
    }

    /**
     * @param  array<string, mixed>  $c
     */
    protected function lineWidth(string $value, float $size, array $c, int $weight, float $charSpacing): float
    {
        return $this->metrics->width($value, $size, $c['font_category'], $weight, $size * $charSpacing / 1000);
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, string>
     */
    protected function wrapText(string $value, float $maxWidth, float $size, array $c, int $weight, float $charSpacing): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        if ($this->lineWidth($value, $size, $c, $weight, $charSpacing) <= $maxWidth) {
            return [$value];
        }

        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', $value) ?: [] as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;

            if ($this->lineWidth($candidate, $size, $c, $weight, $charSpacing) <= $maxWidth) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }

            if ($this->lineWidth($word, $size, $c, $weight, $charSpacing) <= $maxWidth) {
                $current = $word;

                continue;
            }

            $chunk = '';

            foreach (mb_str_split($word) as $char) {
                $candidate = $chunk.$char;

                if ($chunk !== '' && $this->lineWidth($candidate, $size, $c, $weight, $charSpacing) > $maxWidth) {
                    $lines[] = $chunk;
                    $chunk = $char;
                } else {
                    $chunk = $candidate;
                }
            }

            $current = $chunk;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$value];
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, string>
     */
    protected function paragraph(string $value, float $maxWidth, float $size, array $c, int $weight = 0, float $charSpacing = 0.0): array
    {
        $lines = [];

        foreach (preg_split('/\n+/', trim($value)) ?: [] as $paragraph) {
            foreach ($this->wrapText($paragraph, $maxWidth, $size, $c, $weight ?: $c['weight'], $charSpacing) as $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    protected function visible(string $color, string $bg): string
    {
        if (Color::contrast($color, $bg) >= 3.2) {
            return $color;
        }

        $shaded = Color::shade($color, Color::isLight($bg) ? -0.52 : 0.52);

        if (Color::contrast($shaded, $bg) >= 3.2) {
            return $shaded;
        }

        return Color::readableInk($bg);
    }

    protected function initials(string $brand, string $explicit = ''): string
    {
        $explicit = preg_replace('/[^A-Za-z0-9]/', '', $explicit) ?? '';

        if ($explicit !== '') {
            return mb_strtoupper(mb_substr($explicit, 0, 3));
        }

        $words = preg_split('/\s+/', trim($brand)) ?: [];
        $letters = '';

        foreach ($words as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', '', $word) ?? '';
            if ($word !== '') {
                $letters .= mb_substr($word, 0, 1);
            }
            if (mb_strlen($letters) >= 2) {
                break;
            }
        }

        return mb_strtoupper($letters !== '' ? $letters : mb_substr(trim($brand), 0, 1));
    }

    /**
     * Brand mark: an imported symbol when one is supplied, otherwise a
     * monogram tile derived from the brand name.
     *
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function mark(array $c, float $cx, float $cy, float $size, ?string $tint = null): array
    {
        if (! empty($c['no_logo'])) {
            return [];
        }

        if (! empty($c['logo_image']) && is_string($c['logo_image'])) {
            return [$this->imageObject('logo-uploaded', 'logo', $c['logo_image'], $cx, $cy, $size, $size)];
        }

        if (is_array($c['symbol']) && ($c['symbol']['asset_type'] ?? 'svg') === 'png' && ! empty($c['symbol']['image_url'])) {
            return [$this->imageObject('logo-symbol-img', 'logo', $c['symbol']['image_url'], $cx, $cy, $size, $size)];
        }

        if (is_array($c['symbol']) && ! empty($c['symbol']['paths'])) {
            return [$this->symbolGroup($c, $cx, $cy, $size, $tint)];
        }

        $tint ??= $c['accent'];
        $ink = Color::readableInk($tint);
        $initials = $c['initials'] !== '' ? $c['initials'] : mb_substr($c['brand'], 0, 1);
        $fontSize = $this->fitOne($initials, $size * 0.72, $size * 0.5, $c, 800, 40);

        return [
            $this->rect('mark-tile', $cx - $size / 2, $cy - $size / 2, $size, $size, $tint, $size * 0.22),
            $this->text(
                'mark-initials',
                'shape',
                $initials,
                $cx,
                $cy - ($fontSize * 1.12) / 2 + $size * 0.02,
                $fontSize,
                $ink,
                $c,
                800,
                30,
                'center'
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<string, mixed>
     */
    protected function symbolGroup(array $c, float $cx, float $cy, float $size, ?string $tint = null): array
    {
        $symbol = $c['symbol'];
        $parts = preg_split('/[\s,]+/', trim((string) ($symbol['view_box'] ?? '0 0 64 64'))) ?: [];
        $boxWidth = (float) ($parts[2] ?? 64);
        $boxHeight = (float) ($parts[3] ?? $boxWidth);
        $box = max(1.0, $boxWidth, $boxHeight);
        $scale = $size / $box;

        $primary = $tint ?? $c['accent'];
        $secondary = $this->visible($c['support'], $c['bg']);
        $tertiary = $c['ink'];

        $paths = [];

        foreach (array_slice($symbol['paths'] ?? [], 0, 80) as $index => $path) {
            $d = (string) ($path['d'] ?? '');

            if ($d === '') {
                continue;
            }

            $tone = (int) ($path['tone'] ?? 1);
            $fill = match ($tone) {
                2 => $secondary,
                3 => $tertiary,
                default => $primary,
            };

            if (! empty($path['preserve_color']) && ! empty($path['color'])) {
                $fill = (string) $path['color'];
            }

            $paths[] = [
                'id' => 'symbol-path-'.($index + 1),
                'type' => 'path',
                'role' => 'logo-path',
                'd' => $d,
                'fill' => $fill,
                'tone' => $tone,
                'fill_rule' => $path['fill_rule'] ?? null,
                'fill_opacity' => $path['fill_opacity'] ?? null,
            ];
        }

        return [
            'id' => 'mark-symbol',
            'role' => 'logo',
            'type' => 'group',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'originX' => 'center',
            'originY' => 'center',
            'scaleX' => round($scale, 4),
            'scaleY' => round($scale, 4),
            'angle' => 0,
            'pathBox' => $box,
            'objects' => $paths,
        ];
    }

    /**
     * Draw an arbitrary path (in a 0..1000 box) at a canvas position.
     *
     * @return array<string, mixed>
     */
    protected function pathGroup(string $id, string $d, float $cx, float $cy, float $size, string $fill, float $angle = 0.0, ?float $strokeWidth = null, ?string $stroke = null): array
    {
        $path = [
            'id' => $id.'-path',
            'type' => 'path',
            'role' => 'logo-path',
            'd' => $d,
            'fill' => $fill,
        ];

        if ($stroke !== null) {
            $path['stroke'] = $stroke;
            $path['strokeWidth'] = $strokeWidth ?? 0;
        }

        return [
            'id' => $id,
            'role' => 'shape',
            'type' => 'group',
            'left' => round($cx, 2),
            'top' => round($cy, 2),
            'originX' => 'center',
            'originY' => 'center',
            'scaleX' => round($size / 1000, 4),
            'scaleY' => round($size / 1000, 4),
            'angle' => $angle,
            'pathBox' => 1000,
            'objects' => [$path],
        ];
    }

    /**
     * Split body copy into rows, supporting "Name|Price" style rows.
     *
     * @return array<int, array<int, string>>
     */
    protected function rows(string $value, int $limit = 8): array
    {
        $rows = [];

        foreach (preg_split('/\n+/', trim($value)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $cells = array_map('trim', explode('|', $line));
            $rows[] = $cells;

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }
}
