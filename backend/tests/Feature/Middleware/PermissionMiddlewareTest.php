<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register test route for permission middleware testing
        Route::middleware(['api', 'auth:sanctum', 'permission:projects.create'])->get('/test/perm-check', fn () => response()->json(['ok' => true]));
    }

    public function test_user_with_permission_can_access(): void
    {
        $user = User::factory()->create(['role' => UserRole::CONTRACTOR, 'is_active' => true]);
        $role = Role::create(['name' => 'contractor', 'display_name' => 'Contractor', 'display_name_ar' => 'المقاول']);
        $permission = Permission::create(['name' => 'projects.create', 'display_name' => 'Create Projects', 'group' => 'projects']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->getJson('/test/perm-check')
            ->assertOk();
    }

    public function test_user_without_permission_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);
        $role = Role::create(['name' => 'customer', 'display_name' => 'Customer', 'display_name_ar' => 'العميل']);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->getJson('/test/perm-check')
            ->assertForbidden();
    }

    public function test_admin_bypasses_permission_check(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);

        $this->actingAs($user)
            ->getJson('/test/perm-check')
            ->assertOk();
    }

    public function test_inactive_user_is_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => false]);

        $this->actingAs($user)
            ->getJson('/test/perm-check')
            ->assertForbidden();
    }
}
