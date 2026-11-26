<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use App\Models\UserDesign;
use Database\Seeders\AdminRoleSeeder;
use Database\Seeders\SocialPlatformSeeder;
use Database\Seeders\TemplateTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPlatformTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminRoleSeeder::class);
        $this->seed(SocialPlatformSeeder::class);
        $this->seed(TemplateTaxonomySeeder::class);

        $super = AdminRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'role_id' => $super->id,
            'status' => 'active',
        ]);
    }

    public function test_dashboard_returns_real_platform_metrics(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'stats' => ['users_total', 'designs_total', 'ai_generations', 'templates_total', 'assets_total', 'queue_pending'],
                    'charts' => ['daily_users', 'daily_designs', 'daily_ai', 'designs_by_tool'],
                    'recent' => ['users', 'designs', 'assets', 'ai_generations', 'activity'],
                    'health' => ['status', 'checks', 'queue', 'storage', 'runtime'],
                ],
            ]);
    }

    public function test_analytics_accepts_a_date_range(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics?from='.now()->subDays(7)->toDateString().'&to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.range.from', now()->subDays(7)->toDateString())
            ->assertJsonStructure(['data' => ['stats', 'charts', 'tables']]);
    }

    public function test_admin_can_manage_roles_and_permission_matrix(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/roles', [
            'name' => 'Content Editor',
            'description' => 'Manage website content.',
            'permissions' => ['dashboard.view', 'content.view', 'content.manage'],
        ])->assertCreated();

        $roleId = $response->json('data.id');

        $this->putJson("/api/v1/admin/roles/{$roleId}", [
            'permissions' => ['dashboard.view', 'content.view'],
        ])->assertOk()->assertJsonPath('data.permissions.1', 'content.view');

        $this->getJson('/api/v1/admin/roles')
            ->assertOk()
            ->assertJsonStructure(['data', 'permissions' => ['design', 'ai', 'system']]);
    }

    public function test_role_permissions_are_enforced(): void
    {
        $designer = AdminRole::query()->create([
            'name' => 'Limited Designer',
            'slug' => 'limited-designer',
            'permissions' => ['dashboard.view'],
            'is_system' => false,
        ]);
        $user = User::factory()->create(['is_admin' => true, 'role_id' => $designer->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/settings')->assertForbidden();
    }

    public function test_non_admins_cannot_access_admin_platform_routes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->getJson('/api/v1/admin/ai/providers')->assertForbidden();
    }

    public function test_admin_can_manage_ai_providers_prompts_and_prices(): void
    {
        Sanctum::actingAs($this->admin);

        $provider = $this->postJson('/api/v1/admin/ai/providers', [
            'name' => 'Primary OpenAI',
            'driver' => 'openai',
            'model' => 'gpt-4o-mini',
            'api_key' => 'sk-test-secret',
            'is_active' => true,
            'priority' => 1,
        ])->assertCreated()
            ->assertJsonPath('data.has_api_key', true)
            ->assertJsonMissingPath('data.api_key');

        $prompt = $this->postJson('/api/v1/admin/ai/prompts', [
            'name' => 'Logo concept prompt',
            'category' => 'logo',
            'workflow' => 'logo_generation',
            'content' => 'Create a logo for {{brand}} in a {{style}} style.',
            'variables' => ['brand', 'style'],
            'status' => 'published',
        ])->assertCreated();

        $this->putJson('/api/v1/admin/ai/prompts/'.$prompt->json('data.id'), [
            'content' => 'Updated prompt for {{brand}}.',
        ])->assertOk()->assertJsonPath('data.version', 2);

        $this->postJson('/api/v1/admin/ai/prices', [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'input_per_million' => 0.15,
            'output_per_million' => 0.60,
            'currency' => 'USD',
        ])->assertCreated();

        $this->getJson('/api/v1/admin/ai/costs')->assertOk()->assertJsonStructure(['data' => ['summary', 'prices', 'providers']]);
        $this->getJson('/api/v1/admin/ai/jobs')->assertOk()->assertJsonStructure(['data' => ['pending', 'failed']]);

        $this->deleteJson('/api/v1/admin/ai/providers/'.$provider->json('data.id'))->assertOk();
    }

    public function test_admin_can_manage_content_seo_and_support(): void
    {
        Sanctum::actingAs($this->admin);

        $page = $this->postJson('/api/v1/admin/content/pages', [
            'title' => 'AI Logo Maker',
            'slug' => 'ai-logo-maker',
            'type' => 'landing',
            'excerpt' => 'Create a logo in minutes.',
            'body' => 'A real landing page body.',
            'status' => 'published',
            'seo_title' => 'AI Logo Maker',
            'seo_description' => 'Create an editable logo with AI.',
        ])->assertCreated();

        $this->putJson('/api/v1/admin/content/pages/'.$page->json('data.id'), [
            'title' => 'AI Logo Maker & Brand Kit',
        ])->assertOk();

        $this->putJson('/api/v1/admin/content/seo', [
            'default_title' => 'Logoforge Studio',
            'default_description' => 'AI design tools.',
            'robots' => 'index,follow',
        ])->assertOk()->assertJsonPath('data.default_title', 'Logoforge Studio');

        $ticket = $this->postJson('/api/v1/admin/support/tickets', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'subject' => 'Export issue',
            'description' => 'The PNG export is blank.',
            'category' => 'bug',
            'priority' => 'high',
        ])->assertCreated();

        $this->postJson('/api/v1/admin/support/tickets/'.$ticket->json('data.uuid').'/replies', [
            'body' => 'We are investigating this export.',
        ])->assertCreated();

        $this->patchJson('/api/v1/admin/support/tickets/'.$ticket->json('data.uuid'), [
            'status' => 'resolved',
        ])->assertOk()->assertJsonPath('data.status', 'resolved');
    }

    public function test_admin_can_manage_settings_features_catalog_and_collections(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/admin/settings', [
            'group' => 'general',
            'settings' => ['site_name' => 'Logoforge Admin Test'],
        ])->assertOk()->assertJsonPath('data.site_name', 'Logoforge Admin Test');

        $this->putJson('/api/v1/admin/settings/features', [
            'features' => ['logo_generator' => false, 'brand_kits' => true],
        ])->assertOk();

        $industry = $this->postJson('/api/v1/admin/catalog/industries', [
            'name' => 'Climate Tech',
            'accent_color' => '#0F766E',
            'is_active' => true,
        ])->assertCreated();

        $this->putJson('/api/v1/admin/catalog/industries/'.$industry->json('data.id'), [
            'tagline' => 'Technology for a cooler planet.',
        ])->assertOk();

        $this->deleteJson('/api/v1/admin/catalog/industries/'.$industry->json('data.id'))->assertOk();

        $this->postJson('/api/v1/admin/collections', [
            'name' => 'Launch Collection',
            'description' => 'Coordinated launch assets.',
            'is_active' => true,
        ])->assertCreated();

        $this->getJson('/api/v1/admin/collections')->assertOk()->assertJsonStructure(['data', 'templates']);
        $this->getJson('/api/v1/admin/catalog')->assertOk()->assertJsonStructure(['data' => ['industries', 'categories', 'styles', 'fonts', 'palettes']]);
    }

    public function test_admin_can_moderate_a_user_design_and_suspend_a_user(): void
    {
        Sanctum::actingAs($this->admin);

        $owner = User::factory()->create();
        $design = UserDesign::create([
            'user_id' => $owner->id,
            'name' => 'Moderation test',
            'business_name' => 'Moderation test',
            'design_json' => [
                'version' => 1,
                'canvas' => ['width' => 512, 'height' => 512, 'background' => '#FFFFFF'],
                'objects' => [],
                'meta' => [],
            ],
            'is_draft' => false,
        ]);

        $this->patchJson('/api/v1/admin/designs/'.$design->uuid.'/moderation', [
            'status' => 'flagged',
            'note' => 'Needs review.',
        ])->assertOk()->assertJsonPath('data.moderation_status', 'flagged');

        $this->assertDatabaseHas('user_designs', ['id' => $design->id, 'moderation_status' => 'flagged']);

        $this->postJson('/api/v1/admin/users/'.$owner->id.'/suspend')->assertOk();
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'status' => 'suspended']);
        $this->postJson('/api/v1/admin/users/'.$owner->id.'/restore')->assertOk();
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'status' => 'active']);
    }

    public function test_system_health_and_queue_endpoints_are_real(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/system/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'healthy')
            ->assertJsonStructure(['data' => ['checks', 'queue', 'storage', 'runtime']]);

        $this->getJson('/api/v1/admin/system/queue')
            ->assertOk()
            ->assertJsonStructure(['data' => ['pending', 'failed'], 'meta' => ['queue_driver']]);
    }

    public function test_favorites_and_activity_are_available(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/favorites')->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->getJson('/api/v1/admin/activity')->assertOk()->assertJsonStructure(['data', 'meta']);
    }
}
