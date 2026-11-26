<?php

namespace Database\Seeders;

use App\Models\ColorPalette;
use App\Models\DesignPreset;
use App\Models\LayoutPreset;
use App\Models\TypographyPreset;
use App\Support\Color;
use Illuminate\Database\Seeder;

class DesignImprovementSeeder extends Seeder
{
    public function run(): void
    {
        $this->enrichColorPalettes();
        $typography = $this->seedTypographyPresets();
        $layouts = $this->seedLayoutPresets();
        $this->seedDesignPresets($typography, $layouts);
    }

    private function enrichColorPalettes(): void
    {
        $extraPalettes = [
            ['name' => 'Champagne & Obsidian', 'slug' => 'champagne-obsidian', 'colors' => ['#111111', '#D4AF37', '#F9F6F0', '#27272A'], 'mood' => 'luxury', 'category' => 'luxury', 'industry_slug' => 'real-estate', 'is_dark' => true],
            ['name' => 'Soft Sage Pastel', 'slug' => 'soft-sage-pastel', 'colors' => ['#2F4F4F', '#84A98C', '#F4F7F5', '#52796F'], 'mood' => 'calm', 'category' => 'pastel', 'industry_slug' => 'wellness', 'is_dark' => false],
            ['name' => 'Electric Coral Pop', 'slug' => 'electric-coral-pop', 'colors' => ['#FF4D4D', '#FFB703', '#FFFFFF', '#1E293B'], 'mood' => 'energetic', 'category' => 'vibrant', 'industry_slug' => 'marketing', 'is_dark' => false],
            ['name' => 'Swiss Monochrome', 'slug' => 'swiss-monochrome', 'colors' => ['#09090B', '#DC2626', '#FAFAFA', '#52525B'], 'mood' => 'minimal', 'category' => 'minimal', 'industry_slug' => 'architecture', 'is_dark' => false],
            ['name' => 'Autumn Harvest', 'slug' => 'autumn-harvest', 'colors' => ['#7C2D12', '#D97706', '#FFFBEB', '#451A03'], 'mood' => 'warm', 'category' => 'seasonal', 'industry_slug' => 'food', 'is_dark' => false],
        ];

        foreach ($extraPalettes as $index => $item) {
            ColorPalette::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true, 'sort_order' => 100 + $index])
            );
        }

        foreach (ColorPalette::query()->get() as $palette) {
            $colors = is_array($palette->colors) ? $palette->colors : ['#0F172A', '#2563EB', '#FFFFFF'];
            $bg = $palette->is_dark ? ($colors[0] ?? '#0F172A') : '#FFFFFF';
            $heading = $palette->is_dark ? '#FFFFFF' : ($colors[0] ?? '#0F172A');
            if (Color::contrast($heading, $bg) < 4.5) {
                $heading = Color::contrast('#0F172A', $bg) >= Color::contrast('#FFFFFF', $bg) ? '#0F172A' : '#FFFFFF';
            }
            $body = Color::contrast($colors[3] ?? $heading, $bg) >= 4.5 ? ($colors[3] ?? $heading) : $heading;
            $accent = $colors[1] ?? ($colors[0] ?? '#2563EB');

            $palette->update([
                'category' => $palette->category ?: ($palette->mood ?: 'modern'),
                'background_color' => $bg,
                'heading_color' => $heading,
                'body_color' => $body,
                'accent_color' => $accent,
                'contrast_ratio' => round(Color::contrast($heading, $bg), 2),
            ]);
        }
    }

    /**
     * @return array<string, TypographyPreset>
     */
    private function seedTypographyPresets(): array
    {
        $items = [
            ['name' => 'Swiss Grotesk Pair', 'slug' => 'swiss-grotesk', 'category' => 'minimal', 'description' => 'Crisp geometric heading paired with high-legibility sans body.', 'heading_font' => 'Plus Jakarta Sans', 'body_font' => 'Inter', 'heading_weight' => 700, 'body_weight' => 400, 'scale_ratio' => 1.40, 'line_height_heading' => 1.08, 'line_height_body' => 1.45, 'letter_spacing_heading' => -20, 'letter_spacing_body' => 0, 'text_alignment' => 'left'],
            ['name' => 'Luxury Editorial Serif', 'slug' => 'luxury-editorial-serif', 'category' => 'luxury', 'description' => 'High-contrast serif display paired with refined sans captions.', 'heading_font' => 'Playfair Display', 'body_font' => 'DM Sans', 'heading_weight' => 700, 'body_weight' => 400, 'scale_ratio' => 1.50, 'line_height_heading' => 1.10, 'line_height_body' => 1.50, 'letter_spacing_heading' => -10, 'letter_spacing_body' => 10, 'text_alignment' => 'center'],
            ['name' => 'Corporate Executive', 'slug' => 'corporate-executive', 'category' => 'corporate', 'description' => 'Balanced enterprise hierarchy built for clarity and trust.', 'heading_font' => 'Poppins', 'body_font' => 'Inter', 'heading_weight' => 600, 'body_weight' => 400, 'scale_ratio' => 1.33, 'line_height_heading' => 1.15, 'line_height_body' => 1.48, 'letter_spacing_heading' => -5, 'letter_spacing_body' => 0, 'text_alignment' => 'left'],
            ['name' => 'Promotional Impact', 'slug' => 'promotional-impact', 'category' => 'promotional', 'description' => 'High-impact condensed display for offers, launches, and ads.', 'heading_font' => 'Montserrat', 'body_font' => 'DM Sans', 'heading_weight' => 800, 'body_weight' => 500, 'scale_ratio' => 1.55, 'line_height_heading' => 1.04, 'line_height_body' => 1.38, 'letter_spacing_heading' => -25, 'letter_spacing_body' => 5, 'text_alignment' => 'center'],
            ['name' => 'Modern Tech Mono', 'slug' => 'modern-tech-mono', 'category' => 'display', 'description' => 'Sharp futuristic geometric proportions for SaaS and tech.', 'heading_font' => 'Space Grotesk', 'body_font' => 'Inter', 'heading_weight' => 700, 'body_weight' => 400, 'scale_ratio' => 1.38, 'line_height_heading' => 1.10, 'line_height_body' => 1.45, 'letter_spacing_heading' => -15, 'letter_spacing_body' => 0, 'text_alignment' => 'left'],
            ['name' => 'Warm Heritage Serif', 'slug' => 'warm-heritage-serif', 'category' => 'editorial', 'description' => 'Classic literary serif pairing for artisan and vintage brands.', 'heading_font' => 'Lora', 'body_font' => 'DM Sans', 'heading_weight' => 700, 'body_weight' => 400, 'scale_ratio' => 1.35, 'line_height_heading' => 1.16, 'line_height_body' => 1.50, 'letter_spacing_heading' => 0, 'letter_spacing_body' => 5, 'text_alignment' => 'left'],
            ['name' => 'Friendly Rounded', 'slug' => 'friendly-rounded', 'category' => 'playful', 'description' => 'Approachable open counters for lifestyle, food, and community.', 'heading_font' => 'Outfit', 'body_font' => 'Poppins', 'heading_weight' => 700, 'body_weight' => 400, 'scale_ratio' => 1.33, 'line_height_heading' => 1.14, 'line_height_body' => 1.45, 'letter_spacing_heading' => -5, 'letter_spacing_body' => 0, 'text_alignment' => 'center'],
        ];

        $map = [];
        foreach ($items as $index => $item) {
            $preset = TypographyPreset::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'rules' => [
                        'max_lines_heading' => 3,
                        'max_lines_body' => 3,
                        'min_font_size' => 14,
                        'auto_fit_overflow' => true,
                        'wrap_margin_ratio' => 0.88,
                    ],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ])
            );
            $map[$preset->slug] = $preset;
        }

        return $map;
    }

    /**
     * @return array<string, LayoutPreset>
     */
    private function seedLayoutPresets(): array
    {
        $items = [
            ['name' => 'Centered Hero', 'slug' => 'centered-hero', 'layout_type' => 'centered', 'alignment' => 'center', 'compatible_formats' => ['square', 'portrait', 'story'], 'description' => 'Balanced vertical center stack with focal emblem, headline, subhead, and CTA.', 'proportions' => ['margin_x' => 0.09, 'margin_y' => 0.10, 'gap' => 0.035, 'heading_y' => 0.42, 'subhead_y' => 0.62, 'cta_y' => 0.78, 'logo_y' => 0.22]],
            ['name' => 'Split 50/50', 'slug' => 'split-50-50', 'layout_type' => 'split', 'alignment' => 'left', 'compatible_formats' => ['landscape', 'banner', 'cover', 'square'], 'description' => 'Left typography stack paired with right visual accent / product frame.', 'proportions' => ['margin_x' => 0.07, 'margin_y' => 0.10, 'gap' => 0.03, 'heading_y' => 0.34, 'subhead_y' => 0.56, 'cta_y' => 0.74, 'text_max_width' => 0.52, 'visual_x' => 0.62]],
            ['name' => 'Asymmetric Editorial', 'slug' => 'asymmetric-editorial', 'layout_type' => 'asymmetric', 'alignment' => 'left', 'compatible_formats' => ['square', 'portrait', 'landscape'], 'description' => 'Off-axis dynamic hierarchy with generous negative space.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.09, 'gap' => 0.04, 'heading_y' => 0.28, 'subhead_y' => 0.54, 'cta_y' => 0.80, 'text_max_width' => 0.68]],
            ['name' => 'Editorial Feature', 'slug' => 'editorial-feature', 'layout_type' => 'editorial', 'alignment' => 'left', 'compatible_formats' => ['portrait', 'story', 'square'], 'description' => 'Magazine-inspired top eyebrow, large display title, and bottom footer bar.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.08, 'gap' => 0.03, 'heading_y' => 0.26, 'subhead_y' => 0.50, 'cta_y' => 0.84, 'text_max_width' => 0.84]],
            ['name' => 'Structured Grid', 'slug' => 'structured-grid', 'layout_type' => 'grid', 'alignment' => 'left', 'compatible_formats' => ['square', 'landscape', 'cover'], 'description' => 'Swiss-inspired modular grid with clean rule dividers.', 'proportions' => ['margin_x' => 0.07, 'margin_y' => 0.08, 'gap' => 0.03, 'heading_y' => 0.30, 'subhead_y' => 0.56, 'cta_y' => 0.78, 'text_max_width' => 0.78]],
            ['name' => 'Image-Focused Showcase', 'slug' => 'image-focused', 'layout_type' => 'image-focused', 'alignment' => 'center', 'compatible_formats' => ['square', 'portrait', 'story'], 'description' => 'Prominent upper visual/symbol zone with anchored lower caption.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.08, 'gap' => 0.025, 'logo_y' => 0.30, 'heading_y' => 0.62, 'subhead_y' => 0.76, 'cta_y' => 0.86]],
            ['name' => 'Typography-Focused Poster', 'slug' => 'typography-focused', 'layout_type' => 'typography-focused', 'alignment' => 'left', 'compatible_formats' => ['square', 'portrait', 'story'], 'description' => 'Bold typographic statement filling 70%+ of the safe canvas.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.10, 'gap' => 0.04, 'heading_y' => 0.32, 'subhead_y' => 0.64, 'cta_y' => 0.82, 'text_max_width' => 0.84]],
            ['name' => 'Product Spotlight', 'slug' => 'product-focused', 'layout_type' => 'product-focused', 'alignment' => 'left', 'compatible_formats' => ['square', 'landscape', 'banner'], 'description' => 'Clear product/brand badge paired with offer headline and CTA.', 'proportions' => ['margin_x' => 0.07, 'margin_y' => 0.09, 'gap' => 0.03, 'heading_y' => 0.36, 'subhead_y' => 0.58, 'cta_y' => 0.76, 'text_max_width' => 0.56]],
            ['name' => 'Promotional Banner', 'slug' => 'promotional-banner', 'layout_type' => 'promotional', 'alignment' => 'center', 'compatible_formats' => ['square', 'landscape', 'cover', 'story'], 'description' => 'High-contrast promo framing with centered headline and strong action button.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.10, 'gap' => 0.03, 'heading_y' => 0.38, 'subhead_y' => 0.58, 'cta_y' => 0.76]],
            ['name' => 'Full-Bleed Frame', 'slug' => 'full-bleed', 'layout_type' => 'full-bleed', 'alignment' => 'left', 'compatible_formats' => ['story', 'portrait', 'cover', 'landscape'], 'description' => 'Edge-to-edge background surface with safe-area padded lower-third typography.', 'proportions' => ['margin_x' => 0.08, 'margin_y' => 0.08, 'gap' => 0.025, 'heading_y' => 0.54, 'subhead_y' => 0.70, 'cta_y' => 0.84, 'text_max_width' => 0.82]],
        ];

        $map = [];
        foreach ($items as $index => $item) {
            $preset = LayoutPreset::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'safe_area' => ['top' => 0.06, 'right' => 0.06, 'bottom' => 0.06, 'left' => 0.06],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ])
            );
            $map[$preset->slug] = $preset;
        }

        return $map;
    }

    /**
     * @param  array<string, TypographyPreset>  $typography
     * @param  array<string, LayoutPreset>  $layouts
     */
    private function seedDesignPresets(array $typography, array $layouts): void
    {
        $palettes = ColorPalette::query()->active()->get();
        $findPalette = fn (string $slug) => $palettes->firstWhere('slug', $slug) ?? $palettes->first();

        $styles = [
            ['name' => 'Minimalist Clean', 'slug' => 'minimalist', 'style_key' => 'minimalist', 'palette' => 'swiss-monochrome', 'typo' => 'swiss-grotesk', 'layout' => 'centered-hero', 'bg' => 'solid', 'shape' => 'clean', 'img' => 'top'],
            ['name' => 'Luxury Gold & Noir', 'slug' => 'luxury', 'style_key' => 'luxury', 'palette' => 'champagne-obsidian', 'typo' => 'luxury-editorial-serif', 'layout' => 'centered-hero', 'bg' => 'solid', 'shape' => 'hairline', 'img' => 'center'],
            ['name' => 'Corporate Precision', 'slug' => 'corporate', 'style_key' => 'corporate', 'palette' => 'midnight-gold', 'typo' => 'corporate-executive', 'layout' => 'split-50-50', 'bg' => 'solid', 'shape' => 'sharp', 'img' => 'right'],
            ['name' => 'Modern Studio', 'slug' => 'modern', 'style_key' => 'modern', 'palette' => 'electric-coral-pop', 'typo' => 'swiss-grotesk', 'layout' => 'asymmetric-editorial', 'bg' => 'subtle-accent', 'shape' => 'rounded', 'img' => 'right'],
            ['name' => 'Editorial Magazine', 'slug' => 'editorial', 'style_key' => 'editorial', 'palette' => 'swiss-monochrome', 'typo' => 'luxury-editorial-serif', 'layout' => 'editorial-feature', 'bg' => 'solid', 'shape' => 'divider', 'img' => 'top'],
            ['name' => 'Bold Promotional', 'slug' => 'bold', 'style_key' => 'bold', 'palette' => 'electric-coral-pop', 'typo' => 'promotional-impact', 'layout' => 'promotional-banner', 'bg' => 'high-contrast', 'shape' => 'pill', 'img' => 'center'],
            ['name' => 'Creative Asymmetric', 'slug' => 'creative', 'style_key' => 'creative', 'palette' => 'soft-sage-pastel', 'typo' => 'friendly-rounded', 'layout' => 'asymmetric-editorial', 'bg' => 'layered', 'shape' => 'organic', 'img' => 'right'],
            ['name' => 'Retro Warmth', 'slug' => 'retro', 'style_key' => 'retro', 'palette' => 'autumn-harvest', 'typo' => 'warm-heritage-serif', 'layout' => 'centered-hero', 'bg' => 'warm-paper', 'shape' => 'framed', 'img' => 'top'],
            ['name' => 'Vintage Heritage', 'slug' => 'vintage', 'style_key' => 'vintage', 'palette' => 'autumn-harvest', 'typo' => 'warm-heritage-serif', 'layout' => 'editorial-feature', 'bg' => 'cream', 'shape' => 'double-border', 'img' => 'center'],
            ['name' => 'Geometric Modular', 'slug' => 'geometric', 'style_key' => 'geometric', 'palette' => 'swiss-monochrome', 'typo' => 'modern-tech-mono', 'layout' => 'structured-grid', 'bg' => 'grid', 'shape' => 'geometric', 'img' => 'right'],
            ['name' => 'Gradient Glow', 'slug' => 'gradient', 'style_key' => 'gradient', 'palette' => 'electric-coral-pop', 'typo' => 'swiss-grotesk', 'layout' => 'full-bleed', 'bg' => 'gradient', 'shape' => 'soft', 'img' => 'center'],
            ['name' => 'Elegant Serif', 'slug' => 'elegant', 'style_key' => 'elegant', 'palette' => 'soft-sage-pastel', 'typo' => 'luxury-editorial-serif', 'layout' => 'centered-hero', 'bg' => 'solid', 'shape' => 'hairline', 'img' => 'top'],
            ['name' => 'Futuristic Tech', 'slug' => 'futuristic', 'style_key' => 'futuristic', 'palette' => 'champagne-obsidian', 'typo' => 'modern-tech-mono', 'layout' => 'split-50-50', 'bg' => 'dark-tech', 'shape' => 'angular', 'img' => 'right'],
            ['name' => 'Organic Botanical', 'slug' => 'organic', 'style_key' => 'organic', 'palette' => 'soft-sage-pastel', 'typo' => 'warm-heritage-serif', 'layout' => 'image-focused', 'bg' => 'soft-tint', 'shape' => 'organic', 'img' => 'top'],
            ['name' => 'Playful Brand', 'slug' => 'playful', 'style_key' => 'playful', 'palette' => 'electric-coral-pop', 'typo' => 'friendly-rounded', 'layout' => 'promotional-banner', 'bg' => 'bright', 'shape' => 'pill', 'img' => 'center'],
            ['name' => 'Swiss Grid Design', 'slug' => 'swiss-design', 'style_key' => 'swiss-design', 'palette' => 'swiss-monochrome', 'typo' => 'swiss-grotesk', 'layout' => 'structured-grid', 'bg' => 'solid', 'shape' => 'rule-line', 'img' => 'right'],
        ];

        foreach ($styles as $index => $item) {
            $typo = $typography[$item['typo']] ?? reset($typography);
            $layout = $layouts[$item['layout']] ?? reset($layouts);
            $palette = $findPalette($item['palette']);

            DesignPreset::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'style_key' => $item['style_key'],
                    'description' => "Rule-based {$item['name']} preset with calibrated hierarchy, spacing, and contrast.",
                    'color_palette_id' => $palette?->id,
                    'typography_preset_id' => $typo?->id,
                    'layout_preset_id' => $layout?->id,
                    'heading_font' => $typo?->heading_font ?? 'Poppins',
                    'body_font' => $typo?->body_font ?? 'Inter',
                    'font_hierarchy' => ['heading' => 1.0, 'subhead' => 0.44, 'body' => 0.34, 'cta' => 0.36],
                    'spacing_rules' => ['margin' => 64, 'padding' => 48, 'gap' => 24],
                    'layout_type' => $layout?->layout_type ?? 'centered',
                    'image_placement' => $item['img'],
                    'background_style' => $item['bg'],
                    'shape_style' => $item['shape'],
                    'decoration_rules' => ['max_decorations' => 4, 'allow_borders' => true, 'prune_excess' => true],
                    'border_shadow_settings' => ['border_width' => $item['shape'] === 'hairline' ? 1 : 0, 'border_radius' => $item['shape'] === 'pill' ? 999 : 12],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
