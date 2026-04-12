<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    public function test_register_sets_role_explicitly(): void
    {
        $result = $this->authService->register([
            'name' => 'Test User',
            'email' => 'explicit@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'role' => 'contractor',
        ]);

        $user = User::where('email', 'explicit@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::CONTRACTOR, $user->role);
        $this->assertArrayHasKey('token', $result);
    }

    public function test_register_creates_token(): void
    {
        $result = $this->authService->register([
            'name' => 'Token User',
            'email' => 'token@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'role' => 'customer',
        ]);

        $this->assertNotEmpty($result['token']);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    public function test_login_verifies_password(): void
    {
        User::factory()->customer()->create([
            'email' => 'verify@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $result = $this->authService->login([
            'email' => 'verify@example.com',
            'password' => 'correctpassword',
        ]);

        $this->assertNotEmpty($result['token']);
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->customer()->create([
            'email' => 'reject@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $this->expectException(ApiException::class);

        $this->authService->login([
            'email' => 'reject@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    public function test_login_checks_is_active(): void
    {
        User::factory()->customer()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);

        try {
            $this->authService->login([
                'email' => 'inactive@example.com',
                'password' => 'password',
            ]);
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertEquals(ApiErrorCode::AUTH_UNAUTHORIZED, $e->getErrorCode());
        }
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = User::factory()->customer()->create();
        $user->createToken('api');
        $user->createToken('api-2');

        // Simulate having a current token
        $token = $user->createToken('current');
        $user->withAccessToken($token->accessToken);

        $this->assertCount(3, $user->tokens);

        $this->authService->logout($user);

        $user->refresh();
        // Only the current token should be deleted — 2 remain
        $this->assertCount(2, $user->tokens);
    }

    public function test_profile_update_changes_data(): void
    {
        $user = User::factory()->customer()->create([
            'name' => 'Old Name',
            'phone' => '0512345678',
        ]);

        $result = $this->authService->updateProfile($user, [
            'name' => 'New Name',
            'phone' => '0587654321',
        ]);

        $this->assertEquals('New Name', $result->resource->name);
    }
}
