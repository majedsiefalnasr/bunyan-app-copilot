# Implementation Plan — STAGE_02: Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Branch**: `spec/002-database-schema`
**Prepared**: 2026-04-11
**Spec**: `specs/runtime/002-database-schema/spec.md`
**Data Model**: `specs/runtime/002-database-schema/data-model.md`
**Research**: `specs/runtime/002-database-schema/research.md`

---

## Plan Summary

| Metric | Value |
|--------|-------|
| Total phases | 7 (Phase 0 – Phase 6) |
| Total files to create | 17 |
| Total files to modify | 3 |
| Estimated tasks | 34 |
| Key risks | 4 (see §Risks) |

---

## Dependency Graph

```
Phase 0 (Verification)
    └─ Phase 1 (Migrations)
        └─ Phase 2 (Models)
            ├─ Phase 3 (Repositories)
            └─ Phase 4 (Seeders)       ← depends on Models
                └─ Phase 5 (Factories) ← depends on Seeders (role records)
                    └─ Phase 6 (Tests)
```

---

## Phase 0 — Pre-Implementation Verification

**Goal**: Confirm STAGE_01 output is clean and the environment is ready.

| Task | Action | Acceptance |
|------|--------|-----------|
| P0-T1 | Run `php artisan migrate:fresh` on dev database | Zero errors; all STAGE_01 tables exist |
| P0-T2 | Verify `app/Models/User.php` has `UserRole` cast and `HasApiTokens` | File matches STAGE_01 spec |
| P0-T3 | Verify `app/Enums/UserRole.php` has all 5 cases | `admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer` |
| P0-T4 | Confirm `composer run lint` passes on current codebase | Zero PSR-12 violations |
| P0-T5 | Confirm current branch is `spec/002-database-schema` | `git branch --show-current` |

---

## Phase 1 — Database Migrations

**Goal**: Create 5 new migrations that alter the `users` table and create the 4 new tables.

**Constraint**: All files use `2026_04_11_XXXXXX_` date prefix to place them after all STAGE_01 migrations in Laravel's alphabetical sort order.

---

### Migration 1 — Add Profile Columns to Users Table

**File**: `backend/database/migrations/2026_04_11_000001_add_profile_columns_to_users_table.php`

**Dependencies**: STAGE_01 `create_users_table` migration

**Implementation Notes**:
- Use `Schema::table('users', ...)` (not `Schema::create`)
- `up()` adds:
  - `$table->string('phone', 30)->nullable()->after('email')` 
  - `$table->boolean('is_active')->default(true)->after('remember_token')`
  - `$table->string('avatar', 500)->nullable()->after('is_active')`
  - `$table->softDeletes()` (adds `deleted_at` timestamp)
  - `$table->index('is_active')`
  - `$table->index('deleted_at')`
- `down()` reverses with `$table->dropColumn()` for each added column and `$table->dropIndex()` for indexes
- No charset override needed — inherits from table default set in STAGE_01

**Acceptance Criteria**: US1-AC1, US1-AC3

---

### Migration 2 — Create Roles Table

**File**: `backend/database/migrations/2026_04_11_000002_create_roles_table.php`

**Dependencies**: None (no FKs pointing to other new tables)

**Implementation Notes**:
- `Schema::create('roles', ...)`
- Columns: `id()`, `string('name', 100)->unique()`, `string('display_name', 150)`, `string('display_name_ar', 150)`, `text('description')->nullable()`, `timestamps()`
- `down()`: `Schema::dropIfExists('roles')`
- No charset directive per column — rely on database connection config (`utf8mb4`)

**Acceptance Criteria**: US1-AC1, US1-AC3, FR-002

---

### Migration 3 — Create Permissions Table

**File**: `backend/database/migrations/2026_04_11_000003_create_permissions_table.php`

**Dependencies**: None

**Implementation Notes**:
- `Schema::create('permissions', ...)`
- Columns: `id()`, `string('name', 150)->unique()`, `string('display_name', 200)`, `string('group', 100)`, `text('description')->nullable()`, `timestamps()`
- Index: `$table->index('group')` — required by NFR-003
- `down()`: `Schema::dropIfExists('permissions')`

**Acceptance Criteria**: US1-AC1, US1-AC3, FR-003, NFR-003

---

### Migration 4 — Create role_user Pivot Table

**File**: `backend/database/migrations/2026_04_11_000004_create_role_user_table.php`

**Dependencies**: migrations 1 (`users` updated) and 2 (`roles` exists)

**Implementation Notes**:
- `Schema::create('role_user', ...)`
- Columns: `$table->foreignId('role_id')->constrained()->cascadeOnDelete()`, `$table->foreignId('user_id')->constrained()->cascadeOnDelete()`, `$table->timestamps()`
- Primary key: `$table->primary(['role_id', 'user_id'])` — composite, no auto-increment (FR-015)
- **Do NOT call** `$table->id()` — this is a pivot table with no surrogate key
- Index `user_id` explicitly: `$table->index('user_id')` for reverse lookup performance
- `down()`: `Schema::dropIfExists('role_user')`

**Acceptance Criteria**: US1-AC1, US1-AC4, FR-004, FR-015

---

### Migration 5 — Create permission_role Pivot Table

**File**: `backend/database/migrations/2026_04_11_000005_create_permission_role_table.php`

**Dependencies**: migrations 2 (`roles`) and 3 (`permissions`)

**Implementation Notes**:
- `Schema::create('permission_role', ...)`
- Columns: `$table->foreignId('permission_id')->constrained()->cascadeOnDelete()`, `$table->foreignId('role_id')->constrained()->cascadeOnDelete()`, `$table->timestamps()`
- Primary key: `$table->primary(['permission_id', 'role_id'])` — composite (FR-015)
- Index `role_id` explicitly: `$table->index('role_id')` for reverse lookup
- `down()`: `Schema::dropIfExists('permission_role')`

**Acceptance Criteria**: US1-AC1, US1-AC4, FR-005, FR-015

---

## Phase 2 — Eloquent Models

**Goal**: Update User model and create Role, Permission, and BaseModel with correct relationships, scopes, and casts.

---

### Task 2.1 — Update User Model

**File**: `backend/app/Models/User.php` (MODIFY)

**Dependencies**: Migration 1 (adds `phone`, `is_active`, `avatar`, `deleted_at`)

**Implementation Notes**:
- Convert `#[Fillable]` attribute to explicit `$fillable` array property: `['name', 'email', 'phone', 'password', 'is_active', 'avatar']`
  - **Do NOT add `role` to `$fillable`** (SEC-FINDING-A: privilege escalation vector — `role` is an enum column assigned only via explicit `$user->role = UserRole::X; $user->save()` in seeders/factories, never via mass assignment)
- Convert `#[Hidden]` attribute to explicit `$hidden` array property: `['password', 'remember_token']`
- Add `use SoftDeletes;` (import `Illuminate\Database\Eloquent\SoftDeletes`)
- Add `$casts` property, updating to include `'is_active' => 'boolean'`
- Add `roles()` relationship: `belongsToMany(Role::class, 'role_user')->withTimestamps()`
- Add `scopeActive(Builder $query): Builder` — `return $query->where('is_active', true)`
- Add `scopeByRole(Builder $query, string $role): Builder` — `return $query->whereHas('roles', fn ($q) => $q->where('name', $role))`
- Rename existing `hasRole(UserRole $role)` → `hasEnumRole(UserRole $role)` (preserve enum-based logic; used by STAGE_03 after update); add new `hasRole(string $roleSlug): bool` checking `roles` pivot relationship — PHP does not support method overloading so both cannot share the same name
- Keep `HasApiTokens`, `HasFactory`, `Notifiable` traits
- Import: `App\Models\Role`, `Illuminate\Database\Eloquent\Builder`, `Illuminate\Database\Eloquent\SoftDeletes`

**Acceptance Criteria**: US2-AC1, US2-AC3, US2-AC4, US2-AC5, FR-008, FR-009, FR-010

---

### Task 2.2 — Create Role Model

**File**: `backend/app/Models/Role.php` (CREATE)

**Dependencies**: Migration 2 (`roles` table)

**Implementation Notes**:
- Extends `Illuminate\Database\Eloquent\Model`
- Uses `HasFactory`
- `$fillable = ['name', 'display_name', 'display_name_ar', 'description']`
- `users()`: `belongsToMany(User::class, 'role_user')->withTimestamps()`
- `permissions()`: `belongsToMany(Permission::class, 'permission_role')->withTimestamps()`
- No `$casts` beyond defaults
- No SoftDeletes (NFR-007)

**Acceptance Criteria**: US2-AC1, US2-AC2, FR-002, NFR-006

---

### Task 2.3 — Create Permission Model

**File**: `backend/app/Models/Permission.php` (CREATE)

**Dependencies**: Migration 3 (`permissions` table)

**Implementation Notes**:
- Extends `Illuminate\Database\Eloquent\Model`
- Uses `HasFactory`
- `$fillable = ['name', 'display_name', 'group', 'description']`
- `roles()`: `belongsToMany(Role::class, 'permission_role')->withTimestamps()`
- `scopeByGroup(Builder $query, string $group): Builder` — `return $query->where('group', $group)`
- No SoftDeletes (NFR-007)

**Acceptance Criteria**: US2-AC2, FR-003, NFR-006

---

### Task 2.4 — Create BaseModel

**File**: `backend/app/Models/BaseModel.php` (CREATE)

**Dependencies**: None

**Implementation Notes**:
- Extends `Illuminate\Database\Eloquent\Model`
- Uses `SoftDeletes`, `HasFactory`
- `protected $guarded = []` — child models must declare explicit `$fillable`
- `protected $dateFormat = 'Y-m-d H:i:s'`
- `scopeActive(Builder $query): Builder` — `return $query->where('is_active', true)` (child models needing this scope inherit it automatically)
- Docblock: note that `User` extends `Authenticatable` and does not extend `BaseModel` but manually follows the same conventions
- No business logic, no HTTP concerns (NFR-006)

**Acceptance Criteria**: NFR-006, NFR-007

---

## Phase 3 — Repository Pattern

**Goal**: Establish the `RepositoryInterface` contract and `BaseRepository` abstract implementation, plus `UserRepository`.

---

### Task 3.1 — Create Contracts Directory and RepositoryInterface

**File**: `backend/app/Repositories/Contracts/RepositoryInterface.php` (CREATE)

**Dependencies**: None

**Implementation Notes**:

```php
namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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

**Acceptance Criteria**: FR-011

---

### Task 3.2 — Create BaseRepository

**File**: `backend/app/Repositories/BaseRepository.php` (CREATE)

**Dependencies**: Task 3.1 (RepositoryInterface)

**Implementation Notes**:
- Abstract class implements `RepositoryInterface`
- Constructor: `public function __construct(protected Model $model)`
- `find(int $id): ?Model` — `return $this->model->find($id)`
- `findAll(): Collection` — `return $this->model->all()`
- `findBy(array $criteria): Collection` — `return $this->model->where($criteria)->get()`
- `create(array $data): Model` — `return $this->model->create($data)`
- `update(int $id, array $data): Model` — find by ID (throw `ModelNotFoundException` if null), then `fill($data)->save()`, return model
- `delete(int $id): bool` — find by ID (throw `ModelNotFoundException` if null), then `delete()`, return `bool`
- `paginate(int $perPage = 15): LengthAwarePaginator` — `return $this->model->paginate($perPage)`
- Import `Illuminate\Database\Eloquent\ModelNotFoundException`
- No business logic. No HTTP exceptions.

**Acceptance Criteria**: FR-011

---

### Task 3.3 — Create UserRepository

**File**: `backend/app/Repositories/UserRepository.php` (CREATE)

**Dependencies**: Task 3.2 (BaseRepository), Task 2.1 (User model updated)

**Implementation Notes**:
- Extends `BaseRepository`
- Constructor injects `User` model: `public function __construct(User $model) { parent::__construct($model); }`
- `findByEmail(string $email): ?User` — `return $this->model->where('email', $email)->first()`
- `findActiveUsers(): Collection` — `return $this->model->active()->get()`
- Import `App\Models\User`, `Illuminate\Database\Eloquent\Collection`

**Acceptance Criteria**: FR-011

---

### Task 3.4 — Register Repository Binding in AppServiceProvider

**File**: `backend/app/Providers/AppServiceProvider.php` (MODIFY)

**Dependencies**: Tasks 3.1, 3.3

**Implementation Notes**:
- In `register()` method add:
  ```php
  $this->app->bind(UserRepository::class, fn () => new UserRepository(new User()));
  ```
- This enables constructor injection of `UserRepository` in future service classes
- Do NOT bind `RepositoryInterface` globally — bind each concrete repository separately to avoid ambiguity

**Acceptance Criteria**: FR-011 (enablement)

---

## Phase 4 — Database Seeders

**Goal**: Populate roles, permissions, and test users with deterministic, idempotent data.

---

### Task 4.1 — Create RoleSeeder

**File**: `backend/database/seeders/RoleSeeder.php` (CREATE)

**Dependencies**: Migration 2 (roles table), Task 2.2 (Role model)

**Implementation Notes**:
- Use `Role::updateOrCreate(['name' => $slug], $data)` for idempotency
- Insert exactly 5 roles:

| Slug | display_name | display_name_ar |
|------|-------------|-----------------|
| `admin` | Administrator | الإدارة |
| `customer` | Customer | العميل |
| `contractor` | Contractor | المقاول |
| `supervising_architect` | Supervising Architect | المهندس المشرف |
| `field_engineer` | Field Engineer | المهندس الميداني |

**Acceptance Criteria**: US3-AC1, FR-012

---

### Task 4.2 — Create PermissionSeeder

**File**: `backend/database/seeders/PermissionSeeder.php` (CREATE)

**Dependencies**: Migration 3 (permissions table), Task 2.3 (Permission model)

**Implementation Notes**:
- Define permissions as a PHP array keyed by group, then iterate
- Use `Permission::updateOrCreate(['name' => $slug], $data)` for idempotency
- Minimum 25 permissions across 7 groups:

```php
$permissions = [
    'users' => [
        ['name' => 'users.view',       'display_name' => 'View Users'],
        ['name' => 'users.create',     'display_name' => 'Create Users'],
        ['name' => 'users.update',     'display_name' => 'Update Users'],
        ['name' => 'users.delete',     'display_name' => 'Delete Users'],
        ['name' => 'users.impersonate','display_name' => 'Impersonate Users'],
    ],
    'projects' => [
        ['name' => 'projects.view',    'display_name' => 'View Projects'],
        ['name' => 'projects.create',  'display_name' => 'Create Projects'],
        ['name' => 'projects.update',  'display_name' => 'Update Projects'],
        ['name' => 'projects.delete',  'display_name' => 'Delete Projects'],
        ['name' => 'projects.manage',  'display_name' => 'Manage Projects'],
    ],
    'reports' => [
        ['name' => 'reports.view',     'display_name' => 'View Reports'],
        ['name' => 'reports.create',   'display_name' => 'Create Reports'],
        ['name' => 'reports.approve',  'display_name' => 'Approve Reports'],
        ['name' => 'reports.reject',   'display_name' => 'Reject Reports'],
    ],
    'transactions' => [
        ['name' => 'transactions.view',    'display_name' => 'View Transactions'],
        ['name' => 'transactions.create',  'display_name' => 'Create Transactions'],
        ['name' => 'transactions.approve', 'display_name' => 'Approve Transactions'],
    ],
    'products' => [
        ['name' => 'products.view',    'display_name' => 'View Products'],
        ['name' => 'products.create',  'display_name' => 'Create Products'],
        ['name' => 'products.update',  'display_name' => 'Update Products'],
        ['name' => 'products.delete',  'display_name' => 'Delete Products'],
    ],
    'orders' => [
        ['name' => 'orders.view',    'display_name' => 'View Orders'],
        ['name' => 'orders.create',  'display_name' => 'Create Orders'],
        ['name' => 'orders.manage',  'display_name' => 'Manage Orders'],
    ],
    'settings' => [
        ['name' => 'settings.view',   'display_name' => 'View Settings'],
        ['name' => 'settings.update', 'display_name' => 'Update Settings'],
    ],
];
```

**Acceptance Criteria**: US3-AC2, FR-003

---

### Task 4.3 — Create UserSeeder

**File**: `backend/database/seeders/UserSeeder.php` (CREATE)

**Dependencies**: Task 4.1 (RoleSeeder must run first), Task 2.1 (User model updated)

**Implementation Notes**:
- Use `User::firstOrCreate(['email' => $email], $data)` for idempotency
- Set `role` enum column (STAGE_01 field) AND attach `roles` relationship (STAGE_02 RBAC)
- After creating user: `$user->roles()->syncWithoutDetaching([Role::where('name', $roleSlug)->firstOrFail()->id])`
- Use `syncWithoutDetaching` to avoid duplicate pivot errors on re-seeding
- Passwords: `bcrypt('password')` — **strictly test/local/CI only; never production**

| Email | `role` enum | Role slug | name |
|-------|------------|-----------|------|
| `admin@bunyan.test` | `admin` | `admin` | Admin User |
| `customer@bunyan.test` | `customer` | `customer` | Customer User |
| `contractor@bunyan.test` | `contractor` | `contractor` | Contractor User |
| `architect@bunyan.test` | `supervising_architect` | `supervising_architect` | Architect User |
| `engineer@bunyan.test` | `field_engineer` | `field_engineer` | Engineer User |

**Acceptance Criteria**: US3-AC3, US3-AC4

---

### Task 4.4 — Update DatabaseSeeder

**File**: `backend/database/seeders/DatabaseSeeder.php` (MODIFY)

**Dependencies**: Tasks 4.1, 4.2, 4.3

**Implementation Notes**:
- Replace the existing single-user `create` call with ordered seeder calls:
  ```php
  $this->call([
      RoleSeeder::class,
      PermissionSeeder::class,
      UserSeeder::class,
  ]);
  ```
- Keep `WithoutModelEvents` trait
- Remove the hardcoded `test@example.com` user creation (superseded by UserSeeder)

**Acceptance Criteria**: US3-AC1 through US3-AC4

---

## Phase 5 — Factories

**Goal**: Update `UserFactory` with new columns and role-state methods using `afterCreating()`.

---

### Task 5.1 — Update UserFactory

**File**: `backend/database/factories/UserFactory.php` (MODIFY)

**Dependencies**: Phase 2 models (Role must exist for `afterCreating`), Phase 4 seeders (RoleSeeder must run before role-state factories are used in tests)

**Implementation Notes**:

**Update `definition()` to add new columns**:
```php
public function definition(): array
{
    return [
        'name'              => fake()->name(),
        'email'             => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password'          => static::$password ??= Hash::make('password'),
        'remember_token'    => Str::random(10),
        'phone'             => fake()->optional()->e164PhoneNumber(),
        'is_active'         => true,
        'avatar'            => null,
    ];
}
```

**Add role-state methods** (all use `afterCreating`):
```php
public function admin(): static
{
    return $this->afterCreating(function (User $user) {
        $role = Role::where('name', 'admin')->firstOrFail();
        $user->roles()->attach($role->id);
    });
}
// Repeat for: customer(), contractor(), supervisingArchitect(), fieldEngineer()
```

**Add `inactive()` state**:
```php
public function inactive(): static
{
    return $this->state(fn (array $attributes) => ['is_active' => false]);
}
```

**Keep existing `unverified()` state** (unchanged).

**Import**: `App\Models\Role`

**Note**: Tests using role-state methods must call `$this->seed(RoleSeeder::class)` or inline-create the role. Document this in test file headers.

**Acceptance Criteria**: US4-AC1, US4-AC2, US4-AC3, FR-013

---

## Phase 6 — Tests

**Goal**: PHPUnit coverage for migrations, models, relationships, seeders, and factories meeting all acceptance criteria.

**Constraint**: All database tests use `RefreshDatabase` trait (NFR-008).

---

### Task 6.1 — Unit: UserModelTest

**File**: `backend/tests/Unit/Models/UserModelTest.php` (CREATE)

**Dependencies**: Task 2.1

**Test cases**:
- `fillable_contains_expected_fields()` — asserts `$fillable` contains `name`, `email`, `phone`, `is_active`, `avatar`
- `hidden_contains_password_and_remember_token()` — asserts `$hidden`
- `casts_include_is_active_as_boolean()` — asserts cast config
- `active_scope_filters_inactive_users()` — creates active + inactive users, asserts `User::active()->get()` excludes inactive
- `soft_deleted_user_excluded_from_default_query()` — creates user, soft-deletes, asserts `User::find()` returns null but `User::withTrashed()` returns record

**Acceptance Criteria**: US2-AC4, US2-AC5, FR-008, FR-009, FR-010

---

### Task 6.2 — Unit: RoleModelTest

**File**: `backend/tests/Unit/Models/RoleModelTest.php` (CREATE)

**Dependencies**: Task 2.2

**Test cases**:
- `fillable_contains_expected_fields()` — asserts `$fillable`
- `users_relationship_is_declared()` — asserts `users()` method exists and returns `BelongsToMany`
- `permissions_relationship_is_declared()` — asserts `permissions()` method exists and returns `BelongsToMany`

**Acceptance Criteria**: US2-AC1, US2-AC2

---

### Task 6.3 — Unit: PermissionModelTest

**File**: `backend/tests/Unit/Models/PermissionModelTest.php` (CREATE)

**Dependencies**: Task 2.3

**Test cases**:
- `fillable_contains_expected_fields()` — asserts `$fillable`
- `scope_by_group_filters_correctly()` — creates permissions in different groups, asserts `Permission::byGroup('users')->get()` returns only that group
- `roles_relationship_is_declared()` — asserts `roles()` returns `BelongsToMany`

**Acceptance Criteria**: US2-AC2, FR-003

---

### Task 6.4 — Feature: MigrationTest

**File**: `backend/tests/Feature/Database/MigrationTest.php` (CREATE)

**Dependencies**: All Phase 1 migrations

**Test cases**:
- `all_tables_exist_after_migration()` — asserts `Schema::hasTable()` for all 5 tables
- `users_table_has_new_columns()` — asserts `phone`, `is_active`, `avatar`, `deleted_at` columns exist
- `roles_table_has_correct_columns()` — asserts all column names
- `permissions_table_has_correct_columns()` — asserts all column names
- `role_user_pivot_has_no_id_column()` — asserts `Schema::hasColumn('role_user', 'id')` returns false
- `permission_role_pivot_has_no_id_column()` — same for `permission_role`

**Acceptance Criteria**: US1-AC1, US1-AC3, US1-AC4

---

### Task 6.5 — Feature: RelationshipTest

**File**: `backend/tests/Feature/Database/RelationshipTest.php` (CREATE)

**Dependencies**: Phase 1 (migrations), Phase 2 (models), Phase 4 (RoleSeeder for setup)

**Test cases**:
- `user_can_access_roles_relationship()` — create user, seed role, attach, assert `$user->roles` contains role (US2-AC1)
- `role_can_access_permissions_relationship()` — seed role + permission, attach, assert `$role->permissions` returns collection (US2-AC2)
- `user_has_role_method_returns_true_for_assigned_role()` — US2-AC3 (enum-based `hasRole`)
- `user_password_is_hashed()` — create user, assert password is hashed (US2-AC4)
- `soft_deleted_user_excluded_from_default_query()` — US2-AC5

**Acceptance Criteria**: US2-AC1 through US2-AC5

---

### Task 6.6 — Feature: SeederTest

**File**: `backend/tests/Feature/Database/SeederTest.php` (CREATE)

**Dependencies**: Phase 4 (all seeders)

**Test cases**:
- `role_seeder_creates_exactly_five_roles()` — run RoleSeeder, assert `Role::count() === 5` (US3-AC1)
- `roles_have_correct_arabic_display_names()` — assert each role has correct `display_name_ar`
- `permission_seeder_creates_at_least_twenty_permissions()` — run PermissionSeeder, assert `Permission::count() >= 20` (US3-AC2)
- `permissions_grouped_by_correct_domains()` — assert permissions exist for all 7 groups
- `admin_user_has_admin_role()` — run full seeder, find admin user, assert role attached (US3-AC3)
- `each_role_has_one_test_user()` — run full seeder, assert one user per role email (US3-AC4)
- `seeder_is_idempotent()` — run seeder twice, assert counts unchanged

**Acceptance Criteria**: US3-AC1 through US3-AC4

---

### Task 6.7 — Feature: FactoryTest

**File**: `backend/tests/Feature/Database/FactoryTest.php` (CREATE)

**Dependencies**: Phase 4 (RoleSeeder — must be called in setUp), Phase 5 (factories)

**Test cases**:
- `factory_creates_user_without_role()` — `User::factory()->make()` produces valid User (US4-AC3)
- `admin_state_attaches_admin_role()` — `User::factory()->admin()->create()`, assert `$user->roles->first()->name === 'admin'` (US4-AC1)
- `customer_state_attaches_customer_role()` — US4-AC1 for customer
- `contractor_state_attaches_contractor_role()` — US4-AC1 for contractor
- `supervising_architect_state_attaches_role()` — US4-AC1 for supervising_architect
- `field_engineer_state_attaches_role()` — US4-AC1 for field_engineer
- `factory_count_with_role_state()` — `User::factory()->count(10)->customer()->create()`, assert 10 users all bearing customer role (US4-AC2)
- `inactive_state_sets_is_active_false()` — assert `is_active === false`
- `unverified_state_sets_email_verified_at_null()` — assert `email_verified_at === null`

**Note**: All tests in this file call `$this->seed(RoleSeeder::class)` in `setUp()`.

**Acceptance Criteria**: US4-AC1, US4-AC2, US4-AC3, FR-013

---

## Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|----|------|-----------|--------|-----------|
| R1 | Migration naming conflict — if `2026_04_11_XXXXXX_` collides with another developer's migration | Low | High | Coordinate migration timestamps in PR; use sequential suffixes 000001–000005 |
| R2 | `#[Fillable]` attribute removal on User model breaks existing STAGE_01 tests | Medium | Medium | Run full test suite after User model update; `#[Fillable]` and `$fillable` are functionally equivalent to Eloquent |
| R3 | `UserFactory` role-state methods fail with `ModelNotFoundException` if tests don't seed roles | High | Medium | Add `$this->seed(RoleSeeder::class)` to all FactoryTest setUp; document in quickstart |
| R4 | `role_user` composite PK syntax differs across Laravel versions | Low | Low | Use `$table->primary(['role_id', 'user_id'])` — confirmed valid in Laravel 11 |

---

## Files Summary

### Files to CREATE (17)

| Phase | File |
|-------|------|
| 1 | `backend/database/migrations/2026_04_11_000001_add_profile_columns_to_users_table.php` |
| 1 | `backend/database/migrations/2026_04_11_000002_create_roles_table.php` |
| 1 | `backend/database/migrations/2026_04_11_000003_create_permissions_table.php` |
| 1 | `backend/database/migrations/2026_04_11_000004_create_role_user_table.php` |
| 1 | `backend/database/migrations/2026_04_11_000005_create_permission_role_table.php` |
| 2 | `backend/app/Models/Role.php` |
| 2 | `backend/app/Models/Permission.php` |
| 2 | `backend/app/Models/BaseModel.php` |
| 3 | `backend/app/Repositories/Contracts/RepositoryInterface.php` |
| 3 | `backend/app/Repositories/BaseRepository.php` |
| 3 | `backend/app/Repositories/UserRepository.php` |
| 4 | `backend/database/seeders/RoleSeeder.php` |
| 4 | `backend/database/seeders/PermissionSeeder.php` |
| 4 | `backend/database/seeders/UserSeeder.php` |
| 6 | `backend/tests/Unit/Models/UserModelTest.php` |
| 6 | `backend/tests/Unit/Models/RoleModelTest.php` |
| 6 | `backend/tests/Unit/Models/PermissionModelTest.php` |

> Note: FactoryTest, MigrationTest, RelationshipTest, SeederTest are 4 additional files = total **21 files created** once feature tests are included.

Wait — corrected count:

| Category | Count |
|----------|-------|
| New migration files | 5 |
| New model files | 3 |
| New repository files | 3 |
| New seeder files | 3 |
| New unit test files | 3 |
| New feature test files | 4 |
| **Total CREATE** | **21** |

### Files to MODIFY (3)

| Phase | File | Change |
|-------|------|--------|
| 2 | `backend/app/Models/User.php` | Add SoftDeletes, update fillable/hidden, add roles(), scopeActive(), scopeByRole(), is_active cast |
| 4 | `backend/database/seeders/DatabaseSeeder.php` | Replace hardcoded user creation with ordered seeder calls |
| 5 | `backend/database/factories/UserFactory.php` | Add phone/is_active/avatar to definition; add role-state methods; add inactive() state |
