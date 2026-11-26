<?php

namespace Tests\Feature;

use App\Models\ColorPalette;
use App\Models\Font;
use App\Models\SocialDesignType;
use App\Models\SocialTemplate;
use App\Models\SocialTemplateVariation;
use App\Models\User;
use App\Services\Social\SocialTemplateQualityService;
use App\Services\Social\SocialTemplateService;
use App\Services\Social\SocialTemplateVariationService;
use Database\Seeders\SocialPlatformSeeder;
use Database\Seeders\TemplateTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SocialPlatformSeeder::class);
        $this->seed(TemplateTaxonomySeeder::class);

        $this->admin = User::factory()->create(['is_admin' => true]);

        foreach ([
            ['slug' => 'test-ink', 'name' => 'Test Ink', 'colors' => ['#0F172A', '#2563EB', '#F8FAFC']],
            ['slug' => 'test-amber', 'name' => 'Test Amber', 'colors' => ['#111827', '#F59E0B', '#FFFFFF']],
            ['slug' => 'test-violet', 'name' => 'Test Violet', 'colors' => ['#1E1B4B', '#C084FC', '#FAF5FF']],
        ] as $index => $palette) {
            ColorPalette::create([
                'name' => $palette['name'],
                'slug' => $palette['slug'],
                'colors' => $palette['colors'],
                'mood' => 'Confident',
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        foreach ([
            ['slug' => 'poppins', 'name' => 'Poppins', 'family' => 'Poppins', 'category' => 'sans-serif'],
            ['slug' => 'playfair-display', 'name' => 'Playfair Display', 'family' => 'Playfair Display', 'category' => 'serif'],
            ['slug' => 'archivo-black', 'name' => 'Archivo Black', 'family' => 'Archivo Black', 'category' => 'display'],
        ] as $index => $font) {
            Font::create([
                'name' => $font['name'],
                'slug' => $font['slug'],
                'family' => $font['family'],
                'google_family' => $font['family'],
                'category' => $font['category'],
                'weights' => [400, 500, 600, 700],
                'has_italic' => false,
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $this->createFixtureTemplate('instagram-square-post', false, 'Free Instagram Launch', true);
        $this->createFixtureTemplate('facebook-cover', true, 'Premium Facebook Cover', false);
    }

    public function test_gallery_lists_published_templates_without_layer_payload(): void
    {
        $response = $this->getJson('/api/v1/social-templates')->assertOk();

        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('design', $data[0]);
        $this->assertArrayHasKey('preview_url', $data[0]);
        $this->assertSame('published', $data[0]['status']);
    }

    public function test_gallery_filters_by_platform(): void
    {
        $response = $this->getJson('/api/v1/social-templates?platform=instagram&per_page=48')
            ->assertOk();

        $data = $response->json('data');

        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $this->assertSame('instagram', $item['platform']['slug']);
        }
    }

    public function test_layers_endpoint_returns_editable_fabric_document(): void
    {
        $template = SocialTemplate::query()->published()->firstOrFail();

        $response = $this->getJson('/api/v1/social-templates/'.$template->slug.'/layers')->assertOk();

        $this->assertNotEmpty($response->json('data.design.objects'));
        $this->assertSame($template->width, $response->json('data.design.canvas.width'));
        $this->assertArrayHasKey('safe_area', $response->json('data'));
        $this->assertArrayHasKey('quality', $response->json('data'));
    }

    public function test_guest_cannot_use_a_template(): void
    {
        $template = SocialTemplate::query()->published()->firstOrFail();

        $this->postJson('/api/v1/social-templates/'.$template->slug.'/use')->assertUnauthorized();
    }

    public function test_all_users_can_use_a_premium_marked_template_without_a_subscription(): void
    {
        $premium = SocialTemplate::query()->where('is_premium', true)->firstOrFail();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/social-templates/'.$premium->slug.'/use')
            ->assertCreated()
            ->assertJsonStructure(['data' => ['uuid', 'name', 'edit_href', 'svg']]);

        $this->assertDatabaseHas('user_designs', ['user_id' => $user->id]);
    }

    public function test_admin_routes_reject_non_admins(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/social-templates')->assertForbidden();
    }

    public function test_admin_can_create_publish_and_duplicate_a_template(): void
    {
        $type = SocialDesignType::query()->where('slug', 'instagram-square-post')->firstOrFail();
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/social-templates', [
            'name' => 'Test Launch Template',
            'design_type_id' => $type->id,
            'design_json' => $this->validDesign($type),
            'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.status', 'draft');

        $slug = $response->json('data.slug');

        $this->postJson('/api/v1/admin/social-templates/'.$slug.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->postJson('/api/v1/admin/social-templates/'.$slug.'/duplicate')
            ->assertCreated();

        $this->assertDatabaseHas('social_templates', ['slug' => $slug, 'status' => 'published']);
        $this->assertGreaterThanOrEqual(2, SocialTemplate::query()->where('name', 'like', 'Test Launch Template%')->count());
    }

    public function test_resize_reflows_to_the_target_canvas(): void
    {
        $template = SocialTemplate::query()->published()->firstOrFail();
        $target = SocialDesignType::query()->where('slug', 'instagram-story')->firstOrFail();

        $response = $this->postJson('/api/v1/social-templates/resize', [
            'template_uuid' => $template->uuid,
            'target_design_type' => 'instagram-story',
        ])->assertOk();

        $this->assertSame($target->width, $response->json('data.design.canvas.width'));
        $this->assertSame($target->height, $response->json('data.design.canvas.height'));
        $this->assertNotEmpty($response->json('data.svg'));
    }

    public function test_variation_engine_deduplicates_fingerprints(): void
    {
        $template = SocialTemplate::query()->published()->firstOrFail();

        $variations = app(SocialTemplateVariationService::class)->generate($template, 4);

        $this->assertGreaterThan(0, $variations->count());
        $this->assertSame(
            $variations->count(),
            $variations->pluck('fingerprint')->unique()->count()
        );
        $this->assertSame(
            $variations->count(),
            SocialTemplateVariation::query()->where('template_id', $template->id)->count()
        );
    }

    public function test_quality_inspection_flags_text_outside_the_canvas(): void
    {
        $type = SocialDesignType::query()->firstOrFail();

        $design = [
            'canvas' => ['width' => $type->width, 'height' => $type->height, 'background' => '#FFFFFF'],
            'objects' => [[
                'id' => 'headline',
                'type' => 'i-text',
                'role' => 'primary-text',
                'left' => $type->width + 300,
                'top' => 40,
                'width' => 500,
                'height' => 80,
                'fontSize' => 48,
                'fontFamily' => 'Poppins',
                'fill' => '#111111',
                'text' => 'Outside the canvas',
            ]],
        ];

        $report = app(SocialTemplateQualityService::class)->inspect($design, $type);

        $this->assertFalse($report['valid']);
        $this->assertNotEmpty($report['errors']);
    }

    public function test_platform_catalogue_exposes_dimensions_and_safe_areas(): void
    {
        $response = $this->getJson('/api/v1/social-templates/platforms')->assertOk();
        $instagram = collect($response->json('data'))->firstWhere('slug', 'instagram');

        $this->assertNotNull($instagram);
        $this->assertNotEmpty($instagram['design_types']);
        $this->assertArrayHasKey('safe_area', $instagram['design_types'][0]);
        $this->assertArrayHasKey('export_formats', $instagram['design_types'][0]);
    }

    private function createFixtureTemplate(string $typeSlug, bool $premium, string $name, bool $featured): void
    {
        $type = SocialDesignType::query()->where('slug', $typeSlug)->firstOrFail();

        app(SocialTemplateService::class)->create([
            'name' => $name,
            'design_type_id' => $type->id,
            'design_json' => $this->validDesign($type),
            'styles' => ['modern', 'minimal', 'bold', 'editorial', 'gradient'],
            'keywords' => ['launch', 'social'],
            'tags' => ['social'],
            'palette' => ['#0F172A', '#2563EB', '#F8FAFC'],
            'fonts' => ['Poppins'],
            'is_premium' => $premium,
            'is_featured' => $featured,
            'is_trending' => ! $featured,
            'status' => 'published',
        ], $this->admin);
    }

    /**
     * @return array<string, mixed>
     */
    private function validDesign(SocialDesignType $type): array
    {
        return [
            'canvas' => [
                'width' => $type->width,
                'height' => $type->height,
                'background' => '#FFFFFF',
            ],
            'objects' => [
                [
                    'id' => 'background',
                    'type' => 'rect',
                    'role' => 'background',
                    'left' => 0,
                    'top' => 0,
                    'width' => $type->width,
                    'height' => $type->height,
                    'fill' => '#F8FAFC',
                ],
                [
                    'id' => 'headline',
                    'type' => 'i-text',
                    'role' => 'primary-text',
                    'left' => 90,
                    'top' => 140,
                    'width' => 900,
                    'fontSize' => 64,
                    'fontFamily' => 'Poppins',
                    'fontWeight' => '700',
                    'fill' => '#0F172A',
                    'text' => 'Launch something remarkable',
                    'lineHeight' => 1.1,
                    'textAlign' => 'left',
                ],
            ],
            'meta' => [],
        ];
    }
}
