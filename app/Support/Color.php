<?php

namespace App\Support;

class Color
{
    /**
     * @return array{0:int,1:int,2:int}|null
     */
    public static function toRgb(?string $hex): ?array
    {
        if (! is_string($hex)) {
            return null;
        }

        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function luminance(string $hex): float
    {
        $rgb = self::toRgb($hex);

        if ($rgb === null) {
            return 1.0;
        }

        $channels = array_map(function (int $value): float {
            $value /= 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * WCAG relative contrast ratio between two colours (1 to 21).
     */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        $light = max($la, $lb);
        $dark = min($la, $lb);

        return ($light + 0.05) / ($dark + 0.05);
    }

    public static function isLight(string $hex): bool
    {
        return self::luminance($hex) > 0.5;
    }

    public static function readableInk(string $background): string
    {
        return self::isLight($background) ? '#111827' : '#FFFFFF';
    }

    /**
     * Mix a colour towards white/black. $amount is between 0 and 1.
     */
    public static function shade(string $hex, float $amount): string
    {
        $rgb = self::toRgb($hex);

        if ($rgb === null) {
            return $hex;
        }

        $target = $amount >= 0 ? 255 : 0;
        $factor = abs($amount);

        $mixed = array_map(
            fn (int $value): int => (int) round($value + ($target - $value) * $factor),
            $rgb
        );

        return self::toHex($mixed[0], $mixed[1], $mixed[2]);
    }

    public static function toHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
