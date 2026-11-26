<?php

namespace App\Services\Logo;

use App\Support\Color;

/**
 * Renders the canonical design document to SVG. This is the authoritative
 * renderer used for gallery previews, mockups and every export format.
 */
class SvgRenderingService
{
    private const TRANSPARENT = 'none';

    public function __construct(private readonly SvgSanitizer $sanitizer)
    {
    }

    /**
     * @param  array{transparent?: bool, monochrome?: ?string, background?: ?string}  $options
     */
    public function render(array $design, array $options = []): string
    {
        $canvas = $design['canvas'] ?? [];
        $canvasWidth = (float) ($canvas['width'] ?? DesignDocument::CANVAS_WIDTH);
        $canvasHeight = (float) ($canvas['height'] ?? DesignDocument::CANVAS_HEIGHT);

        $viewX = 0.0;
        $viewY = 0.0;
        $width = $canvasWidth;
        $height = $canvasHeight;
        $bounds = $design['meta']['bounds'] ?? null;

        if (($options['crop'] ?? false) && is_array($bounds) && ($bounds['width'] ?? 0) > 0) {
            $viewX = (float) $bounds['x'];
            $viewY = (float) $bounds['y'];
            $width = (float) $bounds['width'];
            $height = (float) $bounds['height'];
        }

        $transparent = (bool) ($options['transparent'] ?? false);
        $monochrome = $options['monochrome'] ?? null;
        $defs = [];
        $body = '';

        foreach ($design['objects'] ?? [] as $object) {
            $body .= $this->renderObject($object, $defs, $transparent, $monochrome);
        }

        $defsMarkup = $defs === [] ? '' : '<defs>'.implode('', $defs).'</defs>';

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$s" height="%2$s" viewBox="%3$s %4$s %1$s %2$s" role="img">%5$s%6$s</svg>',
            $this->number($width),
            $this->number($height),
            $this->number($viewX),
            $this->number($viewY),
            $defsMarkup,
            $body
        );
    }

    public function sanitize(string $svg): string
    {
        return $this->sanitizer->sanitize($svg);
    }

    /**
     * Wrap a rendered logo in a standalone HTML document for headless Chrome
     * rasterisation, loading the Google Fonts required by the design.
     */
    public function renderHtmlDocument(string $svg, array $fontFamilies = [], ?string $background = '#FFFFFF'): string
    {
        $families = array_values(array_unique(array_filter($fontFamilies)));
        $fontQuery = implode('&', array_map(
            fn (string $family) => 'family='.str_replace(' ', '+', $family).':wght@300;400;500;600;700;800',
            $families
        ));

        $fonts = $fontFamilies === []
            ? ''
            : '<link rel="preconnect" href="https://fonts.googleapis.com">'
                .'<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                .'<link href="https://fonts.googleapis.com/css2?'.$fontQuery.'&display=block" rel="stylesheet">';

        $bg = $background ?? 'transparent';

        return '<!DOCTYPE html><html><head><meta charset="utf-8">'.$fonts
            .'<style>html,body{margin:0;padding:0;background:'.$bg.';}'
            .'svg{display:block;width:100%;height:auto;}</style></head><body>'
            .$svg
            .'</body></html>';
    }

    /**
     * @param  array<int, string>  $defs
     */
    private function renderObject(array $object, array &$defs, bool $transparent, ?string $monochrome): string
    {
        if (($object['visible'] ?? true) === false) {
            return '';
        }

        $type = $object['type'] ?? 'rect';
        $role = $object['role'] ?? null;
        $opacity = isset($object['opacity']) ? (float) $object['opacity'] : 1.0;
        $opacityAttr = $opacity < 1 ? ' opacity="'.$this->number($opacity).'"' : '';

        if ($type === 'group') {
            return $this->renderGroup($object, $defs, $monochrome, $opacityAttr);
        }

        if ($type === 'image') {
            $src = (string) ($object['src'] ?? '');
            if ($src === '') {
                return '';
            }

            $cx = (float) ($object['left'] ?? 325);
            $cy = (float) ($object['top'] ?? 260);
            $w = (float) ($object['width'] ?? 180) * (float) ($object['scaleX'] ?? 1);
            $h = (float) ($object['height'] ?? $w) * (float) ($object['scaleY'] ?? 1);
            $x = $cx - $w / 2;
            $y = $cy - $h / 2;
            $transform = $this->centerTransform($cx, $cy, array_merge($object, ['scaleX' => 1, 'scaleY' => 1]));

            return sprintf(
                '<image x="%s" y="%s" width="%s" height="%s" href="%s" preserveAspectRatio="xMidYMid meet"%s%s/>',
                $this->number($x),
                $this->number($y),
                $this->number($w),
                $this->number($h),
                e($src),
                $transform,
                $opacityAttr
            );
        }

        if ($type === 'i-text') {
            return $this->renderText($object, $defs, $monochrome, $opacityAttr);
        }

        if ($type === 'circle') {
            $fill = $this->paint($object['fill'] ?? null, $monochrome);
            $cx = (float) ($object['left'] ?? 0);
            $cy = (float) ($object['top'] ?? 0);
            $r = (float) ($object['radius'] ?? 0);
            $stroke = $this->paint($object['stroke'] ?? null, $monochrome);
            $strokeWidth = (float) ($object['strokeWidth'] ?? 0);

            if ($fill === self::TRANSPARENT && ($stroke === null || $strokeWidth <= 0)) {
                return '';
            }

            $transform = $this->centerTransform($cx, $cy, $object);

            $dashAttr = ! empty($object['strokeDashArray']) && is_array($object['strokeDashArray'])
                ? ' stroke-dasharray="'.implode(' ', array_map([$this, 'number'], $object['strokeDashArray'])).'"'
                : '';

            return sprintf(
                '<circle cx="%s" cy="%s" r="%s" fill="%s"%s%s%s%s%s/>',
                $this->number($cx),
                $this->number($cy),
                $this->number($r),
                $fill,
                $stroke !== null ? ' stroke="'.$stroke.'"' : '',
                $stroke !== null && $strokeWidth > 0 ? ' stroke-width="'.$this->number($strokeWidth).'"' : '',
                $dashAttr,
                $transform,
                $opacityAttr
            );
        }

        if ($type === 'rect') {
            $fill = $object['fill'] ?? ['type' => 'solid', 'color' => '#EEEEEE'];
            $isBackground = $role === 'background';

            if ($isBackground && $transparent) {
                return '';
            }

            $paint = $this->paint($fill, $monochrome, $defs, $object['id'] ?? 'bg');
            $x = (float) ($object['left'] ?? 0);
            $y = (float) ($object['top'] ?? 0);
            $w = (float) ($object['width'] ?? 0);
            $h = (float) ($object['height'] ?? 0);
            $transform = $isBackground ? '' : $this->centerTransform($x + $w / 2, $y + $h / 2, $object);
            $stroke = isset($object['stroke']) ? $this->paint($object['stroke'], $monochrome) : null;
            $strokeWidth = (float) ($object['strokeWidth'] ?? 0);
            $dashAttr = ! empty($object['strokeDashArray']) && is_array($object['strokeDashArray'])
                ? ' stroke-dasharray="'.implode(' ', array_map([$this, 'number'], $object['strokeDashArray'])).'"'
                : '';

            $rx = (float) ($object['rx'] ?? 0);
            $ry = isset($object['ry']) ? (float) $object['ry'] : $rx;
            $ryAttr = $ry !== $rx ? ' ry="'.$this->number($ry).'"' : '';

            return sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="%s"%s fill="%s"%s%s%s%s%s/>',
                $this->number($x),
                $this->number($y),
                $this->number($w),
                $this->number($h),
                $this->number($rx),
                $ryAttr,
                $paint,
                $stroke !== null && $stroke !== self::TRANSPARENT ? ' stroke="'.$stroke.'"' : '',
                $stroke !== null && $stroke !== self::TRANSPARENT && $strokeWidth > 0 ? ' stroke-width="'.$this->number($strokeWidth).'"' : '',
                $dashAttr,
                $transform,
                $opacityAttr
            );
        }

        if ($type === 'line') {
            $stroke = $this->paint($object['fill'] ?? $object['stroke'] ?? ['type' => 'solid', 'color' => '#111827'], $monochrome) ?? '#111827';
            $x = (float) ($object['left'] ?? 0);
            $y = (float) ($object['top'] ?? 0);
            $w = (float) ($object['width'] ?? 0);
            $h = (float) ($object['height'] ?? 2);
            $transform = $this->centerTransform($x + $w / 2, $y + $h / 2, $object);

            return sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" fill="%s"%s%s/>',
                $this->number($x),
                $this->number($y),
                $this->number($w),
                $this->number($h),
                $stroke,
                $transform,
                $opacityAttr
            );
        }

        if ($type === 'path') {
            $fill = $this->paint($object['fill'] ?? null, $monochrome, $defs, ($object['id'] ?? 'path').'-'.count($defs));
            $stroke = $this->paint($object['stroke'] ?? null, $monochrome);
            $strokeWidth = (float) ($object['strokeWidth'] ?? 0);
            $d = (string) ($object['d'] ?? '');

            if ($d === '') {
                return '';
            }

            $rule = isset($object['fill_rule']) && $object['fill_rule'] !== null
                ? ' fill-rule="'.e($object['fill_rule']).'" clip-rule="'.e($object['fill_rule']).'"'
                : '';

            $fillOpacity = isset($object['fill_opacity']) && (float) $object['fill_opacity'] < 0.99
                ? ' fill-opacity="'.$this->number((float) $object['fill_opacity']).'"'
                : '';

            $dashAttr = ! empty($object['strokeDashArray']) && is_array($object['strokeDashArray'])
                ? ' stroke-dasharray="'.implode(' ', array_map([$this, 'number'], $object['strokeDashArray'])).'"'
                : '';

            $transform = $role === 'logo-path'
                ? ''
                : $this->centerTransform((float) ($object['left'] ?? 325), (float) ($object['top'] ?? 260), $object);

            return sprintf(
                '<path d="%s" fill="%s"%s%s%s%s%s%s%s/>',
                e($d),
                $fill,
                $fillOpacity,
                $rule,
                $stroke !== null && $stroke !== self::TRANSPARENT ? ' stroke="'.$stroke.'"' : '',
                $stroke !== null && $stroke !== self::TRANSPARENT && $strokeWidth > 0 ? ' stroke-width="'.$this->number($strokeWidth).'"' : '',
                $dashAttr,
                $transform,
                $opacityAttr
            );
        }

        return '';
    }

    private function renderGroup(array $object, array &$defs, ?string $monochrome, string $opacityAttr): string
    {
        $x = (float) ($object['left'] ?? 0);
        $y = (float) ($object['top'] ?? 0);
        $scaleX = (float) ($object['scaleX'] ?? 1) * (($object['flipX'] ?? false) ? -1 : 1);
        $scaleY = (float) ($object['scaleY'] ?? 1) * (($object['flipY'] ?? false) ? -1 : 1);
        $angle = (float) ($object['angle'] ?? 0);
        $box = (float) ($object['pathBox'] ?? 64);

        $children = '';

        foreach ($object['objects'] ?? [] as $child) {
            $children .= $this->renderObject($child, $defs, false, $monochrome);
        }

        $transform = sprintf(
            'translate(%s %s)',
            $this->number($x),
            $this->number($y)
        );

        if ($angle !== 0.0) {
            $transform .= ' rotate('.$this->number($angle).')';
        }

        $transform .= sprintf(' scale(%s %s) translate(%s %s)', $this->number($scaleX), $this->number($scaleY), $this->number(-$box / 2), $this->number(-$box / 2));

        return '<g transform="'.$transform.'"'.$opacityAttr.'>'.$children.'</g>';
    }

    private function renderText(array $object, array &$defs, ?string $monochrome, string $opacityAttr): string
    {
        $text = (string) ($object['text'] ?? '');

        if ($text === '') {
            return '';
        }

        $fontSize = (float) ($object['fontSize'] ?? 32);
        $lineHeight = (float) ($object['lineHeight'] ?? 1.12);
        $lineHeightPx = $fontSize * $lineHeight;
        $charSpacing = (float) ($object['charSpacing'] ?? 0);
        $letterSpacing = round($fontSize * $charSpacing / 1000, 2);
        $align = (string) ($object['textAlign'] ?? 'left');
        $rawOrigin = (string) ($object['originX'] ?? '');
        $anchor = match ($rawOrigin) {
            'center' => 'middle',
            'right' => 'end',
            default => ($align === 'right' ? 'end' : ($align === 'center' ? 'middle' : 'start')),
        };
        $x = (float) ($object['left'] ?? 0);
        $top = (float) ($object['top'] ?? 0);
        $fill = $this->paint($object['fill'] ?? ['type' => 'solid', 'color' => '#111827'], $monochrome);
        $family = (string) ($object['fontFamily'] ?? 'Poppins');

        $stroke = $this->paint($object['stroke'] ?? null, $monochrome);
        $strokeWidth = (float) ($object['strokeWidth'] ?? 0);
        $strokeAttr = $stroke !== null && $stroke !== self::TRANSPARENT && $strokeWidth > 0
            ? ' stroke="'.$stroke.'" stroke-width="'.$this->number($strokeWidth).'" paint-order="stroke fill"'
            : '';

        $filterAttr = '';
        if (! empty($object['shadow']) && is_array($object['shadow'])) {
            $shadowId = 'shadow-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($object['id'] ?? 'text')).'-'.count($defs);
            $sColor = e((string) ($object['shadow']['color'] ?? 'rgba(0,0,0,0.28)'));
            $sBlur = $this->number((float) ($object['shadow']['blur'] ?? 6) / 2);
            $sDx = $this->number((float) ($object['shadow']['offsetX'] ?? 0));
            $sDy = $this->number((float) ($object['shadow']['offsetY'] ?? 4));
            $defs[] = sprintf(
                '<filter id="%s" x="-20%%" y="-20%%" width="140%%" height="140%%"><feDropShadow dx="%s" dy="%s" stdDeviation="%s" flood-color="%s"/></filter>',
                $shadowId, $sDx, $sDy, $sBlur, $sColor
            );
            $filterAttr = ' filter="url(#'.$shadowId.')"';
        }

        $effect = (string) ($object['textEffect'] ?? $object['effect'] ?? 'none');
        $strength = (float) ($object['effectStrength'] ?? 1.0);
        $lines = explode("\n", $text);
        $centerX = $anchor === 'middle' ? $x : $x + ((float) ($object['width'] ?? ($fontSize * mb_strlen($lines[0]) * 0.55))) / 2;
        $centerY = $top + (count($lines) * $lineHeightPx) / 2;

        $transformAttr = $this->centerTransform($centerX, $centerY, $object, $effect === 'angle' ? -8.0 * $strength : 0.0);

        $attributes = sprintf(
            'font-family="%s" font-size="%s" font-weight="%s" fill="%s" text-anchor="%s"%s%s%s%s%s',
            e($family),
            $this->number($fontSize),
            (int) ($object['fontWeight'] ?? 400),
            $fill,
            $anchor,
            $letterSpacing !== 0.0 ? ' letter-spacing="'.$this->number($letterSpacing).'"' : '',
            $strokeAttr,
            $filterAttr,
            $transformAttr,
            $opacityAttr
        );

        if (in_array($effect, ['arc-top', 'arc-bottom'], true) && count($lines) === 1) {
            $pathId = 'tp-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($object['id'] ?? 'text')).'-'.count($defs);
            $arcCx = (float) ($object['arcCenterX'] ?? $centerX);
            $arcCy = (float) ($object['arcCenterY'] ?? 260.0);
            $arcR = max(60.0, (float) ($object['arcRadius'] ?? 176.0));
            $sweep = $effect === 'arc-top' ? 1 : 0;

            $d = sprintf(
                'M %s %s A %s %s 0 0 %d %s %s',
                $this->number($arcCx - $arcR),
                $this->number($arcCy),
                $this->number($arcR),
                $this->number($arcR),
                $sweep,
                $this->number($arcCx + $arcR),
                $this->number($arcCy)
            );

            $defs[] = '<path id="'.$pathId.'" d="'.$d.'"/>';

            return '<text '.$attributes.'><textPath href="#'.$pathId.'" startOffset="50%" text-anchor="middle">'.$this->escapeText($text).'</textPath></text>';
        }

        if (in_array($effect, ['curve', 'arch', 'wave', 'flag'], true) && count($lines) === 1) {
            $pathId = 'tp-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($object['id'] ?? 'text')).'-'.count($defs);
            $spanWidth = max(140.0, (float) ($object['width'] ?? ($fontSize * mb_strlen($text) * 0.65)) * 1.15);
            $half = $spanWidth / 2;
            $baseline = $top + $fontSize * 0.85;
            $sag = ($effect === 'arch' ? 58.0 : 38.0) * $strength;

            if ($effect === 'curve' || $effect === 'arch') {
                $d = sprintf(
                    'M %s %s Q %s %s %s %s',
                    $this->number($centerX - $half),
                    $this->number($baseline + $sag * 0.35),
                    $this->number($centerX),
                    $this->number($baseline - $sag),
                    $this->number($centerX + $half),
                    $this->number($baseline + $sag * 0.35)
                );
            } else {
                $amp = ($effect === 'flag' ? 28.0 : 20.0) * $strength;
                $d = sprintf(
                    'M %s %s C %s %s, %s %s, %s %s',
                    $this->number($centerX - $half),
                    $this->number($baseline),
                    $this->number($centerX - $half * 0.33),
                    $this->number($baseline - $amp),
                    $this->number($centerX + $half * 0.33),
                    $this->number($baseline + $amp),
                    $this->number($centerX + $half),
                    $this->number($baseline)
                );
            }

            $defs[] = '<path id="'.$pathId.'" d="'.$d.'"/>';

            return '<text '.$attributes.'><textPath href="#'.$pathId.'" startOffset="50%" text-anchor="middle">'.$this->escapeText($text).'</textPath></text>';
        }

        $spans = '';

        foreach ($lines as $index => $line) {
            $baseline = $top + $lineHeightPx * $index + $fontSize * 0.8;
            $spans .= sprintf('<tspan x="%s" y="%s">%s</tspan>', $this->number($x), $this->number($baseline), $this->escapeText($line));
        }

        return '<text '.$attributes.'>'.$spans.'</text>';
    }

    private function centerTransform(float $cx, float $cy, array $object, float $extraSkewY = 0.0): string
    {
        $angle = (float) ($object['angle'] ?? 0);
        $scaleX = (float) ($object['scaleX'] ?? 1) * (($object['flipX'] ?? false) ? -1 : 1);
        $scaleY = (float) ($object['scaleY'] ?? 1) * (($object['flipY'] ?? false) ? -1 : 1);
        $skewY = (float) ($object['skewY'] ?? 0) + $extraSkewY;

        if ($angle === 0.0 && $scaleX === 1.0 && $scaleY === 1.0 && $skewY === 0.0) {
            return '';
        }

        $ops = [];

        if ($angle !== 0.0) {
            $ops[] = 'rotate('.$this->number($angle).' '.$this->number($cx).' '.$this->number($cy).')';
        }

        if ($skewY !== 0.0) {
            $ops[] = 'translate('.$this->number($cx).' '.$this->number($cy).') skewY('.$this->number($skewY).') translate('.$this->number(-$cx).' '.$this->number(-$cy).')';
        }

        if ($scaleX !== 1.0 || $scaleY !== 1.0) {
            $ops[] = 'translate('.$this->number($cx).' '.$this->number($cy).') scale('.$this->number($scaleX).' '.$this->number($scaleY).') translate('.$this->number(-$cx).' '.$this->number(-$cy).')';
        }

        return ' transform="'.implode(' ', $ops).'"';
    }

    private function paint(mixed $fill, ?string $monochrome, array &$defs = [], string $idSuffix = 'fill'): string
    {
        if ($monochrome !== null) {
            return $monochrome;
        }

        if ($fill === null) {
            return self::TRANSPARENT;
        }

        if (is_string($fill)) {
            if ($fill === '' || $fill === 'transparent' || $fill === 'rgba(0,0,0,0)') {
                return self::TRANSPARENT;
            }

            return $fill;
        }

        if (! is_array($fill)) {
            return self::TRANSPARENT;
        }

        $type = $fill['type'] ?? 'solid';

        if ($type === 'none') {
            return self::TRANSPARENT;
        }

        if ($type === 'solid') {
            $color = (string) ($fill['color'] ?? '#111827');

            return $color === '' ? self::TRANSPARENT : $color;
        }

        if ($type === 'linear' || $type === 'radial') {
            $gradientId = 'gradient-'.preg_replace('/[^a-zA-Z0-9_-]/', '', $idSuffix);
            $stops = '';

            foreach ($fill['stops'] ?? [] as $stop) {
                $stops .= sprintf(
                    '<stop offset="%s" stop-color="%s"/>',
                    $this->number((float) ($stop['offset'] ?? 0)),
                    e((string) ($stop['color'] ?? '#000000'))
                );
            }

            if ($type === 'linear') {
                $angle = (float) ($fill['angle'] ?? 90);
                $radians = deg2rad($angle);
                $x1 = 50 - cos($radians) * 50;
                $y1 = 50 - sin($radians) * 50;
                $x2 = 50 + cos($radians) * 50;
                $y2 = 50 + sin($radians) * 50;

                $defs[] = sprintf(
                    '<linearGradient id="%s" x1="%s%%" y1="%s%%" x2="%s%%" y2="%s%%">%s</linearGradient>',
                    $gradientId,
                    $this->number($x1),
                    $this->number($y1),
                    $this->number($x2),
                    $this->number($y2),
                    $stops
                );
            } else {
                $defs[] = sprintf('<radialGradient id="%s">%s</radialGradient>', $gradientId, $stops);
            }

            return 'url(#'.$gradientId.')';
        }

        return self::TRANSPARENT;
    }

    private function number(float $value): string
    {
        if (floor($value) == $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function escapeText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Build a monochrome copy of a design (used for black/white brand-kit exports).
     */
    public function monochrome(array $design, string $color): array
    {
        $copy = $design;

        $recolor = function (array $objects) use (&$recolor, $color): array {
            return array_map(function (array $object) use (&$recolor, $color) {
                if (($object['role'] ?? null) === 'background') {
                    $object['fill'] = ['type' => 'none'];

                    return $object;
                }

                if (($object['type'] ?? null) === 'image') {
                    return $object;
                }

                if (isset($object['fill'])) {
                    $object['fill'] = ['type' => 'solid', 'color' => $color];
                }

                if (! empty($object['objects'])) {
                    $object['objects'] = $recolor($object['objects']);
                }

                return $object;
            }, $objects);
        };

        $copy['objects'] = $recolor($copy['objects'] ?? []);

        return $copy;
    }

    /**
     * Perceived brightness of a design's dominant ink, used to pick preview frames.
     */
    public function dominantInk(array $design): string
    {
        foreach ($design['objects'] ?? [] as $object) {
            if (in_array($object['role'] ?? null, ['primary-text', 'logo'], true)) {
                if ($object['type'] === 'group') {
                    $fill = $object['objects'][0]['fill'] ?? null;
                } else {
                    $fill = $object['fill'] ?? null;
                }

                if (is_array($fill) && ! empty($fill['stops'][0]['color'])) {
                    return (string) $fill['stops'][0]['color'];
                }

                if (is_string($fill) && preg_match('/^#[0-9a-fA-F]{6}$/', $fill)) {
                    return $fill;
                }
            }
        }

        return (string) ($design['meta']['palette'][0] ?? '#111827');
    }
}
