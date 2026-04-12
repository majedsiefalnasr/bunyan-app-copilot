<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/reset-password';

    public function test_user_can_reset_password(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'reset@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson($this->url, [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'error' => null,
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_all_tokens_revoked_after_reset(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'tokens@example.com',
        ]);

        // Create some tokens
        $user->createToken('api-1');
        $user->createToken('api-2');
        $this->assertCount(2, $user->tokens);

        $token = Password::createToken($user);

        $this->postJson($this->url, [
            'email' => 'tokens@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $user->refresh();
        $this->assertCount(0, $user->tokens);
    }

    public function test_invalid_token_returns_422(): void
    {
        User::factory()->customer()->create([
            'email' => 'invalid@example.com',
        ]);

        $response = $this->postJson($this->url, [
            'email' => 'invalid@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_validation_errors(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['email', 'token', 'password']]]);
    }

    public function test_password_confirmation_mismatch(): void
    {
        $user = User::factory()->customer()->create([
            'email' => 'mismatch@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson($this->url, [
            'email' => 'mismatch@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['password']]]);
    }
}
