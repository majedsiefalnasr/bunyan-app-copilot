# Feature Specification: Database Schema Foundation

**Feature Branch**: `spec/002-database-schema`
**Stage**: STAGE_02 — DATABASE_SCHEMA
**Phase**: 01_PLATFORM_FOUNDATION
**Created**: 2026-04-11
**Status**: Draft
**Input**: Database schema foundation for the Bunyan construction marketplace platform

---

## Overview

This stage delivers the core MySQL database schema for Bunyan — a full-stack Arabic construction services and building materials marketplace. It establishes the foundational data structures, Eloquent model conventions, repository pattern base classes, seeders, and factories upon which all subsequent platform stages depend.

The deliverables are:
- Forward-only Laravel migrations for all identity and RBAC tables
- Eloquent models (`User`, `Role`, `Permission`) with typed relationships, casts, and scopes
- A `BaseModel` with shared traits (`SoftDeletes`, standard active scopes)
- `BaseRepository` interface and abstract implementation
- Database seeders for all 5 platform roles, core permissions, and test users per role
- `UserFactory` with per-role state methods for test isolation

This stage does **not** implement authentication (tokens, sessions) or enforce RBAC middleware — those are addressed in STAGE_03 and STAGE_04 respectively.

---

## User Scenarios & Testing _(mandatory)_

### User Story 1 — Migrations Run Successfully (Priority: P1)

As a backend engineer, I need the database migration suite to run from a clean MySQL 8.x database and produce all schema tables with correct columns, types, constraints, and indexes so that downstream stages can begin development.

**Why this priority**: All other stages depend on these tables. Without a valid schema, nothing can be built or tested.

**Independent Test**: Run `php artisan migrate:fresh` on a clean test database; assert that all 5 tables exist with expected columns and constraints via `artisan db:table <table>` or PHPUnit database assertions.

**Acceptance Scenarios**:

1. **Given** a clean MySQL 8.x database with utf8mb4_unicode_ci collation, **When** `php artisan migrate` is executed, **Then** all 5 tables (`users`, `roles`, `permissions`, `role_user`, `permission_role`) are created with zero errors.
2. **Given** all migrations have run, **When** `php artisan migrate:rollback` is executed, **Then** all 5 tables are dropped cleanly with zero errors.
3. **Given** all migrations have run, **When** any column is inspected, **Then** it matches the specification (name, type, nullable, default, charset where applicable).
4. **Given** all migrations have run, **When** indexes are inspected, **Then** `users.email` is UNIQUE, foreign key indexes exist on all pivot FK columns.

---

### User Story 2 — Eloquent Models with Relationships Work (Priority: P1)

As a backend engineer, I need the `User`, `Role`, and `Permission` Eloquent models to correctly resolve their relationships so that application code can query user roles and permissions without raw SQL.

**Why this priority**: Models are the programmatic API for all domain data access. Broken relationships block every feature layer.

**Independent Test**: Use PHPUnit feature tests with database transactions; seed one user, attach a role, attach a permission to that role; assert that `$user->roles`, `$role->permissions`, and `$user->roles()->first()->permissions` each return correct collections.

**Acceptance Scenarios**:

1. **Given** a seeded user and attached role, **When** `$user->roles` is accessed, **Then** it returns a collection containing the attached `Role` model.
2. **Given** a role with attached permissions, **When** `$role->permissions` is accessed, **Then** it returns the correct `Permission` collection.
3. **Given** a user, **When** `$user->hasRole('admin')` is called (scope method), **Then** it returns `true` if the user has the admin role, `false` otherwise.
4. **Given** the `User` model, **When** `$user->password` is accessed after creation, **Then** the value is hashed (cast via `hashed`).
5. **Given** a soft-deleted user, **When** `User::withTrashed()->find($id)` is used, **Then** the deleted user is returned; `User::find($id)` excludes it.

---

### User Story 3 — Seeders Populate Test Data (Priority: P2)

As a backend engineer, I need seeders to populate predictable test data (all 5 roles, core permissions, one admin user, and one test user per role) so that integration tests and local development have consistent starting state.

**Why this priority**: Test isolation depends on reliable seed data. Factories alone cannot guarantee named roles/permissions exist.

**Independent Test**: Run `php artisan db:seed`; assert via PHPUnit that `Role::count() === 5`, `Permission::count() >= 20`, users for all 5 roles exist, and admin user authenticates with known credentials.

**Acceptance Scenarios**:

1. **Given** an empty database after migration, **When** `php artisan db:seed` is run, **Then** exactly 5 roles exist with correct Arabic `display_name` values.
2. **Given** seeded roles, **When** permissions are inspected, **Then** at least 20 core permissions exist grouped by domain (`users`, `projects`, `reports`, `transactions`, `products`, `orders`, `settings`).
3. **Given** the seeded database, **When** `User::where('email', 'admin@bunyan.test')->first()` is called, **Then** a User is returned with the `admin` role attached.
4. **Given** the seeded database, **When** each of the 5 role test users is retrieved, **Then** each has exactly one role assigned matching their email domain (e.g., `customer@bunyan.test`, `contractor@bunyan.test`, etc.).

---

### User Story 4 — UserFactory Generates Role-State Users (Priority: P2)

As a test engineer, I need `UserFactory` state methods per role so that PHPUnit tests can create isolated users with a specific role without relying solely on seeders.

**Why this priority**: Feature and unit tests require deterministic user creation with role context. Seeders are too coarse for test-level isolation.

**Independent Test**: In a PHPUnit test, create `User::factory()->customer()->create()` and assert that the user has exactly the `customer` role attached; repeat for all 5 roles.

**Acceptance Scenarios**:

1. **Given** the `UserFactory`, **When** `User::factory()->admin()->create()` is called, **Then** the created user has the `admin` role in its `roles` relationship.
2. **Given** the factory, **When** `User::factory()->count(10)->customer()->create()` is called, **Then** 10 user records are created, all bearing the `customer` role.
3. **Given** the factory, **When** `User::factory()->make()` (without role state) is called, **Then** a valid `User` instance is produced without throwing exceptions.

---

### Edge Cases

- What happens when `php artisan migrate` is run twice? Idempotent — no duplicate table errors because of migration state tracking.
- What happens if `role_user` references a non-existent `user_id` or `role_id`? MySQL FK constraint raises an integrity constraint violation.
- What happens when `User::factory()->create()` is called without an attached role? The user is created with no roles; role-dependent features must handle empty roles gracefully in later stages.
- What happens if seeder runs on a database that already has data? `RoleSeeder` uses `updateOrCreate` to avoid duplicates; `UserSeeder` is idempotent via `firstOrCreate` on email.

---

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST create the `users` table with all columns defined in the Database Tables section, including computed indexes and soft-delete support.
- **FR-002**: System MUST create the `roles` table with slug-based `name`, human-readable `display_name`, and optional translated Arabic `display_name_ar`.
- **FR-003**: System MUST create the `permissions` table with slug-based `name`, `display_name`, and a `group` column for domain-based grouping.
- **FR-004**: System MUST create `role_user` pivot table with `role_id` FK → `roles.id` and `user_id` FK → `users.id`, both non-nullable with CASCADE delete.
- **FR-005**: System MUST create `permission_role` pivot table with `permission_id` FK → `permissions.id` and `role_id` FK → `roles.id`, both non-nullable with CASCADE delete.
- **FR-006**: All migrations MUST include a `down()` method that performs a clean full rollback.
- **FR-007**: All tables MUST use `utf8mb4` charset and `utf8mb4_unicode_ci` collation.
- **FR-008**: The `User` model MUST hide `password` and `remember_token` from serialization.
- **FR-009**: The `User` model MUST cast `email_verified_at` as `datetime`, `password` as `hashed`, and `is_active` as `boolean`.
- **FR-010**: The `User` model MUST use `SoftDeletes` and expose an `active()` local scope.
- **FR-011**: `BaseRepository` MUST define interface methods: `find`, `findAll`, `create`, `update`, `delete`, `paginate`.
- **FR-012**: `RoleSeeder` MUST insert exactly 5 roles matching the Bunyan domain model with both English slug and Arabic `display_name`.
- **FR-013**: `UserFactory` MUST provide state methods: `admin()`, `customer()`, `contractor()`, `supervisingArchitect()`, `fieldEngineer()`.
- **FR-014**: All migrations MUST be forward-only (no modification of existing migration files permitted).
- **FR-015**: Pivot tables MUST NOT use auto-increment primary keys; they use composite primary keys on (`role_id`, `user_id`) and (`permission_id`, `role_id`) respectively.

### Key Entities

- **User**: Platform account holder. Has `name`, `email` (unique), `phone` (nullable), hashed `password`, `email_verified_at`, `is_active` flag, `avatar` (nullable path), timestamps, and soft-delete. Belongs-to-many `Role`.
- **Role**: Platform role definition. Has slug `name` (unique), `display_name` (English), `display_name_ar` (Arabic), optional `description`, timestamps. Belongs-to-many `User` and `Permission`.
- **Permission**: Granular capability. Has slug `name` (unique), `display_name`, `group` (domain category), optional `description`, timestamps. Belongs-to-many `Role`.
- **role_user** (pivot): Links `User` ↔ `Role`. Timestamps included.
- **permission_role** (pivot): Links `Permission` ↔ `Role`. Timestamps included.

---

## Database Tables

### `users` Table

| Column              | Type                | Nullable | Default | Notes                                          |
| ------------------- | ------------------- | -------- | ------- | ---------------------------------------------- |
| `id`                | `bigint UNSIGNED`   | NO       | —       | Auto-increment primary key                     |
| `name`              | `varchar(255)`      | NO       | —       | Full name; utf8mb4                             |
| `email`             | `varchar(255)`      | NO       | —       | UNIQUE index; utf8mb4_unicode_ci               |
| `phone`             | `varchar(30)`       | YES      | NULL    | E.164 format recommended; no uniqueness needed |
| `password`          | `varchar(255)`      | NO       | —       | Bcrypt hash; hidden from serialization         |
| `email_verified_at` | `timestamp`         | YES      | NULL    | Set on email verification                      |
| `remember_token`    | `varchar(100)`      | YES      | NULL    | Laravel auth; hidden from serialization        |
| `is_active`         | `tinyint(1)`        | NO       | `1`     | Cast to boolean; `active()` scope filters this |
| `avatar`            | `varchar(500)`      | YES      | NULL    | Relative or S3 path; actual storage in later stage |
| `created_at`        | `timestamp`         | YES      | NULL    | Laravel managed                                |
| `updated_at`        | `timestamp`         | YES      | NULL    | Laravel managed                                |
| `deleted_at`        | `timestamp`         | YES      | NULL    | SoftDeletes column                             |

**Indexes**: `UNIQUE(email)`, index on `is_active`, index on `deleted_at`.

---

### `roles` Table

| Column           | Type           | Nullable | Default | Notes                                   |
| ---------------- | -------------- | -------- | ------- | --------------------------------------- |
| `id`             | `bigint UNSIGNED` | NO     | —       | Auto-increment primary key              |
| `name`           | `varchar(100)` | NO       | —       | Slug form (e.g., `admin`); UNIQUE       |
| `display_name`   | `varchar(150)` | NO       | —       | English label (e.g., `Administrator`)   |
| `display_name_ar`| `varchar(150)` | NO       | —       | Arabic label (e.g., `الإدارة`)          |
| `description`    | `text`         | YES      | NULL    | Optional explanation                    |
| `created_at`     | `timestamp`    | YES      | NULL    | Laravel managed                         |
| `updated_at`     | `timestamp`    | YES      | NULL    | Laravel managed                         |

**Indexes**: `UNIQUE(name)`.

---

### `permissions` Table

| Column         | Type           | Nullable | Default | Notes                                       |
| -------------- | -------------- | -------- | ------- | ------------------------------------------- |
| `id`           | `bigint UNSIGNED` | NO     | —       | Auto-increment primary key                  |
| `name`         | `varchar(150)` | NO       | —       | Dot-notation slug (e.g., `users.create`); UNIQUE |
| `display_name` | `varchar(200)` | NO       | —       | Human-readable English label                |
| `group`        | `varchar(100)` | NO       | —       | Domain group (e.g., `users`, `projects`)    |
| `description`  | `text`         | YES      | NULL    | Optional explanation                        |
| `created_at`   | `timestamp`    | YES      | NULL    | Laravel managed                             |
| `updated_at`   | `timestamp`    | YES      | NULL    | Laravel managed                             |

**Indexes**: `UNIQUE(name)`, index on `group`.

---

### `role_user` Pivot Table

| Column       | Type              | Nullable | Default | Notes                                        |
| ------------ | ----------------- | -------- | ------- | -------------------------------------------- |
| `role_id`    | `bigint UNSIGNED` | NO       | —       | FK → `roles.id` ON DELETE CASCADE            |
| `user_id`    | `bigint UNSIGNED` | NO       | —       | FK → `users.id` ON DELETE CASCADE            |
| `created_at` | `timestamp`       | YES      | NULL    | Pivot timestamps                             |
| `updated_at` | `timestamp`       | YES      | NULL    | Pivot timestamps                             |

**Primary Key**: Composite `(role_id, user_id)`.  
**Indexes**: Index on `user_id` for reverse lookups.

---

### `permission_role` Pivot Table

| Column          | Type              | Nullable | Default | Notes                                        |
| --------------- | ----------------- | -------- | ------- | -------------------------------------------- |
| `permission_id` | `bigint UNSIGNED` | NO       | —       | FK → `permissions.id` ON DELETE CASCADE      |
| `role_id`       | `bigint UNSIGNED` | NO       | —       | FK → `roles.id` ON DELETE CASCADE            |
| `created_at`    | `timestamp`       | YES      | NULL    | Pivot timestamps                             |
| `updated_at`    | `timestamp`       | YES      | NULL    | Pivot timestamps                             |

**Primary Key**: Composite `(permission_id, role_id)`.  
**Indexes**: Index on `role_id` for reverse lookups.

---

## Eloquent Models

### `User` Model (`app/Models/User.php`)

```
Extends: Illuminate\Foundation\Auth\User (Authenticatable)
Uses: SoftDeletes, HasFactory
```

| Property/Method    | Value/Description                                                   |
| ------------------ | ------------------------------------------------------------------- |
| `$fillable`        | `name`, `email`, `phone`, `password`, `is_active`, `avatar`        |
| `$hidden`          | `password`, `remember_token`                                        |
| `$casts`           | `email_verified_at` → `datetime`, `password` → `hashed`, `is_active` → `boolean` |
| `roles()`          | `belongsToMany(Role::class, 'role_user')->withTimestamps()`         |
| `scopeActive()`    | `->where('is_active', true)`                                        |
| `scopeByRole()`    | `->whereHas('roles', fn($q) => $q->where('name', $role))`          |
| `hasRole(string)`  | Returns `bool`; checks `roles` relationship for given slug          |

**Notes**: No business logic. No Sanctum token methods in this stage.

---

### `Role` Model (`app/Models/Role.php`)

```
Extends: Illuminate\Database\Eloquent\Model
Uses: HasFactory
```

| Property/Method  | Value/Description                                                   |
| ---------------- | ------------------------------------------------------------------- |
| `$fillable`      | `name`, `display_name`, `display_name_ar`, `description`           |
| `$casts`         | none beyond defaults                                                |
| `users()`        | `belongsToMany(User::class, 'role_user')->withTimestamps()`        |
| `permissions()`  | `belongsToMany(Permission::class, 'permission_role')->withTimestamps()` |

**Notes**: `name` is slug-form and must be lowercase ASCII.

---

### `Permission` Model (`app/Models/Permission.php`)

```
Extends: Illuminate\Database\Eloquent\Model
Uses: HasFactory
```

| Property/Method  | Value/Description                                                   |
| ---------------- | ------------------------------------------------------------------- |
| `$fillable`      | `name`, `display_name`, `group`, `description`                     |
| `$casts`         | none beyond defaults                                                |
| `roles()`        | `belongsToMany(Role::class, 'permission_role')->withTimestamps()`  |
| `scopeByGroup()` | `->where('group', $group)`                                          |

---

## Base Model

### `BaseModel` (`app/Models/BaseModel.php`)

All future application models (beyond User/Role/Permission) MUST extend `BaseModel`.

```
Extends: Illuminate\Database\Eloquent\Model
Uses: SoftDeletes, HasFactory
```

**Behaviors**:
- Disables mass assignment protection via `$guarded = []` with explicit `$fillable` required on child models
- Defines `scopeActive($query)` returning `$query->where('is_active', true)` when column exists
- Sets `$dateFormat = 'Y-m-d H:i:s'` for consistent MySQL datetime serialization
- No business logic; no HTTP concerns

**Note**: `User` extends `Authenticatable` and therefore does not extend `BaseModel` directly, but MUST implement the same traits and conventions manually.

---

## Repository Pattern

### `RepositoryInterface` (`app/Repositories/Contracts/RepositoryInterface.php`)

```php
interface RepositoryInterface
{
    public function find(int $id): ?Model;
    public function findAll(): Collection;
    public function findBy(array $criteria): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): Model;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
```

### `BaseRepository` (`app/Repositories/BaseRepository.php`)

```
Implements: RepositoryInterface
```

**Properties**:
- `protected Model $model` — injected via constructor

**Method contracts**:

| Method      | Implementation note                                                       |
| ----------- | ------------------------------------------------------------------------- |
| `find`      | `$this->model->find($id)` — returns null if not found                   |
| `findAll`   | `$this->model->all()`                                                    |
| `findBy`    | `$this->model->where($criteria)->get()`                                  |
| `create`    | `$this->model->create($data)` — returns persisted model                  |
| `update`    | Find by ID → `fill($data)` → `save()` — throws `ModelNotFoundException` |
| `delete`    | Find by ID → `delete()` — returns `bool`                                 |
| `paginate`  | `$this->model->paginate($perPage)`                                       |

**Notes**: No business logic. No HTTP exceptions. Service layer handles domain exceptions.

### `UserRepository` (`app/Repositories/UserRepository.php`)

```
Extends: BaseRepository
Constructor: injects User model
```

Additional methods (beyond base):
- `findByEmail(string $email): ?User`
- `findActiveUsers(): Collection` — delegates to `User::active()->get()`

---

## Seeders

### `RoleSeeder` (`database/seeders/RoleSeeder.php`)

Inserts exactly 5 roles via `Role::updateOrCreate(['name' => $slug], $data)`:

| Slug                   | `display_name`         | `display_name_ar`   |
| ---------------------- | ---------------------- | ------------------- |
| `admin`                | Administrator          | الإدارة             |
| `customer`             | Customer               | العميل              |
| `contractor`           | Contractor             | المقاول             |
| `supervising_architect`| Supervising Architect  | المهندس المشرف      |
| `field_engineer`       | Field Engineer         | المهندس الميداني    |

### `PermissionSeeder` (`database/seeders/PermissionSeeder.php`)

Inserts core permissions grouped by domain. Minimum 20 permissions. Groups and examples:

| Group          | Example Permission Slugs                               |
| -------------- | ------------------------------------------------------ |
| `users`        | `users.view`, `users.create`, `users.update`, `users.delete` |
| `projects`     | `projects.view`, `projects.create`, `projects.update`, `projects.delete`, `projects.manage` |
| `reports`      | `reports.view`, `reports.create`, `reports.approve`    |
| `transactions` | `transactions.view`, `transactions.create`, `transactions.approve` |
| `products`     | `products.view`, `products.create`, `products.update`, `products.delete` |
| `orders`       | `orders.view`, `orders.create`, `orders.manage`        |
| `settings`     | `settings.view`, `settings.update`                     |

Uses `Permission::updateOrCreate(['name' => $slug], $data)` for idempotency.

### `UserSeeder` (`database/seeders/UserSeeder.php`)

Creates one user per role via `User::firstOrCreate(['email' => $email], $data)` then attaches the matching role:

| Email                       | Role                   | Password (seeded) |
| --------------------------- | ---------------------- | ----------------- |
| `admin@bunyan.test`         | `admin`                | `password`        |
| `customer@bunyan.test`      | `customer`             | `password`        |
| `contractor@bunyan.test`    | `contractor`           | `password`        |
| `architect@bunyan.test`     | `supervising_architect`| `password`        |
| `engineer@bunyan.test`      | `field_engineer`       | `password`        |

**Security note**: Test passwords use `bcrypt('password')` — these users are strictly for local/CI environments; never seeded in production.

### `DatabaseSeeder` (`database/seeders/DatabaseSeeder.php`)

Calls seeders in dependency order:
1. `RoleSeeder`
2. `PermissionSeeder`
3. `UserSeeder`

---

## Factories

### `UserFactory` (`database/factories/UserFactory.php`)

**Default state**: generates a random user with `is_active = true`, verified email, no role attached.

**State methods**:

| State method           | Role slug assigned      |
| ---------------------- | ----------------------- |
| `admin()`              | `admin`                 |
| `customer()`           | `customer`              |
| `contractor()`         | `contractor`            |
| `supervisingArchitect()`| `supervising_architect`|
| `fieldEngineer()`      | `field_engineer`        |

Each state method uses `afterCreating()` callback to attach the role via `$user->roles()->attach(Role::where('name', $slug)->firstOrFail())`.

**Unverified state**: `unverified()` — sets `email_verified_at = null`.

**Inactive state**: `inactive()` — sets `is_active = false`.

**Note**: `RoleFactory` is not needed — roles are never generated randomly; they are seeded with fixed slugs.

---

## Non-Functional Requirements

- **NFR-001 (Charset)**: All text columns MUST use `charset('utf8mb4')->collation('utf8mb4_unicode_ci')`. The full database connection in `config/database.php` MUST specify `charset: 'utf8mb4'` and `collation: 'utf8mb4_unicode_ci'`.
- **NFR-002 (MySQL 8.x)**: All migrations MUST be compatible with MySQL 8.x. Avoid `ENUM` types in favor of `tinyint` or `varchar` with application-level validation. Do not use deprecated MySQL 5.x syntax.
- **NFR-003 (Indexes)**: `users.email` MUST be UNIQUE. All foreign key columns MUST be indexed. The `permissions.group` column MUST be indexed for group-based permission lookups.
- **NFR-004 (Migration Order)**: Migration files MUST be numbered in dependency order: users → roles → permissions → role_user → permission_role.
- **NFR-005 (PSR-12)**: All PHP files MUST comply with PSR-12 coding standards and pass `composer run lint`.
- **NFR-006 (No Business Logic in Models)**: Models MUST contain only relationships, local scopes, casts, `$fillable`, and `$hidden`. No service calls, no HTTP calls, no event dispatching (that belongs in services/observers).
- **NFR-007 (SoftDeletes on Users)**: The `users` table MUST support soft deletion. The `roles` and `permissions` tables do NOT use soft deletes (they are configuration data).
- **NFR-008 (Test Isolation)**: All PHPUnit tests touching the database MUST use `RefreshDatabase` or `DatabaseTransactions` trait to ensure test isolation.
- **NFR-009 (Timestamps on Pivots)**: Both pivot tables MUST use `->withTimestamps()` in their Eloquent relationship definitions.

---

## Out of Scope

The following are explicitly **NOT** part of this stage and must not be implemented here:

| Item                                   | Reason / Stage                                     |
| -------------------------------------- | -------------------------------------------------- |
| Laravel Sanctum API token generation   | STAGE_03 — Authentication                         |
| Login / logout / register endpoints    | STAGE_03 — Authentication                         |
| RBAC middleware and gate definitions   | STAGE_04 — RBAC System                            |
| Policy classes                         | STAGE_04 — RBAC System                            |
| Email verification flow                | STAGE_03 — Authentication                         |
| Password reset flow                    | STAGE_03 — Authentication                         |
| JWT or OAuth                           | Not planned; Sanctum only                          |
| Project, Phase, Task tables            | Later phases                                       |
| Product, Order tables                  | Later phases                                       |
| File/avatar upload processing          | STAGE_05 or later                                  |
| Caching layer (Redis/Memcached)        | Infrastructure stage                               |
| Workflow engine tables                 | STAGE_06 or later                                  |
| Admin UI or Filament                   | Not in scope for this platform                     |
| Multi-tenancy                          | Not applicable to Bunyan domain                    |
| Internationalization of model layer    | Arabic handled at API response/display layer only  |

---

## Success Criteria _(mandatory)_

- **SC-001**: `php artisan migrate:fresh` on a clean MySQL 8.x database completes in under 10 seconds with zero errors.
- **SC-002**: `php artisan migrate:rollback --step=5` (or full rollback) completes with zero errors, leaving no Bunyan tables.
- **SC-003**: `php artisan db:seed` completes with exactly 5 roles, 20+ permissions, and 5 test users, all verifiable via SQL count queries.
- **SC-004**: PHPUnit feature tests pass 100% for all Eloquent relationship scenarios — `User->roles`, `Role->users`, `Role->permissions`, `Permission->roles`.
- **SC-005**: `User::factory()->admin()->create()` produces a user with the `admin` role; similar for all 5 role states.
- **SC-006**: `composer run lint` passes with zero violations.
- **SC-007**: `composer run test` passes all migration and model tests with zero failures.
- **SC-008**: `User::withTrashed()` returns soft-deleted users; `User::all()` excludes them.
- **SC-009**: All pivot records are CASCADE-deleted when a parent `User` or `Role` is deleted.

---

## Assumptions

- Laravel 11.x is the target framework version (as established in STAGE_01).
- MySQL 8.x with InnoDB engine is the target database (Docker Compose already configured).
- The `develop` branch is the base for all feature branches; this stage branches from `develop` as established in STAGE_01.
- Test user passwords are `password` (hashed via bcrypt); this is acceptable for local/CI only.
- The `users` table uses auto-increment `bigint` IDs (not UUIDs) in this stage. UUID support is deferred — [NEEDS CLARIFICATION: confirm if UUID primary keys are required for users, or if auto-increment `bigint` is acceptable for the platform].
- `display_name_ar` is a separate column rather than a JSON translation column — if a JSON-based localization approach is preferred, this should be revisited. [NEEDS CLARIFICATION: prefer separate `_ar` columns for each translatable field, or a single `translations` JSON column, or a polymorphic `model_translations` table?]
- `RoleFactory` is not needed; roles are configuration data seeded explicitly.
- The `avatar` column stores a path/URL string; actual file validation and storage logic is deferred to a later file-management stage.
