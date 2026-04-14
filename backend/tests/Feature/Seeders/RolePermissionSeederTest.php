<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_seeder_creates_32_permissions(): void
    {
        $this->assertEquals(32, Permission::count());
    }

    public function test_seeder_creates_5_roles(): void
    {
        $this->assertEquals(5, Role::count());
    }

    public function test_admin_has_all_32_permissions(): void
    {
        $admin = Role::where('name', 'admin')->first();
        $this->assertEquals(32, $admin->permissions()->count());
    }

    public function test_customer_has_correct_permissions(): void
    {
        $customer = Role::where('name', 'customer')->first();
        $this->assertEquals(9, $customer->permissions()->count());
    }

    public function test_contractor_has_correct_permissions(): void
    {
        $contractor = Role::where('name', 'contractor')->first();
        $this->assertEquals(12, $contractor->permissions()->count());
    }

    public function test_supervising_architect_has_correct_permissions(): void
    {
        $architect = Role::where('name', 'supervising_architect')->first();
        $this->assertEquals(11, $architect->permissions()->count());
    }

    public function test_field_engineer_has_correct_permissions(): void
    {
        $engineer = Role::where('name', 'field_engineer')->first();
        $this->assertEquals(7, $engineer->permissions()->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        // Run seeder a second time
        $this->seed(RolePermissionSeeder::class);

        // Counts should not change
        $this->assertEquals(32, Permission::count());
        $this->assertEquals(5, Role::count());

        $admin = Role::where('name', 'admin')->first();
        $this->assertEquals(32, $admin->permissions()->count());
    }

    public function test_pivot_integrity(): void
    {
        // Verify every role has at least one permission
        $roles = Role::withCount('permissions')->get();

        foreach ($roles as $role) {
            $this->assertGreaterThan(0, $role->permissions_count, "Role {$role->name} has no permissions");
        }
    }
}
