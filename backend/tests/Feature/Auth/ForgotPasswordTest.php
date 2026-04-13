<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/forgot-password';

    public function test_existing_email_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->customer()->create([
            'email' => 'forgot@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'forgot@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_nonexistent_email_returns_same_response(): void
    {
        // Prevent email enumeration — same response regardless
        $response = $this->postJson($this->url, [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);
    }

    public function test_rate_limiting_after_3_attempts(): void
    {
        // Rate limiter is keyed by IP + email, so use the same email
        for ($i = 0; $i < 3; $i++) {
            $this->postJson($this->url, [
                'email' => 'rate@example.com',
            ]);
        }

        $response = $this->postJson($this->url, [
            'email' => 'rate@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_validation_errors_for_missing_email(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['email']]]);
    }
}
