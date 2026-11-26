<?php

namespace App\Services\Logo;

/**
 * Composes the finished logo into brand-context mockups as pure SVG.
 *
 * Mockups are drawn server-side (no rasteriser required) so the preview modal
 * loads instantly and the payload the editor sends stays tiny.
 */
class MockupRenderingService
{
    public const VARIANTS = [
        'business-card', 'website', 'app-icon', 't-shirt', 'packaging', 'stationery', 'social', 'signage',
    ];

    public function __construct(private readonly SvgRenderingService $renderer)
    {
    }

    /**
     * @return array<string, string> variant => SVG markup
     */
    public function renderAll(array $design, string $businessName, array $palette = []): array
    {
        $mockups = [];

        foreach (self::VARIANTS as $variant) {
            $mockups[$variant] = $this->render($design, $variant, $businessName, $palette);
        }

        return $mockups;
    }

    public function render(array $design, string $variant, string $businessName, array $palette = []): string
    {
        $logo = $this->innerSvg($design);
        $accent = $palette[1] ?? '#FF1F8E';
        $ink = $palette[0] ?? '#050748';

        return match ($variant) {
            'business-card' => $this->businessCard($logo, $businessName, $ink, $accent),
            'website' => $this->website($logo, $businessName, $ink, $accent),
            'app-icon' => $this->appIcon($logo, $accent),
            't-shirt' => $this->tShirt($logo, $ink),
            'packaging' => $this->packaging($logo, $businessName, $ink, $accent),
            'stationery' => $this->stationery($logo, $businessName, $ink),
            'social' => $this->social($logo, $businessName, $ink, $accent),
            default => $this->signage($logo, $businessName, $ink),
        };
    }

    /**
     * Render the foreground artwork via the authoritative SvgRenderingService
     * so frames, shapes, hidden layers and text effects stay faithful across mockups.
     */
    private function innerSvg(array $design): string
    {
        return $this->renderer->sanitize(
            $this->renderer->render($design, ['transparent' => true, 'crop' => false])
        );
    }

    private function businessCard(string $logo, string $name, string $ink, string $accent): string
    {
        return $this->frame(1000, 640, '#F3F9FF', '
            <rect x="120" y="120" width="760" height="430" rx="18" fill="#FFFFFF"/>
            <rect x="120" y="120" width="760" height="430" rx="18" fill="none" stroke="#E4E7EC"/>
            <g transform="translate(190 170) scale(0.52)">'.$logo.'</g>
            <rect x="700" y="150" width="6" height="120" fill="'.$accent.'" rx="3"/>
            <text x="730" y="200" font-family="Poppins, sans-serif" font-size="26" font-weight="600" fill="'.$ink.'">'.$this->escape($name).'</text>
            <text x="730" y="236" font-family="Open Sans, sans-serif" font-size="16" fill="#6A6A8E">hello@'.strtolower(preg_replace('/[^a-z0-9]/i', '', $name)).'.com</text>
        ');
    }

    private function website(string $logo, string $name, string $ink, string $accent): string
    {
        return $this->frame(1200, 750, '#FFFFFF', '
            <rect x="0" y="0" width="1200" height="92" fill="#FFFFFF"/>
            <line x1="0" y1="92" x2="1200" y2="92" stroke="#EEF0F5"/>
            <g transform="translate(48 22) scale(0.09)">'.$logo.'</g>
            <rect x="920" y="30" width="120" height="34" rx="17" fill="'.$accent.'"/>
            <text x="980" y="53" font-family="Poppins, sans-serif" font-size="14" fill="#FFFFFF" text-anchor="middle">Get started</text>
            <g transform="translate(300 150) scale(0.9)">'.$logo.'</g>
            <rect x="300" y="640" width="600" height="14" rx="7" fill="#EEF0F5"/>
            <rect x="300" y="672" width="380" height="14" rx="7" fill="#F5F6FA"/>
        ');
    }

    private function appIcon(string $logo, string $accent): string
    {
        return $this->frame(600, 600, '#FFFFFF', '
            <defs><linearGradient id="app-bg" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="'.$accent.'" stop-opacity="0.95"/>
                <stop offset="1" stop-color="#050748"/>
            </linearGradient></defs>
            <rect x="130" y="110" width="340" height="340" rx="80" fill="url(#app-bg)"/>
            <g transform="translate(300 280) scale(0.55) translate(-325 -260)">'.$logo.'</g>
            <text x="300" y="520" font-family="Poppins, sans-serif" font-size="22" fill="#6A6A8E" text-anchor="middle">App icon</text>
        ');
    }

    private function tShirt(string $logo, string $ink): string
    {
        return $this->frame(700, 700, '#F8F9FA', '
            <path d="M250 150 L150 200 L110 320 L190 350 L190 600 L510 600 L510 350 L590 320 L550 200 L450 150 L400 130 L300 130 Z" fill="'.$ink.'"/>
            <g transform="translate(350 340) scale(0.42) translate(-325 -260)" opacity="0.96">'.$logo.'</g>
        ');
    }

    private function packaging(string $logo, string $name, string $ink, string $accent): string
    {
        return $this->frame(900, 700, '#FFF8F2', '
            <rect x="180" y="180" width="420" height="420" rx="14" fill="'.$accent.'" opacity="0.12"/>
            <rect x="200" y="200" width="380" height="400" rx="10" fill="#FFFFFF" stroke="#EEF0F5"/>
            <g transform="translate(390 330) scale(0.42) translate(-325 -260)">'.$logo.'</g>
            <text x="390" y="560" font-family="Poppins, sans-serif" font-size="20" font-weight="600" fill="'.$ink.'" text-anchor="middle">'.$this->escape($name).'</text>
        ');
    }

    private function stationery(string $logo, string $name, string $ink): string
    {
        return $this->frame(900, 700, '#FFFFFF', '
            <rect x="140" y="120" width="400" height="520" rx="8" fill="#FFFFFF" stroke="#EEF0F5"/>
            <g transform="translate(340 250) scale(0.36) translate(-325 -260)">'.$logo.'</g>
            <rect x="200" y="430" width="280" height="8" rx="4" fill="#EEF0F5"/>
            <rect x="200" y="456" width="220" height="8" rx="4" fill="#F5F6FA"/>
            <text x="200" y="590" font-family="Open Sans, sans-serif" font-size="15" fill="#6A6A8E">'.$this->escape($name).'</text>
            <rect x="600" y="200" width="220" height="320" rx="8" fill="'.$ink.'" opacity="0.04"/>
        ');
    }

    private function social(string $logo, string $name, string $ink, string $accent): string
    {
        return $this->frame(700, 700, '#F3F9FF', '
            <circle cx="350" cy="300" r="140" fill="#FFFFFF"/>
            <g transform="translate(350 300) scale(0.34) translate(-325 -260)">'.$logo.'</g>
            <circle cx="350" cy="430" r="26" fill="'.$accent.'"/>
            <rect x="232" y="472" width="236" height="16" rx="8" fill="#DDE3F0"/>
            <text x="350" y="560" font-family="Poppins, sans-serif" font-size="24" font-weight="600" fill="'.$ink.'" text-anchor="middle">'.$this->escape($name).'</text>
        ');
    }

    private function signage(string $logo, string $name, string $ink): string
    {
        return $this->frame(1000, 600, '#F8F9FA', '
            <rect x="100" y="150" width="800" height="300" rx="16" fill="#FFFFFF" stroke="#E4E7EC"/>
            <g transform="translate(500 300) scale(0.62) translate(-325 -260)">'.$logo.'</g>
            <rect x="100" y="470" width="800" height="24" rx="12" fill="'.$ink.'" opacity="0.06"/>
        ');
    }

    private function frame(int $width, int $height, string $background, string $content): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">'
            .'<rect width="'.$width.'" height="'.$height.'" fill="'.$background.'"/>'
            .$content
            .'</svg>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
