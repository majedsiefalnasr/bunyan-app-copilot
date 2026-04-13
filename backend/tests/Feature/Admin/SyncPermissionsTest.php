<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
    }

    public function test_admin_can_sync_valid_permissions(): void
    {
        $role = Role::where('name', 'contractor')->first();
        $permissionIds = Permission::limit(3)->pluck('id')->toArray();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => $permissionIds])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(3, $role->fresh()->permissions);
    }

    public function test_sync_with_invalid_permission_ids(): void
    {
        $role = Role::where('name', 'contractor')->first();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => [9999]])
            ->assertUnprocessable();
    }

    public function test_sync_with_empty_array_clears_permissions(): void
    {
        $role = Role::where('name', 'contractor')->first();

        $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => []])
            ->assertUnprocessable();
    }

    public function test_non_admin_cannot_sync_permissions(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);
        $role = Role::where('name', 'contractor')->first();
        $permissionIds = Permission::limit(2)->pluck('id')->toArray();

        $this->actingAs($customer)
            ->putJson("/api/v1/admin/roles/{$role->id}/permissions", ['permission_ids' => $permissionIds])
            ->assertForbidden();
    }
}
