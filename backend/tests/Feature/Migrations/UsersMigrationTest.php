<?php

declare(strict_types=1);

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function phone_column_exists_on_users_table(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'phone'));
    }

    #[Test]
    public function is_active_column_exists_with_default_value(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));

        $column = collect(Schema::getColumns('users'))->firstWhere('name', 'is_active');
        $this->assertNotNull($column);
        // SQLite may wrap the default value in single quotes; strip them before comparison.
        $default = trim((string) $column['default'], "'");
        $this->assertEquals('1', $default);
    }

    #[Test]
    public function deleted_at_column_exists_on_users_table(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'deleted_at'));
    }

    #[Test]
    public function is_active_index_exists_on_users_table(): void
    {
        $indexes = Schema::getIndexes('users');
        $indexNames = array_column($indexes, 'name');

        $hasIndex = collect($indexNames)->contains(fn ($name) => str_contains($name, 'is_active'));
        $this->assertTrue($hasIndex, 'Expected index on is_active column to exist.');
    }
}
