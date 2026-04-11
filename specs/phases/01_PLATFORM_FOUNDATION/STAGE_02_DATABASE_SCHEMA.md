# STAGE_02 — Database Schema Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** Core MySQL schema, base migrations, Eloquent model patterns
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: plan
Risk Level: MEDIUM
Last Updated: 2026-04-11T00:03:00Z

Scope Planned:

- 5 migrations: ALTER users + CREATE roles, permissions, role_user, permission_role
- 4 model changes: new Role, Permission, BaseModel; updated User (SoftDeletes, roles(), scopeActive())
- Repository pattern: RepositoryInterface, BaseRepository, UserRepository
- 3 seeders + DatabaseSeeder update; UserFactory with 5 role states
- 7 test files (3 unit + 4 feature)

Deferred Scope:

- Sanctum tokens, auth endpoints (STAGE_03)
- RBAC middleware + policies, RoleRepository, PermissionRepository (STAGE_04)
- Project/Phase/Task/Product tables (later phases)

Architecture Governance Compliance:

- Architecture Guardian: PASS (10/10)
- API Designer: PASS (5/5)
- Technical plan compliant — task generation authorized

Notes:
Technical plan complete. Task breakdown in progress.

Notes:
All specification ambiguities resolved. Ready for technical planning.

## Objective

Define the core database schema for Bunyan. Establish migration patterns, Eloquent model conventions, and the repository pattern foundation.

## Scope

### Backend

- Core migrations: users, roles, permissions tables
- Base Eloquent model with shared traits (HasUuid, HasTimestamps, SoftDeletes)
- Repository pattern base class
- Database seeder structure
- Factory classes for testing

### Database Design

| Table           | Purpose                                                             |
| --------------- | ------------------------------------------------------------------- |
| users           | User accounts (all roles)                                           |
| roles           | Role definitions (Customer, Contractor, Architect, Engineer, Admin) |
| permissions     | Granular permission definitions                                     |
| role_user       | User-role pivot                                                     |
| permission_role | Permission-role pivot                                               |

## Dependencies

- **Upstream:** STAGE_01_PROJECT_INITIALIZATION
- **Downstream:** STAGE_03_AUTHENTICATION, STAGE_04_RBAC_SYSTEM
