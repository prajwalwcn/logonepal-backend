<?php

namespace Tests\Feature;

use App\Models\GeneratedLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeederTestCase;

class MockupPreviewTest extends SeederTestCase
{
    use RefreshDatabase;

    public function test_mockups_are_rendered_from_a_compact_source_payload(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Ember Studio'])->assertOk();
        $logo = GeneratedLogo::query()->firstOrFail();

        $response = $this->postJson('/api/v1/designs/preview-mockups', [
            'source' => ['type' => 'generated', 'id' => $logo->id],
            'text' => ['primary' => 'EMBER STUDIO'],
            'color_updates' => [
                ['from_color' => $logo->palette[1] ?? '#2563EB', 'to_color' => '#FF1F8E'],
            ],
            'business_name' => 'Ember Studio',
        ])->assertOk();

        $mockups = $response->json('data.mockups');

        $this->assertCount(8, $mockups);
        $this->assertArrayHasKey('business-card', $mockups);
        $this->assertStringContainsString('EMBER STUDIO', $mockups['business-card']);
        $this->assertStringContainsString('<svg', $mockups['t-shirt']);
    }

    public function test_the_payload_is_smaller_than_uploading_the_document(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Payload Test'])->assertOk();
        $logo = GeneratedLogo::query()->firstOrFail();

        $compact = json_encode([
            'source' => ['type' => 'generated', 'id' => $logo->id],
            'text' => ['primary' => 'PAYLOAD TEST'],
            'color_updates' => [],
        ]);

        $full = json_encode($logo->design_json);

        $this->assertLessThan(strlen($full), strlen($compact));
    }

    public function test_invalid_sources_are_rejected(): void
    {
        $this->postJson('/api/v1/designs/preview-mockups', [])->assertStatus(422);

        $this->postJson('/api/v1/designs/preview-mockups', [
            'source' => ['type' => 'generated', 'id' => 999999],
        ])->assertStatus(422);
    }
}
