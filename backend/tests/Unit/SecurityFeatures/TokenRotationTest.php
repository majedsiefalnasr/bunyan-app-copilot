<?php

declare(strict_types=1);

namespace Tests\Unit\SecurityFeatures;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenRotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_rotation_creates_new_token(): void
    {
        // Create user with initial token
        $user = User::factory()->create();
        $initialToken = $user->createToken('api')->plainTextToken;

        $this->actingAs($user, 'sanctum');

        // Call refresh endpoint
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'token_type',
            ],
        ]);

        $newToken = $response->json('data.token');

        // New token should be different from initial token
        $this->assertNotEquals($initialToken, $newToken);
        $this->assertTrue($response->json('success'));
    }

    public function test_token_rotation_revokes_old_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('api')->plainTextToken;

        $this->actingAs($user, 'sanctum');

        // Get initial token count
        $initialTokenCount = $user->tokens()->count();

        // Refresh token
        $response = $this->postJson('/api/v1/auth/refresh');
        $response->assertStatus(200);

        // Token count should remain the same (old one deleted, new one created)
        $this->assertEquals($initialTokenCount, $user->tokens()->count());
    }

    public function test_old_token_invalid_after_rotation(): void
    {
        $user = User::factory()->create();
        $oldTokenModel = $user->createToken('api');
        $oldToken = $oldTokenModel->plainTextToken;

        $this->actingAs($user, 'sanctum');

        // Refresh token (rotation)
        $this->postJson('/api/v1/auth/refresh');

        // Try to use old token
        $response = $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/auth/user');

        // Old token should be invalid (401 Unauthorized)
        $this->assertEquals(401, $response->status());
    }

    public function test_new_token_valid_after_rotation(): void
    {
        $user = User::factory()->create();
        $user->createToken('api')->plainTextToken;

        $this->actingAs($user, 'sanctum');

        // Refresh token
        $response = $this->postJson('/api/v1/auth/refresh');
        $newToken = $response->json('data.token');

        // Use new token to verify it works
        $response = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'email',
                'name',
            ],
        ]);
    }

    public function test_token_rotation_requires_authentication(): void
    {
        // Try without authentication
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }
}
