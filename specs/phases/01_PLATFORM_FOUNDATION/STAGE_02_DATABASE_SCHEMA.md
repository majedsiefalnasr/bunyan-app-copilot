# STAGE_02 — Database Schema Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** Core MySQL schema, base migrations, Eloquent model patterns
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: clarify
Risk Level: MEDIUM
Last Updated: 2026-04-11T00:02:00Z

Scope Defined:

- MySQL schema: users (ALTER), roles, permissions, role_user, permission_role tables
- Eloquent models + repository base classes + seeders + factories
- Migration strategy: 5 new files, STAGE_01 migration immutable

Deferred Scope:

- Sanctum tokens, auth endpoints (STAGE_03)
- RBAC middleware + policies (STAGE_04)
- Project/Phase/Task/Product tables (later phases)

Architecture Governance Compliance:

- Clarifications resolved — planning authorized

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
