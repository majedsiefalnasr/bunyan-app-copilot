<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function permission_has_fillable_defined(): void
    {
        $permission = new Permission;

        $this->assertNotEmpty($permission->getFillable());
        $this->assertContains('name', $permission->getFillable());
        $this->assertContains('display_name', $permission->getFillable());
        $this->assertContains('group', $permission->getFillable());
    }

    #[Test]
    public function roles_method_returns_belongs_to_many(): void
    {
        $permission = new Permission;

        $this->assertInstanceOf(BelongsToMany::class, $permission->roles());
    }

    #[Test]
    public function scope_by_group_filters_permissions_correctly(): void
    {
        Permission::create(['name' => 'users.view', 'display_name' => 'View Users', 'group' => 'users']);
        Permission::create(['name' => 'users.create', 'display_name' => 'Create Users', 'group' => 'users']);
        Permission::create(['name' => 'projects.view', 'display_name' => 'View Projects', 'group' => 'projects']);

        $userPermissions = Permission::byGroup('users')->get();

        $this->assertCount(2, $userPermissions);
        $this->assertTrue($userPermissions->every(fn ($p) => $p->group === 'users'));
    }
}
