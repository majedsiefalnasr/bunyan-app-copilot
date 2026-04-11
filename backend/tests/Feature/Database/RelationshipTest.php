<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    #[Test]
    public function user_roles_returns_attached_roles_via_pivot(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'customer')->first();
        $user->roles()->attach($role->id);

        $this->assertTrue($user->roles->contains($role));
    }

    #[Test]
    public function role_permissions_contains_attached_permission(): void
    {
        $role = Role::where('name', 'admin')->first();
        $permission = Permission::where('name', 'users.view')->first();
        $role->permissions()->attach($permission->id);

        $this->assertTrue($role->fresh()->permissions->contains($permission));
    }

    #[Test]
    public function three_table_traversal_resolves_user_role_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();
        $permission = Permission::where('name', 'users.manage')->firstOrCreate([
            'name' => 'users.manage',
            'display_name' => 'Manage Users',
            'group' => 'users',
        ]);

        $user->roles()->attach($role->id);
        $role->permissions()->attach($permission->id);

        $userPermissions = $user->roles()->first()->permissions;
        $this->assertTrue($userPermissions->contains($permission));
    }

    #[Test]
    public function detaching_role_removes_it_from_user_roles(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'customer')->first();
        $user->roles()->attach($role->id);
        $user->roles()->detach($role->id);

        $this->assertFalse($user->fresh()->roles->contains($role));
    }

    #[Test]
    public function cascade_delete_role_removes_role_user_row(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'contractor')->first();
        $user->roles()->attach($role->id);

        $this->assertDatabaseHas('role_user', ['role_id' => $role->id, 'user_id' => $user->id]);

        $role->delete();

        $this->assertDatabaseMissing('role_user', ['role_id' => $role->id, 'user_id' => $user->id]);
    }
}
