<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\Industry;
use App\Models\LogoAsset;
use App\Models\LogoStyle;
use App\Models\LogoTemplate;
use App\Models\Plan;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Logo\LogoComposer;
use App\Services\Logo\SvgRenderingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $composer = app(LogoComposer::class);
        $renderer = app(SvgRenderingService::class);

        $this->seedIndustries();
        $this->seedCategories();
        $this->seedStyles();
        $this->seedSymbols();
        $this->seedFonts();
        $this->seedPalettes();
        $this->seedPlans();
        $this->seedSettings();

        User::query()->updateOrCreate(
            ['email' => 'admin@logoforge.test'],
            [
                'name' => 'Logoforge Admin',
                'password' => 'password',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'demo@logoforge.test'],
            [
                'name' => 'Demo Customer',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->seedTemplates($composer, $renderer);

        $this->call([
            AdminRoleSeeder::class,
            SocialPlatformSeeder::class,
            TemplateTaxonomySeeder::class,
            SocialTemplateSeeder::class,
        ]);
    }

    private function seedIndustries(): void
    {
        foreach (config('industries') as $index => $industry) {
            Industry::query()->updateOrCreate(
                ['slug' => $industry['slug']],
                [
                    'name' => $industry['name'],
                    'tagline' => $industry['tagline'],
                    'description' => $industry['description'],
                    'accent_color' => $industry['accent'],
                    'keywords' => $industry['keywords'],
                    'symbol_hints' => $industry['symbol_hints'],
                    'meta_title' => $industry['name'].' Logo Maker — Design a '.$industry['name'].' Logo in Minutes',
                    'meta_description' => Str::limit($industry['description'], 150),
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        Cache::forget('logoforge:symbol-vocab:v2');
    }

    private function seedCategories(): void
    {
        $categories = [
            ['Abstract', 'abstract'], ['Geometric', 'geometric'], ['Nature', 'nature'],
            ['Monogram', 'monogram'], ['Emblem', 'emblem'], ['Vintage', 'vintage'],
            ['Minimal', 'minimal'], ['Typography', 'typography'],
        ];

        foreach ($categories as $index => [$name, $slug]) {
            Category::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $name.' logo concepts and templates.', 'is_active' => true, 'sort_order' => $index]
            );
        }
    }

    private function seedStyles(): void
    {
        $styles = [
            'Minimal', 'Modern', 'Geometric', 'Luxury', 'Vintage', 'Retro', 'Corporate',
            'Playful', 'Elegant', 'Bold', 'Futuristic', 'Tech', 'Organic', 'Abstract',
            'Monogram', 'Mascot', 'Badge',
        ];

        foreach ($styles as $index => $name) {
            LogoStyle::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $name.' logo direction.', 'is_active' => true, 'sort_order' => $index]
            );
        }
    }

    private function seedSymbols(): void
    {
        $symbols = require database_path('seeders/data/symbols.php');
        $mascotsPath = database_path('seeders/data/mascots.php');
        $travelPath = database_path('seeders/data/travel_emblems.php');
        $iconifyPath = database_path('seeders/data/iconify_symbols.json');

        if (is_file($mascotsPath)) {
            $mascots = require $mascotsPath;

            if (is_array($mascots)) {
                $symbols = array_merge($symbols, $mascots);
            }
        }

        if (is_file($travelPath)) {
            $travel = require $travelPath;

            if (is_array($travel)) {
                $symbols = array_merge($symbols, $travel);
            }
        }

        if (is_file($iconifyPath)) {
            $extra = json_decode((string) file_get_contents($iconifyPath), true);

            if (is_array($extra)) {
                $symbols = array_merge($symbols, $extra);
            }
        }

        foreach ($symbols as $index => $symbol) {
            $keywords = $symbol['keywords'] ?? [];

            if (isset($symbol['category']) && is_string($symbol['category']) && ! in_array($symbol['category'], $keywords, true)) {
                $keywords[] = $symbol['category'];
            }

            LogoAsset::query()->updateOrCreate(
                ['slug' => $symbol['slug']],
                [
                    'name' => $symbol['name'],
                    'view_box' => $symbol['view_box'] ?? '0 0 64 64',
                    'paths' => $symbol['paths'],
                    'keywords' => array_values(array_unique($keywords)),
                    'style_slugs' => $symbol['styles'] ?? [],
                    'industry_slugs' => $symbol['industries'] ?? [],
                    'is_premium' => (bool) ($symbol['premium'] ?? false),
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        Cache::forget('logoforge:symbol-vocab:v2');
    }

    private function seedFonts(): void
    {
        $fonts = require database_path('seeders/data/fonts.php');

        foreach ($fonts as $index => $font) {
            Font::query()->updateOrCreate(
                ['slug' => $font['slug']],
                [
                    'name' => $font['name'],
                    'family' => $font['family'],
                    'google_family' => $font['family'],
                    'category' => $font['category'],
                    'weights' => $font['weights'],
                    'has_italic' => $font['italic'],
                    'preview_text' => $font['name'],
                    'is_premium' => $font['premium'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        Cache::forget('logoforge:symbol-vocab:v2');
    }

    private function seedPalettes(): void
    {
        $palettes = require database_path('seeders/data/palettes.php');

        foreach ($palettes as $index => $palette) {
            ColorPalette::query()->updateOrCreate(
                ['slug' => $palette['slug']],
                [
                    'name' => $palette['name'],
                    'colors' => $palette['colors'],
                    'mood' => $palette['mood'],
                    'is_dark' => $palette['is_dark'] ?? false,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }

        Cache::forget('logoforge:symbol-vocab:v2');
    }

    private function seedPlans(): void
    {
        $plans = [
            [
                'name' => 'Free', 'slug' => 'free', 'price_cents' => 0, 'interval' => 'one_time',
                'description' => 'Explore concepts and preview in the editor at no cost.',
                'features' => ['Unlimited logo concepts', 'Full browser editor', 'PNG preview download', 'Mockup previews'],
                'limits' => ['downloads' => 1, 'formats' => ['png']], 'sort_order' => 0,
            ],
            [
                'name' => 'Logo Pack', 'slug' => 'logo-pack', 'price_cents' => 2900, 'interval' => 'one_time',
                'description' => 'One logo, every format you need to launch.',
                'features' => ['1 logo licence', 'SVG + PNG + PDF', 'Transparent background', 'High-resolution exports'],
                'limits' => ['downloads' => 50, 'logo_licences' => 1], 'sort_order' => 1,
            ],
            [
                'name' => 'Brand Kit', 'slug' => 'brand-kit', 'price_cents' => 7900, 'interval' => 'one_time',
                'description' => 'The complete identity pack with every variant.',
                'features' => ['Commercial licence', 'Black & white variants', 'Horizontal & vertical layouts', 'Icon-only files', 'ZIP brand bundle'],
                'limits' => ['downloads' => -1, 'logo_licences' => 1], 'is_popular' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Studio', 'slug' => 'studio', 'price_cents' => 1900, 'interval' => 'month',
                'description' => 'For freelancers and agencies shipping brands weekly.',
                'features' => ['Unlimited logo licences', 'All export formats', 'AI symbol generation', 'Priority rendering'],
                'limits' => ['downloads' => -1, 'logo_licences' => -1], 'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge(['currency' => 'USD', 'is_active' => true], $plan)
            );
        }
    }

    private function seedSettings(): void
    {
        SiteSetting::put('brand', [
            'name' => 'Logoforge',
            'tagline' => 'A complete brand identity from one business name.',
            'support_email' => 'hello@logoforge.test',
        ], 'brand');

        SiteSetting::put('generation', [
            'page_size' => 24,
            'stored_results' => 96,
        ], 'engine');
    }

    private function seedTemplates(LogoComposer $composer, SvgRenderingService $renderer): void
    {
        $fonts = Font::query()->get()->keyBy('slug');
        $palettes = ColorPalette::query()->get();
        $symbols = LogoAsset::query()->get();

        if ($symbols->isEmpty() || $palettes->isEmpty() || $fonts->isEmpty()) {
            return;
        }

        $layouts = ['icon-left', 'icon-top', 'badge', 'monogram', 'stacked', 'emblem', 'icon-left', 'wordmark'];
        $sampleNames = [
            'technology' => 'Nexora',
            'ai' => 'Aether AI',
            'software' => 'Veloce',
            'finance' => 'Northbridge',
            'real-estate' => 'Summit Realty',
            'restaurant' => 'Saffron Bistro',
            'coffee' => 'Everest Coffee',
            'food' => 'Harvest Co',
            'fitness' => 'Pulse Club',
            'beauty' => 'Lumiere',
            'fashion' => 'Atelier Noire',
            'education' => 'Brightleaf',
            'healthcare' => 'Veridian Care',
            'travel' => 'Horizon Pass',
            'photography' => 'Aperture',
            'automotive' => 'Torque Motors',
            'construction' => 'Ironworks',
            'consulting' => 'Meridian',
            'gaming' => 'Valkyrie',
            'ecommerce' => 'Cartcraft',
            'law' => 'Lexington',
            'architecture' => 'Arclight',
            'music' => 'Resonance',
            'agriculture' => 'Terraflora',
            'marketing' => 'Sparkline',
            'pet-businesses' => 'Pawsome',
            'entertainment' => 'Marquee',
        ];

        $industries = Industry::query()->orderBy('sort_order')->get();
        $index = 0;

        foreach ($industries as $industry) {
            $hints = $industry->symbol_hints ?? [];

            $matches = $symbols->filter(fn (LogoAsset $symbol) => in_array($industry->slug, $symbol->industry_slugs ?? [], true));
            $candidates = $matches->isNotEmpty() ? $matches : $symbols->filter(fn (LogoAsset $symbol) => array_intersect($hints, $symbol->keywords ?? []) !== []);

            if ($candidates->isEmpty()) {
                $candidates = $symbols;
            }

            $picked = $candidates->take(2);
            $sampleBrand = $sampleNames[$industry->slug] ?? $industry->name;

            foreach ($picked as $offset => $symbol) {
                $fontSlug = $offset === 0 ? 'poppins' : 'playfair-display';
                $font = $fonts[$fontSlug] ?? $fonts->first();
                $palette = $palettes[($index + $offset) % $palettes->count()];
                $layout = $layouts[$index % count($layouts)];
                $style = $industry->name === 'Fashion' ? 'luxury' : ($industry->name === 'Technology' ? 'tech' : 'modern');

                $design = $composer->compose([
                    'brand_name' => $sampleBrand,
                    'symbol' => $symbol,
                    'font' => $font,
                    'palette' => $palette,
                    'layout' => $layout,
                    'style' => $style,
                ]);

                $svg = $renderer->sanitize($renderer->render($design));

                LogoTemplate::query()->updateOrCreate(
                    ['slug' => $industry->slug.'-'.$symbol->slug.'-'.$layout],
                    [
                        'name' => $industry->name.' '.$symbol->name,
                        'industry_id' => $industry->id,
                        'category_id' => Category::query()->inRandomOrder()->value('id'),
                        'style_id' => LogoStyle::query()->where('slug', $style)->value('id'),
                        'default_font_id' => $font->id,
                        'symbol_asset_id' => $symbol->id,
                        'layout' => $layout,
                        'orientation' => \App\Services\Logo\Layouts::orientation($layout),
                        'keywords' => array_merge($industry->keywords ?? [], $symbol->keywords ?? []),
                        'design_json' => $design,
                        'svg' => $svg,
                        'is_premium' => $symbol->is_premium || $offset === 1,
                        'is_active' => true,
                        'popularity' => 100 - $index,
                    ]
                );

                $index++;
            }
        }
    }
}
