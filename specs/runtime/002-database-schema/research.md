# Research — STAGE_02: Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Branch**: `spec/002-database-schema`
**Prepared**: 2026-04-11

---

## 1. Existing Codebase State (STAGE_01 Output)

### Migrations Inherited (Immutable)

| File                                                        | Purpose                                                                                                                                                                                                                                                               |
| ----------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `0001_01_01_000000_create_users_table.php`                  | Creates `users`, `password_reset_tokens`, `sessions` tables. The `users` table columns: `id`, `name`, `email` (UNIQUE + indexed), `email_verified_at`, `password`, `role` (enum), `remember_token`, `created_at`, `updated_at`. Also indexes `role` and `created_at`. |
| `0001_01_01_000001_create_cache_table.php`                  | Creates `cache` and `cache_locks` tables.                                                                                                                                                                                                                             |
| `0001_01_01_000002_create_jobs_table.php`                   | Creates `jobs`, `job_batches`, `failed_jobs` tables.                                                                                                                                                                                                                  |
| `2026_04_10_155208_create_personal_access_tokens_table.php` | Creates `personal_access_tokens` table (Sanctum).                                                                                                                                                                                                                     |

### User Model Inherited

`app/Models/User.php` currently:

- Extends `Illuminate\Foundation\Auth\User` (Authenticatable)
- Uses `HasApiTokens`, `HasFactory`, `Notifiable`
- Uses PHP 8.3 attribute-style `#[Fillable]` and `#[Hidden]` declarations
  - `$fillable`: `name`, `email`, `password`, `role`
  - `$hidden`: `password`, `remember_token`
- `$casts`: `email_verified_at → datetime`, `password → hashed`, `role → UserRole::class`
- `hasRole(UserRole)` and `hasAnyRole(UserRole ...)` methods based on the `role` enum column
- **Does NOT yet have**: `SoftDeletes`, `phone`, `is_active`, `avatar` in fillable, `roles()` relationship, `scopeActive()`, `scopeByRole()`

### UserFactory Inherited

`database/factories/UserFactory.php` currently:

- Default state: `name`, `email`, `email_verified_at`, `password`, `remember_token`
- `unverified()` state method exists
- **Does NOT yet have**: `phone`, `is_active`, `avatar` in definition; no role-state methods; no `inactive()` state

### DatabaseSeeder Inherited

Creates a single `test@example.com` user. No role seeding. No permission seeding.

### Enums Inherited

`app/Enums/UserRole.php` — PHP enum with cases: `customer`, `contractor`, `supervising_architect`, `field_engineer`, `admin`. This is cast on the `role` column in the `users` table.

---

## 2. Migration Sequence Strategy

### Why ADD COLUMN Is Required

The `users` table was created in STAGE_01. The forward-only migration policy (FR-014) prohibits modifying existing migration files. Therefore, new columns (`phone`, `is_active`, `avatar`, `deleted_at`) are added via a new `ALTER TABLE` migration.

Laravel's `Schema::table()` with `$table->string(...)` and `$table->softDeletes()` issues `ALTER TABLE ADD COLUMN` statements in MySQL, which is safe on MySQL 8.x and supports concurrent reads during the operation for tables under typical development/staging sizes.

### Migration Execution Order

MySQL resolves foreign key dependencies. The 5 new migrations must execute in this strict order:

```
STAGE_01 migrations  (already run — immutable)
 └─ 0001_01_01_000000_create_users_table.php        → users table exists

STAGE_02 migrations (new — MUST follow this sequence)
 └─ 2026_04_11_000001_add_profile_columns_to_users_table.php   (ALTER users)
 └─ 2026_04_11_000002_create_roles_table.php                   (no FKs)
 └─ 2026_04_11_000003_create_permissions_table.php             (no FKs)
 └─ 2026_04_11_000004_create_role_user_table.php               (FK → users, roles)
 └─ 2026_04_11_000005_create_permission_role_table.php         (FK → permissions, roles)
```

**Why this order matters**: `role_user` has FKs to both `users` and `roles`. Both parent tables must exist before the pivot is created. Same for `permission_role`. The 4-digit suffix (`000001`–`000005`) guarantees Laravel's alphabetical migration sorting places them after all STAGE_01 files.

### Rollback Reversal Order

When `php artisan migrate:rollback` is executed, Laravel reverses the batch:

```
DROP permission_role  → DROP role_user  → DROP permissions  → DROP roles  → ALTER users (drop columns)
```

This is the safe FK-respecting reverse order.

---

## 3. Role Enum Coexistence Strategy

### Two Parallel Role Systems

After STAGE_02, the platform temporarily runs two parallel role representations:

| System      | Column/Table                                         | Purpose                                                                     |
| ----------- | ---------------------------------------------------- | --------------------------------------------------------------------------- |
| Enum column | `users.role` (STAGE_01)                              | Fast, denormalised role lookup; used in STAGE_03 auth guards and JWT claims |
| RBAC tables | `roles` + `role_user` + `permission_role` (STAGE_02) | Full granular permission system used from STAGE_04 onward                   |

### Why Both Are Needed

- **STAGE_03 (Authentication)**: Auth guards and middleware need a fast role check without a JOIN. The `role` enum column provides an O(1) indexed lookup.
- **STAGE_04 (RBAC System)**: Gate definitions and Policy resolution need fine-grained permissions. The `roles`/`permissions` tables support the full RBAC model.
- **Long-term**: Once STAGE_04 is fully implemented, the denormalised `role` column may be deprecated — but this is out of scope for STAGE_02 and must be addressed by an explicit future ADR.

### No Conflict Risk

The `role` enum column is **not removed** in STAGE_02. The `roles` table introduces a new slug-based `name` column that exactly mirrors the `UserRole` enum cases (`admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer`). Seeders sync both systems: `UserSeeder` sets both `users.role` (via `User::firstOrCreate`) and attaches the matching `roles` record via `$user->roles()->sync()`.

**NFR-002 scope clarification**: The no-ENUM rule in NFR-002 applies exclusively to NEW tables created in STAGE_02. The existing `users.role` enum is exempt.

---

## 4. Repository Pattern Rationale

### Why Abstract BaseRepository + Interface?

**Problem with Eloquent in controllers**: Direct Eloquent calls in controllers couple HTTP logic to persistence logic, prevent mocking in tests, and scatter query logic across the codebase.

**Problem with Eloquent directly in services**: Services become untestable without a real database, and changing the ORM or adding caching requires touching business logic.

**Solution**: The Repository pattern provides a clean separation:

```
Controller → Service → Repository → Eloquent Model → MySQL
```

Benefits for Bunyan:

1. **Testability**: Services receive `RepositoryInterface` via constructor injection — mocked in unit tests without database.
2. **Consistency**: All CRUD operations go through one interface — `find`, `findAll`, `findBy`, `create`, `update`, `delete`, `paginate`.
3. **Extensibility**: `UserRepository` adds domain-specific finders (`findByEmail`, `findActiveUsers`) without polluting the generic interface.
4. **Future caching**: A caching decorator can be wrapped around any repository without changing service code.

### Why Concrete `BaseRepository` Over Pure Interface?

All 15+ future repositories (ProjectRepository, PhaseRepository, etc.) need the same 7 basic CRUD methods. An abstract `BaseRepository` implementing `RepositoryInterface` provides those methods once via `$this->model` injection — child repositories inherit them for free and only add domain-specific methods.

### Why Not Repository Generator Packages?

External RBAC packages (Spatie, Laratrust) ship their own repository/model patterns that conflict with Bunyan's layered architecture. Bunyan uses its own minimal implementation to retain full control over the query layer.

---

## 5. Factory State Patterns (Laravel 11)

### `afterCreating()` vs `state()` for Role Attachment

**`state()` method**: Returns an array of attribute overrides. Appropriate for attribute-level changes (e.g., `email_verified_at = null`).

**`afterCreating()` callback**: Executes code AFTER the model has been persisted to the database. Required for relationship operations (e.g., `$user->roles()->attach(...)`) because the model needs a populated `id` before pivot records can be inserted.

**Conclusion**: Role-state methods MUST use `afterCreating()`. The `state()` approach cannot attach pivot records.

### Implementation Pattern

```php
public function admin(): static
{
    return $this->afterCreating(function (User $user) {
        $role = Role::where('name', 'admin')->firstOrFail();
        $user->roles()->attach($role->id);
    });
}
```

### Dependency Requirement

Role-state factory methods depend on the `roles` table being seeded. In PHPUnit tests, either:

- Call `$this->seed(RoleSeeder::class)` before using role-state factories, OR
- Create the role inline in the test setup

This is documented in `quickstart.md` and test file headers.

### Available State Methods

| Method                   | Role Slug               | Notes                                                                   |
| ------------------------ | ----------------------- | ----------------------------------------------------------------------- |
| `admin()`                | `admin`                 |                                                                         |
| `customer()`             | `customer`              |                                                                         |
| `contractor()`           | `contractor`            |                                                                         |
| `supervisingArchitect()` | `supervising_architect` | camelCase per Laravel convention                                        |
| `fieldEngineer()`        | `field_engineer`        | camelCase per Laravel convention                                        |
| `unverified()`           | —                       | sets `email_verified_at = null` (inherited from STAGE_01 factory, kept) |
| `inactive()`             | —                       | sets `is_active = false`                                                |

---

## 6. User Model Update: Attribute Syntax Migration

The existing `User.php` uses PHP 8.3 attribute-style declarations (`#[Fillable]`, `#[Hidden]`). STAGE_02 must add `phone`, `is_active`, `avatar` to fillable.

**Decision**: Replace attribute-style declarations with traditional `$fillable` and `$hidden` array properties to allow SoftDeletes and additional relationship methods to coexist cleanly. The PHP 8.3 attribute syntax for Eloquent is newer and less universally supported by tooling. This is a within-file refactor of the existing User model — not a new feature, but necessary for correctness.

---

## 7. Unknown Resolution

All unknowns from the clarification step have been resolved:

| Question                                                                       | Resolution                                                                                                       |
| ------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| Should `role_user` composite PK use `$table->primary(['role_id', 'user_id'])`? | **Yes** — explicit composite PK per FR-015. No auto-increment on pivot tables.                                   |
| Should `UserSeeder` also set `users.role` enum column?                         | **Yes** — `User::firstOrCreate` payload includes `role` to keep both systems in sync.                            |
| Does `BaseModel` use `$guarded = []` or `$fillable`?                           | `$guarded = []` on BaseModel as default (as per spec), but child models MUST declare explicit `$fillable`.       |
| Should `UserRepository` be bound in a Service Provider?                        | **Yes** — bind `RepositoryInterface` implementations in `AppServiceProvider` for DI. Documented in plan Phase 3. |
| Are `roles` and `permissions` tables needing soft deletes?                     | **No** — NFR-007 explicitly excludes them. They are configuration data.                                          |
