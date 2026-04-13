# RBAC System — Requirements Checklist

**Stage**: STAGE_04 — RBAC System
**Phase**: 01_PLATFORM_FOUNDATION
**Spec**: `specs/runtime/004-rbac-system/spec.md`

---

## Backend — Middleware

- [ ] **RBE-001**: `RoleMiddleware` implemented and registered as `role` alias in `bootstrap/app.php`
- [ ] **RBE-002**: `RoleMiddleware` accepts comma-separated role slugs (e.g., `role:admin,contractor`)
- [ ] **RBE-003**: `RoleMiddleware` checks `User::hasAnyRole()` against the `users.role` enum column
- [ ] **RBE-004**: `RoleMiddleware` returns `RBAC_ROLE_DENIED` (403) with standard error contract on failure
- [ ] **RBE-005**: `RoleMiddleware` checks `is_active` — rejects inactive users with `AUTH_UNAUTHORIZED` (403)
- [ ] **RBE-006**: `PermissionMiddleware` implemented and registered as `permission` alias
- [ ] **RBE-007**: `PermissionMiddleware` accepts comma-separated permission names (e.g., `permission:projects.create,projects.update`)
- [ ] **RBE-008**: `PermissionMiddleware` checks user's role permissions via the `role_user` + `permission_role` pivot chain
- [ ] **RBE-009**: `PermissionMiddleware` returns `RBAC_ROLE_DENIED` (403) with standard error contract on failure
- [ ] **RBE-010**: Admin role bypasses permission checks (middleware or Gate `before` callback)

## Backend — Services

- [ ] **RBE-011**: `RoleService` class created in `app/Services/`
- [ ] **RBE-012**: `RoleService::assignRoleToUser(User, UserRole)` — updates enum column + syncs pivot in DB transaction
- [ ] **RBE-013**: `RoleService::getUserRole(User)` — returns user's current role
- [ ] **RBE-014**: `RoleService::listRoles()` — returns all roles with permission counts
- [ ] **RBE-015**: `RoleService::getRoleWithPermissions(Role)` — returns role with permissions
- [ ] **RBE-016**: `PermissionService` class created in `app/Services/`
- [ ] **RBE-017**: `PermissionService::syncPermissionsToRole(Role, array)` — syncs permission IDs
- [ ] **RBE-018**: `PermissionService::listPermissions()` — returns permissions grouped by `group` field
- [ ] **RBE-019**: `PermissionService::userHasPermission(User, string)` — checks permission via role-permission chain

## Backend — Seeders

- [ ] **RBE-020**: `RoleSeeder` seeds all five roles with `name`, `display_name`, `display_name_ar`, `description`
- [ ] **RBE-021**: `PermissionSeeder` seeds all permissions across 10 groups (projects, phases, tasks, reports, users, orders, products, payments, settings, roles)
- [ ] **RBE-022**: `RolePermissionSeeder` seeds default role-permission mappings per the permission matrix
- [ ] **RBE-023**: Seeders are idempotent (`firstOrCreate` or `updateOrCreate`) — safe to re-run

## Backend — API Endpoints

- [ ] **RBE-024**: `GET /api/v1/admin/roles` — lists all roles (Admin only)
- [ ] **RBE-025**: `GET /api/v1/admin/roles/{id}` — view role with permissions (Admin only)
- [ ] **RBE-026**: `PUT /api/v1/admin/roles/{id}/permissions` — sync permissions to role (Admin only)
- [ ] **RBE-027**: `POST /api/v1/admin/users/{id}/role` — assign role to user (Admin only)
- [ ] **RBE-028**: `GET /api/v1/admin/users` — list users with roles (Admin only)
- [ ] **RBE-029**: `GET /api/v1/admin/permissions` — list all permissions grouped (Admin only)
- [ ] **RBE-030**: All Admin endpoints protected with `auth:sanctum` + `role:admin` middleware

## Backend — Controllers & Resources

- [ ] **RBE-031**: `AdminRoleController` with `index`, `show`, `updatePermissions` methods
- [ ] **RBE-032**: `AdminUserController` with `index`, `assignRole` methods (or extend existing)
- [ ] **RBE-033**: `RoleResource` API Resource for role serialization
- [ ] **RBE-034**: `PermissionResource` API Resource for permission serialization
- [ ] **RBE-035**: `AssignRoleRequest` Form Request with validation rules
- [ ] **RBE-036**: `SyncPermissionsRequest` Form Request with validation rules

## Backend — Gates & Policies

- [ ] **RBE-037**: `Gate::before` callback for Admin superuser bypass in `AuthServiceProvider` (or `AppServiceProvider`)
- [ ] **RBE-038**: Gate definitions for domain-specific authorization (e.g., `manage-project`, `approve-report`)
- [ ] **RBE-039**: Admin cannot remove Admin role from themselves (self-lockout prevention)

## Backend — Error Handling

- [ ] **RBE-040**: All RBAC authorization failures return `RBAC_ROLE_DENIED` with standard error contract
- [ ] **RBE-041**: Error messages support Arabic/English localization
- [ ] **RBE-042**: No stack traces or role information exposed in error responses

## Backend — Testing

- [ ] **RBE-043**: Unit tests for `RoleMiddleware` — each role allowed/denied
- [ ] **RBE-044**: Unit tests for `PermissionMiddleware` — each permission allowed/denied
- [ ] **RBE-045**: Unit tests for `RoleService` — role assignment, role sync, transaction behavior
- [ ] **RBE-046**: Unit tests for `PermissionService` — permission sync, lookup, grouping
- [ ] **RBE-047**: Feature tests for all Admin API endpoints — CRUD + authorization matrix
- [ ] **RBE-048**: Feature tests for `is_active` check in middleware
- [ ] **RBE-049**: Feature tests for Admin self-lockout prevention
- [ ] **RBE-050**: Seeder tests — verify all roles, permissions, and mappings are seeded correctly
- [ ] **RBE-051**: ≥ 90% code coverage on RBAC middleware, services, and controllers

## Frontend — Auth Store & Composables

- [ ] **FE-001**: `auth` Pinia store exposes `permissions` array from user profile response
- [ ] **FE-002**: `hasPermission(permission: string)` composable/helper implemented
- [ ] **FE-003**: `hasAnyPermission(permissions: string[])` composable/helper implemented
- [ ] **FE-004**: Auth store `hasRole()` continues to work for role-based UI gating

## Frontend — Navigation

- [ ] **FE-005**: Navigation items render conditionally based on `hasRole()` / `hasPermission()`
- [ ] **FE-006**: Customer navigation: My Projects, My Orders, Profile
- [ ] **FE-007**: Contractor navigation: My Projects, Earnings, Withdrawals, Profile
- [ ] **FE-008**: Supervising Architect navigation: Project Oversight, Field Engineers, Profile
- [ ] **FE-009**: Field Engineer navigation: Assigned Projects, Reports, Profile
- [ ] **FE-010**: Admin navigation: Dashboard, Users, Roles, Settings, plus all standard items

## Frontend — Route Middleware

- [ ] **FE-011**: `role` Nuxt route middleware created (`middleware/role.ts`)
- [ ] **FE-012**: Route middleware checks `auth.role` before allowing navigation
- [ ] **FE-013**: Unauthorized navigation redirects to user's dashboard with error toast
- [ ] **FE-014**: Admin pages (under `/admin/`) gated with `role:admin` middleware

## Frontend — Role Management Page (Admin)

- [ ] **FE-015**: `/admin/roles` page implemented with `UTable` showing all roles
- [ ] **FE-016**: Role detail view shows permissions grouped by category with toggle controls
- [ ] **FE-017**: Permission toggle changes call `PUT /api/v1/admin/roles/{id}/permissions`
- [ ] **FE-018**: Success/error toast notifications on save (Nuxt UI `useToast`)
- [ ] **FE-019**: Page fully RTL-compatible with Arabic labels
- [ ] **FE-020**: Form validation with VeeValidate + Zod on applicable forms

## Frontend — Testing

- [ ] **FE-021**: Vitest unit tests for `hasPermission()` composable
- [ ] **FE-022**: Vitest tests for role-based navigation rendering
- [ ] **FE-023**: Vitest tests for route middleware role checks

## Cross-Cutting

- [ ] **CC-001**: `users.role` enum and `role_user` pivot stay in sync (verified via tests)
- [ ] **CC-002**: Role assignment logged with structured logging (correlation ID, actor, target, change)
- [ ] **CC-003**: All middleware registered in `bootstrap/app.php` (not Kernel.php — Laravel 11+)
- [ ] **CC-004**: API responses follow standard error contract (`{ success, data, error }`)
- [ ] **CC-005**: No N+1 queries in permission checks (eager loading verified)
- [ ] **CC-006**: RBAC middleware latency ≤ 5ms (profiled in tests)
