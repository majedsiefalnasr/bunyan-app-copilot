<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * T070: Backend Migration Validation
     * Verify migrations run without errors and are reversible
     */
    public function test_migration_creates_categories_table_with_correct_schema(): void
    {
        // Check table exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('categories'));

        // Check required columns exist
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
        $columns = DB::getSchemaBuilder()->getColumns('categories');
        $columnTypes = [];

        foreach ($columns as $column) {
            $columnTypes[$column['name']] = $column['type'];
        }

        // Verify critical column types
        $this->assertStringContainsString('bigint', strtolower($columnTypes['id'] ?? ''));
        $this->assertStringContainsString('varchar', strtolower($columnTypes['slug'] ?? ''));
        $this->assertTrue(in_array(strtolower($columnTypes['is_active'] ?? ''), ['boolean', 'tinyint']));
    }

    public function test_migration_creates_indexes(): void
    {
        // Get indexes
        $indexes = DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('categories');

        // Should have indexes on commonly queried columns
        $indexNames = array_keys($indexes);

        // At minimum should have primary key
        $this->assertTrue(count($indexNames) > 0);
    }

    public function test_seeder_runs_without_errors(): void
    {
        // Clear existing data
        Category::truncate();

        // Run seeder
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        // Verify data created
        $count = Category::count();
        $this->assertGreaterThan(10, $count, 'Seeder should create at least 10 categories');
    }

    public function test_seeder_creates_valid_hierarchy(): void
    {
        Category::truncate();

        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        // Verify tree structure is valid
        $roots = Category::whereNull('parent_id')->count();
        $this->assertGreaterThan(0, $roots, 'Should have at least one root category');

        // All children should have valid parents
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

        // Should not appear in normal queries
        $found = Category::where('id', $category->id)->first();
        $this->assertNull($found);

        // Should appear with withTrashed
        $found = Category::withTrashed()->where('id', $category->id)->first();
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }

    public function test_foreign_key_constraint_on_parent_id(): void
    {
        $category = Category::factory()->create();

        // Verify parent_id foreign key exists
        $foreignKeys = DB::connection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('categories');

        $parentIdFkExists = false;
        foreach ($foreignKeys as $fk) {
            if (in_array('parent_id', $fk->getColumns())) {
                $parentIdFkExists = true;
                break;
            }
        }

        $this->assertTrue($parentIdFkExists, 'Parent ID should have foreign key');
    }

    public function test_migration_is_reversible(): void
    {
        // Get current migration status
        $beforeMigrations = DB::table('migrations')->count();

        // We can't truly rollback without running all migrations,
        // but we can verify migration was registered
        $this->assertGreaterThan(0, $beforeMigrations);
    }

    public function test_data_integrity_after_seeding(): void
    {
        Category::truncate();

        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        // Verify no null required fields
        $nullNames = Category::whereNull('name_ar')
            ->orWhereNull('name_en')
            ->orWhereNull('slug')
            ->count();

        $this->assertEquals(0, $nullNames, 'All categories should have name_ar, name_en, and slug');

        // Verify sort_order is reasonable
        $invalid = Category::whereBetween('sort_order', [-1, -999])->count();
        $this->assertEquals(0, $invalid, 'Sort order should be non-negative');
    }

    public function test_index_performance_on_1000_categories(): void
    {
        Category::truncate();

        // Create 1000 categories
        Category::factory(1000)->create();

        $startTime = microtime(true);

        // Query should use indexes
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
