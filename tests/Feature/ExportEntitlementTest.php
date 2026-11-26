<?php

namespace Tests\Feature;

use App\Models\GeneratedLogo;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\SeederTestCase;

class ExportEntitlementTest extends SeederTestCase
{
    use RefreshDatabase;

    public function test_free_users_cannot_download_vector_files(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $design = $this->saveDesignFor($user);

        $this->postJson("/api/v1/designs/{$design['uuid']}/export", ['format' => 'svg'])
            ->assertForbidden()
            ->assertJsonPath('upgrade_required', true);
    }

    public function test_free_users_can_download_a_standard_png(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $design = $this->saveDesignFor($user);

        $response = $this->postJson("/api/v1/designs/{$design['uuid']}/export", ['format' => 'png']);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.download_url'));
    }

    public function test_paid_users_can_download_svg(): void
    {
        $user = User::factory()->create();
        Order::create([
            'user_id' => $user->id,
            'plan_id' => Plan::query()->value('id'),
            'status' => 'paid',
            'total_cents' => 2900,
            'subtotal_cents' => 2900,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $design = $this->saveDesignFor($user);

        $response = $this->postJson("/api/v1/designs/{$design['uuid']}/export", ['format' => 'svg'])
            ->assertOk();

        $url = $response->json('data.download_url');
        $this->assertStringContainsString('signature=', $url);

        $file = $this->get($url)->assertOk();
        $this->assertStringContainsString('<svg', $file->streamedContent());
    }

    public function test_a_design_cannot_be_exported_by_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($owner);
        $design = $this->saveDesignFor($owner);

        Sanctum::actingAs($other);

        $this->postJson("/api/v1/designs/{$design['uuid']}/export", ['format' => 'png'])
            ->assertForbidden();
    }

    private function saveDesignFor(User $user): array
    {
        $logo = GeneratedLogo::query()->first();

        if ($logo === null) {
            $this->postJson('/api/v1/logos/generate', ['business_name' => 'Export Test'])->assertOk();
            $logo = GeneratedLogo::query()->firstOrFail();
        }

        return $this->postJson('/api/v1/logos/'.$logo->id.'/save', ['name' => 'Test design'])
            ->assertCreated()
            ->json('data');
    }
}
