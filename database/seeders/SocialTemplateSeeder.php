<?php

namespace Database\Seeders;

use App\Models\ColorPalette;
use App\Models\DesignPreset;
use App\Models\Font;
use App\Models\Industry;
use App\Models\LogoAsset;
use App\Models\SocialPlatform;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateCollection;
use App\Models\TemplateCategory;
use App\Models\User;
use App\Services\Social\SocialTemplateComposer;
use App\Services\Social\SocialTemplateService;
use App\Services\Social\TemplateBeautifierService;
use Illuminate\Database\Seeder;

class SocialTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $composer = app(SocialTemplateComposer::class);
        $service = app(SocialTemplateService::class);
        $beautifier = app(TemplateBeautifierService::class);
        $admin = User::query()->where('is_admin', true)->first();

        $platforms = SocialPlatform::query()
            ->with(['designTypes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $industries = Industry::query()->orderBy('sort_order')->get();
        $palettes = ColorPalette::query()->active()->get();
        $fonts = Font::query()->active()->get();
        $symbols = LogoAsset::query()->active()->get();
        $categories = TemplateCategory::query()->orderBy('sort_order')->get();
        $presets = DesignPreset::query()->with(['colorPalette', 'typographyPreset', 'layoutPreset'])->active()->get();

        if ($platforms->isEmpty() || $palettes->isEmpty() || $fonts->isEmpty()) {
            return;
        }

        $showcase = [
            ['brand' => 'Atelier Aura', 'headline' => 'Timeless Craft, Modern Form', 'subhead' => 'Autumn/Winter Architectural Collection Now Live', 'cta' => 'Explore Collection', 'preset' => 'luxury'],
            ['brand' => 'Veloce Cloud', 'headline' => 'Deploy 10x Faster at Scale', 'subhead' => 'Zero-config edge infrastructure built for modern engineering teams.', 'cta' => 'Start Free Trial', 'preset' => 'futuristic'],
            ['brand' => 'Saffron & Cedar', 'headline' => 'Seasonal Tasting Menu', 'subhead' => 'Wood-fired heritage dishes crafted with local organic harvests.', 'cta' => 'Reserve a Table', 'preset' => 'editorial'],
            ['brand' => 'Kinetix Studio', 'headline' => 'Move With Pure Intention', 'subhead' => 'Strength, mobility, and recovery classes tailored to your rhythm.', 'cta' => 'Book First Session', 'preset' => 'bold'],
            ['brand' => 'Apex Capital', 'headline' => 'Clarity for Every Milestone', 'subhead' => 'Strategic wealth architecture and institutional advisory.', 'cta' => 'Schedule Briefing', 'preset' => 'corporate'],
            ['brand' => 'Verde Botanicals', 'headline' => 'Pure Cold-Pressed Rituals', 'subhead' => 'Small-batch skincare formulated without synthetic fillers.', 'cta' => 'Shop Essentials', 'preset' => 'organic'],
            ['brand' => 'Monolith Press', 'headline' => 'Design Systems Quarterly', 'subhead' => 'Issue 14: Swiss Grids, Variable Type & Motion Principles.', 'cta' => 'Read Issue 14', 'preset' => 'swiss-design'],
            ['brand' => 'Prism Collective', 'headline' => 'Launch Day: 30% Off Studio', 'subhead' => 'Limited-edition creative kits available through Sunday.', 'cta' => 'Claim Offer', 'preset' => 'playful'],
        ];

        // Remove older low-score duplicates from earlier partial seed runs so catalog is clean
        SocialTemplate::query()->where('source', 'seed')->delete();

        $createdTemplates = collect();

        foreach ($platforms as $platformIndex => $platform) {
            foreach ($platform->designTypes->take(3) as $typeIndex => $type) {
                $seed = $platformIndex * 3 + $typeIndex;
                $item = $showcase[$seed % count($showcase)];
                $preset = $presets->firstWhere('slug', $item['preset']) ?? ($presets->isNotEmpty() ? $presets[$seed % $presets->count()] : null);
                $palette = $preset?->colorPalette ?? $palettes[$seed % $palettes->count()];
                $fontFamily = $preset?->heading_font ?? $fonts[($seed * 3) % $fonts->count()]->family;
                $symbol = $symbols->isNotEmpty() ? $symbols[($seed * 5) % $symbols->count()] : null;
                $industry = $industries->isNotEmpty() ? $industries[$seed % $industries->count()] : null;
                $category = $categories->isNotEmpty() ? $categories[$seed % $categories->count()] : null;

                $rawDesign = $composer->compose($type, [
                    'brand' => $item['brand'],
                    'headline' => $item['headline'],
                    'subhead' => $item['subhead'],
                    'cta' => $item['cta'],
                    'handle' => '@'.strtolower(str_replace([' ', '&'], ['', 'and'], $item['brand'])),
                    'website' => strtolower(str_replace([' ', '&'], ['', 'and'], $item['brand'])).'.com',
                    'colors' => $palette->colors,
                    'font' => $fontFamily,
                    'style' => $preset?->style_key ?? 'modern',
                    'symbol' => $symbol,
                ]);

                // Apply rule-based beautifier (WCAG contrast, safe-area clamping, overflow fitting)
                $beautified = $beautifier->transform($rawDesign, $type, [
                    'design_preset_id' => $preset?->id,
                    'color_palette_id' => $palette->id,
                    'typography_preset_id' => $preset?->typography_preset_id,
                    'fix_contrast' => true,
                    'fit_overflow' => true,
                    'enforce_safe_area' => true,
                    'prune_decorations' => true,
                ]);

                $template = $service->create([
                    'name' => $item['brand'].' — '.$type->name,
                    'description' => "Premium {$platform->name} {$type->name} template styled with {$preset?->name} rules.",
                    'design_type_id' => $type->id,
                    'industry_id' => $industry?->id,
                    'category_id' => $category?->id,
                    'styles' => array_values(array_unique([$preset?->style_key ?? 'modern', 'premium'])),
                    'keywords' => array_values(array_unique(array_merge($industry?->keywords ?? [], [$preset?->style_key ?? 'modern', $platform->slug]))),
                    'tags' => ['social', 'premium', $platform->slug],
                    'palette' => $palette->colors,
                    'fonts' => array_values(array_unique([$fontFamily, $preset?->body_font ?? 'Inter'])),
                    'design_json' => $beautified['design'],
                    'license_type' => 'standard',
                    'is_premium' => $typeIndex === 0,
                    'is_featured' => $typeIndex === 0,
                    'is_trending' => $typeIndex === 1,
                    'status' => SocialTemplate::STATUS_PUBLISHED,
                    'source' => 'seed',
                    'asset_refs' => $symbol !== null ? ['symbol_slug' => $symbol->slug] : [],
                ], $admin);

                $createdTemplates->push($template);
            }
        }

        // Seed curated collections if none exist yet
        if (SocialTemplateCollection::query()->count() === 0 && $createdTemplates->count() >= 6) {
            $launchKit = SocialTemplateCollection::create([
                'name' => 'Premium Brand Launch Kit',
                'slug' => 'premium-brand-launch-kit',
                'description' => 'Coordinated multi-platform templates for product launches and brand announcements.',
                'is_active' => true,
                'created_by' => $admin?->id,
            ]);
            $launchKit->templates()->sync(
                $createdTemplates->take(6)->values()->mapWithKeys(fn ($t, $i) => [$t->id => ['sort_order' => $i]])->all()
            );
        }
    }
}
