# STAGE_02 — Database Schema Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** Core MySQL schema, base migrations, Eloquent model patterns
> **Risk Level:** MEDIUM

## Stage Status

Status: IN PROGRESS
Step: analyze
Risk Level: MEDIUM
Last Updated: 2026-04-11T00:15:00Z

Scope Defined:

- 5 migrations: ALTER users + CREATE roles, permissions, role_user, permission_role
- 4 model changes: new Role, Permission, BaseModel; updated User (SoftDeletes, roles(), scopeActive())
- Repository pattern: RepositoryInterface, BaseRepository, UserRepository
- 3 seeders + DatabaseSeeder update; UserFactory with 5 role states
- 10 test files (T023–T032) covering all 4 user stories

Deferred Scope:

- Sanctum tokens, auth endpoints (STAGE_03)
- RBAC middleware + policies, RoleRepository, PermissionRepository (STAGE_04)
- Project/Phase/Task/Product tables (later phases)

Architecture Governance Compliance:

- speckit.analyze (structural drift): ✅ PASS (33/33 criteria)
- Security Auditor: ✅ PASS (SEC-FINDING-A fixed: role removed from $fillable)
- Performance Optimizer: ✅ PASS (12/12 criteria)
- QA Engineer: ✅ PASS (genuine gaps resolved: paginate, password hash, index checks)
- Code Reviewer: ✅ PASS (CR-03 resolved: test_ prefix removed from plan.md)
- Drift analysis PASSED — implementation AUTHORIZED

Notes:
All 5 audit gates cleared after remediation rounds. 32-task implementation plan is governance-compliant.
SEC-FINDING-A (HIGH): `role` removed from `$fillable` in T013 and plan.md — assign via explicit attribute only.
SEC-FINDING-B/D (MEDIUM): Noted for STAGE_03 HTTP-layer hardening.

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
