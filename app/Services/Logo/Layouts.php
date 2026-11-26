<?php

namespace App\Services\Logo;

/**
 * The 14 layout presets supported by both the server engine and the client
 * editor. The editor mirrors this catalogue so a layout switch is a pure
 * client-side recomputation.
 */
class Layouts
{
    public const ALL = [
        'icon-left' => ['label' => 'Icon left', 'orientation' => 'horizontal'],
        'icon-right' => ['label' => 'Icon right', 'orientation' => 'horizontal'],
        'icon-top' => ['label' => 'Icon above', 'orientation' => 'vertical'],
        'icon-bottom' => ['label' => 'Icon below', 'orientation' => 'vertical'],
        'stacked' => ['label' => 'Stacked', 'orientation' => 'vertical'],
        'horizontal' => ['label' => 'Horizontal', 'orientation' => 'horizontal'],
        'vertical' => ['label' => 'Vertical', 'orientation' => 'vertical'],
        'badge' => ['label' => 'Circular badge', 'orientation' => 'square'],
        'seal' => ['label' => 'Circular seal', 'orientation' => 'square'],
        'ribbon' => ['label' => 'Ribbon banner', 'orientation' => 'square'],
        'medallion' => ['label' => 'Initial badge', 'orientation' => 'vertical'],
        'emblem' => ['label' => 'Emblem', 'orientation' => 'square'],
        'shield' => ['label' => 'Shield', 'orientation' => 'square'],
        'monogram' => ['label' => 'Monogram', 'orientation' => 'square'],
        'wordmark' => ['label' => 'Wordmark', 'orientation' => 'horizontal'],
        'script-3d' => ['label' => '3D Wordmark', 'orientation' => 'horizontal'],
        'icon-only' => ['label' => 'Icon only', 'orientation' => 'square'],
        'text-only' => ['label' => 'Text only', 'orientation' => 'horizontal'],
    ];

    public static function slugs(): array
    {
        return array_keys(self::ALL);
    }

    public static function orientation(string $layout): string
    {
        return self::ALL[$layout]['orientation'] ?? 'horizontal';
    }
}
