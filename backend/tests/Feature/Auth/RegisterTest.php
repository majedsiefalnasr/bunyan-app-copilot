<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth/register';

    /**
     * @return array<string, array{string}>
     */
    public static function allowedRolesProvider(): array
    {
        return [
            'customer' => ['customer'],
            'contractor' => ['contractor'],
            'supervising_architect' => ['supervising_architect'],
            'field_engineer' => ['field_engineer'],
        ];
    }

    #[DataProvider('allowedRolesProvider')]
    public function test_user_can_register_with_allowed_role(string $role): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => "test-{$role}@example.com",
            'phone' => '0512345678',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => $role,
        ]);

        $response->assertStatus(201)
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
                    'user' => ['role' => $role],
                    'token_type' => 'Bearer',
                ],
                'error' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => "test-{$role}@example.com",
            'role' => $role,
        ]);

        Event::assertDispatched(Registered::class);
    }

    public function test_password_is_hashed(): void
    {
        $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => 'hash@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'hash@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password1234', $user->password));
        $this->assertNotEquals('password1234', $user->password);
    }

    public function test_admin_role_is_blocked(): void
    {
        $response = $this->postJson($this->url, [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_duplicate_email_returns_conflict(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson($this->url, [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'customer',
        ]);

        $response->assertStatus(422);
    }

    public function test_validation_errors_for_missing_fields(): void
    {
        $response = $this->postJson($this->url, []);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['name', 'email', 'phone', 'password', 'role']]]);
    }

    public function test_invalid_phone_format(): void
    {
        $response = $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => 'phone@example.com',
            'phone' => '1234567890',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['phone']]]);
    }

    public function test_valid_saudi_phone_formats(): void
    {
        // +9665 format
        $response = $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => 'saudi1@example.com',
            'phone' => '+966512345678',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => 'customer',
        ]);

        $response->assertStatus(201);
    }

    public function test_password_confirmation_mismatch(): void
    {
        $response = $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => 'confirm@example.com',
            'phone' => '0512345678',
            'password' => 'password1234',
            'password_confirmation' => 'different1234',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['password']]]);
    }

    public function test_password_minimum_length(): void
    {
        $response = $this->postJson($this->url, [
            'name' => 'Test User',
            'email' => 'short@example.com',
            'phone' => '0512345678',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['password']]]);
    }
}
