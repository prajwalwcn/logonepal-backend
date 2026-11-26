<?php

namespace App\Services\Design\Concerns;

use App\Support\Color;

/**
 * Apparel, product, icon, illustration and production layouts.
 */
trait ComposesCreative
{
    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutApparel(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.1;
        $cx = $w / 2;
        $objects = [];
        $ink = Color::isLight($c['colors'][0]) ? '#0F172A' : $c['colors'][0];

        $markSize = $unit * 0.22;
        $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.34, $markSize, $c['colors'][1]));

        $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.1, 3, $c, 800, 10, true);
        $objects[] = $this->text('apparel-headline', 'primary-text', $headline['text'], $cx, $h * 0.52, $headline['size'], $ink, $c, 800, 10, 'center');
        $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.032, 2, $c, 500, 160, true);
        $objects[] = $this->text('apparel-sub', 'new-text', $sub['text'], $cx, $h * 0.52 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.03, $sub['size'], $c['colors'][1], $c, 500, 160, 'center');
        $objects[] = $this->text('apparel-brand', 'new-text', $c['brand'], $cx, $h * 0.86, $unit * 0.022, $ink, $c, 700, 220, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutMockup(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.08;
        $objects = [];
        $variant = $c['variant'];
        $garment = $c['dark'] ? '#1F2937' : '#E8ECF2';
        $ink = $c['dark'] ? '#FFFFFF' : '#0F172A';

        if ($variant === '3d') {
            $objects[] = $this->rect('wall', 0, 0, $w, $h * 0.78, ['type' => 'linear', 'angle' => 160, 'stops' => [
                ['offset' => 0, 'color' => Color::shade($c['colors'][1], 0.55)],
                ['offset' => 1, 'color' => Color::shade($c['colors'][1], -0.35)],
            ]]);
            $objects[] = $this->rect('floor', 0, $h * 0.78, $w, $h * 0.22, Color::shade($c['colors'][0], 0.1));
            $objects[] = $this->rule('wall-line', 0, $h * 0.78, $w, $c['colors'][2], $unit * 0.003, 0.6);
            $markSize = $unit * 0.2;
            $objects = array_merge($objects, $this->mark($c, $w * 0.5, $h * 0.4, $markSize));
            $objects[] = $this->text('mockup-3d-name', 'primary-text', $c['brand'], $w / 2, $h * 0.4 + $markSize * 0.7, $unit * 0.06, '#FFFFFF', $c, 800, 120, 'center', 1, [
                'shadow' => ['color' => 'rgba(0,0,0,0.45)', 'blur' => 18, 'offsetX' => 8, 'offsetY' => 10],
            ]);
            $objects[] = $this->text('mockup-3d-note', 'new-text', '3D mockup · presentation only', $w - $m, $h - $m * 0.6, $unit * 0.018, $c['colors'][2], $c, 500, 120, 'center');

            return $objects;
        }

        if ($variant === 'bottle') {
            $style = (string) ($c['style'] ?? 'editorial');
            $cx = $w / 2;
            $stageBg = $c['dark'] ? '#0B1120' : ($style === 'retro' ? '#EAE6D7' : ($style === 'editorial' ? '#FFFBF0' : '#F3F5F9'));
            $objects[] = $this->rect('mockup-stage', 0, 0, $w, $h, $stageBg);
            $objects[] = $this->rect('mockup-podium', 0, $h * 0.78, $w, $h * 0.22, $c['dark'] ? '#111827' : '#E5E7EB', 0, 0.7);

            if ($style === 'split') {
                // Luxury Packaging Box
                $boxX = $w * 0.24;
                $boxY = $h * 0.2;
                $boxW = $w * 0.52;
                $boxH = $h * 0.62;
                $boxColor = $c['colors'][0];
                $boxInk = Color::readableInk($boxColor);
                $objects[] = $this->rect('box-shadow', $boxX + $unit * 0.02, $boxY + $unit * 0.025, $boxW, $boxH, '#000000', $unit * 0.018, 0.14);
                $objects[] = $this->rect('box-body', $boxX, $boxY, $boxW, $boxH, $boxColor, $unit * 0.018);
                $objects[] = $this->rect('box-lid', $boxX - $unit * 0.012, $boxY, $boxW + $unit * 0.024, $boxH * 0.17, Color::shade($boxColor, 0.12), $unit * 0.014);
                $objects[] = $this->rule('box-seam', $boxX, $boxY + $boxH * 0.17, $boxW, $c['accent'], $unit * 0.005);
                $objects = array_merge($objects, $this->mark($c, $cx, $boxY + $boxH * 0.44, $unit * 0.18));
                $objects[] = $this->text('box-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $boxY + $boxH * 0.63, $unit * 0.044, $boxInk, $c, 800, 140, 'center');
                $objects[] = $this->text('box-sub', 'new-text', $c['headline'], $cx, $boxY + $boxH * 0.72, $unit * 0.024, $c['accent'], $c, 600, 120, 'center');

                return $objects;
            }

            if ($style === 'spotlight') {
                // Insulated Coffee Cup / Tumbler
                $objects[] = $this->circle('cup-glow', $cx, $h * 0.46, $unit * 0.34, $c['accent'], null, 0, 0.16);
                $cup = 'M 330 220 L 670 220 L 620 880 Q 615 920 575 920 L 425 920 Q 385 920 380 880 Z';
                $objects[] = $this->pathGroup('cup-body', $cup, $cx, $h * 0.54, $unit * 0.72, '#FFFFFF');
                $objects[] = $this->rect('cup-lid', $w * 0.31, $h * 0.18, $w * 0.38, $h * 0.065, '#1F2937', $unit * 0.02);
                $objects[] = $this->rect('cup-sleeve', $w * 0.34, $h * 0.42, $w * 0.32, $h * 0.28, $c['colors'][0], $unit * 0.012);
                $sleeveInk = Color::readableInk($c['colors'][0]);
                $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.52, $unit * 0.13));
                $objects[] = $this->text('cup-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $h * 0.61, $unit * 0.032, $sleeveInk, $c, 800, 120, 'center');

                return $objects;
            }

            if ($style === 'kinetic') {
                // Canvas Tote Bag
                $bagX = $w * 0.25;
                $bagY = $h * 0.32;
                $bagW = $w * 0.5;
                $bagH = $h * 0.52;
                $objects[] = $this->circle('tote-handle', $cx, $bagY, $unit * 0.11, 'none', '#D6CFC2', $unit * 0.014);
                $objects[] = $this->rect('tote-body', $bagX, $bagY, $bagW, $bagH, '#F5EFE6', $unit * 0.02);
                $objects[] = $this->rule('tote-hem', $bagX, $bagY + $unit * 0.04, $bagW, '#DDD5C7', $unit * 0.004);
                $objects = array_merge($objects, $this->mark($c, $cx, $bagY + $bagH * 0.4, $unit * 0.18));
                $objects[] = $this->text('tote-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $bagY + $bagH * 0.65, $unit * 0.042, '#111827', $c, 800, 140, 'center');
                $objects[] = $this->text('tote-sub', 'new-text', $c['headline'], $cx, $bagY + $bagH * 0.76, $unit * 0.022, $c['accent'], $c, 600, 120, 'center');

                return $objects;
            }

            if ($style === 'gradient') {
                // Smartphone App Mockup
                $phoneX = $w * 0.3;
                $phoneY = $h * 0.12;
                $phoneW = $w * 0.4;
                $phoneH = $h * 0.76;
                $objects[] = $this->rect('phone-bezel', $phoneX, $phoneY, $phoneW, $phoneH, '#0F172A', $unit * 0.055);
                $objects[] = $this->rect('phone-screen', $phoneX + $unit * 0.018, $phoneY + $unit * 0.018, $phoneW - $unit * 0.036, $phoneH - $unit * 0.036, [
                    'type' => 'linear',
                    'angle' => 145,
                    'stops' => [
                        ['offset' => 0, 'color' => $c['colors'][0]],
                        ['offset' => 1, 'color' => $c['colors'][1]],
                    ],
                ], $unit * 0.042);
                $objects[] = $this->rect('phone-notch', $cx - $unit * 0.06, $phoneY + $unit * 0.03, $unit * 0.12, $unit * 0.022, '#0F172A', $unit * 0.011);
                $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.42, $unit * 0.18));
                $objects[] = $this->text('phone-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $h * 0.56, $unit * 0.038, '#FFFFFF', $c, 800, 120, 'center');
                $objects[] = $this->text('phone-sub', 'new-text', $c['headline'], $cx, $h * 0.63, $unit * 0.022, '#E2E8F0', $c, 500, 80, 'center');

                return $objects;
            }

            if ($style === 'editorial') {
                // Boutique Gift / Shopping Bag
                $bagX = $w * 0.24;
                $bagY = $h * 0.28;
                $bagW = $w * 0.52;
                $bagH = $h * 0.56;
                $objects[] = $this->circle('gift-handle', $cx, $bagY, $unit * 0.09, 'none', $c['accent'], $unit * 0.012);
                $objects[] = $this->rect('gift-bag', $bagX, $bagY, $bagW, $bagH, $c['colors'][0], $unit * 0.014);
                $bagInk = Color::readableInk($c['colors'][0]);
                $objects = array_merge($objects, $this->mark($c, $cx, $bagY + $bagH * 0.42, $unit * 0.18));
                $objects[] = $this->text('gift-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $bagY + $bagH * 0.66, $unit * 0.042, $bagInk, $c, 800, 140, 'center');
                $objects[] = $this->text('gift-sub', 'new-text', $c['headline'], $cx, $bagY + $bagH * 0.76, $unit * 0.022, $c['accent'], $c, 600, 100, 'center');

                return $objects;
            }

            if ($style === 'minimal') {
                // Matte Stationery / Business Card Stack
                $cardX = $w * 0.16;
                $cardY = $h * 0.3;
                $cardW = $w * 0.68;
                $cardH = $h * 0.4;
                $objects[] = $this->rect('card-back', $cardX + $unit * 0.03, $cardY + $unit * 0.03, $cardW, $cardH, $c['colors'][0], $unit * 0.02);
                $objects[] = $this->rect('card-front', $cardX, $cardY, $cardW, $cardH, '#FFFFFF', $unit * 0.02);
                $objects = array_merge($objects, $this->mark($c, $cx, $cardY + $cardH * 0.38, $unit * 0.15));
                $objects[] = $this->text('card-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $cardY + $cardH * 0.62, $unit * 0.04, '#0F172A', $c, 800, 150, 'center');
                $objects[] = $this->text('card-sub', 'new-text', $c['headline'], $cx, $cardY + $cardH * 0.76, $unit * 0.022, '#64748B', $c, 500, 100, 'center');

                return $objects;
            }

            // Retro & Bold: Artisan Bottle Mockup
            $bottle = 'M 430 80 L 570 80 L 570 220 Q 640 290 640 400 L 640 900 Q 640 940 600 940 L 400 940 Q 360 940 360 900 L 360 400 Q 360 290 430 220 Z';
            $objects[] = $this->pathGroup('bottle-shape', $bottle, $cx, $h * 0.5, $unit * 0.86, $c['colors'][0], 0, null, null);
            $objects[] = $this->rect('bottle-label', $w * 0.33, $h * 0.44, $w * 0.34, $h * 0.28, '#FFFBF0', $unit * 0.012);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.52, $unit * 0.12));
            $objects[] = $this->text('bottle-brand', 'primary-text', mb_strtoupper($c['brand']), $cx, $h * 0.6, $unit * 0.032, '#0F172A', $c, 800, 120, 'center');
            $objects[] = $this->text('bottle-name', 'new-text', $c['headline'], $cx, $h * 0.65, $unit * 0.021, '#64748B', $c, 500, 100, 'center');

            return $objects;
        }

        // Tee / apparel mockup.
        $objects[] = $this->rect('mockup-stage', 0, 0, $w, $h, $c['dark'] ? '#0B1120' : '#F4F6FA');
        $tee = 'M 330 120 L 440 80 Q 500 140 560 80 L 670 120 L 830 260 L 730 340 L 690 300 L 690 900 Q 690 930 660 930 L 340 930 Q 310 930 310 900 L 310 300 L 270 340 L 170 260 Z';
        $objects[] = $this->pathGroup('tee-shape', $tee, $w / 2, $h * 0.5, $unit * 0.86, $garment);
        $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.4, $unit * 0.13));
        $headline = $this->block($c['headline'], $w * 0.4, $unit * 0.05, 2, $c, 800, 40, true);
        $objects[] = $this->text('mockup-headline', 'primary-text', $headline['text'], $w / 2, $h * 0.5, $headline['size'], $ink, $c, 800, 40, 'center');
        $objects[] = $this->text('mockup-sub', 'new-text', $c['subhead'], $w / 2, $h * 0.5 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.02, $unit * 0.022, $c['colors'][1], $c, 500, 120, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutIcon(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $objects = [];
        $variant = $c['variant'];
        $style = (string) ($c['style'] ?? 'editorial');

        $gradient = ['type' => 'linear', 'angle' => 135, 'stops' => [
            ['offset' => 0, 'color' => $c['colors'][1]],
            ['offset' => 1, 'color' => $c['colors'][2] ?? $c['colors'][1]],
        ]];

        if ($variant === 'favicon') {
            $objects[] = $this->rect('icon-bg', 0, 0, $w, $h, $gradient, $unit * 0.22);
            $initials = $c['initials'];
            $size = $this->fitOne($initials, $unit * 0.5, $unit * 0.42, $c, 800, 20);
            $objects[] = $this->text('icon-initials', 'primary-text', $initials, $w / 2, $h / 2 - $size * 0.58, $size, '#FFFFFF', $c, 800, 20, 'center');

            return $objects;
        }

        // Authentic BrandCrowd Social Media Profile Picture / Avatar Archetypes (800x800)
        if ($variant === 'avatar' || $variant === 'channel' || str_starts_with($style, 'profile-')) {
            $cx = $w / 2;
            $cy = $h / 2;
            $r = $unit * 0.46;

            if ($style === 'profile-dark-circle') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#0F172A', '#334155', 2);
                $objects[] = $this->circle('prof-glow', $cx, $cy, $r * 0.75, '#1E293B', null, 0, 0.4);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.68));

                return $objects;
            }

            if ($style === 'profile-gold-ring') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#FFFFFF');
                $objects[] = $this->circle('prof-ring1', $cx, $cy, $r - 4, 'none', '#D97706', 2.5);
                $objects[] = $this->circle('prof-ring2', $cx, $cy, $r - 12, 'none', '#F59E0B', 1.5);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.64));

                return $objects;
            }

            if ($style === 'profile-brand-solid') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, $c['colors'][0] ?? '#1E293B');
                $objects[] = $this->circle('prof-inner', $cx, $cy, $r * 0.88, '#FFFFFF', null, 0, 0.95);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.64));

                return $objects;
            }

            if ($style === 'profile-gradient-radial') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, $gradient);
                $objects[] = $this->circle('prof-inner', $cx, $cy, $r * 0.86, '#FFFFFF', '#F1F5F9', 2);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.64));

                return $objects;
            }

            if ($style === 'profile-minimal-framed') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#F8FAFC', '#E2E8F0', 2);
                $objects[] = $this->rect('prof-frame', $cx - $r * 0.65, $cy - $r * 0.65, $r * 1.3, $r * 1.3, 'none', 0, 1.0, 45, '#CBD5E1', 1.5);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.66));

                return $objects;
            }

            if ($style === 'profile-scalloped-seal') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#1E293B');
                for ($a = 0; $a < 360; $a += 15) {
                    $rad = deg2rad($a);
                    $sx = $cx + cos($rad) * ($r - 2);
                    $sy = $cy + sin($rad) * ($r - 2);
                    $objects[] = $this->circle('prof-pip-'.$a, $sx, $sy, 5, '#F59E0B');
                }
                $objects[] = $this->circle('prof-inner', $cx, $cy, $r * 0.82, '#FFFFFF');
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.60));

                return $objects;
            }

            if ($style === 'profile-luxury-glow') {
                $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#000000');
                $objects[] = $this->circle('prof-glow', $cx, $cy, $r * 0.65, '#FFFFFF', null, 0, 0.15);
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.68));

                return $objects;
            }

            // Default: profile-clean-white
            $objects[] = $this->circle('prof-bg', $cx, $cy, $r, '#FFFFFF', '#E2E8F0', 2);
            $objects = array_merge($objects, $this->mark($c, $cx, $cy, $unit * 0.70));

            return $objects;
        }

        $objects[] = $this->rect('icon-bg', 0, 0, $w, $h, $gradient, $unit * 0.22);
        $objects[] = $this->circle('icon-glow', $w * 0.78, $h * 0.2, $unit * 0.3, '#FFFFFF', null, 0, 0.08);
        $markSize = $unit * 0.34;
        $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.44, $markSize, '#FFFFFF'));
        $name = $this->block($c['brand'], $w - $unit * 0.16, $unit * 0.045, 1, $c, 700, 160, true);
        $objects[] = $this->text('icon-name', 'new-text', $name['text'], $w / 2, $h * 0.72, $name['size'], '#FFFFFF', $c, 700, 160, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutPattern(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'art') {
            $objects[] = $this->circle('art-blob-1', $w * 0.35, $h * 0.32, $unit * 0.28, $c['colors'][1], null, 0, 0.85);
            $objects[] = $this->circle('art-blob-2', $w * 0.66, $h * 0.5, $unit * 0.2, $c['colors'][2], null, 0, 0.7);
            $objects[] = $this->circle('art-blob-3', $w * 0.45, $h * 0.68, $unit * 0.14, $c['ink'], null, 0, 0.25);
            $objects[] = $this->pathGroup('art-arc', 'M 120 820 Q 500 480 880 820', $w / 2, $h * 0.62, $unit * 0.9, 'none', 0, $unit * 0.012, $c['ink']);
            $objects[] = $this->circle('art-ring', $w * 0.7, $h * 0.25, $unit * 0.09, 'none', $c['ink'], $unit * 0.004, 0.5);
            if ($c['headline'] !== '') {
                $title = $this->block($c['headline'], $w - 2 * $unit * 0.1, $unit * 0.05, 1, $c, 600, 200, true);
                $objects[] = $this->text('art-caption', 'new-text', $title['text'], $w / 2, $h - $unit * 0.12, $title['size'], $c['ink'], $c, 600, 200, 'center');
            }

            return $objects;
        }

        $cols = 5;
        $rows = 5;
        $stepX = $w / $cols;
        $stepY = $h / $rows;
        $shapes = ['circle', 'ring', 'plus', 'arc', 'dot'];
        $index = 0;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $cx = $stepX * ($col + 0.5);
                $cy = $stepY * ($row + 0.5);
                $shape = $shapes[$index % count($shapes)];
                $color = $c['colors'][$index % 3];
                $size = min($stepX, $stepY) * 0.22;

                if ($shape === 'circle') {
                    $objects[] = $this->circle('pat-'.$index, $cx, $cy, $size, $color, null, 0, 0.9);
                } elseif ($shape === 'ring') {
                    $objects[] = $this->circle('pat-'.$index, $cx, $cy, $size * 1.1, 'none', $color, $unit * 0.005);
                } elseif ($shape === 'plus') {
                    $objects[] = $this->rect('pat-'.$index.'-h', $cx - $size, $cy - $size * 0.18, $size * 2, $size * 0.36, $color);
                    $objects[] = $this->rect('pat-'.$index.'-v', $cx - $size * 0.18, $cy - $size, $size * 0.36, $size * 2, $color);
                } elseif ($shape === 'arc') {
                    $objects[] = $this->circle('pat-'.$index, $cx, $cy, $size, 'none', $color, $unit * 0.004);
                    $objects[] = $this->circle('pat-'.$index.'-cover', $cx + $size * 0.55, $cy - $size * 0.55, $size, $c['bg']);
                } else {
                    $objects[] = $this->circle('pat-'.$index, $cx, $cy, $size * 0.45, $color);
                }

                $index++;
            }
        }

        if ($c['headline'] !== '') {
            $title = $this->block($c['headline'], $w * 0.7, $unit * 0.035, 1, $c, 600, 200, true);
            $objects[] = $this->text('pattern-caption', 'new-text', $title['text'], $w / 2, $h - $unit * 0.07, $title['size'], $c['ink'], $c, 600, 200, 'center');
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutIllustration(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $objects = [];
        $variant = $c['variant'];
        $face = $c['colors'][2];
        $body = $c['colors'][1];

        if ($variant === 'minimal') {
            $objects[] = $this->circle('min-sun', $w * 0.66, $h * 0.26, $unit * 0.11, $c['colors'][2]);
            $objects[] = $this->pathGroup('min-hill-1', 'M 0 760 L 300 420 L 620 760 Z', $w / 2, $h * 0.62, $unit, $c['colors'][1], 0, null, null);
            $objects[] = $this->pathGroup('min-hill-2', 'M 320 760 L 640 500 L 1000 760 Z', $w / 2, $h * 0.62, $unit, $c['colors'][0], 0, null, null);
            $objects[] = $this->rule('min-horizon', 0, $h * 0.62, $w, $c['ink'], $unit * 0.003, 0.5);
            $title = $this->block($c['headline'], $w * 0.8, $unit * 0.045, 1, $c, 600, 240, true);
            $objects[] = $this->text('min-title', 'new-text', $title['text'], $cx, $h * 0.86, $title['size'], $c['ink'], $c, 600, 240, 'center');

            return $objects;
        }

        if ($variant === 'ui') {
            $cardX = $w * 0.16;
            $cardY = $h * 0.16;
            $cardW = $w * 0.68;
            $cardH = $h * 0.56;
            $objects[] = $this->rect('ui-card', $cardX, $cardY, $cardW, $cardH, '#FFFFFF', $unit * 0.02);
            $objects[] = $this->rect('ui-bar', $cardX, $cardY, $cardW, $unit * 0.04, $c['support'], $unit * 0.01, 0.4);
            foreach (['#F87171', '#FBBF24', '#34D399'] as $index => $dot) {
                $objects[] = $this->circle('ui-dot-'.$index, $cardX + $unit * 0.035 + $index * $unit * 0.025, $cardY + $unit * 0.02, $unit * 0.006, $dot);
            }
            $objects = array_merge($objects, $this->mark($c, $cardX + $unit * 0.1, $cardY + $unit * 0.1, $unit * 0.1));
            $objects[] = $this->rect('ui-block-1', $cardX + $unit * 0.05, $cardY + $unit * 0.2, $cardW * 0.55, $unit * 0.02, $c['ink'], $unit * 0.01, 0.85);
            $objects[] = $this->rect('ui-block-2', $cardX + $unit * 0.05, $cardY + $unit * 0.24, $cardW * 0.4, $unit * 0.014, $c['muted'], $unit * 0.007, 0.5);
            for ($i = 0; $i < 3; $i++) {
                $objects[] = $this->rect('ui-tile-'.$i, $cardX + $unit * 0.05 + $i * ($cardW * 0.3), $cardY + $unit * 0.33, $cardW * 0.26, $unit * 0.12, $c['colors'][$i % 3], $unit * 0.012, 0.9);
            }
            $title = $this->block($c['headline'], $w * 0.8, $unit * 0.04, 1, $c, 600, 200, true);
            $objects[] = $this->text('ui-title', 'new-text', $title['text'], $cx, $h * 0.8, $title['size'], $c['ink'], $c, 600, 200, 'center');

            return $objects;
        }

        if ($variant === 'dashboard') {
            $objects[] = $this->rect('dash-card', $w * 0.1, $h * 0.12, $w * 0.8, $h * 0.6, '#FFFFFF', $unit * 0.02);
            $objects[] = $this->text('dash-title', 'new-text', $c['headline'], $w * 0.14, $h * 0.16, $unit * 0.035, $c['ink'], $c, 700, 20);
            for ($i = 0; $i < 5; $i++) {
                $barH = $unit * (0.06 + ($i % 3) * 0.05);
                $objects[] = $this->rect('dash-bar-'.$i, $w * 0.15 + $i * $w * 0.06, $h * 0.55 - $barH, $w * 0.035, $barH, $c['colors'][$i % 3], $unit * 0.006, 0.9);
            }
            $objects[] = $this->circle('dash-donut', $w * 0.72, $h * 0.36, $unit * 0.1, 'none', $c['colors'][1], $unit * 0.025);
            $objects[] = $this->circle('dash-donut-inner', $w * 0.72, $h * 0.36, $unit * 0.06, '#FFFFFF');
            $objects[] = $this->text('dash-value', 'new-text', '72%', $w * 0.72, $h * 0.36 - $unit * 0.018, $unit * 0.03, $c['ink'], $c, 700, 20, 'center');
            $objects[] = $this->text('dash-brand', 'new-text', $c['brand'], $w * 0.14, $h * 0.64, $unit * 0.022, $c['muted'], $c, 500, 120);

            return $objects;
        }

        // Mascot, character, avatar.
        $headR = $unit * ($variant === 'avatar' ? 0.24 : 0.2);
        $headY = $h * ($variant === 'avatar' ? 0.42 : 0.38);

        if ($variant === 'avatar') {
            $objects[] = $this->circle('avatar-frame', $cx, $h * 0.5, $unit * 0.42, $c['support'], null, 0, 0.45);
            $objects[] = $this->pathGroup('avatar-shoulders', 'M 220 900 Q 500 560 780 900 Z', $cx, $h * 0.5, $unit * 0.82, $body);
        }

        $objects[] = $this->circle('illus-body', $cx, $h * 0.7, $unit * 0.26, $body);
        $objects[] = $this->circle('illus-head', $cx, $headY, $headR, $face);
        $objects[] = $this->circle('illus-eye-l', $cx - $headR * 0.34, $headY - $headR * 0.08, $headR * 0.07, $c['ink']);
        $objects[] = $this->circle('illus-eye-r', $cx + $headR * 0.34, $headY - $headR * 0.08, $headR * 0.07, $c['ink']);
        $objects[] = $this->pathGroup('illus-smile', 'M 420 520 Q 500 600 580 520', $cx, $headY, $headR * 2, 'none', 0, $unit * 0.008, $c['ink']);
        $objects[] = $this->circle('illus-cheek-l', $cx - $headR * 0.5, $headY + $headR * 0.18, $headR * 0.08, $c['colors'][1], null, 0, 0.35);
        $objects[] = $this->circle('illus-cheek-r', $cx + $headR * 0.5, $headY + $headR * 0.18, $headR * 0.08, $c['colors'][1], null, 0, 0.35);

        if ($variant === 'mascot') {
            $objects[] = $this->circle('mascot-arm-l', $cx - $unit * 0.27, $h * 0.68, $unit * 0.055, $body);
            $objects[] = $this->circle('mascot-arm-r', $cx + $unit * 0.27, $h * 0.68, $unit * 0.055, $body);
            $objects[] = $this->circle('mascot-foot-l', $cx - $unit * 0.1, $h * 0.92, $unit * 0.05, $c['ink'], null, 0, 0.85);
            $objects[] = $this->circle('mascot-foot-r', $cx + $unit * 0.1, $h * 0.92, $unit * 0.05, $c['ink'], null, 0, 0.85);
            foreach ([[0.2, 0.18], [0.82, 0.3], [0.76, 0.12]] as $index => $point) {
                $objects[] = $this->text('mascot-spark-'.$index, 'shape', '✦', $w * $point[0], $h * $point[1], $unit * 0.045, $c['colors'][$index % 3], $c, 700, 0, 'center');
            }
        }

        if ($variant === 'character') {
            $objects[] = $this->pathGroup('char-hair', 'M 260 420 Q 320 160 500 200 Q 700 220 720 460 Q 640 330 500 340 Q 360 350 260 420 Z', $cx, $headY - $headR * 0.2, $headR * 2.2, $c['ink']);
            $objects[] = $this->rect('char-shirt', $cx - $unit * 0.16, $h * 0.6, $unit * 0.32, $h * 0.22, $c['colors'][0], $unit * 0.03, 0.9);
        }

        $title = $this->block($c['headline'], $w * 0.8, $unit * 0.05, 2, $c, 700, 30, true);
        $objects[] = $this->text('illus-title', 'primary-text', $title['text'], $cx, $h * 0.84, $title['size'], $c['ink'], $c, 700, 30, 'center');
        if ($c['subhead'] !== '') {
            $objects[] = $this->text('illus-sub', 'new-text', $c['subhead'], $cx, $h * 0.84 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.012, $unit * 0.022, $c['muted'], $c, 500, 80, 'center');
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutLineart(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $objects = [];
        $variant = $c['variant'];
        $ink = Color::isLight($c['colors'][0]) ? '#111827' : $c['colors'][0];
        $stroke = max(2.0, $unit * 0.006);

        if ($variant === 'coloring') {
            $objects[] = $this->circle('color-flower', $cx, $h * 0.36, $unit * 0.13, 'none', $ink, $stroke);
            for ($i = 0; $i < 6; $i++) {
                $angle = $i * M_PI / 3;
                $objects[] = $this->circle('color-petal-'.$i, $cx + cos($angle) * $unit * 0.2, $h * 0.36 + sin($angle) * $unit * 0.2, $unit * 0.1, 'none', $ink, $stroke);
            }
            $objects[] = $this->pathGroup('color-stem', 'M 500 480 Q 460 660 520 840', $cx, $h * 0.5, $unit * 0.9, 'none', 0, $stroke, $ink);
            $objects[] = $this->pathGroup('color-leaf-1', 'M 500 640 Q 320 560 360 700 Q 440 720 500 640 Z', $cx, $h * 0.5, $unit * 0.9, 'none', 0, $stroke, $ink);
            $objects[] = $this->pathGroup('color-leaf-2', 'M 510 720 Q 690 650 650 790 Q 570 800 510 720 Z', $cx, $h * 0.5, $unit * 0.9, 'none', 0, $stroke, $ink);
            $title = $this->block($c['headline'], $w * 0.8, $unit * 0.05, 1, $c, 600, 240, true);
            $objects[] = $this->text('color-title', 'new-text', $title['text'], $cx, $h * 0.9, $title['size'], $ink, $c, 600, 240, 'center');

            return $objects;
        }

        if ($variant === 'craft') {
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.34, $unit * 0.24, $ink));
            $title = $this->block($c['headline'], $w * 0.8, $unit * 0.075, 2, $c, 800, 40, true);
            $objects[] = $this->text('craft-title', 'primary-text', $title['text'], $cx, $h * 0.56, $title['size'], $ink, $c, 800, 40, 'center');
            $objects[] = $this->text('craft-note', 'new-text', 'Single-colour production file', $cx, $h * 0.82, $unit * 0.022, $ink, $c, 500, 120, 'center');
            $objects[] = $this->rule('craft-rule', $cx - $unit * 0.12, $h * 0.78, $unit * 0.24, $ink, $unit * 0.003);

            return $objects;
        }

        // Tattoo.
        $objects[] = $this->circle('tattoo-ring', $cx, $h * 0.4, $unit * 0.24, 'none', $ink, $stroke);
        $objects[] = $this->circle('tattoo-ring-inner', $cx, $h * 0.4, $unit * 0.18, 'none', $ink, $stroke * 0.6);
        $objects[] = $this->pathGroup('tattoo-wave', 'M 300 520 Q 400 380 500 520 Q 600 660 700 520', $cx, $h * 0.4, $unit * 0.8, 'none', 0, $stroke, $ink);
        $objects[] = $this->pathGroup('tattoo-leaf-1', 'M 500 300 Q 620 240 660 340 Q 580 390 500 300 Z', $cx, $h * 0.4, $unit * 0.8, 'none', 0, $stroke, $ink);
        $objects[] = $this->pathGroup('tattoo-leaf-2', 'M 500 300 Q 380 240 340 340 Q 420 390 500 300 Z', $cx, $h * 0.4, $unit * 0.8, 'none', 0, $stroke, $ink);
        $title = $this->block($c['headline'], $w * 0.7, $unit * 0.06, 1, $c, 600, 240, true);
        $objects[] = $this->text('tattoo-title', 'new-text', $title['text'], $cx, $h * 0.72, $title['size'], $ink, $c, 600, 240, 'center');
        $objects[] = $this->text('tattoo-note', 'new-text', 'Line art · stencil ready', $cx, $h * 0.82, $unit * 0.022, $ink, $c, 400, 160, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutInfo(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.075;
        $objects = [];
        $variant = $c['variant'];

        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.07, 2, $c, 800, 10);
        $objects[] = $this->text('info-title', 'primary-text', $title['text'], $m, $m * 0.8, $title['size'], $c['ink'], $c, 800, 10);
        $objects[] = $this->rule('info-rule', $m, $m * 0.8 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.02, $unit * 0.16, $c['accent'], $unit * 0.005);
        $top = $m * 0.8 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.06;

        if ($variant === 'comparison') {
            $rows = $this->rows($c['body'], 6);
            $colW = ($w - 2 * $m) / 2;
            $objects[] = $this->text('cmp-col-a', 'new-text', $c['brand'], $m + $colW / 2, $top, $unit * 0.03, $c['ink'], $c, 700, 80, 'center');
            $objects[] = $this->text('cmp-col-b', 'new-text', 'The alternative', $m + $colW * 1.5, $top, $unit * 0.03, $c['muted'], $c, 600, 80, 'center');
            foreach ($rows as $index => $row) {
                $y = $top + $unit * 0.07 + $index * $unit * 0.075;
                $objects[] = $this->rule('cmp-rule-'.$index, $m, $y, $w - 2 * $m, $c['muted'], $unit * 0.0012, 0.3);
                $objects[] = $this->text('cmp-label-'.$index, 'new-text', $row[0] ?? '', $m, $y + $unit * 0.018, $unit * 0.023, $c['ink'], $c, 500, 20);
                $objects[] = $this->text('cmp-a-'.$index, 'new-text', '✓  '.($row[2] ?? 'Included'), $m + $colW * 1.05, $y + $unit * 0.018, $unit * 0.021, $c['accent'], $c, 600, 20);
                $objects[] = $this->text('cmp-b-'.$index, 'new-text', '—  '.($row[3] ?? 'Not included'), $m + $colW * 1.55, $y + $unit * 0.018, $unit * 0.021, $c['muted'], $c, 500, 20);
            }

            return $objects;
        }

        if ($variant === 'flowchart' || $variant === 'diagram') {
            $steps = array_slice(array_values(array_filter(array_map('trim', preg_split('/\n+/', $c['steps']) ?: []))), 0, 4);
            if ($steps === []) {
                $steps = ['Discover', 'Design', 'Deliver'];
            }
            $count = count($steps);
            $boxW = min($w * 0.24, ($w - 2 * $m - ($count - 1) * $unit * 0.04) / $count);
            $boxH = $unit * 0.16;
            $startX = ($w - ($count * $boxW + ($count - 1) * $unit * 0.04)) / 2;
            foreach ($steps as $index => $step) {
                $x = $startX + $index * ($boxW + $unit * 0.04);
                $y = $h * 0.42;
                $objects[] = $this->rect('flow-box-'.$index, $x, $y, $boxW, $boxH, $index === 0 ? $c['accent'] : $c['support'], $unit * 0.014, $index === 0 ? 1 : 0.4);
                $objects[] = $this->circle('flow-num-'.$index, $x + $unit * 0.03, $y + $unit * 0.03, $unit * 0.016, $index === 0 ? Color::readableInk($c['accent']) : $c['accent']);
                $objects[] = $this->text('flow-step-'.$index, 'new-text', $step, $x + $boxW / 2, $y + $boxH * 0.42, $unit * 0.024, $index === 0 ? Color::readableInk($c['accent']) : $c['ink'], $c, 600, 20, 'center');
                if ($index < $count - 1) {
                    $objects[] = $this->rule('flow-arrow-'.$index, $x + $boxW + $unit * 0.008, $y + $boxH / 2, $unit * 0.024, $c['accent'], $unit * 0.004);
                }
            }

            return $objects;
        }

        // Infographic / product feature list.
        $rows = $this->rows($c['body'], 4);
        if ($rows === []) {
            $rows = [['01', 'Discover', 'Research and direction'], ['02', 'Design', 'Systems and craft'], ['03', 'Deliver', 'Launch and support']];
        }
        $rowH = min($unit * 0.13, ($h - $top - $m) / max(1, count($rows)));
        foreach ($rows as $index => $row) {
            $y = $top + $index * ($rowH + $unit * 0.02);
            $objects[] = $this->rect('info-card-'.$index, $m, $y, $w - 2 * $m, $rowH, $c['support'], $unit * 0.012, 0.25);
            $objects[] = $this->circle('info-badge-'.$index, $m + $unit * 0.06, $y + $rowH / 2, $unit * 0.035, $c['accent']);
            $objects[] = $this->text('info-num-'.$index, 'shape', $row[0] ?? (string) ($index + 1), $m + $unit * 0.06, $y + $rowH / 2 - $unit * 0.016, $unit * 0.024, Color::readableInk($c['accent']), $c, 700, 0, 'center');
            $objects[] = $this->text('info-label-'.$index, 'new-text', $row[1] ?? $row[0] ?? '', $m + $unit * 0.12, $y + $rowH * 0.24, $unit * 0.028, $c['ink'], $c, 700, 20);
            $detail = $row[2] ?? ($row[1] ?? '');
            $objects[] = $this->text('info-detail-'.$index, 'new-text', $detail, $m + $unit * 0.12, $y + $rowH * 0.58, $unit * 0.021, $c['muted'], $c, 400, 20);
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutQr(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $objects = [];

        $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.14, $unit * 0.08));
        $title = $this->block($c['headline'], $w * 0.8, $unit * 0.055, 1, $c, 800, 60, true);
        $objects[] = $this->text('qr-title', 'primary-text', $title['text'], $cx, $h * 0.22, $title['size'], $c['ink'], $c, 800, 60, 'center');

        $frame = $unit * 0.62;
        $frameX = $cx - $frame / 2;
        $frameY = $h * 0.32;
        $objects[] = $this->rect('qr-frame', $frameX - $unit * 0.03, $frameY - $unit * 0.03, $frame + $unit * 0.06, $frame + $unit * 0.06, '#FFFFFF', $unit * 0.02);
        $objects[] = $this->rect('qr-frame-border', $frameX - $unit * 0.03, $frameY - $unit * 0.03, $frame + $unit * 0.06, $unit * 0.006, $c['accent']);

        if (is_string($c['qr_image']) && $c['qr_image'] !== '') {
            $objects[] = [
                'id' => 'qr-image',
                'role' => 'logo',
                'type' => 'image',
                'left' => $cx,
                'top' => $frameY + $frame / 2,
                'originX' => 'center',
                'originY' => 'center',
                'width' => $frame,
                'height' => $frame,
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => $c['qr_image'],
            ];
        } else {
            // Design placeholder: a branded code area, not a scannable code.
            $modules = 21;
            $cell = $frame / $modules;
            $hash = md5($c['body'].$c['brand']);
            for ($row = 0; $row < $modules; $row++) {
                for ($col = 0; $col < $modules; $col++) {
                    $isFinder = ($row < 7 && $col < 7) || ($row < 7 && $col >= $modules - 7) || ($row >= $modules - 7 && $col < 7);
                    $on = $isFinder
                        ? ($row % 6 === 0 || $col % 6 === 0 || ($row >= 2 && $row <= 4 && $col >= 2 && $col <= 4))
                        : (hexdec($hash[($row * $modules + $col) % 32]) % 3 !== 0);
                    if ($on) {
                        $objects[] = $this->rect('qr-mod-'.$row.'-'.$col, $frameX + $col * $cell, $frameY + $row * $cell, $cell * 0.92, $cell * 0.92, $c['ink']);
                    }
                }
            }
        }

        $ctaY = $frameY + $frame + $unit * 0.06;
        $objects = array_merge($objects, $this->ctaPill('qr-cta', $cx - $unit * 0.14, $ctaY, $c['cta'], $c, null, null, true));
        $objects[] = $this->text('qr-url', 'new-text', $c['body'], $cx, $ctaY + $unit * 0.08, $unit * 0.022, $c['muted'], $c, 500, 40, 'center');
        $objects[] = $this->text('qr-brand', 'new-text', $c['brand'], $cx, $h - $unit * 0.07, $unit * 0.022, $c['ink'], $c, 700, 200, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutBarcode(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $objects = [];

        $objects[] = $this->text('barcode-brand', 'new-text', $c['brand'], $unit * 0.08, $unit * 0.08, $unit * 0.03, $c['ink'], $c, 700, 160, 'left');
        $barTop = $h * 0.28;
        $barHeight = $h * 0.34;
        $hash = md5($c['code'].$c['brand']);
        $barX = $w * 0.12;
        $maxX = $w * 0.88;

        for ($i = 0; $i < 72 && $barX < $maxX; $i++) {
            $value = hexdec($hash[$i % 32]);
            $barW = $unit * (0.003 + ($value % 3) * 0.003);
            if ($value % 2 === 0) {
                $objects[] = $this->rect('bar-'.$i, $barX, $barTop, $barW, $barHeight, $c['ink']);
            }
            $barX += $barW + $unit * 0.004;
        }

        $objects[] = $this->text('barcode-code', 'new-text', $c['code'], $cx, $barTop + $barHeight + $unit * 0.025, $unit * 0.045, $c['ink'], $c, 700, 200, 'center');
        $objects[] = $this->text('barcode-label', 'new-text', $c['headline'], $cx, $h * 0.78, $unit * 0.024, $c['muted'], $c, 500, 120, 'center');
        $objects[] = $this->text('barcode-note', 'new-text', 'Design artwork — replace with a scannable code before print.', $cx, $h * 0.88, $unit * 0.016, $c['muted'], $c, 400, 40, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutSticker(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'pack') {
            $objects[] = $this->text('pack-sheet-title', 'new-text', $c['headline'], $cx, $unit * 0.06, $unit * 0.045, $c['ink'], $c, 800, 160, 'center');
            $labels = ['Good vibes', 'On my way', 'Small joy', 'Do not rush', 'Stay curious', 'Made here'];
            $cell = min($w, $h) * 0.28;
            for ($i = 0; $i < 6; $i++) {
                $col = $i % 3;
                $row = intdiv($i, 3);
                $x = $w / 2 + ($col - 1) * $cell * 1.05;
                $y = $h * 0.28 + $row * $cell * 1.05;
                if ($i % 2 === 0) {
                    $objects[] = $this->circle('pack-sticker-'.$i, $x, $y, $cell * 0.42, $c['colors'][$i % 3]);
                } else {
                    $objects[] = $this->rect('pack-sticker-'.$i, $x - $cell * 0.4, $y - $cell * 0.32, $cell * 0.8, $cell * 0.64, $c['colors'][$i % 3], $cell * 0.14);
                }
                $objects[] = $this->text('pack-label-'.$i, 'shape', $labels[$i], $x, $y - $unit * 0.014, $unit * 0.026, Color::readableInk($c['colors'][$i % 3]), $c, 700, 40, 'center');
            }

            return $objects;
        }

        if ($variant === 'badge') {
            $objects[] = $this->circle('badge-ring', $cx, $h * 0.42, $unit * 0.3, $c['accent']);
            $objects[] = $this->circle('badge-inner', $cx, $h * 0.42, $unit * 0.25, 'none', Color::readableInk($c['accent']), $unit * 0.004);
            $initials = $c['initials'];
            $size = $this->fitOne($initials, $unit * 0.3, $unit * 0.22, $c, 800, 20);
            $objects[] = $this->text('badge-initials', 'primary-text', $initials, $cx, $h * 0.42 - $size * 0.6, $size, Color::readableInk($c['accent']), $c, 800, 20, 'center');
            $objects[] = $this->text('badge-role', 'new-text', $c['headline'], $cx, $h * 0.78, $unit * 0.036, $c['ink'], $c, 700, 120, 'center');
            $objects[] = $this->text('badge-brand', 'new-text', $c['brand'], $cx, $h * 0.86, $unit * 0.024, $c['muted'], $c, 500, 200, 'center');

            return $objects;
        }

        // Die-cut sticker.
        $objects[] = $this->circle('sticker-ring', $cx, $h * 0.44, $unit * 0.33, 'none', $c['accent'], $unit * 0.012);
        $objects[] = $this->circle('sticker-ring-inner', $cx, $h * 0.44, $unit * 0.29, 'none', $c['ink'], $unit * 0.002, 0.3);
        $markSize = $unit * 0.15;
        $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.34, $markSize));
        $title = $this->block($c['headline'], $w * 0.6, $unit * 0.06, 2, $c, 800, 40, true);
        $objects[] = $this->text('sticker-title', 'primary-text', $title['text'], $cx, $h * 0.52, $title['size'], $c['ink'], $c, 800, 40, 'center');
        $objects[] = $this->text('sticker-brand', 'new-text', $c['brand'], $cx, $h * 0.82, $unit * 0.024, $c['accent'], $c, 700, 200, 'center');

        return $objects;
    }
}
