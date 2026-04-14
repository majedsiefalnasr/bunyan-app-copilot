<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_already_verified_is_idempotent(): void
    {
        $user = User::factory()->customer()->create([
            'email_verified_at' => now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200);
    }

    public function test_invalid_signature_returns_403(): void
    {
        $user = User::factory()->customer()->unverified()->create();

        $response = $this->getJson("/api/v1/auth/email/verify/{$user->id}/invalid-hash");

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_resend_verification(): void
    {
        $user = User::factory()->customer()->unverified()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);
    }

    public function test_resend_rate_limiting(): void
    {
        $user = User::factory()->customer()->unverified()->create();
        Sanctum::actingAs($user);

        // Rate limiter is configured for 5 per 15 minutes (auth-email-resend)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/email/resend');
            // First 5 should succeed (200)
            $this->assertEquals(200, $response->status());
        }

        // 6th attempt should be rate limited (429)
        $response = $this->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(429);
    }

    public function test_unauthenticated_cannot_resend(): void
    {
        $response = $this->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(401);
    }
}
