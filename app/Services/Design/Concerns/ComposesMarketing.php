<?php

namespace App\Services\Design\Concerns;

use App\Services\Design\QrCodeMatrix;

use App\Support\Color;

/**
 * Social, marketing, editorial and event layouts.
 */
trait ComposesMarketing
{
    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutSocial(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.075;
        $cx = $w / 2;
        $cy = $h / 2;
        $objects = [];
        $variant = $c['variant'];
        $style = (string) ($c['style'] ?? 'post-emblem-showcase');

        // Helper: brand emblem card medallion
        $logoBadge = function (string $id, float $bcx, float $bcy, float $bsize, bool $withCard = true) use (&$objects, $c): void {
            if ($withCard) {
                $objects[] = $this->rect($id.'-card-back', $bcx - $bsize / 2 + 4, $bcy - $bsize / 2 + 4, $bsize, $bsize, '#000000', 16, 0.08);
                $objects[] = $this->rect($id.'-card', $bcx - $bsize / 2, $bcy - $bsize / 2, $bsize, $bsize, '#FFFFFF', 16, 1.0, 0, '#E2E8F0', 1.5);
            }

            $sym = $c['symbol'] ?? null;
            $isPng = is_array($sym) && ! empty($sym['src']) && preg_match('#\.(png|jpg|webp)$#i', (string) $sym['src']);

            if ($isPng) {
                $objects = array_merge($objects, $this->mark($c, $bcx, $bcy, $bsize * 0.88));
            } else {
                $objects = array_merge($objects, $this->mark($c, $bcx, $bcy - $bsize * 0.10, $bsize * 0.52));
                $bText = $this->block($c['brand'], $bsize * 0.90, $bsize * 0.12, 2, $c, 800, 40, true);
                $objects[] = $this->text($id.'-txt', 'primary-text', $bText['text'], $bcx, $bcy + $bsize * 0.26, $bText['size'], '#0F172A', $c, 800, 40, 'center');
            }
        };

        // Helper: scannable QR code
        $addQr = function (string $id, float $qcx, float $qcy, float $qsize, string $fg) use (&$objects, $c): void {
            $target = trim((string) ($c['qr_url'] ?? ''));

            if ($target === '' || $target === 'https://yourbrand.com') {
                $website = trim((string) ($c['website'] ?? ''));

                if ($website !== '' && $website !== 'yourbrand.com') {
                    $target = preg_match('#^https?://#i', $website) ? $website : 'https://'.$website;
                }
            }

            $matrix = QrCodeMatrix::encode($target !== '' ? $target : 'https://yourbrand.com');

            if ($matrix === null) {
                return;
            }

            $n = count($matrix);
            $quiet = 4;
            $total = $n + $quiet * 2;
            $module = $qsize / $total;
            $left = $qcx - $qsize / 2;
            $top = $qcy - $qsize / 2;

            $objects[] = $this->rect($id.'-bg', $left, $top, $qsize, $qsize, '#FFFFFF');

            $parts = [];

            for ($row = 0; $row < $n; $row++) {
                $runStart = null;

                for ($col = 0; $col <= $n; $col++) {
                    $dark = $col < $n && $matrix[$row][$col] === 1;

                    if ($dark && $runStart === null) {
                        $runStart = $col;
                    }

                    if (! $dark && $runStart !== null) {
                        $rx = $left + ($runStart + $quiet) * $module;
                        $ry = $top + ($row + $quiet) * $module;
                        $runWidth = ($col - $runStart) * $module;
                        $parts[] = 'M '.round($rx, 2).' '.round($ry, 2)
                            .' h '.round($runWidth, 2).' v '.round($module, 2)
                            .' h -'.round($runWidth, 2).' Z';
                        $runStart = null;
                    }
                }
            }

            $objects[] = $this->path($id, implode(' ', $parts), $fg);
        };

        // 1. post-emblem-showcase (BrandCrowd Authentic Emblem Showcase)
        if ($style === 'post-emblem-showcase') {
            $cardBg = $c['dark'] ? '#0F172A' : '#FAF9F6';
            $ink = $c['dark'] ? '#F8FAFC' : '#0F172A';
            $muted = $c['dark'] ? '#94A3B8' : '#64748B';
            $objects[] = $this->rect('post-es-bg', 0, 0, $w, $h, $cardBg);

            $inset1 = $unit * 0.05;
            $inset2 = $unit * 0.062;
            $objects[] = $this->rect('post-es-f1', $inset1, $inset1, $w - 2 * $inset1, $h - 2 * $inset1, 'none', 0, 1.0, 0, $c['accent'], 1.5);
            $objects[] = $this->rect('post-es-f2', $inset2, $inset2, $w - 2 * $inset2, $h - 2 * $inset2, 'none', 0, 1.0, 0, $c['dark'] ? '#334155' : '#E2E8F0', 1.0);

            $objects[] = $this->text('post-es-c1', 'new-text', '✦', $inset1 + 12, $inset1 + 12, $unit * 0.03, $c['accent'], $c);
            $objects[] = $this->text('post-es-c2', 'new-text', '✦', $w - $inset1 - 12, $inset1 + 12, $unit * 0.03, $c['accent'], $c, 400, 0, 'right');
            $objects[] = $this->text('post-es-c3', 'new-text', '✦', $inset1 + 12, $h - $inset1 - 12, $unit * 0.03, $c['accent'], $c);
            $objects[] = $this->text('post-es-c4', 'new-text', '✦', $w - $inset1 - 12, $h - $inset1 - 12, $unit * 0.03, $c['accent'], $c, 400, 0, 'right');

            $objects[] = $this->text('post-es-eyebrow', 'new-text', '★  PREMIUM QUALITY & CRAFT  ★', $cx, $h * 0.13, $unit * 0.024, $c['accent'], $c, 800, 120, 'center');
            $objects[] = $this->text('post-es-stars', 'new-text', '★★★★★', $cx, $h * 0.18, $unit * 0.042, '#F59E0B', $c, 900, 40, 'center');

            $logoBadge('post-es-logo', $cx, $h * 0.44, $unit * 0.40, true);

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.075, 2, $c, 900, 20, true);
            $objects[] = $this->text('post-es-brand', 'primary-text', $brand['text'], $cx, $h * 0.70, $brand['size'], $ink, $c, 900, 20, 'center');

            $tagline = ! empty($c['tagline']) && $c['tagline'] !== 'Independent studio' ? $c['tagline'] : ($c['subhead'] ?? 'Authentic Flavours & Warm Hospitality');
            $sub = $this->block($tagline, $w * 0.75, $unit * 0.03, 1, $c, 600, 80);
            $objects[] = $this->text('post-es-tag', 'new-text', $sub['text'], $cx, $h * 0.78, $sub['size'], $muted, $c, 600, 80, 'center');

            $objects = array_merge($objects, $this->ctaPill('post-es-cta', $cx - $unit * 0.18, $h * 0.86, $c['cta'], $c, null, null, true));

            return $objects;
        }

        // 2. post-bites-brews-cup (BrandCrowd Coffee & Bakery Style)
        if ($style === 'post-bites-brews-cup') {
            $objects[] = $this->rect('post-bb-bg', 0, 0, $w, $h, '#FFFFFF');

            $objects[] = $this->text('post-bb-t1', 'new-text', 'Bites &', $cx - $unit * 0.12, $h * 0.12, $unit * 0.08, '#18181B', $c, 800, 10, 'center');
            $objects[] = $this->text('post-bb-t2', 'new-text', 'Brews', $cx + $unit * 0.14, $h * 0.12, $unit * 0.095, '#18181B', $c, 900, 20, 'center');
            $objects[] = $this->text('post-bb-star1', 'new-text', '✦', $cx - $unit * 0.32, $h * 0.11, $unit * 0.05, '#F59E0B', $c, 700);
            $objects[] = $this->text('post-bb-star2', 'new-text', '✦', $cx + $unit * 0.32, $h * 0.14, $unit * 0.04, '#F59E0B', $c, 700);

            $objects[] = [
                'id' => 'post-bb-latte',
                'type' => 'image',
                'left' => round($cx, 2),
                'top' => round($h * 0.44, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.46, 2),
                'height' => round($unit * 0.46, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/latte-coffee-cup-topdown.png',
            ];

            $logoBadge('post-bb-logo', $cx + $unit * 0.28, $h * 0.58, $unit * 0.28, true);

            $objects[] = $this->rect('post-bb-pill-bg', $cx - $unit * 0.24, $h * 0.72, $unit * 0.48, $unit * 0.05, '#18181B', $unit * 0.025);
            $objects[] = $this->text('post-bb-pill-txt', 'new-text', 'FRESHLY BREWED DAILY', $cx, $h * 0.732, $unit * 0.024, '#FFFFFF', $c, 800, 80, 'center');

            $objects[] = $this->text('post-bb-hours', 'new-text', 'OPEN DAILY · 8:00 AM - 10:00 PM', $cx, $h * 0.81, $unit * 0.026, '#64748B', $c, 700, 40, 'center');
            $objects[] = $this->text('post-bb-order', 'new-text', 'Order online at '.($c['website'] ?? 'website.com'), $cx, $h * 0.88, $unit * 0.024, '#0F172A', $c, 600, 20, 'center');

            return $objects;
        }

        // 3. post-special-discount (BrandCrowd Special Discount Offer)
        if ($style === 'post-special-discount') {
            $cardBg = $c['colors'][0] ?? '#0F172A';
            $objects[] = $this->rect('post-sd-bg', 0, 0, $w, $h, $cardBg);
            $objects[] = $this->circle('post-sd-glow', $cx, $h * 0.4, $unit * 0.55, '#FFFFFF', null, 0, 0.06);

            $objects[] = $this->rect('post-sd-tag-bg', $cx - $unit * 0.22, $h * 0.11, $unit * 0.44, $unit * 0.05, '#F59E0B', $unit * 0.025);
            $objects[] = $this->text('post-sd-tag-txt', 'new-text', '★ LIMITED TIME OFFER ★', $cx, $h * 0.123, $unit * 0.024, '#000000', $c, 800, 60, 'center');

            $disc = ! empty($c['discount']) ? $c['discount'] : '20% OFF';
            $objects[] = $this->text('post-sd-num', 'primary-text', $disc, $cx, $h * 0.23, $unit * 0.15, '#FFFFFF', $c, 900, 10, 'center');

            $tW = $w * 0.68;
            $tH = $unit * 0.13;
            $objects[] = $this->rect('post-sd-tkt', $cx - $tW / 2, $h * 0.43, $tW, $tH, '#FFFFFF', $unit * 0.015, 0.95);
            $objects[] = $this->text('post-sd-tkt-lbl', 'new-text', 'USE COUPON CODE AT CHECKOUT', $cx, $h * 0.45, $unit * 0.020, '#64748B', $c, 700, 60, 'center');
            $code = ! empty($c['code']) ? $c['code'] : 'TASTY20';
            $objects[] = $this->text('post-sd-tkt-code', 'new-text', $code, $cx, $h * 0.50, $unit * 0.052, '#0F172A', $c, 900, 120, 'center');

            $logoBadge('post-sd-logo', $cx, $h * 0.67, $unit * 0.22, true);

            $objects[] = $this->text('post-sd-note', 'new-text', 'Valid for dine-in, takeaway & online orders', $cx, $h * 0.81, $unit * 0.024, '#E2E8F0', $c, 500, 20, 'center');
            $objects = array_merge($objects, $this->ctaPill('post-sd-cta', $cx - $unit * 0.20, $h * 0.87, $c['cta'], $c, null, null, true));

            return $objects;
        }

        // 4. post-were-open-diamond (BrandCrowd Diamond We're Open)
        if ($style === 'post-were-open-diamond') {
            $objects[] = $this->rect('post-wod-bg', 0, 0, $w, $h, '#F8FAFC');
            $objects[] = $this->text('post-wod-kicker', 'new-text', 'PICK UP & DELIVERY AVAILABLE', $cx, $h * 0.10, $unit * 0.024, '#64748B', $c, 700, 80, 'center');

            $diaSize = $unit * 0.44;
            $objects[] = $this->rect('post-wod-diamond', $cx - $diaSize / 2, $h * 0.36 - $diaSize / 2, $diaSize, $diaSize, '#18181B', $unit * 0.015, 1.0, 45);

            $objects[] = $this->text('post-wod-yes', 'new-text', 'Yes!', $cx, $h * 0.25, $unit * 0.045, '#FFFFFF', $c, 700, 10, 'center');
            $objects[] = $this->text('post-wod-t1', 'new-text', "WE'RE", $cx, $h * 0.33, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');
            $objects[] = $this->text('post-wod-t2', 'new-text', 'OPEN', $cx, $h * 0.42, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');

            $objects[] = $this->rule('post-wod-r1', $m, $h * 0.36, $cx - $diaSize * 0.65 - $m, '#CBD5E1', 2);
            $objects[] = $this->rule('post-wod-r2', $cx + $diaSize * 0.65, $h * 0.36, $w - $m - ($cx + $diaSize * 0.65), '#CBD5E1', 2);

            $logoBadge('post-wod-logo', $cx, $h * 0.67, $unit * 0.26, true);

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.042, 1, $c, 800, 40, true);
            $objects[] = $this->text('post-wod-brand', 'primary-text', $brand['text'], $cx, $h * 0.82, $brand['size'], '#18181B', $c, 800, 40, 'center');
            $objects[] = $this->text('post-wod-hours', 'new-text', 'MON - SUN · 8:00 AM - 10:00 PM  ·  '.($c['website'] ?? 'yourbrand.com'), $cx, $h * 0.89, $unit * 0.024, '#64748B', $c, 600, 30, 'center');

            return $objects;
        }

        // 5. post-customer-review (BrandCrowd Customer Review Stars)
        if ($style === 'post-customer-review') {
            $objects[] = $this->rect('post-cr-bg', 0, 0, $w, $h, '#FAF8F5');
            $objects[] = $this->rect('post-cr-card', $m, $m, $w - 2 * $m, $h - 2 * $m, '#FFFFFF', $unit * 0.025, 1.0, 0, '#E2E8F0', 1.5);

            $objects[] = $this->text('post-cr-quote', 'shape', '“', $cx, $h * 0.13, $unit * 0.22, $c['accent'], $c, 800, 0, 'center');
            $objects[] = $this->text('post-cr-stars', 'new-text', '★★★★★', $cx, $h * 0.26, $unit * 0.05, '#F59E0B', $c, 900, 40, 'center');

            $quoteText = ! empty($c['quote']) && $c['quote'] !== 'Simplicity is the ultimate sophistication.' ? $c['quote'] : 'The finest atmosphere, warm hospitality, and most delightful cuisine in town. Absolutely unforgettable!';
            $review = $this->block('“'.$quoteText.'”', $w * 0.72, $unit * 0.045, 4, $c, 600, 20);
            $objects[] = $this->text('post-cr-text', 'primary-text', $review['text'], $cx, $h * 0.35, $review['size'], '#1E293B', $c, 600, 20, 'center');

            $attr = ! empty($c['attribution']) && $c['attribution'] !== 'Leonardo da Vinci' ? $c['attribution'] : 'Jordan Ellis · Verified Diner';
            $objects[] = $this->text('post-cr-attr', 'new-text', '— '.$attr, $cx, $h * 0.59, $unit * 0.028, '#64748B', $c, 700, 60, 'center');

            $objects[] = $this->rule('post-cr-div', $cx - $unit * 0.15, $h * 0.65, $unit * 0.30, '#E2E8F0', 1.5);

            $logoBadge('post-cr-logo', $cx, $h * 0.77, $unit * 0.22, true);
            $objects[] = $this->text('post-cr-handle', 'new-text', ($c['handle'] ?? '@yourbrand').'  ·  '.($c['website'] ?? 'yourbrand.com'), $cx, $h * 0.90, $unit * 0.024, '#0F172A', $c, 600, 40, 'center');

            return $objects;
        }

        // 6. post-menu-qr (BrandCrowd Menu QR Code)
        if ($style === 'post-menu-qr') {
            $cardBg = $c['dark'] ? '#0F172A' : '#FFFFFF';
            $ink = $c['dark'] ? '#F8FAFC' : '#0F172A';
            $objects[] = $this->rect('post-mq-bg', 0, 0, $w, $h, $cardBg);

            $logoBadge('post-mq-logo', $cx, $h * 0.14, $unit * 0.20, true);

            $objects[] = $this->text('post-mq-eyebrow', 'new-text', 'DIGITAL EXPERIENCE', $cx, $h * 0.26, $unit * 0.024, $c['accent'], $c, 800, 100, 'center');
            $objects[] = $this->text('post-mq-hl', 'primary-text', 'VIEW OUR FULL MENU', $cx, $h * 0.31, $unit * 0.065, $ink, $c, 900, 20, 'center');

            $qrSize = $unit * 0.34;
            $addQr('post-mq-qr', $cx, $h * 0.54, $qrSize, '#000000');

            $bracketOff = $qrSize / 2 + 10;
            $objects[] = $this->text('post-mq-c1', 'new-text', '┌', $cx - $bracketOff, $h * 0.54 - $bracketOff + 10, $unit * 0.04, $c['accent'], $c);
            $objects[] = $this->text('post-mq-c2', 'new-text', '┐', $cx + $bracketOff - 10, $h * 0.54 - $bracketOff + 10, $unit * 0.04, $c['accent'], $c);
            $objects[] = $this->text('post-mq-c3', 'new-text', '└', $cx - $bracketOff, $h * 0.54 + $bracketOff, $unit * 0.04, $c['accent'], $c);
            $objects[] = $this->text('post-mq-c4', 'new-text', '┘', $cx + $bracketOff - 10, $h * 0.54 + $bracketOff, $unit * 0.04, $c['accent'], $c);

            $objects[] = $this->text('post-mq-scan', 'new-text', 'POINT YOUR PHONE CAMERA TO SCAN', $cx, $h * 0.76, $unit * 0.026, $c['accent'], $c, 800, 60, 'center');
            $objects[] = $this->text('post-mq-sub', 'new-text', 'Explore drinks, chef specials & dietary options', $cx, $h * 0.81, $unit * 0.024, '#64748B', $c, 500, 20, 'center');
            $objects[] = $this->text('post-mq-web', 'new-text', ($c['website'] ?? 'yourbrand.com'), $cx, $h * 0.88, $unit * 0.026, $ink, $c, 700, 40, 'center');

            return $objects;
        }

        // 7. post-giveaway-bold (BrandCrowd Kinetic Giveaway Alert)
        if ($style === 'post-giveaway-bold') {
            $objects[] = $this->rect('post-gb-bg', 0, 0, $w, $h, '#18181B');

            $objects[] = $this->rect('post-gb-ribbon', 0, $h * 0.06, $w, $unit * 0.08, '#DC2626');
            $objects[] = $this->text('post-gb-rib-txt', 'new-text', '★  G I V E A W A Y   A L E R T  ★', $cx, $h * 0.095, $unit * 0.034, '#FFFFFF', $c, 900, 100, 'center');

            $objects[] = $this->text('post-gb-win', 'new-text', 'WIN A COMPLIMENTARY', $cx, $h * 0.19, $unit * 0.035, '#FBBF24', $c, 800, 60, 'center');
            $objects[] = $this->text('post-gb-prize', 'primary-text', '$150 DINING VOUCHER', $cx, $h * 0.25, $unit * 0.08, '#FFFFFF', $c, 900, 10, 'center');

            $logoBadge('post-gb-logo', $cx, $h * 0.44, $unit * 0.26, true);

            $boxW = $w * 0.78;
            $boxH = $unit * 0.22;
            $objects[] = $this->rect('post-gb-box', $cx - $boxW / 2, $h * 0.59, $boxW, $boxH, '#27272A', $unit * 0.02);

            $objects[] = $this->text('post-gb-s1', 'new-text', '1.  Follow  '.($c['handle'] ?? '@yourbrand'), $cx - $boxW * 0.42, $h * 0.63, $unit * 0.026, '#F8FAFC', $c, 700, 30);
            $objects[] = $this->text('post-gb-s2', 'new-text', '2.  Like & Tag 2 friends in comments', $cx - $boxW * 0.42, $h * 0.69, $unit * 0.026, '#F8FAFC', $c, 700, 30);
            $objects[] = $this->text('post-gb-s3', 'new-text', '3.  Share to your Story for a bonus entry!', $cx - $boxW * 0.42, $h * 0.75, $unit * 0.026, '#FBBF24', $c, 700, 30);

            $objects[] = $this->text('post-gb-terms', 'new-text', 'Winner announced this Sunday · Terms & Conditions apply', $cx, $h * 0.88, $unit * 0.022, '#94A3B8', $c, 500, 20, 'center');

            return $objects;
        }

        // 8. post-luxury-dark (BrandCrowd Luxury Midnight Gold Spotlight)
        if ($style === 'post-luxury-dark') {
            $objects[] = $this->rect('post-ld-bg', 0, 0, $w, $h, '#09090B');

            $objects[] = $this->circle('post-ld-glow', $cx, $h * 0.42, $unit * 0.45, '#D97706', null, 0, 0.12);
            $objects[] = $this->circle('post-ld-ring1', $cx, $h * 0.42, $unit * 0.38, 'none', '#D97706', 1.5, 0.4);
            $objects[] = $this->circle('post-ld-ring2', $cx, $h * 0.42, $unit * 0.44, 'none', '#F59E0B', 1.0, 0.25);

            $objects[] = $this->text('post-ld-eye', 'new-text', 'AN UNPARALLELED CULINARY JOURNEY', $cx, $h * 0.11, $unit * 0.024, '#F59E0B', $c, 700, 140, 'center');

            $logoBadge('post-ld-logo', $cx, $h * 0.42, $unit * 0.36, true);

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.075, 2, $c, 900, 20, true);
            $objects[] = $this->text('post-ld-brand', 'primary-text', $brand['text'], $cx, $h * 0.66, $brand['size'], '#FFFFFF', $c, 900, 20, 'center');

            $sub = $this->block($c['subhead'] ?? 'Exquisite Flavours · Rare Ingredients · Warm Ambiance', $w * 0.75, $unit * 0.028, 1, $c, 500, 60);
            $objects[] = $this->text('post-ld-sub', 'new-text', $sub['text'], $cx, $h * 0.73, $sub['size'], '#D4D4D8', $c, 500, 60, 'center');

            $objects = array_merge($objects, $this->ctaPill('post-ld-cta', $cx - $unit * 0.20, $h * 0.82, 'RESERVE A TABLE', $c, '#D97706', '#FFFFFF', true));
            $objects[] = $this->text('post-ld-web', 'new-text', ($c['website'] ?? 'yourbrand.com').'  ·  '.($c['phone'] ?? '+1 555-010-2030'), $cx, $h * 0.91, $unit * 0.024, '#A1A1AA', $c, 600, 40, 'center');

            return $objects;
        }

        // 9. post-vintage-frappe-sketch (BrandCrowd Vintage Sketch Style)
        if ($style === 'post-vintage-frappe-sketch') {
            $objects[] = $this->rect('post-vf-bg', 0, 0, $w, $h, '#FBF9F5');

            $inset = $unit * 0.04;
            $objects[] = $this->rect('post-vf-frame', $inset, $inset, $w - 2 * $inset, $h - 2 * $inset, 'none', 0, 1.0, 0, '#78350F', 1.5);
            $objects[] = $this->text('post-vf-kicker', 'new-text', '· ARTISANAL BEVERAGES ·', $cx, $h * 0.10, $unit * 0.026, '#78350F', $c, 800, 100, 'center');

            $objects[] = [
                'id' => 'post-vf-sketch',
                'type' => 'image',
                'left' => round($cx - $unit * 0.20, 2),
                'top' => round($h * 0.42, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.42, 2),
                'height' => round($unit * 0.42, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/vintage-coffee-frappe-sketch.png',
            ];

            $logoBadge('post-vf-logo', $cx + $unit * 0.24, $h * 0.42, $unit * 0.32, true);

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.065, 2, $c, 900, 10, true);
            $objects[] = $this->text('post-vf-brand', 'primary-text', $brand['text'], $cx, $h * 0.69, $brand['size'], '#451A03', $c, 900, 10, 'center');

            $objects[] = $this->text('post-vf-est', 'new-text', 'BREWED WITH PASSION SINCE 2018', $cx, $h * 0.76, $unit * 0.026, '#9A3412', $c, 700, 60, 'center');
            $objects[] = $this->text('post-vf-sub', 'new-text', 'Hand-poured single origins & signature frappes', $cx, $h * 0.81, $unit * 0.024, '#78350F', $c, 500, 20, 'center');

            $objects = array_merge($objects, $this->ctaPill('post-vf-cta', $cx - $unit * 0.18, $h * 0.88, $c['cta'], $c, '#78350F', '#FFFFFF', true));

            return $objects;
        }

        // 10. post-todays-special (BrandCrowd Chef Special Ribbon)
        if ($style === 'post-todays-special') {
            $objects[] = $this->rect('post-ts-bg', 0, 0, $w, $h, '#FFFFFF');

            $objects[] = $this->rect('post-ts-ribbon', 0, 0, $w, $unit * 0.08, '#DC2626');
            $objects[] = $this->text('post-ts-rib-txt', 'new-text', "★  TODAY'S CHEF SPECIAL  ★", $cx, $unit * 0.038, $unit * 0.032, '#FFFFFF', $c, 900, 80, 'center');

            $hl = ! empty($c['headline']) && $c['headline'] !== 'Make it memorable' && $c['headline'] !== 'New drop, new season' ? $c['headline'] : 'Signature Chef Special';
            $head = $this->block($hl, $w * 0.85, $unit * 0.075, 2, $c, 900, 10, true);
            $objects[] = $this->text('post-ts-dish', 'primary-text', $head['text'], $cx, $h * 0.17, $head['size'], '#0F172A', $c, 900, 10, 'center');

            $logoBadge('post-ts-logo', $cx, $h * 0.43, $unit * 0.38, true);

            $price = ! empty($c['price']) ? $c['price'] : '$24.99';
            $objects[] = $this->circle('post-ts-price-bg', $cx + $unit * 0.22, $h * 0.33, $unit * 0.10, '#DC2626');
            $objects[] = $this->text('post-ts-price', 'new-text', $price, $cx + $unit * 0.22, $h * 0.32, $unit * 0.042, '#FFFFFF', $c, 900, 0, 'center');

            $objects[] = $this->text('post-ts-notes', 'new-text', 'Slow-cooked with wild mountain herbs & artisanal glaze', $cx, $h * 0.68, $unit * 0.028, '#475569', $c, 500, 20, 'center');
            $objects[] = $this->text('post-ts-limit', 'new-text', 'LIMITED PORTIONS AVAILABLE · DINE-IN & TAKEAWAY', $cx, $h * 0.73, $unit * 0.024, '#DC2626', $c, 800, 60, 'center');

            $objects = array_merge($objects, $this->ctaPill('post-ts-cta', $cx - $unit * 0.18, $h * 0.81, 'ORDER NOW', $c, '#DC2626', '#FFFFFF', true));
            $objects[] = $this->text('post-ts-hours', 'new-text', 'Served daily from 5:00 PM · Reservations recommended', $cx, $h * 0.90, $unit * 0.022, '#64748B', $c, 600, 20, 'center');

            return $objects;
        }

        // 11. post-minimal-editorial (BrandCrowd Clean Minimalist Editorial)
        if ($style === 'post-minimal-editorial') {
            $objects[] = $this->rect('post-me-bg', 0, 0, $w, $h, '#FFFFFF');

            $inset = $unit * 0.06;
            $objects[] = $this->rect('post-me-frame', $inset, $inset, $w - 2 * $inset, $h - 2 * $inset, 'none', 0, 1.0, 0, '#E2E8F0', 1.0);

            $logoBadge('post-me-logo', $cx, $h * 0.23, $unit * 0.28, false);

            $brand = $this->block($c['brand'], $w * 0.75, $unit * 0.045, 1, $c, 800, 80, true);
            $objects[] = $this->text('post-me-brand', 'primary-text', $brand['text'], $cx, $h * 0.41, $brand['size'], '#0F172A', $c, 800, 80, 'center');

            $hl = ! empty($c['headline']) && $c['headline'] !== 'Make it memorable' && $c['headline'] !== 'New drop, new season' ? $c['headline'] : '“Crafted for those who appreciate the finer details.”';
            $head = $this->block($hl, $w * 0.72, $unit * 0.055, 3, $c, 600, 10);
            $objects[] = $this->text('post-me-hl', 'primary-text', $head['text'], $cx, $h * 0.51, $head['size'], '#1E293B', $c, 600, 10, 'center');

            $objects[] = $this->rule('post-me-rule', $cx - $unit * 0.12, $h * 0.67, $unit * 0.24, '#0F172A', 1.5);

            $objects[] = $this->text('post-me-loc', 'new-text', ($c['address'] ?? '24 Market Street').'  ·  '.($c['phone'] ?? '+1 555-010-2030'), $cx, $h * 0.74, $unit * 0.024, '#64748B', $c, 600, 40, 'center');
            $objects = array_merge($objects, $this->ctaPill('post-me-cta', $cx - $unit * 0.16, $h * 0.82, $c['cta'], $c, '#0F172A', '#FFFFFF', true));

            return $objects;
        }

        // 12. post-weekend-brunch (BrandCrowd Weekend Brunch Club)
        if ($style === 'post-weekend-brunch') {
            $objects[] = $this->rect('post-wb-bg', 0, 0, $w, $h, '#FFFDF7');

            $objects[] = $this->text('post-wb-eye', 'new-text', '★ JOIN US THIS SATURDAY & SUNDAY ★', $cx, $h * 0.10, $unit * 0.024, '#B45309', $c, 800, 80, 'center');
            $objects[] = $this->text('post-wb-hl', 'primary-text', 'WEEKEND BRUNCH CLUB', $cx, $h * 0.16, $unit * 0.075, '#18181B', $c, 900, 20, 'center');

            $objects[] = [
                'id' => 'post-wb-latte',
                'type' => 'image',
                'left' => round($cx - $unit * 0.18, 2),
                'top' => round($h * 0.43, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.40, 2),
                'height' => round($unit * 0.40, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/latte-coffee-cup-topdown.png',
            ];

            $logoBadge('post-wb-logo', $cx + $unit * 0.22, $h * 0.43, $unit * 0.28, true);

            $objects[] = $this->rect('post-wb-pill-bg', $cx - $unit * 0.28, $h * 0.68, $unit * 0.56, $unit * 0.05, '#B45309', $unit * 0.025);
            $objects[] = $this->text('post-wb-pill-txt', 'new-text', '9:00 AM - 3:00 PM · FRESH ROAST & PASTRIES', $cx, $h * 0.693, $unit * 0.022, '#FFFFFF', $c, 800, 60, 'center');

            $objects[] = $this->text('post-wb-sub', 'new-text', 'Handcrafted mimosas, artisanal sourdough & signature pancakes', $cx, $h * 0.76, $unit * 0.025, '#78350F', $c, 500, 20, 'center');

            $objects = array_merge($objects, $this->ctaPill('post-wb-cta', $cx - $unit * 0.18, $h * 0.82, 'BOOK A TABLE', $c, '#18181B', '#FFFFFF', true));
            $objects[] = $this->text('post-wb-web', 'new-text', ($c['website'] ?? 'yourbrand.com'), $cx, $h * 0.90, $unit * 0.024, '#64748B', $c, 600, 30, 'center');

            return $objects;
        }

        // Fallback for non-post styles or legacy variants
        if ($variant === 'quote') {
            $objects[] = $this->text('quote-mark', 'shape', '“', $m, $h * 0.14, $unit * 0.24, $c['accent'], $c, 800, 0);
            $quote = $this->block($c['quote'], $w - 2 * $m, $unit * 0.085, 5, $c, 600, 10);
            $objects[] = $this->text('quote-text', 'primary-text', $quote['text'], $cx, $h * 0.36, $quote['size'], $c['ink'], $c, 600, 10, 'center');
            $objects[] = $this->rule('quote-rule', $cx - $unit * 0.06, $h * 0.76, $unit * 0.12, $c['accent'], $unit * 0.005);
            $attr = $this->block('— '.$c['attribution'], $w - 2 * $m, $unit * 0.028, 1, $c, 600, 120, true);
            $objects[] = $this->text('quote-attr', 'new-text', $attr['text'], $cx, $h * 0.8, $attr['size'], $c['muted'], $c, 600, 120, 'center');
            $objects[] = $this->text('quote-brand', 'new-text', $c['brand'], $cx, $h - $m, $unit * 0.022, $c['ink'], $c, 700, 200, 'center');

            return $objects;
        }

        if ($variant === 'thumbnail') {
            $objects[] = $this->rect('thumb-block', 0, $h * 0.58, $w, $h * 0.42, $c['accent'], 0, 0.94);
            $objects[] = $this->circle('thumb-arrow-bg', $w * 0.84, $h * 0.3, $unit * 0.13, $c['accent']);
            $objects[] = $this->text('thumb-arrow', 'shape', '→', $w * 0.84, $h * 0.3 - $unit * 0.07, $unit * 0.12, Color::readableInk($c['accent']), $c, 700, 0, 'center');
            $chip = $this->block($c['subhead'], $w * 0.5, $unit * 0.026, 1, $c, 700, 160, true);
            $chipW = $chip['width'] + $unit * 0.05;
            $objects[] = $this->rect('thumb-chip', $m, $m, $chipW, $unit * 0.055, $c['ink'], $unit * 0.0275);
            $objects[] = $this->text('thumb-chip-text', 'new-text', $chip['text'], $m + $chipW / 2, $m + ($unit * 0.055 - $chip['size'] * 1.12) / 2, $chip['size'], Color::readableInk($c['ink']), $c, 700, 160, 'center');
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.13, 3, $c, 800, 0, true);
            $lines = max(1, count($headline['lines']));
            $objects[] = $this->text('thumb-headline', 'primary-text', $headline['text'], $m, $h * 0.66 - ($headline['size'] * 1.12 * $lines) / 2, $headline['size'], Color::readableInk($c['accent']), $c, 800, 0);
            $objects[] = $this->text('thumb-brand', 'new-text', $c['brand'], $w - $m, $h - $m, $unit * 0.022, Color::readableInk($c['accent']), $c, 700, 200, 'center');

            return $objects;
        }

        if ($variant === 'listing') {
            $tile = $w * 0.4;
            $objects[] = $this->rect('listing-tile', $m, $m, $tile, $h - 2 * $m, $c['support'], $unit * 0.02, 0.4);
            $objects = array_merge($objects, $this->mark($c, $m + $tile / 2, $h * 0.42, $unit * 0.18));
            $textX = $m + $tile + $unit * 0.05;
            $textW = $w - $textX - $m;
            $brand = $this->block($c['brand'], $textW, $unit * 0.024, 1, $c, 700, 220, true);
            $objects[] = $this->text('listing-brand', 'new-text', $brand['text'], $textX, $m, $brand['size'], $c['accent'], $c, 700, 220);
            $headline = $this->block($c['headline'], $textW, $unit * 0.06, 3, $c, 800, 10);
            $objects[] = $this->text('listing-headline', 'primary-text', $headline['text'], $textX, $h * 0.2, $headline['size'], $c['ink'], $c, 800, 10);
            $objects[] = $this->text('listing-price', 'new-text', $c['price'], $textX, $h * 0.58, $unit * 0.075, $c['accent'], $c, 800, 0);
            $cta = $this->ctaPill('listing-cta', $textX, $h * 0.72, $c['cta'], $c);
            $objects = array_merge($objects, $cta);

            return $objects;
        }

        // Brand lockup row fallback.
        $markSize = $unit * 0.055;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
        $brand = $this->block($c['brand'], $w * 0.55, $unit * 0.022, 1, $c, 700, 140, true);
        $objects[] = $this->text('social-brand', 'new-text', $brand['text'], $m + $markSize + $unit * 0.015, $m + $markSize / 2 - $brand['size'] * 0.55, $brand['size'], $c['ink'], $c, 700, 140);

        if ($variant === 'carousel') {
            $objects[] = $this->circle('carousel-count-bg', $w - $m - $unit * 0.045, $m + $unit * 0.045, $unit * 0.045, $c['accent']);
            $objects[] = $this->text('carousel-count', 'shape', $c['notes'] !== '' ? $c['notes'] : '1 / 5', $w - $m - $unit * 0.045, $m + $unit * 0.045 - $unit * 0.014, $unit * 0.022, Color::readableInk($c['accent']), $c, 700, 40, 'center');
        }

        if ($variant === 'event') {
            $date = $this->block($c['date'], $w * 0.6, $unit * 0.028, 1, $c, 700, 160, true);
            $objects[] = $this->rect('event-date-bg', $m, $m + $unit * 0.1, $date['width'] + $unit * 0.04, $unit * 0.05, $c['accent'], $unit * 0.025);
            $objects[] = $this->text('event-date', 'new-text', $date['text'], $m + ($date['width'] + $unit * 0.04) / 2, $m + $unit * 0.1 + ($unit * 0.05 - $date['size'] * 1.12) / 2, $date['size'], Color::readableInk($c['accent']), $c, 700, 160, 'center');
        }

        if ($variant === 'giveaway') {
            $badge = $this->block('GIVEAWAY', $w * 0.4, $unit * 0.024, 1, $c, 700, 220, true);
            $objects[] = $this->rect('giveaway-badge', $m, $m + $unit * 0.1, $badge['width'] + $unit * 0.04, $unit * 0.05, $c['accent'], $unit * 0.025);
            $objects[] = $this->text('giveaway-badge-text', 'shape', $badge['text'], $m + ($badge['width'] + $unit * 0.04) / 2, $m + $unit * 0.1 + ($unit * 0.05 - $badge['size'] * 1.12) / 2, $badge['size'], Color::readableInk($c['accent']), $c, 700, 220, 'center');
        }

        $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.11, 3, $c, 800, 0, $variant !== 'post');
        $headlineTop = $h * ($variant === 'announcement' ? 0.34 : 0.32);
        $objects[] = $this->text('social-headline', 'primary-text', $headline['text'], $variant === 'announcement' ? $cx : $m, $headlineTop, $headline['size'], $c['ink'], $c, 800, 0, $variant === 'announcement' ? 'center' : 'left');
        $headlineBottom = $headlineTop + $headline['size'] * 1.12 * max(1, count($headline['lines']));

        $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.032, 2, $c, 500, 20);
        $objects[] = $this->text('social-sub', 'new-text', $sub['text'], $variant === 'announcement' ? $cx : $m, $headlineBottom + $unit * 0.03, $sub['size'], $c['muted'], $c, 500, 20, $variant === 'announcement' ? 'center' : 'left');

        $objects = array_merge($objects, $this->ctaPill('social-cta', $variant === 'announcement' ? $cx - $unit * 0.16 : $m, $h * 0.74, $c['cta'], $c, null, null, $variant === 'announcement'));

        if ($variant === 'giveaway') {
            $notes = $this->block($c['notes'], $w * 0.6, $unit * 0.021, 2, $c, 400, 40);
            $objects[] = $this->text('giveaway-notes', 'new-text', $notes['text'], $m, $h * 0.84, $notes['size'], $c['muted'], $c, 400, 40);
        }

        $handle = $this->block($c['handle'], $w * 0.45, $unit * 0.022, 1, $c, 600, 160);
        $objects[] = $this->text('social-handle', 'new-text', $handle['text'], $w - $m, $h - $m, $handle['size'], $c['ink'], $c, 600, 160, 'center');

        $objects[] = $this->circle('social-deco', $w - $m * 0.6, $h * 0.16, $unit * 0.12, $c['support'], null, 0, 0.22);

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutStory(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.075;
        $cx = $w / 2;
        $style = (string) ($c['style'] ?? 'story-framed-crest');
        $objects = [];

        // 1. story-framed-crest (or retro, editorial)
        if ($style === 'story-framed-crest' || $style === 'retro' || $style === 'editorial') {
            $inset = $m * 0.65;
            $objects[] = $this->rule('story-frame-t', $inset, $inset, $w - 2 * $inset, $c['ink'], $unit * 0.004, 0.55);
            $objects[] = $this->rule('story-frame-b', $inset, $h - $inset, $w - 2 * $inset, $c['ink'], $unit * 0.004, 0.55);
            $objects[] = $this->rect('story-frame-l', $inset, $inset, $unit * 0.004, $h - 2 * $inset, $c['ink'], 0, 0.55);
            $objects[] = $this->rect('story-frame-r', $w - $inset - $unit * 0.004, $inset, $unit * 0.004, $h - 2 * $inset, $c['ink'], 0, 0.55);

            $objects[] = $this->circle('story-crest-ring', $cx, $h * 0.16, $unit * 0.17, 'none', $c['accent'], $unit * 0.005, 0.7);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.16, $unit * 0.22));
            $brand = $this->block($c['brand'], $w * 0.72, $unit * 0.032, 1, $c, 700, 220, true);
            $objects[] = $this->text('story-brand', 'new-text', $brand['text'], $cx, $h * 0.27, $brand['size'], $c['accent'], $c, 700, 220, 'center');

            $headline = $this->block($c['headline'], $w - 2.4 * $m, $unit * 0.145, 3, $c, 800, 10, true);
            $objects[] = $this->text('story-headline', 'primary-text', $headline['text'], $cx, $h * 0.38, $headline['size'], $c['ink'], $c, 800, 10, 'center');
            $hBot = $h * 0.38 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $sub = $this->block($c['subhead'], $w * 0.78, $unit * 0.036, 2, $c, 500, 30);
            $objects[] = $this->text('story-sub', 'new-text', $sub['text'], $cx, $hBot + $unit * 0.035, $sub['size'], $c['muted'], $c, 500, 30, 'center');

            $objects = array_merge($objects, $this->ctaPill('story-cta', $cx - $unit * 0.2, $h * 0.76, $c['cta'], $c, null, null, true));
            $objects[] = $this->text('story-handle', 'new-text', $c['handle'], $cx, $h - $m * 1.35, $unit * 0.026, $c['muted'], $c, 600, 160, 'center');

            return $objects;
        }

        // 2. story-polaroid-strip (or split, gradient)
        if ($style === 'story-polaroid-strip' || $style === 'split' || $style === 'gradient') {
            $objects[] = $this->circle('story-bg-glow', $cx, $h * 0.28, $w * 0.55, '#FFFFFF', null, 0, 0.08);
            $brand = $this->block($c['brand'], $w * 0.72, $unit * 0.034, 1, $c, 700, 220, true);
            $objects[] = $this->text('story-brand', 'new-text', $brand['text'], $cx, $h * 0.1, $brand['size'], $c['ink'], $c, 700, 220, 'center');

            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.15, 3, $c, 800, 0, true);
            $objects[] = $this->text('story-headline', 'primary-text', $headline['text'], $cx, $h * 0.18, $headline['size'], $c['ink'], $c, 800, 0, 'center');
            $hBot = $h * 0.18 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $sub = $this->block($c['subhead'], $w * 0.8, $unit * 0.038, 2, $c, 500, 20);
            $objects[] = $this->text('story-sub', 'new-text', $sub['text'], $cx, $hBot + $unit * 0.025, $sub['size'], $c['muted'], $c, 500, 20, 'center');

            $tileW = $w * 0.26;
            $tileH = $tileW * 1.18;
            $tileY = $h * 0.54;
            $xs = [$w * 0.07, $cx - $tileW / 2, $w * 0.93 - $tileW];
            foreach ($xs as $i => $tx) {
                $objects[] = $this->rect('story-pol-'.$i, $tx, $tileY + ($i === 1 ? -$unit * 0.02 : $unit * 0.02), $tileW, $tileH, '#FFFFFF', $unit * 0.015);
                $innerBg = $i === 1 ? '#F8FAFC' : ($i === 0 ? $c['colors'][1] : $c['colors'][2]);
                $objects[] = $this->rect('story-pol-in-'.$i, $tx + $unit * 0.016, $tileY + ($i === 1 ? -$unit * 0.004 : $unit * 0.036), $tileW - $unit * 0.032, $tileW - $unit * 0.032, $innerBg, $unit * 0.008);
            }
            $objects = array_merge($objects, $this->mark($c, $cx, $tileY + $tileW * 0.46, $unit * 0.18));

            $objects = array_merge($objects, $this->ctaPill('story-cta', $cx - $unit * 0.2, $h * 0.8, $c['cta'], $c, null, null, true));
            $objects[] = $this->text('story-handle', 'new-text', $c['handle'], $cx, $h - $m * 1.1, $unit * 0.026, $c['ink'], $c, 600, 160, 'center');

            return $objects;
        }

        // 3. story-spotlight-emblem (or spotlight, kinetic)
        if ($style === 'story-spotlight-emblem' || $style === 'spotlight' || $style === 'kinetic') {
            $objects[] = $this->circle('story-spot-ring', $cx, $h * 0.5, $w * 0.44, 'none', $c['accent'], $unit * 0.005, 0.4);
            $objects[] = $this->circle('story-spot-glow', $cx, $h * 0.5, $w * 0.34, $c['accent'], null, 0, 0.15);

            $brand = $this->block($c['brand'], $w * 0.72, $unit * 0.032, 1, $c, 700, 240, true);
            $objects[] = $this->text('story-brand', 'new-text', $brand['text'], $cx, $h * 0.085, $brand['size'], $c['accent'], $c, 700, 240, 'center');

            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.135, 3, $c, 800, 10, true);
            $objects[] = $this->text('story-headline', 'primary-text', $headline['text'], $cx, $h * 0.15, $headline['size'], $c['ink'], $c, 800, 10, 'center');

            $cardW = $w * 0.58;
            $cardH = $h * 0.28;
            $cardX = $cx - $cardW / 2;
            $cardY = $h * 0.39;
            $objects[] = $this->rect('story-card-back', $cardX + $unit * 0.025, $cardY + $unit * 0.025, $cardW, $cardH, $c['accent'], $unit * 0.024, 0.35);
            $objects[] = $this->rect('story-card-front', $cardX, $cardY, $cardW, $cardH, $c['dark'] ? '#111827' : '#FFFFFF', $unit * 0.024);
            $objects = array_merge($objects, $this->mark($c, $cx, $cardY + $cardH * 0.5, $unit * 0.26));

            $sub = $this->block($c['subhead'], $w * 0.8, $unit * 0.036, 2, $c, 500, 20);
            $objects[] = $this->text('story-sub', 'new-text', $sub['text'], $cx, $h * 0.71, $sub['size'], $c['muted'], $c, 500, 20, 'center');
            $objects = array_merge($objects, $this->ctaPill('story-cta', $cx - $unit * 0.2, $h * 0.8, $c['cta'], $c, null, null, true));
            $objects[] = $this->text('story-handle', 'new-text', $c['handle'], $cx, $h - $m * 1.0, $unit * 0.025, $c['muted'], $c, 600, 160, 'center');

            return $objects;
        }

        // 4. story-daily-cup (BrandCrowd Coffee Roast & Bakery Vertical Story)
        if ($style === 'story-daily-cup') {
            $objects[] = $this->rect('story-dc-bg', 0, 0, $w, $h, '#FFFFFF');

            $objects[] = $this->text('story-dc-eye', 'new-text', '★  FRESHLY ROASTED DAILY  ★', $cx, $h * 0.10, $unit * 0.024, '#B45309', $c, 800, 100, 'center');
            $objects[] = $this->text('story-dc-t1', 'new-text', 'Bites & Brews', $cx, $h * 0.15, $unit * 0.08, '#18181B', $c, 900, 20, 'center');

            $objects[] = [
                'id' => 'story-dc-latte',
                'type' => 'image',
                'left' => round($cx, 2),
                'top' => round($h * 0.40, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.54, 2),
                'height' => round($unit * 0.54, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/latte-coffee-cup-topdown.png',
            ];

            $cardW = $unit * 0.36;
            $objects[] = $this->rect('story-dc-card-b', $cx - $cardW / 2 + 4, $h * 0.62 - $cardW / 2 + 4, $cardW, $cardW, '#000000', 16, 0.08);
            $objects[] = $this->rect('story-dc-card', $cx - $cardW / 2, $h * 0.62 - $cardW / 2, $cardW, $cardW, '#FFFFFF', 16, 1.0, 0, '#E2E8F0', 1.5);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.62, $unit * 0.30));

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.045, 1, $c, 800, 40, true);
            $objects[] = $this->text('story-dc-brand', 'primary-text', $brand['text'], $cx, $h * 0.73, $brand['size'], '#18181B', $c, 800, 40, 'center');

            $objects[] = $this->text('story-dc-hours', 'new-text', 'OPEN DAILY · 8:00 AM - 10:00 PM', $cx, $h * 0.78, $unit * 0.024, '#64748B', $c, 700, 40, 'center');

            $objects = array_merge($objects, $this->ctaPill('story-dc-cta', $cx - $unit * 0.20, $h * 0.84, 'SWIPE UP TO ORDER', $c, '#18181B', '#FFFFFF', true));
            $objects[] = $this->text('story-dc-handle', 'new-text', ($c['handle'] ?? '@yourbrand').'  ·  '.($c['website'] ?? 'yourbrand.com'), $cx, $h - $m * 1.0, $unit * 0.024, '#0F172A', $c, 600, 40, 'center');

            return $objects;
        }

        // 5. story-were-open (BrandCrowd Diamond We're Open Vertical Story)
        if ($style === 'story-were-open') {
            $objects[] = $this->rect('story-wo-bg', 0, 0, $w, $h, '#F8FAFC');

            $objects[] = $this->text('story-wo-eye', 'new-text', 'PICK UP & DELIVERY AVAILABLE', $cx, $h * 0.12, $unit * 0.024, '#64748B', $c, 700, 80, 'center');

            $diaSize = $unit * 0.44;
            $objects[] = $this->rect('story-wo-diamond', $cx - $diaSize / 2, $h * 0.32 - $diaSize / 2, $diaSize, $diaSize, '#18181B', $unit * 0.015, 1.0, 45);

            $objects[] = $this->text('story-wo-yes', 'new-text', 'Yes!', $cx, $h * 0.23, $unit * 0.045, '#FFFFFF', $c, 700, 10, 'center');
            $objects[] = $this->text('story-wo-t1', 'new-text', "WE'RE", $cx, $h * 0.30, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');
            $objects[] = $this->text('story-wo-t2', 'new-text', 'OPEN', $cx, $h * 0.38, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');

            $cardW = $unit * 0.38;
            $objects[] = $this->rect('story-wo-card-b', $cx - $cardW / 2 + 4, $h * 0.58 - $cardW / 2 + 4, $cardW, $cardW, '#000000', 16, 0.08);
            $objects[] = $this->rect('story-wo-card', $cx - $cardW / 2, $h * 0.58 - $cardW / 2, $cardW, $cardW, '#FFFFFF', 16, 1.0, 0, '#E2E8F0', 1.5);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.58, $unit * 0.32));

            $brand = $this->block($c['brand'], $w * 0.8, $unit * 0.05, 1, $c, 900, 20, true);
            $objects[] = $this->text('story-wo-brand', 'primary-text', $brand['text'], $cx, $h * 0.70, $brand['size'], '#18181B', $c, 900, 20, 'center');

            $objects[] = $this->text('story-wo-hours', 'new-text', 'MON - SUN · 8:00 AM - 10:00 PM', $cx, $h * 0.76, $unit * 0.026, '#64748B', $c, 600, 30, 'center');

            $objects = array_merge($objects, $this->ctaPill('story-wo-cta', $cx - $unit * 0.20, $h * 0.83, 'SWIPE UP TO VISIT', $c, '#18181B', '#FFFFFF', true));
            $objects[] = $this->text('story-wo-handle', 'new-text', ($c['handle'] ?? '@yourbrand'), $cx, $h - $m * 1.0, $unit * 0.024, '#0F172A', $c, 600, 40, 'center');

            return $objects;
        }

        // 6. story-menu-qr (BrandCrowd Menu QR Code Vertical Story)
        if ($style === 'story-menu-qr') {
            $objects[] = $this->rect('story-mq-bg', 0, 0, $w, $h, '#0F172A');

            $cardW = $unit * 0.32;
            $objects[] = $this->rect('story-mq-card', $cx - $cardW / 2, $h * 0.16 - $cardW / 2, $cardW, $cardW, '#FFFFFF', 16, 1.0, 0, '#E2E8F0', 1.5);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.16, $unit * 0.26));

            $objects[] = $this->text('story-mq-eye', 'new-text', 'DIGITAL MENU EXPERIENCE', $cx, $h * 0.28, $unit * 0.024, $c['accent'], $c, 800, 100, 'center');
            $objects[] = $this->text('story-mq-hl', 'primary-text', 'VIEW OUR FULL MENU', $cx, $h * 0.33, $unit * 0.065, '#FFFFFF', $c, 900, 20, 'center');

            $qrSize = $unit * 0.44;
            $addQr = function (string $id, float $qcx, float $qcy, float $qsize, string $fg) use (&$objects, $c): void {
                $target = trim((string) ($c['qr_url'] ?? ''));
                if ($target === '' || $target === 'https://yourbrand.com') {
                    $website = trim((string) ($c['website'] ?? ''));
                    if ($website !== '' && $website !== 'yourbrand.com') {
                        $target = preg_match('#^https?://#i', $website) ? $website : 'https://'.$website;
                    }
                }
                $matrix = QrCodeMatrix::encode($target !== '' ? $target : 'https://yourbrand.com');
                if ($matrix === null) {
                    return;
                }
                $n = count($matrix);
                $quiet = 4;
                $total = $n + $quiet * 2;
                $module = $qsize / $total;
                $left = $qcx - $qsize / 2;
                $top = $qcy - $qsize / 2;
                $objects[] = $this->rect($id.'-bg', $left, $top, $qsize, $qsize, '#FFFFFF');
                $parts = [];
                for ($row = 0; $row < $n; $row++) {
                    $runStart = null;
                    for ($col = 0; $col <= $n; $col++) {
                        $dark = $col < $n && $matrix[$row][$col] === 1;
                        if ($dark && $runStart === null) {
                            $runStart = $col;
                        }
                        if (! $dark && $runStart !== null) {
                            $rx = $left + ($runStart + $quiet) * $module;
                            $ry = $top + ($row + $quiet) * $module;
                            $runWidth = ($col - $runStart) * $module;
                            $parts[] = 'M '.round($rx, 2).' '.round($ry, 2)
                                .' h '.round($runWidth, 2).' v '.round($module, 2)
                                .' h -'.round($runWidth, 2).' Z';
                            $runStart = null;
                        }
                    }
                }
                $objects[] = $this->path($id, implode(' ', $parts), $fg);
            };
            $addQr('story-mq-qr', $cx, $h * 0.54, $qrSize, '#000000');

            $objects[] = $this->text('story-mq-scan', 'new-text', 'SCAN WITH YOUR PHONE CAMERA', $cx, $h * 0.73, $unit * 0.026, $c['accent'], $c, 800, 60, 'center');
            $objects[] = $this->text('story-mq-sub', 'new-text', 'Artisan drinks, food & seasonal tasting menu', $cx, $h * 0.77, $unit * 0.024, '#94A3B8', $c, 500, 20, 'center');

            $objects = array_merge($objects, $this->ctaPill('story-mq-cta', $cx - $unit * 0.20, $h * 0.84, 'SWIPE UP TO BROWSE', $c, $c['accent'], '#FFFFFF', true));
            $objects[] = $this->text('story-mq-web', 'new-text', ($c['website'] ?? 'yourbrand.com'), $cx, $h - $m * 1.0, $unit * 0.024, '#E2E8F0', $c, 600, 40, 'center');

            return $objects;
        }

        // 7. story-giveaway-swipe (BrandCrowd Giveaway Contest Vertical Story)
        if ($style === 'story-giveaway-swipe') {
            $objects[] = $this->rect('story-gs-bg', 0, 0, $w, $h, '#18181B');

            $objects[] = $this->rect('story-gs-ribbon', 0, $h * 0.07, $w, $unit * 0.09, '#DC2626');
            $objects[] = $this->text('story-gs-rib-txt', 'new-text', '★  G I V E A W A Y   A L E R T  ★', $cx, $h * 0.108, $unit * 0.034, '#FFFFFF', $c, 900, 100, 'center');

            $objects[] = $this->text('story-gs-win', 'new-text', 'WIN A COMPLIMENTARY', $cx, $h * 0.20, $unit * 0.035, '#FBBF24', $c, 800, 60, 'center');
            $objects[] = $this->text('story-gs-prize', 'primary-text', '$150 DINING VOUCHER', $cx, $h * 0.26, $unit * 0.08, '#FFFFFF', $c, 900, 10, 'center');

            $cardW = $unit * 0.36;
            $objects[] = $this->rect('story-gs-card-b', $cx - $cardW / 2 + 4, $h * 0.44 - $cardW / 2 + 4, $cardW, $cardW, '#000000', 16, 0.08);
            $objects[] = $this->rect('story-gs-card', $cx - $cardW / 2, $h * 0.44 - $cardW / 2, $cardW, $cardW, '#FFFFFF', 16, 1.0, 0, '#E2E8F0', 1.5);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.44, $unit * 0.30));

            $boxW = $w * 0.82;
            $boxH = $unit * 0.24;
            $objects[] = $this->rect('story-gs-box', $cx - $boxW / 2, $h * 0.58, $boxW, $boxH, '#27272A', $unit * 0.02);

            $objects[] = $this->text('story-gs-s1', 'new-text', '1.  Follow  '.($c['handle'] ?? '@yourbrand'), $cx - $boxW * 0.42, $h * 0.62, $unit * 0.028, '#F8FAFC', $c, 700, 30);
            $objects[] = $this->text('story-gs-s2', 'new-text', '2.  Like & Tag 2 friends in comments', $cx - $boxW * 0.42, $h * 0.68, $unit * 0.028, '#F8FAFC', $c, 700, 30);
            $objects[] = $this->text('story-gs-s3', 'new-text', '3.  Share to your Story for bonus entry!', $cx - $boxW * 0.42, $h * 0.74, $unit * 0.028, '#FBBF24', $c, 700, 30);

            $objects = array_merge($objects, $this->ctaPill('story-gs-cta', $cx - $unit * 0.22, $h * 0.84, 'SWIPE UP TO ENTER', $c, '#DC2626', '#FFFFFF', true));
            $objects[] = $this->text('story-gs-terms', 'new-text', 'Winner announced this Sunday · Terms & Conditions apply', $cx, $h - $m * 1.0, $unit * 0.022, '#94A3B8', $c, 500, 20, 'center');

            return $objects;
        }

        // 8. story-minimal-progress (or bold, minimal)
        $objects[] = $this->circle('story-glow', $w * 0.8, $h * 0.18, $w * 0.55, $c['accent'], null, 0, 0.16);
        $objects[] = $this->circle('story-ring', $m * 0.4, $h * 0.62, $w * 0.32, 'none', $c['accent'], $unit * 0.004, 0.45);

        $markSize = $unit * 0.16;
        $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.2, $markSize));
        $brand = $this->block($c['brand'], $w * 0.65, $unit * 0.03, 1, $c, 700, 200, true);
        $objects[] = $this->text('story-brand', 'new-text', $brand['text'], $cx, $h * 0.3, $brand['size'], $c['accent'], $c, 700, 200, 'center');

        $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.125, 4, $c, 800, 0, true);
        $objects[] = $this->text('story-headline', 'primary-text', $headline['text'], $cx, $h * 0.4, $headline['size'], $c['ink'], $c, 800, 0, 'center');
        $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.036, 2, $c, 500, 20);
        $objects[] = $this->text('story-sub', 'new-text', $sub['text'], $cx, $h * 0.4 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.035, $sub['size'], $c['muted'], $c, 500, 20, 'center');

        $objects = array_merge($objects, $this->ctaPill('story-cta', $cx - $unit * 0.18, $h * 0.76, $c['cta'], $c, null, null, true));
        $objects[] = $this->rule('story-progress', $m, $h - $m * 0.55, $w - 2 * $m, $c['muted'], $unit * 0.003, 0.3);
        $objects[] = $this->rule('story-progress-fill', $m, $h - $m * 0.55, ($w - 2 * $m) * 0.33, $c['ink'], $unit * 0.003);
        $handle = $this->block($c['handle'], $w * 0.5, $unit * 0.024, 1, $c, 600, 160);
        $objects[] = $this->text('story-handle', 'new-text', $handle['text'], $cx, $h - $m * 1.1, $handle['size'], $c['muted'], $c, 600, 160, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutBanner(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = max(14.0, $unit * 0.075);
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'hero') {
            $markSize = $unit * 0.07;
            $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
            $brand = $this->block($c['brand'], $w * 0.3, $unit * 0.024, 1, $c, 700, 140, true);
            $objects[] = $this->text('hero-brand', 'new-text', $brand['text'], $m + $markSize + $unit * 0.02, $m + $markSize / 2 - $brand['size'] * 0.55, $brand['size'], $c['ink'], $c, 700, 140);

            $textW = $w * 0.48;
            $headline = $this->block($c['headline'], $textW, $unit * 0.1, 3, $c, 800, 0);
            $objects[] = $this->text('hero-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 0);
            $sub = $this->block($c['subhead'], $textW, $unit * 0.032, 2, $c, 500, 20);
            $objects[] = $this->text('hero-sub', 'new-text', $sub['text'], $m, $h * 0.3 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.03, $sub['size'], $c['muted'], $c, 500, 20);
            $objects = array_merge($objects, $this->ctaPill('hero-cta', $m, $h * 0.72, $c['cta'], $c));

            $cardX = $w * 0.56;
            $cardY = $h * 0.18;
            $cardW = $w - $cardX - $m;
            $cardH = $h * 0.64;
            $objects[] = $this->rect('hero-card', $cardX, $cardY, $cardW, $cardH, $c['dark'] ? $c['colors'][1] : '#FFFFFF', $unit * 0.02);
            $objects[] = $this->rect('hero-card-bar', $cardX, $cardY, $cardW, $unit * 0.04, $c['support'], $unit * 0.01, 0.5);
            foreach (['#F87171', '#FBBF24', '#34D399'] as $index => $dot) {
                $objects[] = $this->circle('hero-dot-'.$index, $cardX + $unit * 0.035 + $index * $unit * 0.025, $cardY + $unit * 0.02, $unit * 0.006, $dot);
            }
            $objects = array_merge($objects, $this->mark($c, $cardX + $cardW / 2, $cardY + $cardH * 0.42, $unit * 0.16));
            for ($i = 0; $i < 3; $i++) {
                $objects[] = $this->rect('hero-line-'.$i, $cardX + $cardW * 0.2, $cardY + $cardH * 0.66 + $i * $unit * 0.028, $cardW * (0.6 - $i * 0.12), $unit * 0.012, $c['muted'], $unit * 0.006, 0.35);
            }

            return $objects;
        }

        if ($variant === 'overlay') {
            $bracket = $unit * 0.09;
            $stroke = $unit * 0.004;
            $objects[] = $this->rule('overlay-tl-h', $m, $m, $bracket, $c['accent'], $stroke);
            $objects[] = $this->rect('overlay-tl-v', $m, $m, $stroke, $bracket, $c['accent']);
            $objects[] = $this->rule('overlay-br-h', $w - $m - $bracket, $h - $m, $bracket, $c['accent'], $stroke);
            $objects[] = $this->rect('overlay-br-v', $w - $m - $stroke, $h - $m - $bracket, $stroke, $bracket, $c['accent']);
            $objects[] = $this->rect('overlay-lower-third', $m, $h * 0.7, $w * 0.46, $h * 0.18, $c['ink'], $unit * 0.012, 0.92);
            $headline = $this->block($c['headline'], $w * 0.42, $unit * 0.05, 2, $c, 800, 10, true);
            $objects[] = $this->text('overlay-headline', 'primary-text', $headline['text'], $m + $unit * 0.025, $h * 0.7 + $unit * 0.025, $headline['size'], Color::readableInk($c['ink']), $c, 800, 10);
            $objects[] = $this->text('overlay-sub', 'new-text', $c['subhead'], $m + $unit * 0.025, $h * 0.7 + $unit * 0.025 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.012, $unit * 0.024, $c['accent'], $c, 500, 40);
            $objects[] = $this->text('overlay-handle', 'new-text', $c['handle'], $w - $m, $h - $m * 0.8, $unit * 0.024, $c['ink'], $c, 600, 120, 'center');

            return $objects;
        }

        if ($variant === 'twitch') {
            $objects[] = $this->rect('twitch-bar-left', 0, 0, $w * 0.012, $h, $c['accent']);
            $objects[] = $this->rect('twitch-bar-right', $w * 0.988, 0, $w * 0.012, $h, $c['accent']);
            $headline = $this->block($c['headline'], $w * 0.72, $unit * 0.12, 2, $c, 800, 20, true);
            $objects[] = $this->text('twitch-headline', 'primary-text', $headline['text'], $w / 2, $h * 0.28, $headline['size'], $c['ink'], $c, 800, 20, 'center');
            $sub = $this->block($c['subhead'], $w * 0.7, $unit * 0.03, 1, $c, 500, 160, true);
            $objects[] = $this->text('twitch-sub', 'new-text', $sub['text'], $w / 2, $h * 0.28 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.03, $sub['size'], $c['accent'], $c, 500, 160, 'center');

            return $objects;
        }

        if ($variant === 'notion') {
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.09, 2, $c, 800, 10);
            $objects[] = $this->text('notion-headline', 'primary-text', $headline['text'], $m, $h * 0.38, $headline['size'], $c['ink'], $c, 800, 10);
            $objects[] = $this->text('notion-brand', 'new-text', $c['brand'], $m, $h * 0.24, $unit * 0.024, $c['accent'], $c, 700, 200, 'left');

            return $objects;
        }

        if ($variant === 'ads') {
            $objects[] = $this->text('ads-brand', 'new-text', $c['brand'], $m, $m * 0.65, $unit * 0.032, $c['ink'], $c, 700, 120, 'left');
            $headline = $this->block($c['headline'], $w * 0.52, $unit * 0.09, 2, $c, 800, 10);
            $objects[] = $this->text('ads-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 10);
            $sub = $this->block($c['subhead'], $w * 0.48, $unit * 0.028, 2, $c, 500, 20);
            $objects[] = $this->text('ads-sub', 'new-text', $sub['text'], $m, $h * 0.3 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.02, $sub['size'], $c['muted'], $c, 500, 20);
            $objects = array_merge($objects, $this->ctaPill('ads-cta', $w - $m - $unit * 0.34, $h * 0.42, $c['cta'], $c, null, null, true));

            return $objects;
        }

        // Cover / shopify / default wide banner — rich style-specific cover compositions (Facebook Cover, LinkedIn, etc.).
        $style = (string) ($c['style'] ?? 'editorial');

        // Helper: Authentic BrandCrowd Framed Logo Badge (Mark + Brand Text in White Card)
        $logoBadge = function (string $id, float $cx, float $cy, float $size, bool $withBorder = true, bool $circle = false) use (&$objects, $c): void {
            if ($circle) {
                $objects[] = $this->circle($id.'-card', $cx, $cy, $size * 0.52, '#FFFFFF', $withBorder ? '#E2E8F0' : null, $withBorder ? 1.5 : 0);
            } else {
                $objects[] = $this->rect($id.'-card', $cx - $size * 0.52, $cy - $size * 0.52, $size * 1.04, $size * 1.04, '#FFFFFF', $size * 0.08, 1.0, 0, $withBorder ? '#E2E8F0' : null, $withBorder ? 1.5 : 0);
            }
            $isPngMark = is_array($c['symbol'] ?? null) && (($c['symbol']['asset_type'] ?? '') === 'png' || ! empty($c['symbol']['image_url']));
            if ($isPngMark) {
                // If it's a rich illustrated crest/emblem PNG, center it proudly inside the badge filling the card
                $objects = array_merge($objects, $this->mark($c, $cx, $cy, $size * 0.90));
            } else {
                $objects = array_merge($objects, $this->mark($c, $cx, $cy - $size * 0.10, $size * 0.54));
                $bText = $this->block($c['brand'], $size * 0.90, $size * 0.12, 2, $c, 800, 40, true);
                $objects[] = $this->text($id.'-txt', 'primary-text', $bText['text'], $cx, $cy + $size * 0.26, $bText['size'], '#0F172A', $c, 800, 40, 'center');
            }
        };

        // Helper: scannable QR code with a four-module quiet zone.
        $addQr = function (string $id, float $cx, float $cy, float $size, string $fg) use (&$objects, $c): void {
            $target = trim((string) ($c['qr_url'] ?? ''));

            if ($target === '' || $target === 'https://yourbrand.com') {
                $website = trim((string) ($c['website'] ?? ''));

                if ($website !== '' && $website !== 'yourbrand.com') {
                    $target = preg_match('#^https?://#i', $website) ? $website : 'https://'.$website;
                }
            }

            $matrix = QrCodeMatrix::encode($target !== '' ? $target : 'https://yourbrand.com');

            if ($matrix === null) {
                return;
            }

            $n = count($matrix);
            $quiet = 4;
            $total = $n + $quiet * 2;
            $module = $size / $total;
            $left = $cx - $size / 2;
            $top = $cy - $size / 2;

            $objects[] = $this->rect($id.'-bg', $left, $top, $size, $size, '#FFFFFF');

            $parts = [];

            for ($row = 0; $row < $n; $row++) {
                $runStart = null;

                for ($col = 0; $col <= $n; $col++) {
                    $dark = $col < $n && $matrix[$row][$col] === 1;

                    if ($dark && $runStart === null) {
                        $runStart = $col;
                    }

                    if (! $dark && $runStart !== null) {
                        $x = $left + ($runStart + $quiet) * $module;
                        $y = $top + ($row + $quiet) * $module;
                        $runWidth = ($col - $runStart) * $module;
                        $parts[] = 'M '.round($x, 2).' '.round($y, 2)
                            .' h '.round($runWidth, 2).' v '.round($module, 2)
                            .' h -'.round($runWidth, 2).' Z';
                        $runStart = null;
                    }
                }
            }

            $objects[] = $this->path($id, implode(' ', $parts), $fg);
        };

        // 1. BITES & BREWS DOODLE (BrandCrowd Café / Food Style)
        if ($style === 'bites-brews-doodle') {
            $objects[] = $this->rect('cov-bb-bg', 0, 0, $w, $h, '#FFFFFF');
            $objects[] = $this->text('cov-bb-t1', 'new-text', 'Bites &', $w * 0.35, $h * 0.22, $unit * 0.09, '#18181B', $c, 800, 10, 'center');
            $objects[] = $this->text('cov-bb-t2', 'new-text', 'Brews', $w * 0.35, $h * 0.37, $unit * 0.11, '#18181B', $c, 900, 20, 'center');

            // Steaming mug doodle
            $objects[] = $this->rect('cov-bb-cup', $w * 0.35 - $unit * 0.045, $h * 0.51, $unit * 0.09, $unit * 0.09, '#1E293B', $unit * 0.02);
            $objects[] = $this->path('cov-bb-handle', sprintf('M %d %d C %d %d %d %d %d %d', (int) ($w * 0.35 + $unit * 0.045), (int) ($h * 0.53), (int) ($w * 0.35 + $unit * 0.085), (int) ($h * 0.53), (int) ($w * 0.35 + $unit * 0.085), (int) ($h * 0.58), (int) ($w * 0.35 + $unit * 0.045), (int) ($h * 0.58)), 'none', '#1E293B', 3);
            $objects[] = $this->path('cov-bb-steam1', sprintf('M %d %d Q %d %d %d %d', (int) ($w * 0.33), (int) ($h * 0.49), (int) ($w * 0.32), (int) ($h * 0.45), (int) ($w * 0.34), (int) ($h * 0.42)), 'none', '#94A3B8', 2);
            $objects[] = $this->path('cov-bb-steam2', sprintf('M %d %d Q %d %d %d %d', (int) ($w * 0.37), (int) ($h * 0.49), (int) ($w * 0.36), (int) ($h * 0.45), (int) ($w * 0.38), (int) ($h * 0.42)), 'none', '#94A3B8', 2);
            // Golden Cookie with chocolate chips
            $objects[] = $this->circle('cov-bb-cookie', $w * 0.28, $h * 0.56, $unit * 0.046, '#F59E0B', '#B45309', 2.5);
            $objects[] = $this->circle('cov-bb-chip1', $w * 0.27, $h * 0.54, 3, '#451A03');
            $objects[] = $this->circle('cov-bb-chip2', $w * 0.29, $h * 0.57, 3.5, '#451A03');
            $objects[] = $this->circle('cov-bb-chip3', $w * 0.27, $h * 0.58, 2.5, '#451A03');

            $objects[] = $this->text('cov-bb-star1', 'new-text', '✦', $w * 0.22, $h * 0.44, $unit * 0.05, '#18181B', $c, 700);
            $objects[] = $this->text('cov-bb-star2', 'new-text', '✦', $w * 0.44, $h * 0.24, $unit * 0.04, '#18181B', $c, 700);

            $objects[] = $this->text('cov-bb-order', 'new-text', 'Order now at '.($c['website'] ?? 'website.com'), $w * 0.35, $h * 0.76, $unit * 0.026, '#64748B', $c, 600, 20, 'center');

            $logoBadge('cov-bb-logo', $w * 0.78, $h * 0.50, $unit * 0.46);

            return $objects;
        }

        // 2. DIAMOND WE'RE OPEN (BrandCrowd Rotated Diamond Style)
        if ($style === 'were-open-diamond') {
            $objects[] = $this->rect('cov-dia-bg', 0, 0, $w, $h, '#F8FAFC');

            $objects[] = $this->text('cov-dia-kicker', 'new-text', 'Pick up & Delivery Available', $w * 0.38, $h * 0.16, $unit * 0.024, '#64748B', $c, 700, 60, 'center');

            $diaSize = $unit * 0.46;
            $objects[] = $this->rect('cov-dia-box', $w * 0.38 - $diaSize / 2, $h * 0.52 - $diaSize / 2, $diaSize, $diaSize, '#18181B', $unit * 0.015, 1.0, 45);

            $objects[] = $this->text('cov-dia-yes', 'new-text', 'Yes!', $w * 0.38, $h * 0.38, $unit * 0.045, '#FFFFFF', $c, 700, 10, 'center');
            $objects[] = $this->text('cov-dia-hl1', 'new-text', "WE'RE", $w * 0.38, $h * 0.48, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');
            $objects[] = $this->text('cov-dia-hl2', 'new-text', 'OPEN', $w * 0.38, $h * 0.58, $unit * 0.075, '#FFFFFF', $c, 900, 10, 'center');

            $objects[] = $this->rule('cov-dia-dash', $w * 0.22, $h * 0.52, $unit * 0.08, '#CBD5E1', 2);
            $objects[] = $this->rule('cov-dia-dash2', $w * 0.46, $h * 0.52, $unit * 0.08, '#CBD5E1', 2);

            $logoBadge('cov-dia-logo', $w * 0.78, $h * 0.50, $unit * 0.46);

            return $objects;
        }

        // 3. MEGAPHONE YES WE ARE BACK (BrandCrowd Megaphone Announcement)
        if ($style === 'yes-we-are-back') {
            $objects[] = $this->rect('cov-back-bg', 0, 0, $w, $h, '#F8FAFC');

            $logoBadge('cov-back-logo', $w * 0.36, $h * 0.50, $unit * 0.46);

            $objects[] = $this->path('cov-back-mega', sprintf('M %d %d L %d %d L %d %d L %d %d Z', (int) ($w * 0.65), (int) ($h * 0.24), (int) ($w * 0.70), (int) ($h * 0.18), (int) ($w * 0.70), (int) ($h * 0.32), (int) ($w * 0.65), (int) ($h * 0.28)), '#18181B');
            $objects[] = $this->circle('cov-back-horn', $w * 0.64, $h * 0.26, $unit * 0.024, '#18181B');
            $objects[] = $this->path('cov-back-handle', sprintf('M %d %d L %d %d', (int) ($w * 0.67), (int) ($h * 0.29), (int) ($w * 0.66), (int) ($h * 0.35)), 'none', '#18181B', 3);

            $objects[] = $this->circle('cov-back-wave1', $w * 0.71, $h * 0.25, $unit * 0.025, 'none', '#18181B', 2);
            $objects[] = $this->circle('cov-back-wave2', $w * 0.71, $h * 0.25, $unit * 0.045, 'none', '#18181B', 2);

            $objects[] = $this->text('cov-back-hl', 'primary-text', 'YES, WE ARE BACK', $w * 0.68, $h * 0.43, $unit * 0.092, '#18181B', $c, 900, 10, 'center');
            $objects[] = $this->rule('cov-back-rule', $w * 0.54, $h * 0.58, $w * 0.28, '#18181B', 2);
            $objects[] = $this->text('cov-back-sub', 'new-text', 'Limited capacity only   ·   Reservations recommended', $w * 0.68, $h * 0.64, $unit * 0.028, '#64748B', $c, 600, 20, 'center');

            return $objects;
        }

        // 4. LUXURY MENU QR PLUM (BrandCrowd Deep Wine/Plum Menu Cover)
        if ($style === 'menu-qr-plum') {
            $objects[] = $this->rect('cov-plum-bg', 0, 0, $w, $h, '#4A0E2E');

            $objects[] = $this->text('cov-plum-v', 'new-text', 'View Our', $w * 0.25, $h * 0.25, $unit * 0.08, '#FBCFE8', $c, 400, 10, 'center');
            $objects[] = $this->text('cov-plum-m', 'primary-text', 'MENU', $w * 0.25, $h * 0.44, $unit * 0.13, '#FFFFFF', $c, 900, 100, 'center');
            $objects[] = $this->text('cov-plum-sub', 'new-text', 'DELIVERY & PICK UP AVAILABLE', $w * 0.25, $h * 0.68, $unit * 0.025, '#F472B6', $c, 700, 80, 'center');

            $addQr('cov-plum-qr', $w * 0.64, $h * 0.50, $unit * 0.42, '#4A0E2E');

            $objects[] = $this->text('cov-plum-sh1', 'new-text', 'SCAN HERE', $w * 0.54, $h * 0.50, $unit * 0.024, '#FBCFE8', $c, 700, 80, 'center', 1.0, ['angle' => -90]);
            $objects[] = $this->text('cov-plum-sh2', 'new-text', 'SCAN HERE', $w * 0.74, $h * 0.50, $unit * 0.024, '#FBCFE8', $c, 700, 80, 'center', 1.0, ['angle' => 90]);

            $logoBadge('cov-plum-logo', $w * 0.88, $h * 0.50, $unit * 0.38, false, true);

            return $objects;
        }

        // 5. CURVED RIBBON MENU QR (BrandCrowd Silver Ribbon Menu Cover)
        if ($style === 'menu-qr-ribbon') {
            $objects[] = $this->rect('cov-rib-bg', 0, 0, $w, $h, '#F1F5F9');

            $ribW = $w * 0.86;
            $ribH = $h * 0.70;
            $objects[] = $this->rect('cov-rib-pill', ($w - $ribW) / 2, ($h - $ribH) / 2, $ribW, $ribH, '#FFFFFF', $ribH / 2, 1.0, 0, '#E2E8F0', 1.5);

            $logoBadge('cov-rib-logo', $w * 0.24, $h * 0.50, $unit * 0.40, false, true);

            $objects[] = $this->text('cov-rib-v', 'new-text', 'View Our', $w * 0.50, $h * 0.27, $unit * 0.07, '#64748B', $c, 400, 10, 'center');
            $objects[] = $this->text('cov-rib-m', 'primary-text', 'MENU', $w * 0.50, $h * 0.44, $unit * 0.11, '#1E293B', $c, 900, 80, 'center');
            $objects[] = $this->text('cov-rib-sub', 'new-text', 'DELIVERY & PICK UP AVAILABLE', $w * 0.50, $h * 0.67, $unit * 0.024, '#94A3B8', $c, 700, 60, 'center');

            $addQr('cov-rib-qr', $w * 0.77, $h * 0.50, $unit * 0.38, '#1E293B');
            $objects[] = $this->text('cov-rib-sh', 'new-text', 'SCAN HERE', $w * 0.70, $h * 0.50, $unit * 0.022, '#64748B', $c, 700, 60, 'center', 1.0, ['angle' => -90]);

            return $objects;
        }

        // 6. CLASSIC OPEN DAILY DAMASK (BrandCrowd Sketch & Wallpaper Cover)
        if ($style === 'open-daily-damask') {
            $objects[] = $this->rect('cov-dam-bg', 0, 0, $w, $h, '#FAFAFA');

            for ($x = 60; $x < $w; $x += 180) {
                for ($y = 40; $y < $h; $y += 120) {
                    $objects[] = $this->circle('cov-dam-pt-'.$x.'-'.$y, $x, $y, 3, '#E2E8F0');
                    $objects[] = $this->circle('cov-dam-rg-'.$x.'-'.$y, $x, $y, 14, 'none', '#E2E8F0', 1);
                }
            }

            $objects[] = $this->text('cov-dam-kicker', 'new-text', 'Pick up & Delivery Available', $w * 0.48, $h * 0.15, $unit * 0.024, '#64748B', $c, 700, 40, 'center');
            $objects[] = $this->text('cov-dam-hl1', 'new-text', "WE'RE", $w * 0.48, $h * 0.26, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-dam-hl2', 'new-text', 'OPEN', $w * 0.48, $h * 0.40, $unit * 0.092, '#0F172A', $c, 900, 20, 'center');

            $objects[] = [
                'id' => 'cov-dam-sketch',
                'type' => 'image',
                'left' => round($w * 0.48, 2),
                'top' => round($h * 0.58, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.36, 2),
                'height' => round($unit * 0.26, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/vintage-coffee-frappe-sketch.png',
            ];

            $objects[] = $this->text('cov-dam-hours', 'new-text', 'OPEN DAILY 07AM - 09PM', $w * 0.48, $h * 0.74, $unit * 0.028, '#334155', $c, 800, 80, 'center');
            $objects[] = $this->text('cov-dam-loc', 'new-text', '📍  Location & Valet Available', $w * 0.48, $h * 0.83, $unit * 0.024, '#64748B', $c, 600, 20, 'center');

            $logoBadge('cov-dam-logo', $w * 0.80, $h * 0.50, $unit * 0.42);

            return $objects;
        }

        // 7. DRY BRUSH STROKE GRUNGE (BrandCrowd High Contrast Paint Strokes)
        if ($style === 'brush-stroke-grunge') {
            $objects[] = $this->rect('cov-bs-bg', 0, 0, $w, $h, '#FFFFFF');

            $objects[] = $this->path('cov-bs-top1', sprintf('M 0 0 L %d 0 L %d %d L %d %d L 0 %d Z', (int) ($w * 0.42), (int) ($w * 0.38), (int) ($h * 0.32), (int) ($w * 0.20), (int) ($h * 0.18), (int) ($h * 0.38)), '#0A0A0B');
            $objects[] = $this->path('cov-bs-top2', sprintf('M %d 0 L %d 0 L %d %d L %d %d Z', (int) ($w * 0.58), (int) $w, (int) $w, (int) ($h * 0.42), (int) ($w * 0.64), (int) ($h * 0.28)), '#0A0A0B');

            $objects[] = $this->path('cov-bs-bot1', sprintf('M 0 %d L %d %d L %d %d L 0 %d Z', (int) ($h * 0.65), (int) ($w * 0.36), (int) ($h * 0.75), (int) ($w * 0.44), (int) $h, (int) $h), '#0A0A0B');
            $objects[] = $this->path('cov-bs-bot2', sprintf('M %d %d L %d %d L %d %d L %d %d Z', (int) ($w * 0.60), (int) $h, (int) ($w * 0.66), (int) ($h * 0.68), (int) $w, (int) ($h * 0.60), (int) $w, (int) $h), '#0A0A0B');

            for ($s = 0; $s < 6; $s++) {
                $objects[] = $this->rect('cov-bs-sp-'.$s, $w * (0.05 + $s * 0.05), $h * (0.2 + ($s % 3) * 0.2), $unit * 0.015, $unit * 0.12, '#0A0A0B', 0, 0.9, ($s % 2 === 0 ? 30 : -25));
                $objects[] = $this->rect('cov-bs-sp2-'.$s, $w * (0.65 + $s * 0.05), $h * (0.2 + ($s % 3) * 0.2), $unit * 0.015, $unit * 0.12, '#0A0A0B', 0, 0.9, ($s % 2 === 0 ? -30 : 25));
            }

            $logoBadge('cov-bs-logo', $w * 0.50, $h * 0.50, $unit * 0.50);

            return $objects;
        }

        // 8. KINETIC GIVEAWAY PROMO (BrandCrowd Outlined Typography Cover)
        if ($style === 'kinetic-giveaway') {
            $objects[] = $this->rect('cov-kg-bg', 0, 0, $w, $h, '#FFFFFF');

            for ($m1 = 0; $m1 < 6; $m1++) {
                $objects[] = $this->rule('cov-kg-mesh-'.$m1, 0, $m1 * 22, $w * 0.25, '#CBD5E1', 2, 0.4);
                $objects[] = $this->rule('cov-kg-mesh2-'.$m1, $w * 0.75, $h - $m1 * 22, $w * 0.25, '#CBD5E1', 2, 0.4);
            }

            $objects[] = $this->text('cov-kg-lt', 'new-text', 'LIMITED TIME', $w * 0.32, $h * 0.22, $unit * 0.075, '#64748B', $c, 900, 40, 'center');
            $objects[] = $this->text('cov-kg-g1', 'new-text', 'GIVEAWAY', $w * 0.32, $h * 0.37, $unit * 0.095, '#CBD5E1', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-kg-g2', 'primary-text', 'GIVEAWAY', $w * 0.32, $h * 0.54, $unit * 0.105, '#0F172A', $c, 900, 20, 'center');

            $objects[] = $this->text('cov-kg-rules', 'new-text', 'Follow & tag friends to enter  ·  Winner chosen weekly', $w * 0.32, $h * 0.76, $unit * 0.026, '#64748B', $c, 600, 20, 'center');

            $logoBadge('cov-kg-logo', $w * 0.78, $h * 0.50, $unit * 0.46);

            return $objects;
        }

        // 9. MINIMALIST DREAM BELIEVE BUILD (BrandCrowd Brick Wall Typography Cover)
        if ($style === 'dream-believe-build') {
            $objects[] = $this->rect('cov-dbb-bg', 0, 0, $w, $h, '#F8FAFC');

            for ($bk = 0; $bk < 12; $bk++) {
                $objects[] = $this->rule('cov-dbb-line-'.$bk, 0, $bk * ($h / 12), $w, '#E2E8F0', 1, 0.5);
            }

            $objects[] = $this->text('cov-dbb-1', 'new-text', 'DREAM IT.', $w * 0.34, $h * 0.23, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-dbb-2', 'new-text', 'BELIEVE IT.', $w * 0.34, $h * 0.42, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-dbb-3', 'new-text', 'BUILD IT.', $w * 0.34, $h * 0.61, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');

            $logoBadge('cov-dbb-logo', $w * 0.76, $h * 0.50, $unit * 0.48);

            return $objects;
        }

        // 10. ISOMETRIC 3D CUBE OPEN (BrandCrowd Cube Pattern Cover)
        if ($style === 'geometric-cube-open') {
            $objects[] = $this->rect('cov-cube-bg', 0, 0, $w, $h, '#F1F5F9');

            for ($cx = 0; $cx < $w + 80; $cx += 90) {
                for ($cy = 0; $cy < $h + 80; $cy += 80) {
                    $objects[] = $this->rect('cov-cube-t-'.$cx.'-'.$cy, $cx, $cy, 45, 40, '#E2E8F0', 0, 0.4);
                    $objects[] = $this->rect('cov-cube-b-'.$cx.'-'.$cy, $cx + 45, $cy, 45, 40, '#CBD5E1', 0, 0.3);
                }
            }

            $cardW = $unit * 0.46;
            $cardH = $h * 0.82;
            $objects[] = $this->rect('cov-cube-card', $w * 0.45 - $cardW / 2, ($h - $cardH) / 2, $cardW, $cardH, '#FFFFFF', $unit * 0.02, 1.0, 0, '#E2E8F0', 1.5);

            $objects[] = $this->text('cov-cube-hl1', 'new-text', "WE'RE", $w * 0.45, $h * 0.20, $unit * 0.065, '#000000', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-cube-hl2', 'new-text', 'OPEN', $w * 0.45, $h * 0.30, $unit * 0.075, '#000000', $c, 900, 20, 'center');

            $objects[] = [
                'id' => 'cov-cube-latte-art',
                'type' => 'image',
                'left' => round($w * 0.45, 2),
                'top' => round($h * 0.52, 2),
                'originX' => 'center',
                'originY' => 'center',
                'width' => round($unit * 0.28, 2),
                'height' => round($unit * 0.28, 2),
                'scaleX' => 1,
                'scaleY' => 1,
                'angle' => 0,
                'src' => '/storage/symbols/latte-coffee-cup-topdown.png',
            ];

            $objects[] = $this->text('cov-cube-hours', 'new-text', 'MON - SUN: 8AM - 10PM', $w * 0.45, $h * 0.74, $unit * 0.024, '#18181B', $c, 800, 60, 'center');

            $logoBadge('cov-cube-logo', $w * 0.80, $h * 0.50, $unit * 0.42);

            return $objects;
        }

        // 11. WELCOME TO OUR PAGE (BrandCrowd Cleanest Modern Banner)
        if ($style === 'welcome-clean') {
            $objects[] = $this->rect('cov-wel-bg', 0, 0, $w, $h, '#FFFFFF');

            $logoBadge('cov-wel-logo', $w * 0.36, $h * 0.50, $unit * 0.48);

            $objects[] = $this->text('cov-wel-hl1', 'primary-text', 'WELCOME', $w * 0.68, $h * 0.26, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');
            $objects[] = $this->text('cov-wel-hl2', 'primary-text', 'TO OUR PAGE!', $w * 0.68, $h * 0.42, $unit * 0.088, '#0F172A', $c, 900, 20, 'center');
            $objects[] = $this->rule('cov-wel-rule', $w * 0.54, $h * 0.58, $w * 0.28, '#0F172A', 2);
            $objects[] = $this->text('cov-wel-sub', 'new-text', 'Mon - Sun: 8:00 AM - 10:00 PM  ·  '.($c['website'] ?? 'www.yourbrand.com'), $w * 0.68, $h * 0.64, $unit * 0.028, '#64748B', $c, 500, 20, 'center');
            $objects[] = $this->text('cov-wel-loc', 'new-text', '📍  Visit us in-store or order online', $w * 0.68, $h * 0.74, $unit * 0.026, '#334155', $c, 600, 30, 'center');

            return $objects;
        }

        // 1. AGENCY SPLIT SHOWCASE
        if ($style === 'agency-split') {
            $splitX = $w * 0.58;
            $objects[] = $this->path('cov-split-bg', sprintf('M %d 0 L %d 0 L %d %d L %d %d Z', (int) ($splitX + $w * 0.05), (int) $w, (int) $w, (int) $h, (int) ($splitX - $w * 0.05), (int) $h), [
                'type' => 'linear',
                'angle' => 135,
                'stops' => [
                    ['offset' => 0, 'color' => $c['colors'][0]],
                    ['offset' => 1, 'color' => $c['colors'][1] ?? $c['colors'][0]],
                ],
            ]);
            $panelCx = $splitX + ($w - $splitX) * 0.52;
            $objects[] = $this->circle('cov-split-ring', $panelCx, $h * 0.44, $unit * 0.38, 'none', '#FFFFFF', $unit * 0.008, 0.22);
            $objects[] = $this->circle('cov-split-disc', $panelCx, $h * 0.44, $unit * 0.28, '#FFFFFF', null, 0, 0.95);
            $objects = array_merge($objects, $this->mark($c, $panelCx, $h * 0.44, $unit * 0.38));
            $brandText = $this->block($c['brand'], ($w - $splitX) * 0.82, $unit * 0.046, 1, $c, 700, 140, true);
            $objects[] = $this->text('cov-split-brand', 'new-text', $brandText['text'], $panelCx, $h * 0.77, $brandText['size'], '#FFFFFF', $c, 700, 140, 'center');

            $badgeW = $unit * 0.45;
            $objects[] = $this->rect('cov-agency-badge-bg', $m, $h * 0.12, $badgeW, $unit * 0.054, $c['accent'], $unit * 0.027, 0.14);
            $objects[] = $this->text('cov-agency-badge-txt', 'new-text', '✦  FULL SERVICE CREATIVE STUDIO', $m + $badgeW / 2, $h * 0.12 + $unit * 0.015, $unit * 0.024, $c['accent'], $c, 700, 80, 'center');

            $headlineText = ! empty($c['headline']) ? $c['headline'] : 'We Build High-Impact Digital Brands';
            $headline = $this->block($headlineText, $splitX - $m - $unit * 0.08, $unit * 0.092, 2, $c, 800, 0, true);
            $objects[] = $this->text('cov-agency-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], $c['ink'], $c, 800, 0);

            $subheadText = ! empty($c['subhead']) ? $c['subhead'] : 'Strategy · Branding · Web Development · Growth';
            $sub = $this->block($subheadText, $splitX - $m - $unit * 0.08, $unit * 0.038, 1, $c, 500, 20);
            $objects[] = $this->text('cov-agency-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], $c['muted'], $c, 500, 20);

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'Start Your Project →';
            $objects = array_merge($objects, $this->ctaPill('cov-agency-cta', $m, $h * 0.65, $ctaLabel, $c, $c['accent'], $unit * 0.032));

            $web = ! empty($c['website']) ? $c['website'] : 'www.yourbrand.com';
            $mail = ! empty($c['email']) ? $c['email'] : 'hello@yourbrand.com';
            $objects[] = $this->text('cov-agency-contact', 'new-text', $web.'    ·    '.$mail, $m, $h * 0.82, $unit * 0.028, $c['muted'], $c, 600, 30);

            return $objects;
        }

        // 2. ESPORTS CYBER GAMING
        if ($style === 'esports-cyber') {
            $objects[] = $this->rect('cov-esports-bg', 0, 0, $w, $h, '#0B0F19');
            $objects[] = $this->path('cov-cyber-poly1', sprintf('M %d 0 L %d 0 L %d %d L %d %d Z', (int) ($w * 0.65), (int) ($w * 0.85), (int) ($w * 0.75), (int) $h, (int) ($w * 0.55), (int) $h), '#00F0FF', null, 0, 0.12);
            $objects[] = $this->path('cov-cyber-poly2', sprintf('M %d 0 L %d 0 L %d %d L %d %d Z', (int) ($w * 0.82), (int) $w, (int) ($w * 0.92), (int) $h, (int) ($w * 0.74), (int) $h), '#FF007A', null, 0, 0.15);
            $objects[] = $this->rule('cov-cyber-line-top', 0, 0, $w, '#00F0FF', $unit * 0.008);

            $objects[] = $this->rect('cov-cyber-live-bg', $m, $h * 0.12, $unit * 0.42, $unit * 0.052, '#EF4444', $unit * 0.026);
            $objects[] = $this->circle('cov-cyber-live-dot', $m + $unit * 0.032, $h * 0.12 + $unit * 0.026, $unit * 0.012, '#FFFFFF');
            $objects[] = $this->text('cov-cyber-live-txt', 'new-text', 'LIVE ON TWITCH & YT', $m + $unit * 0.055, $h * 0.12 + $unit * 0.015, $unit * 0.024, '#FFFFFF', $c, 800, 100);

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'DOMINATE THE ARENA';
            $headline = $this->block($hlText, $w * 0.54, $unit * 0.096, 2, $c, 900, 20, true);
            $objects[] = $this->text('cov-cyber-hl', 'primary-text', $headline['text'], $m, $h * 0.24, $headline['size'], '#FFFFFF', $c, 900, 20);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Official Competitive Team · Daily Streams · Highlights · Merch';
            $sub = $this->block($subText, $w * 0.52, $unit * 0.034, 1, $c, 500, 30);
            $objects[] = $this->text('cov-cyber-sub', 'new-text', $sub['text'], $m, $h * 0.55, $sub['size'], '#94A3B8', $c, 500, 30);

            $objects[] = $this->rect('cov-cyber-st1', $m, $h * 0.66, $unit * 0.28, $unit * 0.06, '#1E293B', $unit * 0.01, 1.0, 0, '#00F0FF', 1.5);
            $objects[] = $this->text('cov-cyber-st1-t', 'new-text', '🏆  15X CHAMPIONS', $m + $unit * 0.14, $h * 0.66 + $unit * 0.019, $unit * 0.022, '#00F0FF', $c, 700, 40, 'center');
            $objects[] = $this->rect('cov-cyber-st2', $m + $unit * 0.31, $h * 0.66, $unit * 0.28, $unit * 0.06, '#1E293B', $unit * 0.01, 1.0, 0, '#FF007A', 1.5);
            $objects[] = $this->text('cov-cyber-st2-t', 'new-text', '⚡  500K+ COMMUNITY', $m + $unit * 0.45, $h * 0.66 + $unit * 0.019, $unit * 0.022, '#FF007A', $c, 700, 40, 'center');

            $objects[] = $this->text('cov-cyber-handle', 'new-text', '@'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $c['brand'])).'_gg', $m, $h * 0.83, $unit * 0.03, '#38BDF8', $c, 700, 60);

            $logoCx = $w * 0.80;
            $objects[] = $this->circle('cov-cyber-aura1', $logoCx, $h * 0.46, $unit * 0.42, 'none', '#00F0FF', 2, 0.4, [10, 8]);
            $objects[] = $this->circle('cov-cyber-aura2', $logoCx, $h * 0.46, $unit * 0.32, 'none', '#FF007A', 2, 0.5);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.46, '#00F0FF'));
            $brandText = $this->block($c['brand'], $w * 0.32, $unit * 0.048, 1, $c, 900, 180, true);
            $objects[] = $this->text('cov-cyber-bname', 'new-text', $brandText['text'], $logoCx, $h * 0.78, $brandText['size'], '#00F0FF', $c, 900, 180, 'center');

            return $objects;
        }

        // 3. CORPORATE SERVICES CHECKLIST
        if ($style === 'corporate-checklist') {
            $objects[] = $this->rect('cov-corp-bg', 0, 0, $w, $h, '#F8FAFC');
            $objects[] = $this->rect('cov-corp-topbar', 0, 0, $w, $unit * 0.012, $c['accent']);

            $objects[] = $this->text('cov-corp-kicker', 'new-text', 'ENTERPRISE ADVISORY  ·  EST. 2026', $m, $h * 0.14, $unit * 0.025, $c['accent'], $c, 700, 120);

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'Strategic Advisory & Solutions For Global Teams';
            $headline = $this->block($hlText, $w * 0.46, $unit * 0.082, 2, $c, 800, 0, true);
            $objects[] = $this->text('cov-corp-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#0F172A', $c, 800, 0);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Delivering measurable business value, risk mitigation, and scalable operations.';
            $sub = $this->block($subText, $w * 0.45, $unit * 0.035, 2, $c, 400, 20);
            $objects[] = $this->text('cov-corp-sub', 'new-text', $sub['text'], $m, $h * 0.51, $sub['size'], '#64748B', $c, 400, 20);

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'Schedule Free Consultation →';
            $objects = array_merge($objects, $this->ctaPill('cov-corp-cta', $m, $h * 0.67, $ctaLabel, $c, '#0F172A', $unit * 0.030));
            $objects[] = $this->text('cov-corp-trust', 'new-text', '★★★★★  Trusted by 250+ Industry Leaders', $m, $h * 0.83, $unit * 0.026, '#64748B', $c, 600, 30);

            $cardX = $w * 0.54;
            $cardY = $h * 0.10;
            $cardW = $w * 0.41;
            $cardH = $h * 0.80;
            $objects[] = $this->rect('cov-corp-card', $cardX, $cardY, $cardW, $cardH, '#FFFFFF', $unit * 0.02, 1.0, 0, '#E2E8F0', 1.5);

            $objects = array_merge($objects, $this->mark($c, $cardX + $unit * 0.07, $cardY + $unit * 0.07, $unit * 0.10));
            $objects[] = $this->text('cov-corp-cbrand', 'primary-text', $c['brand'], $cardX + $unit * 0.14, $cardY + $unit * 0.052, $unit * 0.040, '#0F172A', $c, 700, 80);
            $objects[] = $this->rule('cov-corp-crule', $cardX + $unit * 0.03, $cardY + $unit * 0.13, $cardW - $unit * 0.06, '#E2E8F0', 1.0);

            $bullets = [
                '✓   Enterprise Financial Planning & Analysis',
                '✓   End-to-End Strategic Transformation',
                '✓   24/7 Dedicated Client Partnership',
            ];
            foreach ($bullets as $bIdx => $bText) {
                $by = $cardY + $unit * 0.19 + $bIdx * $unit * 0.075;
                $objects[] = $this->circle('cov-corp-tick-bg-'.$bIdx, $cardX + $unit * 0.065, $by + $unit * 0.016, $unit * 0.022, '#EFF6FF', '#3B82F6', 1);
                $objects[] = $this->text('cov-corp-bullet-'.$bIdx, 'new-text', $bText, $cardX + $unit * 0.11, $by, $unit * 0.029, '#1E293B', $c, 600, 20);
            }

            $objects[] = $this->rect('cov-corp-cfoot-bg', $cardX, $cardY + $cardH - $unit * 0.09, $cardW, $unit * 0.09, '#F8FAFC', 0, 1.0, 0, '#E2E8F0', 1.0);
            $objects[] = $this->text('cov-corp-cfoot', 'new-text', '🌐  '.($c['website'] ?? 'www.yourbrand.com').'    ·    ☎  '.($c['phone'] ?? '+1 (555) 019-2834'), $cardX + $cardW / 2, $cardY + $cardH - $unit * 0.055, $unit * 0.026, '#475569', $c, 600, 30, 'center');

            return $objects;
        }

        // 4. LEADGEN CONNECT QR
        if ($style === 'leadgen-qr') {
            $objects[] = $this->rect('cov-qr-bg', 0, 0, $w, $h, '#0B132B');
            for ($i = 0; $i < 4; $i++) {
                $objects[] = $this->circle('cov-qr-wave-'.$i, $w * 0.25, $h * 0.95, $unit * (0.5 + $i * 0.25), 'none', '#1C2541', 2, 0.4);
            }

            $objects[] = $this->rect('cov-qr-kicker-bg', $m, $h * 0.12, $unit * 0.40, $unit * 0.052, '#38BDF8', $unit * 0.026, 0.15);
            $objects[] = $this->text('cov-qr-kicker-txt', 'new-text', '⚡  CONNECT & SCALE WITH US', $m + $unit * 0.20, $h * 0.12 + $unit * 0.015, $unit * 0.024, '#38BDF8', $c, 700, 80, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'Transform Your Business Operations';
            $headline = $this->block($hlText, $w * 0.50, $unit * 0.088, 2, $c, 800, 0, true);
            $objects[] = $this->text('cov-qr-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#FFFFFF', $c, 800, 0);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Scan the QR code directly with your phone camera to download our portfolio or book a 1-on-1 discovery call.';
            $sub = $this->block($subText, $w * 0.48, $unit * 0.034, 2, $c, 400, 20);
            $objects[] = $this->text('cov-qr-sub', 'new-text', $sub['text'], $m, $h * 0.52, $sub['size'], '#94A3B8', $c, 400, 20);

            $objects[] = $this->text('cov-qr-badges', 'new-text', '✦ 500+ Projects Delivered     ✦ 4.9/5 Client Rating     ✦ Zero Retainers', $m, $h * 0.74, $unit * 0.026, '#38BDF8', $c, 600, 30);
            $objects[] = $this->text('cov-qr-url', 'new-text', ($c['website'] ?? 'www.yourbrand.com').'   ·   '.($c['email'] ?? 'hello@yourbrand.com'), $m, $h * 0.84, $unit * 0.026, '#64748B', $c, 500, 40);

            $cardW = $unit * 0.58;
            $cardH = $h * 0.84;
            $cardX = $w * 0.72;
            $cardY = $h * 0.08;
            $objects[] = $this->rect('cov-qr-card', $cardX, $cardY, $cardW, $cardH, '#FFFFFF', $unit * 0.03);
            $cardCx = $cardX + $cardW / 2;

            $objects = array_merge($objects, $this->mark($c, $cardCx, $cardY + $unit * 0.09, $unit * 0.12, '#0B132B'));
            $objects[] = $this->text('cov-qr-cbrand', 'primary-text', $c['brand'], $cardCx, $cardY + $unit * 0.17, $unit * 0.034, '#0B132B', $c, 700, 80, 'center');

            $brandUpper = mb_strtoupper($c['brand']);
            $seedOffset = (int) ($c['variant_seed'] ?? 0);
            $addQr = function (string $id, float $cx, float $cy, float $size, string $fg) use (&$objects, $brandUpper, $seedOffset): void {
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
                $pathData = implode(' ', $parts);
                $objects[] = $this->pathGroup($id, $pathData, $cx, $cy, $size, $fg);
            };

            $addQr('cov-qr-code', $cardCx, $cardY + $unit * 0.44, $unit * 0.38, '#0B132B');

            $objects[] = $this->rect('cov-qr-scan-pill', $cardCx - $unit * 0.22, $cardY + $unit * 0.69, $unit * 0.44, $unit * 0.065, '#0B132B', $unit * 0.032);
            $objects[] = $this->text('cov-qr-scan-txt', 'new-text', 'SCAN TO CONNECT ↗', $cardCx, $cardY + $unit * 0.71, $unit * 0.024, '#FFFFFF', $c, 700, 80, 'center');

            return $objects;
        }

        // 5. HIGH-IMPACT FITNESS NEON
        if ($style === 'fitness-neon') {
            $objects[] = $this->rect('cov-fit-bg', 0, 0, $w, $h, '#141416');
            for ($s = 0; $s < 5; $s++) {
                $objects[] = $this->path('cov-fit-rib-'.$s, sprintf('M %d 0 L %d 0 L %d %d L %d %d Z', (int) ($w * (0.55 + $s * 0.09)), (int) ($w * (0.60 + $s * 0.09)), (int) ($w * (0.48 + $s * 0.09)), (int) $h, (int) ($w * (0.43 + $s * 0.09)), (int) $h), '#CCFF00', null, 0, 0.06 + $s * 0.04);
            }
            $objects[] = $this->rule('cov-fit-top-stripe', 0, 0, $w, '#CCFF00', $unit * 0.01);

            $objects[] = $this->rect('cov-fit-badge-bg', $m, $h * 0.12, $unit * 0.42, $unit * 0.052, '#CCFF00', $unit * 0.01);
            $objects[] = $this->text('cov-fit-badge-t', 'new-text', '⚡  SPRING ENROLLMENT OPEN', $m + $unit * 0.21, $h * 0.12 + $unit * 0.015, $unit * 0.025, '#141416', $c, 900, 80, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'UNLEASH YOUR HIGHEST POTENTIAL';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.095, 2, $c, 900, 10, true);
            $objects[] = $this->text('cov-fit-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#FFFFFF', $c, 900, 10);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Strength & Conditioning · High-Intensity HIIT · Certified Coaches';
            $sub = $this->block($subText, $w * 0.50, $unit * 0.035, 1, $c, 500, 20);
            $objects[] = $this->text('cov-fit-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], '#A1A1AA', $c, 500, 20);

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'CLAIM FREE 7-DAY PASS →';
            $objects = array_merge($objects, $this->ctaPill('cov-fit-cta', $m, $h * 0.65, $ctaLabel, $c, '#CCFF00', $unit * 0.032));

            $objects[] = $this->text('cov-fit-perks', 'new-text', '✓  24/7 Access     ✓  5 Prime Locations     ✓  Free Body Composition Scan', $m, $h * 0.82, $unit * 0.026, '#CCFF00', $c, 600, 40);

            $logoCx = $w * 0.80;
            $objects[] = $this->circle('cov-fit-c1', $logoCx, $h * 0.46, $unit * 0.44, 'none', '#CCFF00', 3, 0.4);
            $objects[] = $this->circle('cov-fit-c2', $logoCx, $h * 0.46, $unit * 0.35, '#1E1E24', '#CCFF00', 1.5, 0.8);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.44, '#CCFF00'));
            $brandText = $this->block($c['brand'], $w * 0.30, $unit * 0.05, 1, $c, 900, 160, true);
            $objects[] = $this->text('cov-fit-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.80, $brandText['size'], '#FFFFFF', $c, 900, 160, 'center');

            return $objects;
        }

        // 6. RETAIL CLEARANCE PROMO
        if ($style === 'retail-promo') {
            $objects[] = $this->rect('cov-ret-bg', 0, 0, $w, $h, '#881337');
            for ($r = 0; $r < 4; $r++) {
                for ($col = 0; $col < 8; $col++) {
                    $objects[] = $this->circle('cov-ret-dot-'.$r.'-'.$col, $w * 0.65 + $col * $unit * 0.06, $h * 0.15 + $r * $unit * 0.06, 2, '#FFFFFF', null, 0, 0.25);
                }
            }

            $objects[] = $this->rect('cov-ret-tag-bg', $m, $h * 0.11, $unit * 0.36, $unit * 0.048, '#FBBF24', $unit * 0.01);
            $objects[] = $this->text('cov-ret-tag', 'new-text', '★  EXCLUSIVE SALE EVENT', $m + $unit * 0.18, $h * 0.11 + $unit * 0.013, $unit * 0.024, '#1C1917', $c, 800, 80, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'UP TO 60% OFF EVERYTHING';
            $headline = $this->block($hlText, $w * 0.54, $unit * 0.105, 2, $c, 900, 10, true);
            $objects[] = $this->text('cov-ret-hl', 'primary-text', $headline['text'], $m, $h * 0.21, $headline['size'], '#FFFFFF', $c, 900, 10);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Discover new seasonal drops at unbeatable clearance prices. Free worldwide shipping.';
            $sub = $this->block($subText, $w * 0.52, $unit * 0.034, 1, $c, 400, 20);
            $objects[] = $this->text('cov-ret-sub', 'new-text', $sub['text'], $m, $h * 0.55, $sub['size'], '#FECDD3', $c, 400, 20);

            $boxW = $unit * 0.44;
            $boxH = $unit * 0.065;
            $objects[] = $this->rect('cov-ret-cbox', $m, $h * 0.65, $boxW, $boxH, '#4C0519', $unit * 0.01, 1.0, 0, '#FBBF24', 1.5, [6, 4]);
            $objects[] = $this->text('cov-ret-code', 'new-text', 'PROMO CODE: SAVE60', $m + $boxW / 2, $h * 0.65 + $unit * 0.019, $unit * 0.025, '#FDE68A', $c, 700, 80, 'center');

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'SHOP SALE NOW →';
            $objects = array_merge($objects, $this->ctaPill('cov-ret-cta', $m + $boxW + $unit * 0.04, $h * 0.65, $ctaLabel, $c, '#FBBF24', $unit * 0.028));

            $objects[] = $this->text('cov-ret-foot', 'new-text', 'Limited stock available   ·   Terms & conditions apply   ·   '.($c['website'] ?? 'www.yourbrand.com'), $m, $h * 0.83, $unit * 0.024, '#FDA4AF', $c, 500, 30);

            $logoCx = $w * 0.81;
            $objects[] = $this->circle('cov-ret-c1', $logoCx, $h * 0.46, $unit * 0.40, 'none', '#FBBF24', 3, 0.4);
            $objects[] = $this->circle('cov-ret-c2', $logoCx, $h * 0.46, $unit * 0.32, '#4C0519', '#FBBF24', 2, 0.9);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.42, '#FBBF24'));
            $brandText = $this->block($c['brand'], $w * 0.30, $unit * 0.046, 1, $c, 800, 160, true);
            $objects[] = $this->text('cov-ret-bname', 'new-text', $brandText['text'], $logoCx, $h * 0.78, $brandText['size'], '#FFFFFF', $c, 800, 160, 'center');

            return $objects;
        }

        // 7. Y2K CYBER STREETWEAR
        if ($style === 'y2k-streetwear') {
            $objects[] = $this->rect('cov-y2k-bg', 0, 0, $w, $h, '#1E1B4B');
            $objects[] = $this->circle('cov-y2k-aim', $w * 0.78, $h * 0.46, $unit * 0.45, 'none', '#818CF8', 1, 0.3, [8, 8]);
            $objects[] = $this->rule('cov-y2k-h', $w * 0.55, $h * 0.46, $w * 0.40, '#818CF8', 1, 0.25);
            $objects[] = $this->rule('cov-y2k-v', $w * 0.78, $h * 0.05, $h * 0.82, '#818CF8', 1, 0.25);

            $objects[] = $this->text('cov-y2k-tag', 'new-text', '[ DROP_004 // LIMITED RUN ARCHIVE ]', $m, $h * 0.13, $unit * 0.026, '#A78BFA', $c, 700, 160);

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'RAW STREETWEAR ARCHIVE';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.092, 2, $c, 900, 10, true);
            $objects[] = $this->text('cov-y2k-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#FFFFFF', $c, 900, 10);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Heavyweight Boxy Fits · Vintage Washed Cotton · Custom Hardware';
            $sub = $this->block($subText, $w * 0.50, $unit * 0.034, 1, $c, 400, 30);
            $objects[] = $this->text('cov-y2k-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], '#C7D2FE', $c, 400, 30);

            $barW = $unit * 0.005;
            $barX = $m;
            $barY = $h * 0.65;
            $barH = $unit * 0.06;
            for ($b = 0; $b < 28; $b++) {
                $bw = ($b % 3 === 0 || $b % 7 === 0) ? $barW * 2.2 : $barW;
                $objects[] = $this->rect('cov-y2k-bar-'.$b, $barX + $b * $unit * 0.013, $barY, $bw, $barH, '#A78BFA');
            }
            $objects[] = $this->text('cov-y2k-bar-txt', 'new-text', 'SYS.ARCHIVE // 2026-V4', $barX, $barY + $barH + $unit * 0.015, $unit * 0.020, '#818CF8', $c, 600, 120);

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'EXPLORE COLLECTION ↗';
            $objects = array_merge($objects, $this->ctaPill('cov-y2k-cta', $m + $unit * 0.42, $h * 0.65, $ctaLabel, $c, '#818CF8', $unit * 0.028));

            $logoCx = $w * 0.78;
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.44, '#A78BFA'));
            $brandText = $this->block($c['brand'], $w * 0.28, $unit * 0.046, 1, $c, 900, 180, true);
            $objects[] = $this->text('cov-y2k-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.78, $brandText['size'], '#FFFFFF', $c, 900, 180, 'center');
            $objects[] = $this->text('cov-y2k-star1', 'new-text', '✦', $logoCx - $unit * 0.26, $h * 0.22, $unit * 0.06, '#818CF8', $c, 700);
            $objects[] = $this->text('cov-y2k-star2', 'new-text', '✦', $logoCx + $unit * 0.22, $h * 0.68, $unit * 0.05, '#A78BFA', $c, 700);

            return $objects;
        }

        // 8. CREATOR COMMUNITY MILESTONE
        if ($style === 'creator-community') {
            $objects[] = $this->rect('cov-cc-bg', 0, 0, $w, $h, '#FFFDF5');
            $objects[] = $this->circle('cov-cc-blob1', $w * 0.82, $h * 0.44, $unit * 0.42, '#FEF3C7', null, 0, 0.6);
            $objects[] = $this->circle('cov-cc-blob2', $w * 0.75, $h * 0.38, $unit * 0.28, '#FDE68A', null, 0, 0.4);

            $objects[] = $this->rect('cov-cc-badge-bg', $m, $h * 0.12, $unit * 0.44, $unit * 0.052, '#FEF3C7', $unit * 0.026);
            $objects[] = $this->text('cov-cc-badge-txt', 'new-text', '✦  WEEKLY PODCAST & ESSAYS', $m + $unit * 0.22, $h * 0.12 + $unit * 0.015, $unit * 0.024, '#92400E', $c, 700, 80, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'Meaningful Stories on Craft & Culture';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.088, 2, $c, 800, 0, true);
            $objects[] = $this->text('cov-cc-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#1C1917', $c, 800, 0);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Join 100,000+ designers, founders, and creators receiving our weekly deep dives.';
            $sub = $this->block($subText, $w * 0.50, $unit * 0.035, 1, $c, 400, 20);
            $objects[] = $this->text('cov-cc-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], '#78716C', $c, 400, 20);

            $objects[] = $this->rect('cov-cc-mile-bg', $m, $h * 0.65, $unit * 0.38, $unit * 0.062, '#1C1917', $unit * 0.031);
            $objects[] = $this->text('cov-cc-mile-txt', 'new-text', '🎉  100K+ COMMUNITY MEMBERS', $m + $unit * 0.19, $h * 0.65 + $unit * 0.019, $unit * 0.023, '#F5F5F4', $c, 700, 60, 'center');

            $objects[] = $this->text('cov-cc-platforms', 'new-text', '🎙  Spotify    ·    ▶  Apple Podcasts    ·    📹  YouTube', $m, $h * 0.83, $unit * 0.028, '#78716C', $c, 600, 30);

            $logoCx = $w * 0.80;
            $objects[] = $this->circle('cov-cc-disc', $logoCx, $h * 0.44, $unit * 0.32, '#FFFFFF', '#E7E5E4', 2);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.44, $unit * 0.40));
            $brandText = $this->block($c['brand'], $w * 0.30, $unit * 0.046, 1, $c, 700, 140, true);
            $objects[] = $this->text('cov-cc-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.77, $brandText['size'], '#1C1917', $c, 700, 140, 'center');

            return $objects;
        }

        // 9. FESTIVAL WEEKEND EVENT
        if ($style === 'festival-event') {
            $objects[] = $this->rect('cov-fest-bg', 0, 0, $w, $h, '#FFFBEB');
            for ($a = 0; $a < 4; $a++) {
                $objects[] = $this->circle('cov-fest-arch-'.$a, $w * 0.80, $h * 0.50, $unit * (0.30 + $a * 0.10), 'none', '#F59E0B', 2, 0.3);
            }

            $objects[] = $this->rect('cov-fest-badge-bg', $m, $h * 0.11, $unit * 0.50, $unit * 0.052, '#F59E0B', $unit * 0.01);
            $objects[] = $this->text('cov-fest-badge-txt', 'new-text', '★  NOVEMBER 24-26, 2026  ·  LIVE IN PARK', $m + $unit * 0.25, $h * 0.11 + $unit * 0.015, $unit * 0.023, '#78350F', $c, 800, 60, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'ANNUAL ART & SOUND FESTIVAL';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.092, 2, $c, 900, 10, true);
            $objects[] = $this->text('cov-fest-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#78350F', $c, 900, 10);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : '3 Stages · 45+ Live Artists · Food Trucks · Interactive Art Installations';
            $sub = $this->block($subText, $w * 0.50, $unit * 0.035, 1, $c, 500, 20);
            $objects[] = $this->text('cov-fest-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], '#92400E', $c, 500, 20);

            $ctaLabel = ! empty($c['cta']) ? $c['cta'] : 'GET FESTIVAL PASSES →';
            $objects = array_merge($objects, $this->ctaPill('cov-fest-cta', $m, $h * 0.65, $ctaLabel, $c, '#D97706', $unit * 0.030));

            $objects[] = $this->text('cov-fest-notice', 'new-text', 'Early bird tickets valid until Oct 31   ·   soundfestival.com', $m, $h * 0.83, $unit * 0.026, '#B45309', $c, 600, 30);

            $logoCx = $w * 0.80;
            $objects[] = $this->circle('cov-fest-sun', $logoCx, $h * 0.46, $unit * 0.34, '#FDE68A', '#F59E0B', 2);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.42));
            $brandText = $this->block($c['brand'], $w * 0.28, $unit * 0.046, 1, $c, 900, 140, true);
            $objects[] = $this->text('cov-fest-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.79, $brandText['size'], '#78350F', $c, 900, 140, 'center');

            return $objects;
        }

        // 10. MODERN ARCHITECTURE SHOWCASE (REAL ESTATE)
        if ($style === 'realestate-showcase') {
            $objects[] = $this->rect('cov-re-bg', 0, 0, $w, $h, '#0F172A');

            $objects[] = $this->text('cov-re-kicker', 'new-text', 'EXCLUSIVE ARCHITECTURAL PORTFOLIO', $m, $h * 0.14, $unit * 0.025, '#94A3B8', $c, 700, 140);

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'Where Modern Design Meets Coastal Luxury';
            $headline = $this->block($hlText, $w * 0.46, $unit * 0.084, 2, $c, 700, 0, true);
            $objects[] = $this->text('cov-re-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#FFFFFF', $c, 700, 0);

            $objects[] = $this->rect('cov-re-price-bg', $m, $h * 0.52, $unit * 0.38, $unit * 0.054, '#1E293B', $unit * 0.01, 1.0, 0, '#38BDF8', 1.0);
            $objects[] = $this->text('cov-re-price', 'new-text', 'OFFERED AT $3,850,000', $m + $unit * 0.19, $h * 0.52 + $unit * 0.015, $unit * 0.025, '#38BDF8', $c, 700, 80, 'center');

            $objects[] = $this->text('cov-re-agent', 'new-text', 'Private Showings: (555) 019-2834   ·   realty@yourbrand.com', $m, $h * 0.69, $unit * 0.028, '#CBD5E1', $c, 500, 20);
            $objects[] = $this->text('cov-re-lic', 'new-text', 'LICENSED BROKERAGE #01928341   ·   YOURBRAND REAL ESTATE GROUP', $m, $h * 0.83, $unit * 0.024, '#64748B', $c, 600, 60);

            $frameX = $w * 0.56;
            $frameY = $h * 0.10;
            $frameW = $w * 0.39;
            $frameH = $h * 0.78;
            $objects[] = $this->rect('cov-re-frame', $frameX, $frameY, $frameW, $frameH, '#1E293B', $unit * 0.015, 1.0, 0, '#334155', 1.5);

            $objects[] = $this->rect('cov-re-facade', $frameX + $unit * 0.03, $frameY + $unit * 0.03, $frameW - $unit * 0.06, $frameH * 0.65, [
                'type' => 'linear',
                'angle' => 160,
                'stops' => [
                    ['offset' => 0, 'color' => '#1E293B'],
                    ['offset' => 1, 'color' => '#0F172A'],
                ],
            ], $unit * 0.01);
            $objects = array_merge($objects, $this->mark($c, $frameX + $frameW / 2, $frameY + $frameH * 0.34, $unit * 0.34, '#FFFFFF'));

            $objects[] = $this->rect('cov-re-specs-bg', $frameX + $unit * 0.03, $frameY + $frameH - $unit * 0.12, $frameW - $unit * 0.06, $unit * 0.09, '#0F172A', $unit * 0.01);
            $objects[] = $this->text('cov-re-specs', 'new-text', '4 BEDS   ·   3.5 BATHS   ·   4,200 SQ FT   ·   OCEAN VIEW', $frameX + $frameW / 2, $frameY + $frameH - $unit * 0.075, $unit * 0.024, '#F8FAFC', $c, 700, 60, 'center');

            return $objects;
        }

        // 11. ALTERNATIVE GRUNGE MUSIC DROP
        if ($style === 'grunge-music') {
            $objects[] = $this->rect('cov-gr-bg', 0, 0, $w, $h, '#121214');
            $objects[] = $this->rect('cov-gr-strip', 0, 0, $w, $unit * 0.015, '#F59E0B');
            for ($hz = 0; $hz < 30; $hz++) {
                $objects[] = $this->rect('cov-gr-hz-'.$hz, $hz * $unit * 0.09, 0, $unit * 0.04, $unit * 0.015, '#121214');
            }

            $objects[] = $this->rect('cov-gr-tag-bg', $m, $h * 0.12, $unit * 0.36, $unit * 0.050, '#FFFFFF', 0);
            $objects[] = $this->text('cov-gr-tag', 'new-text', 'NEW ALBUM OUT EVERYWHERE', $m + $unit * 0.18, $h * 0.12 + $unit * 0.014, $unit * 0.024, '#121214', $c, 900, 80, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'CHAOS IN THE METROPOLIS';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.095, 2, $c, 900, 20, true);
            $objects[] = $this->text('cov-gr-hl', 'primary-text', $headline['text'], $m, $h * 0.23, $headline['size'], '#FFFFFF', $c, 900, 20);

            $subText = ! empty($c['subhead']) ? $c['subhead'] : 'Streaming on Spotify, Apple Music, Bandcamp & Limited 180g Vinyl';
            $sub = $this->block($subText, $w * 0.50, $unit * 0.035, 1, $c, 500, 20);
            $objects[] = $this->text('cov-gr-sub', 'new-text', $sub['text'], $m, $h * 0.54, $sub['size'], '#A1A1AA', $c, 500, 20);

            $objects[] = $this->text('cov-gr-tour-hl', 'new-text', '★ FALL 2026 WORLD TOUR DATES', $m, $h * 0.69, $unit * 0.026, '#F59E0B', $c, 800, 80);
            $objects[] = $this->text('cov-gr-cities', 'new-text', 'NYC · CHI · LA · LON · PAR · BER · TOK', $m, $h * 0.81, $unit * 0.034, '#FFFFFF', $c, 900, 100);

            $logoCx = $w * 0.79;
            $objects[] = $this->circle('cov-gr-vinyl', $logoCx, $h * 0.46, $unit * 0.42, '#18181B', '#27272A', 4);
            $objects[] = $this->circle('cov-gr-vinyl-g1', $logoCx, $h * 0.46, $unit * 0.34, 'none', '#27272A', 1);
            $objects[] = $this->circle('cov-gr-vinyl-g2', $logoCx, $h * 0.46, $unit * 0.26, 'none', '#27272A', 1);
            $objects[] = $this->circle('cov-gr-label', $logoCx, $h * 0.46, $unit * 0.16, '#F59E0B');
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.26, '#121214'));
            $brandText = $this->block($c['brand'], $w * 0.28, $unit * 0.046, 1, $c, 900, 180, true);
            $objects[] = $this->text('cov-gr-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.80, $brandText['size'], '#FFFFFF', $c, 900, 180, 'center');

            return $objects;
        }

        // 12. POP ART CONTEST GIVEAWAY
        if ($style === 'popart-giveaway') {
            $objects[] = $this->rect('cov-pop-bg', 0, 0, $w, $h, '#FEF08A');
            for ($r = 0; $r < 5; $r++) {
                for ($col = 0; $col < 9; $col++) {
                    $objects[] = $this->circle('cov-pop-ht-'.$r.'-'.$col, $w * 0.62 + $col * $unit * 0.065, $h * 0.12 + $r * $unit * 0.065, $unit * 0.012, '#FACC15');
                }
            }

            $objects[] = $this->rect('cov-pop-badge-bg', $m, $h * 0.11, $unit * 0.48, $unit * 0.054, '#EF4444', $unit * 0.01, 1.0, 0, '#000000', 2);
            $objects[] = $this->text('cov-pop-badge-txt', 'new-text', '💥  MEGA CREATIVE GIVEAWAY  💥', $m + $unit * 0.24, $h * 0.11 + $unit * 0.015, $unit * 0.026, '#FFFFFF', $c, 900, 60, 'center');

            $hlText = ! empty($c['headline']) ? $c['headline'] : 'WIN A $1,500 STUDIO UPGRADE';
            $headline = $this->block($hlText, $w * 0.52, $unit * 0.092, 2, $c, 900, 10, true);
            $objects[] = $this->text('cov-pop-hl', 'primary-text', $headline['text'], $m, $h * 0.22, $headline['size'], '#000000', $c, 900, 10);

            $stepW = $unit * 0.23;
            $stepH = $unit * 0.085;
            $steps = [
                ['1️⃣', 'Follow @Brand'],
                ['2️⃣', 'Like & Repost'],
                ['3️⃣', 'Tag 2 Friends'],
            ];
            foreach ($steps as $sIdx => $step) {
                $sx = $m + $sIdx * ($stepW + $unit * 0.02);
                $sy = $h * 0.54;
                $objects[] = $this->rect('cov-pop-step-'.$sIdx, $sx, $sy, $stepW, $stepH, '#FFFFFF', $unit * 0.01, 1.0, 0, '#000000', 2);
                $objects[] = $this->text('cov-pop-stxt-'.$sIdx, 'new-text', $step[0].' '.$step[1], $sx + $stepW / 2, $sy + $unit * 0.024, $unit * 0.023, '#000000', $c, 800, 20, 'center');
            }

            $objects[] = $this->rect('cov-pop-win-bg', $m, $h * 0.69, $unit * 0.44, $unit * 0.054, '#06B6D4', $unit * 0.01, 1.0, 0, '#000000', 2);
            $objects[] = $this->text('cov-pop-win-txt', 'new-text', '★  WINNER CHOSEN LIVE EVERY FRIDAY  ★', $m + $unit * 0.22, $h * 0.69 + $unit * 0.015, $unit * 0.022, '#FFFFFF', $c, 900, 60, 'center');

            $objects[] = $this->text('cov-pop-foot', 'new-text', 'No purchase necessary   ·   18+   ·   '.($c['website'] ?? 'www.yourbrand.com'), $m, $h * 0.83, $unit * 0.024, '#713F12', $c, 700, 30);

            $logoCx = $w * 0.80;
            $objects[] = $this->circle('cov-pop-boom', $logoCx, $h * 0.46, $unit * 0.40, '#FFFFFF', '#000000', 3);
            $objects = array_merge($objects, $this->mark($c, $logoCx, $h * 0.46, $unit * 0.42));
            $brandText = $this->block($c['brand'], $w * 0.28, $unit * 0.046, 1, $c, 900, 140, true);
            $objects[] = $this->text('cov-pop-brand', 'new-text', $brandText['text'], $logoCx, $h * 0.79, $brandText['size'], '#000000', $c, 900, 140, 'center');

            return $objects;
        }

        if ($style === 'retro') {
            // Boarding-pass / ticket-stub cover style.
            $stubX = $w * 0.84;
            $objects[] = $this->rect('cover-stub', $stubX, 0, $w - $stubX, $h, $c['accent']);
            $objects[] = $this->circle('cover-notch-top', $stubX, 0, $unit * 0.08, '#FFFFFF');
            $objects[] = $this->circle('cover-notch-bot', $stubX, $h, $unit * 0.08, '#FFFFFF');
            for ($b = 0; $b < 14; $b++) {
                $bh = ($b % 3 === 0) ? $unit * 0.032 : $unit * 0.016;
                $objects[] = $this->rect('cover-bar-'.$b, $stubX + ($w - $stubX) * 0.28, $h * 0.16 + $b * $unit * 0.048, ($w - $stubX) * 0.44, $bh, Color::readableInk($c['accent']), 2, 0.85);
            }
            $headline = $this->block($c['headline'], $w * 0.46, $unit * 0.19, 2, $c, 800, 10, true);
            $objects[] = $this->text('banner-headline', 'primary-text', $headline['text'], $m, $h * 0.22, $headline['size'], $c['ink'], $c, 800, 10);
            $sub = $this->block($c['subhead'], $w * 0.44, $unit * 0.045, 1, $c, 500, 30);
            $objects[] = $this->text('banner-sub', 'new-text', $sub['text'], $m, $h * 0.72, $sub['size'], $c['muted'], $c, 500, 30);
            $markSize = $unit * 0.34;
            $objects = array_merge($objects, $this->mark($c, $w * 0.68, $h * 0.38, $markSize));
            $brand = $this->block($c['brand'], $w * 0.24, $unit * 0.04, 1, $c, 700, 160, true);
            $objects[] = $this->text('banner-brand', 'new-text', $brand['text'], $w * 0.68, $h * 0.62, $brand['size'], $c['ink'], $c, 700, 160, 'center');
            $objects = array_merge($objects, $this->ctaPill('banner-cta', $w * 0.68 - $unit * 0.18, $h * 0.75, $c['cta'], $c, null, null, true));

            return $objects;
        }

        if ($style === 'split') {
            $panelX = $w * 0.62;
            $objects[] = $this->rect('cover-split-panel', $panelX, 0, $w - $panelX, $h, [
                'type' => 'linear',
                'angle' => 135,
                'stops' => [
                    ['offset' => 0, 'color' => $c['colors'][0]],
                    ['offset' => 1, 'color' => $c['colors'][1]],
                ],
            ]);
            $objects[] = $this->circle('cover-split-ring', $panelX + ($w - $panelX) / 2, $h / 2, $unit * 0.36, 'none', '#FFFFFF', $unit * 0.008, 0.28);
            $objects = array_merge($objects, $this->mark($c, $panelX + ($w - $panelX) / 2, $h / 2, $unit * 0.52));

            $brand = $this->block($c['brand'], $w * 0.52, $unit * 0.045, 1, $c, 700, 220, true);
            $objects[] = $this->text('banner-brand', 'new-text', $brand['text'], $m, $h * 0.16, $brand['size'], $c['accent'], $c, 700, 220);
            $headline = $this->block($c['headline'], $w * 0.52, $unit * 0.16, 2, $c, 800, 10, true);
            $objects[] = $this->text('banner-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 10);
            $sub = $this->block($c['subhead'], $w * 0.5, $unit * 0.048, 1, $c, 500, 20);
            $objects[] = $this->text('banner-sub', 'new-text', $sub['text'], $m, $h * 0.64, $sub['size'], $c['muted'], $c, 500, 20);
            $objects = array_merge($objects, $this->ctaPill('banner-cta', $m, $h * 0.76, $c['cta'], $c));

            return $objects;
        }

        if ($style === 'spotlight') {
            $markX = $w * 0.78;
            $objects[] = $this->circle('cover-spot-outer', $markX, $h / 2, $unit * 0.44, 'none', $c['accent'], $unit * 0.006, 0.35);
            $objects[] = $this->circle('cover-spot-glow', $markX, $h / 2, $unit * 0.32, $c['accent'], null, 0, 0.18);
            $objects = array_merge($objects, $this->mark($c, $markX, $h / 2, $unit * 0.48));

            $brand = $this->block($c['brand'], $w * 0.56, $unit * 0.044, 1, $c, 700, 240, true);
            $objects[] = $this->text('banner-brand', 'new-text', $brand['text'], $m, $h * 0.16, $brand['size'], $c['accent'], $c, 700, 240);
            $headline = $this->block($c['headline'], $w * 0.58, $unit * 0.165, 2, $c, 800, 10, true);
            $objects[] = $this->text('banner-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], '#FFFFFF', $c, 800, 10);
            $sub = $this->block($c['subhead'], $w * 0.54, $unit * 0.046, 1, $c, 400, 20);
            $objects[] = $this->text('banner-sub', 'new-text', $sub['text'], $m, $h * 0.64, $sub['size'], '#CBD5E1', $c, 400, 20);
            $objects = array_merge($objects, $this->ctaPill('banner-cta', $m, $h * 0.76, $c['cta'], $c));

            return $objects;
        }

        $markSize = $unit * 0.34;
        $objects = array_merge($objects, $this->mark($c, $w - $m - $markSize * 0.7, $h * 0.5, $markSize));
        $brand = $this->block($c['brand'], $w * 0.52, $unit * 0.046, 1, $c, 700, 180, true);
        $objects[] = $this->text('banner-brand', 'new-text', $brand['text'], $m, $h * 0.16, $brand['size'], $c['accent'], $c, 700, 180);
        $textW = $w * 0.58;
        $headline = $this->block($c['headline'], $textW, $unit * 0.155, 2, $c, 800, 10, true);
        $objects[] = $this->text('banner-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 10);
        $sub = $this->block($c['subhead'], $textW, $unit * 0.046, 1, $c, 500, 20);
        $objects[] = $this->text('banner-sub', 'new-text', $sub['text'], $m, $h * 0.63, $sub['size'], $c['muted'], $c, 500, 20);
        $objects = array_merge($objects, $this->ctaPill('banner-cta', $m, $h * 0.76, $c['cta'], $c));
        $objects[] = $this->circle('banner-deco-1', $w - $m * 0.8, $h * 0.18, $unit * 0.16, $c['support'], null, 0, 0.25);

        return $objects;
    }

        protected function layoutPoster(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.08;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'billboard') {
            $objects[] = $this->rect('billboard-band', 0, 0, $w * 0.012, $h, $c['accent']);
            $headline = $this->block($c['headline'], $w * 0.6, $unit * 0.17, 2, $c, 800, 0, true);
            $objects[] = $this->text('billboard-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 0);
            $objects[] = $this->text('billboard-sub', 'new-text', $c['subhead'], $m, $h * 0.3 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.02, $unit * 0.032, $c['muted'], $c, 500, 20);
            $objects = array_merge($objects, $this->mark($c, $w * 0.84, $h * 0.45, $unit * 0.22));
            $objects[] = $this->text('billboard-brand', 'new-text', $c['brand'], $w - $m, $h - $m * 0.9, $unit * 0.03, $c['ink'], $c, 700, 120, 'center');

            return $objects;
        }

        if ($variant === 'newspaper') {
            $objects[] = $this->text('news-kicker', 'new-text', 'ADVERTISEMENT', $w / 2, $m * 0.6, $unit * 0.019, $c['muted'], $c, 700, 300, 'center');
            $objects[] = $this->rule('news-rule-top', $m, $m + $unit * 0.035, $w - 2 * $m, $c['ink'], $unit * 0.003);
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.1, 2, $c, 800, 0);
            $objects[] = $this->text('news-headline', 'primary-text', $headline['text'], $w / 2, $h * 0.16, $headline['size'], $c['ink'], $c, 800, 0, 'center');
            $objects[] = $this->rule('news-rule-mid', $m, $h * 0.38, $w - 2 * $m, $c['ink'], $unit * 0.002);
            $colW = ($w - 2 * $m - $unit * 0.04) / 2;
            $body = $this->paragraph($c['subhead'].' '.$c['body'], $colW, $unit * 0.021, $c, 400, 10);
            $half = (int) ceil(count($body) / 2);
            $objects[] = $this->text('news-col-1', 'new-text', implode("\n", array_slice($body, 0, $half)), $m, $h * 0.42, $unit * 0.021, $c['muted'], $c, 400, 10);
            $objects[] = $this->text('news-col-2', 'new-text', implode("\n", array_slice($body, $half)), $m + $colW + $unit * 0.04, $h * 0.42, $unit * 0.021, $c['muted'], $c, 400, 10);
            $objects[] = $this->text('news-brand', 'new-text', $c['brand'], $w / 2, $h - $m * 0.9, $unit * 0.026, $c['ink'], $c, 700, 160, 'center');

            return $objects;
        }

        // Poster, flyer and event poster — 8 distinct art-directed compositions.
        $style = (string) ($c['style'] ?? 'editorial');
        $cx = $w / 2;

        if ($style === 'split') {
            $heroH = $h * 0.48;
            $heroBg = $c['colors'][0];
            $heroInk = Color::readableInk($heroBg);
            $objects[] = $this->rect('poster-split-hero', 0, 0, $w, $heroH, [
                'type' => 'linear',
                'angle' => 135,
                'stops' => [
                    ['offset' => 0, 'color' => $heroBg],
                    ['offset' => 1, 'color' => $c['colors'][1]],
                ],
            ]);
            $objects[] = $this->circle('poster-split-glow', $w * 0.82, $heroH * 0.28, $unit * 0.24, '#FFFFFF', null, 0, 0.14);
            $objects[] = $this->circle('poster-split-ring', $w * 0.82, $heroH * 0.28, $unit * 0.34, 'none', '#FFFFFF', $unit * 0.004, 0.24);
            $markSize = $unit * 0.14;
            $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
            $brand = $this->block($c['brand'], $w * 0.65, $unit * 0.032, 1, $c, 700, 180, true);
            $objects[] = $this->text('poster-brand', 'new-text', $brand['text'], $m + $markSize + $unit * 0.024, $m + $markSize / 2 - $brand['size'] * 0.55, $brand['size'], $heroInk, $c, 700, 180);
            $dateChip = $this->block($c['date'], $w * 0.55, $unit * 0.024, 1, $c, 700, 160, true);
            $objects[] = $this->rect('poster-date-chip', $m, $heroH - $unit * 0.11, $dateChip['width'] + $unit * 0.05, $unit * 0.056, '#FFFFFF', $unit * 0.028, 0.94);
            $objects[] = $this->text('poster-date-chip-text', 'new-text', $dateChip['text'], $m + ($dateChip['width'] + $unit * 0.05) / 2, $heroH - $unit * 0.11 + ($unit * 0.056 - $dateChip['size'] * 1.12) / 2, $dateChip['size'], $heroBg, $c, 700, 160, 'center');

            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.135, 3, $c, 800, 0, true);
            $objects[] = $this->text('poster-headline', 'primary-text', $headline['text'], $m, $heroH + $unit * 0.055, $headline['size'], $c['ink'], $c, 800, 0);
            $hBot = $heroH + $unit * 0.055 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $objects[] = $this->rule('poster-accent-bar', $m, $hBot + $unit * 0.02, $unit * 0.18, $c['accent'], $unit * 0.008);
            $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.036, 2, $c, 500, 20);
            $objects[] = $this->text('poster-sub', 'new-text', $sub['text'], $m, $hBot + $unit * 0.055, $sub['size'], $c['muted'], $c, 500, 20);

            $objects[] = $this->text('poster-location', 'new-text', $c['location'].' · '.$c['time'], $m, $h - $m * 2.2, $unit * 0.026, $c['ink'], $c, 600, 60);
            $objects = array_merge($objects, $this->ctaPill('poster-cta', $m, $h - $m * 1.35, $c['cta'], $c));

            return $objects;
        }

        if ($style === 'spotlight') {
            $objects[] = $this->circle('poster-spot-outer', $cx, $h * 0.42, $unit * 0.42, 'none', $c['accent'], $unit * 0.004, 0.35);
            $objects[] = $this->circle('poster-spot-mid', $cx, $h * 0.42, $unit * 0.3, $c['accent'], null, 0, 0.16);
            $objects[] = $this->circle('poster-spot-inner', $cx, $h * 0.42, $unit * 0.19, 'none', '#FFFFFF', $unit * 0.003, 0.25);

            $brand = $this->block($c['brand'], $w * 0.72, $unit * 0.028, 1, $c, 700, 240, true);
            $objects[] = $this->text('poster-brand', 'new-text', $brand['text'], $cx, $m * 0.9, $brand['size'], $c['accent'], $c, 700, 240, 'center');
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.21, $unit * 0.14));

            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.15, 3, $c, 800, 20, true);
            $objects[] = $this->text('poster-headline', 'primary-text', $headline['text'], $cx, $h * 0.36, $headline['size'], '#FFFFFF', $c, 800, 20, 'center');
            $hBot = $h * 0.36 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $sub = $this->block($c['subhead'], $w * 0.78, $unit * 0.035, 2, $c, 400, 30);
            $objects[] = $this->text('poster-sub', 'new-text', $sub['text'], $cx, $hBot + $unit * 0.03, $sub['size'], '#CBD5E1', $c, 400, 30, 'center');

            $objects[] = $this->rule('poster-rule', $m, $h * 0.75, $w - 2 * $m, $c['accent'], $unit * 0.003, 0.55);
            $objects[] = $this->text('poster-date', 'new-text', mb_strtoupper($c['date'].'  ·  '.$c['time']), $cx, $h * 0.78, $unit * 0.03, $c['accent'], $c, 700, 120, 'center');
            $objects[] = $this->text('poster-location', 'new-text', $c['location'], $cx, $h * 0.83, $unit * 0.026, '#E2E8F0', $c, 500, 60, 'center');
            $objects = array_merge($objects, $this->ctaPill('poster-cta', $cx - $unit * 0.16, $h - $m * 1.35, $c['cta'], $c, null, null, true));

            return $objects;
        }

        if ($style === 'retro') {
            $inset = $m * 0.55;
            $objects[] = $this->rect('poster-retro-outer', $inset, $inset, $w - 2 * $inset, $h - 2 * $inset, 'none', 0);
            $objects[] = $this->rule('poster-retro-t', $inset, $inset, $w - 2 * $inset, $c['ink'], $unit * 0.008);
            $objects[] = $this->rule('poster-retro-b', $inset, $h - $inset, $w - 2 * $inset, $c['ink'], $unit * 0.008);
            $objects[] = $this->rect('poster-retro-l', $inset, $inset, $unit * 0.008, $h - 2 * $inset, $c['ink']);
            $objects[] = $this->rect('poster-retro-r', $w - $inset - $unit * 0.008, $inset, $unit * 0.008, $h - 2 * $inset, $c['ink']);

            $objects[] = $this->circle('poster-retro-badge', $cx, $h * 0.17, $unit * 0.115, $c['ink'], $c['accent'], $unit * 0.006);
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.17, $unit * 0.11));

            $brand = $this->block($c['brand'], $w * 0.7, $unit * 0.026, 1, $c, 700, 260, true);
            $objects[] = $this->text('poster-brand', 'new-text', $brand['text'], $cx, $h * 0.29, $brand['size'], $c['accent'], $c, 700, 260, 'center');
            $objects[] = $this->rule('poster-retro-div1', $cx - $unit * 0.24, $h * 0.34, $unit * 0.48, $c['ink'], $unit * 0.003);

            $headline = $this->block($c['headline'], $w - 2.4 * $m, $unit * 0.145, 3, $c, 800, 10, true);
            $objects[] = $this->text('poster-headline', 'primary-text', $headline['text'], $cx, $h * 0.38, $headline['size'], $c['ink'], $c, 800, 10, 'center');
            $hBot = $h * 0.38 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $sub = $this->block($c['subhead'], $w * 0.72, $unit * 0.034, 2, $c, 500, 20);
            $objects[] = $this->text('poster-sub', 'new-text', $sub['text'], $cx, $hBot + $unit * 0.025, $sub['size'], $c['muted'], $c, 500, 20, 'center');

            $objects[] = $this->rule('poster-retro-div2', $m, $h * 0.74, $w - 2 * $m, $c['ink'], $unit * 0.004);
            $objects[] = $this->text('poster-date', 'new-text', mb_strtoupper($c['date']), $cx, $h * 0.77, $unit * 0.034, $c['accent'], $c, 800, 140, 'center');
            $objects[] = $this->text('poster-location', 'new-text', $c['location'].' · '.$c['time'], $cx, $h * 0.825, $unit * 0.025, $c['ink'], $c, 600, 80, 'center');
            $objects = array_merge($objects, $this->ctaPill('poster-cta', $cx - $unit * 0.16, $h - $m * 1.55, $c['cta'], $c, null, null, true));

            return $objects;
        }

        if ($style === 'kinetic') {
            $objects[] = $this->rect('poster-kin-top', 0, 0, $w, $unit * 0.035, $c['accent']);
            $objects[] = $this->circle('poster-kin-blob1', $w * 0.88, $h * 0.16, $unit * 0.22, $c['accent'], null, 0, 0.22);
            $objects[] = $this->circle('poster-kin-blob2', $w * 0.12, $h * 0.88, $unit * 0.24, $c['support'], null, 0, 0.2);
            $objects[] = $this->rect('poster-kin-stripe', $w - $m * 1.4, $m * 1.2, $unit * 0.018, $h * 0.45, $c['accent'], $unit * 0.009);

            $markSize = $unit * 0.095;
            $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
            $brand = $this->block($c['brand'], $w * 0.56, $unit * 0.03, 1, $c, 800, 160, true);
            $objects[] = $this->text('poster-brand', 'new-text', $brand['text'], $m + $markSize + $unit * 0.022, $m + $markSize / 2 - $brand['size'] * 0.55, $brand['size'], $c['ink'], $c, 800, 160);

            $headline = $this->block($c['headline'], $w - 2.4 * $m, $unit * 0.165, 3, $c, 800, 0, true);
            $objects[] = $this->text('poster-headline', 'primary-text', $headline['text'], $m, $h * 0.26, $headline['size'], $c['ink'], $c, 800, 0);
            $hBot = $h * 0.26 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
            $objects[] = $this->rect('poster-kin-bar', $m, $hBot + $unit * 0.02, $w * 0.42, $unit * 0.014, $c['accent'], $unit * 0.007);

            $sub = $this->block($c['subhead'], $w * 0.76, $unit * 0.038, 2, $c, 500, 20);
            $objects[] = $this->text('poster-sub', 'new-text', $sub['text'], $m, $hBot + $unit * 0.06, $sub['size'], $c['muted'], $c, 500, 20);

            $cardY = $h * 0.72;
            $objects[] = $this->rect('poster-kin-card', $m, $cardY, $w - 2 * $m, $h * 0.16, $c['ink'], $unit * 0.024);
            $cardInk = Color::readableInk($c['ink']);
            $objects[] = $this->text('poster-date', 'new-text', mb_strtoupper($c['date']), $m + $unit * 0.04, $cardY + $unit * 0.038, $unit * 0.034, $c['accent'], $c, 800, 100);
            $objects[] = $this->text('poster-location', 'new-text', $c['location'].' · '.$c['time'], $m + $unit * 0.04, $cardY + $unit * 0.092, $unit * 0.026, $cardInk, $c, 500, 40);
            $objects = array_merge($objects, $this->ctaPill('poster-cta', $m, $h - $m * 1.15, $c['cta'], $c));

            return $objects;
        }

        // Editorial, Bold, Gradient & Minimal — upgraded with structured top accent bar, layered geometry & crisp card footer.
        $objects[] = $this->rect('poster-top-bar', 0, 0, $w, $unit * 0.022, $c['accent']);
        $markSize = $unit * 0.09;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
        $brand = $this->block($c['brand'], $w * 0.52, $unit * 0.028, 1, $c, 700, 180, true);
        $objects[] = $this->text('poster-brand', 'new-text', $brand['text'], $m + $markSize + $unit * 0.022, $m + $markSize / 2 - $brand['size'] * 0.55, $brand['size'], $c['ink'], $c, 700, 180);
        $objects[] = $this->text('poster-label', 'new-text', mb_strtoupper($c['date']), $w - $m, $m + $markSize / 2 - $unit * 0.012, $unit * 0.022, $c['accent'], $c, 700, 160, 'center');

        $objects[] = $this->circle('poster-deco', $w * 0.84, $h * 0.22, $unit * 0.2, $c['accent'], null, 0, 0.22);
        $objects[] = $this->circle('poster-deco-ring', $w * 0.84, $h * 0.22, $unit * 0.29, 'none', $c['accent'], $unit * 0.004, 0.35);

        $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.16, 3, $c, 800, 0, true);
        $objects[] = $this->text('poster-headline', 'primary-text', $headline['text'], $m, $h * 0.29, $headline['size'], $c['ink'], $c, 800, 0);
        $headlineBottom = $h * 0.29 + $headline['size'] * 1.12 * max(1, count($headline['lines']));
        $objects[] = $this->rule('poster-headline-rule', $m, $headlineBottom + $unit * 0.02, $unit * 0.16, $c['accent'], $unit * 0.008);
        $sub = $this->block($c['subhead'], $w * 0.76, $unit * 0.038, 2, $c, 500, 20);
        $objects[] = $this->text('poster-sub', 'new-text', $sub['text'], $m, $headlineBottom + $unit * 0.055, $sub['size'], $c['muted'], $c, 500, 20);

        $metaY = $h * 0.73;
        $objects[] = $this->rule('poster-rule', $m, $metaY - $unit * 0.03, $w - 2 * $m, $c['ink'], $unit * 0.003, 0.35);
        $objects[] = $this->text('poster-date', 'new-text', mb_strtoupper($c['date']), $m, $metaY, $unit * 0.034, $c['accent'], $c, 700, 60);
        $objects[] = $this->text('poster-location', 'new-text', $c['location'], $m, $metaY + $unit * 0.052, $unit * 0.027, $c['ink'], $c, 500, 40);
        $objects[] = $this->text('poster-time', 'new-text', $c['time'], $w - $m, $metaY, $unit * 0.027, $c['muted'], $c, 600, 40, 'center');
        $objects = array_merge($objects, $this->ctaPill('poster-cta', $m, $h - $m * 1.5, $c['cta'], $c));

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutPromo(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.075;
        $objects = [];
        $variant = $c['variant'];

        if (str_starts_with((string) ($c['style'] ?? ''), 'post-') || $variant === 'ad') {
            return $this->layoutSocial($c);
        }

        if ($variant === 'coupon') {
            $inset = $m * 0.5;
            $objects[] = $this->rule('coupon-top', $inset, $inset, $w - 2 * $inset, $c['accent'], $unit * 0.006);
            $objects[] = $this->rule('coupon-bottom', $inset, $h - $inset, $w - 2 * $inset, $c['accent'], $unit * 0.006);
            $objects[] = $this->rect('coupon-left', $inset, $inset, $unit * 0.006, $h - 2 * $inset, $c['accent']);
            $objects[] = $this->rect('coupon-right', $w - $inset, $inset, $unit * 0.006, $h - 2 * $inset, $c['accent']);
            $discount = $this->block($c['discount'], $w * 0.6, $unit * 0.17, 1, $c, 800, 0, true);
            $objects[] = $this->text('coupon-discount', 'primary-text', $discount['text'], $m, $h * 0.22, $discount['size'], $c['accent'], $c, 800, 0);
            $headline = $this->block($c['headline'], $w * 0.55, $unit * 0.055, 2, $c, 700, 20);
            $objects[] = $this->text('coupon-headline', 'primary-text', $headline['text'], $m, $h * 0.45, $headline['size'], $c['ink'], $c, 700, 20);
            $codeW = max($w * 0.3, $this->lineWidth($c['code'], $unit * 0.045, $c, 700, 60) + $unit * 0.06);
            $objects[] = $this->rect('coupon-code-bg', $w - $m - $codeW, $h * 0.5, $codeW, $unit * 0.09, $c['ink'], $unit * 0.014);
            $objects[] = $this->text('coupon-code', 'new-text', $c['code'], $w - $m - $codeW / 2, $h * 0.5 + ($unit * 0.09 - $unit * 0.045 * 1.12) / 2, $unit * 0.045, Color::readableInk($c['ink']), $c, 700, 60, 'center');
            $objects[] = $this->text('coupon-notes', 'new-text', $c['notes'], $m, $h * 0.78, $unit * 0.021, $c['muted'], $c, 400, 40);
            $objects[] = $this->text('coupon-brand', 'new-text', $c['brand'], $m, $h - $m * 0.8, $unit * 0.024, $c['ink'], $c, 700, 160);

            return $objects;
        }

        if ($variant === 'sale') {
            $objects[] = $this->text('sale-brand', 'new-text', $c['brand'], $m, $m * 0.6, $unit * 0.03, $c['ink'], $c, 700, 140);
            $discount = $this->block($c['discount'], $w * 0.55, $unit * 0.2, 1, $c, 800, 0, true);
            $objects[] = $this->text('sale-discount', 'primary-text', $discount['text'], $m, $h * 0.2, $discount['size'], $c['accent'], $c, 800, 0);
            $headline = $this->block($c['headline'], $w * 0.5, $unit * 0.055, 2, $c, 700, 20);
            $objects[] = $this->text('sale-headline', 'primary-text', $headline['text'], $m, $h * 0.48, $headline['size'], $c['ink'], $c, 700, 20);
            $objects = array_merge($objects, $this->ctaPill('sale-cta', $m, $h * 0.72, $c['cta'], $c));
            $codeW = max($unit * 0.26, $this->lineWidth($c['code'], $unit * 0.032, $c, 700, 80) + $unit * 0.05);
            $objects[] = $this->rect('sale-code-bg', $w - $m - $codeW, $h * 0.36, $codeW, $unit * 0.07, $c['ink'], $unit * 0.035);
            $objects[] = $this->text('sale-code', 'new-text', $c['code'], $w - $m - $codeW / 2, $h * 0.36 + ($unit * 0.07 - $unit * 0.032 * 1.12) / 2, $unit * 0.032, Color::readableInk($c['ink']), $c, 700, 80, 'center');
            $objects[] = $this->circle('sale-deco', $w * 0.86, $h * 0.76, $unit * 0.13, 'none', $c['accent'], $unit * 0.006, 0.7);

            return $objects;
        }

        if ($variant === 'restaurant') {
            $objects[] = $this->circle('restaurant-plate', $w * 0.76, $h * 0.3, $unit * 0.19, 'none', $c['accent'], $unit * 0.005, 0.6);
            $objects[] = $this->text('restaurant-label', 'new-text', 'DINNER · '.$c['time'], $m, $m, $unit * 0.022, $c['accent'], $c, 700, 220);
            $headline = $this->block($c['headline'], $w * 0.75, $unit * 0.1, 3, $c, 800, 10);
            $objects[] = $this->text('restaurant-headline', 'primary-text', $headline['text'], $m, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 10);
            $objects[] = $this->text('restaurant-sub', 'new-text', $c['subhead'], $m, $h * 0.3 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.02, $unit * 0.03, $c['muted'], $c, 500, 20);
            $objects[] = $this->text('restaurant-price', 'new-text', $c['price'].' per person', $m, $h * 0.72, $unit * 0.032, $c['ink'], $c, 600, 40);
            $objects = array_merge($objects, $this->ctaPill('restaurant-cta', $m, $h * 0.8, $c['cta'], $c));
            $objects[] = $this->text('restaurant-brand', 'new-text', $c['brand'], $w - $m, $h - $m * 0.8, $unit * 0.024, $c['muted'], $c, 700, 140, 'center');

            return $objects;
        }

        if ($variant === 'realestate') {
            $objects[] = $this->rect('re-image', $m, $m, $w - 2 * $m, $h * 0.5, ['type' => 'linear', 'angle' => 120, 'stops' => [
                ['offset' => 0, 'color' => $c['colors'][1]],
                ['offset' => 1, 'color' => $c['colors'][2]],
            ]], $unit * 0.016);
            $objects[] = $this->circle('re-sun', $w * 0.72, $h * 0.2, $unit * 0.07, $c['ink'], null, 0, 0.35);
            $objects[] = $this->rect('re-house', $w * 0.28, $h * 0.32, $w * 0.22, $h * 0.12, $c['ink'], $unit * 0.006, 0.4);
            $objects[] = $this->text('re-label', 'new-text', 'FOR SALE', $m, $h * 0.54, $unit * 0.02, $c['accent'], $c, 700, 240);
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.07, 2, $c, 800, 10);
            $objects[] = $this->text('re-headline', 'primary-text', $headline['text'], $m, $h * 0.6, $headline['size'], $c['ink'], $c, 800, 10);
            $objects[] = $this->text('re-sub', 'new-text', $c['subhead'], $m, $h * 0.6 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.015, $unit * 0.026, $c['muted'], $c, 500, 20);
            $objects[] = $this->text('re-price', 'new-text', $c['price'], $m, $h * 0.82, $unit * 0.06, $c['accent'], $c, 800, 0);
            $objects[] = $this->text('re-address', 'new-text', $c['address'], $w - $m, $h * 0.86, $unit * 0.022, $c['muted'], $c, 500, 60, 'center');

            return $objects;
        }

        // Product / generic advertisement.
        $objects[] = $this->text('promo-brand', 'new-text', $c['brand'], $m, $m * 0.65, $unit * 0.028, $c['ink'], $c, 700, 140);
        $productX = $w * 0.6;
        $objects[] = $this->rect('promo-product', $productX, $h * 0.18, $w * 0.33, $h * 0.42, $c['support'], $unit * 0.02, 0.4);
        $objects = array_merge($objects, $this->mark($c, $productX + $w * 0.165, $h * 0.39, $unit * 0.16));
        $headline = $this->block($c['headline'], $w * 0.55, $unit * 0.085, 3, $c, 800, 10);
        $objects[] = $this->text('promo-headline', 'primary-text', $headline['text'], $m, $h * 0.26, $headline['size'], $c['ink'], $c, 800, 10);
        $sub = $this->block($c['subhead'], $w * 0.5, $unit * 0.03, 2, $c, 500, 20);
        $objects[] = $this->text('promo-sub', 'new-text', $sub['text'], $m, $h * 0.26 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.02, $sub['size'], $c['muted'], $c, 500, 20);
        $objects[] = $this->text('promo-price', 'new-text', $c['price'], $m, $h * 0.66, $unit * 0.07, $c['accent'], $c, 800, 0);
        $objects = array_merge($objects, $this->ctaPill('promo-cta', $m, $h * 0.78, $c['cta'], $c));
        if ($c['code'] !== '') {
            $objects[] = $this->text('promo-code', 'new-text', 'Use code '.$c['code'], $w - $m, $h * 0.88, $unit * 0.022, $c['muted'], $c, 600, 80, 'center');
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutCover(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.085;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'magazine') {
            $objects[] = $this->text('mag-masthead', 'primary-text', $c['brand'], $w / 2, $m * 0.55, $unit * 0.11, $c['ink'], $c, 800, 10, 'center');
            $objects[] = $this->rule('mag-rule', $m, $m + $unit * 0.15, $w - 2 * $m, $c['accent'], $unit * 0.004);
            $objects[] = $this->rect('mag-image', $m, $h * 0.22, $w - 2 * $m, $h * 0.4, ['type' => 'linear', 'angle' => 120, 'stops' => [
                ['offset' => 0, 'color' => $c['colors'][1]],
                ['offset' => 1, 'color' => $c['colors'][2]],
            ]], $unit * 0.01);
            $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.4, $unit * 0.18, $c['ink']));
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.09, 3, $c, 800, 0, true);
            $objects[] = $this->text('mag-headline', 'primary-text', $headline['text'], $m, $h * 0.68, $headline['size'], $c['ink'], $c, 800, 0);
            $objects[] = $this->text('mag-sub', 'new-text', $c['subhead'], $m, $h * 0.68 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.015, $unit * 0.03, $c['accent'], $c, 600, 120);
            $objects[] = $this->text('mag-price', 'new-text', $c['price'], $w - $m, $m * 0.7, $unit * 0.026, $c['ink'], $c, 700, 60, 'center');
            $objects[] = $this->rect('mag-barcode', $w - $m - $unit * 0.09, $h - $m - $unit * 0.05, $unit * 0.09, $unit * 0.05, '#FFFFFF');
            for ($i = 0; $i < 8; $i++) {
                $objects[] = $this->rect('mag-barcode-'.$i, $w - $m - $unit * 0.088 + $i * $unit * 0.0105, $h - $m - $unit * 0.048, $unit * 0.003, $unit * 0.045, $c['ink']);
            }
            $objects[] = $this->text('mag-date', 'new-text', $c['date'], $m, $h - $m, $unit * 0.022, $c['muted'], $c, 500, 80);

            return $objects;
        }

        if ($variant === 'album' || $variant === 'episode') {
            $objects[] = $this->circle('album-disc', $w * 0.5, $h * 0.42, $unit * 0.27, 'none', $c['accent'], $unit * 0.004, 0.4);
            $objects[] = $this->circle('album-disc-inner', $w * 0.5, $h * 0.42, $unit * 0.08, $c['accent'], null, 0, 0.5);
            $objects = array_merge($objects, $this->mark($c, $w * 0.5, $h * 0.42, $unit * 0.1));
            if ($variant === 'episode') {
                $objects[] = $this->text('episode-number', 'new-text', $c['headline'], $w / 2, $h * 0.1, $unit * 0.07, $c['accent'], $c, 800, 120, 'center');
                $title = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.06, 2, $c, 700, 10);
                $objects[] = $this->text('episode-title', 'primary-text', $title['text'], $w / 2, $h * 0.68, $title['size'], $c['ink'], $c, 700, 10, 'center');
                $objects[] = $this->text('episode-brand', 'new-text', $c['brand'], $w / 2, $h * 0.9, $unit * 0.026, $c['muted'], $c, 600, 200, 'center');
            } else {
                $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.075, 2, $c, 800, 10, true);
                $objects[] = $this->text('album-title', 'primary-text', $title['text'], $w / 2, $h * 0.7, $title['size'], $c['ink'], $c, 800, 10, 'center');
                $objects[] = $this->text('album-artist', 'new-text', $c['author'], $w / 2, $h * 0.7 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.02, $unit * 0.028, $c['accent'], $c, 600, 160, 'center');
            }

            return $objects;
        }

        if ($variant === 'podcast') {
            $objects[] = $this->circle('podcast-ring-1', $w / 2, $h * 0.36, $unit * 0.26, 'none', $c['accent'], $unit * 0.005, 0.5);
            $objects[] = $this->circle('podcast-ring-2', $w / 2, $h * 0.36, $unit * 0.2, 'none', $c['support'], $unit * 0.004, 0.4);
            $objects[] = $this->text('podcast-mic', 'shape', '●', $w / 2, $h * 0.36 - $unit * 0.09, $unit * 0.17, $c['accent'], $c, 700, 0, 'center');
            $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.08, 2, $c, 800, 10, true);
            $objects[] = $this->text('podcast-title', 'primary-text', $title['text'], $w / 2, $h * 0.64, $title['size'], $c['ink'], $c, 800, 10, 'center');
            $objects[] = $this->text('podcast-sub', 'new-text', $c['subhead'], $w / 2, $h * 0.64 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.015, $unit * 0.028, $c['muted'], $c, 500, 40, 'center');
            $objects[] = $this->text('podcast-brand', 'new-text', $c['brand'], $w / 2, $h * 0.9, $unit * 0.024, $c['accent'], $c, 700, 220, 'center');

            return $objects;
        }

        if ($variant === 'report') {
            $objects[] = $this->rect('report-band', 0, 0, $w, $h * 0.08, $c['accent']);
            $objects = array_merge($objects, $this->mark($c, $m + $unit * 0.04, $h * 0.16, $unit * 0.08));
            $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.09, 3, $c, 800, 0, true);
            $objects[] = $this->text('report-title', 'primary-text', $title['text'], $m, $h * 0.34, $title['size'], $c['ink'], $c, 800, 0);
            $objects[] = $this->text('report-sub', 'new-text', $c['subhead'], $m, $h * 0.34 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.02, $unit * 0.05, $c['accent'], $c, 700, 40);
            $objects[] = $this->rule('report-rule', $m, $h * 0.72, $w * 0.3, $c['ink'], $unit * 0.004);
            $objects[] = $this->text('report-brand', 'new-text', $c['brand'], $m, $h - $m * 1.4, $unit * 0.026, $c['ink'], $c, 700, 140);
            $objects[] = $this->text('report-website', 'new-text', $c['website'], $m, $h - $m * 0.9, $unit * 0.021, $c['muted'], $c, 500, 60);

            return $objects;
        }

        // Book / ebook.
        $objects[] = $this->rect('book-frame', $m * 0.6, $m * 0.6, $w - $m * 1.2, $h - $m * 1.2, 'none', $unit * 0.006);
        $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.3, $unit * 0.13));
        $label = $this->block($variant === 'ebook' ? 'EBOOK' : 'A NOVEL', $w * 0.5, $unit * 0.022, 1, $c, 700, 300, true);
        $objects[] = $this->text('book-label', 'new-text', $label['text'], $w / 2, $h * 0.42, $label['size'], $c['accent'], $c, 700, 300, 'center');
        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.095, 3, $c, 800, 10);
        $objects[] = $this->text('book-title', 'primary-text', $title['text'], $w / 2, $h * 0.5, $title['size'], $c['ink'], $c, 800, 10, 'center');
        $objects[] = $this->rule('book-rule', $w / 2 - $unit * 0.06, $h * 0.5 + $title['size'] * 1.12 * max(1, count($title['lines'])) + $unit * 0.03, $unit * 0.12, $c['accent'], $unit * 0.004);
        $objects[] = $this->text('book-author', 'new-text', $c['author'], $w / 2, $h * 0.82, $unit * 0.036, $c['ink'], $c, 600, 140, 'center');
        $objects[] = $this->text('book-brand', 'new-text', $c['brand'], $w / 2, $h - $m * 0.8, $unit * 0.019, $c['muted'], $c, 500, 220, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutSlide(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.075;
        $objects = [];
        $variant = $c['variant'];

        if ($variant === 'intro') {
            $objects[] = $this->circle('intro-ring', $w * 0.8, $h * 0.2, $unit * 0.3, 'none', $c['accent'], $unit * 0.005, 0.4);
            $objects = array_merge($objects, $this->mark($c, $w / 2, $h * 0.38, $unit * 0.14));
            $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.11, 2, $c, 800, 10, true);
            $objects[] = $this->text('intro-title', 'primary-text', $title['text'], $w / 2, $h * 0.58, $title['size'], $c['ink'], $c, 800, 10, 'center');
            $objects[] = $this->text('intro-brand', 'new-text', $c['brand'], $w / 2, $h * 0.8, $unit * 0.03, $c['accent'], $c, 600, 180, 'center');

            return $objects;
        }

        $panelX = $w * 0.64;
        $objects[] = $this->rect('slide-panel', $panelX, 0, $w - $panelX, $h, $c['support'], 0, 0.32);
        $objects[] = $this->rect('slide-accent', 0, 0, $w * 0.01, $h, $c['accent']);
        $markSize = $unit * 0.07;
        $objects = array_merge($objects, $this->mark($c, $m + $markSize / 2, $m + $markSize / 2, $markSize));
        $objects[] = $this->text('slide-brand', 'new-text', $c['brand'], $m + $markSize + $unit * 0.02, $m + $markSize / 2 - $unit * 0.012, $unit * 0.024, $c['ink'], $c, 700, 120);

        $headline = $this->block($c['headline'], $w * 0.55, $unit * 0.1, 3, $c, 800, 0);
        $objects[] = $this->text('slide-headline', 'primary-text', $headline['text'], $m, $h * 0.28, $headline['size'], $c['ink'], $c, 800, 0);
        $sub = $this->block($c['subhead'], $w * 0.5, $unit * 0.032, 2, $c, 500, 20);
        $objects[] = $this->text('slide-sub', 'new-text', $sub['text'], $m, $h * 0.28 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.025, $sub['size'], $c['accent'], $c, 500, 20);

        if ($variant === 'pitch') {
            $stats = ['$4.2M' => 'Market', '38%' => 'Growth', '12k' => 'Users'];
            $boxW = ($w * 0.5 - 2 * $unit * 0.02) / 3;
            $i = 0;
            foreach ($stats as $value => $label) {
                $x = $m + $i * ($boxW + $unit * 0.02);
                $objects[] = $this->rect('slide-stat-'.$i, $x, $h * 0.64, $boxW, $unit * 0.16, $c['support'], $unit * 0.014, 0.45);
                $objects[] = $this->text('slide-stat-value-'.$i, 'new-text', $value, $x + $unit * 0.02, $h * 0.67, $unit * 0.045, $c['ink'], $c, 800, 0);
                $objects[] = $this->text('slide-stat-label-'.$i, 'new-text', $label, $x + $unit * 0.02, $h * 0.67 + $unit * 0.055, $unit * 0.019, $c['muted'], $c, 500, 80);
                $i++;
            }
        } else {
            $body = $this->block($c['body'], $w * 0.5, $unit * 0.024, 4, $c, 400, 20);
            $objects[] = $this->text('slide-body', 'new-text', $body['text'], $m, $h * 0.62, $body['size'], $c['muted'], $c, 400, 20);
        }

        $objects = array_merge($objects, $this->mark($c, $panelX + ($w - $panelX) / 2, $h * 0.5, $unit * 0.2));
        $objects[] = $this->rule('slide-footer-rule', $m, $h - $m * 0.7, $w - 2 * $m, $c['muted'], $unit * 0.0015, 0.3);
        $objects[] = $this->text('slide-footer', 'new-text', $c['brand'].'  ·  2026', $m, $h - $m * 0.55, $unit * 0.018, $c['muted'], $c, 500, 120);
        $objects[] = $this->text('slide-number', 'new-text', '01', $w - $m, $h - $m * 0.55, $unit * 0.018, $c['muted'], $c, 600, 40, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutCertificate(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.06;
        $objects = [];

        $objects[] = $this->rect('cert-border-outer', $m, $m, $w - 2 * $m, $h - 2 * $m, 'none', $unit * 0.008);
        $objects[] = $this->rule('cert-line-top', $m + $unit * 0.02, $m + $unit * 0.02, $w - 2 * $m - $unit * 0.04, $c['accent'], $unit * 0.004);
        $objects[] = $this->rule('cert-line-bottom', $m + $unit * 0.02, $h - $m - $unit * 0.02, $w - 2 * $m - $unit * 0.04, $c['accent'], $unit * 0.004);
        $objects[] = $this->rect('cert-line-left', $m + $unit * 0.02, $m + $unit * 0.02, $unit * 0.004, $h - 2 * $m - $unit * 0.04, $c['accent']);
        $objects[] = $this->rect('cert-line-right', $w - $m - $unit * 0.02, $m + $unit * 0.02, $unit * 0.004, $h - 2 * $m - $unit * 0.04, $c['accent']);

        $objects[] = $this->text('cert-kicker', 'new-text', 'CERTIFICATE', $w / 2, $h * 0.16, $unit * 0.028, $c['accent'], $c, 700, 300, 'center');
        $title = $this->block($c['headline'], $w * 0.75, $unit * 0.075, 2, $c, 800, 10);
        $objects[] = $this->text('cert-title', 'primary-text', $title['text'], $w / 2, $h * 0.23, $title['size'], $c['ink'], $c, 800, 10, 'center');
        $objects[] = $this->text('cert-presented', 'new-text', 'is proudly presented to', $w / 2, $h * 0.4, $unit * 0.026, $c['muted'], $c, 500, 160, 'center');
        $recipient = $this->block($c['recipient'], $w * 0.7, $unit * 0.09, 1, $c, 700, 20);
        $objects[] = $this->text('cert-recipient', 'primary-text', $recipient['text'], $w / 2, $h * 0.47, $recipient['size'], $c['ink'], $c, 700, 20, 'center');
        $objects[] = $this->rule('cert-recipient-rule', $w / 2 - $unit * 0.18, $h * 0.59, $unit * 0.36, $c['accent'], $unit * 0.003);
        $body = $this->block($c['body'], $w * 0.6, $unit * 0.023, 3, $c, 400, 20);
        $objects[] = $this->text('cert-body', 'new-text', $body['text'], $w / 2, $h * 0.63, $body['size'], $c['muted'], $c, 400, 20, 'center');

        $objects[] = $this->rule('cert-sig-1', $w * 0.14, $h * 0.82, $w * 0.2, $c['ink'], $unit * 0.002);
        $objects[] = $this->text('cert-sig-name-1', 'new-text', $c['issuer'], $w * 0.24, $h * 0.84, $unit * 0.022, $c['ink'], $c, 600, 60, 'center');
        $objects[] = $this->text('cert-sig-role-1', 'new-text', 'Programme director', $w * 0.24, $h * 0.87, $unit * 0.018, $c['muted'], $c, 400, 60, 'center');
        $objects[] = $this->text('cert-date', 'new-text', $c['date'], $w * 0.76, $h * 0.85, $unit * 0.022, $c['ink'], $c, 600, 80, 'center');

        $objects[] = $this->circle('cert-seal', $w * 0.86, $h * 0.24, $unit * 0.075, $c['accent'], null, 0, 0.9);
        $objects[] = $this->circle('cert-seal-ring', $w * 0.86, $h * 0.24, $unit * 0.055, 'none', Color::readableInk($c['accent']), $unit * 0.003);
        $objects[] = $this->text('cert-seal-text', 'shape', '★', $w * 0.86, $h * 0.24 - $unit * 0.03, $unit * 0.05, Color::readableInk($c['accent']), $c, 700, 0, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutInvitation(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.08;
        $cx = $w / 2;
        $objects = [];
        $variant = $c['variant'];

        $border = in_array($variant, ['wedding', 'invitation'], true);
        if ($border) {
            $objects[] = $this->rect('inv-border', $m * 0.5, $m * 0.5, $w - $m, $h - $m, 'none', $unit * 0.004);
            $objects[] = $this->rect('inv-border-inner', $m * 0.7, $m * 0.7, $w - $m * 1.4, $h - $m * 1.4, 'none', $unit * 0.002);
        }

        if ($variant === 'birthday') {
            foreach ([[0.16, 0.14], [0.82, 0.2], [0.24, 0.84], [0.78, 0.8], [0.5, 0.1]] as $index => $point) {
                $objects[] = $this->circle('inv-confetti-'.$index, $w * $point[0], $h * $point[1], $unit * (0.012 + ($index % 3) * 0.006), $c['colors'][$index % 3], null, 0, 0.75);
            }
            $objects[] = $this->text('inv-kicker', 'new-text', 'YOU ARE INVITED', $cx, $h * 0.18, $unit * 0.026, $c['accent'], $c, 700, 280, 'center');
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * 0.12, 2, $c, 800, 0, true);
            $objects[] = $this->text('inv-headline', 'primary-text', $headline['text'], $cx, $h * 0.3, $headline['size'], $c['ink'], $c, 800, 0, 'center');
            $sub = $this->block($c['subhead'], $w - 2 * $m, $unit * 0.032, 2, $c, 500, 20);
            $objects[] = $this->text('inv-sub', 'new-text', $sub['text'], $cx, $h * 0.3 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.03, $sub['size'], $c['muted'], $c, 500, 20, 'center');
        } else {
            $eyebrow = $variant === 'wedding' ? 'TOGETHER WITH THEIR FAMILIES' : ($variant === 'thanks' ? 'WITH GRATITUDE' : 'YOU ARE INVITED');
            $objects[] = $this->text('inv-kicker', 'new-text', $eyebrow, $cx, $h * 0.18, $unit * 0.022, $c['accent'], $c, 700, 300, 'center');
            $objects = array_merge($objects, $this->mark($c, $cx, $h * 0.28, $unit * 0.1));
            $headline = $this->block($c['headline'], $w - 2 * $m, $unit * ($variant === 'wedding' ? 0.095 : 0.075), 2, $c, 700, 20);
            $objects[] = $this->text('inv-headline', 'primary-text', $headline['text'], $cx, $h * 0.4, $headline['size'], $c['ink'], $c, 700, 20, 'center');
            $sub = $this->block($c['subhead'], $w * 0.8, $unit * 0.028, 2, $c, 400, 20);
            $objects[] = $this->text('inv-sub', 'new-text', $sub['text'], $cx, $h * 0.4 + $headline['size'] * 1.12 * max(1, count($headline['lines'])) + $unit * 0.025, $sub['size'], $c['muted'], $c, 400, 20, 'center');
        }

        $objects[] = $this->rule('inv-rule', $cx - $unit * 0.08, $h * 0.68, $unit * 0.16, $c['accent'], $unit * 0.003);
        $meta = array_filter([$c['date'], $c['time'], $c['location']]);
        $metaY = $h * 0.72;
        foreach (array_values($meta) as $index => $line) {
            $objects[] = $this->text('inv-meta-'.$index, 'new-text', $line, $cx, $metaY + $index * $unit * 0.042, $unit * ($index === 0 ? 0.03 : 0.024), $index === 0 ? $c['ink'] : $c['muted'], $c, $index === 0 ? 700 : 500, $index === 0 ? 80 : 60, 'center');
        }
        $objects[] = $this->text('inv-brand', 'new-text', $c['brand'], $cx, $h - $m * 0.85, $unit * 0.021, $c['muted'], $c, 600, 220, 'center');

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    protected function layoutGrid(array $c): array
    {
        $w = $c['width'];
        $h = $c['height'];
        $unit = min($w, $h);
        $m = $unit * 0.07;
        $objects = [];
        $variant = $c['variant'];

        $objects[] = $this->text('grid-brand', 'new-text', $c['brand'], $m, $m * 0.7, $unit * 0.022, $c['accent'], $c, 700, 200, 'left');
        $title = $this->block($c['headline'], $w - 2 * $m, $unit * 0.06, 1, $c, 800, 40, true);
        $objects[] = $this->text('grid-title', 'primary-text', $title['text'], $m, $m * 0.7 + $unit * 0.035, $title['size'], $c['ink'], $c, 800, 40);
        $objects[] = $this->rule('grid-rule', $m, $m * 0.7 + $unit * 0.035 + $title['size'] * 1.25, $w - 2 * $m, $c['accent'], $unit * 0.004);

        if ($variant === 'calendar') {
            $top = $m * 0.7 + $unit * 0.035 + $title['size'] * 1.25 + $unit * 0.04;
            $cols = 7;
            $rows = 5;
            $cellW = ($w - 2 * $m) / $cols;
            $cellH = min(($h - $top - $m * 1.4) / $rows, $cellW * 0.85);
            $days = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
            foreach ($days as $index => $day) {
                $objects[] = $this->text('cal-day-'.$index, 'shape', $day, $m + $cellW * ($index + 0.5), $top, $unit * 0.02, $c['muted'], $c, 700, 120, 'center');
            }
            $dayNumber = 1;
            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $x = $m + $col * $cellW;
                    $y = $top + $unit * 0.04 + $row * $cellH;
                    $objects[] = $this->rect('cal-cell-'.$row.'-'.$col, $x, $y, $cellW - $unit * 0.004, $cellH - $unit * 0.004, $c['support'], $unit * 0.004, 0.18);
                    $objects[] = $this->text('cal-num-'.$row.'-'.$col, 'shape', (string) $dayNumber, $x + $unit * 0.012, $y + $unit * 0.012, $unit * 0.019, $c['ink'], $c, 500, 20);
                    $dayNumber++;
                }
            }
            $objects[] = $this->text('cal-note', 'new-text', $c['body'], $m, $h - $m * 0.9, $unit * 0.019, $c['muted'], $c, 400, 20);

            return $objects;
        }

        if ($variant === 'planner' || $variant === 'digital') {
            $top = $m * 0.7 + $unit * 0.035 + $title['size'] * 1.25 + $unit * 0.05;
            $sections = $variant === 'digital' ? ['Morning', 'Afternoon', 'Evening'] : ['Today', 'This week', 'Notes'];
            $colW = ($w - 2 * $m - 2 * $unit * 0.025) / 3;
            foreach ($sections as $index => $section) {
                $x = $m + $index * ($colW + $unit * 0.025);
                $objects[] = $this->text('plan-section-'.$index, 'new-text', $section, $x, $top, $unit * 0.024, $c['accent'], $c, 700, 160);
                $objects[] = $this->rule('plan-rule-'.$index, $x, $top + $unit * 0.035, $colW, $c['muted'], $unit * 0.0015, 0.4);
                for ($line = 0; $line < 9; $line++) {
                    $y = $top + $unit * 0.065 + $line * $unit * 0.045;
                    $objects[] = $this->rect('plan-check-'.$index.'-'.$line, $x, $y, $unit * 0.014, $unit * 0.014, 'none');
                    $objects[] = $this->rule('plan-line-'.$index.'-'.$line, $x + $unit * 0.022, $y + $unit * 0.013, $colW - $unit * 0.022, $c['muted'], $unit * 0.0012, 0.35);
                }
            }

            return $objects;
        }

        // Worksheet.
        $top = $m * 0.7 + $unit * 0.035 + $title['size'] * 1.25 + $unit * 0.05;
        $objects[] = $this->text('ws-name', 'new-text', 'Name: ______________________', $m, $top, $unit * 0.024, $c['ink'], $c, 400, 20);
        $objects[] = $this->text('ws-date', 'new-text', 'Date: ______________', $w - $m, $top, $unit * 0.024, $c['ink'], $c, 400, 20, 'center');
        $objects[] = $this->text('ws-instructions', 'new-text', $c['subhead'], $m, $top + $unit * 0.05, $unit * 0.021, $c['muted'], $c, 400, 20);
        $body = $this->paragraph($c['body'], $w - 2 * $m, $unit * 0.024, $c, 400, 20);
        foreach (array_slice($body, 0, 12) as $index => $line) {
            $objects[] = $this->text('ws-line-'.$index, 'new-text', $line, $m, $top + $unit * 0.1 + $index * $unit * 0.05, $unit * 0.024, $c['muted'], $c, 400, 20);
            $objects[] = $this->rule('ws-rule-'.$index, $m, $top + $unit * 0.128 + $index * $unit * 0.05, $w - 2 * $m, $c['muted'], $unit * 0.0012, 0.3);
        }

        return $objects;
    }

    /**
     * @param  array<string, mixed>  $c
     * @return array<int, array<string, mixed>>
     */
    private function ctaPill(string $id, float $x, float $y, string $label, array $c, ?string $fill = null, float|string|null $size = null, bool $centered = false): array
    {
        $unit = min($c['width'], $c['height']);
        $fill ??= $c['accent'];
        $textColor = (is_string($size) && str_starts_with($size, '#')) ? $size : Color::readableInk($fill);
        $size = is_numeric($size) ? (float) $size : $unit * 0.026;
        $label = $label !== '' ? $label : 'Learn more';
        $width = $this->lineWidth($label, $size, $c, 700, 60) + $unit * 0.07;
        $height = $size * 2.4;
        $left = $centered ? $x - $width / 2 : $x;

        return [
            $this->rect($id.'-bg', $left, $y, $width, $height, $fill, $height / 2),
            $this->text($id, 'new-text', $label, $left + $width / 2, $y + ($height - $size * 1.12) / 2, $size, $textColor, $c, 700, 60, 'center'),
        ];
    }
}
