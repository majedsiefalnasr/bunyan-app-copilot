# PR Summary — STAGE_02: Database Schema Foundation

## Pull Request

**Title:** `feat: STAGE_02 — Database Schema Foundation`
**Branch:** `spec/002-database-schema` → `develop`
**Type:** Feature
**Stage:** STAGE_02_DATABASE_SCHEMA
**Phase:** 01_PLATFORM_FOUNDATION

---

## Summary

Establishes the complete database schema foundation for Bunyan's RBAC system, Eloquent model hierarchy, repository pattern, data seeders, and factory states. This stage is pre-HTTP — it covers schema, models, repositories, seeders, and tests only. No authentication or endpoints are introduced.

---

## Changes

### Migrations (5 new)

| File                                                       | Type   | Purpose                                                      |
| ---------------------------------------------------------- | ------ | ------------------------------------------------------------ |
| `2026_04_11_000001_add_profile_columns_to_users_table.php` | ALTER  | Adds `phone`, `is_active`, `avatar`, `deleted_at` to `users` |
| `2026_04_11_000002_create_roles_table.php`                 | CREATE | RBAC roles with Arabic display names                         |
| `2026_04_11_000003_create_permissions_table.php`           | CREATE | Permissions with group field + index                         |
| `2026_04_11_000004_create_role_user_table.php`             | CREATE | User↔Role pivot (composite PK, reverse index)                |
| `2026_04_11_000005_create_permission_role_table.php`       | CREATE | Role↔Permission pivot (composite PK, reverse index)          |

All migrations include `down()` rollback methods and are FK-safe.

### Eloquent Models (3 new, 1 updated)

| File                        | Status  | Notes                                                                 |
| --------------------------- | ------- | --------------------------------------------------------------------- |
| `app/Models/BaseModel.php`  | NEW     | Abstract base; SoftDeletes, HasFactory, `scopeActive()`               |
| `app/Models/Role.php`       | NEW     | Extends `Eloquent\Model` (not BaseModel); Arabic display names        |
| `app/Models/Permission.php` | NEW     | Extends `Eloquent\Model` (not BaseModel); `scopeByGroup()`            |
| `app/Models/User.php`       | UPDATED | SoftDeletes, `roles()`, `hasRole()`, `hasEnumRole()`, `scopeByRole()` |

**Security:** `role` is NOT in `User::$fillable`. Role is assigned only via explicit property assignment or pivot attachment.

### Repository Pattern (3 new files)

| File                                                 | Purpose                                                              |
| ---------------------------------------------------- | -------------------------------------------------------------------- |
| `app/Repositories/Contracts/RepositoryInterface.php` | 7-method contract                                                    |
| `app/Repositories/BaseRepository.php`                | Abstract implementation; `ModelNotFoundException` on missing records |
| `app/Repositories/UserRepository.php`                | Domain methods: `findByEmail()`, `findActiveUsers()`                 |

`UserRepository` is bound in `AppServiceProvider`.

### Seeders (3 new, 1 updated)

| File                                    | Status  | Data                                                |
| --------------------------------------- | ------- | --------------------------------------------------- |
| `database/seeders/RoleSeeder.php`       | NEW     | 5 roles with Arabic `display_name_ar`               |
| `database/seeders/PermissionSeeder.php` | NEW     | 26 permissions across 7 groups                      |
| `database/seeders/UserSeeder.php`       | NEW     | 5 test users (one per role); production guard       |
| `database/seeders/DatabaseSeeder.php`   | UPDATED | Ordered: RoleSeeder → PermissionSeeder → UserSeeder |

All seeders use `updateOrCreate` / `firstOrCreate` for idempotency.

### Factory (1 updated)

`UserFactory` updated with `phone`, `is_active`, `avatar` fields and 6 states:
`admin()`, `customer()`, `contractor()`, `supervisingArchitect()`, `fieldEngineer()`, `inactive()`

### Tests (10 files, 60 tests)

| Test File                                     | Type    | Tests                              |
| --------------------------------------------- | ------- | ---------------------------------- |
| `Unit/Models/RoleModelTest.php`               | Unit    | Reflection-based, no DB            |
| `Feature/Models/UserModelTest.php`            | Feature | Password hash, scopes, hasRole     |
| `Feature/Models/PermissionModelTest.php`      | Feature | Fillable, scopes, relationships    |
| `Feature/Repositories/UserRepositoryTest.php` | Feature | All 7 interface methods + paginate |
| `Feature/Migrations/UsersMigrationTest.php`   | Feature | Column + index assertions          |
| `Feature/Migrations/RolesMigrationTest.php`   | Feature | RBAC tables, PKs, indexes          |
| `Feature/Seeders/RoleSeederTest.php`          | Feature | 5 roles, Arabic, idempotency       |
| `Feature/Database/RelationshipTest.php`       | Feature | 3-table traversal, cascade         |
| `Feature/Database/SeederTest.php`             | Feature | 26 permissions, idempotency        |
| `Feature/Database/FactoryTest.php`            | Feature | All role states                    |

---

## Acceptance Gate Results

| Gate   | Command                                 | Result                              |
| ------ | --------------------------------------- | ----------------------------------- |
| CHK084 | `php artisan migrate:fresh`             | ✅ 9 migrations, 0 errors           |
| CHK085 | `php artisan migrate:rollback --step=5` | ✅ FK-safe, 0 errors                |
| CHK086 | `php artisan db:seed`                   | ✅ 5 roles, 26 permissions, 5 users |
| CHK087 | `composer run lint`                     | ✅ 55 files, 0 violations           |
| CHK088 | `composer run test`                     | ✅ 60 passed, 107 assertions        |

---

## Security Notes

- `role` field is NOT in `User::$fillable` — prevents mass-assignment privilege escalation (SEC-FINDING-A resolved)
- `UserSeeder` checks `app()->environment('production')` before seeding test users
- `BaseRepository::findBy()` has no HTTP consumers in this stage; column allowlist to be added in STAGE_03

---

## Architecture Compliance

- ✅ Repository pattern: `RepositoryInterface` → `BaseRepository` → `UserRepository`
- ✅ Service layer boundary respected (no business logic in migrations or models)
- ✅ Arabic/RTL: role display names seeded in Arabic
- ✅ ADR-aligned: follows RBAC schema decisions from architecture governance
- ✅ Migration safety: `utf8mb4`, no MySQL ENUM, forward-only with rollbacks

---

## Deferred to STAGE_03+

- Sanctum authentication and API tokens
- HTTP middleware and RBAC policy enforcement
- `RoleRepository`, `PermissionRepository`
- `findBy()` column allowlist (safe for current pre-HTTP stage)

---

## Reviewer Checklist

- [ ] Migration `down()` methods tested in staging
- [ ] Seeder idempotency verified on pre-populated DB
- [ ] `role` NOT in `$fillable` confirmed
- [ ] All 60 tests pass in CI
- [ ] Arabic role display names verified by Arabic speaker
