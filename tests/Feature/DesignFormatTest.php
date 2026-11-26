<?php

namespace Tests\Feature;

use App\Services\Design\DesignFormats;
use App\Services\Design\FormatVariationEngine;
use Tests\TestCase;

class DesignFormatTest extends TestCase
{
    public function test_catalogue_endpoint_lists_every_generator(): void
    {
        $response = $this->getJson('/api/v1/design-formats')->assertOk();

        $this->assertSame(count(DesignFormats::all()), $response->json('data.total'));
        $this->assertGreaterThanOrEqual(120, $response->json('data.total'));
        $this->assertNotEmpty($response->json('data.categories'));
        $this->assertNotEmpty($response->json('meta.palettes'));
    }

    public function test_format_detail_returns_fields_and_options(): void
    {
        $this->getJson('/api/v1/design-formats/business-card')
            ->assertOk()
            ->assertJsonPath('data.slug', 'business-card')
            ->assertJsonPath('data.width', 1050)
            ->assertJsonPath('data.fields.0.key', 'brand')
            ->assertJsonStructure([
                'data' => [
                    'fields' => [['key', 'label', 'type', 'default', 'required']],
                    'options' => ['palettes', 'fonts'],
                ],
            ]);
    }

    public function test_generation_returns_four_editable_concepts(): void
    {
        $response = $this->postJson('/api/v1/design-formats/instagram-post/generate', [
            'brand' => 'Everest Coffee',
            'headline' => 'Fresh roast Fridays',
            'subhead' => 'Small batch, big flavour',
            'cta' => 'Shop the roast',
            'colors' => ['#0F172A', '#EA580C', '#FDE047'],
            'font' => 'Archivo Black',
            'count' => 4,
        ])->assertOk()->assertJsonCount(4, 'data.concepts');

        $concept = $response->json('data.concepts.0');

        $this->assertSame(1080, $concept['width']);
        $this->assertSame(1080, $concept['height']);
        $this->assertSame(1080, $concept['design']['canvas']['width']);
        $this->assertStringContainsString('<svg', $concept['svg']);
        $this->assertNotEmpty($concept['design']['objects']);
        $this->assertContains('background', array_column($concept['design']['objects'], 'role'));
        $this->assertStringContainsString('EVEREST COFFEE', $concept['svg']);
    }

    public function test_every_format_composes_a_renderable_document(): void
    {
        $engine = app(FormatVariationEngine::class);
        $formats = DesignFormats::all();
        $input = [
            'brand' => 'Everest Coffee',
            'headline' => 'Fresh roast Fridays',
            'subhead' => 'Small batch, big flavour',
            'body' => "01 Source\n02 Roast\n03 Ship",
            'steps' => "Source\nRoast\nShip",
            'items' => "Espresso|3.50\nFlat white|4.20",
            'quote' => 'Good coffee, good company.',
            'code' => 'BRAND-001',
        ];

        foreach ($formats as $format) {
            $concepts = $engine->concepts($format, $input, 1);

            $this->assertCount(1, $concepts, $format->slug);
            $this->assertStringContainsString('<svg', $concepts[0]['svg'], $format->slug);
            $this->assertSame($format->width, $concepts[0]['design']['canvas']['width'], $format->slug);
            $this->assertSame($format->height, $concepts[0]['design']['canvas']['height'], $format->slug);
            $this->assertNotEmpty($concepts[0]['design']['objects'], $format->slug);
            $this->assertStringNotContainsString('NaN', $concepts[0]['svg'], $format->slug);
        }
    }

    public function test_cover_archetypes_are_available(): void
    {
        $format = DesignFormats::find('facebook-cover');
        $this->assertNotNull($format);

        $concepts = app(FormatVariationEngine::class)->concepts($format, [
            'brand' => 'Everest Coffee',
            'qr_url' => 'https://everest.coffee',
        ], 18);

        $labels = array_column($concepts, 'label');

        foreach ([
            'Bites & Brews Doodle',
            'Isometric 3D Cube Open',
            'Classic Open Daily Damask',
            'Luxury Menu QR Plum',
            'Curved Ribbon Menu QR',
            'Megaphone Yes We Are Back',
            "Diamond We're Open",
            'Dry Brush Stroke Grunge',
            'Kinetic Giveaway Promo',
            'Welcome To Our Page',
        ] as $label) {
            $this->assertContains($label, $labels);
        }

        $qr = collect($concepts)->firstWhere('label', 'Luxury Menu QR Plum');
        $this->assertNotNull($qr);
        $this->assertGreaterThan(6000, strlen($qr['svg']), 'Expected the menu cover to render a dense QR path.');
        $this->assertGreaterThan(100, substr_count($qr['svg'], 'M '));

        foreach ($concepts as $concept) {
            $this->assertStringNotContainsString('NaN', $concept['svg']);
            $this->assertStringContainsString('<svg', $concept['svg']);
        }
    }

    public function test_primary_emblem_is_consistent_across_every_template(): void
    {
        $format = DesignFormats::find('instagram-post');
        $this->assertNotNull($format);

        $symbol = [
            'asset_type' => 'png',
            'image_url' => '/storage/symbols/primary-emblem.png',
            'view_box' => '0 0 64 64',
            'paths' => [],
        ];

        $concepts = app(FormatVariationEngine::class)->concepts($format, [
            'brand' => 'Everest Coffee',
            'symbol' => $symbol,
        ], 4);

        $this->assertCount(4, $concepts);

        foreach ($concepts as $concept) {
            $this->assertStringContainsString('primary-emblem.png', $concept['svg'], $concept['variant']);
        }
    }

    public function test_unknown_formats_and_invalid_payloads_are_rejected(): void
    {
        $this->getJson('/api/v1/design-formats/not-a-format')->assertNotFound();

        $this->postJson('/api/v1/design-formats/instagram-post/generate', [
            'brand' => 'A',
        ])->assertStatus(422);

        $this->postJson('/api/v1/design-formats/instagram-post/generate', [
            'brand' => 'Everest Coffee',
            'colors' => ['not-a-colour'],
        ])->assertStatus(422);
    }

    public function test_svg_export_returns_a_downloadable_file(): void
    {
        $generated = $this->postJson('/api/v1/design-formats/poster/generate', [
            'brand' => 'Everest Coffee',
        ])->assertOk();

        $response = $this->postJson('/api/v1/design-formats/export', [
            'design_json' => $generated->json('data.concepts.0.design'),
            'format' => 'poster',
            'file' => 'svg',
            'filename' => 'everest-poster',
        ])->assertOk();

        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('<svg', (string) $response->getContent());
    }

    public function test_invalid_design_documents_are_rejected_on_export(): void
    {
        $this->postJson('/api/v1/design-formats/export', [
            'design_json' => ['objects' => [['type' => 'unknown']]],
            'format' => 'poster',
        ])->assertStatus(422);
    }
}
