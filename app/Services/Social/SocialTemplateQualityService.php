<?php

namespace App\Services\Social;

use App\Models\SocialDesignType;
use App\Support\Color;

/**
 * Configurable, deterministic design-quality inspection.
 *
 * Hard errors block publishing; warnings are surfaced in the admin panel and
 * the editor so designers can make an informed decision.
 */
class SocialTemplateQualityService
{
    /**
     * @param  array<string, mixed>  $design
     * @return array{valid: bool, score: int, errors: array<int, string>, warnings: array<int, string>, checks: array<int, array<string, string>>}
     */
    public function inspect(array $design, ?SocialDesignType $type = null): array
    {
        $canvas = $design['canvas'] ?? [];
        $width = max(1.0, (float) ($canvas['width'] ?? 1080));
        $height = max(1.0, (float) ($canvas['height'] ?? 1080));
        $safe = $this->safePixels($type, $width, $height);
        $background = $this->backgroundColor($design);

        $errors = [];
        $warnings = [];
        $checks = [];
        $texts = [];
        $fontsUsed = [];
        $decorative = 0;

        foreach (($design['objects'] ?? []) as $index => $object) {
            if (! is_array($object)) {
                continue;
            }

            $role = (string) ($object['role'] ?? '');
            $bounds = $this->bounds($object);
            $label = $role !== '' ? str_replace('-', ' ', $role) : 'layer '.($index + 1);

            if ($bounds['width'] <= 0 || $bounds['height'] <= 0) {
                continue;
            }

            if ($role !== 'background' && $this->outsideCanvas($bounds, $width, $height)) {
                if (($object['type'] ?? null) === 'i-text' || $role === 'logo') {
                    $errors[] = ucfirst($label).' extends outside the canvas.';
                } else {
                    $warnings[] = ucfirst($label).' bleeds outside the canvas as decoration.';
                }
            }

            if (in_array($role, ['primary-text', 'new-text', 'logo'], true) && ! $this->insideSafeArea($bounds, $safe, $width, $height)) {
                $warnings[] = ucfirst($label).' sits inside the platform safe-area margin.';
            }

            if (in_array($role, ['shape', 'shape-ribbon', 'shape-frame', 'decorative'], true)) {
                $decorative++;
            }

            if (($object['type'] ?? null) === 'i-text') {
                $texts[] = ['bounds' => $bounds, 'object' => $object, 'role' => $role];
                if (! empty($object['fontFamily'])) {
                    $fontsUsed[] = (string) $object['fontFamily'];
                }

                $fontSize = (float) ($object['fontSize'] ?? 0);
                $minimum = min($width, $height) * 0.018;

                if ($fontSize > 0 && $fontSize < $minimum) {
                    $warnings[] = ucfirst($label).' is below the legible minimum size for this canvas.';
                }

                $ink = $this->colorOf($object['fill'] ?? null);
                if ($ink !== null && Color::contrast($ink, $background) < 4.5) {
                    $warnings[] = ucfirst($label).' has low contrast against the background.';
                }
            }
        }

        for ($i = 0; $i < count($texts); $i++) {
            for ($j = $i + 1; $j < count($texts); $j++) {
                if ($this->overlapRatio($texts[$i]['bounds'], $texts[$j]['bounds']) > 0.28) {
                    $errors[] = 'Text layers overlap and will be unreadable.';
                }
            }
        }

        if ($decorative > 18) {
            $warnings[] = 'Too many decorative layers compete with the message.';
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));
        $score = max(0, min(100, 100 - count($errors) * 18 - count($warnings) * 5));

        $checks[] = [
            'id' => 'canvas',
            'label' => 'Canvas bounds',
            'status' => $errors === [] ? 'pass' : 'warn',
            'detail' => $errors === [] ? 'Every layer sits inside the canvas.' : $errors[0],
        ];
        $checks[] = [
            'id' => 'safe-area',
            'label' => 'Platform safe area',
            'status' => $warnings === [] ? 'pass' : 'warn',
            'detail' => $warnings === [] ? 'Text and logo respect the safe margins.' : $warnings[0],
        ];
        $checks[] = [
            'id' => 'hierarchy',
            'label' => 'Text hierarchy',
            'status' => $texts === [] ? 'warn' : 'pass',
            'detail' => $texts === [] ? 'No editable text layers found.' : count($texts).' editable text layers.',
        ];
        $uniqueFonts = array_values(array_unique($fontsUsed));
        if (count($uniqueFonts) > 3) {
            $warnings[] = 'More than 3 font families are used ('.count($uniqueFonts).'); limit to 2–3 for visual consistency.';
        }

        $checks[] = [
            'id' => 'font-consistency',
            'label' => 'Font consistency',
            'status' => count($uniqueFonts) > 3 ? 'warn' : 'pass',
            'detail' => count($uniqueFonts) > 0 ? count($uniqueFonts).' font family(ies) in use.' : 'No fonts declared.',
        ];
        $checks[] = [
            'id' => 'contrast',
            'label' => 'WCAG contrast',
            'status' => collect($warnings)->contains(fn ($warning) => str_contains($warning, 'contrast')) ? 'warn' : 'pass',
            'detail' => 'Text colours are checked against the dominant background.',
        ];

        return [
            'valid' => $errors === [],
            'score' => $score,
            'errors' => $errors,
            'warnings' => $warnings,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    private function safePixels(?SocialDesignType $type, float $width, float $height): array
    {
        if ($type !== null) {
            return $type->safeAreaPixels((int) $width, (int) $height);
        }

        return [
            'top' => round($height * 0.05, 2),
            'right' => round($width * 0.05, 2),
            'bottom' => round($height * 0.05, 2),
            'left' => round($width * 0.05, 2),
        ];
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    private function bounds(array $object): array
    {
        $type = (string) ($object['type'] ?? '');
        $x = (float) ($object['left'] ?? 0);
        $y = (float) ($object['top'] ?? 0);
        $scaleX = abs((float) ($object['scaleX'] ?? 1));
        $scaleY = abs((float) ($object['scaleY'] ?? 1));

        if ($type === 'i-text') {
            $fontSize = (float) ($object['fontSize'] ?? 24);
            $lineHeight = (float) ($object['lineHeight'] ?? 1.12);
            $text = (string) ($object['text'] ?? '');
            $lines = explode("\n", $text);
            $lineCount = max(1, count($lines));
            $longestLine = max(array_map(fn (string $line): int => mb_strlen($line), $lines));
            $width = (float) ($object['width'] ?? 0);
            if ($width <= 0) {
                $width = max($fontSize, $longestLine * $fontSize * 0.55);
            }
            $height = $fontSize * $lineHeight * $lineCount;
            $origin = (string) ($object['originX'] ?? 'left');
            if ($origin === 'center') {
                $x -= $width / 2;
            } elseif ($origin === 'right') {
                $x -= $width;
            }

            return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
        }

        if ($type === 'group') {
            $box = (float) ($object['pathBox'] ?? 64) * $scaleX;
            $boxY = (float) ($object['pathBox'] ?? 64) * $scaleY;

            return ['x' => $x - $box / 2, 'y' => $y - $boxY / 2, 'width' => $box, 'height' => $boxY];
        }

        if ($type === 'circle') {
            $radius = (float) ($object['radius'] ?? 0) * max($scaleX, $scaleY);

            return ['x' => $x - $radius, 'y' => $y - $radius, 'width' => $radius * 2, 'height' => $radius * 2];
        }

        if ($type === 'image') {
            $width = (float) ($object['width'] ?? 0) * $scaleX;
            $height = (float) ($object['height'] ?? $width) * $scaleY;
            $origin = (string) ($object['originX'] ?? 'left');
            if ($origin === 'center') {
                $x -= $width / 2;
            }

            return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
        }

        return [
            'x' => $x,
            'y' => $y,
            'width' => (float) ($object['width'] ?? 0) * $scaleX,
            'height' => (float) ($object['height'] ?? 0) * $scaleY,
        ];
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $bounds
     */
    private function outsideCanvas(array $bounds, float $width, float $height): bool
    {
        return $bounds['x'] < -1
            || $bounds['y'] < -1
            || $bounds['x'] + $bounds['width'] > $width + 1
            || $bounds['y'] + $bounds['height'] > $height + 1;
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $bounds
     * @param  array{top: float, right: float, bottom: float, left: float}  $safe
     */
    private function insideSafeArea(array $bounds, array $safe, float $width, float $height): bool
    {
        return $bounds['x'] >= $safe['left'] - 1
            && $bounds['y'] >= $safe['top'] - 1
            && $bounds['x'] + $bounds['width'] <= $width - $safe['right'] + 1
            && $bounds['y'] + $bounds['height'] <= $height - $safe['bottom'] + 1;
    }

    /**
     * @param  array{x: float, y: float, width: float, height: float}  $a
     * @param  array{x: float, y: float, width: float, height: float}  $b
     */
    private function overlapRatio(array $a, array $b): float
    {
        $x = max(0.0, min($a['x'] + $a['width'], $b['x'] + $b['width']) - max($a['x'], $b['x']));
        $y = max(0.0, min($a['y'] + $a['height'], $b['y'] + $b['height']) - max($a['y'], $b['y']));
        $intersection = $x * $y;
        $smallest = min($a['width'] * $a['height'], $b['width'] * $b['height']);

        return $smallest > 0 ? $intersection / $smallest : 0.0;
    }

    private function backgroundColor(array $design): string
    {
        foreach (($design['objects'] ?? []) as $object) {
            if (($object['role'] ?? null) !== 'background') {
                continue;
            }

            $color = $this->colorOf($object['fill'] ?? null);
            if ($color !== null) {
                return $color;
            }
        }

        return '#FFFFFF';
    }

    private function colorOf(mixed $fill): ?string
    {
        if (is_string($fill) && preg_match('/^#[0-9a-fA-F]{6}$/', $fill)) {
            return $fill;
        }

        if (is_array($fill)) {
            $color = $fill['color'] ?? ($fill['stops'][0]['color'] ?? null);
            if (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                return $color;
            }
        }

        return null;
    }
}
