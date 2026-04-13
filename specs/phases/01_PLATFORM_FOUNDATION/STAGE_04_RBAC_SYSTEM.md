# STAGE_04 — RBAC System

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** Role-based access control, permissions, middleware
> **Risk Level:** HIGH

## Stage Status

Status: DRAFT
Step: clarify
Risk Level: HIGH
Last Updated: 2026-04-13T00:00:00Z

Scope Defined:

- RBAC middleware (RoleMiddleware, PermissionMiddleware) for all protected routes
- 5 user roles with 32 permissions across 10 domain groups
- Admin-only API endpoints for role/permission management
- Frontend role-based navigation and permission-based UI visibility
- 7 clarifications resolved (caching, atomicity, self-lockout, permission drift)

Deferred Scope:

- Custom role creation, per-user permission overrides, multi-tenancy

Architecture Governance Compliance:

- Clarifications resolved — planning authorized

Notes:
All specification ambiguities resolved. Ready for technical planning.

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
