<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PermissionService::class);
    }

    public function test_sync_permissions_to_role(): void
    {
        $role = Role::create(['name' => 'contractor', 'display_name' => 'Contractor', 'display_name_ar' => 'المقاول']);
        $p1 = Permission::create(['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects']);
        $p2 = Permission::create(['name' => 'projects.create', 'display_name' => 'Create Projects', 'group' => 'projects']);

        $result = $this->service->syncPermissionsToRole($role, [$p1->id, $p2->id]);

        $this->assertCount(2, $result->permissions);
    }

    public function test_list_permissions_grouped(): void
    {
        Permission::create(['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects']);
        Permission::create(['name' => 'reports.view', 'display_name' => 'View Reports', 'group' => 'reports']);

        $grouped = $this->service->listPermissionsGrouped();

        $this->assertArrayHasKey('projects', $grouped);
        $this->assertArrayHasKey('reports', $grouped);
    }

    public function test_user_has_permission(): void
    {
        $user = User::factory()->create(['role' => UserRole::CONTRACTOR]);
        $role = Role::create(['name' => 'contractor', 'display_name' => 'Contractor', 'display_name_ar' => 'المقاول']);
        $permission = Permission::create(['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($this->service->userHasPermission($user, 'projects.view'));
        $this->assertFalse($this->service->userHasPermission($user, 'projects.delete'));
    }
}
