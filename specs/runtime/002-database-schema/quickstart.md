# Developer Quickstart — STAGE_02: Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Branch**: `spec/002-database-schema`

---

## Prerequisites

- Docker is running with the MySQL container from `docker-compose.yml`
- Backend dependencies are installed: `cd backend && composer install`
- `.env` has correct `DB_*` values pointing to the Docker MySQL instance

---

## 1. Verify Docker MySQL Is Running

```bash
docker compose ps
```

Confirm the MySQL container (`bunyan-mysql` or similar) shows `running`. If not:

```bash
docker compose up -d mysql
```

---

## 2. Run Migrations

**Fresh environment** (drops and recreates all tables):

```bash
cd backend
php artisan migrate:fresh
```

**Existing database** (adds new migrations only):

```bash
cd backend
php artisan migrate
```

Expected output — 5 new migrations should run:

```
  2026_04_11_000001_add_profile_columns_to_users_table ... DONE
  2026_04_11_000002_create_roles_table .................. DONE
  2026_04_11_000003_create_permissions_table ............ DONE
  2026_04_11_000004_create_role_user_table .............. DONE
  2026_04_11_000005_create_permission_role_table ........ DONE
```

---

## 3. Seed the Database

```bash
php artisan db:seed
```

This runs in order:

1. `RoleSeeder` — creates 5 platform roles
2. `PermissionSeeder` — creates 25+ permissions across 7 groups
3. `UserSeeder` — creates 5 test users (one per role)

The seeder is **idempotent** — safe to run multiple times.

---

## 4. Verify the Seed Data

```bash
php artisan tinker
```

Then run:

```php
// Table record counts
User::count();        // → 5
Role::count();        // → 5
Permission::count();  // → 25+

// Verify admin user
User::where('email', 'admin@bunyan.test')->first()?->roles->pluck('name');
// → ["admin"]

// Verify role-user pivot
User::with('roles')->get()->map(fn($u) => [$u->email, $u->roles->pluck('name')]);

// Verify permissions by group
Permission::byGroup('users')->get()->pluck('name');
// → ["users.view", "users.create", "users.update", "users.delete", "users.impersonate"]

// Test soft deletes
$user = User::first();
$user->delete();
User::find($user->id);         // → null (soft deleted)
User::withTrashed()->find($user->id); // → User model

// Test active scope
User::active()->count();       // → all is_active=1 users
```

---

## 5. Run Tests

**All database tests**:

```bash
php artisan test --filter=Database
```

**Specific test groups**:

```bash
php artisan test tests/Feature/Database/MigrationTest.php
php artisan test tests/Feature/Database/RelationshipTest.php
php artisan test tests/Feature/Database/SeederTest.php
php artisan test tests/Feature/Database/FactoryTest.php
```

**Unit tests**:

```bash
php artisan test tests/Unit/Models/
```

**Full backend test suite**:

```bash
php artisan test
```

---

## 6. Using Role Factories in Tests

When writing PHPUnit tests, role-state factory methods require the `roles` table to contain the seeded roles.

**Option A** — Seed roles in test setup:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed(RoleSeeder::class);
}

// Then in your test:
$admin = User::factory()->admin()->create();
```

**Option B** — Create the role inline:

```php
$role = Role::create([
    'name'           => 'admin',
    'display_name'   => 'Administrator',
    'display_name_ar'=> 'الإدارة',
]);
$admin = User::factory()->admin()->create();
```

---

## 7. Rollback (If Needed)

```bash
php artisan migrate:rollback
```

This drops STAGE_02 migrations in reverse dependency order:

```
  2026_04_11_000005_create_permission_role_table ... DONE
  2026_04_11_000004_create_role_user_table ......... DONE
  2026_04_11_000003_create_permissions_table ....... DONE
  2026_04_11_000002_create_roles_table ............. DONE
  2026_04_11_000001_add_profile_columns_to_users... DONE
```

---

## 8. Linting

```bash
cd backend && composer run lint
```

PSR-12 compliance is required on all PHP files before committing.

---

## Test User Credentials

| Email                    | Password   | Role                  |
| ------------------------ | ---------- | --------------------- |
| `admin@bunyan.test`      | `password` | admin                 |
| `customer@bunyan.test`   | `password` | customer              |
| `contractor@bunyan.test` | `password` | contractor            |
| `architect@bunyan.test`  | `password` | supervising_architect |
| `engineer@bunyan.test`   | `password` | field_engineer        |

> **Security warning**: These credentials are for local development and CI environments only. Never seed these users in production.
