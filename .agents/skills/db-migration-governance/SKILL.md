---
name: db-migration-governance
description: MySQL migration governance, naming, safety
---

# Database Migration Governance — Bunyan

## Database: MySQL 8.x

### Migration Naming Convention

```
YYYY_MM_DD_HHMMSS_verb_noun_table.php
```

**Verbs**: `create`, `add`, `modify`, `drop`, `rename`

### Examples

```
2024_01_15_100000_create_users_table.php
2024_01_15_100001_create_projects_table.php
2024_01_15_100002_create_phases_table.php
2024_01_15_100003_add_budget_to_projects_table.php
```

## Non-Negotiable Rules

1. **Forward-only**: Never modify existing migration files
2. **Reversible**: Every `up()` must have a corresponding `down()`
3. **Atomic**: One logical change per migration
4. **Idempotent checks**: Use `Schema::hasTable()` / `Schema::hasColumn()` guards
5. **No raw SQL** unless absolutely necessary (documented why)
6. **Always set charset**: `utf8mb4` with `utf8mb4_unicode_ci` collation

## Migration Template

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('budget', 12, 2)->default(0);
            $table->string('location');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

## Lock Risk Assessment

Before generating a migration, AI must assess:

| Operation             | Risk Level | Lock Duration               |
| --------------------- | ---------- | --------------------------- |
| CREATE TABLE          | Low        | Minimal                     |
| ADD COLUMN (nullable) | Low        | Minimal                     |
| ADD COLUMN (default)  | Medium     | Table copy on MySQL < 8     |
| ADD INDEX             | Medium     | Can be slow on large tables |
| MODIFY COLUMN         | High       | Full table rewrite          |
| DROP COLUMN           | Medium     | Table rewrite               |
| RENAME COLUMN         | Medium     | Brief lock                  |

For **High risk** operations on production tables with >100k rows, AI must flag and suggest online schema change or batching strategy.

## Foreign Key Rules

1. Always use `$table->foreignId()` with `->constrained()`
2. Default to `->cascadeOnDelete()` for child records
3. Use `->nullOnDelete()` for optional relationships
4. Never use `->restrictOnDelete()` without explicit justification
5. Always add index on foreign key columns
