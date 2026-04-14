<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RoleService::class);
    }

    public function test_assign_role_updates_enum_and_pivot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $targetUser = User::factory()->create(['role' => UserRole::CUSTOMER]);
        $contractorRole = Role::create(['name' => 'contractor', 'display_name' => 'Contractor', 'display_name_ar' => 'المقاول']);

        $result = $this->service->assignRoleToUser($targetUser, UserRole::CONTRACTOR, $admin);

        $this->assertEquals(UserRole::CONTRACTOR, $result->role);
        $this->assertTrue($result->roles->contains('name', 'contractor'));
    }

    public function test_self_lockout_prevention(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->expectException(ValidationException::class);

        $this->service->assignRoleToUser($admin, UserRole::CUSTOMER, $admin);
    }

    public function test_admin_can_reassign_own_admin_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'display_name_ar' => 'الإدارة']);

        // Admin assigning admin to themselves should work
        $result = $this->service->assignRoleToUser($admin, UserRole::ADMIN, $admin);

        $this->assertEquals(UserRole::ADMIN, $result->role);
    }

    public function test_list_roles(): void
    {
        Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'display_name_ar' => 'الإدارة']);
        Role::create(['name' => 'customer', 'display_name' => 'Customer', 'display_name_ar' => 'العميل']);

        $roles = $this->service->listRoles();

        $this->assertCount(2, $roles);
    }

    public function test_get_role_with_permissions(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'display_name_ar' => 'الإدارة']);

        $result = $this->service->getRoleWithPermissions($role->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->relationLoaded('permissions'));
    }

    public function test_get_user_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::CONTRACTOR]);

        $this->assertEquals(UserRole::CONTRACTOR, $this->service->getUserRole($user));
    }
}
