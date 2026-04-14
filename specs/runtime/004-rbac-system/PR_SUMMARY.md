# PR — RBAC System

## Summary

**Stage:** RBAC System
**Phase:** 01_PLATFORM_FOUNDATION
**Branch:** `spec/004-rbac-system` → `develop`
**Tasks:** 45 / 45 completed

## What Changed

### Backend

- **RBAC Core**: RoleRepository, PermissionRepository, RoleService, PermissionService following service/repository pattern
- **Middleware**: RoleMiddleware (multi-role OR via variadic params), PermissionMiddleware (admin bypass, eager-loaded permissions)
- **Gate::before**: Admin superuser bypass in AppServiceProvider
- **User Model**: Added `hasPermission()` method with proper PHPStan typing
- **Admin API**: AdminRbacController with 6 actions (listRoles, showRole, syncPermissions, assignRole, listUsers, listPermissions)
- **Form Requests**: AssignRoleRequest, SyncPermissionsRequest for input validation
- **API Resources**: RoleResource, PermissionResource, UserRoleResource
- **Seeders**: PermissionSeeder updated to 32 permissions/10 groups, new RolePermissionSeeder for pivot data
- **Auth Integration**: UserResource includes permissions, AuthService eager-loads roles.permissions on login
- **Middleware Registration**: `role` and `permission` aliases registered in bootstrap/app.php
- **Routes**: Admin RBAC routes under `auth:sanctum` + `role:admin`

### Frontend

- **Types**: `AuthUser.permissions` field added
- **Store**: Auth store `hasPermission()` helper + `permissions` getter
- **Composable**: `usePermission()` for reactive permission checks
- **Middleware**: `role.ts` client-side route guard
- **Pages**: Admin roles list + detail pages with Nuxt UI components
- **Navigation**: Admin RBAC section added
- **i18n**: Arabic + English RBAC translations

### Database

- No new migrations (RBAC tables exist from Stage 02)
- Seeders updated: 32 permissions, role-permission pivot data

## Breaking Changes

- None — all changes are additive

## Testing

- [x] Unit tests pass (42 unit tests)
- [x] Feature tests pass (211 feature tests)
- [x] Frontend tests pass (135 tests, 16 files)
- [x] Lint passes (Pint: 124 files, 0 violations; ESLint: 0 violations)
- [x] Type check passes (PHPStan: 0 errors at level 5)

**Total: 253 backend tests (1,771 assertions) + 135 frontend tests — all green**

## Checklist

- [x] RBAC middleware applied on all admin routes
- [x] Form Request validation on all new endpoints
- [x] Arabic/RTL support verified (i18n keys, Nuxt UI RTL-native)
- [x] Service/repository pattern enforced
- [x] Admin self-lockout prevention (RoleService validation)
- [x] DB::transaction for atomic role assignment
- [x] Error contract compliance (RBAC_ROLE_DENIED, VALIDATION_ERROR)
- [x] No direct Eloquent in controllers (all via services)
- [x] PHPStan clean
- [x] Per-request permission loading (no stale cache)
