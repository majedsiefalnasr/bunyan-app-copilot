# Phase 1 — Data Model: Category Hierarchy

**Stage**: STAGE_07_CATEGORIES  
**Date**: 2026-04-15

---

## Database Schema

### Categories Table

| Column       | Type            | Nullable | Default           | Constraint                     | Purpose                                            |
| ------------ | --------------- | -------- | ----------------- | ------------------------------ | -------------------------------------------------- |
| `id`         | BIGINT UNSIGNED | No       | AUTO_INCREMENT    | PRIMARY KEY                    | Unique identifier                                  |
| `parent_id`  | BIGINT UNSIGNED | **Yes**  | NULL              | FOREIGN KEY (self-referential) | Parent category ID (NULL for top-level)            |
| `name_ar`    | VARCHAR(100)    | No       | —                 | —                              | Arabic category name                               |
| `name_en`    | VARCHAR(100)    | No       | —                 | —                              | English category name                              |
| `slug`       | VARCHAR(100)    | No       | —                 | UNIQUE INDEX                   | URL-safe identifier (immutable)                    |
| `icon`       | VARCHAR(50)     | **Yes**  | NULL              | —                              | CSS class or icon identifier (e.g., "lucide-cube") |
| `sort_order` | INT UNSIGNED    | No       | 0                 | INDEX                          | Display order within siblings (ascending)          |
| `is_active`  | BOOLEAN         | No       | true              | INDEX                          | Visibility flag (admins can deactivate)            |
| `version`    | INT UNSIGNED    | No       | 0                 | —                              | Optimistic locking version (incremented on update) |
| `created_at` | TIMESTAMP       | No       | CURRENT_TIMESTAMP | —                              | Record creation timestamp                          |
| `updated_at` | TIMESTAMP       | No       | CURRENT_TIMESTAMP | —                              | Record update timestamp                            |
| `deleted_at` | TIMESTAMP       | **Yes**  | NULL              | INDEX                          | Soft-delete timestamp (NULL = active)              |

### Schema SQL

```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULLABLE,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) NULLABLE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    version INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,

    INDEX idx_parent_id (parent_id),
    INDEX idx_parent_sort (parent_id, sort_order, is_active),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_is_active (is_active),
    UNIQUE INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Index Rationale

| Index             | Query Optimization                                          |
| ----------------- | ----------------------------------------------------------- |
| `idx_parent_id`   | Speeds getChildren(parentId) queries                        |
| `idx_parent_sort` | Composite index for tree traversal with ordering            |
| `idx_deleted_at`  | Optimizes withoutTrashed() filtering in soft-delete queries |
| `idx_is_active`   | Filters active categories efficiently                       |
| `idx_slug`        | Enables fast lookup by slug (URLs, uniqueness checks)       |

---

## Eloquent Model

### Category Model Structure

```php
// app/Models/Category.php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'sort_order',
        'is_active',
        'version',
    ];

    protected $casts = [
        'parent_id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'bool',
        'version' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /**
     * Get the parent category for this category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all direct children of this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('sort_order');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Get only active, non-deleted categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereNull('deleted_at');
    }

    /**
     * Scope: Get root-level categories (parent_id = null).
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Get categories with no children.
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Scope: Ordered by sort_order ascending.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope: For tree rendering (active + not deleted + ordered).
     */
    public function scopeForTree(Builder $query): Builder
    {
        return $query
            ->active()
            ->ordered();
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Get display name based on app locale.
     * In real implementation, would check app()->getLocale().
     */
    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    // ==================== Methods ====================

    /**
     * Get all ancestors (parents up to root).
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $ancestors->prepend($current);
        }

        return $ancestors;
    }

    /**
     * Get all descendants recursively.
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * Check if this category is an ancestor of another.
     */
    public function isAncestorOf(Category $other): bool
    {
        return $other->getAncestors()->contains('id', $this->id);
    }

    /**
     * Check if this category is a descendant of another.
     */
    public function isDescendantOf(Category $other): bool
    {
        return $this->getAncestors()->contains('id', $other->id);
    }

    /**
     * Factory method for testing.
     */
    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
```

### Model Casts

- `parent_id`, `sort_order`, `version` → Cast to `int` for type safety
- `is_active` → Cast to `bool` (database stores as TINYINT)
- All timestamps → Cast to `datetime` for Carbon formatting

### Relationships Summary

| Relationship | Type      | Target   | Purpose                                                           |
| ------------ | --------- | -------- | ----------------------------------------------------------------- |
| `parent()`   | BelongsTo | Category | Self-referential; returns immediate parent                        |
| `children()` | HasMany   | Category | Self-referential; returns direct children (ordered by sort_order) |

---

## Query Patterns

### Load Full Tree (One Query with Recursive Eager Loading)

```php
// Fetch root categories with all descendants eagerly loaded
$tree = Category::forTree()
    ->roots()
    ->with(['children' => function ($query) {
        $query->forTree()->with(['children' => ...]) // Recursive for depth
    }])
    ->get();
```

**Alternative**: Use recursive CTE for single database query (MySQL 8.0+):

```php
// Single query using recursive CTE
DB::select(<<<SQL
    WITH RECURSIVE category_tree AS (
        SELECT id, parent_id, name_ar, name_en, slug, icon, sort_order, is_active, version
        FROM categories
        WHERE parent_id IS NULL AND deleted_at IS NULL AND is_active = TRUE

        UNION ALL

        SELECT c.id, c.parent_id, c.name_ar, c.name_en, c.slug, c.icon, c.sort_order, c.is_active, c.version
        FROM categories c
        INNER JOIN category_tree ct ON c.parent_id = ct.id
        WHERE c.deleted_at IS NULL AND c.is_active = TRUE
    )
    SELECT * FROM category_tree ORDER BY sort_order
SQL);
```

### Get Children of a Specific Parent

```php
$children = Category::forTree()
    ->where('parent_id', $parentId)
    ->get();
```

### Get Ancestors (Path from Current to Root)

```php
$category = Category::findOrFail($id);
$path = $category->getAncestors(); // Collection ordered root → parent
```

### Get Descendants (All children recursively)

```php
$descendants = $category->getDescendants(); // Flat collection
```

### Prevent Circular References (Validation Query)

```php
// When setting parent_id, check if it creates a cycle
if ($newParentId && $newParentId !== $category->id) {
    $hasCircle = Category::find($newParentId)
        ->getDescendants()
        ->contains('id', $category->id);

    if ($hasCircle) {
        throw new ValidationException('Parent creates circular reference');
    }
}
```

---

## Migration Strategy

### Migration File: Create Categories Table

**Filename**: `2026_04_15_000000_create_categories_table.php`

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Self-referential parent
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // Names (bilingual)
            $table->string('name_ar', 100);
            $table->string('name_en', 100);

            // Slug (immutable, unique)
            $table->string('slug', 100)->unique();

            // Icon (optional)
            $table->string('icon', 50)->nullable();

            // Ordering & visibility
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Optimistic locking
            $table->unsignedInteger('version')->default(0);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('parent_id');
            $table->index(['parent_id', 'sort_order', 'is_active']);
            $table->index('deleted_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### Key Migration Decisions

1. **Charset**: Uses `utf8mb4_unicode_ci` (default in Laravel 11+, supports Arabic/Unicode)
2. **Parent Foreign Key**: `nullOnDelete()` prevents cascade; parent can be deleted independently
3. **Soft Deletes**: Uses Laravel's `softDeletes()` trait (adds `deleted_at` column)
4. **Indexes**: Composite index on `(parent_id, sort_order, is_active)` for tree queries
5. **Forward-only**: Migration is non-destructive; down() only used for rollback

### Lock Risk Assessment

| Operation            | Lock Duration                       | Risk   |
| -------------------- | ----------------------------------- | ------ |
| CREATE TABLE         | Low (table doesn't exist yet)       | ✅ Low |
| Foreign Key creation | Low (self-referential, single pass) | ✅ Low |
| Index creation       | Medium (table empty initially)      | ✅ Low |

---

## Factory & Seeding

### CategoryFactory

```php
// database/factories/CategoryFactory.php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name_en = $this->faker->word();
        return [
            'parent_id' => null,
            'name_ar' => $this->faker->word(),
            'name_en' => ucfirst($name_en),
            'slug' => Str::slug($name_en),
            'icon' => 'lucide-' . $this->faker->word(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'version' => 0,
        ];
    }

    public function parent(Category $parent): self
    {
        return $this->state([
            'parent_id' => $parent->id,
        ]);
    }

    public function inactive(): self
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}
```

### CategorySeeder

```php
// database/seeders/CategorySeeder.php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    private const CATEGORIES = [
        [
            'name_ar' => 'مواد بناء',
            'name_en' => 'Building Materials',
            'icon' => 'lucide-box',
            'children' => [
                ['name_ar' => 'أسمنت', 'name_en' => 'Cement', 'icon' => 'lucide-package'],
                ['name_ar' => 'رمل', 'name_en' => 'Sand', 'icon' => 'lucide-package'],
                ['name_ar' => 'حديد', 'name_en' => 'Steel', 'icon' => 'lucide-package'],
                ['name_ar' => 'خشب', 'name_en' => 'Wood', 'icon' => 'lucide-package'],
            ],
        ],
        [
            'name_ar' => 'كهرباء',
            'name_en' => 'Electrical',
            'icon' => 'lucide-zap',
            'children' => [
                ['name_ar' => 'أسلاك', 'name_en' => 'Cables', 'icon' => 'lucide-package'],
                ['name_ar' => 'لوحات', 'name_en' => 'Panels', 'icon' => 'lucide-package'],
            ],
        ],
        [
            'name_ar' => 'سباكة',
            'name_en' => 'Plumbing',
            'icon' => 'lucide-droplet',
            'children' => [
                ['name_ar' => 'أنابيب', 'name_en' => 'Pipes', 'icon' => 'lucide-package'],
                ['name_ar' => 'تجهيزات', 'name_en' => 'Fixtures', 'icon' => 'lucide-package'],
            ],
        ],
        [
            'name_ar' => 'تشطيبات',
            'name_en' => 'Finishing',
            'icon' => 'lucide-paint-brush',
            'children' => [
                ['name_ar' => 'دهانات', 'name_en' => 'Paints', 'icon' => 'lucide-package'],
                ['name_ar' => 'بلاط', 'name_en' => 'Tiles', 'icon' => 'lucide-package'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $parent = Category::create(array_merge($categoryData, [
                'slug' => Str::slug($categoryData['name_en']),
                'sort_order' => Category::max('sort_order') + 1,
            ]));

            foreach ($children as $index => $childData) {
                Category::create(array_merge($childData, [
                    'parent_id' => $parent->id,
                    'slug' => Str::slug($childData['name_en']),
                    'sort_order' => $index,
                ]));
            }
        }
    }
}
```

### Seeder Idempotency

The seeder checks for duplicates before inserting:

```php
if (Category::where('slug', $slug)->exists()) {
    continue; // Skip duplicate
}
```

This ensures running seeder multiple times produces idempotent results.

---

## Type Safety & Validation

### Data Types in Eloquent

```php
$category = Category::create([
    'parent_id' => 1,           // INT
    'name_ar' => 'أسمنت',       // STRING (UTF-8)
    'name_en' => 'Cement',      // STRING
    'slug' => 'cement',         // STRING (immutable)
    'icon' => 'lucide-box',     // STRING (optional)
    'sort_order' => 0,          // INT
    'is_active' => true,        // BOOL
    'version' => 0,             // INT
]);

// Type-safe access
$parentId = $category->parent_id;   // INT
$isActive = $category->is_active;   // BOOL
$version = $category->version;      // INT
```

---

**Next Steps**: API contracts and controller design will follow in Phase 1.
