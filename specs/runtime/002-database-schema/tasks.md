# Tasks: DATABASE_SCHEMA — Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Phase**: 01_PLATFORM_FOUNDATION
**Feature**: `specs/runtime/002-database-schema/`
**Generated**: 2026-04-11
**Spec**: `specs/runtime/002-database-schema/spec.md`
**Plan**: `specs/runtime/002-database-schema/plan.md`

---

## Summary

| Metric                | Value                               |
| --------------------- | ----------------------------------- |
| Total tasks           | 32                                  |
| Parallel tasks [P]    | 17                                  |
| Phases                | 7 (Phase 0 – Phase 6) + 3 gap-fill |
| Files to create       | 24                                  |
| Files to modify       | 4 (User.php, UserFactory.php, DatabaseSeeder.php, AppServiceProvider.php) |
| User stories covered  | US1 (P1), US2 (P1), US3 (P2), US4 (P2) |

---

## Dependency Graph

```
Phase 0 (Verification) ─sequential─
    └─ Phase 1 (Migrations) [US1]
           └─ Phase 2 (Models) [US2]
                   ├─ Phase 3 (Repositories)
                   └─ Phase 4 (Seeders) [US3]
                           └─ Phase 5 (Factory) [US4]
                                   └─ Phase 6 (Tests) [US1–US4]
```

---

## Phase 0: Pre-Implementation Verification

**Purpose**: Confirm STAGE_01 output is clean and the environment is ready before any schema modifications.

**⚠️ GATE**: ALL 4 verification tasks MUST pass before Phase 1 begins. STOP and resolve any failures before proceeding.

- [ ] T001 Verify STAGE_01 users migration exists and contains columns: id, name, email, email_verified_at, password, role (enum), remember_token, created_at, updated_at — inspect `backend/database/migrations/0001_01_01_000000_create_users_table.php` (read-only)
- [ ] T002 Verify `backend/app/Enums/UserRole.php` exists and defines all 5 cases: `admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer`
- [ ] T003 Verify `backend/app/Models/User.php` has `HasApiTokens`, `HasFactory`, `Notifiable` traits and casts `role` to `UserRole::class`, plus existing `hasRole(UserRole)` and `hasAnyRole(UserRole ...)` methods
- [ ] T004 Verify no STAGE_02 migration files already exist matching pattern `2026_04_11_00000*` in `backend/database/migrations/`

---

## Phase 1: Migrations (User Story 1 — Schema Establishment)

**Goal**: Create 5 forward-only migrations that alter the `users` table and establish the 4 new RBAC tables.

**Constraint**: All files use `2026_04_11_XXXXXX_` date prefix with sequential 6-digit suffix (`000001`–`000005`) to guarantee execution after all STAGE_01 migrations. Every migration MUST include a `down()` rollback method. All tables: charset `utf8mb4`, collation `utf8mb4_unicode_ci`. No ENUM types on new tables (NFR-002).

**Independent Test**: `php artisan migrate:fresh` → all 5 tables exist with zero errors (US1-AC1). `php artisan migrate:rollback --step=5` → all 5 drop cleanly (US1-AC2).

- [ ] T005 [US1] Create ALTER TABLE migration adding `phone` (string 30, nullable), `is_active` (boolean, default true), `avatar` (string 500, nullable), `deleted_at` (softDeletes timestamp) to `users` table; add `INDEX(is_active)`, `INDEX(deleted_at)` in `up()`; reverse with `dropColumn` and `dropIndex` in `down()` — `backend/database/migrations/2026_04_11_000001_add_profile_columns_to_users_table.php`
- [ ] T006 [P] [US1] Create migration to CREATE `roles` table with columns: `id()`, `string('name', 100)->unique()`, `string('display_name', 150)`, `string('display_name_ar', 150)`, `text('description')->nullable()`, `timestamps()`; `down()` uses `Schema::dropIfExists('roles')` — `backend/database/migrations/2026_04_11_000002_create_roles_table.php`
- [ ] T007 [P] [US1] Create migration to CREATE `permissions` table with columns: `id()`, `string('name', 150)->unique()`, `string('display_name', 200)`, `string('group', 100)`, `text('description')->nullable()`, `timestamps()`; add `$table->index('group')`; `down()` uses `Schema::dropIfExists('permissions')` — `backend/database/migrations/2026_04_11_000003_create_permissions_table.php`
- [ ] T008 [US1] Create migration to CREATE `role_user` pivot table with `foreignId('role_id')->constrained()->cascadeOnDelete()`, `foreignId('user_id')->constrained()->cascadeOnDelete()`, `timestamps()`; composite PK `$table->primary(['role_id', 'user_id'])` (NO `$table->id()`), explicit `$table->index('user_id')`; `down()` uses `Schema::dropIfExists('role_user')` — `backend/database/migrations/2026_04_11_000004_create_role_user_table.php`
- [ ] T009 [US1] Create migration to CREATE `permission_role` pivot table with `foreignId('permission_id')->constrained()->cascadeOnDelete()`, `foreignId('role_id')->constrained()->cascadeOnDelete()`, `timestamps()`; composite PK `$table->primary(['permission_id', 'role_id'])` (NO `$table->id()`), explicit `$table->index('role_id')`; `down()` uses `Schema::dropIfExists('permission_role')` — `backend/database/migrations/2026_04_11_000005_create_permission_role_table.php`

**Checkpoint**: `php artisan migrate` → 5 new migrations green. `php artisan migrate:rollback --step=5` → 5 drop in FK-safe reverse order without errors.

---

## Phase 2: Eloquent Models (User Story 2 — Typed Relationships)

**Goal**: Create `BaseModel`, `Role`, `Permission` and update `User` with typed relationships, casts, scopes, and SoftDeletes. No business logic in any model.

**Independent Test**: In tinker, create a user, attach a role and permission; assert `$user->roles`, `$role->permissions`, and `$user->roles()->first()->permissions` resolve correctly.

- [ ] T010 [US2] Create abstract `BaseModel` extending `Illuminate\Database\Eloquent\Model` with `use SoftDeletes, HasFactory`, `protected $guarded = []`, `protected $dateFormat = 'Y-m-d H:i:s'`, and `scopeActive(Builder $query): Builder` returning `$query->where('is_active', true)`; docblock notes User cannot extend BaseModel due to Authenticatable — `backend/app/Models/BaseModel.php`
- [ ] T011 [P] [US2] Create `Role` model extending `BaseModel` with `$fillable = ['name', 'display_name', 'display_name_ar', 'description']`, `users()` as `belongsToMany(User::class, 'role_user')->withTimestamps()`, `permissions()` as `belongsToMany(Permission::class, 'permission_role')->withTimestamps()`; no SoftDeletes — `backend/app/Models/Role.php`
- [ ] T012 [P] [US2] Create `Permission` model extending `BaseModel` with `$fillable = ['name', 'display_name', 'group', 'description']`, `roles()` as `belongsToMany(Role::class, 'permission_role')->withTimestamps()`, `scopeByGroup(Builder $query, string $group): Builder` returning `$query->where('group', $group)`; no SoftDeletes — `backend/app/Models/Permission.php`
- [ ] T013 [P] [US2] Update `User` model: replace `#[Fillable]` attribute with `$fillable` array adding `phone`, `is_active`, `avatar`; replace `#[Hidden]` with `$hidden` array; add `use SoftDeletes`; add `'is_active' => 'boolean'` to `$casts`; add `roles()` as `belongsToMany(Role::class, 'role_user')->withTimestamps()`; add `scopeActive(Builder $query): Builder`; add `scopeByRole(Builder $query, string $role): Builder`; rename existing `hasRole(UserRole $role)` → `hasEnumRole(UserRole $role)` (preserve logic); add new `hasRole(string $roleSlug): bool` checking `roles` relationship; preserve all existing traits and methods — `backend/app/Models/User.php`

**Checkpoint**: `php artisan tinker` — `new App\Models\User` → no fatal errors; `App\Models\Role::query()->getModel()` → Role instance.

---

## Phase 3: Repository Pattern

**Goal**: Define the `RepositoryInterface` contract and provide the abstract `BaseRepository` implementation with `UserRepository` and its service-provider binding.

- [ ] T014 Create `RepositoryInterface` declaring method signatures: `find(int $id): ?Model`, `findAll(): Collection`, `findBy(array $criteria): Collection`, `create(array $data): Model`, `update(int $id, array $data): Model`, `delete(int $id): bool`, `paginate(int $perPage = 15): LengthAwarePaginator` — `backend/app/Repositories/Contracts/RepositoryInterface.php`
- [ ] T015 Create abstract `BaseRepository` implementing `RepositoryInterface` with constructor `__construct(protected Model $model)`; concrete implementations of all 7 interface methods; `update()` and `delete()` throw `ModelNotFoundException` when record not found; `findBy()` accepts only pre-validated array (no raw HTTP input forwarding) — `backend/app/Repositories/BaseRepository.php`
- [ ] T016 Create `UserRepository` extending `BaseRepository` with constructor `__construct(User $model) { parent::__construct($model); }`; add `findByEmail(string $email): ?User` using `$this->model->where('email', $email)->first()`; add `findActiveUsers(): Collection` using `$this->model->active()->get()` — `backend/app/Repositories/UserRepository.php`
- [ ] T017 Update `AppServiceProvider::register()` to bind `UserRepository::class` with `fn () => new UserRepository(new User())`; add required `use` statements for `UserRepository` and `User` — `backend/app/Providers/AppServiceProvider.php`

**Checkpoint**: `php artisan tinker` — `app(App\Repositories\UserRepository::class)->findAll()` returns `Illuminate\Database\Eloquent\Collection`.

---

## Phase 4: Database Seeders (User Story 3 — Predictable Test Data)

**Goal**: Populate all 5 platform roles, 25+ permissions grouped by domain, and 5 test users (one per role) with idempotent `updateOrCreate` / `firstOrCreate` patterns.

**Independent Test**: `php artisan db:seed` → `Role::count() === 5`, `Permission::count() >= 25`, 5 test users each with exactly one role. Run twice → same counts, no errors.

- [ ] T018 [P] [US3] Create `RoleSeeder` using `Role::updateOrCreate(['name' => $slug], $data)` to seed exactly 5 roles: `admin` (Administrator / الإدارة), `customer` (Customer / العميل), `contractor` (Contractor / المقاول), `supervising_architect` (Supervising Architect / المهندس المشرف), `field_engineer` (Field Engineer / المهندس الميداني) — `backend/database/seeders/RoleSeeder.php`
- [ ] T019 [P] [US3] Create `PermissionSeeder` using `Permission::updateOrCreate(['name' => $slug], $data)` to seed 25+ permissions across 7 groups: `users` (5), `projects` (5), `reports` (4), `transactions` (3), `products` (4), `orders` (3), `settings` (2); each permission has `name` (dot-notation slug), `display_name`, and `group` — `backend/database/seeders/PermissionSeeder.php`
- [ ] T020 [US3] Create `UserSeeder` using `User::firstOrCreate(['email' => $email], $data)` for 5 test users; for each user set both the `role` enum column and attach the corresponding `roles` record via `$user->roles()->syncWithoutDetaching([...])` — passwords via `bcrypt('password')` — guard with `app()->isProduction()` check — `backend/database/seeders/UserSeeder.php`
- [ ] T021 [US3] Update `DatabaseSeeder` to replace hardcoded `test@example.com` user creation with ordered `$this->call([RoleSeeder::class, PermissionSeeder::class, UserSeeder::class])` inside `run()` method; keep `WithoutModelEvents` trait — `backend/database/seeders/DatabaseSeeder.php`

**Checkpoint**: `php artisan db:seed` twice consecutively → no exceptions, `Role::count()` still 5, no duplicate rows.

---

## Phase 5: Factory (User Story 4 — Role-State Factories)

**Goal**: Extend `UserFactory` with the new column definitions and 5 role-state methods using `afterCreating()` for pivot attachment.

**Independent Test**: Seed `RoleSeeder` first, then `User::factory()->admin()->create()` → `$user->roles->first()->name === 'admin'`.

- [ ] T022 [US4] Update `UserFactory::definition()` to include `phone` (optional E.164), `is_active` (true), `avatar` (null); add role-state methods `admin()`, `customer()`, `contractor()`, `supervisingArchitect()`, `fieldEngineer()` each using `afterCreating()` to attach the corresponding `Role` record via `$user->roles()->attach()`; add `inactive()` state setting `is_active = false`; keep existing `unverified()` state; add `use App\Models\Role` import — `backend/database/factories/UserFactory.php`

**Checkpoint**: In a test with `$this->seed(RoleSeeder::class)`, `User::factory()->count(10)->customer()->create()` → 10 users, all with `customer` role.

---

## Phase 6: Tests

**Purpose**: PHPUnit coverage for models, migrations, repositories, and seeders. All tests touching the database MUST use `RefreshDatabase`. PHPUnit `#[Test]` attribute preferred over `test` prefix.

- [ ] T023 [P] [US2] Create `UserModelTest` with tests: `SoftDeletes` trait present on User; `scopeActive()` filters out `is_active=false` users; `hasRole(string $slug)` returns true for attached role and false otherwise; `hasEnumRole(UserRole)` returns correct bool via enum cast; `$fillable` includes `phone`, `is_active`, `avatar` — `backend/tests/Unit/Models/UserModelTest.php`
- [ ] T024 [P] [US2] Create `RoleModelTest` with tests: `$fillable` defined on Role; `users()` method exists and returns `BelongsToMany` instance; `permissions()` method exists and returns `BelongsToMany` instance — `backend/tests/Unit/Models/RoleModelTest.php`
- [ ] T025 [P] [US2] Create `PermissionModelTest` with tests: `$fillable` defined on Permission; `roles()` method exists and returns `BelongsToMany` instance — `backend/tests/Unit/Models/PermissionModelTest.php`
- [ ] T026 [P] Create `UserRepositoryTest` using `RefreshDatabase` with tests: `all()` returns all users; `find(id)` returns correct user; `findBy(['email' => ...])` returns matching user; `findByEmail(email)` returns correct user or null; `create(data)` persists user to database; `update(id, data)` modifies user attributes; `delete(id)` soft-deletes user (excluded from default query); `findActiveUsers()` excludes `is_active=false` users — `backend/tests/Feature/Repositories/UserRepositoryTest.php`
- [ ] T027 [P] [US1] Create `UsersMigrationTest` using `RefreshDatabase` with tests: `phone` column exists on `users` table; `is_active` column exists with default value 1 (true); `deleted_at` column exists on `users` table — `backend/tests/Feature/Migrations/UsersMigrationTest.php`
- [ ] T028 [P] [US1] Create `RolesMigrationTest` using `RefreshDatabase` with tests: `roles` table exists with columns `id`, `name`, `display_name`, `display_name_ar`, `description`; `permissions` table exists with columns `id`, `name`, `display_name`, `group`, `description`; `role_user` pivot table exists; `permission_role` pivot table exists; `role_user` has no `id` column (composite PK only); `permission_role` has no `id` column (composite PK only) — `backend/tests/Feature/Migrations/RolesMigrationTest.php`
- [ ] T029 [P] [US3] Create `RoleSeederTest` using `RefreshDatabase` with tests: running `RoleSeeder` creates exactly 5 roles with correct slugs (`admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer`); each role has the correct Arabic `display_name_ar` value; seeder is idempotent (run twice → `Role::count()` still equals 5, no exceptions) — `backend/tests/Feature/Seeders/RoleSeederTest.php`

**Checkpoint**: `composer run test` → all PHPUnit tests pass, zero failures, zero skips.

---

## Dependency Reference

```
T001 → T002 → T003 → T004          (sequential verification gate)
T004 → T005                         (verified clean state before ALTER)
T005 → T008                         (role_user FK needs altered users table)
T006 → T008, T009                   (role_user + permission_role need roles)
T007 → T009                         (permission_role needs permissions)
T005, T006 → T008                   (role_user depends on both)
T006, T007 → T009                   (permission_role depends on both)
T009 → T010                         (BaseModel after all migrations set)
T010 → T011, T012, T013             (child models after BaseModel)
T010, T011, T012, T013 → T014      (interfaces after models exist)
T014 → T015                         (BaseRepository after interface)
T015, T013 → T016                   (UserRepository after Base + User model)
T016 → T017                         (ServiceProvider after UserRepository)
T011, T012 → T018, T019            (seeders after Role + Permission models)
T013 → T020                         (UserSeeder after User model update)
T018 → T020                         (UserSeeder needs seeded roles)
T018, T019, T020 → T021             (DatabaseSeeder after all seeders)
T013, T018 → T022                   (Factory after User model + roles table)
T010–T013 → T023, T024, T025       (model unit tests after models)
T014–T017 → T026                    (repository test after repositories)
T005–T009 → T027, T028              (migration tests after migrations)
T018–T021 → T029                    (seeder test after seeders)
```

---

## Parallel Execution Waves

| Wave | Parallel Tasks                        | Gate (must complete first)             |
| ---- | ------------------------------------- | -------------------------------------- |
| 1    | T001                                  | —                                      |
| 2    | T002                                  | T001                                   |
| 3    | T003                                  | T002                                   |
| 4    | T004                                  | T003                                   |
| 5    | T005                                  | T004 (verification gate cleared)       |
| 6    | **T006, T007**                        | T005 (roles + permissions independent) |
| 7    | **T008, T009**                        | T006, T007                             |
| 8    | T010                                  | T008, T009                             |
| 9    | **T011, T012, T013**                  | T010 (BaseModel exists)                |
| 10   | T014                                  | T011, T012, T013                       |
| 11   | T015                                  | T014                                   |
| 12   | T016                                  | T015, T013                             |
| 13   | T017                                  | T016                                   |
| 14   | **T018, T019**                        | T011, T012 (models exist)              |
| 15   | T020                                  | T018 (roles seeded)                    |
| 16   | T021                                  | T018, T019, T020                       |
| 17   | T022                                  | T013, T018                             |
| 18   | **T023, T024, T025, T026, T027, T028, T029** | All implementation complete   |

---

## Acceptance Gate

All tasks are complete when:

- [ ] `php artisan migrate:fresh` runs with zero errors producing all 5 tables (CHK084)
- [ ] `php artisan migrate:rollback --step=5` runs with zero errors dropping STAGE-02 tables in FK-safe order (CHK085)
- [ ] `php artisan db:seed` produces exactly 5 roles, ≥25 permissions, 5 test users (CHK086)
- [ ] `composer run lint` passes with zero PSR-12 violations (CHK087)
- [ ] `composer run test` passes with all PHPUnit unit and feature tests green (CHK088)

---

## Added Tasks — Coverage Gap Resolution

The following tasks close the HIGH and MEDIUM coverage gaps identified post-generation.

- [ ] T030 [P] [US2] Create `RelationshipTest` using `RefreshDatabase` plus `seed(RoleSeeder::class, PermissionSeeder::class)` with tests: `$user->roles()` returns roles attached via pivot; attach a Permission to a Role then `$role->permissions` contains it; `$user->roles()->first()->permissions()` returns the expected collection (three-table traversal); detaching a role removes it from `$user->roles`; cascade delete — deleting a Role removes the `role_user` row (US2-AC1–AC5) — `backend/tests/Feature/Database/RelationshipTest.php`
- [ ] T031 [P] [US3] Create `SeederTest` using `RefreshDatabase` with tests: `PermissionSeeder` creates ≥25 permissions; each permission has a non-empty `group`; `UserSeeder` creates 5 test users with correct emails; each test user has exactly one role attached via pivot; running full `DatabaseSeeder` twice is idempotent (`Role::count() === 5`, no exceptions) — `backend/tests/Feature/Database/SeederTest.php`
- [ ] T032 [P] [US4] Create `FactoryTest` using `RefreshDatabase` plus `seed(RoleSeeder::class)` with tests: `User::factory()->admin()->create()` attaches admin role; `User::factory()->customer()->create()` attaches customer role; `User::factory()->contractor()->create()` attaches contractor role; `User::factory()->supervisingArchitect()->create()` attaches supervising_architect role; `User::factory()->fieldEngineer()->create()` attaches field_engineer role; `User::factory()->inactive()->create()` sets `is_active = false`; `User::factory()->count(5)->customer()->create()` creates 5 users all with customer role — `backend/tests/Feature/Database/FactoryTest.php`

**Updated dependency:** T030 requires T011–T013 (models) + T018–T019 (seeders); T031 requires T018–T021 (all seeders); T032 requires T022 (factory) + T018 (RoleSeeder).



The following test files were specified in `plan.md` but are **not** included in this task list per the user-defined Phase 6 scope. They may need to be added in a follow-up:

| Missing Test File | Plan Reference | Coverage |
| --- | --- | --- |
| `backend/tests/Feature/Database/RelationshipTest.php` | Plan T6.5 | US2-AC1 through AC5 (functional DB relationship verification) |
| `backend/tests/Feature/Database/SeederTest.php` | Plan T6.6 | US3 full suite (PermissionSeeder, UserSeeder, full counts) |
| `backend/tests/Feature/Database/FactoryTest.php` | Plan T6.7 | US4-AC1 through AC3 (all 5 role-state factory methods + inactive state) |

> **Risk**: US4 (`UserFactory` role states) and US3 full seeder suite have zero PHPUnit coverage without these files. Consider adding them as T030–T032 before marking the stage HARDENED.

---

## Splits from plan.md

| Split | Reason |
| --- | --- |
| Plan T6.4 `MigrationTest.php` → Tasks T027 + T028 | Atomic constraint: users-table column tests and RBAC-table existence tests are logically independent and belong in separate files for granular failure isolation |
| Plan T6.6 `SeederTest.php` (7 tests) → Task T029 `RoleSeederTest.php` (3 tests) | Partial coverage only; full seeder coverage deferred — see Coverage Gap Notice above |
