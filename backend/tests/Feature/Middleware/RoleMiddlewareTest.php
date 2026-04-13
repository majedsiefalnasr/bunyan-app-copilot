<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test routes for middleware testing
        Route::middleware(['api', 'auth:sanctum', 'role:admin'])->get('/test/admin-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'role:contractor'])->get('/test/contractor-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'role:admin,contractor'])->get('/test/admin-or-contractor', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'role:supervising_architect'])->get('/test/architect-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'role:field_engineer'])->get('/test/engineer-only', fn () => response()->json(['ok' => true]));
        Route::middleware(['api', 'auth:sanctum', 'role:customer'])->get('/test/customer-only', fn () => response()->json(['ok' => true]));
    }

    public function test_admin_can_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/admin-only')
            ->assertOk();
    }

    public function test_customer_cannot_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/admin-only')
            ->assertForbidden();
    }

    public function test_contractor_can_access_contractor_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::CONTRACTOR, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/contractor-only')
            ->assertOk();
    }

    public function test_contractor_cannot_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::CONTRACTOR, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/admin-only')
            ->assertForbidden();
    }

    public function test_multiple_roles_or_logic(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $contractor = User::factory()->create(['role' => UserRole::CONTRACTOR, 'is_active' => true]);
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($admin)->getJson('/test/admin-or-contractor')->assertOk();
        $this->actingAs($contractor)->getJson('/test/admin-or-contractor')->assertOk();
        $this->actingAs($customer)->getJson('/test/admin-or-contractor')->assertForbidden();
    }

    public function test_inactive_user_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => false]);

        $this->actingAs($user)
            ->getJson('/test/admin-only')
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $this->getJson('/test/admin-only')
            ->assertUnauthorized();
    }

    public function test_supervising_architect_can_access_architect_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERVISING_ARCHITECT, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/architect-only')
            ->assertOk();
    }

    public function test_field_engineer_can_access_engineer_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::FIELD_ENGINEER, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/engineer-only')
            ->assertOk();
    }

    public function test_customer_can_access_customer_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/customer-only')
            ->assertOk();
    }

    public function test_field_engineer_cannot_access_admin_route(): void
    {
        $user = User::factory()->create(['role' => UserRole::FIELD_ENGINEER, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/admin-only')
            ->assertForbidden();
    }
}
