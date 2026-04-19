<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_categories_table_with_correct_schema(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('categories'));

        $columns = DB::getSchemaBuilder()->getColumnListing('categories');

        $requiredColumns = [
            'id',
            'parent_id',
            'name_ar',
            'name_en',
            'slug',
            'icon',
            'sort_order',
            'is_active',
            'version',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $columns, "Column {$column} not found in categories table");
        }
    }

    public function test_migration_creates_correct_column_types(): void
    {
        $columns = collect(Schema::getColumns('categories'))->keyBy('name');

        $idColumn = $columns->get('id');
        $slugColumn = $columns->get('slug');
        $isActiveColumn = $columns->get('is_active');

        $this->assertNotNull($idColumn);
        $this->assertNotNull($slugColumn);
        $this->assertNotNull($isActiveColumn);

        $this->assertContains(strtolower($idColumn['type_name'] ?? ''), ['bigint', 'integer']);
        $this->assertTrue((bool) ($idColumn['auto_increment'] ?? false));
        $this->assertContains(strtolower($slugColumn['type_name'] ?? ''), ['varchar', 'text']);
        $this->assertContains(strtolower($isActiveColumn['type_name'] ?? ''), ['boolean', 'tinyint', 'integer']);
    }

    public function test_migration_creates_indexes(): void
    {
        $indexes = Schema::getIndexes('categories');

        $this->assertNotEmpty($indexes);
        $this->assertTrue(
            collect($indexes)->contains(fn (array $index) => $index['columns'] === ['parent_id']),
            'Expected index on parent_id to exist.',
        );
        $this->assertTrue(
            collect($indexes)->contains(
                fn (array $index) => $index['columns'] === ['parent_id', 'sort_order', 'is_active']
            ),
            'Expected composite index on parent_id, sort_order, is_active to exist.',
        );
    }

    public function test_seeder_runs_without_errors(): void
    {
        Category::truncate();

        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        $count = Category::count();
        $this->assertGreaterThan(10, $count, 'Seeder should create at least 10 categories');
    }

    public function test_seeder_creates_valid_hierarchy(): void
    {
        Category::truncate();

        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        $roots = Category::whereNull('parent_id')->count();
        $this->assertGreaterThan(0, $roots, 'Should have at least one root category');

        $orphans = Category::query()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->count();

        $this->assertEquals(0, $orphans, 'Should not have orphaned categories');
    }

    public function test_soft_delete_scope_works(): void
    {
        Category::truncate();

        $category = Category::factory()->create(['name_ar' => 'Test', 'name_en' => 'Test']);
        $category->delete();

        $found = Category::where('id', $category->id)->first();
        $this->assertNull($found);

        $found = Category::withTrashed()->where('id', $category->id)->first();
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }

    public function test_foreign_key_constraint_on_parent_id(): void
    {
        Category::factory()->create();

        $foreignKeys = Schema::getForeignKeys('categories');

        $parentIdForeignKey = collect($foreignKeys)->first(
            fn (array $foreignKey) => $foreignKey['columns'] === ['parent_id']
        );

        $this->assertNotNull($parentIdForeignKey, 'Parent ID should have foreign key');
        $this->assertSame('categories', $parentIdForeignKey['foreign_table']);
        $this->assertSame(['id'], $parentIdForeignKey['foreign_columns']);
        $this->assertSame('set null', $parentIdForeignKey['on_delete']);
    }

    public function test_migration_is_reversible(): void
    {
        $beforeMigrations = DB::table('migrations')->count();

        $this->assertGreaterThan(0, $beforeMigrations);
    }

    public function test_data_integrity_after_seeding(): void
    {
        Category::truncate();

        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        $nullNames = Category::whereNull('name_ar')
            ->orWhereNull('name_en')
            ->orWhereNull('slug')
            ->count();

        $this->assertEquals(0, $nullNames, 'All categories should have name_ar, name_en, and slug');

        $invalid = Category::whereBetween('sort_order', [-1, -999])->count();
        $this->assertEquals(0, $invalid, 'Sort order should be non-negative');
    }

    public function test_index_performance_on_1000_categories(): void
    {
        Category::truncate();

        Category::factory(1000)->create();

        $startTime = microtime(true);

        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->limit(100)
            ->get();

        $duration = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $categories->count());
        $this->assertLessThan(1.0, $duration, 'Query should complete in under 1 second');
    }
}
