<?php

namespace App\Services\Social;

use App\Models\SocialDesignType;

/**
 * Intelligent, non-stretching canvas conversion.
 *
 * Layers are re-flowed into semantic zones (logo, headline, supporting copy,
 * decoration) and clamped to the target safe area instead of being scaled as a
 * flat image.
 */
class SocialTemplateResizeService
{
    /**
     * @param  array<string, mixed>  $design
     * @return array<string, mixed>
     */
    public function resize(array $design, SocialDesignType $target): array
    {
        $sourceCanvas = $design['canvas'] ?? [];
        $sourceWidth = max(1.0, (float) ($sourceCanvas['width'] ?? 1080));
        $sourceHeight = max(1.0, (float) ($sourceCanvas['height'] ?? 1080));
        $width = (float) $target->width;
        $height = (float) $target->height;
        $safe = $target->safeAreaPixels((int) $width, (int) $height);
        $scale = min($width / $sourceWidth, $height / $sourceHeight);
        $minDimension = min($width, $height);

        $objects = [];
        $primaryText = null;
        $primarySize = $minDimension * 0.075;

        foreach (($design['objects'] ?? []) as $object) {
            if (! is_array($object)) {
                continue;
            }

            $role = (string) ($object['role'] ?? '');

            if ($role === 'background') {
                $object['left'] = 0;
                $object['top'] = 0;
                $object['width'] = $width;
                $object['height'] = $height;
                $objects[] = $object;
                continue;
            }

            if ($role === 'primary-text' && ($object['type'] ?? null) === 'i-text') {
                $fontSize = max($minDimension * 0.045, min($minDimension * 0.11, (float) ($object['fontSize'] ?? 48) * $scale));
                $object['left'] = $width / 2;
                $object['top'] = $safe['top'] + $height * 0.34;
                $object['width'] = $width - $safe['left'] - $safe['right'];
                $object['fontSize'] = round($fontSize, 2);
                $object['originX'] = 'center';
                $object['textAlign'] = 'center';
                $primaryText = $object;
                $primarySize = $fontSize;
                $objects[] = $object;
                continue;
            }

            if ($role === 'new-text' && ($object['type'] ?? null) === 'i-text') {
                $object['left'] = $width / 2;
                $object['top'] = ($primaryText['top'] ?? $height * 0.34) + $primarySize * 1.35;
                $object['width'] = $width * 0.74;
                $object['fontSize'] = round(max($minDimension * 0.022, $primarySize * 0.38), 2);
                $object['originX'] = 'center';
                $object['textAlign'] = 'center';
                $objects[] = $this->clampText($object, $safe, $width, $height);
                continue;
            }

            if ($role === 'logo') {
                $size = $minDimension * 0.26;
                $object['left'] = $width / 2;
                $object['top'] = $safe['top'] + $height * 0.14;
                $object['scaleX'] = $size / max(1.0, (float) ($object['pathBox'] ?? 64));
                $object['scaleY'] = $object['scaleX'];

                if (($object['type'] ?? null) === 'image') {
                    $object['width'] = $size;
                    $object['height'] = $size;
                }

                $objects[] = $object;
                continue;
            }

            $object = $this->scaleLayer($object, $sourceWidth, $sourceHeight, $width, $height);
            $objects[] = $object;
        }

        $design['objects'] = $objects;
        $design['canvas'] = [
            'width' => $target->width,
            'height' => $target->height,
            'background' => $sourceCanvas['background'] ?? 'transparent',
        ];
        $design['meta'] = array_merge($design['meta'] ?? [], [
            'design_type' => $target->slug,
            'platform' => $target->platform?->slug,
            'safe_area' => $target->safe_area ?? [],
            'resized_from' => [
                'width' => (int) $sourceWidth,
                'height' => (int) $sourceHeight,
            ],
        ]);

        return $design;
    }

    /**
     * @param  array<string, mixed>  $object
     * @return array<string, mixed>
     */
    private function scaleLayer(array $object, float $sourceWidth, float $sourceHeight, float $width, float $height): array
    {
        $ratioX = $width / $sourceWidth;
        $ratioY = $height / $sourceHeight;
        $ratio = min($ratioX, $ratioY);

        $object['left'] = round((float) ($object['left'] ?? 0) * $ratioX, 2);
        $object['top'] = round((float) ($object['top'] ?? 0) * $ratioY, 2);
        $object['width'] = round((float) ($object['width'] ?? 0) * $ratioX, 2);
        $object['height'] = round((float) ($object['height'] ?? 0) * $ratioY, 2);

        if (isset($object['radius'])) {
            $object['radius'] = round((float) $object['radius'] * $ratio, 2);
        }

        if (isset($object['fontSize'])) {
            $object['fontSize'] = round((float) $object['fontSize'] * $ratio, 2);
        }

        return $object;
    }

    /**
     * @param  array<string, mixed>  $object
     * @param  array{top: float, right: float, bottom: float, left: float}  $safe
     * @return array<string, mixed>
     */
    private function clampText(array $object, array $safe, float $width, float $height): array
    {
        $object['top'] = max($safe['top'], min($height - $safe['bottom'] - (float) ($object['fontSize'] ?? 0) * 1.4, (float) $object['top']));
        $object['left'] = max($safe['left'], min($width - $safe['right'], (float) $object['left']));

        return $object;
    }
}
