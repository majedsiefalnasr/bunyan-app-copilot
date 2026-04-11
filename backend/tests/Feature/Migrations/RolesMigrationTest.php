<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolesMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function roles_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasColumns('roles', ['id', 'name', 'display_name', 'display_name_ar', 'description']));
    }

    #[Test]
    public function permissions_table_exists_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasColumns('permissions', ['id', 'name', 'display_name', 'group', 'description']));
    }

    #[Test]
    public function role_user_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('role_user'));
    }

    #[Test]
    public function permission_role_pivot_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('permission_role'));
    }

    #[Test]
    public function role_user_has_no_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('role_user', 'id'));
    }

    #[Test]
    public function permission_role_has_no_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('permission_role', 'id'));
    }

    #[Test]
    public function permissions_group_index_exists(): void
    {
        $indexes = Schema::getIndexes('permissions');
        $indexNames = array_column($indexes, 'name');

        $hasIndex = collect($indexNames)->contains(fn ($name) => str_contains($name, 'group'));
        $this->assertTrue($hasIndex, 'Expected index on permissions.group to exist.');
    }

    #[Test]
    public function role_user_has_user_id_reverse_lookup_index(): void
    {
        $indexes = Schema::getIndexes('role_user');
        $indexNames = array_column($indexes, 'name');

        $hasIndex = collect($indexNames)->contains(fn ($name) => str_contains($name, 'user_id'));
        $this->assertTrue($hasIndex, 'Expected reverse-lookup index on role_user.user_id to exist.');
    }

    #[Test]
    public function permission_role_has_role_id_reverse_lookup_index(): void
    {
        $indexes = Schema::getIndexes('permission_role');
        $indexNames = array_column($indexes, 'name');

        $hasIndex = collect($indexNames)->contains(fn ($name) => str_contains($name, 'role_id'));
        $this->assertTrue($hasIndex, 'Expected reverse-lookup index on permission_role.role_id to exist.');
    }
}
