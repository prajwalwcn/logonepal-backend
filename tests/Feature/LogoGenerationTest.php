<?php

namespace Tests\Feature;

use App\Models\GeneratedLogo;
use App\Models\LogoGeneration;
use App\Services\Logo\BrandAnalysisService;
use App\Services\Logo\VariationEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeederTestCase;

class LogoGenerationTest extends SeederTestCase
{
    use RefreshDatabase;

    public function test_guests_can_generate_logos_without_ai_credentials(): void
    {
        config(['services.ai.enabled' => false, 'services.openai.key' => null]);

        $response = $this->postJson('/api/v1/logos/generate', ['business_name' => 'Nexora']);

        $response->assertOk()
            ->assertJsonPath('data.generation.engine', 'template')
            ->assertJsonCount(24, 'data.logos')
            ->assertJsonPath('data.generation.intent.source', 'fallback');

        $this->assertGreaterThanOrEqual(24, GeneratorCount::count());
        $this->assertNotEmpty($response->headers->get('X-Guest-Token'));
    }

    public function test_generated_designs_are_editable_documents(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Everest Coffee'])->assertOk();

        $logo = GeneratedLogo::query()->firstOrFail();
        $design = $logo->design_json;

        $this->assertSame(650, $design['canvas']['width']);
        $this->assertNotEmpty($design['objects']);

        $roles = array_column($design['objects'], 'role');
        $this->assertContains('background', $roles);
        $this->assertContains('primary-text', $roles);
        $this->assertContains('logo', $roles);

        $symbol = collect($design['objects'])->firstWhere('role', 'logo');
        $this->assertSame('group', $symbol['type']);
        $this->assertNotEmpty($symbol['objects'][0]['d']);
    }

    public function test_long_brand_names_are_wrapped_without_clipping(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'The Northern Light Trading Company',
        ])->assertOk();

        foreach ($response->json('data.logos') as $logo) {
            foreach ($logo['design']['objects'] as $object) {
                if ($object['type'] !== 'i-text') {
                    continue;
                }

                $width = (float) ($object['width'] ?? 0);
                $right = ($object['originX'] ?? 'left') === 'center'
                    ? $object['left'] + $width / 2
                    : $object['left'] + $width;

                $this->assertLessThanOrEqual(651, $right, 'Text extends past the artboard.');
            }
        }
    }

    public function test_fallback_analysis_returns_structured_intent(): void
    {
        $intent = app(BrandAnalysisService::class)->analyze('Blue Summit Wealth');

        $this->assertSame('finance', $intent['category_slug']);
        $this->assertNotEmpty($intent['keywords']);
        $this->assertCount(3, $intent['color_palettes'][0]);
        $this->assertSame('fallback', $intent['source']);
    }

    public function test_ambiguous_names_produce_multiple_directions(): void
    {
        $intent = app(BrandAnalysisService::class)->analyze('Nova');

        $this->assertArrayHasKey('directions', $intent);
        $this->assertGreaterThanOrEqual(3, count($intent['directions']));
    }

    public function test_variations_are_diverse_and_do_not_repeat(): void
    {
        $intent = app(BrandAnalysisService::class)->analyze('Everest Coffee');
        $candidates = app(VariationEngineService::class)->candidates($intent, 'Everest Coffee', [], 40);

        $this->assertGreaterThanOrEqual(24, count($candidates));

        $signatures = [];

        foreach ($candidates as $candidate) {
            $signature = implode('|', [
                $candidate['symbol']?->slug,
                $candidate['layout'],
                implode('', $candidate['palette']->colors),
            ]);

            $this->assertNotContains($signature, $signatures, 'Duplicate combination was returned.');
            $signatures[] = $signature;
        }

        $this->assertGreaterThanOrEqual(6, count(array_unique(array_column($candidates, 'layout'))));
    }

    public function test_pagination_returns_the_next_page(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Orbit Labs'])->assertOk();
        $generation = LogoGeneration::query()->firstOrFail();

        $pageTwo = $this->getJson("/api/v1/logos/generation/{$generation->uuid}?page=2")->assertOk();

        $this->assertSame(2, $pageTwo->json('data.meta.current_page'));
        $this->assertCount(24, $pageTwo->json('data.logos'));
    }

    public function test_validation_rejects_invalid_business_names(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => '<script>alert(1)</script>'])
            ->assertStatus(422);
    }

    public function test_preview_endpoint_is_cacheable(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Cache Test'])->assertOk();
        $logo = GeneratedLogo::query()->firstOrFail();

        $response = $this->get("/api/v1/logos/{$logo->id}/preview.svg");
        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));

        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag);

        $this->withHeader('If-None-Match', $etag)
            ->get("/api/v1/logos/{$logo->id}/preview.svg")
            ->assertStatus(304);
    }

    public function test_badge_shield_and_icon_only_layouts_are_generated(): void
    {
        foreach (['badge', 'shield', 'icon-only'] as $layout) {
            $response = $this->postJson('/api/v1/logos/generate', [
                'business_name' => 'Nexora',
                'layout' => $layout,
            ])->assertOk();

            $this->assertNotEmpty(
                $response->json('data.logos'),
                "Layout [{$layout}] returned no results."
            );
        }
    }

    public function test_multi_filters_keywords_and_showcase_tokens_work(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'Manoj',
            'keywords' => 'minimalist',
            'styles' => ['classic', 'minimal'],
            'colors' => ['grayscale'],
            'sort' => 'newest',
        ])->assertOk();

        $this->assertNotEmpty($response->json('data.logos'));

        $showcase = $this->getJson('/api/v1/showcase')->assertOk();
        $first = $showcase->json('data.0');
        $this->assertNotEmpty($first['token']);
        $this->assertStringNotContainsString('{{brand_name}}', $first['svg']);
    }

    public function test_dashboard_includes_favorited_gallery_logos(): void
    {
        $user = \App\Models\User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($user);

        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Manoj'])->assertOk();
        $logo = GeneratedLogo::query()->firstOrFail();

        $this->postJson('/api/v1/favorites/toggle', ['generated_logo_id' => $logo->id])
            ->assertOk()
            ->assertJsonPath('data.favorited', true);

        $dashboard = $this->getJson('/api/v1/user/dashboard')->assertOk();
        $this->assertSame(1, $dashboard->json('data.stats.favorites'));
        $this->assertCount(1, $dashboard->json('data.favorites'));
        $this->assertSame('/editor?logo='.$logo->id, $dashboard->json('data.favorites.0.edit_href'));
    }

    public function test_keywords_and_typos_match_requested_symbols_on_page_one(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'Manoj',
            'keywords' => 'lion, elepjandt',
            'per_page' => 32,
        ])->assertOk();

        $intent = $response->json('data.generation.intent');
        $this->assertSame('elephant', $intent['keyword_corrections']['elepjandt'] ?? null);
        $this->assertContains('lion', $intent['resolved_keywords'] ?? []);
        $this->assertContains('elephant', $intent['resolved_keywords'] ?? []);

        $symbols = collect($response->json('data.logos'))->pluck('symbol')->all();
        $this->assertCount(32, $symbols);

        $lionOrElephantSlugs = \App\Models\LogoAsset::query()
            ->where(function ($q) {
                $q->where('slug', 'like', '%lion%')
                    ->orWhere('slug', 'like', '%elephant%')
                    ->orWhereJsonContains('keywords', 'lion')
                    ->orWhereJsonContains('keywords', 'elephant');
            })
            ->pluck('slug')
            ->all();

        $hasLion = false;
        $hasElephant = false;

        foreach ($symbols as $slug) {
            $this->assertContains($slug, $lionOrElephantSlugs, "Expected lion or elephant symbol on page 1, got [{$slug}].");
            if (str_contains((string) $slug, 'lion')) {
                $hasLion = true;
            }
            if (str_contains((string) $slug, 'elephant')) {
                $hasElephant = true;
            }
        }

        $this->assertTrue($hasLion, 'Expected at least one lion symbol on page 1.');
        $this->assertTrue($hasElephant, 'Expected at least one elephant symbol on page 1.');
    }

    public function test_natural_language_prompt_splits_name_and_keywords(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'mAOJ WITH LION,ELEPJANDT',
            'per_page' => 32,
        ])->assertOk();

        $this->assertSame('mAOJ', $response->json('data.generation.business_name'));
        $intent = $response->json('data.generation.intent');
        $this->assertSame('elephant', $intent['keyword_corrections']['elepjandt'] ?? null);

        $lionOrElephantSlugs = \App\Models\LogoAsset::query()
            ->where(function ($q) {
                $q->where('slug', 'like', '%lion%')
                    ->orWhere('slug', 'like', '%elephant%')
                    ->orWhereJsonContains('keywords', 'lion')
                    ->orWhereJsonContains('keywords', 'elephant');
            })
            ->pluck('slug')
            ->all();

        $symbols = collect($response->json('data.logos'))->pluck('symbol')->all();
        $this->assertNotEmpty($symbols);
        foreach ($symbols as $slug) {
            $this->assertContains($slug, $lionOrElephantSlugs, "Expected lion or elephant symbol, got [{$slug}].");
        }
    }

    public function test_per_path_linear_gradients_render_and_survive_sanitization(): void
    {
        $renderer = app(\App\Services\Logo\SvgRenderingService::class);
        $design = [
            'canvas' => ['width' => 650, 'height' => 520, 'background' => 'transparent'],
            'objects' => [
                [
                    'id' => 'logo-logo',
                    'role' => 'logo',
                    'type' => 'group',
                    'left' => 325,
                    'top' => 260,
                    'scaleX' => 0.4,
                    'scaleY' => 0.4,
                    'angle' => 0,
                    'pathBox' => 512,
                    'objects' => [
                        [
                            'id' => 'symbol-path-1',
                            'type' => 'path',
                            'role' => 'logo-path',
                            'd' => 'M 50 50 L 450 50 L 250 450 Z',
                            'fill' => [
                                'type' => 'linear',
                                'angle' => 135,
                                'stops' => [
                                    ['offset' => 0, 'color' => '#FDE68A'],
                                    ['offset' => 1, 'color' => '#D97706'],
                                ],
                            ],
                            'fill_opacity' => 0.9,
                        ],
                    ],
                ],
            ],
        ];

        $svg = $renderer->sanitize($renderer->render($design));
        $this->assertStringContainsString('<linearGradient', $svg);
        $this->assertStringContainsString('#FDE68A', $svg);
        $this->assertStringContainsString('url(#gradient-', $svg);
        $this->assertStringContainsString('fill-opacity="0.9"', $svg);
    }

    public function test_bulk_svg_importer_preserves_multi_color_and_gradients(): void
    {
        $importer = app(\App\Services\Logo\SvgAssetImporter::class);
        $sampleSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><defs><linearGradient id="gold" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#D97706"/></linearGradient></defs><path d="M40 40 H472 V472 H40 Z" fill="url(#gold)"/><path d="M120 120 H392 V392 H120 Z" fill="#1E3A8A" fill-opacity="0.85"/></svg>';

        $asset = $importer->importSvg($sampleSvg, [
            'name' => 'Test Crown Crest',
            'slug' => 'test-imported-crest',
            'category' => 'badges',
            'keywords' => ['crown', 'crest'],
            'preserve_colors' => true,
        ]);

        $this->assertNotNull($asset);
        $this->assertSame('0 0 512 512', $asset->view_box);
        $this->assertCount(2, $asset->paths);
        $this->assertSame('linear', $asset->paths[0]['gradient']['type'] ?? null);
        $this->assertSame('#1E3A8A', $asset->paths[1]['color'] ?? null);
        $this->assertStringContainsString('<linearGradient', $asset->toSvg('#111827', '#2563EB'));
    }

    public function test_high_detail_symbols_and_gradients_appear_on_page_one(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'Nebula Coffee',
            'keywords' => 'elephant',
            'per_page' => 32,
        ])->assertOk();

        $logos = $response->json('data.logos');
        $this->assertNotEmpty($logos);

        $svgs = implode("\n", array_column($logos, 'svg'));
        $symbols = array_column($logos, 'symbol');

        $this->assertStringContainsString('<linearGradient', $svgs);
        $hasDetailed = collect($symbols)->contains(fn ($s) => is_string($s) && (str_contains($s, 'game-icons-') || str_contains($s, 'elephant')));
        $this->assertTrue($hasDetailed, 'Expected detailed elephant illustration marks on page 1.');
    }

    public function test_travel_business_name_plural_matches_travel_industry_and_emblems(): void
    {
        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'MANOJ TRAVELS',
            'per_page' => 32,
        ])->assertOk();

        $this->assertSame('travel', $response->json('data.generation.intent.category_slug'));
        $this->assertSame('Travel', $response->json('data.generation.intent.industry'));

        $logos = $response->json('data.logos');
        $this->assertCount(32, $logos);

        $travelSlugs = \App\Models\LogoAsset::query()
            ->whereJsonContains('industry_slugs', 'travel')
            ->pluck('slug')
            ->all();

        $bespokeCount = 0;
        foreach ($logos as $logo) {
            $slug = (string) ($logo['symbol'] ?? '');
            $this->assertContains($slug, $travelSlugs, "Expected travel-industry symbol for MANOJ TRAVELS, got [{$slug}].");
            if (str_starts_with($slug, 'travel-') || str_starts_with($slug, 'game-icons-')) {
                $bespokeCount++;
            }
        }

        $this->assertGreaterThanOrEqual(10, $bespokeCount, 'Expected bespoke/detailed travel emblems on page 1.');

        // Page 1 must lead with unique symbols (no immediate back-to-back symbol repetition).
        $uniquePageOne = count(array_unique(array_column($logos, 'symbol')));
        $this->assertGreaterThanOrEqual(16, $uniquePageOne, 'Expected at least 16 unique symbols on page 1.');
    }

    public function test_transparent_png_symbol_imports_renders_and_survives_sanitization(): void
    {
        $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'lf_test_emblem_'.uniqid().'.png';
        file_put_contents($tmpPath, $pngBytes);

        try {
            $importer = app(\App\Services\Logo\SvgAssetImporter::class);
            $asset = $importer->importImage($tmpPath, [
                'name' => 'Golden Travel Emblem PNG',
                'slug' => 'test-png-travel-emblem',
                'category' => 'travel',
                'keywords' => ['travel', 'airplane'],
                'industries' => ['travel'],
            ]);

            $this->assertNotNull($asset);
            $this->assertSame('png', $asset->asset_type);
            $this->assertStringStartsWith('/storage/symbols/', (string) $asset->image_url);
            $this->assertStringContainsString('<image', $asset->toSvg('#111827'));

            $composer = app(\App\Services\Logo\LogoComposer::class);
            $renderer = app(\App\Services\Logo\SvgRenderingService::class);
            $font = \App\Models\Font::query()->firstOrFail();
            $palette = \App\Models\ColorPalette::query()->firstOrFail();

            $design = $composer->compose([
                'brand_name' => 'MANOJ TRAVELS',
                'tagline' => 'SINCE 2026',
                'symbol' => $asset,
                'font' => $font,
                'palette' => $palette,
                'layout' => 'icon-top',
                'style' => 'modern',
            ]);

            $symbolObj = collect($design['objects'])->firstWhere('role', 'logo');
            $this->assertSame('image', $symbolObj['type']);
            $this->assertGreaterThanOrEqual(180, $symbolObj['width']);

            $svg = $renderer->sanitize($renderer->render($design));
            $this->assertStringContainsString('<image', $svg);
            $this->assertStringContainsString('/storage/symbols/test-png-travel-emblem.png', $svg);
        } finally {
            @unlink($tmpPath);
            @unlink(storage_path('app/public/symbols/test-png-travel-emblem.png'));
        }
    }

    public function test_template_variety_engine_generates_seal_ribbon_medallion_and_script_3d(): void
    {
        $composer = app(\App\Services\Logo\LogoComposer::class);
        $renderer = app(\App\Services\Logo\SvgRenderingService::class);
        $symbol = \App\Models\LogoAsset::query()->active()->firstOrFail();
        $font = \App\Models\Font::query()->firstOrFail();
        $palette = \App\Models\ColorPalette::query()->firstOrFail();

        $sealDesign = $composer->compose([
            'brand_name' => 'MANOJ TRAVELS',
            'tagline' => 'SINCE 2026',
            'symbol' => $symbol,
            'font' => $font,
            'palette' => $palette,
            'layout' => 'seal',
            'style' => 'vintage',
            'card_background' => '#F2F8FF',
        ]);
        $sealSvg = $renderer->sanitize($renderer->render($sealDesign));
        $this->assertStringContainsString('<textPath', $sealSvg);
        $this->assertStringContainsString('MANOJ TRAVELS', $sealSvg);
        $this->assertStringContainsString('SINCE 2026', $sealSvg);

        $ribbonDesign = $composer->compose([
            'brand_name' => 'MANOJ TRAVELS',
            'tagline' => 'SINCE 2026',
            'symbol' => $symbol,
            'font' => $font,
            'palette' => $palette,
            'layout' => 'ribbon',
            'style' => 'modern',
            'card_background' => '#FFFBF0',
        ]);
        $ribbonSvg = $renderer->sanitize($renderer->render($ribbonDesign));
        $this->assertStringContainsString('<textPath', $ribbonSvg);
        $this->assertNotNull(collect($ribbonDesign['objects'])->firstWhere('id', 'shape-ribbon'));

        $medallionDesign = $composer->compose([
            'brand_name' => 'MANOJ TRAVELS',
            'tagline' => 'SINCE 2026',
            'symbol' => $symbol,
            'font' => $font,
            'palette' => $palette,
            'layout' => 'medallion',
            'style' => 'modern',
            'card_background' => '#EAE6D7',
        ]);
        $initialObj = collect($medallionDesign['objects'])->firstWhere('id', 'medallion-initial');
        $this->assertSame('M', $initialObj['text'] ?? null);

        $scriptDesign = $composer->compose([
            'brand_name' => 'MANOJ TRAVELS',
            'symbol' => null,
            'font' => $font,
            'palette' => $palette,
            'layout' => 'script-3d',
            'style' => 'retro',
            'card_background' => '#FFFFFF',
        ]);
        $scriptSvg = $renderer->sanitize($renderer->render($scriptDesign));
        $this->assertStringContainsString('<feDropShadow', $scriptSvg);
        $this->assertStringContainsString('manoj travels', $scriptSvg);

        $response = $this->postJson('/api/v1/logos/generate', [
            'business_name' => 'MANOJ TRAVELS',
            'per_page' => 32,
        ])->assertOk();

        $layouts = collect($response->json('data.logos'))->pluck('layout')->unique()->values()->all();
        $this->assertNotEmpty(array_intersect(['seal', 'ribbon', 'medallion', 'script-3d'], $layouts));
    }
}

/**
 * Small helper to keep the assertion readable.
 */
class GeneratorCount
{
    public static function count(): int
    {
        return GeneratedLogo::query()->count();
    }
}
