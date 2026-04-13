# Specify Report — RBAC System

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-13T00:00:00Z

## Specification Summary

| Metric                 | Value                       |
| ---------------------- | --------------------------- |
| User Stories           | 6 (P1×3, P2×2, P3×1)        |
| Acceptance Criteria    | 25 scenarios                |
| Technical Requirements | 20 (14 backend, 6 frontend) |
| Dependencies           | STAGE_03 (Authentication)   |
| Open Questions         | 0                           |

## Scope Defined

- RBAC middleware (`RoleMiddleware`, `PermissionMiddleware`) for all protected routes
- 5 user roles: Customer, Contractor, Supervising Architect, Field Engineer, Admin
- 32 permissions across 10 domain groups
- Role assignment and permission management service layer
- 6 Admin-only API endpoints for role/permission management
- Admin superuser bypass via `Gate::before`
- Frontend role-based navigation and permission-based UI visibility
- Admin Role Management page (Nuxt UI)
- Permission seeder with default role-permission matrix

## Deferred Scope

- Custom role creation (beyond 5 predefined roles)
- Per-user permission overrides
- Multi-tenancy / project-level role scoping
- Role hierarchy / inheritance
- OAuth/SSO role mapping

## Risk Assessment

- **HIGH**: RBAC is a security-critical subsystem — errors could expose unauthorized data
- Dual role tracking (enum + pivot) must stay synchronized via DB transactions
- Admin self-lockout prevention required
- Permission cache invalidation on role changes

## Checklist Status

- Requirements checklist: Created at `checklists/requirements.md` (82 items)
