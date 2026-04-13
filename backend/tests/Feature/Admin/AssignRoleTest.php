<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
    }

    public function test_admin_can_assign_role(): void
    {
        $targetUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$targetUser->id}/role", ['role' => 'contractor'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'contractor');

        $this->assertEquals(UserRole::CONTRACTOR, $targetUser->fresh()->role);
    }

    public function test_admin_self_lockout_prevention(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$this->admin->id}/role", ['role' => 'customer'])
            ->assertUnprocessable();
    }

    public function test_assign_invalid_role(): void
    {
        $targetUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/users/{$targetUser->id}/role", ['role' => 'invalid_role'])
            ->assertUnprocessable();
    }

    public function test_non_admin_cannot_assign_role(): void
    {
        $customer = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);
        $targetUser = User::factory()->create(['role' => UserRole::CUSTOMER, 'is_active' => true]);

        $this->actingAs($customer)
            ->postJson("/api/v1/admin/users/{$targetUser->id}/role", ['role' => 'contractor'])
            ->assertForbidden();
    }

    public function test_assign_role_to_nonexistent_user(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/users/9999/role', ['role' => 'contractor'])
            ->assertNotFound();
    }
}
