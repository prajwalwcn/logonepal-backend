<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SeederTestCase;

class AuthFlowTest extends SeederTestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'super-secret-1',
            'password_confirmation' => 'super-secret-1',
        ])->assertCreated();

        $this->assertNotEmpty($response->json('token'));

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'ada@example.test');
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'demo@example.test']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'demo@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_guests_cannot_save_designs(): void
    {
        $this->postJson('/api/v1/logos/generate', ['business_name' => 'Guest Brand'])->assertOk();

        $logoId = \App\Models\GeneratedLogo::query()->value('id');

        $this->postJson("/api/v1/logos/{$logoId}/save", [])->assertUnauthorized();
    }

    public function test_signing_out_revokes_the_token(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.test',
            'password' => 'super-secret-1',
            'password_confirmation' => 'super-secret-1',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::query()->count());
    }
}
