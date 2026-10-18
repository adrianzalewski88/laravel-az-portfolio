<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_api_requires_a_token(): void
    {
        $this->getJson('/api/v1/projects')
            ->assertUnauthorized();
    }

    public function test_protected_api_rejects_a_token_without_the_read_scope(): void
    {
        Passport::actingAs(User::factory()->create(), []);

        $this->getJson('/api/v1/projects')
            ->assertForbidden();
    }

    public function test_protected_api_accepts_the_portfolio_read_scope(): void
    {
        Passport::actingAs(
            User::factory()->create(),
            ['portfolio:read']
        );

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_api_user_response_does_not_include_sensitive_fields(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user, []);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token');
    }
}
