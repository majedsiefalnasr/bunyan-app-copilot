# Testing Guide — STAGE_02: Database Schema Foundation

> **Branch:** `spec/002-database-schema` > **Stage:** STAGE_02_DATABASE_SCHEMA
> **Phase:** 01_PLATFORM_FOUNDATION

---

## Prerequisites

1. Docker running (`docker-compose up -d`)
2. Backend container accessible: `docker-compose exec backend bash`
3. Test database configured in `.env.testing` (SQLite or MySQL test DB)

---

## Running the Full Test Suite

```bash
cd backend
composer run test
```

Expected output:

```
60 / 60   tests passed
107 assertions
0 failures
Time: ~2s
```

Or run with more detail:

```bash
php artisan test --testdox
```

---

## Running Individual Test Groups

### Unit Tests Only

```bash
php artisan test --filter Unit
```

### Feature Tests Only

```bash
php artisan test --filter Feature
```

### By Domain

```bash
# Migration tests
php artisan test tests/Feature/Migrations/

# Model tests
php artisan test tests/Feature/Models/

# Repository tests
php artisan test tests/Feature/Repositories/

# Seeder tests
php artisan test tests/Feature/Seeders/

# Database integration tests
php artisan test tests/Feature/Database/
```

---

## Migration Validation

### Fresh Migration (complete install)

```bash
cd backend
php artisan migrate:fresh
```

Expected:

- 9 migrations applied
- 0 errors

Tables created:

- `users` (updated with phone, is_active, avatar, deleted_at)
- `roles`
- `permissions`
- `role_user`
- `permission_role`

### Rollback Test (FK-safety)

```bash
php artisan migrate:rollback --step=5
```

Expected:

- 5 STAGE_02 migrations rolled back in reverse order
- FK constraint violations: none
- 0 errors

### Re-apply After Rollback

```bash
php artisan migrate
```

Expected: clean re-apply with no errors.

---

## Seeder Validation

### Run Full Seeder (non-production)

```bash
cd backend
php artisan db:seed
```

Expected output:

```
Seeding: RoleSeeder    → 5 roles created/updated
Seeding: PermissionSeeder → 26 permissions created/updated
Seeding: UserSeeder    → 5 test users created (skipped in production)
Database seeding completed successfully.
```

### Verify Seeded Data

Open Tinker and run:

```bash
php artisan tinker
```

```php
// Verify roles
\App\Models\Role::all()->pluck('name', 'display_name_ar');
// Expected:
// {'الإدارة' => 'admin', 'العميل' => 'customer', 'المقاول' => 'contractor',
//  'المهندس المشرف' => 'supervising_architect', 'المهندس الميداني' => 'field_engineer'}

// Verify permissions count
\App\Models\Permission::count();
// Expected: 26

// Verify permission groups
\App\Models\Permission::distinct()->pluck('group');
// Expected: ['users', 'projects', 'reports', 'transactions', 'products', 'orders', 'settings']

// Verify test users
\App\Models\User::with('roles')->get()->map(fn($u) => [$u->email, $u->roles->pluck('name')]);
```

### Idempotency Test

Run seeder twice — no duplicates or errors expected:

```bash
php artisan db:seed
php artisan db:seed
```

Expected: same counts, no unique constraint violations.

---

## Test User Credentials

> ⚠️ These credentials are for local/staging only. `UserSeeder` is guarded against production environments.

| Role                  | Email                    | Password   | Arabic Role Name |
| --------------------- | ------------------------ | ---------- | ---------------- |
| Admin                 | `admin@bunyan.test`      | `password` | الإدارة          |
| Customer              | `customer@bunyan.test`   | `password` | العميل           |
| Contractor            | `contractor@bunyan.test` | `password` | المقاول          |
| Supervising Architect | `architect@bunyan.test`  | `password` | المهندس المشرف   |
| Field Engineer        | `engineer@bunyan.test`   | `password` | المهندس الميداني |

Verify password hash is stored (not plaintext):

```php
// In Tinker
$user = \App\Models\User::where('email', 'admin@bunyan.test')->first();
\Hash::check('password', $user->password); // must return true
str_starts_with($user->password, '$2y$'); // Bcrypt prefix
```

---

## Factory Usage

The `UserFactory` supports all 5 role states for test setup:

```php
// In tests or Tinker

// Create admin user
$admin = User::factory()->admin()->create();

// Create 3 contractor users
$contractors = User::factory()->contractor()->count(3)->create();

// Create inactive user
$inactive = User::factory()->inactive()->create();

// Verify role assignment
$admin->roles->pluck('name'); // ['admin']
```

---

## Repository Manual Test

```php
// In Tinker
$repo = app(\App\Repositories\UserRepository::class);

// find() — by ID
$user = $repo->find(1);

// findByEmail()
$user = $repo->findByEmail('admin@bunyan.test');

// findActiveUsers()
$active = $repo->findActiveUsers(); // Collection of active users

// paginate()
$page = $repo->paginate(10); // LengthAwarePaginator, 10 per page
$page->total(); // total count
$page->currentPage(); // 1
```

---

## Relationship Traversal Test

```php
// In Tinker

// Get a user and traverse to permissions
$user = \App\Models\User::with('roles.permissions')->first();
$user->roles->first()->permissions->pluck('name');

// Verify cascade delete
$role = \App\Models\Role::where('name', 'customer')->first();
$user = User::factory()->create();
$user->roles()->attach($role->id);

// Confirm pivot exists
\DB::table('role_user')->where('user_id', $user->id)->count(); // 1

// Soft delete user — does NOT delete pivot
$user->delete();
\DB::table('role_user')->where('user_id', $user->id)->count(); // still 1 (soft delete only)

// Hard delete user — cascades pivot
$user->forceDelete();
\DB::table('role_user')->where('user_id', $user->id)->count(); // 0 (FK cascade)
```

---

## Lint Check

```bash
cd backend
composer run lint
```

Expected:

```
Found 0 errors in 55 files
```

---

## Type Check (PHPStan)

```bash
cd backend
composer run phpstan
```

Expected:

```
[OK] No errors
```

---

## Known Test Environment Notes

- **SQLite test environment**: Boolean column defaults are returned as the string `'1'` (not integer `1`) by SQLite's `SHOW COLUMNS` equivalent. `UsersMigrationTest` handles this with a `trim()` comparison for portability.
- Tests use `RefreshDatabase` trait — each test method resets the schema.
- Factory `afterCreating()` runs pivot attachment after the model is saved. Do not call `create()` and then manually attach roles if using factory states, as the role will be double-attached.
