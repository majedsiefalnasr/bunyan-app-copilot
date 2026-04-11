# Plan Report — DATABASE_SCHEMA

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-11T00:03:00Z

## Plan Summary

| Metric             | Value                                                        |
| ------------------ | ------------------------------------------------------------ |
| New Tables         | 4 (roles, permissions, role_user, permission_role)           |
| Modified Tables    | 1 (users — ADD COLUMN migration)                             |
| New Models         | 3 (Role, Permission, BaseModel)                              |
| Modified Models    | 1 (User — add SoftDeletes, roles relationship, new fillable) |
| New Repositories   | 3 (RepositoryInterface, BaseRepository, UserRepository)      |
| New Seeders        | 3 (RoleSeeder, PermissionSeeder, UserSeeder)                 |
| Modified Seeders   | 1 (DatabaseSeeder — wire call order)                         |
| Modified Factories | 1 (UserFactory — 5 role states + 2 utility states)           |
| New Test Files     | 7 (3 Unit + 4 Feature)                                       |
| New Endpoints      | 0 (schema stage only)                                        |
| New Services       | 0 (schema stage only)                                        |
| New Frontend Pages | 0 (backend only)                                             |

## Architecture Decisions

1. **ALTER TABLE over new migration**: STAGE_01 users migration is immutable. A new `add_profile_columns_to_users_table` migration adds missing columns (`phone`, `is_active`, `avatar`, `deleted_at`).

2. **Role enum coexistence**: The existing `role` enum on users is preserved for STAGE_03 auth compatibility. The new `roles` table + `role_user` pivot adds granular RBAC foundation for STAGE_04. Dual encoding is intentional.

3. **hasRole() method naming**: STAGE_01 User model has `hasRole(UserRole $role)` (enum-based). Spec defines `hasRole(string)` (slug-based). PHP doesn't support overloading — implementation will rename existing to `hasEnumRole()` and introduce slug-based `hasRole(string)` as primary interface.

4. **BaseModel does not extend Authenticatable**: User model extends `Authenticatable` and cannot extend `BaseModel`. User manually implements the same traits/conventions. All other future models extend `BaseModel`.

5. **AppServiceProvider binds RepositoryInterface**: Service container binding `RepositoryInterface::class => UserRepository::class` registered in AppServiceProvider.

6. **No RoleRepository/PermissionRepository in STAGE_02**: These will be added in STAGE_04 when RBAC policies require them. The pattern infrastructure (BaseRepository, Interface) is ready.

## Guardian Verdicts

| Guardian              | Verdict | Notes                                                                            |
| --------------------- | ------- | -------------------------------------------------------------------------------- |
| Architecture Guardian | ✅ PASS | All 10 layering/RBAC/migration criteria passed                                   |
| API Designer          | ✅ PASS | Repository interface + pagination contract validated; 2 MINOR non-blocking notes |

### Minor Notes (non-blocking)

1. `hasRole()` signature conflict — resolved in plan: rename existing enum method to `hasEnumRole()`, introduce slug-based primary `hasRole(string)`
2. FR-011 omits `findBy` in text — acknowledged; implementation correctly includes it

## Risk Assessment

| Risk Level | Count | Details                                                                                                                                    |
| ---------- | ----- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| HIGH       | 0     | No high-risk items identified                                                                                                              |
| MEDIUM     | 2     | (1) User model modification may surface STAGE_01 test gaps; (2) Role-state factory tests require seeded roles — setUp must call RoleSeeder |
| LOW        | 2     | (1) Migration timestamp collision — coordinate in PR; (2) Composite PK syntax on pivot tables — `primary(['col1','col2'])` confirmed valid |

## Implementation Phases

| Phase     | Scope              | Tasks  |
| --------- | ------------------ | ------ |
| 0         | Pre-verification   | 4      |
| 1         | 5 migrations       | 5      |
| 2         | 4 model changes    | 4      |
| 3         | Repository pattern | 4      |
| 4         | 4 seeder changes   | 4      |
| 5         | UserFactory update | 1      |
| 6         | 7 test files       | 7      |
| **Total** |                    | **29** |

## External Dependencies (Context7-resolved)

| Library | Version | Patterns Used                                                                                              |
| ------- | ------- | ---------------------------------------------------------------------------------------------------------- |
| Laravel | 11.x    | `$table->softDeletes()`, `belongsToMany()->withTimestamps()`, factory `afterCreating()`, `RefreshDatabase` |
| PHPUnit | 11.x    | Feature and unit test structure                                                                            |
