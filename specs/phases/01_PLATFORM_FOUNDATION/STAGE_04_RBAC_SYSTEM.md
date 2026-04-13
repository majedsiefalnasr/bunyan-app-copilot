# STAGE_04 — RBAC System

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** Role-based access control, permissions, middleware
> **Risk Level:** HIGH

## Stage Status

Status: PRODUCTION READY
Step: stage_production_ready
Risk Level: HIGH
Closure Date: 2026-04-13

Scope Closed:

- RoleRepository + PermissionRepository (BaseRepository pattern)
- RoleService + PermissionService (business logic layer)
- RoleMiddleware + PermissionMiddleware (route-level RBAC enforcement)
- Gate::before admin superuser bypass
- User::hasPermission() method
- AdminRbacController (6 admin API actions)
- 32 permissions across 10 groups (seeder updated)
- RolePermissionSeeder (pivot data)
- Frontend: usePermission composable, role middleware, admin pages, i18n
- 45 / 45 tasks completed

Deferred Scope:

- None

Architecture Governance Compliance:

- ADR alignment verified
- RBAC enforcement confirmed (middleware on all admin routes)
- Service layer architecture maintained (thin controllers)
- Error contract compliance verified (RBAC_ROLE_DENIED, VALIDATION_ERROR)
- i18n/RTL support verified

Notes:
Stage is production ready. No structural modifications allowed.
Modifications require a new stage.

## Objective

Implement a comprehensive RBAC system supporting five user roles with granular permissions. RBAC middleware must be applied on all protected routes.

## User Roles

| Role                  | Arabic       | Description                               |
| --------------------- | ------------ | ----------------------------------------- |
| Customer              | عميل         | End user requesting construction services |
| Contractor            | مقاول        | Service provider executing work           |
| Supervising Architect | مهندس مشرف   | Architect overseeing project compliance   |
| Field Engineer        | مهندس ميداني | On-site engineer managing execution       |
| Admin                 | مدير النظام  | Platform administrator                    |

## Scope

### Backend

- RBAC middleware for route protection
- Permission-based authorization (can/cannot)
- Role assignment and management service
- Admin role management endpoints
- Permission seeder with default role-permission mappings
- Gate definitions for complex authorization rules

### Frontend

- Role-based navigation rendering
- Permission-based UI element visibility
- Role management page (Admin only)

## Dependencies

- **Upstream:** STAGE_03_AUTHENTICATION
- **Downstream:** All protected features
