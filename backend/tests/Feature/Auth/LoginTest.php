<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/login';

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role', 'is_active', 'email_verified_at', 'created_at'],
                    'token',
                    'token_type',
                ],
                'error',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'login@example.com',
                        'role' => 'customer',
                    ],
                    'token_type' => 'Bearer',
                ],
                'error' => null,
            ]);
    }

    public function test_login_includes_email_verified_flag(): void
    {
        User::factory()->customer()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'verified@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.user.email_verified_at'));

        // Unverified user
        User::factory()->customer()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'unverified@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.user.email_verified_at'));
    }

    public function test_invalid_credentials_returns_401(): void
    {
        User::factory()->customer()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID_CREDENTIALS',
                ],
            ]);
    }

    public function test_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson($this->url, [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID_CREDENTIALS',
                ],
            ]);
    }

    public function test_inactive_user_returns_403(): void
    {
        User::factory()->customer()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_UNAUTHORIZED',
                ],
            ]);
    }

    public function test_rate_limiting_after_5_attempts(): void
    {
        // Use unique email to avoid lockout triggering
        // (lockout is per-email, throttle is per-IP)
        // Both are set to 5 per 15 minutes
        User::factory()->customer()->create([
            'email' => 'rate-'.now()->timestamp.'@example.com',
        ]);

        $email = 'rate-'.now()->timestamp.'@example.com';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson($this->url, [
                'email' => $email,
                'password' => 'wrongpassword',
            ]);
            // First 5 attempts should return 401 (invalid credentials)
            $this->assertEquals(401, $response->status());
        }

        // 6th attempt should trigger rate limiting (429)
        $response = $this->postJson($this->url, [
            'email' => $email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_validation_errors_for_missing_fields(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['email', 'password']]]);
    }
}
