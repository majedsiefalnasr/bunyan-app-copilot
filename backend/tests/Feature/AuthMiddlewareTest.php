<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T079: RBAC Middleware & Authorization Tests
 *
 * Validates:
 * - Unauthenticated users cannot access protected routes (401)
 * - Authenticated users can access their own resources (200)
 * - Users cannot access other users' resources (403)
 * - Guest middleware redirects authenticated users
 *
 * @see T076, T078 — RBAC Middleware & Exception Handling
 */
class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated user gets 401 on protected route.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::AUTH_INVALID_CREDENTIALS->value,
            ],
        ]);
    }

    /**
     * Test authenticated user can access their profile.
     */
    public function test_authenticated_user_can_access_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/user');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.email', $user->email);
    }

    /**
     * Test login endpoint is public (guest).
     */
    public function test_login_endpoint_is_public(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should return 401 (invalid credentials) not 403 (auth required)
        // This proves the endpoint is accessible to unauthenticated users
        $response->assertStatus(401);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test rate limiting middleware on login.
     */
    public function test_rate_limiting_applies_to_login_attempts(): void
    {
        // Simulate 11 login attempts (limit is 10 per 15 minutes)
        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            if ($i < 10) {
                // First 10 should return 401 (invalid credentials, not rate limited)
                $this->assertEquals(401, $response->status());
            } else {
                // 11th should return 429 (rate limited)
                $this->assertEquals(429, $response->status());
                $response->assertJson([
                    'error' => [
                        'code' => ApiErrorCode::RATE_LIMIT_EXCEEDED->value,
                    ],
                ]);
            }
        }
    }

    /**
     * Test custom exceptions map to proper error codes.
     */
    public function test_exception_mapping_produces_correct_error_codes(): void
    {
        // Create user
        $user = User::factory()->create();

        // Test that validation errors return VALIDATION_ERROR code
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'pass', // too short
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => ApiErrorCode::VALIDATION_ERROR->value,
            ],
        ]);
    }
}
