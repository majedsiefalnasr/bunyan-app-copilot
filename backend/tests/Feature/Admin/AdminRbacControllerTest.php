<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRbacControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
    }

    public function test_admin_can_list_roles(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/roles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_show_role(): void
    {
        $role = Role::where('name', 'admin')->first();

        $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/roles/{$role->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'admin');
    }

    public function test_admin_can_list_permissions(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/permissions')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_users(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_customer_cannot_access_admin_roles(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($customer)
            ->getJson('/api/v1/admin/roles')
            ->assertForbidden();
    }

    public function test_contractor_cannot_access_admin_roles(): void
    {
        $contractor = User::factory()->create(['role' => UserRole::CONTRACTOR, 'is_active' => true]);

        $this->actingAs($contractor)
            ->getJson('/api/v1/admin/roles')
            ->assertForbidden();
    }

    public function test_architect_cannot_access_admin_roles(): void
    {
        $architect = User::factory()->create(['role' => UserRole::SUPERVISING_ARCHITECT, 'is_active' => true]);

        $this->actingAs($architect)
            ->getJson('/api/v1/admin/roles')
            ->assertForbidden();
    }

    public function test_field_engineer_cannot_access_admin_roles(): void
    {
        $engineer = User::factory()->create(['role' => UserRole::FIELD_ENGINEER, 'is_active' => true]);

        $this->actingAs($engineer)
            ->getJson('/api/v1/admin/roles')
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_admin_roles(): void
    {
        $this->getJson('/api/v1/admin/roles')
            ->assertUnauthorized();
    }

    public function test_show_role_not_found(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/roles/9999')
            ->assertNotFound();
    }
}
