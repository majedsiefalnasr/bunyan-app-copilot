# Requirements Checklist — STAGE_02 Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Phase**: 01_PLATFORM_FOUNDATION
**Spec**: `specs/runtime/002-database-schema/spec.md`
**Generated**: 2026-04-11
**Updated**: 2026-04-11 (Enhanced — Migration Strategy + Clarifications pass)

---

## Table Completeness

- [ ] CHK001 — `users` table specified with all columns
- [ ] CHK002 — `roles` table specified with all columns
- [ ] CHK003 — `permissions` table specified with all columns
- [ ] CHK004 — `role_user` pivot table specified
- [ ] CHK005 — `permission_role` pivot table specified

---

## Column Definitions

- [ ] CHK006 — All columns have data types defined (`bigint UNSIGNED`, `varchar`, `tinyint`, `text`, `timestamp`)
- [ ] CHK007 — Nullable / NOT NULL constraints specified for every column
- [ ] CHK008 — Default values specified where applicable (`is_active` default `1`)
- [ ] CHK009 — `users.email` defined as UNIQUE
- [ ] CHK010 — `users.password` defined as `varchar(255)` (stores hash)
- [ ] CHK011 — `users.is_active` defined with boolean cast
- [ ] CHK012 — `users.deleted_at` column present for SoftDeletes
- [ ] CHK013 — `roles.name` defined as UNIQUE slug column
- [ ] CHK014 — `roles.display_name_ar` Arabic column defined
- [ ] CHK015 — `permissions.name` defined as UNIQUE dot-notation slug
- [ ] CHK016 — `permissions.group` defined for domain grouping

---

## Foreign Key Relationships

- [ ] CHK017 — `role_user.role_id` → `roles.id` ON DELETE CASCADE declared
- [ ] CHK018 — `role_user.user_id` → `users.id` ON DELETE CASCADE declared
- [ ] CHK019 — `permission_role.permission_id` → `permissions.id` ON DELETE CASCADE declared
- [ ] CHK020 — `permission_role.role_id` → `roles.id` ON DELETE CASCADE declared

---

## Indexes

- [ ] CHK021 — `UNIQUE(users.email)` index declared
- [ ] CHK022 — `INDEX(users.is_active)` declared
- [ ] CHK023 — `INDEX(users.deleted_at)` declared
- [ ] CHK024 — `UNIQUE(roles.name)` declared
- [ ] CHK025 — `UNIQUE(permissions.name)` declared
- [ ] CHK026 — `INDEX(permissions.group)` declared
- [ ] CHK027 — Composite primary key `(role_id, user_id)` on `role_user` declared
- [ ] CHK028 — Composite primary key `(permission_id, role_id)` on `permission_role` declared
- [ ] CHK029 — Index on `role_user.user_id` for reverse lookups declared
- [ ] CHK030 — Index on `permission_role.role_id` for reverse lookups declared

---

## Eloquent Models

- [ ] CHK031 — `User` model — `$fillable` list defined
- [ ] CHK032 — `User` model — `$hidden` includes `password` and `remember_token`
- [ ] CHK033 — `User` model — `$casts` includes `email_verified_at`, `password`, `is_active`
- [ ] CHK034 — `User` model — `roles()` relationship defined (`belongsToMany`)
- [ ] CHK035 — `User` model — `scopeActive()` defined
- [ ] CHK036 — `User` model — `hasRole()` helper defined
- [ ] CHK037 — `User` model — `SoftDeletes` trait specified
- [ ] CHK038 — `Role` model — `$fillable` list defined
- [ ] CHK039 — `Role` model — `users()` relationship defined
- [ ] CHK040 — `Role` model — `permissions()` relationship defined
- [ ] CHK041 — `Permission` model — `$fillable` list defined
- [ ] CHK042 — `Permission` model — `roles()` relationship defined
- [ ] CHK043 — `Permission` model — `scopeByGroup()` defined
- [ ] CHK044 — `BaseModel` class defined with shared traits and conventions
- [ ] CHK045 — All models contain ONLY relationships, scopes, casts, `$fillable`, `$hidden` (no business logic)

---

## Repository Pattern

- [ ] CHK046 — `RepositoryInterface` defined with all required method signatures
- [ ] CHK047 — `BaseRepository` abstract class defined implementing `RepositoryInterface`
- [ ] CHK048 — `UserRepository` extends `BaseRepository` with `findByEmail` and `findActiveUsers`
- [ ] CHK049 — Repository constructor injects Eloquent model (not hardcoded)

---

## Seeders

- [ ] CHK050 — `RoleSeeder` seeds exactly 5 roles
- [ ] CHK051 — All 5 role slugs defined: `admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer`
- [ ] CHK052 — All 5 Arabic `display_name_ar` values defined: الإدارة, العميل, المقاول, المهندس المشرف, المهندس الميداني
- [ ] CHK053 — `PermissionSeeder` seeds minimum 20 permissions
- [ ] CHK054 — Permissions grouped into at least 7 domain groups
- [ ] CHK055 — `UserSeeder` creates one test user per role (5 users total)
- [ ] CHK056 — All seeded users use idempotent `firstOrCreate` / `updateOrCreate` pattern
- [ ] CHK057 — `DatabaseSeeder` calls seeders in dependency order (Roles → Permissions → Users)

---

## Security

- [ ] CHK058 — No plaintext passwords in seeders — `bcrypt('password')` or Laravel hashed cast used for all seeded user passwords
- [ ] CHK059 — Test user credentials (`@bunyan.test` emails) are local/CI only — seeder MUST NOT run in production environments (guarded by `app()->isProduction()` check or env gate)
- [ ] CHK060 — `password` is in `$hidden` on the `User` model — spec explicitly lists it as hidden from serialization
- [ ] CHK061 — `remember_token` is in `$hidden` on the `User` model — spec explicitly lists it as hidden from serialization
- [ ] CHK062 — No raw SQL or `DB::statement` — all queries use Eloquent query builder or model methods only
- [ ] CHK063 — CASCADE deletes on pivot tables (`role_user`, `permission_role`) are scoped to join rows only — they do NOT cascade into `users`, `roles`, or `permissions` themselves; no unauthorized data exposure via cascade
- [ ] CHK064 — `is_active` flag is defined and castable to boolean; spec notes enforcement is deferred to RBAC stage but column MUST be present and spec references downstream guard chain

---

## Database Integrity

- [ ] CHK065 — All FK columns (`role_user.role_id`, `role_user.user_id`, `permission_role.permission_id`, `permission_role.role_id`) are indexed
- [ ] CHK066 — Composite PKs on both pivot tables: `(role_id, user_id)` on `role_user`; `(permission_id, role_id)` on `permission_role` — no auto-increment on pivots
- [ ] CHK067 — UNIQUE constraint on `users.email` explicitly declared in migration and spec index table
- [ ] CHK068 — UNIQUE constraint on `roles.name` explicitly declared in migration and spec index table
- [ ] CHK069 — UNIQUE constraint on `permissions.name` explicitly declared in migration and spec index table
- [ ] CHK070 — utf8mb4 charset and utf8mb4_unicode_ci collation specified for all text columns (NFR-001 requirement quantified)
- [ ] CHK071 — All 5 STAGE_02 migrations have complete `down()` rollback methods (FR-006)
- [ ] CHK072 — STAGE_01 migration `0001_01_01_000000_create_users_table.php` is NOT modified — STAGE_02 uses a new `add_profile_columns_to_users_table` ALTER migration instead

---

## Model / Architecture

- [ ] CHK073 — No business logic in any model — models contain only relationships, local scopes, casts, `$fillable`, `$hidden` (NFR-006 quantified)
- [ ] CHK074 — `User` model uses `SoftDeletes` trait (NFR-007 quantified)
- [ ] CHK075 — `Role` and `Permission` models do NOT use `SoftDeletes` — spec explicitly excludes them (configuration data)
- [ ] CHK076 — `BaseModel` is defined at `app/Models/BaseModel.php` with `SoftDeletes`, `HasFactory`, `$guarded = []`, `scopeActive()`, and standardized `$dateFormat`
- [ ] CHK077 — `RepositoryInterface` contract is defined at `app/Repositories/Contracts/RepositoryInterface.php` with all 7 method signatures: `find`, `findAll`, `findBy`, `create`, `update`, `delete`, `paginate`
- [ ] CHK078 — `BaseRepository` abstract class is defined at `app/Repositories/BaseRepository.php` implementing `RepositoryInterface` with a `protected Model $model` injected via constructor

---

## Testing

- [ ] CHK079 — `RefreshDatabase` or `DatabaseTransactions` trait is used on ALL PHPUnit tests that touch the database (NFR-008 quantified)
- [ ] CHK080 — All 5 role states tested in `UserFactory`: `admin()`, `customer()`, `contractor()`, `supervisingArchitect()`, `fieldEngineer()`
- [ ] CHK081 — Seeder is idempotent — spec states `RoleSeeder` uses `updateOrCreate`, `UserSeeder` uses `firstOrCreate`; running seeder twice produces no duplicate-row errors
- [ ] CHK082 — Relationship tests cover all 4 associations: `User->roles`, `Role->users`, `Role->permissions`, `Permission->roles`
- [ ] CHK083 — Soft-delete behavior tested: soft-deleted user excluded from `User::all()` / `User::find()`, returned by `User::withTrashed()`

---

## Acceptance Gate

- [ ] CHK084 — `php artisan migrate:fresh` runs with zero errors on clean MySQL 8.x database producing all 5 tables
- [ ] CHK085 — `php artisan migrate:rollback --step=5` runs with zero errors dropping all STAGE_02 tables cleanly
- [ ] CHK086 — `php artisan db:seed` produces exactly 5 roles, at least 20 permissions, and exactly 5 test users
- [ ] CHK087 — `composer run lint` passes with zero PSR-12 violations across all backend PHP files
- [ ] CHK088 — `composer run test` passes with all PHPUnit unit and feature tests green

---

_Checklist sections: 11 | Total items: 88_
