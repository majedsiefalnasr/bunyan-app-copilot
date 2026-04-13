# Feature Specification: RBAC System

**Feature Branch**: `001-rbac-system`
**Created**: 2026-04-13
**Status**: Draft
**Stage**: STAGE_04 — RBAC System
**Phase**: 01_PLATFORM_FOUNDATION
**Risk Level**: HIGH
**Input**: User description: "RBAC System — Role-based access control, permissions, middleware for the Bunyan construction marketplace"

---

## User Scenarios & Testing _(mandatory)_

### User Story 1 — Admin Assigns Role to User (Priority: P1)

As an **Admin**, I need to assign one of the five platform roles to a user so that the user gains the correct permissions for their function on the platform.

**Why this priority**: Role assignment is the foundational action that enables all RBAC enforcement. Without it, no user can access role-protected resources.

**Independent Test**: Create a user, assign the "contractor" role via the admin API, then verify the user's role is reflected in their profile and they can access contractor-only routes.

**Acceptance Scenarios**:

1. **Given** an Admin is authenticated, **When** they assign the `contractor` role to an existing user via `POST /api/v1/admin/users/{id}/role`, **Then** the user's role is updated and the response returns `{ success: true, data: { user } }`.
2. **Given** an Admin assigns a role to a user who already has a role, **When** the request is processed, **Then** the previous role is replaced by the new role and associated pivot permissions are updated.
3. **Given** a non-Admin user attempts to assign a role, **When** the request is sent, **Then** it is rejected with `RBAC_ROLE_DENIED` (HTTP 403).
4. **Given** an Admin assigns a role using an invalid role value, **When** the request is processed, **Then** it is rejected with `VALIDATION_ERROR` (HTTP 422) and field-level details.

---

### User Story 2 — RBAC Middleware Protects Routes (Priority: P1)

As the **platform**, all protected API routes must be guarded by RBAC middleware so that only users with the correct role can access role-specific endpoints.

**Why this priority**: Route protection is the core enforcement mechanism. If routes are unprotected, the entire authorization model is meaningless.

**Independent Test**: Authenticate as a Customer, attempt to access a Contractor-only endpoint, and confirm a 403 response with `RBAC_ROLE_DENIED`.

**Acceptance Scenarios**:

1. **Given** a Customer is authenticated, **When** they access a route restricted to Contractors (e.g., `GET /api/v1/contractor/dashboard`), **Then** the response is `403` with error code `RBAC_ROLE_DENIED`.
2. **Given** a Contractor is authenticated, **When** they access a Contractor-allowed route, **Then** the route handler executes and returns a success response.
3. **Given** an unauthenticated user accesses a protected route, **When** the request is processed, **Then** the response is `401` with `AUTH_TOKEN_EXPIRED` (Sanctum handles this).
4. **Given** a route requires multiple roles (e.g., Admin OR Supervising Architect), **When** a user with either role accesses it, **Then** the route handler executes successfully.
5. **Given** a route is protected by the `role` middleware, **When** a user with a deactivated account (`is_active = false`) accesses it, **Then** the response is `403`.

---

### User Story 3 — Permission-Based Authorization (Priority: P1)

As the **platform**, feature access must be controlled via granular permissions (e.g., `projects.create`, `reports.approve`) so that roles can have fine-grained access control beyond role-level gating.

**Why this priority**: Permissions enable granular control within roles. E.g., a Field Engineer can view reports but not approve them, even though both actions are within the "reports" group.

**Independent Test**: Assign the `reports.view` permission (but not `reports.approve`) to the Field Engineer role. Authenticate as a Field Engineer and verify they can view reports but get `RBAC_ROLE_DENIED` when attempting to approve one.

**Acceptance Scenarios**:

1. **Given** a user's role has `projects.view` permission, **When** the user accesses `GET /api/v1/projects`, **Then** the route succeeds.
2. **Given** a user's role lacks `projects.create` permission, **When** the user sends `POST /api/v1/projects`, **Then** the request is rejected with `RBAC_ROLE_DENIED` (403).
3. **Given** Admin role has all permissions by default, **When** an Admin accesses any protected endpoint, **Then** the request succeeds regardless of individual permission checks.
4. **Given** a permission is checked via Laravel Gate/Policy, **When** the check fails, **Then** the response uses the standard error contract with `RBAC_ROLE_DENIED`.

---

### User Story 4 — Admin Manages Roles and Permissions (Priority: P2)

As an **Admin**, I need to view all roles, their assigned permissions, and manage role-permission mappings so that I can adjust access control as the platform evolves.

**Why this priority**: Role management is essential for platform governance but the initial role-permission mappings come from seeders. Management is needed for ongoing operations.

**Independent Test**: Authenticate as Admin, list all roles, view a role's permissions, assign a new permission to a role, and verify the change persists.

**Acceptance Scenarios**:

1. **Given** an Admin is authenticated, **When** they request `GET /api/v1/admin/roles`, **Then** the response includes all five roles with their display names (English and Arabic) and permission counts.
2. **Given** an Admin requests `GET /api/v1/admin/roles/{id}`, **When** the role exists, **Then** the response includes the role's full details and all assigned permissions.
3. **Given** an Admin sends `POST /api/v1/admin/roles/{id}/permissions`, **When** the request body contains valid permission IDs, **Then** the permissions are synced to the role.
4. **Given** a non-Admin user attempts to access any `/admin/roles/*` endpoint, **When** the request is processed, **Then** it is rejected with `RBAC_ROLE_DENIED` (403).

---

### User Story 5 — Role-Based Navigation (Frontend) (Priority: P2)

As a **user** of the platform, I should only see navigation items and UI elements relevant to my role, so that the interface is not cluttered with options I cannot use.

**Why this priority**: Clean role-based UX is essential for usability, especially for an Arabic-first platform with diverse user roles (customers vs. engineers vs. admins).

**Independent Test**: Log in as a Customer and verify the navigation shows only customer-relevant items. Log in as Admin and verify the admin dashboard and management links appear.

**Acceptance Scenarios**:

1. **Given** a Customer is authenticated, **When** the navigation renders, **Then** only Customer-relevant menu items are visible (e.g., "My Projects", "My Orders").
2. **Given** an Admin is authenticated, **When** the navigation renders, **Then** Admin-specific items appear (e.g., "User Management", "Role Management", "System Settings").
3. **Given** a Supervising Architect is authenticated, **When** the navigation renders, **Then** architect-specific items appear (e.g., "Project Oversight", "Field Engineer Management").
4. **Given** the user's role changes (e.g., after re-fetching the profile), **When** the navigation re-renders, **Then** it reflects the updated role immediately.

---

### User Story 6 — Admin Role Management Page (Frontend) (Priority: P3)

As an **Admin**, I need a dedicated Role Management page in the admin dashboard where I can view all roles, their permissions, manage user-role assignments, and update role-permission mappings.

**Why this priority**: While backend APIs are the enforcement layer, a usable admin UI is necessary for ongoing platform operations without raw API calls.

**Independent Test**: Navigate to the Admin dashboard, open the Role Management page, view roles, click on a role to see its permissions, and assign/remove permissions.

**Acceptance Scenarios**:

1. **Given** an Admin navigates to `/admin/roles`, **When** the page loads, **Then** it displays a table of all five roles with name, Arabic name, description, and permission count.
2. **Given** an Admin clicks on a role, **When** the detail view opens, **Then** permissions are grouped by category (e.g., Projects, Reports, Users) with toggle controls.
3. **Given** an Admin toggles a permission on/off for a role, **When** they save changes, **Then** the backend syncs the permissions and shows a success toast notification.
4. **Given** a non-Admin user navigates to `/admin/roles`, **When** the route middleware executes, **Then** they are redirected to their dashboard with an appropriate error message.

---

### Edge Cases

- **Deleted user with active tokens**: When a user's role is removed or they are deactivated, existing Sanctum tokens should still be valid but middleware must check `is_active` on each request.
- **Role enum vs. pivot mismatch**: The `users.role` enum column and `role_user` pivot must stay in sync. Role assignment must update both atomically.
- **Permission cache invalidation**: If permissions are cached (e.g., per-request), changing a role's permissions must be reflected on subsequent requests without requiring a logout.
- **Admin cannot remove own Admin role**: An Admin should not be able to remove the Admin role from their own account (prevents lockout).
- **Concurrent role changes**: If two Admins simultaneously change different user roles, both changes should succeed without conflict (each operates on different user records).
- **Empty permission set**: A role with zero permissions should be allowed (e.g., a newly created role before permissions are assigned) but the user should have no access to permission-gated resources.

---

## Requirements _(mandatory)_

### Functional Requirements

#### Backend

- **FR-001**: System MUST implement `RoleMiddleware` that checks the authenticated user's role against a list of allowed roles for each route group.
- **FR-002**: System MUST implement `PermissionMiddleware` that checks the authenticated user's role-permissions against required permissions for each route.
- **FR-003**: System MUST provide a `RoleService` (service pattern) for role assignment, role lookup, and role-permission management.
- **FR-004**: System MUST provide a `PermissionService` for permission CRUD and role-permission syncing.
- **FR-005**: System MUST seed all five roles (`customer`, `contractor`, `supervising_architect`, `field_engineer`, `admin`) with display names in English and Arabic.
- **FR-006**: System MUST seed a default permission set grouped by domain (e.g., `projects.view`, `projects.create`, `reports.view`, `reports.approve`, `users.manage`).
- **FR-007**: System MUST define Laravel Gate definitions for complex authorization rules (e.g., "can manage project" checks both role and project ownership).
- **FR-008**: System MUST implement Admin API endpoints for role management: list roles, view role details, update role permissions, assign role to user.
- **FR-009**: System MUST enforce that the `users.role` enum column and the `role_user` pivot table remain synchronized when roles are assigned.
- **FR-010**: System MUST return standard error contract responses (`RBAC_ROLE_DENIED`, HTTP 403) for all authorization failures.
- **FR-011**: System MUST check `is_active` status in RBAC middleware — inactive users are rejected with `AUTH_UNAUTHORIZED` (403).
- **FR-012**: System MUST treat Admin role as a superuser — Admin bypasses individual permission checks (Gate `before` callback).
- **FR-013**: System MUST implement `UserRoleResource` and `PermissionResource` API Resources for consistent response formatting.
- **FR-014**: System MUST implement Form Request validation classes for role assignment and permission management endpoints.

#### Frontend

- **FR-015**: Frontend MUST use Pinia `auth` store's `hasRole()` helper for presentation-only role checks (all enforcement server-side).
- **FR-016**: Frontend MUST implement a `hasPermission()` composable/helper that checks the user's permissions array returned from the `/api/v1/auth/user` endpoint.
- **FR-017**: Frontend MUST render navigation items conditionally based on user role using `v-if` with `hasRole()`.
- **FR-018**: Frontend MUST implement an Admin Role Management page at `/admin/roles` using Nuxt UI components (`UTable`, `UButton`, `UModal`, `UCard`).
- **FR-019**: Frontend MUST implement route-level middleware (`defineNuxtRouteMiddleware`) that checks role before allowing navigation to role-specific pages.
- **FR-020**: Frontend MUST display all text labels in Arabic (RTL) with English fallback, including role names and permission labels.

### Key Entities

- **Role**: Represents a platform role (name, display_name, display_name_ar, description). Has many Permissions via `permission_role` pivot. Has many Users via `role_user` pivot.
- **Permission**: Represents a granular permission (name, display_name, group, description). Belongs to many Roles via `permission_role` pivot. Grouped by domain (projects, reports, users, orders, etc.).
- **User**: Existing entity with `role` enum column on `users` table AND `role_user` pivot relationship. Both must stay synchronized.
- **role_user**: Pivot table linking users to roles (already migrated).
- **permission_role**: Pivot table linking permissions to roles (already migrated).

---

## Non-Functional Requirements

- **NFR-001 (Performance)**: RBAC middleware must add no more than 5ms to request latency (eager-load roles/permissions per request, or cache per-session).
- **NFR-002 (Security)**: All RBAC enforcement MUST be server-side. Frontend role checks are presentation-only and MUST NOT be treated as security boundaries.
- **NFR-003 (Auditability)**: Role assignment changes must be logged via structured logging (correlation ID, user performing action, target user, old role, new role).
- **NFR-004 (Testability)**: All RBAC middleware and services must have unit + feature tests covering each role and permission combination.
- **NFR-005 (Scalability)**: Permission checks must not perform N+1 queries. Permissions should be loaded once per request via eager loading.
- **NFR-006 (i18n)**: All role and permission display names must support Arabic and English. Frontend labels use i18n translation keys.
- **NFR-007 (Consistency)**: The `users.role` enum column and `role_user` pivot table must always be in sync. Any role assignment operation must update both in a database transaction.

---

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: All five roles are seeded and queryable via the admin API.
- **SC-002**: Every protected API route has `role` or `permission` middleware applied — verified via route:list inspection and tests.
- **SC-003**: Unauthorized access to a role-protected route returns `RBAC_ROLE_DENIED` (403) with the standard error contract — 100% of tested cases.
- **SC-004**: Admin can assign/change any user's role via the API, and the change is reflected immediately in middleware checks.
- **SC-005**: Frontend navigation renders correctly for all five roles — each role sees only their relevant menu items.
- **SC-006**: Admin Role Management page loads within 2 seconds and correctly displays all roles, permissions, and allows toggling.
- **SC-007**: RBAC middleware adds ≤ 5ms to request latency (measured via middleware profiling in tests).
- **SC-008**: Minimum 90% code coverage on RBAC middleware, services, and policies.

---

## Out of Scope

- **Custom role creation**: Admins cannot create new roles beyond the five predefined ones in this stage. Custom roles are a future enhancement.
- **Per-user permission overrides**: Permissions are assigned at the role level only. Per-user permission exceptions are out of scope.
- **Multi-tenancy**: RBAC is platform-wide; project-level or organization-level role scoping is handled in later stages (Workflow Engine).
- **Role hierarchy**: No role inheritance (e.g., Admin does not automatically inherit all Contractor permissions through hierarchy — Admin is a superuser via `Gate::before`).
- **Permission UI for non-Admin roles**: Only the Admin role has access to role/permission management. Other roles do not see or manage permissions.
- **OAuth/SSO role mapping**: External identity provider role synchronization is not in scope.

---

## Assumptions

- Sanctum-based authentication is fully functional (STAGE_03 complete).
- The `UserRole` enum, `Role` model, `Permission` model, and all four migration tables (`roles`, `permissions`, `role_user`, `permission_role`) already exist in the codebase.
- The `User` model already has `hasRole()`, `hasEnumRole()`, `hasAnyRole()` methods and the `roles()` relationship.
- The `auth` Pinia store already has `hasRole()` for frontend presentation-level checks.
- The error contract with `RBAC_ROLE_DENIED` code is already defined in `ApiErrorCode` enum and exception handler.
- The default set of permissions will be defined in this stage's seeder and can be expanded in future stages.
- The five roles are fixed — no role CRUD (create/delete) is needed, only permission management.

---

## API Endpoints

### Admin Role Management

| Method | Endpoint                               | Description                     | Auth    | Middleware   |
| ------ | -------------------------------------- | ------------------------------- | ------- | ------------ |
| GET    | `/api/v1/admin/roles`                  | List all roles                  | Sanctum | `role:admin` |
| GET    | `/api/v1/admin/roles/{id}`             | View role details + permissions | Sanctum | `role:admin` |
| PUT    | `/api/v1/admin/roles/{id}/permissions` | Sync permissions to role        | Sanctum | `role:admin` |
| POST   | `/api/v1/admin/users/{id}/role`        | Assign role to user             | Sanctum | `role:admin` |
| GET    | `/api/v1/admin/users`                  | List users with roles           | Sanctum | `role:admin` |
| GET    | `/api/v1/admin/permissions`            | List all permissions (grouped)  | Sanctum | `role:admin` |

### Permission Seed Groups

| Group    | Permissions                                                              |
| -------- | ------------------------------------------------------------------------ |
| projects | `projects.view`, `projects.create`, `projects.update`, `projects.delete` |
| phases   | `phases.view`, `phases.create`, `phases.update`, `phases.delete`         |
| tasks    | `tasks.view`, `tasks.create`, `tasks.update`, `tasks.delete`             |
| reports  | `reports.view`, `reports.create`, `reports.approve`                      |
| users    | `users.view`, `users.manage`, `users.deactivate`                         |
| orders   | `orders.view`, `orders.create`, `orders.manage`                          |
| products | `products.view`, `products.create`, `products.update`, `products.delete` |
| payments | `payments.view`, `payments.process`, `payments.refund`                   |
| settings | `settings.view`, `settings.manage`                                       |
| roles    | `roles.view`, `roles.manage`                                             |

### Default Role-Permission Mapping

| Permission       | Customer | Contractor | Supervising Architect | Field Engineer | Admin |
| ---------------- | :------: | :--------: | :-------------------: | :------------: | :---: |
| projects.view    |    ✓     |     ✓      |           ✓           |       ✓        |   ✓   |
| projects.create  |    ✓     |            |                       |                |   ✓   |
| projects.update  |          |     ✓      |           ✓           |                |   ✓   |
| projects.delete  |          |            |                       |                |   ✓   |
| phases.view      |    ✓     |     ✓      |           ✓           |       ✓        |   ✓   |
| phases.create    |          |     ✓      |           ✓           |                |   ✓   |
| phases.update    |          |     ✓      |           ✓           |                |   ✓   |
| phases.delete    |          |            |                       |                |   ✓   |
| tasks.view       |    ✓     |     ✓      |           ✓           |       ✓        |   ✓   |
| tasks.create     |          |     ✓      |           ✓           |       ✓        |   ✓   |
| tasks.update     |          |     ✓      |           ✓           |       ✓        |   ✓   |
| tasks.delete     |          |            |                       |                |   ✓   |
| reports.view     |    ✓     |     ✓      |           ✓           |       ✓        |   ✓   |
| reports.create   |          |            |                       |       ✓        |   ✓   |
| reports.approve  |          |            |           ✓           |                |   ✓   |
| users.view       |          |            |           ✓           |                |   ✓   |
| users.manage     |          |            |                       |                |   ✓   |
| users.deactivate |          |            |                       |                |   ✓   |
| orders.view      |    ✓     |     ✓      |                       |                |   ✓   |
| orders.create    |    ✓     |            |                       |                |   ✓   |
| orders.manage    |          |            |                       |                |   ✓   |
| products.view    |    ✓     |     ✓      |                       |                |   ✓   |
| products.create  |          |            |                       |                |   ✓   |
| products.update  |          |            |                       |                |   ✓   |
| products.delete  |          |            |                       |                |   ✓   |
| payments.view    |    ✓     |     ✓      |                       |                |   ✓   |
| payments.process |          |            |                       |                |   ✓   |
| payments.refund  |          |            |                       |                |   ✓   |
| settings.view    |          |            |                       |                |   ✓   |
| settings.manage  |          |            |                       |                |   ✓   |
| roles.view       |          |            |                       |                |   ✓   |
| roles.manage     |          |            |                       |                |   ✓   |

---

## Technical Notes

### Middleware Registration

Two middleware must be registered in `bootstrap/app.php`:

- `role:{roles}` — e.g., `role:admin,contractor` — checks `UserRole` enum on `users.role` column.
- `permission:{permissions}` — e.g., `permission:projects.create` — checks `permission_role` via the user's assigned role.

### Gate::before (Admin Superuser)

```php
Gate::before(function (User $user, string $ability) {
    if ($user->hasEnumRole(UserRole::ADMIN)) {
        return true; // Admin bypasses all Gates
    }
});
```

### Role Sync Strategy

When assigning a role:

1. Wrap in DB transaction
2. Update `users.role` enum column
3. Sync `role_user` pivot table
4. Flush any per-request permission cache

### Frontend Permission Flow

1. `/api/v1/auth/user` returns user with `role` and `permissions[]` array
2. `auth` Pinia store holds user data including permissions
3. `hasPermission('projects.create')` composable checks against cached permissions
4. Navigation items use `v-if="hasRole('admin')"` or `v-if="hasPermission('roles.manage')"`
5. Route middleware checks role before page navigation (redirect to dashboard if unauthorized)

---

## Clarifications

_Clarification pass completed 2026-04-13. Autopilot mode — decisions derived from codebase evidence, Laravel best practices, and AGENTS.md conventions._

### CLR-001: RBAC Middleware — `hasAnyRole()` vs `hasAllRoles()`

**Question**: Should the `RoleMiddleware` check if the user has _any_ of the listed roles (OR logic) or _all_ of them (AND logic)?

**Decision**: **`hasAnyRole()` (OR logic) only.** The middleware signature `role:admin,contractor` means the user must have at least one of the listed roles. This matches the existing `User::hasAnyRole(UserRole ...$roles)` method in the codebase, the spec's User Story 2 Scenario 4 ("Admin OR Supervising Architect"), and standard Laravel middleware conventions. A `hasAllRoles()` variant is not needed because Bunyan uses single-role-per-user (the `users.role` enum is a single value, not an array), making AND logic logically impossible.

**Spec impact**: FR-001 is confirmed as-is. No `hasAllRoles` middleware is needed.

### CLR-002: Permission Caching Strategy

**Question**: How should permissions be cached — per-request eager load, per-session in Redis, or another approach?

**Decision**: **Per-request eager loading only (no Redis, no session cache).** On each authenticated request, the `RoleMiddleware` / `PermissionMiddleware` eager-loads the user's role with its permissions via `$user->load('roles.permissions')`. This is:

- Simpler (no cache invalidation complexity).
- Consistent (permission changes take effect on the next request immediately, satisfying the edge case "Permission cache invalidation").
- Performant (2 queries max: user→roles, roles→permissions; well within the ≤5ms NFR-001 budget for a local MySQL join on small tables).

Redis caching MAY be introduced in a future performance optimization stage if permission tables grow significantly. For now, per-request eager loading is sufficient and avoids premature optimization.

**Spec impact**: NFR-001 clarified. Add to Technical Notes: "Permissions are eager-loaded per-request. No cross-request caching is used in this stage."

### CLR-003: Admin Self-Lockout Prevention

**Question**: How exactly should the system prevent an Admin from removing their own Admin role?

**Decision**: **Server-side validation in `RoleService::assignRole()`.** Before updating a user's role, the service checks: if `$targetUser->id === $currentAdmin->id` AND the new role is not `admin`, throw a `ValidationException` with error code `VALIDATION_ERROR` and message "Cannot remove Admin role from your own account." This is implemented as a business rule in the service layer (not middleware, not controller). The frontend should also disable the role dropdown when viewing the current Admin's own record (presentation-only guard).

**Spec impact**: Encode in FR-003 scope. Error code: `VALIDATION_ERROR` (HTTP 422), not `RBAC_ROLE_DENIED`, because this is a business rule violation, not an authorization failure.

### CLR-004: Role Assignment Atomicity — Enum + Pivot Sync Failure

**Question**: What happens if the `users.role` enum column update succeeds but the `role_user` pivot sync fails within the transaction?

**Decision**: **Database transaction with full rollback.** The spec's "Role Sync Strategy" section already mandates wrapping in a DB transaction, which means if _either_ step fails, both are rolled back. The implementation in `RoleService::assignRole()` will use `DB::transaction()`. If the pivot sync throws (e.g., invalid role ID, constraint violation), the enum update is rolled back and a `SERVER_ERROR` (500) is returned. The service MUST NOT catch exceptions inside the transaction closure — let them bubble up so the transaction manager rolls back correctly.

**Spec impact**: NFR-007 confirmed. Add explicit note: "If any step within the role assignment transaction fails, the entire operation is rolled back. No partial state is possible."

### CLR-005: Seeder Idempotency — `updateOrCreate` Implications

**Question**: The existing seeders use `updateOrCreate`. Should the spec mandate `firstOrCreate` instead to prevent overwriting manual Admin changes to role/permission metadata?

**Decision**: **Keep `updateOrCreate` (current codebase pattern).** Rationale:

- `updateOrCreate` ensures the seeder is the source of truth for role/permission definitions, which is correct for a fixed-role system where roles are not user-created.
- If an Admin changes a role's `display_name` via the API, running the seeder again will revert it — this is _intentional_ because the seeder defines the canonical role metadata.
- The spec explicitly states "five roles are fixed — no role CRUD" and "permission mappings come from seeders."
- For role-permission _assignments_ (pivot data), the seeder should use `syncWithoutDetaching()` to add default permissions without removing any Admin-added ones.

**Spec impact**: Add to Technical Notes: "Role and Permission seeders use `updateOrCreate` (canonical metadata). Role-permission pivot seeding uses `syncWithoutDetaching` to preserve Admin-added permission assignments."

### CLR-006: Frontend Route Middleware — Unauthorized Redirect Behavior

**Question**: Where should unauthorized users be redirected when they attempt to access a role-restricted page?

**Decision**: **Redirect to the user's role-specific dashboard with a toast notification.** Behavior:

1. If the user is **authenticated but lacks the required role**: redirect to `/{locale}/dashboard` (the generic dashboard route, which itself redirects to the role-specific dashboard). Show an Arabic toast: "ليس لديك صلاحية للوصول إلى هذه الصفحة" ("You do not have permission to access this page").
2. If the user is **unauthenticated**: handled by the existing `auth` middleware — redirect to `/{locale}/auth/login` (already implemented).
3. The role middleware MUST run _after_ the auth middleware in the middleware pipeline to ensure the user object is available.

**Spec impact**: FR-019 clarified with redirect target and toast behavior. Middleware ordering: `auth` → `role` → page component.

### CLR-007: Permission Name Drift — Spec vs. Existing Seeder

**Question** (surfaced during analysis): The spec defines permission names like `users.manage`, `users.deactivate`, `projects.update`, `projects.delete`, but the existing `PermissionSeeder` uses different names: `users.edit`, `users.delete`, `users.restore`, `projects.edit`, `projects.delete`, `projects.manage`. Which is canonical?

**Decision**: **The spec is authoritative; the seeder will be updated to match.** The spec's permission naming is more domain-appropriate for Bunyan:

- `users.manage` (broader than `users.edit`) — covers role assignment + profile editing
- `users.deactivate` (specific action, not a generic `users.delete`)
- `projects.update` (standard CRUD verb, aligned with REST conventions)
- New permissions not in the current seeder (`phases.*`, `tasks.*`, `payments.*`, `roles.*`) will be added

The existing seeder also has `transactions.*` permissions not in the spec, plus a `reports.edit` not in the spec. These discrepancies will be resolved during implementation: the seeder will be regenerated from the spec's permission table as the single source of truth.

**Spec impact**: Add note to Assumptions: "The existing `PermissionSeeder` will be updated/replaced to match this spec's permission definitions. The spec's permission table is the canonical source."
