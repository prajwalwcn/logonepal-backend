<?php

namespace App\Services\Social;

/**
 * Stable structural signature for a template design.
 *
 * Two designs with the same canvas, layer roles, positions, type scale and
 * colours are treated as duplicates even when their copy differs.
 */
class SocialTemplateFingerprint
{
    public static function for(array $design): string
    {
        $objects = [];

        foreach ($design['objects'] ?? [] as $object) {
            if (! is_array($object)) {
                continue;
            }

            $fill = $object['fill'] ?? null;
            $color = null;

            if (is_string($fill)) {
                $color = $fill;
            } elseif (is_array($fill)) {
                $color = $fill['color'] ?? ($fill['stops'][0]['color'] ?? null);
            }

            $objects[] = [
                'type' => $object['type'] ?? null,
                'role' => $object['role'] ?? null,
                'x' => round((float) ($object['left'] ?? 0)),
                'y' => round((float) ($object['top'] ?? 0)),
                'w' => round((float) ($object['width'] ?? 0)),
                'h' => round((float) ($object['height'] ?? 0)),
                'size' => isset($object['fontSize']) ? round((float) $object['fontSize']) : null,
                'font' => $object['fontFamily'] ?? null,
                'color' => is_string($color) ? strtoupper($color) : null,
                'text_length' => isset($object['text']) ? mb_strlen((string) $object['text']) : null,
            ];
        }

        return hash('sha256', json_encode([
            'canvas' => [
                'width' => round((float) ($design['canvas']['width'] ?? 0)),
                'height' => round((float) ($design['canvas']['height'] ?? 0)),
            ],
            'objects' => $objects,
        ], JSON_UNESCAPED_SLASHES) ?: '');
    }
}
