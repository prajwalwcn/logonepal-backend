<?php

namespace App\Services\Logo;

/**
 * Lightweight, dependency-free text measurement.
 *
 * The server never has canvas font metrics available, so text widths are
 * approximated from per-glyph metrics tables. The same approximation is used
 * when fitting a brand name into a layout, which keeps server renders and the
 * browser canvas within a few pixels of each other.
 */
class TextMetrics
{
    /** Width multipliers, in em, for glyph groups. */
    private const NARROW = 'ijltrfI.,:;\'"!|`';

    private const WIDE = 'MWmw@%';

    private const CATEGORY_FACTORS = [
        'sans' => 1.0,
        'serif' => 1.02,
        'display' => 1.06,
        'script' => 0.94,
        'mono' => 1.12,
    ];

    public function width(string $text, float $fontSize, string $category = 'sans', int $weight = 400, float $letterSpacing = 0): float
    {
        if ($text === '') {
            return 0;
        }

        $factor = self::CATEGORY_FACTORS[$category] ?? 1.0;
        $factor *= $weight >= 700 ? 1.035 : 1.0;

        $em = 0.0;

        foreach (mb_str_split($text) as $char) {
            $em += $this->glyphWidth($char);
        }

        $width = $em * $fontSize * $factor;

        return $width + $letterSpacing * max(0, mb_strlen($text) - 1);
    }

    private function glyphWidth(string $char): float
    {
        if ($char === ' ') {
            return 0.28;
        }

        if (str_contains(self::NARROW, $char)) {
            return 0.32;
        }

        if (str_contains(self::WIDE, $char)) {
            return 0.89;
        }

        if (ctype_upper($char)) {
            return 0.68;
        }

        if (ctype_digit($char)) {
            return 0.6;
        }

        if (ctype_alpha($char)) {
            return 0.55;
        }

        return 0.4;
    }

    /**
     * Largest font size that keeps `$text` on one line inside `$maxWidth`.
     */
    public function fitFontSize(string $text, float $maxWidth, float $preferred, string $category = 'sans', int $weight = 400, float $letterSpacing = 0): float
    {
        if ($text === '') {
            return $preferred;
        }

        $atHundred = $this->width($text, 100, $category, $weight, $letterSpacing * 100 / 100);

        if ($atHundred <= 0) {
            return $preferred;
        }

        return min($preferred, $maxWidth / $atHundred * 100);
    }

    /**
     * Break a brand name into at most two balanced lines.
     *
     * Single words longer than the available width are split at their midpoint
     * so text is never clipped by the artboard.
     *
     * @return array<int, string>
     */
    public function wrap(string $text, float $maxWidth, float $fontSize, string $category = 'sans', int $weight = 400, float $letterSpacing = 0): array
    {
        if ($this->width($text, $fontSize, $category, $weight, $letterSpacing) <= $maxWidth) {
            return [$text];
        }

        $words = preg_split('/\s+/', trim($text)) ?: [];

        if (count($words) > 1) {
            $best = null;
            $bestDiff = PHP_FLOAT_MAX;

            for ($split = 1; $split < count($words); $split++) {
                $lineA = implode(' ', array_slice($words, 0, $split));
                $lineB = implode(' ', array_slice($words, $split));
                $widest = max(
                    $this->width($lineA, $fontSize, $category, $weight, $letterSpacing),
                    $this->width($lineB, $fontSize, $category, $weight, $letterSpacing)
                );

                if ($widest > $maxWidth) {
                    continue;
                }

                $diff = abs(
                    $this->width($lineA, $fontSize, $category, $weight, $letterSpacing)
                    - $this->width($lineB, $fontSize, $category, $weight, $letterSpacing)
                );

                if ($diff < $bestDiff) {
                    $bestDiff = $diff;
                    $best = [$lineA, $lineB];
                }
            }

            if ($best !== null) {
                return $best;
            }
        }

        // Fallback: split a long single token at the visual midpoint.
        $length = mb_strlen($text);
        $half = (int) ceil($length / 2);

        return [mb_substr($text, 0, $half), mb_substr($text, $half)];
    }
}
