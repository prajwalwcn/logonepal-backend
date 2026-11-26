<?php

namespace App\Services\Design\Concerns;

use App\Support\Color;

/**
 * Brand, stationery and print-document layouts.
 */
trait ComposesBrand
{
    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutLockup(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $cx = $w / 2;
        $cy = $h / 2;
        $m = $unit * 0.08;
        $objects = [];
        $name = $c['brand'];
        $tagline = $c['tagline'] ?: $c['subhead'];

        if ($c['variant'] === 'monogram') {
            $radius = $unit * 0.29;
            $objects[] = $this->circle('monogram-ring', $cx, $cy - $unit * 0.04, $radius, 'none', $c['accent'], $unit * 0.008);
            $initials = $c['initials'];
            $size = $this->fitOne($initials, $radius * 1.35, $radius * 1.05, $c, 700, 60);
            $objects[] = $this->text('monogram-initials', 'primary-text', $initials, $cx, $cy - $unit * 0.04 - ($size * 1.12) / 2, $size, $c['ink'], $c, 700, 60, 'center');
            $nameBlock = $this->block($name, $w - 2 * $m, $unit * 0.062, 1, $c, $c['weight'], 140, true);
            $objects[] = $this->text('monogram-name', 'new-text', $nameBlock['text'], $cx, $cy + $radius * 0.62, $nameBlock['size'], $c['muted'], $c, $c['weight'], 140, 'center');

            return $objects;
        }

        if ($c['variant'] === 'neon') {
            $block = $this->block($name, $w - 2 * $m, $unit * 0.15, 2, $c, 800, 40, true);
            $lines = max(1, count($block['lines']));
            $top = $cy - ($block['size'] * 1.12 * $lines) / 2;
            $objects[] = $this->text('neon-glow', 'primary-text', $block['text'], $cx, $top, $block['size'], $c['accent'], $c, 800, 40, 'center', 0.35, [
                'shadow' => ['color' => $c['accent'], 'blur' => $unit * 0.09, 'offsetX' => 0, 'offsetY' => 0],
            ]);
            $objects[] = $this->text('neon-sign', 'primary-text', $block['text'], $cx, $top, $block['size'], $c['ink'], $c, 800, 40, 'center', 1, [
                'stroke' => $c['accent'],
                'strokeWidth' => $unit * 0.006,
                'shadow' => ['color' => $c['accent'], 'blur' => $unit * 0.03, 'offsetX' => 0, 'offsetY' => 0],
            ]);
            $objects[] = $this->rule('neon-rule', $cx - $unit * 0.22, $top + $block['size'] * 1.12 * $lines + $unit * 0.05, $unit * 0.44, $c['accent'], $unit * 0.006);

            return $objects;
        }

        if ($c['variant'] === 'signature') {
            $script = array_merge($c, ['font' => 'Dancing Script', 'font_category' => 'script', 'weight' => 700]);
            $block = $this->block($name, $w - 2 * $m, $unit * 0.2, 1, $script, 700, 10);
            $objects[] = $this->text('signature-name', 'primary-text', $block['text'], $cx, $cy - $block['size'] * 0.75, $block['size'], $c['ink'], $script, 700, 10, 'center');
            $objects[] = $this->rule('signature-rule', $cx - $unit * 0.18, $cy + $unit * 0.09, $unit * 0.36, $c['accent'], $unit * 0.005);
            $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.05, 1, $c, 600, 220, true);
            $objects[] = $this->text('signature-sub', 'new-text', $sub['text'], $cx, $cy + $unit * 0.13, $sub['size'], $c['muted'], $c, 600, 220, 'center');

            return $objects;
        }

        if ($c['variant'] === 'typography') {
            $words = preg_split('/\s+/', $name) ?: [$name];
            $split = max(1, (int) ceil(count($words) / 2));
            $lineOne = implode(' ', array_slice($words, 0, $split));
            $lineTwo = implode(' ', array_slice($words, $split));
            $block = $this->block($lineOne."\n".$lineTwo, $w - 2 * $m, $unit * 0.15, 2, $c, 800, 20, true);
            $lines = max(1, count($block['lines']));
            $top = $cy - ($block['size'] * 1.12 * $lines) / 2;
            $objects[] = $this->rect('type-accent', $m * 0.6, $top - $unit * 0.03, $w - $m * 1.2, $block['size'] * 1.12 * $lines + $unit * 0.06, $c['accent'], 0, 0.14);
            $objects[] = $this->text('type-name', 'primary-text', $block['text'], $cx, $top, $block['size'], $c['ink'], $c, 800, 20, 'center');
            $sub = $this->block($tagline, $w - 2 * $m, $unit * 0.045, 1, $c, 600, 260, true);
            $objects[] = $this->text('type-tagline', 'new-text', $sub['text'], $cx, $top + $block['size'] * 1.12 * $lines + $unit * 0.06, $sub['size'], $c['muted'], $c, 600, 260, 'center');

            return $objects;
        }

        if ($c['variant'] === 'wordmark') {
            $block = $this->block($name, $w - 2 * $m, $unit * 0.19, 1, $c, $c['weight'], 60, true);
            $objects[] = $this->text('wordmark-name', 'primary-text', $block['text'], $cx, $cy - $block['size'] * 0.78, $block['size'], $c['ink'], $c, $c['weight'], 60, 'center');
            $objects[] = $this->rule('wordmark-rule', $cx - $block['width'] * 0.22, $cy + $unit * 0.055, $block['width'] * 0.44, $c['accent'], $unit * 0.005);
            $sub = $this->block($tagline, $w - 2 * $m, $unit * 0.045, 1, $c, 600, 300, true);
            $objects[] = $this->text('wordmark-tagline', 'new-text', $sub['text'], $cx, $cy + $unit * 0.09, $sub['size'], $c['muted'], $c, 600, 300, 'center');

            return $objects;
        }

        // Default lockup: mark above (or beside) the name.
        $wide = $w > $h * 1.6;
        $markSize = $unit * ($wide ? 0.26 : 0.22);

        if ($wide) {
            $markCx = $m + $markSize / 2 + $unit * 0.03;
            $textX = $markCx + $markSize / 2 + $unit * 0.07;
            $textWidth = $w - $textX - $m;
            $objects = array_merge($objects, $this->mark($c, $markCx, $cy, $markSize));
            $nameBlock = $this->block($name, $textWidth, $unit * 0.13, 2, $c, $c['weight'], 30, true);
            $nameTop = $cy - ($nameBlock['size'] * 1.12 * max(1, count($nameBlock['lines'])) + $unit * 0.06) / 2;
            $objects[] = $this->text('lockup-name', 'primary-text', $nameBlock['text'], $textX, $nameTop, $nameBlock['size'], $c['ink'], $c, $c['weight'], 30);
            $sub = $this->block($tagline, $textWidth, $unit * 0.04, 1, $c, 600, 260, true);
            $objects[] = $this->text('lockup-tagline', 'new-text', $sub['text'], $textX, $nameTop + $nameBlock['size'] * 1.12 * max(1, count($nameBlock['lines'])) + $unit * 0.035, $sub['size'], $c['muted'], $c, 600, 260);

            return $objects;
        }

        $objects = array_merge($objects, $this->mark($c, $cx, $cy - $unit * 0.15, $markSize));
        $nameBlock = $this->block($name, $w - 2 * $m, $unit * 0.12, 2, $c, $c['weight'], 40, true);
        $nameTop = $cy + $unit * 0.02;
        $objects[] = $this->text('lockup-name', 'primary-text', $nameBlock['text'], $cx, $nameTop, $nameBlock['size'], $c['ink'], $c, $c['weight'], 40, 'center');
        $sub = $this->block($tagline, $w - 2 * $m, $unit * 0.042, 1, $c, 600, 280, true);
        $objects[] = $this->text('lockup-tagline', 'new-text', $sub['text'], $cx, $nameTop + $nameBlock['size'] * 1.12 * max(1, count($nameBlock['lines'])) + $unit * 0.045, $sub['size'], $c['muted'], $c, 600, 280, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutKit(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.07;
        $objects = [];

        $objects[] = $this->text('kit-brand', 'primary-text', $c['brand'], $m, $m * 0.55, $unit * 0.045, $c['ink'], $c, $c['weight'], 40, 'left');
        $label = $this->block('Brand identity', $w * 0.4, $unit * 0.025, 1, $c, 600, 240, true);
        $objects[] = $this->text('kit-label', 'new-text', $label['text'], $w - $m, $m * 0.55 + $unit * 0.012, $label['size'], $c['muted'], $c, 600, 240, 'center');
        $objects[] = $this->rule('kit-rule', $m, $m + $unit * 0.06, $w - 2 * $m, $c['accent'], $unit * 0.004);

        $markSize = $unit * 0.19;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $h * 0.3, $markSize));
        $nameBlock = $this->block($c['brand'], $w * 0.5, $unit * 0.075, 2, $c, $c['weight'], 30, true);
        $objects[] = $this->text('kit-name', 'primary-text', $nameBlock['text'], $m, $h * 0.4, $nameBlock['size'], $c['ink'], $c, $c['weight'], 30);
        $tag = $this->block($c['tagline'], $w * 0.5, $unit * 0.03, 1, $c, 600, 240, true);
        $objects[] = $this->text('kit-tagline', 'new-text', $tag['text'], $m, $h * 0.4 + $nameBlock['size'] * 1.12 * max(1, count($nameBlock['lines'])) + $unit * 0.02, $tag['size'], $c['muted'], $c, 600, 240);

        // Palette swatches.
        $swatchX = $w * 0.62;
        $swatchY = $h * 0.18;
        $swatch = $unit * 0.075;
        foreach ($c['colors'] as $index => $color) {
            $x = $swatchX + $index * ($swatch + $unit * 0.02);
            $objects[] = $this->rect('kit-swatch-'.$index, $x, $swatchY, $swatch, $swatch, $color, $swatch * 0.12);
            $objects[] = $this->text('kit-hex-'.$index, 'shape', strtoupper($color), $x, $swatchY + $swatch + $unit * 0.012, $unit * 0.017, $c['muted'], $c, 600, 60);
        }

        // Typography specimen.
        $typeY = $h * 0.58;
        $objects[] = $this->rule('kit-type-rule', $m, $typeY - $unit * 0.03, $w - 2 * $m, $c['muted'], $unit * 0.002, 0.4);
        $objects[] = $this->text('kit-aa', 'primary-text', 'Aa', $m, $typeY, $unit * 0.15, $c['ink'], $c, $c['weight'], 0);
        $objects[] = $this->text('kit-font-name', 'new-text', $c['font'], $m + $unit * 0.19, $typeY + $unit * 0.03, $unit * 0.03, $c['ink'], $c, 600, 20);
        $body = $this->block($c['body'], $w * 0.42, $unit * 0.022, 4, $c, 400, 10);
        $objects[] = $this->text('kit-body', 'new-text', $body['text'], $m + $unit * 0.19, $typeY + $unit * 0.085, $body['size'], $c['muted'], $c, 400, 10);

        // Mini business card.
        $cardX = $w * 0.55;
        $cardY = $h * 0.66;
        $cardW = $w * 0.37;
        $cardH = $cardW * 0.58;
        $objects[] = $this->rect('kit-card', $cardX, $cardY, $cardW, $cardH, $c['dark'] ? $c['colors'][1] : '#FFFFFF', $unit * 0.012);
        $objects[] = $this->rect('kit-card-bar', $cardX, $cardY, $cardW * 0.035, $cardH, $c['accent']);
        $objects = array_merge($objects, $this->mark($c, $cardX + $cardW * 0.2, $cardY + $cardH * 0.35, $cardH * 0.3, $c['accent']));
        $cardName = $this->block($c['headline'], $cardW * 0.55, $cardH * 0.16, 1, $c, 700, 20);
        $objects[] = $this->text('kit-card-name', 'new-text', $cardName['text'], $cardX + $cardW * 0.36, $cardY + $cardH * 0.34, $cardName['size'], $c['dark'] ? '#FFFFFF' : $c['ink'], $c, 700, 20);
        $objects[] = $this->text('kit-card-role', 'new-text', $c['role'], $cardX + $cardW * 0.36, $cardY + $cardH * 0.52, $cardH * 0.085, $c['dark'] ? '#E5E7EB' : $c['muted'], $c, 400, 40);

        $objects[] = $this->text('kit-footer', 'new-text', $c['website'], $m, $h - $m * 0.75, $unit * 0.024, $c['muted'], $c, 600, 120);

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutCard(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.095;
        $style = (string) ($c['style'] ?? 'wave-qr');
        $theme = is_array($c['card_theme'] ?? null) && count($c['card_theme']) >= 6 ? $c['card_theme'] : null;
        $seedOffset = (int) ($c['variant_seed'] ?? 0);
        // Optional per-page studio theme overrides (cards 1..14 keep signature colorways; cards 15..364 cycle 18 rich themes)
        $th = static fn (int $idx, string $default): string => $theme !== null ? (string) $theme[$idx] : $default;
        $objects = [];

        $hlWeight = max(400, (int) ($c['headline_weight'] ?? 700));
        $hlCase = (string) ($c['headline_case'] ?? 'upper');
        $hlTrack = (float) ($c['headline_tracking'] ?? 25);
        $hlScale = (float) ($c['headline_scale'] ?? 1.0);
        $cBody = array_merge($c, [
            'font' => (string) ($c['body_font'] ?? 'Inter'),
            'font_category' => 'sans',
            'weight' => 500,
        ]);

        $defaultPersonas = [
            'wave-qr' => ['Name', 'Job Title', 'title'],
            'arch-stripes-qr' => ['FULL NAME', 'Founder & CEO', 'upper'],
            'midnight-cyan' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'ribbon-navy' => ['FULL NAME', 'FOUNDER & CEO', 'upper'],
            'executive-gold' => ['FULL NAME', 'JOB TITLE', 'upper'],
            'hatch-badge' => ['FULL NAME', 'FOUNDER & CEO', 'upper'],
            'forest-editorial' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'blush-rose' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'stepped-white-qr' => ['FULL NAME', 'JOB TITLE', 'upper'],
            'lowpoly-qr' => ['FULL NAME', 'FOUNDER & CEO', 'upper'],
            'diagonal-qr' => ['FIRST NAME SURNAME', 'FOUNDER & CEO', 'upper'],
            'olive-sage' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'sand-editorial' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'sky-arcs' => ['FULL NAME', 'JOB POSITION', 'upper'],
            'brush-noir' => ['Full Name', 'Creative Lead', 'title'],
            'bracket-frame' => ['FULL NAME', 'Managing Director', 'upper'],
            'split-contrast' => ['FULL NAME', 'CEO & Founder', 'upper'],
            'dot-matrix' => ['FULL NAME', 'Principal Architect', 'upper'],
        ];

        $hasCustomName = ! empty($c['headline']) && $c['headline'] !== 'Avery Lane' && $c['headline'] !== 'Make it memorable';
        $stylePersona = $defaultPersonas[$style] ?? ['FULL NAME', 'CEO & Founder', 'upper'];

        $rawPersonName = $hasCustomName ? trim($c['headline']) : $stylePersona[0];
        $effectiveCase = $hasCustomName ? $hlCase : $stylePersona[2];
        $personName = $effectiveCase === 'title'
            ? mb_convert_case($rawPersonName, MB_CASE_TITLE, 'UTF-8')
            : mb_strtoupper($rawPersonName);

        $hasCustomRole = ! empty($c['role']) && $c['role'] !== 'Creative Director';
        $personRole = $hasCustomRole ? trim($c['role']) : $stylePersona[1];
        $brandUpper = mb_strtoupper(trim($c['brand'] !== '' ? $c['brand'] : 'YOUR BRAND'));
        $slogan = mb_strtoupper(trim($c['tagline'] !== '' ? $c['tagline'] : 'SLOGAN HERE'));
        $phone = $c['phone'] !== '' ? $c['phone'] : '415-999-9999';
        $email = $c['email'] !== '' ? $c['email'] : 'hello@'.strtolower(preg_replace('/[^a-z0-9]/i', '', $c['brand']) ?: 'brand').'.com';
        $website = $c['website'] !== '' ? $c['website'] : 'website.com';
        $contact = array_values(array_filter([$phone, $email, $website]));

        // Crisp monochrome vector contact icons (phone, mail, globe) in 24x24 viewBox
        $contactIcons = [
            'M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.24 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .57 3.6 1 1 0 0 1-.25 1Z',
            'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 3.2V18h16V7.2l-8 5.2Z',
            'M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm6.9 6h-3.1a15 15 0 0 0-1.4-3.6A8 8 0 0 1 18.9 8ZM12 4.1a13 13 0 0 1 1.9 3.9h-3.8A13 13 0 0 1 12 4.1ZM4.3 14a8 8 0 0 1 0-4h3.4a16 16 0 0 0 0 4Zm.8 2h3.1a15 15 0 0 0 1.4 3.6A8 8 0 0 1 5.1 16Zm3.1-8H5.1a8 8 0 0 1 4.5-3.6A15 15 0 0 0 8.2 8ZM12 19.9a13 13 0 0 1-1.9-3.9h3.8A13 13 0 0 1 12 19.9Zm2.3-5.9H9.7a14 14 0 0 1 0-4h4.6a14 14 0 0 1 0 4Zm.1 5.6a15 15 0 0 0 1.4-3.6h3.1a8 8 0 0 1-4.5 3.6Zm1.9-5.6a16 16 0 0 0 0-4h3.4a8 8 0 0 1 0 4Z',
        ];
        $addContacts = function (float $x, float $startY, float $stepY, float $fontSize, string $textColor, string $iconColor) use (&$objects, $contact, $contactIcons, $cBody): void {
            $iconSize = $fontSize * 0.9;
            foreach ($contact as $i => $line) {
                $rowY = $startY + $i * $stepY;
                $objects[] = [
                    'id' => 'card-cicon-'.$i,
                    'role' => 'shape',
                    'type' => 'group',
                    'left' => round($x + $iconSize * 0.5, 2),
                    'top' => round($rowY + $fontSize * 0.52, 2),
                    'originX' => 'center',
                    'originY' => 'center',
                    'scaleX' => round($iconSize / 24, 5),
                    'scaleY' => round($iconSize / 24, 5),
                    'angle' => 0,
                    'pathBox' => 24,
                    'objects' => [[
                        'id' => 'card-cicon-p-'.$i,
                        'type' => 'path',
                        'role' => 'logo-path',
                        'd' => $contactIcons[$i % 3],
                        'fill' => $iconColor,
                        'tone' => 1,
                    ]],
                ];
                $objects[] = $this->text('card-contact-'.$i, 'new-text', $line, $x + $iconSize * 1.45, $rowY, $fontSize, $textColor, $cBody, 500, 20);
            }
        };

        // Crisp deterministic vector QR Code (21x21 module matrix with authentic finder patterns)
        $addQr = function (string $id, float $cx, float $cy, float $size, string $fg, ?string $bg = null) use (&$objects, $brandUpper, $seedOffset): void {
            if ($bg !== null) {
                $objects[] = $this->rect($id.'-bg', $cx - $size * 0.54, $cy - $size * 0.54, $size * 1.08, $size * 1.08, $bg, $size * 0.04);
            }
            $n = 21;
            $seed = crc32($brandUpper.'|'.$id.'|'.$seedOffset);
            $parts = [];
            $addFinder = static function (int $ox, int $oy) use (&$parts): void {
                $parts[] = "M {$ox} {$oy} h 7 v 7 h -7 Z M ".($ox + 1).' '.($oy + 1).' v 5 h 5 v -5 Z';
                $parts[] = 'M '.($ox + 2).' '.($oy + 2).' h 3 v 3 h -3 Z';
            };
            $addFinder(0, 0);
            $addFinder($n - 7, 0);
            $addFinder(0, $n - 7);
            for ($r = 0; $r < $n; $r++) {
                for ($col = 0; $col < $n; $col++) {
                    $inTopLeft = $r < 8 && $col < 8;
                    $inTopRight = $r < 8 && $col >= $n - 8;
                    $inBotLeft = $r >= $n - 8 && $col < 8;
                    if ($inTopLeft || $inTopRight || $inBotLeft) {
                        continue;
                    }
                    $hash = ($seed ^ ($r * 73856093) ^ ($col * 19349663)) & 0x7FFFFFFF;
                    if (($hash % 100) < 48) {
                        $parts[] = "M {$col} {$r} h 1 v 1 h -1 Z";
                    }
                }
            }
            $objects[] = [
                'id' => $id,
                'role' => 'shape',
                'type' => 'group',
                'left' => round($cx, 2),
                'top' => round($cy, 2),
                'originX' => 'center',
                'originY' => 'center',
                'scaleX' => round($size / $n, 5),
                'scaleY' => round($size / $n, 5),
                'angle' => 0,
                'pathBox' => $n,
                'objects' => [[
                    'id' => $id.'-path',
                    'type' => 'path',
                    'role' => 'logo-path',
                    'd' => implode(' ', $parts),
                    'fill' => $fg,
                    'fill_rule' => 'evenodd',
                    'tone' => 1,
                ]],
            ];
        };

        $brandBlock = $this->block($brandUpper, $w * 0.34, $unit * 0.044, 1, $cBody, 800, 100, true);
        $nameBlock = $this->block($personName, $w * 0.46, $unit * 0.115 * $hlScale, 1, $c, $hlWeight, $hlTrack, $effectiveCase !== 'title');

        if (($c['card_side'] ?? 'front') === 'back') {
            $primaryColor = $c['colors'][0] ?? $c['ink'];
            $isDarkBack = $c['dark'] || in_array($style, ['midnight-cyan', 'ribbon-navy', 'executive-gold', 'forest-editorial', 'brush-noir', 'split-contrast'], true);
            $backBg = $theme !== null
                ? $th(0, $isDarkBack ? '#0F172A' : '#FFFFFF')
                : ($isDarkBack ? $primaryColor : '#FAF9F6');
            $backAccent = $theme !== null ? $th(1, $c['accent']) : $c['accent'];
            $backInk = Color::contrast($backBg, '#FFFFFF') >= 4.2 ? '#FFFFFF' : '#111827';
            $backSub = Color::contrast($backBg, '#FFFFFF') >= 4.2 ? 'rgba(255,255,255,0.72)' : '#64748B';

            $objects[] = $this->rect('card-back-bg', 0, 0, $w, $h, $backBg);
            $objects[] = $this->rect('card-back-bar-top', 0, 0, $w, $unit * 0.022, $backAccent);
            $objects[] = $this->rect('card-back-bar-bot', 0, $h - $unit * 0.022, $w, $unit * 0.022, $backAccent);
            $objects[] = array_merge(
                $this->rect('card-back-frame', $m * 0.7, $m * 0.7, $w - $m * 1.4, $h - $m * 1.4, 'transparent', $unit * 0.015),
                ['fill' => ['type' => 'none'], 'stroke' => $backAccent, 'strokeWidth' => 1.5],
            );

            $hasLogo = (! empty($c['logo_image']) || is_array($c['symbol'] ?? null)) && empty($c['no_logo']);
            $brandY = $hasLogo ? $h * 0.56 : $h * 0.41;

            if ($hasLogo) {
                $logoSize = $unit * 0.24;
                if (! empty($c['logo_image'])) {
                    $objects[] = $this->imageObject('card-back-logo', 'logo', (string) $c['logo_image'], $w * 0.5, $h * 0.34, $logoSize, $logoSize);
                } elseif (! empty($c['symbol']['image_url'])) {
                    $objects[] = $this->imageObject('card-back-logo', 'logo', (string) $c['symbol']['image_url'], $w * 0.5, $h * 0.34, $logoSize, $logoSize);
                } elseif (! empty($c['symbol']['paths'])) {
                    $objects[] = $this->symbolGroup($c, $w * 0.5, $h * 0.34, $logoSize, $backAccent);
                } else {
                    $objects = array_merge($objects, $this->mark($c, $w * 0.5, $h * 0.34, $logoSize, $backAccent));
                }
            }

            $backBrand = $this->block($brandUpper, $w * 0.72, $unit * 0.092 * $hlScale, 1, $c, $hlWeight, 110, true);
            $objects[] = $this->text('card-back-brand', 'primary-text', $backBrand['text'], $w * 0.5, $brandY, $backBrand['size'], $backInk, $c, $hlWeight, 110, 'center');
            $objects[] = $this->text('card-back-slogan', 'new-text', $slogan, $w * 0.5, $brandY + $backBrand['size'] * 1.32, $unit * 0.036, $backAccent, $cBody, 600, 140, 'center');
            $objects[] = $this->text('card-back-web', 'new-text', strtolower($website), $w * 0.5, $h - $m * 1.15, $unit * 0.034, $backSub, $cBody, 500, 40, 'center');

            return $objects;
        }

        // 1. WAVE QR — Topographic waves bottom-left + Logo top-left + Name & QR right
        if ($style === 'wave-qr') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FAF9F6');
            for ($i = 0; $i < 6; $i++) {
                $dy = $i * 22;
                $objects[] = [
                    'id' => 'card-wave-'.$i,
                    'role' => 'shape',
                    'type' => 'path',
                    'left' => $w / 2,
                    'top' => $h / 2,
                    'originX' => 'center',
                    'originY' => 'center',
                    'd' => sprintf('M -20 %d Q %d %d %d %d T %d %d',
                        (int) ($h * 0.42 + $dy), (int) ($w * 0.18), (int) ($h * 0.72 + $dy * 0.7),
                        (int) ($w * 0.48), (int) ($h * 0.82 + $dy * 0.5), (int) ($w * 0.95), (int) ($h + 30 + $dy * 0.4)
                    ),
                    'fill' => 'none',
                    'stroke' => '#CBD5E1',
                    'strokeWidth' => 2.5,
                ];
            }
            $objects = array_merge($objects, $this->mark($c, $w * 0.17, $h * 0.27, $unit * 0.24, '#1E3A8A'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.17, $h * 0.43, $brandBlock['size'] * 0.85, '#1E293B', $cBody, 700, 100, 'center');
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $w * 0.91, $m * 0.9, $nameBlock['size'] * 0.88, '#111827', $c, $hlWeight, $hlTrack, 'right');
            $objects[] = $this->text('card-role', 'new-text', $personRole, $w * 0.91, $m * 0.9 + $nameBlock['size'] * 1.05, $unit * 0.052, '#475569', $cBody, 500, 20, 'right');
            $addQr('card-qr', $w * 0.77, $h * 0.61, $unit * 0.34, '#111827');
            $objects[] = $this->text('card-foot', 'new-text', $phone.'  ·  '.$website, $w * 0.91, $h - $m * 0.85, $unit * 0.036, '#475569', $cBody, 500, 30, 'right');

            return $objects;
        }

        // 2. ARCH STRIPES QR — Architectural vertical stripes + condensed left + Logo & QR right
        if ($style === 'arch-stripes-qr') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#F4F5F7');
            foreach ([0.04, 0.11, 0.21, 0.35, 0.56, 0.78, 0.91] as $idx => $pos) {
                $objects[] = $this->rect('card-stripe-'.$idx, $w * $pos, 0, $w * 0.035, $h, '#E5E7EB', 0, 0.55);
            }
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m * 1.05, $h * 0.24, $nameBlock['size'] * 0.95, '#0F172A', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m * 1.05, $h * 0.43, $unit * 0.052, '#334155', $cBody, 600, 30);
            $objects[] = $this->text('card-c1', 'new-text', $phone, $m * 1.05, $h * 0.59, $unit * 0.04, '#1E293B', $cBody, 600, 20);
            $objects[] = $this->text('card-c2', 'new-text', $email, $m * 1.05, $h * 0.67, $unit * 0.04, '#1E293B', $cBody, 500, 20);
            $objects[] = $this->text('card-c3', 'new-text', $website, $m * 1.05, $h * 0.75, $unit * 0.04, '#1E293B', $cBody, 500, 20);
            $objects = array_merge($objects, $this->mark($c, $w * 0.80, $h * 0.27, $unit * 0.22, '#0F172A'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.80, $h * 0.41, $brandBlock['size'] * 0.78, '#0F172A', $cBody, 700, 100, 'center');
            $addQr('card-qr', $w * 0.80, $h * 0.71, $unit * 0.30, '#0F172A');

            return $objects;
        }

        // 3. MIDNIGHT CYAN — Deep midnight indigo + electric cyan (or themed dark)
        if ($style === 'midnight-cyan') {
            $dBg = $th(0, '#0D0838');
            $dAcc = $th(1, '#38BDF8');
            $dSub = $th(2, '#7DD3FC');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $dBg);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.35, $nameBlock['size'], $dAcc, $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m, $m * 1.35 + $nameBlock['size'] * 1.25, $unit * 0.056, $dSub, $cBody, 500, 30);
            $addContacts($m, $h * 0.56, $unit * 0.095, $unit * 0.044, '#E2E8F0', $dAcc);
            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.27, $dAcc));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.63, $brandBlock['size'] * 0.85, $dSub, $cBody, 700, 120, 'center');

            return $objects;
        }

        // 4. RIBBON NAVY — Sweeping royal navy curve + nested parallel curve strokes
        if ($style === 'ribbon-navy') {
            $ribCol = $th(4, '#1D417A');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = [
                'id' => 'card-ribbon-fill',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w / 2,
                'top' => $h / 2,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M 0 0 L %d 0 Q %d %d %d %d L 0 %d Z',
                    (int) ($w * 0.58), (int) ($w * 0.55), (int) ($h * 0.62), (int) ($w * 0.14), (int) $h, (int) $h
                ),
                'fill' => $ribCol,
            ];
            for ($i = 1; $i <= 3; $i++) {
                $off = $i * 18;
                $objects[] = [
                    'id' => 'card-ribbon-line-'.$i,
                    'role' => 'shape',
                    'type' => 'path',
                    'left' => $w / 2,
                    'top' => $h / 2,
                    'originX' => 'center',
                    'originY' => 'center',
                    'd' => sprintf('M %d 0 Q %d %d %d %d',
                        (int) ($w * 0.58 + $off), (int) ($w * 0.55 + $off), (int) ($h * 0.64), (int) ($w * 0.14 + $off * 1.4), (int) $h
                    ),
                    'fill' => 'none',
                    'stroke' => $ribCol,
                    'strokeWidth' => 4,
                ];
            }
            $stacked = $this->block($personName, $w * 0.36, $unit * 0.112 * $hlScale, 2, $c, $hlWeight, $hlTrack, $hlCase !== 'title');
            $objects[] = $this->text('card-name', 'primary-text', $stacked['text'], $m * 0.9, $m * 1.2, $stacked['size'], '#FFFFFF', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m * 0.9, $h * 0.56, $unit * 0.046, '#E2E8F0', $cBody, 600, 80);
            $objects = array_merge($objects, $this->mark($c, $w * 0.78, $h * 0.30, $unit * 0.25, $ribCol));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.78, $h * 0.46, $brandBlock['size'] * 0.8, $ribCol, $cBody, 700, 100, 'center');
            $objects[] = $this->text('card-r1', 'new-text', $phone, $w * 0.92, $h * 0.67, $unit * 0.038, '#334155', $cBody, 600, 20, 'right');
            $objects[] = $this->text('card-r2', 'new-text', $email, $w * 0.92, $h * 0.75, $unit * 0.038, '#334155', $cBody, 500, 20, 'right');
            $objects[] = $this->text('card-r3', 'new-text', $website, $w * 0.92, $h * 0.83, $unit * 0.038, '#334155', $cBody, 500, 20, 'right');

            return $objects;
        }

        // 5. EXECUTIVE GOLD — Midnight navy + gold notched corners + centered gold title & QR
        if ($style === 'executive-gold') {
            $eBg = $th(0, '#0B1E45');
            $eAcc = $th(1, '#F59E0B');
            $eSub = $th(2, '#FDE68A');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $eBg);
            $objects[] = [
                'id' => 'card-gold-top',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w / 2,
                'top' => $h / 2,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M 0 0 L %d 0 L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L 0 %d Z',
                    (int) $w, (int) $w, (int) ($h * 0.26), (int) ($w * 0.92), (int) ($h * 0.26),
                    (int) ($w * 0.92), (int) ($h * 0.14), (int) ($w * 0.86), (int) ($h * 0.14),
                    (int) ($w * 0.86), (int) ($h * 0.06), (int) ($w * 0.14), (int) ($h * 0.06),
                    (int) ($w * 0.14), (int) ($h * 0.14), (int) ($w * 0.08), (int) ($h * 0.14),
                    (int) ($w * 0.08), (int) ($h * 0.26), (int) ($h * 0.26)
                ),
                'fill' => $eAcc,
            ];
            $goldNameSize = min($nameBlock['size'] * 0.9, $unit * 0.115);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $w / 2, $h * 0.135, $goldNameSize, $eAcc, $c, $hlWeight, $hlTrack, 'center');
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $w / 2, $h * 0.30, $unit * 0.044, $eSub, $cBody, 600, 120, 'center');
            $objects = array_merge($objects, $this->mark($c, $w * 0.37, $h * 0.53, $unit * 0.20, $eAcc));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.37, $h * 0.72, $brandBlock['size'] * 0.72, $eSub, $cBody, 700, 100, 'center');
            $addQr('card-qr', $w * 0.63, $h * 0.59, $unit * 0.29, $eAcc);
            $objects[] = $this->text('card-bottom', 'new-text', $phone.'   ·   '.$email, $w / 2, $h * 0.86, $unit * 0.036, '#F8FAFC', $cBody, 500, 30, 'center');

            return $objects;
        }

        // 6. HATCH BADGE — Crisp white + circular emblem badge flanked by diagonal hatch semicircles
        if ($style === 'hatch-badge') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = $this->circle('card-hatch-top', $w * 0.79, 0, $unit * 0.22, '#E2E8F0', '#94A3B8', 4, 0.7);
            $objects[] = $this->circle('card-hatch-bot', $w * 0.79, $h, $unit * 0.22, '#E2E8F0', '#94A3B8', 4, 0.7);
            $objects[] = $this->circle('card-badge-ring', $w * 0.79, $h * 0.5, $unit * 0.25, '#FFFFFF', '#64748B', 7, 1.0);
            $objects[] = $this->circle('card-badge-inner', $w * 0.79, $h * 0.5, $unit * 0.22, '#F8FAFC', '#CBD5E1', 2, 1.0);
            $objects = array_merge($objects, $this->mark($c, $w * 0.79, $h * 0.45, $unit * 0.21, '#0F172A'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.79, $h * 0.59, $brandBlock['size'] * 0.68, '#0F172A', $cBody, 700, 80, 'center');
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $h * 0.28, $nameBlock['size'] * 0.88, '#0F172A', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m, $h * 0.43, $unit * 0.042, '#475569', $cBody, 600, 80);
            $objects[] = $this->rule('card-rule', $m, $h * 0.54, $w * 0.34, '#0F172A', 3, 0.8);
            $objects[] = $this->text('card-c1', 'new-text', $email, $m, $h * 0.62, $unit * 0.038, '#334155', $cBody, 500, 20);
            $objects[] = $this->text('card-c2', 'new-text', $phone, $m, $h * 0.70, $unit * 0.038, '#334155', $cBody, 500, 20);
            $objects[] = $this->text('card-c3', 'new-text', $website, $m, $h * 0.78, $unit * 0.038, '#334155', $cBody, 500, 20);

            return $objects;
        }

        // 7. FOREST EDITORIAL — Deep forest green + vibrant lime (or themed dark)
        if ($style === 'forest-editorial') {
            $fBg = $th(0, '#113E0E');
            $fAcc = $th(1, '#84CC16');
            $fSub = $th(2, '#BEF264');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $fBg);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.35, $nameBlock['size'], $fAcc, $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m, $m * 1.35 + $nameBlock['size'] * 1.25, $unit * 0.056, $fSub, $cBody, 500, 30);
            $addContacts($m, $h * 0.56, $unit * 0.095, $unit * 0.044, '#E2E8F0', $fAcc);
            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.27, $fAcc));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.63, $brandBlock['size'] * 0.85, $fSub, $cBody, 700, 120, 'center');

            return $objects;
        }

        // 8. BLUSH ROSE — Soft editorial blush + vibrant coral (or themed light)
        if ($style === 'blush-rose') {
            $bSurf = $th(3, '#FFF1F2');
            $bAcc = $th(5, '#F43F5E');
            $bPri = $th(4, '#E11D48');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $bSurf);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.35, $nameBlock['size'], $bAcc, $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m, $m * 1.35 + $nameBlock['size'] * 1.25, $unit * 0.056, '#475569', $cBody, 500, 30);
            $addContacts($m, $h * 0.56, $unit * 0.095, $unit * 0.044, '#475569', $bAcc);
            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.27, $bAcc));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.63, $brandBlock['size'] * 0.85, $bPri, $cBody, 700, 120, 'center');

            return $objects;
        }

        // 9. STEPPED WHITE QR — Crisp white + deep navy stepped architectural header + QR
        if ($style === 'stepped-white-qr') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = [
                'id' => 'card-navy-step',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w / 2,
                'top' => $h / 2,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M 0 0 L %d 0 L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L %d %d L 0 %d Z',
                    (int) $w, (int) $w, (int) ($h * 0.25), (int) ($w * 0.92), (int) ($h * 0.25),
                    (int) ($w * 0.92), (int) ($h * 0.13), (int) ($w * 0.86), (int) ($h * 0.13),
                    (int) ($w * 0.86), (int) ($h * 0.06), (int) ($w * 0.14), (int) ($h * 0.06),
                    (int) ($w * 0.14), (int) ($h * 0.13), (int) ($w * 0.08), (int) ($h * 0.13),
                    (int) ($w * 0.08), (int) ($h * 0.25), (int) ($h * 0.25)
                ),
                'fill' => '#0F2C59',
            ];
            $stepNameSize = min($nameBlock['size'] * 0.9, $unit * 0.115);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $w / 2, $h * 0.135, $stepNameSize, '#0F2C59', $c, $hlWeight, $hlTrack, 'center');
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $w / 2, $h * 0.30, $unit * 0.042, '#475569', $cBody, 600, 120, 'center');
            $objects = array_merge($objects, $this->mark($c, $w * 0.37, $h * 0.53, $unit * 0.20, '#0F2C59'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.37, $h * 0.72, $brandBlock['size'] * 0.72, '#0F2C59', $cBody, 700, 100, 'center');
            $addQr('card-qr', $w * 0.63, $h * 0.59, $unit * 0.29, '#0F2C59');
            $objects[] = $this->text('card-bottom', 'new-text', $phone.'   ·   '.$email, $w / 2, $h * 0.86, $unit * 0.036, '#334155', $cBody, 600, 30, 'center');

            return $objects;
        }

        // 10. LOWPOLY QR — Faceted geometric triangle mosaic top-right + QR bottom-right
        if ($style === 'lowpoly-qr') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $shards = [
                ['#1E3A8A', sprintf('M %d 0 L %d 0 L %d %d Z', (int) ($w * 0.72), (int) ($w * 0.88), (int) ($w * 0.82), (int) ($h * 0.22))],
                ['#3B82F6', sprintf('M %d 0 L %d 0 L %d %d Z', (int) ($w * 0.88), (int) $w, (int) ($w * 0.94), (int) ($h * 0.26))],
                ['#93C5FD', sprintf('M %d %d L %d %d L %d %d Z', (int) ($w * 0.62), (int) ($h * 0.08), (int) ($w * 0.75), (int) ($h * 0.06), (int) ($w * 0.70), (int) ($h * 0.26))],
                ['#1D4ED8', sprintf('M %d %d L %d %d L %d %d Z', (int) ($w * 0.76), (int) ($h * 0.18), (int) ($w * 0.90), (int) ($h * 0.24), (int) ($w * 0.82), (int) ($h * 0.40))],
                ['#CBD5E1', sprintf('M %d %d L %d %d L %d %d Z', (int) ($w * 0.88), (int) ($h * 0.26), (int) $w, (int) ($h * 0.18), (int) $w, (int) ($h * 0.42))],
                ['#60A5FA', sprintf('M %d %d L %d %d L %d %d Z', (int) ($w * 0.66), (int) ($h * 0.24), (int) ($w * 0.76), (int) ($h * 0.30), (int) ($w * 0.70), (int) ($h * 0.42))],
            ];
            foreach ($shards as $idx => [$fill, $d]) {
                $objects[] = [
                    'id' => 'card-shard-'.$idx,
                    'role' => 'shape',
                    'type' => 'path',
                    'left' => $w / 2,
                    'top' => $h / 2,
                    'originX' => 'center',
                    'originY' => 'center',
                    'd' => $d,
                    'fill' => $fill,
                ];
            }
            $objects = array_merge($objects, $this->mark($c, $w * 0.14, $h * 0.22, $unit * 0.19, '#1E3A8A'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.14, $h * 0.35, $brandBlock['size'] * 0.68, '#1E3A8A', $cBody, 700, 80, 'center');
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $h * 0.47, $nameBlock['size'] * 0.86, '#1E3A8A', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m, $h * 0.61, $unit * 0.04, '#475569', $cBody, 600, 80);
            $objects[] = $this->text('card-c1', 'new-text', $email, $m, $h * 0.72, $unit * 0.036, '#334155', $cBody, 500, 20);
            $objects[] = $this->text('card-c2', 'new-text', $phone.'  ·  '.$website, $m, $h * 0.80, $unit * 0.036, '#334155', $cBody, 500, 20);
            $addQr('card-qr', $w * 0.84, $h * 0.70, $unit * 0.32, '#1E3A8A');

            return $objects;
        }

        // 11. DIAGONAL QR — Bold cobalt diagonal slash + striped edge + QR right
        if ($style === 'diagonal-qr') {
            $dSlash = $th(4, '#2E3B9E');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = [
                'id' => 'card-cobalt-slash',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w / 2,
                'top' => $h / 2,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M 0 0 L %d 0 L %d %d L 0 %d Z', (int) ($w * 0.60), (int) ($w * 0.34), (int) $h, (int) $h),
                'fill' => $dSlash,
            ];
            $objects[] = [
                'id' => 'card-cobalt-accent',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w / 2,
                'top' => $h / 2,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M %d 0 L %d 0 L %d %d L %d %d Z',
                    (int) ($w * 0.63), (int) ($w * 0.655), (int) ($w * 0.395), (int) $h, (int) ($w * 0.37), (int) $h
                ),
                'fill' => $dSlash,
            ];
            $stacked = $this->block($personName, $w * 0.38, $unit * 0.105 * $hlScale, 2, $c, $hlWeight, $hlTrack, $hlCase !== 'title');
            $objects[] = $this->text('card-name', 'primary-text', $stacked['text'], $m * 0.9, $m * 1.1, $stacked['size'], '#FFFFFF', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m * 0.9, $h * 0.47, $unit * 0.042, '#E2E8F0', $cBody, 600, 80);
            $objects[] = $this->text('card-c1', 'new-text', $email, $m * 0.9, $h * 0.67, $unit * 0.036, '#F1F5F9', $cBody, 500, 20);
            $objects[] = $this->text('card-c2', 'new-text', $phone, $m * 0.9, $h * 0.75, $unit * 0.036, '#F1F5F9', $cBody, 500, 20);
            $objects = array_merge($objects, $this->mark($c, $w * 0.80, $h * 0.27, $unit * 0.23, $dSlash));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.80, $h * 0.42, $brandBlock['size'] * 0.74, $dSlash, $cBody, 700, 80, 'center');
            $addQr('card-qr', $w * 0.80, $h * 0.72, $unit * 0.30, $dSlash);

            return $objects;
        }

        // 12. OLIVE SAGE — Matte olive-charcoal + crisp white (or themed dark)
        if ($style === 'olive-sage') {
            $oBg = $th(0, '#3D4C3A');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $oBg);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.35, $nameBlock['size'], '#FFFFFF', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m, $m * 1.35 + $nameBlock['size'] * 1.25, $unit * 0.056, '#D6D3CD', $cBody, 500, 30);
            $addContacts($m, $h * 0.56, $unit * 0.095, $unit * 0.044, '#E7E5E4', '#FFFFFF');
            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.27, '#FFFFFF'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.63, $brandBlock['size'] * 0.85, '#F5F5F4', $cBody, 700, 120, 'center');

            return $objects;
        }

        // 13. SAND EDITORIAL — Warm parchment + deep olive ink (or themed light)
        if ($style === 'sand-editorial') {
            $sSurf = $th(3, '#EFE8D8');
            $sPri = $th(4, '#5B6E1D');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, $sSurf);
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.35, $nameBlock['size'], $sPri, $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m, $m * 1.35 + $nameBlock['size'] * 1.25, $unit * 0.056, '#4B5563', $cBody, 500, 30);
            $addContacts($m, $h * 0.56, $unit * 0.095, $unit * 0.044, '#3F3F46', $sPri);
            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.27, $sPri));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.63, $brandBlock['size'] * 0.85, $sPri, $cBody, 700, 120, 'center');

            return $objects;
        }

        // 14. SKY ARCS — Ice blue + overlapping circle arcs + structured label/value contact grid
        if ($style === 'sky-arcs') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#EDF7FF');
            $objects[] = $this->circle('card-arc-bl1', $w * 0.04, $h * 0.96, $unit * 0.36, '#93B7DB', null, 0, 0.42);
            $objects[] = $this->circle('card-arc-bl2', $w * 0.04, $h * 0.96, $unit * 0.46, 'none', '#4A77A8', 6, 0.75);
            $objects[] = $this->circle('card-arc-tr', $w * 0.92, $h * 0.14, $unit * 0.38, '#FFFFFF', '#4A77A8', 8, 0.95);
            $objects = array_merge($objects, $this->mark($c, $w * 0.86, $h * 0.20, $unit * 0.20, '#3B6EA5'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.86, $h * 0.33, $brandBlock['size'] * 0.65, '#3B6EA5', $cBody, 700, 80, 'center');
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.1, $nameBlock['size'] * 0.96, '#3B6EA5', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m, $m * 1.1 + $nameBlock['size'] * 1.18, $unit * 0.048, '#64748B', $cBody, 600, 80);

            $rows = [['PHONE', $phone], ['EMAIL', $email], ['WEBSITE', $website]];
            foreach ($rows as $idx => [$lbl, $val]) {
                $ry = $h * 0.57 + $idx * $unit * 0.105;
                $objects[] = $this->text('card-lbl-'.$idx, 'new-text', $lbl, $w * 0.33, $ry, $unit * 0.04, '#1E3A5F', $cBody, 700, 60);
                $objects[] = $this->text('card-val-'.$idx, 'new-text', $val, $w * 0.51, $ry, $unit * 0.04, '#475569', $cBody, 500, 20);
            }

            return $objects;
        }

        // 15. BRUSH NOIR — Pitch black + dramatic white brush stroke + script name + logo right
        if ($style === 'brush-noir') {
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#0A0D14');
            $objects[] = [
                'id' => 'card-brush-stroke',
                'role' => 'shape',
                'type' => 'path',
                'left' => $w * 0.46,
                'top' => $h * 0.44,
                'originX' => 'center',
                'originY' => 'center',
                'd' => sprintf('M %d %d Q %d %d %d %d Q %d %d %d %d Q %d %d %d %d Q %d %d %d %d Z',
                    (int) ($w * 0.05), (int) ($h * 0.42),
                    (int) ($w * 0.28), (int) ($h * 0.32),
                    (int) ($w * 0.58), (int) ($h * 0.45),
                    (int) ($w * 0.88), (int) ($h * 0.34),
                    (int) ($w * 0.94), (int) ($h * 0.48),
                    (int) ($w * 0.62), (int) ($h * 0.56),
                    (int) ($w * 0.32), (int) ($h * 0.52),
                    (int) ($w * 0.08), (int) ($h * 0.58),
                    (int) ($w * 0.05), (int) ($h * 0.42)
                ),
                'fill' => '#FFFFFF',
                'opacity' => 0.96,
            ];
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m * 1.1, $h * 0.24, $nameBlock['size'] * 1.1, '#FFFFFF', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', $personRole, $m * 1.1, $h * 0.58, $unit * 0.05, '#94A3B8', $cBody, 500, 40);
            $objects[] = $this->text('card-c1', 'new-text', $phone.'   ·   '.$email, $m * 1.1, $h * 0.76, $unit * 0.038, '#CBD5E1', $cBody, 500, 30);
            $logoCx = $w * 0.82;
            $objects[] = $this->circle('card-logo-badge', $logoCx, $h * 0.42, $unit * 0.22, '#1E293B', '#334155', 2, 0.9);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.38, $unit * 0.20, '#F8FAFC'));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.56, $brandBlock['size'] * 0.72, '#F1F5F9', $cBody, 700, 80, 'center');

            return $objects;
        }

        // 16. BRACKET FRAME — Crisp white + navy corner brackets + centered title & QR + horizontal contact pills
        if ($style === 'bracket-frame') {
            $bCol = $th(4, '#0F2C59');
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $pad = $unit * 0.08;
            $arm = $unit * 0.16;
            $sw = 3.5;
            $objects[] = ['id' => 'card-brk-tl', 'role' => 'shape', 'type' => 'path', 'left' => $pad + $arm/2, 'top' => $pad + $arm/2, 'originX' => 'center', 'originY' => 'center', 'd' => sprintf('M %d %d L %d %d L %d %d', (int)$pad, (int)($pad + $arm), (int)$pad, (int)$pad, (int)($pad + $arm), (int)$pad), 'fill' => 'none', 'stroke' => $bCol, 'strokeWidth' => $sw];
            $objects[] = ['id' => 'card-brk-tr', 'role' => 'shape', 'type' => 'path', 'left' => $w - $pad - $arm/2, 'top' => $pad + $arm/2, 'originX' => 'center', 'originY' => 'center', 'd' => sprintf('M %d %d L %d %d L %d %d', (int)($w - $pad - $arm), (int)$pad, (int)($w - $pad), (int)$pad, (int)($w - $pad), (int)($pad + $arm)), 'fill' => 'none', 'stroke' => $bCol, 'strokeWidth' => $sw];
            $objects[] = ['id' => 'card-brk-bl', 'role' => 'shape', 'type' => 'path', 'left' => $pad + $arm/2, 'top' => $h - $pad - $arm/2, 'originX' => 'center', 'originY' => 'center', 'd' => sprintf('M %d %d L %d %d L %d %d', (int)$pad, (int)($h - $pad - $arm), (int)$pad, (int)($h - $pad), (int)($pad + $arm), (int)($h - $pad)), 'fill' => 'none', 'stroke' => $bCol, 'strokeWidth' => $sw];
            $objects[] = ['id' => 'card-brk-br', 'role' => 'shape', 'type' => 'path', 'left' => $w - $pad - $arm/2, 'top' => $h - $pad - $arm/2, 'originX' => 'center', 'originY' => 'center', 'd' => sprintf('M %d %d L %d %d L %d %d', (int)($w - $pad - $arm), (int)($h - $pad), (int)($w - $pad), (int)($h - $pad), (int)($w - $pad), (int)($h - $pad - $arm)), 'fill' => 'none', 'stroke' => $bCol, 'strokeWidth' => $sw];

            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $w / 2, $h * 0.16, $nameBlock['size'] * 0.94, $bCol, $c, $hlWeight, $hlTrack, 'center');
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $w / 2, $h * 0.31, $unit * 0.044, '#64748B', $cBody, 600, 110, 'center');
            $objects = array_merge($objects, $this->mark($c, $w * 0.36, $h * 0.54, $unit * 0.22, $bCol));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.36, $h * 0.72, $brandBlock['size'] * 0.72, $bCol, $cBody, 700, 90, 'center');
            $addQr('card-qr', $w * 0.64, $h * 0.58, $unit * 0.30, $bCol);
            $objects[] = $this->text('card-bottom', 'new-text', $phone.'   ·   '.$email.'   ·   '.$website, $w / 2, $h * 0.86, $unit * 0.034, '#475569', $cBody, 500, 40, 'center');

            return $objects;
        }

        // 17. SPLIT CONTRAST — Two-tone vertical split (40% dark / 60% light) + QR right
        if ($style === 'split-contrast') {
            $splitBg = $th(0, '#0F172A');
            $splitAcc = $th(1, '#38BDF8');
            $splitW = $w * 0.40;
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = $this->rect('card-split-pane', 0, 0, $splitW, $h, $splitBg);
            $stacked = $this->block($personName, $splitW * 0.82, $unit * 0.11 * $hlScale, 2, $c, $hlWeight, $hlTrack, $hlCase !== 'title');
            $objects[] = $this->text('card-name', 'primary-text', $stacked['text'], $m * 0.9, $m * 1.2, $stacked['size'], '#FFFFFF', $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m * 0.9, $h * 0.48, $unit * 0.042, $splitAcc, $cBody, 600, 60);
            $objects[] = $this->text('card-c1', 'new-text', $phone, $m * 0.9, $h * 0.68, $unit * 0.036, '#E2E8F0', $cBody, 500, 20);
            $objects[] = $this->text('card-c2', 'new-text', $email, $m * 0.9, $h * 0.77, $unit * 0.036, '#CBD5E1', $cBody, 500, 20);

            $rightCx = ($splitW + $w) / 2;
            $objects = array_merge($objects, $this->mark($c, $rightCx - $unit * 0.16, $h * 0.48, $unit * 0.24, $splitBg));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $rightCx - $unit * 0.16, $h * 0.66, $brandBlock['size'] * 0.74, $splitBg, $cBody, 700, 80, 'center');
            $addQr('card-qr', $rightCx + $unit * 0.18, $h * 0.50, $unit * 0.32, $splitBg);

            return $objects;
        }

        // 18. DOT MATRIX — Tech dot grid top-right + wide typography + crisp QR & logo right
        if ($style === 'dot-matrix') {
            $mCol = $th(4, '#0F172A');
            $mDot = '#CBD5E1';
            $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#F8FAFC');
            for ($r = 0; $r < 5; $r++) {
                for ($col = 0; $col < 6; $col++) {
                    $objects[] = $this->circle('card-dot-'.$r.'-'.$col, $w * 0.70 + $col * 16, $h * 0.10 + $r * 16, 2.5, $mDot);
                }
            }
            $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $h * 0.25, $nameBlock['size'] * 0.92, $mCol, $c, $hlWeight, $hlTrack);
            $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m, $h * 0.40, $unit * 0.044, '#475569', $cBody, 600, 90);
            $objects[] = $this->rule('card-accent-line', $m, $h * 0.50, $w * 0.15, '#2563EB', 3, 1.0);
            $addContacts($m, $h * 0.60, $unit * 0.095, $unit * 0.042, '#334155', '#2563EB');
            $logoCx = $w * 0.82;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.34, $unit * 0.22, $mCol));
            $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $logoCx, $h * 0.48, $brandBlock['size'] * 0.70, $mCol, $cBody, 700, 80, 'center');
            $addQr('card-qr', $logoCx, $h * 0.74, $unit * 0.28, $mCol);

            return $objects;
        }

        // Fallback: Sky Arcs
        $objects[] = $this->rect('card-bg', 0, 0, $w, $h, '#EDF7FF');
        $objects[] = $this->circle('card-arc-bl1', $w * 0.04, $h * 0.96, $unit * 0.36, '#93B7DB', null, 0, 0.42);
        $objects[] = $this->circle('card-arc-bl2', $w * 0.04, $h * 0.96, $unit * 0.46, 'none', '#4A77A8', 6, 0.75);
        $objects[] = $this->circle('card-arc-tr', $w * 0.92, $h * 0.14, $unit * 0.38, '#FFFFFF', '#4A77A8', 8, 0.95);
        $objects = array_merge($objects, $this->mark($c, $w * 0.86, $h * 0.20, $unit * 0.20, '#3B6EA5'));
        $objects[] = $this->text('card-brand', 'new-text', $brandBlock['text'], $w * 0.86, $h * 0.33, $brandBlock['size'] * 0.65, '#3B6EA5', $cBody, 700, 80, 'center');
        $objects[] = $this->text('card-name', 'primary-text', $nameBlock['text'], $m, $m * 1.1, $nameBlock['size'] * 0.96, '#3B6EA5', $c, $hlWeight, $hlTrack);
        $objects[] = $this->text('card-role', 'new-text', mb_strtoupper($personRole), $m, $m * 1.1 + $nameBlock['size'] * 1.18, $unit * 0.048, '#64748B', $cBody, 600, 80);

        $rows = [['PHONE', $phone], ['EMAIL', $email], ['WEBSITE', $website]];
        foreach ($rows as $idx => [$lbl, $val]) {
            $ry = $h * 0.57 + $idx * $unit * 0.105;
            $objects[] = $this->text('card-lbl-'.$idx, 'new-text', $lbl, $w * 0.33, $ry, $unit * 0.04, '#1E3A5F', $cBody, 700, 60);
            $objects[] = $this->text('card-val-'.$idx, 'new-text', $val, $w * 0.51, $ry, $unit * 0.04, '#475569', $cBody, 500, 20);
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutDocument(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.09;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'resume') {
            $side = $w * 0.33;
            $objects[] = $this->rect('resume-side', 0, 0, $side, $h, $c['colors'][0]);
            $objects = array_merge($objects, $this->mark($c, $side / 2, $h * 0.14, $unit * 0.16, $c['accent']));
            $name = $this->block($c['headline'], $side - 2 * $m * 0.6, $unit * 0.06, 2, $c, 800, 20, true);
            $objects[] = $this->text('resume-name', 'primary-text', $name['text'], $side / 2, $h * 0.24, $name['size'], '#FFFFFF', $c, 800, 20, 'center');
            $objects[] = $this->text('resume-role', 'new-text', $c['role'], $side / 2, $h * 0.24 + $name['size'] * 1.12 * max(1, count($name['lines'])) + $unit * 0.02, $unit * 0.028, $c['accent'], $c, 600, 160, 'center');
            $contact = array_filter([$c['phone'], $c['email'], $c['website'], $c['address']]);
            $y = $h * 0.48;
            foreach ($contact as $index => $line) {
                $contactBlock = $this->block($line, $side - 2 * $m * 0.5, $unit * 0.024, 2, $c, 400, 30);
                $objects[] = $this->text('resume-contact-'.$index, 'new-text', $contactBlock['text'], $side / 2, $y + $index * $unit * 0.075, $contactBlock['size'], '#E5E7EB', $c, 400, 30, 'center');
            }
            $bodyX = $side + $m * 0.9;
            $bodyW = $w - $bodyX - $m;
            $objects[] = $this->text('resume-section', 'new-text', 'PROFILE', $bodyX, $m * 0.9, $unit * 0.024, $c['accent'], $c, 700, 240);
            $body = $this->block($c['body'], $bodyW, $unit * 0.028, 7, $c, 400, 20);
            $objects[] = $this->text('resume-body', 'new-text', $body['text'], $bodyX, $m * 0.9 + $unit * 0.05, $body['size'], $c['muted'], $c, 400, 20);
            $objects[] = $this->rule('resume-rule', $bodyX, $h * 0.48, $bodyW, $c['muted'], $unit * 0.002, 0.4);
            $objects[] = $this->text('resume-experience', 'new-text', 'EXPERIENCE', $bodyX, $h * 0.52, $unit * 0.024, $c['accent'], $c, 700, 240);
            for ($i = 0; $i < 3; $i++) {
                $y = $h * 0.58 + $i * $unit * 0.13;
                $objects[] = $this->text('resume-job-'.$i, 'new-text', ['Senior Designer', 'Product Designer', 'Visual Designer'][$i], $bodyX, $y, $unit * 0.032, $c['ink'], $c, 700, 0);
                $objects[] = $this->text('resume-years-'.$i, 'new-text', ['2023 — Now', '2020 — 2023', '2018 — 2020'][$i], $bodyX + $bodyW, $y + $unit * 0.005, $unit * 0.022, $c['muted'], $c, 400, 60, 'center');
                $objects[] = $this->rule('resume-line-'.$i, $bodyX, $y + $unit * 0.05, $bodyW, $c['muted'], $unit * 0.0015, 0.3);
            }

            return $objects;
        }

        if ($variant === 'brochure') {
            $panel = $w * 0.4;
            $objects[] = $this->rect('brochure-panel', 0, 0, $panel, $h, $c['accent']);
            $objects = array_merge($objects, $this->mark($c, $panel / 2, $h * 0.22, $unit * 0.2, $c['ink']));
            $title = $this->block($c['headline'], $panel - 2 * $m * 0.8, $unit * 0.07, 3, $c, 800, 10, true);
            $objects[] = $this->text('brochure-title', 'primary-text', $title['text'], $panel / 2, $h * 0.38, $title['size'], Color::readableInk($c['accent']), $c, 800, 10, 'center');
            $rightX = $panel + $m;
            $rightW = $w - $rightX - $m;
            $objects[] = $this->text('brochure-brand', 'new-text', $c['brand'], $rightX, $m * 0.8, $unit * 0.035, $c['ink'], $c, 700, 120, 'left');
            $body = $this->block($c['body'], $rightW, $unit * 0.032, 10, $c, 400, 20);
            $objects[] = $this->text('brochure-body', 'new-text', $body['text'], $rightX, $h * 0.24, $body['size'], $c['muted'], $c, 400, 20);
            $objects[] = $this->rule('brochure-rule', $rightX, $h * 0.8, $rightW * 0.3, $c['accent'], $unit * 0.005);
            $objects[] = $this->text('brochure-contact', 'new-text', implode('  ·  ', array_filter([$c['phone'], $c['email']])), $rightX, $h * 0.84, $unit * 0.024, $c['ink'], $c, 500, 40);

            return $objects;
        }

        if ($variant === 'portfolio') {
            $objects[] = $this->text('portfolio-name', 'primary-text', $c['brand'], $m, $m * 0.8, $unit * 0.045, $c['ink'], $c, 800, 60, 'left');
            $objects[] = $this->text('portfolio-title', 'new-text', $c['headline'], $w - $m, $m * 0.8, $unit * 0.028, $c['muted'], $c, 500, 120, 'center');
            $objects[] = $this->rule('portfolio-rule', $m, $m * 0.8 + $unit * 0.07, $w - 2 * $m, $c['accent'], $unit * 0.004);
            $tileW = ($w - 2 * $m - $unit * 0.03) / 2;
            $tileH = $h * 0.19;
            for ($i = 0; $i < 6; $i++) {
                $col = $i % 2;
                $row = intdiv($i, 2);
                $x = $m + $col * ($tileW + $unit * 0.03);
                $y = $h * 0.22 + $row * ($tileH + $unit * 0.03);
                $objects[] = $this->rect('portfolio-tile-'.$i, $x, $y, $tileW, $tileH, $i % 3 === 0 ? $c['accent'] : $c['colors'][$i % 3], $unit * 0.012, 0.9);
                $objects[] = $this->text('portfolio-tile-label-'.$i, 'shape', 'Project 0'.($i + 1), $x + $unit * 0.02, $y + $tileH - $unit * 0.035, $unit * 0.022, Color::readableInk($i % 3 === 0 ? $c['accent'] : $c['colors'][$i % 3]), $c, 600, 60);
            }
            $objects[] = $this->text('portfolio-contact', 'new-text', implode('  ·  ', array_filter([$c['email'], $c['website']])), $m, $h - $m * 0.8, $unit * 0.024, $c['muted'], $c, 500, 80);

            return $objects;
        }

        if ($variant === 'panel') {
            $objects[] = $this->rect('panel-head', 0, 0, $w, $h * 0.18, $c['accent']);
            $objects[] = $this->text('panel-title', 'primary-text', $c['headline'], $w / 2, $h * 0.06, $unit * 0.07, Color::readableInk($c['accent']), $c, 700, 20, 'center');
            $body = $this->block($c['body'], $w - 2 * $m * 0.6, $unit * 0.045, 8, $c, 400, 10);
            $objects[] = $this->text('panel-body', 'new-text', $body['text'], $w / 2, $h * 0.26, $body['size'], $c['ink'], $c, 400, 10, 'center');
            $objects[] = $this->text('panel-brand', 'new-text', $c['brand'], $w / 2, $h * 0.9, $unit * 0.038, $c['accent'], $c, 700, 120, 'center');

            return $objects;
        }

        // Letterhead, guidelines, profile, proposal, ebook.
        $markSize = $unit * 0.08;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
        $objects[] = $this->text('doc-brand', 'new-text', $c['brand'], $m + $markSize + $unit * 0.02, $m + $markSize / 2 - $unit * 0.014, $unit * 0.028, $c['ink'], $c, 700, 60);
        $objects[] = $this->text('doc-website', 'new-text', $c['website'], $w - $m, $m + $markSize / 2 - $unit * 0.012, $unit * 0.022, $c['muted'], $c, 500, 40, 'center');
        $objects[] = $this->rule('doc-rule', $m, $m + $markSize + $unit * 0.035, $w - 2 * $m, $c['accent'], $unit * 0.004);

        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.065, 2, $c, 800, 10, true);
        $objects[] = $this->text('doc-title', 'primary-text', $title['text'], $m, $h * 0.2, $title['size'], $c['ink'], $c, 800, 10);
        $y = $h * 0.2 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.04;
        $bodyW = $w - 2 * $m;
        $bodySize = $unit * 0.026;
        $body = $this->paragraph($c['body'], $bodyW, $bodySize, $c, 400, 15);
        $maxLines = $variant === 'ebook' ? 22 : 14;
        $bodyText = implode("\n", array_slice($body, 0, $maxLines));
        $objects[] = $this->text('doc-body', 'new-text', $bodyText, $m, $y, $bodySize, $c['muted'], $c, 400, 15);

        if ($variant === 'guidelines') {
            $swatchY = $h * 0.62;
            $swatch = $unit * 0.075;
            foreach ($c['colors'] as $index => $color) {
                $x = $m + $index * ($swatch + $unit * 0.02);
                $objects[] = $this->rect('guide-swatch-'.$index, $x, $swatchY, $swatch, $swatch, $color, $unit * 0.01);
                $objects[] = $this->text('guide-hex-'.$index, 'shape', strtoupper($color), $x, $swatchY + $swatch + $unit * 0.01, $unit * 0.016, $c['muted'], $c, 500, 40);
            }
            $objects[] = $this->text('guide-type', 'primary-text', 'Aa', $m, $h * 0.75, $unit * 0.12, $c['ink'], $c, $c['weight'], 0);
            $objects[] = $this->text('guide-type-label', 'new-text', $c['font'].' · Headings and body', $m + $unit * 0.16, $h * 0.79, $unit * 0.022, $c['muted'], $c, 400, 20);
        }

        if ($variant === 'profile') {
            $stats = ['12+' => 'Years', '40' => 'Clients', '98%' => 'Retention'];
            $boxW = ($bodyW - 2 * $unit * 0.02) / 3;
            $i = 0;
            foreach ($stats as $value => $label) {
                $x = $m + $i * ($boxW + $unit * 0.02);
                $objects[] = $this->rect('profile-stat-'.$i, $x, $h * 0.68, $boxW, $unit * 0.13, $c['support'], $unit * 0.012, 0.35);
                $objects[] = $this->text('profile-stat-value-'.$i, 'new-text', $value, $x + $unit * 0.02, $h * 0.7, $unit * 0.05, $c['ink'], $c, 800, 0);
                $objects[] = $this->text('profile-stat-label-'.$i, 'new-text', $label, $x + $unit * 0.02, $h * 0.7 + $unit * 0.06, $unit * 0.018, $c['muted'], $c, 500, 80);
                $i++;
            }
        }

        if ($variant === 'proposal') {
            $boxY = $h * 0.66;
            $objects[] = $this->rect('proposal-box', $m, $boxY, $bodyW, $unit * 0.2, $c['support'], $unit * 0.014, 0.3);
            $objects[] = $this->text('proposal-box-title', 'new-text', 'Project investment', $m + $unit * 0.025, $boxY + $unit * 0.03, $unit * 0.026, $c['ink'], $c, 700, 20);
            $objects[] = $this->text('proposal-box-price', 'primary-text', $c['price'], $m + $unit * 0.025, $boxY + $unit * 0.085, $unit * 0.06, $c['accent'], $c, 800, 0);
            $objects[] = $this->text('proposal-box-note', 'new-text', '50% to start · 50% on delivery', $m + $unit * 0.025, $boxY + $unit * 0.16, $unit * 0.018, $c['muted'], $c, 400, 40);
        }

        $objects[] = $this->rule('doc-footer-rule', $m, $h - $m * 1.3, $w - 2 * $m, $c['muted'], $unit * 0.002, 0.35);
        $footer = implode('  ·  ', array_filter([$c['address'], $c['phone'], $c['email']]));
        $objects[] = $this->text('doc-footer', 'new-text', $footer, $m, $h - $m * 1.1, $unit * 0.019, $c['muted'], $c, 400, 40);

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutInvoice(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.085;
        $objects = [];

        $markSize = $unit * 0.07;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
        $objects[] = $this->text('invoice-brand', 'new-text', $c['brand'], $m + $markSize + $unit * 0.02, $m + $markSize / 2 - $unit * 0.012, $unit * 0.026, $c['ink'], $c, 700, 40);
        $objects[] = $this->text('invoice-address', 'new-text', $c['address'], $m, $m + $markSize + $unit * 0.03, $unit * 0.02, $c['muted'], $c, 400, 20);

        $title = $this->block($c['headline'], $w * 0.4, $unit * 0.075, 1, $c, 800, 40, true);
        $objects[] = $this->text('invoice-title', 'primary-text', $title['text'], $w - $m, $m + $unit * 0.01, $title['size'], $c['accent'], $c, 800, 40, 'center');
        $objects[] = $this->text('invoice-number', 'new-text', 'INV-2026-042 · '.$c['date'], $w - $m, $m + $unit * 0.01 + $title['size'] * 1.2, $unit * 0.019, $c['muted'], $c, 400, 60, 'center');

        $objects[] = $this->rule('invoice-rule', $m, $h * 0.27, $w - 2 * $m, $c['ink'], $unit * 0.003);
        $objects[] = $this->text('invoice-bill-label', 'new-text', 'BILL TO', $m, $h * 0.3, $unit * 0.019, $c['accent'], $c, 700, 200);
        $objects[] = $this->text('invoice-bill', 'new-text', $c['recipient'], $m, $h * 0.33, $unit * 0.03, $c['ink'], $c, 700, 20);
        $objects[] = $this->text('invoice-bill-detail', 'new-text', $c['email'], $m, $h * 0.33 + $unit * 0.045, $unit * 0.021, $c['muted'], $c, 400, 20);

        $tableY = $h * 0.43;
        $objects[] = $this->rect('invoice-table-head', $m, $tableY, $w - 2 * $m, $unit * 0.045, $c['ink'], $unit * 0.006);
        $objects[] = $this->text('invoice-head-desc', 'shape', 'DESCRIPTION', $m + $unit * 0.015, $tableY + $unit * 0.013, $unit * 0.018, Color::readableInk($c['ink']), $c, 700, 200);
        $objects[] = $this->text('invoice-head-amount', 'shape', 'AMOUNT', $w - $m - $unit * 0.015, $tableY + $unit * 0.013, $unit * 0.018, Color::readableInk($c['ink']), $c, 700, 200, 'center');

        $rows = $this->rows($c['body'], 6);
        if ($rows === [] || ! str_contains($c['body'], '|')) {
            $rows = [['Brand identity', '1', '$1,800'], ['Website design', '1', '$2,400'], ['Support retainer', '3', '$150']];
        }

        $total = 0.0;
        $rowY = $tableY + $unit * 0.075;
        foreach ($rows as $index => $row) {
            $description = $row[0] ?? '';
            $qty = $row[1] ?? '1';
            $amount = $row[2] ?? ($row[1] ?? '');
            $numeric = (float) preg_replace('/[^0-9.]/', '', $amount);
            $quantity = max(1.0, (float) preg_replace('/[^0-9.]/', '', $qty));
            $total += $numeric * $quantity;

            $objects[] = $this->text('invoice-row-desc-'.$index, 'new-text', $description, $m + $unit * 0.015, $rowY, $unit * 0.024, $c['ink'], $c, 500, 20);
            $objects[] = $this->text('invoice-row-qty-'.$index, 'new-text', $qty, $w * 0.66, $rowY, $unit * 0.022, $c['muted'], $c, 400, 40);
            $objects[] = $this->text('invoice-row-amount-'.$index, 'new-text', $amount, $w - $m - $unit * 0.015, $rowY, $unit * 0.024, $c['ink'], $c, 600, 20, 'center');
            $objects[] = $this->rule('invoice-row-line-'.$index, $m, $rowY + $unit * 0.045, $w - 2 * $m, $c['muted'], $unit * 0.0015, 0.25);
            $rowY += $unit * 0.07;
        }

        if ($total <= 0) {
            $total = 4650;
        }

        $totalY = max($rowY + $unit * 0.03, $h * 0.7);
        $objects[] = $this->rect('invoice-total-box', $w * 0.56, $totalY, $w * 0.44 - $m, $unit * 0.16, $c['support'], $unit * 0.012, 0.35);
        $objects[] = $this->text('invoice-total-label', 'new-text', 'TOTAL DUE', $w * 0.58, $totalY + $unit * 0.025, $unit * 0.02, $c['muted'], $c, 700, 200);
        $objects[] = $this->text('invoice-total', 'primary-text', '$'.number_format($total, 0), $w - $m - $unit * 0.02, $totalY + $unit * 0.06, $unit * 0.055, $c['accent'], $c, 800, 0, 'center');

        $objects[] = $this->text('invoice-notes', 'new-text', 'Payment due within 14 days. Thank you for your business.', $m, $h - $m * 1.25, $unit * 0.019, $c['muted'], $c, 400, 20);
        $objects[] = $this->text('invoice-website', 'new-text', $c['website'], $m, $h - $m, $unit * 0.019, $c['ink'], $c, 600, 60);

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutMenu(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.085;
        $objects = [];

        $objects = array_merge($objects, $this->mark($c, $w / 2, $m + $unit * 0.06, $unit * 0.11));
        $brand = $this->block($c['brand'], $w - 2 * $m, $unit * 0.045, 1, $c, $c['weight'], 160, true);
        $objects[] = $this->text('menu-brand', 'new-text', $brand['text'], $w / 2, $m + $unit * 0.135, $brand['size'], $c['ink'], $c, $c['weight'], 160, 'center');
        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.085, 1, $c, 800, 30, true);
        $objects[] = $this->text('menu-title', 'primary-text', $title['text'], $w / 2, $h * 0.25, $title['size'], $c['accent'], $c, 800, 30, 'center');
        $objects[] = $this->rule('menu-rule', $w / 2 - $unit * 0.1, $h * 0.25 + $title['size'] * 1.2, $unit * 0.2, $c['ink'], $unit * 0.003);

        $rows = $this->rows($c['items'], 7);
        $rowY = $h * 0.36;
        $rowGap = min($unit * 0.075, ($h * 0.4) / max(1, count($rows)));
        foreach ($rows as $index => $row) {
            $name = $row[0] ?? '';
            $price = $row[1] ?? '';
            $objects[] = $this->text('menu-item-'.$index, 'new-text', $name, $m, $rowY, $unit * 0.03, $c['ink'], $c, 600, 20);
            $nameWidth = $this->lineWidth($name, $unit * 0.03, $c, 600, 20);
            $priceWidth = $price !== '' ? $this->lineWidth($price, $unit * 0.03, $c, 600, 20) : 0;
            if ($price !== '') {
                $objects[] = $this->rule('menu-dots-'.$index, $m + $nameWidth + $unit * 0.015, $rowY + $unit * 0.022, max(8, $w - 2 * $m - $nameWidth - $priceWidth - $unit * 0.03), $c['muted'], $unit * 0.0015, 0.4);
                $objects[] = $this->text('menu-price-'.$index, 'new-text', $price, $w - $m, $rowY, $unit * 0.03, $c['accent'], $c, 700, 20, 'center');
            }
            $objects[] = $this->text('menu-desc-'.$index, 'new-text', 'Seasonal · house made', $m, $rowY + $unit * 0.036, $unit * 0.019, $c['muted'], $c, 400, 30);
            $rowY += $rowGap;
        }

        $objects[] = $this->rule('menu-footer-rule', $m, $h - $m * 1.45, $w - 2 * $m, $c['muted'], $unit * 0.002, 0.35);
        $objects[] = $this->text('menu-address', 'new-text', $c['address'], $w / 2, $h - $m * 1.2, $unit * 0.021, $c['muted'], $c, 400, 60, 'center');
        $objects[] = $this->text('menu-phone', 'new-text', $c['phone'].'  ·  '.$c['website'], $w / 2, $h - $m * 0.95, $unit * 0.021, $c['ink'], $c, 600, 60, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutLabel(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.09;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'box') {
            $objects[] = $this->rect('box-band', 0, 0, $w, $h * 0.32, $c['accent']);
            $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.16, $unit * 0.14, Color::readableInk($c['accent'])));
            $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.09, 2, $c, 800, 10, true);
            $objects[] = $this->text('box-title', 'primary-text', $title['text'], $w / 2, $h * 0.42, $title['size'], $c['ink'], $c, 800, 10, 'center');
            $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.03, 1, $c, 600, 200, true);
            $objects[] = $this->text('box-sub', 'new-text', $sub['text'], $w / 2, $h * 0.42 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.02, $sub['size'], $c['accent'], $c, 600, 200, 'center');
            $body = $this->block($c['body'], $w - 2 * $m, $unit * 0.024, 5, $c, 400, 20);
            $objects[] = $this->text('box-body', 'new-text', $body['text'], $w / 2, $h * 0.62, $body['size'], $c['muted'], $c, 400, 20, 'center');
            $objects[] = $this->text('box-brand', 'new-text', $c['brand'], $w / 2, $h - $m * 0.9, $unit * 0.026, $c['ink'], $c, 700, 160, 'center');

            return $objects;
        }

        if ($variant === 'packaging') {
            $objects[] = $this->circle('pack-ring', $w / 2, $h * 0.44, $unit * 0.3, 'none', $c['accent'], $unit * 0.005, 0.5);
            $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.38, $unit * 0.16));
            $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.085, 2, $c, 800, 20, true);
            $objects[] = $this->text('pack-title', 'primary-text', $title['text'], $w / 2, $h * 0.55, $title['size'], $c['ink'], $c, 800, 20, 'center');
            $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.028, 1, $c, 600, 220, true);
            $objects[] = $this->text('pack-sub', 'new-text', $sub['text'], $w / 2, $h * 0.55 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.02, $sub['size'], $c['accent'], $c, 600, 220, 'center');
            $body = $this->block($c['body'], $w * 0.66, $unit * 0.021, 4, $c, 400, 20);
            $objects[] = $this->text('pack-body', 'new-text', $body['text'], $w / 2, $h * 0.76, $body['size'], $c['muted'], $c, 400, 20, 'center');
            $objects[] = $this->text('pack-brand', 'new-text', $c['brand'], $w / 2, $h - $m * 0.8, $unit * 0.024, $c['ink'], $c, 700, 160, 'center');

            return $objects;
        }

        // Product label / product image.
        $objects[] = $this->rect('label-frame', $m * 0.7, $m * 0.7, $w - $m * 1.4, $h - $m * 1.4, 'none', $unit * 0.02);
        $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.25, $unit * 0.17));
        $brand = $this->block($c['brand'], $w - 2 * $m, $unit * 0.026, 1, $c, 700, 220, true);
        $objects[] = $this->text('label-brand', 'new-text', $brand['text'], $w / 2, $h * 0.4, $brand['size'], $c['accent'], $c, 700, 220, 'center');
        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.075, 2, $c, 800, 10, true);
        $objects[] = $this->text('label-title', 'primary-text', $title['text'], $w / 2, $h * 0.46, $title['size'], $c['ink'], $c, 800, 10, 'center');
        $objects[] = $this->text('label-price', 'new-text', $c['price'], $w / 2, $h * 0.68, $unit * 0.045, $c['accent'], $c, 800, 0, 'center');
        $body = $this->block($c['body'], $w * 0.7, $unit * 0.021, 3, $c, 400, 20);
        $objects[] = $this->text('label-body', 'new-text', $body['text'], $w / 2, $h * 0.78, $body['size'], $c['muted'], $c, 400, 20, 'center');
        $objects[] = $this->text('label-sub', 'new-text', $c['subhead'], $w / 2, $h * 0.93, $unit * 0.021, $c['ink'], $c, 600, 120, 'center');

        return $objects;
    }

    /**
     * Compose high-end corporate seals, rubber stamps, notary and archival marks.
     *
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutStamp(array $c): array
    {
        $w = (float) $c['width'];
        $h = (float) $c['height'];
        $cx = $w / 2;
        $cy = $h / 2;
        $style = (string) ($c['style'] ?? 'round-corporate-seal');
        $objects = [];

        $theme = $c['stamp_theme'] ?? null;
        if (is_array($theme) && count($theme) >= 4) {
            $paperBg = (string) $theme[0];
            $inkColor = (string) $theme[1];
            $darkInk = (string) $theme[2];
            $accentColor = (string) $theme[3];
            $washBg = (string) ($theme[4] ?? $paperBg);
        } else {
            $paperBg = (string) $c['bg'];
            $inkColor = (string) $c['accent'];
            $darkInk = (string) $c['ink'];
            $accentColor = (string) $c['support'];
            $washBg = $paperBg;
        }

        $brand = mb_strtoupper(trim((string) ($c['brand'] ?: 'CORPORATE SEAL')));
        $headline = mb_strtoupper(trim((string) ($c['headline'] ?: 'OFFICIAL')));
        $tagline = mb_strtoupper(trim((string) ($c['tagline'] ?: 'PVT. LTD.')));
        $location = mb_strtoupper(trim((string) ($c['location'] ?: 'OFFICIAL RECORD')));
        $date = trim((string) ($c['date'] ?: '23 NOV 2026'));
        $code = trim((string) ($c['code'] ?: 'REG. 2026-STMP'));

        $bodyType = array_merge($c, ['font' => $c['body_font'] ?? $c['font']]);

        // Paper base / subtle ink stamp wash
        if (! $c['transparent']) {
            $objects[] = $this->rect('stamp-paper', 0, 0, $w, $h, $paperBg);
        }

        switch ($style) {
            case 'round-authorised-signatory':
            case 'round-corporate-seal':
            default:
                // User Template #1: Canonical Double Concentric Round Stamp
                $objects[] = $this->circle('auth-r-out', $cx, $cy, 460, 'none', $inkColor, 4.5);
                $objects[] = $this->circle('auth-r-in', $cx, $cy, 310, 'none', $inkColor, 3.0);
                $topBrand = $brand !== '' ? $brand : 'YOUR COMPANY';
                $brandSize = $this->fitOne($topBrand, 700, 42, $c, 700, 120);
                $objects[] = $this->text('auth-top', 'primary-text', $topBrand, $cx, 140, $brandSize, $inkColor, $c, 700, 120, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $botText = (! empty($c['tagline']) && $c['tagline'] !== 'AUTHORIZED SIGNATORY')
                    ? $tagline
                    : 'PVT. LTD.';
                $botSize = $this->fitOne($botText, 600, 38, $c, 700, 140);
                $objects[] = $this->text('auth-bot', 'new-text', $botText, $cx, 890, $botSize, $inkColor, $bodyType, 700, 140, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('star-l', 'shape', '•', 155, $cy - 6, 44, $inkColor, $c, 900, 0, 'center');
                $objects[] = $this->text('star-r', 'shape', '★', 925, $cy - 10, 36, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('auth-l1', 'primary-text', 'Authorised', $cx, 470, 48, $inkColor, $c, 700, 20, 'center');
                $objects[] = $this->text('auth-l2', 'primary-text', 'Signatory', $cx, 535, 48, $inkColor, $c, 700, 20, 'center');
                break;

            case 'corporate-scalloped-seal':
                // User Template #2: Scalloped Rosette Corporate Seal
                $objects[] = $this->path('scallop-rim', $this->rosettePath($cx, $cy, 435, 470, 28), 'none', $inkColor, 3.5);
                $objects[] = $this->circle('scallop-c1', $cx, $cy, 428, 'none', $inkColor, 2.5);
                $objects[] = $this->circle('scallop-c2', $cx, $cy, 310, 'none', $inkColor, 2.5);
                $objects[] = $this->circle('scallop-c3', $cx, $cy, 300, 'none', $inkColor, 1.5);
                $objects[] = $this->text('scallop-top', 'primary-text', 'CORPORATE SEAL', $cx, 145, 36, $inkColor, $c, 700, 140, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 365,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('scallop-bot', 'new-text', 'EST. 2018', $cx, 885, 32, $inkColor, $bodyType, 700, 130, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 365,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('scallop-star-l', 'shape', '★', 172, $cy - 10, 30, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('scallop-star-r', 'shape', '★', 908, $cy - 10, 30, $inkColor, $c, 700, 0, 'center');
                $sealBrand = $brand !== '' ? $brand : 'YOUR COMPANY';
                $brandSize = $this->fitOne($sealBrand, 460, 40, $c, 800, 40);
                $objects[] = $this->text('scallop-brand', 'primary-text', $sealBrand, $cx, 475, $brandSize, $inkColor, $c, 800, 40, 'center');
                $objects[] = $this->text('scallop-name', 'new-text', 'NAME', $cx, 535, 28, $inkColor, $bodyType, 700, 60, 'center');
                break;

            case 'common-seal-pte-ltd':
                // User Template #3: Toothed Solid Ink Scalloped Cog Common Seal
                $objects[] = $this->path('cog-rim', $this->rosettePath($cx, $cy, 390, 470, 24), $inkColor, 'none', 0);
                $objects[] = $this->circle('cog-center-bg', $cx, $cy, 280, $paperBg, $inkColor, 3.5);
                $commonBrand = $brand !== '' ? $brand.' PTE. LTD.' : 'YOUR COMPANY PTE. LTD.';
                $objects[] = $this->text('cog-top', 'primary-text', $commonBrand, $cx, 180, 32, $paperBg, $c, 800, 100, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 335,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('cog-bot', 'new-text', '★   COMMON SEAL   ★', $cx, 850, 28, $paperBg, $bodyType, 700, 110, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 335,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('cog-l1', 'primary-text', 'COMMON', $cx, 455, 38, $inkColor, $c, 800, 40, 'center');
                $objects[] = $this->text('cog-l2', 'primary-text', 'SEAL', $cx, 505, 38, $inkColor, $c, 800, 40, 'center');
                $objects[] = $this->text('cog-uen', 'new-text', 'UEN: 123456789X', $cx, 560, 20, $inkColor, $bodyType, 700, 60, 'center');
                break;

            case 'rectangular-signatory-line':
                // User Template #4: Clean Rectangle with Underline & Authorised Signatory / Partner
                $objects[] = $this->rect('box-line-out', 90, 220, 900, 560, 'none', 0, 1.0, 0, $inkColor, 3.5);
                $rectBrand = $brand !== '' ? $brand : 'Stampjam Technologies';
                $brandSize = $this->fitOne($rectBrand, 800, 44, $c, 800, 30);
                $objects[] = $this->text('box-line-brand', 'primary-text', $rectBrand, 150, 320, $brandSize, $inkColor, $c, 800, 30, 'left');
                $objects[] = $this->rule('box-line-rule', 150, 560, 540, $inkColor, 2.5);
                $objects[] = $this->text('box-line-sign', 'new-text', 'Partner', 820, 565, 26, $inkColor, $bodyType, 700, 20, 'right');
                $objects[] = $this->text('box-line-sub', 'new-text', 'Authorised Signatory', 820, 610, 20, $inkColor, $bodyType, 600, 40, 'right');
                break;

            case 'head-institution-box':
                // User Template #5: Institutional Head 4-Line Stamp (Schools, Colleges, Govt)
                $objects[] = $this->rect('inst-box-out', 90, 180, 900, 640, 'none', 0, 1.0, 0, $inkColor, 3.5);
                $objects[] = $this->text('inst-head', 'primary-text', 'THE HEAD', $cx, 275, 42, $inkColor, $c, 900, 60, 'center');
                $schoolName = $brand !== '' ? $brand : 'DELHI PUBLIC SCHOOL';
                $schoolSize = $this->fitOne($schoolName, 820, 36, $c, 800, 40);
                $objects[] = $this->text('inst-school', 'primary-text', $schoolName, $cx, 375, $schoolSize, $inkColor, $c, 800, 40, 'center');
                $objects[] = $this->text('inst-date', 'new-text', '23 AUGUST 2026', $cx, 475, 24, $inkColor, $bodyType, 700, 50, 'center');
                $objects[] = $this->text('inst-sign', 'new-text', 'SIGN: ______________________________', $cx, 585, 26, $inkColor, $bodyType, 700, 30, 'center');
                break;

            case 'multi-line-address-box':
                // User Template #6: Commercial Business Multi-Line Shop/Trading Address Box
                $objects[] = $this->rect('addr-box-out', 100, 160, 880, 680, 'none', 0, 1.0, 0, $inkColor, 4.0);
                $objects[] = $this->rect('addr-box-in', 115, 175, 850, 650, 'none', 0, 0.85, 0, $inkColor, 1.5);
                $shopName = $brand !== '' ? $brand : 'India Mobile';
                $shopSize = $this->fitOne($shopName, 800, 52, $c, 900, 40);
                $objects[] = $this->text('addr-name', 'primary-text', $shopName, $cx, 270, $shopSize, $inkColor, $c, 900, 40, 'center');
                $objects[] = $this->text('addr-sub', 'primary-text', 'Mobile', $cx, 350, 34, $inkColor, $c, 800, 50, 'center');
                $objects[] = $this->text('addr-l1', 'new-text', '80-Collectory', $cx, 435, 26, $inkColor, $bodyType, 700, 40, 'center');
                $objects[] = $this->text('addr-l2', 'new-text', 'Old Rasta Colony, Palidas Road', $cx, 505, 24, $inkColor, $bodyType, 600, 30, 'center');
                $objects[] = $this->text('addr-tax', 'new-text', 'PAN: '.$code.'  ·  TEL: +977-1-4422000', $cx, 580, 22, $inkColor, $bodyType, 700, 40, 'center');
                break;

            case 'oval-signatory-stamp':
                // User Template #7: Executive Oval Signatory Stamp with Signature Line
                $objects[] = $this->oval('oval-sign-out', $cx, $cy, 450, 270, 'none', $inkColor, 3.5);
                $objects[] = $this->oval('oval-sign-in', $cx, $cy, 425, 245, 'none', $inkColor, 1.5);
                $ovalBrand = $brand !== '' ? $brand : 'Stampjam Technologies';
                $brandSize = $this->fitOne($ovalBrand, 620, 38, $c, 800, 30);
                $objects[] = $this->text('oval-brand', 'primary-text', $ovalBrand, $cx, 410, $brandSize, $inkColor, $c, 800, 30, 'center');
                $objects[] = $this->rule('oval-rule', 320, 510, 440, $inkColor, 2.0);
                $objects[] = $this->text('oval-sign', 'new-text', 'Director', $cx, 575, 28, $inkColor, $bodyType, 700, 30, 'center');
                break;

            case 'concentric-oval-corporate':
                // User Template #8: Concentric Double Oval Corporate Stamp
                $objects[] = $this->oval('oval-corp-out', $cx, $cy, 450, 270, 'none', $inkColor, 4.5);
                $objects[] = $this->oval('oval-corp-in', $cx, $cy, 420, 240, 'none', $inkColor, 2.0);
                $corpBrand = $brand !== '' ? $brand : 'Stampjam Technologies';
                $brandSize = $this->fitOne($corpBrand, 640, 42, $c, 900, 40);
                $objects[] = $this->text('oval-corp-b1', 'primary-text', $corpBrand, $cx, 405, $brandSize, $inkColor, $c, 900, 40, 'center');
                $objects[] = $this->text('oval-corp-b2', 'new-text', 'PRIVATE LIMITED', $cx, 475, 28, $inkColor, $bodyType, 800, 60, 'center');
                $objects[] = $this->text('oval-corp-reg', 'new-text', 'REGD. NO: '.$code.'  ·  '.$location, $cx, 535, 20, $inkColor, $bodyType, 600, 40, 'center');
                $objects[] = $this->text('oval-corp-stars', 'shape', '★   ★   ★', $cx, 585, 18, $inkColor, $c, 700, 60, 'center');
                break;

            case 'round-director-seal':
                $objects[] = $this->circle('dir-out', $cx, $cy, 460, 'none', $inkColor, 4.5);
                $objects[] = $this->circle('dir-in', $cx, $cy, 315, 'none', $inkColor, 3.0);
                $objects[] = $this->text('dir-top', 'primary-text', $brand, $cx, 140, 40, $inkColor, $c, 700, 110, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('dir-bot', 'new-text', 'DIRECTOR', $cx, 890, 36, $inkColor, $bodyType, 800, 150, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('dir-star-l', 'shape', '★', 155, $cy - 10, 34, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('dir-star-r', 'shape', '★', 925, $cy - 10, 34, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->rule('dir-sig-rule', 340, 500, 400, $inkColor, 2.5);
                $objects[] = $this->text('dir-sig-lbl', 'new-text', 'DIRECTOR', $cx, 560, 30, $inkColor, $bodyType, 800, 60, 'center');
                break;

            case 'round-proprietor-stamp':
                $objects[] = $this->circle('prop-out', $cx, $cy, 460, 'none', $inkColor, 4.0);
                $objects[] = $this->circle('prop-in', $cx, $cy, 320, 'none', $inkColor, 2.5);
                $objects[] = $this->text('prop-top', 'primary-text', $brand, $cx, 140, 40, $inkColor, $c, 700, 110, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('prop-bot', 'new-text', $location !== '' ? $location : 'COMMERCIAL RECORD', $cx, 890, 32, $inkColor, $bodyType, 700, 110, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 385,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('prop-star-l', 'shape', '•', 155, $cy - 6, 42, $inkColor, $c, 900, 0, 'center');
                $objects[] = $this->text('prop-star-r', 'shape', '•', 925, $cy - 6, 42, $inkColor, $c, 900, 0, 'center');
                $objects[] = $this->text('prop-for', 'new-text', 'FOR '.$brand, $cx, 455, 24, $inkColor, $bodyType, 700, 40, 'center');
                $objects[] = $this->text('prop-lbl', 'primary-text', 'PROPRIETOR', $cx, 525, 42, $inkColor, $c, 900, 50, 'center');
                break;

            case 'received-date-stamp':
                $objects[] = $this->rect('rec-box-out', 90, 160, 900, 680, 'none', 18, 1.0, 0, $inkColor, 5.5);
                $objects[] = $this->text('rec-title', 'primary-text', 'RECEIVED', $cx, 265, 64, $inkColor, $c, 900, 120, 'center');
                $objects[] = $this->rule('rec-r1', 120, 360, 840, $inkColor, 3.0);
                $objects[] = $this->rect('rec-dt-bg', 180, 400, 720, 120, 'none', 10, 1.0, 0, $inkColor, 2.0);
                $objects[] = $this->text('rec-dt', 'new-text', 'DATE: '.$date, $cx, 475, 44, $inkColor, $bodyType, 800, 60, 'center');
                $objects[] = $this->rule('rec-r2', 120, 560, 840, $inkColor, 3.0);
                $objects[] = $this->text('rec-foot', 'new-text', 'BY: ____________________  REF: #'.$code, $cx, 660, 26, $inkColor, $bodyType, 700, 40, 'center');
                $objects[] = $this->text('rec-org', 'new-text', $brand.' · OFFICIAL REGISTRY', $cx, 740, 20, $inkColor, $bodyType, 600, 60, 'center');
                break;

            case 'paid-finance-stamp':
                $angle = -6.0;
                $objects[] = $this->rect('paid-box-out', 100, 220, 880, 560, 'none', 14, 0.95, $angle, $inkColor, 12);
                $objects[] = $this->rect('paid-box-in', 125, 245, 830, 510, 'none', 10, 0.85, $angle, $inkColor, 3, [14, 10]);
                $objects[] = $this->text('paid-title', 'primary-text', 'PAID', $cx, 400, 110, $inkColor, $c, 900, 80, 'center', 1.0, ['angle' => $angle]);
                $objects[] = $this->text('paid-info', 'new-text', 'DATE: '.$date.'  ·  CHQ/REF: #'.$code, $cx, 530, 28, $inkColor, $bodyType, 700, 40, 'center', 1.0, ['angle' => $angle]);
                $objects[] = $this->text('paid-sub', 'new-text', $brand.' · ACCOUNTS DEPARTMENT', $cx, 610, 22, $inkColor, $bodyType, 800, 80, 'center', 1.0, ['angle' => $angle]);
                break;

            case 'approved-rubber-stamp':
                $angle = -8.0;
                $objects[] = $this->rect('appr-out', 90, 240, 900, 600, 'none', 20, 0.95, $angle, $inkColor, 14);
                $objects[] = $this->rect('appr-in', 115, 265, 850, 550, 'none', 14, 0.85, $angle, $inkColor, 4, [16, 12]);
                $objects[] = $this->text('appr-brand', 'new-text', $brand, $cx, 330, 36, $inkColor, $c, 800, 110, 'center', 1.0, ['angle' => $angle]);
                $headlineText = ! empty($c['headline']) ? $headline : 'APPROVED';
                $apprSize = $this->fitOne($headlineText, 760, 110, $c, 900, 80);
                $objects[] = $this->text('appr-title', 'primary-text', $headlineText, $cx, 440, $apprSize, $inkColor, $c, 900, 80, 'center', 1.0, ['angle' => $angle]);
                $objects[] = $this->text('appr-info', 'new-text', 'DATE: '.$date.'   ·   BY: '.$code, $cx, 610, 26, $inkColor, $bodyType, 700, 50, 'center', 1.0, ['angle' => $angle]);
                $objects[] = $this->text('appr-sub', 'new-text', 'OFFICIAL VERIFIED COPY  ·  STRICT COMPLIANCE', $cx, 680, 18, $inkColor, $bodyType, 600, 120, 'center', 1.0, ['angle' => $angle]);
                break;

            case 'qc-inspection-passed':
                $objects[] = $this->circle('qc-c1', $cx, $cy, 460, 'none', $inkColor, 8);
                $objects[] = $this->circle('qc-c2', $cx, $cy, 360, 'none', $inkColor, 3.5);
                $objects[] = $this->text('qc-top', 'primary-text', 'QUALITY CONTROL INSPECTION', $cx, 160, 30, $inkColor, $c, 800, 110, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 410,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('qc-bot', 'new-text', $brand, $cx, 875, 30, $inkColor, $bodyType, 800, 110, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 410,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('qc-star-l', 'shape', '★', 130, $cy - 10, 30, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('qc-star-r', 'shape', '★', 950, $cy - 10, 30, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('qc-title', 'primary-text', 'PASSED', $cx, 470, 72, $inkColor, $c, 900, 60, 'center');
                $objects[] = $this->text('qc-insp', 'new-text', 'INSPECTOR: #'.$code, $cx, 550, 26, $inkColor, $bodyType, 700, 40, 'center');
                $objects[] = $this->text('qc-dt', 'new-text', 'DATE: '.$date, $cx, 610, 22, $inkColor, $bodyType, 600, 40, 'center');
                break;

            case 'notary-triple-ring':
                $objects[] = $this->circle('notary-r1', $cx, $cy, 468, 'none', $inkColor, 8);
                $objects[] = $this->circle('notary-r2', $cx, $cy, 450, 'none', $inkColor, 2);
                $objects[] = $this->circle('notary-r3', $cx, $cy, 434, 'none', $inkColor, 2, 0.75, [8, 5]);
                $objects[] = $this->circle('notary-r4', $cx, $cy, 275, 'none', $inkColor, 4);
                $objects[] = $this->text('notary-top', 'primary-text', 'NOTARY PUBLIC · '.$brand, $cx, 150, 36, $inkColor, $c, 700, 100, 'center', 1.0, [
                    'effect' => 'arc-top',
                    'arcRadius' => 345,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('notary-bot', 'new-text', 'COMMISSION EXPIRES '.$date, $cx, 885, 30, $inkColor, $bodyType, 600, 90, 'center', 1.0, [
                    'effect' => 'arc-bottom',
                    'arcRadius' => 345,
                    'arcCenterX' => $cx,
                    'arcCenterY' => $cy,
                ]);
                $objects[] = $this->text('star-l', 'shape', '★', 185, $cy - 20, 32, $inkColor, $c, 700, 0, 'center');
                $objects[] = $this->text('star-r', 'shape', '★', 895, $cy - 20, 32, $inkColor, $c, 700, 0, 'center');
                $objects = array_merge($objects, $this->mark($c, $cx, 420, 190, $inkColor));
                $objects[] = $this->rule('notary-b1', 300, 550, 480, $inkColor, 2);
                $objects[] = $this->text('notary-title', 'primary-text', 'SEAL OF OFFICE', $cx, 565, 28, $inkColor, $c, 800, 120, 'center');
                $objects[] = $this->rule('notary-b2', 300, 615, 480, $inkColor, 2);
                $objects[] = $this->text('notary-code', 'new-text', 'REG. ID #'.$code, $cx, 635, 22, $inkColor, $bodyType, 600, 80, 'center');
                break;

            case 'certified-true-copy':
                $objects[] = $this->rect('cert-out', 90, 180, 900, 640, 'none', 0, 1.0, 0, $inkColor, 6.0);
                $objects[] = $this->rect('cert-in', 108, 198, 864, 604, 'none', 0, 0.85, 0, $inkColor, 2.0);
                $objects[] = $this->text('cert-title', 'primary-text', 'CERTIFIED TRUE COPY', $cx, 280, 52, $inkColor, $c, 900, 60, 'center');
                $objects[] = $this->text('cert-sub', 'new-text', 'OF THE ORIGINAL DOCUMENT', $cx, 350, 26, $inkColor, $bodyType, 700, 60, 'center');
                $objects[] = $this->text('cert-org', 'new-text', $brand.' · LEGAL VERIFICATION', $cx, 420, 24, $inkColor, $bodyType, 600, 40, 'center');
                $objects[] = $this->rule('cert-sig-line', 160, 540, 760, $inkColor, 2.0);
                $objects[] = $this->text('cert-sign', 'new-text', 'SIGNATURE: ____________________  DATE: '.$date, $cx, 620, 24, $inkColor, $bodyType, 700, 30, 'center');
                $objects[] = $this->text('cert-reg', 'new-text', 'OFFICIAL VERIFICATION CODE: '.$code, $cx, 710, 20, $inkColor, $bodyType, 600, 40, 'center');
                break;

            case 'confidential-urgent-box':
                $objects[] = $this->rect('conf-out', 90, 220, 900, 560, 'none', 14, 1.0, 0, $inkColor, 12.0);
                $objects[] = $this->rect('conf-in', 115, 245, 850, 510, 'none', 10, 0.85, 0, $inkColor, 3.0, [14, 10]);
                $objects[] = $this->text('conf-tag', 'new-text', '*** STRICTLY CONFIDENTIAL ***', $cx, 310, 24, $inkColor, $bodyType, 800, 60, 'center');
                $objects[] = $this->text('conf-title', 'primary-text', 'CONFIDENTIAL', $cx, 430, 96, $inkColor, $c, 900, 80, 'center');
                $objects[] = $this->text('conf-sub', 'new-text', 'FOR AUTHORIZED EYES ONLY · '.$brand, $cx, 570, 24, $inkColor, $bodyType, 700, 50, 'center');
                $objects[] = $this->text('conf-ref', 'new-text', 'REF ID: '.$code.'  ·  DISPOSAL UPON AUDIT', $cx, 640, 18, $inkColor, $bodyType, 600, 40, 'center');
                break;

            case 'traditional-hanko-square':
                $objects[] = $this->rect('hanko-out', 140, 140, 800, 800, 'none', 0, 1.0, 0, $inkColor, 14.0);
                $objects[] = $this->rect('hanko-in', 170, 170, 740, 740, 'none', 0, 0.85, 0, $inkColor, 3.0);
                $objects[] = $this->rect('hanko-v', 538, 170, 4, 740, $inkColor);
                $objects[] = $this->rect('hanko-h', 170, 538, 740, 4, $inkColor);
                $shortBrand = mb_substr($brand, 0, 8);
                $objects[] = $this->text('hanko-q1', 'primary-text', $shortBrand, 355, 360, 38, $inkColor, $c, 900, 30, 'center');
                $objects[] = $this->text('hanko-q2', 'primary-text', 'OFFICIAL', 725, 360, 32, $inkColor, $c, 800, 40, 'center');
                $objects[] = $this->text('hanko-q3', 'primary-text', 'SEAL', 355, 730, 36, $inkColor, $c, 900, 40, 'center');
                $objects[] = $this->text('hanko-q4', 'primary-text', 'VERIFIED', 725, 730, 30, $inkColor, $c, 800, 40, 'center');
                break;
        }

        return $objects;
    }
}
