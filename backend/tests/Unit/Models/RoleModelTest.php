<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleModelTest extends TestCase
{
    #[Test]
    public function role_has_fillable_defined(): void
    {
        $role = new Role;

        $this->assertNotEmpty($role->getFillable());
        $this->assertContains('name', $role->getFillable());
        $this->assertContains('display_name', $role->getFillable());
        $this->assertContains('display_name_ar', $role->getFillable());
    }

    #[Test]
    public function users_method_exists_and_returns_belongs_to_many(): void
    {
        $role = new Role;

        $this->assertInstanceOf(BelongsToMany::class, $role->users());
    }

    #[Test]
    public function permissions_method_exists_and_returns_belongs_to_many(): void
    {
        $role = new Role;

        $this->assertInstanceOf(BelongsToMany::class, $role->permissions());
    }
}
