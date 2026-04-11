# Specify Report — DATABASE_SCHEMA

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-11T00:00:00Z

## Specification Summary

| Metric                 | Value                                                                       |
| ---------------------- | --------------------------------------------------------------------------- |
| User Stories           | 4 (US1: Migrations, US2: Models, US3: Seeders, US4: Factories)              |
| Acceptance Criteria    | 9 success criteria + per-story Given/When/Then scenarios                    |
| Technical Requirements | 15 functional + 9 non-functional requirements                               |
| Dependencies           | STAGE_01_PROJECT_INITIALIZATION (upstream)                                  |
| Open Questions         | 2 (UUID strategy, translation column strategy — auto-resolved via codebase) |

## Scope Defined

- MySQL schema: `users`, `roles`, `permissions`, `role_user`, `permission_role` tables
- Eloquent models: `User`, `Role`, `Permission`, `BaseModel`
- Repository pattern: `RepositoryInterface`, `BaseRepository`, `UserRepository`
- Seeders: `RoleSeeder` (5 roles), `PermissionSeeder` (20+ permissions), `UserSeeder` (5 test users)
- `UserFactory` with 5 role states + `unverified()` + `inactive()`
- All migrations are forward-only with `down()` rollback methods
- utf8mb4 collation enforced throughout
- **Critical:** Users table requires ALTER migration to add `phone`, `is_active`, `avatar`, `deleted_at` columns (existing STAGE_01 migration is immutable)

## Deferred Scope

- Sanctum API token generation (STAGE_03)
- Login/logout/register endpoints (STAGE_03)
- RBAC middleware and gate definitions (STAGE_04)
- Policy classes (STAGE_04)
- Project/Phase/Task/Product/Order tables (later phases)
- File upload processing (STAGE_05+)
- Workflow engine tables (STAGE_06+)

## Risk Assessment

| Risk                                 | Level  | Mitigation                                                                                          |
| ------------------------------------ | ------ | --------------------------------------------------------------------------------------------------- |
| Existing users migration conflict    | MEDIUM | Use `add_columns_to_users_table` migration; do not touch STAGE_01 migration file                    |
| Existing `role` enum column on users | MEDIUM | Spec clarification: keep enum for backward compat with STAGE_01 code; roles table parallel for RBAC |
| FK constraint order in migrations    | LOW    | Run users → roles → permissions → role_user → permission_role                                       |
| Seeder idempotency                   | LOW    | Use `updateOrCreate` / `firstOrCreate` patterns throughout                                          |

## Checklist Status

- Requirements checklist: Created at `checklists/requirements.md`
- All 11 checklist items defined and verifiable
