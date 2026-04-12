<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/logout';

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson($this->url);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);
    }

    public function test_token_is_revoked_after_logout(): void
    {
        $user = User::factory()->customer()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson($this->url)
            ->assertStatus(200);

        // Verify token was deleted from the database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_logout_returns_401(): void
    {
        $response = $this->postJson($this->url);

        $response->assertStatus(401);
    }
}
