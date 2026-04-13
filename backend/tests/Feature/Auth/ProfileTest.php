<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->customer()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'phone' => '0512345678',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Profile User',
                    'email' => 'profile@example.com',
                    'phone' => '0512345678',
                    'role' => 'customer',
                    'is_active' => true,
                ],
                'error' => null,
            ]);
    }

    public function test_profile_excludes_sensitive_fields(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->customer()->create([
            'name' => 'Old Name',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/user', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_user_can_update_phone(): void
    {
        $user = User::factory()->customer()->create([
            'phone' => '0512345678',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/user', [
            'phone' => '0587654321',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'phone' => '0587654321',
                ],
            ]);
    }

    public function test_invalid_phone_format_returns_422(): void
    {
        $user = User::factory()->customer()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/auth/user', [
            'phone' => '1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['error' => ['details' => ['phone']]]);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }

    public function test_update_profile_unauthenticated_returns_401(): void
    {
        $response = $this->putJson('/api/v1/auth/user', [
            'name' => 'Hacker',
        ]);

        $response->assertStatus(401);
    }
}
