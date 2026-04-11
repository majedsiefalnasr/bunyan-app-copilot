# Requirements Checklist — STAGE_02 Database Schema Foundation

**Stage**: STAGE_02 — DATABASE_SCHEMA
**Phase**: 01_PLATFORM_FOUNDATION
**Spec**: `specs/runtime/002-database-schema/spec.md`
**Generated**: 2026-04-11

---

## Table Completeness

- [ ] `users` table specified with all columns
- [ ] `roles` table specified with all columns
- [ ] `permissions` table specified with all columns
- [ ] `role_user` pivot table specified
- [ ] `permission_role` pivot table specified

---

## Column Definitions

- [ ] All columns have data types defined (`bigint UNSIGNED`, `varchar`, `tinyint`, `text`, `timestamp`)
- [ ] Nullable / NOT NULL constraints specified for every column
- [ ] Default values specified where applicable (`is_active` default `1`)
- [ ] `users.email` defined as UNIQUE
- [ ] `users.password` defined as `varchar(255)` (stores hash)
- [ ] `users.is_active` defined with boolean cast
- [ ] `users.deleted_at` column present for SoftDeletes
- [ ] `roles.name` defined as UNIQUE slug column
- [ ] `roles.display_name_ar` Arabic column defined
- [ ] `permissions.name` defined as UNIQUE dot-notation slug
- [ ] `permissions.group` defined for domain grouping

---

## Foreign Key Relationships

- [ ] `role_user.role_id` → `roles.id` ON DELETE CASCADE declared
- [ ] `role_user.user_id` → `users.id` ON DELETE CASCADE declared
- [ ] `permission_role.permission_id` → `permissions.id` ON DELETE CASCADE declared
- [ ] `permission_role.role_id` → `roles.id` ON DELETE CASCADE declared

---

## Indexes

- [ ] `UNIQUE(users.email)` index declared
- [ ] `INDEX(users.is_active)` declared
- [ ] `INDEX(users.deleted_at)` declared
- [ ] `UNIQUE(roles.name)` declared
- [ ] `UNIQUE(permissions.name)` declared
- [ ] `INDEX(permissions.group)` declared
- [ ] Composite primary key `(role_id, user_id)` on `role_user` declared
- [ ] Composite primary key `(permission_id, role_id)` on `permission_role` declared
- [ ] Index on `role_user.user_id` for reverse lookups declared
- [ ] Index on `permission_role.role_id` for reverse lookups declared

---

## Eloquent Models

- [ ] `User` model — `$fillable` list defined
- [ ] `User` model — `$hidden` includes `password` and `remember_token`
- [ ] `User` model — `$casts` includes `email_verified_at`, `password`, `is_active`
- [ ] `User` model — `roles()` relationship defined (`belongsToMany`)
- [ ] `User` model — `scopeActive()` defined
- [ ] `User` model — `hasRole()` helper defined
- [ ] `User` model — `SoftDeletes` trait specified
- [ ] `Role` model — `$fillable` list defined
- [ ] `Role` model — `users()` relationship defined
- [ ] `Role` model — `permissions()` relationship defined
- [ ] `Permission` model — `$fillable` list defined
- [ ] `Permission` model — `roles()` relationship defined
- [ ] `Permission` model — `scopeByGroup()` defined
- [ ] `BaseModel` class defined with shared traits and conventions
- [ ] All models contain ONLY relationships, scopes, casts, `$fillable`, `$hidden` (no business logic)

---

## Repository Pattern

- [ ] `RepositoryInterface` defined with all required method signatures
- [ ] `BaseRepository` abstract class defined implementing `RepositoryInterface`
- [ ] `UserRepository` extends `BaseRepository` with `findByEmail` and `findActiveUsers`
- [ ] Repository constructor injects Eloquent model (not hardcoded)

---

## Seeders

- [ ] `RoleSeeder` seeds exactly 5 roles
- [ ] All 5 role slugs defined: `admin`, `customer`, `contractor`, `supervising_architect`, `field_engineer`
- [ ] All 5 Arabic `display_name_ar` values defined: الإدارة, العميل, المقاول, المهندس المشرف, المهندس الميداني
- [ ] `PermissionSeeder` seeds minimum 20 permissions
- [ ] Permissions grouped into at least 7 domain groups
- [ ] `UserSeeder` creates one test user per role (5 users total)
- [ ] All seeded users use idempotent `firstOrCreate` / `updateOrCreate` pattern
- [ ] `DatabaseSeeder` calls seeders in dependency order (Roles → Permissions → Users)

---

## Factory

- [ ] `UserFactory` default state defined
- [ ] `admin()` state method defined
- [ ] `customer()` state method defined
- [ ] `contractor()` state method defined
- [ ] `supervisingArchitect()` state method defined
- [ ] `fieldEngineer()` state method defined
- [ ] Role state methods use `afterCreating()` to attach role
- [ ] `unverified()` state defined
- [ ] `inactive()` state defined

---

## Arabic Role Names

- [ ] `admin` → `الإدارة`
- [ ] `customer` → `العميل`
- [ ] `contractor` → `المقاول`
- [ ] `supervising_architect` → `المهندس المشرف`
- [ ] `field_engineer` → `المهندس الميداني`

---

## Non-Functional Requirements

- [ ] `utf8mb4` charset specified for all text columns
- [ ] `utf8mb4_unicode_ci` collation specified
- [ ] MySQL 8.x compatibility confirmed (no deprecated syntax, no ENUM)
- [ ] All migrations have `down()` rollback methods
- [ ] Migration file names follow dependency order
- [ ] PSR-12 compliance requirement stated
- [ ] SoftDeletes only on `users` (not roles/permissions)
- [ ] Pivot tables use `->withTimestamps()` in relationships

---

## Acceptance Criteria

- [ ] SC-001: `migrate:fresh` runs without errors
- [ ] SC-002: `migrate:rollback` runs without errors
- [ ] SC-003: Seeder produces correct record counts (5 roles, 20+ permissions, 5 users)
- [ ] SC-004: All Eloquent relationship tests pass
- [ ] SC-005: Factory role states produce correctly-roled users
- [ ] SC-006: `composer run lint` passes
- [ ] SC-007: `composer run test` passes
- [ ] SC-008: SoftDeletes behaviour verified
- [ ] SC-009: Cascade delete verified on pivot tables

---

## Out-of-Scope Compliance

- [ ] Sanctum token setup explicitly excluded from this stage
- [ ] Login/logout endpoints explicitly excluded
- [ ] RBAC middleware and policies explicitly excluded
- [ ] Workflow engine tables explicitly excluded
- [ ] File/avatar upload processing explicitly excluded

---

## Clarification Markers

The following items in `spec.md` are marked **[NEEDS CLARIFICATION]** and require resolution before implementation:

1. **UUID vs. Auto-Increment IDs**: Spec assumes auto-increment `bigint` for `users.id`. If UUID primary keys are required, the migration schema and factory definitions need adjustment.
2. **Translation Strategy**: Spec uses separate `display_name_ar` columns. If a JSON `translations` column or a polymorphic `model_translations` table is preferred, the schema and model casts need updating.

---

_Last updated: 2026-04-11_
