# Technical Plan — RBAC System

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Based on:** `specs/runtime/004-rbac-system/spec.md`  
> **Created:** 2026-04-13  
> **Stage:** STAGE_04 — RBAC System  
> **Risk Level:** HIGH

---

## Architecture Overview

The RBAC System implements a dual-track authorization model: a `UserRole` enum on the `users` table for fast role checks, plus `role_user` and `permission_role` pivot tables for fine-grained permission resolution. All enforcement is server-side via Laravel middleware; the frontend performs presentation-only checks.

**Key architectural decisions:**

- **Middleware-first enforcement**: Two new middleware (`RoleMiddleware`, `PermissionMiddleware`) registered as aliases `role` and `permission` in `bootstrap/app.php`.
- **Admin superuser via Gate::before**: Admin bypasses all Gate/Policy checks without needing individual permissions in the pivot table.
- **Per-request eager loading**: No Redis cache. Permissions loaded via `$user->load('roles.permissions')` — 2 queries max on small lookup tables. Meets ≤5ms budget.
- **Atomic role assignment**: `DB::transaction` wrapping enum column update + pivot sync. Rollback on any failure.
- **Seeder as source of truth**: `updateOrCreate` for role/permission metadata; `syncWithoutDetaching` for the default permission matrix.

**Layer placement:**

```
Request → CORS → CorrelationId → Auth (Sanctum) → RoleMiddleware / PermissionMiddleware → Controller
                                                                   ↓
                                                          AdminRbacController
                                                                   ↓
                                                        RoleService / PermissionService
                                                                   ↓
                                                      RoleRepository / PermissionRepository
                                                                   ↓
                                                     Role / Permission / User (Eloquent)
```

---

## Database Design

### New Tables

No new tables required. All schema already migrated:

| Table             | Status | Notes                                                |
| ----------------- | ------ | ---------------------------------------------------- |
| `roles`           | EXISTS | 5 roles seeded via `RoleSeeder`                      |
| `permissions`     | EXISTS | 26 permissions seeded — NEEDS UPDATE to 32           |
| `role_user`       | EXISTS | Pivot populated by `UserSeeder` only                 |
| `permission_role` | EXISTS | **NEVER SEEDED** — new `RolePermissionSeeder` needed |

### Modified Tables

No migration changes. All columns exist.

### Seeder Changes

| Seeder                 | Action | Details                                                           |
| ---------------------- | ------ | ----------------------------------------------------------------- |
| `PermissionSeeder`     | UPDATE | Replace 26 permissions (7 groups) with 32 permissions (10 groups) |
| `RolePermissionSeeder` | CREATE | Seed the 5×32 default permission matrix from spec                 |
| `DatabaseSeeder`       | UPDATE | Add `RolePermissionSeeder` call after `PermissionSeeder`          |

### Eloquent Relationships

```
User --belongsToMany--> Role (via role_user)
User.role (enum cast to UserRole)
Role --belongsToMany--> Permission (via permission_role)
Role --belongsToMany--> User (via role_user)
Permission --belongsToMany--> Role (via permission_role)
```

---

## API Design

### New Endpoints

| Method | Route                                  | Controller@Action                     | Middleware                   | Description                |
| ------ | -------------------------------------- | ------------------------------------- | ---------------------------- | -------------------------- |
| GET    | `/api/v1/admin/roles`                  | `AdminRbacController@listRoles`       | `auth:sanctum`, `role:admin` | List all roles             |
| GET    | `/api/v1/admin/roles/{id}`             | `AdminRbacController@showRole`        | `auth:sanctum`, `role:admin` | View role + permissions    |
| PUT    | `/api/v1/admin/roles/{id}/permissions` | `AdminRbacController@syncPermissions` | `auth:sanctum`, `role:admin` | Sync permissions to role   |
| POST   | `/api/v1/admin/users/{id}/role`        | `AdminRbacController@assignRole`      | `auth:sanctum`, `role:admin` | Assign role to user        |
| GET    | `/api/v1/admin/users`                  | `AdminRbacController@listUsers`       | `auth:sanctum`, `role:admin` | List users with roles      |
| GET    | `/api/v1/admin/permissions`            | `AdminRbacController@listPermissions` | `auth:sanctum`, `role:admin` | List permissions (grouped) |

### Request/Response Contracts

#### POST `/api/v1/admin/users/{id}/role` — Assign Role

**Request (AssignRoleRequest):**

```json
{
  "role": "contractor"
}
```

**Validation:**

- `role`: required, string, in:customer,contractor,supervising_architect,field_engineer,admin

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "User Name",
    "email": "user@bunyan.test",
    "role": "contractor",
    "is_active": true
  },
  "error": null
}
```

**Error — Self-lockout (422):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Cannot remove Admin role from your own account",
    "details": {
      "role": ["Cannot remove Admin role from your own account"]
    }
  }
}
```

#### PUT `/api/v1/admin/roles/{id}/permissions` — Sync Permissions

**Request (SyncPermissionsRequest):**

```json
{
  "permission_ids": [1, 2, 5, 7, 12]
}
```

**Validation:**

- `permission_ids`: required, array
- `permission_ids.*`: integer, exists:permissions,id

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "contractor",
    "display_name": "Contractor",
    "display_name_ar": "المقاول",
    "description": "Project execution, earnings, and withdrawals.",
    "permissions": [
      {
        "id": 1,
        "name": "projects.view",
        "display_name": "View Projects",
        "group": "projects"
      }
    ],
    "permissions_count": 5
  },
  "error": null
}
```

#### GET `/api/v1/admin/roles` — List Roles

**Success Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "admin",
      "display_name": "Administrator",
      "display_name_ar": "الإدارة",
      "description": "Full platform control and administration.",
      "permissions_count": 32
    }
  ],
  "error": null
}
```

#### GET `/api/v1/admin/roles/{id}` — Show Role

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "contractor",
    "display_name": "Contractor",
    "display_name_ar": "المقاول",
    "description": "Project execution, earnings, and withdrawals.",
    "permissions": [
      {
        "id": 1,
        "name": "projects.view",
        "display_name": "View Projects",
        "group": "projects",
        "description": "List and view projects"
      }
    ],
    "permissions_count": 8
  },
  "error": null
}
```

#### GET `/api/v1/admin/permissions` — List Permissions (Grouped)

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "projects": [
      { "id": 1, "name": "projects.view", "display_name": "View Projects" },
      { "id": 2, "name": "projects.create", "display_name": "Create Projects" }
    ],
    "reports": [],
    "users": []
  },
  "error": null
}
```

#### GET `/api/v1/admin/users` — List Users with Roles

**Query params:** `?page=1&per_page=15&role=contractor&search=name_or_email`

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "name": "User Name",
        "email": "user@bunyan.test",
        "role": "contractor",
        "is_active": true,
        "created_at": "2026-04-13T00:00:00.000000Z"
      }
    ],
    "meta": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  },
  "error": null
}
```

---

## Service Layer Design

| Service             | Methods                                                                                                                  | Dependencies                       |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------ | ---------------------------------- |
| `RoleService`       | `assignRoleToUser(User, UserRole, User)`, `getUserRole(User)`, `listRoles()`, `getRoleWithPermissions(int)`              | `RoleRepository`, `UserRepository` |
| `PermissionService` | `syncPermissionsToRole(Role, array)`, `listPermissions()`, `listPermissionsGrouped()`, `userHasPermission(User, string)` | `PermissionRepository`             |

### RoleService Details

```php
class RoleService
{
    public function __construct(
        private RoleRepository $roleRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * Assign role to user — atomic enum + pivot sync.
     * @throws ValidationException if admin self-lockout
     */
    public function assignRoleToUser(User $targetUser, UserRole $newRole, User $performingAdmin): User
    {
        // Admin self-lockout prevention
        if ($targetUser->id === $performingAdmin->id && $newRole !== UserRole::ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['Cannot remove Admin role from your own account'],
            ]);
        }

        return DB::transaction(function () use ($targetUser, $newRole) {
            // 1. Update enum column
            $targetUser->role = $newRole;
            $targetUser->save();

            // 2. Sync pivot table
            $role = $this->roleRepository->findByName($newRole->value);
            $targetUser->roles()->sync([$role->id]);

            return $targetUser->fresh(['roles']);
        });
    }

    public function listRoles(): Collection { /* via repository */ }
    public function getRoleWithPermissions(int $id): Role { /* with permissions eager load */ }
    public function getUserRole(User $user): UserRole { return $user->role; }
}
```

### PermissionService Details

```php
class PermissionService
{
    public function __construct(
        private PermissionRepository $permissionRepository,
    ) {}

    public function syncPermissionsToRole(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);
        return $role->fresh(['permissions']);
    }

    public function listPermissionsGrouped(): array
    {
        return $this->permissionRepository->findAll()
            ->groupBy('group')
            ->toArray();
    }

    public function userHasPermission(User $user, string $permissionName): bool
    {
        return $user->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissionName))
            ->exists();
    }
}
```

---

## Middleware Design

### RoleMiddleware

**File:** `app/Http/Middleware/RoleMiddleware.php`  
**Alias:** `role`  
**Usage:** `->middleware('role:admin,contractor')`

```
1. Resolve authenticated user (User model from auth guard)
2. Check user.is_active — if false → 403 AUTH_UNAUTHORIZED
3. Parse comma-separated roles from middleware parameter
4. Map strings to UserRole enum values
5. Call $user->hasAnyRole(...$roles)
6. If false → throw RoleNotAllowedException
7. If true → $next($request)
```

### PermissionMiddleware

**File:** `app/Http/Middleware/PermissionMiddleware.php`  
**Alias:** `permission`  
**Usage:** `->middleware('permission:projects.create')`

```
1. Resolve authenticated user
2. Check user.is_active — if false → 403 AUTH_UNAUTHORIZED
3. Check Admin superuser → if admin, pass through
4. Eager-load user.roles.permissions (if not already loaded)
5. Check if any of the user's role-permissions match the required permission
6. If not → throw RoleNotAllowedException
7. If yes → $next($request)
```

### Middleware Registration (bootstrap/app.php)

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    ]);
})
```

---

## User Model Changes

### New Method: `hasPermission(string $permissionName): bool`

```php
/**
 * Check if the user has a specific permission via their role.
 * Relies on eager-loaded roles.permissions relationship.
 */
public function hasPermission(string $permissionName): bool
{
    return $this->roles
        ->flatMap(fn (Role $role) => $role->permissions)
        ->contains('name', $permissionName);
}
```

### Auth User Endpoint Update

`GET /api/v1/auth/user` response must include `permissions` array:

```json
{
  "id": 1,
  "name": "User",
  "role": "contractor",
  "permissions": ["projects.view", "projects.update", "phases.view", ...]
}
```

This requires updating `UserResource` to load and serialize permissions when included.

---

## Gate Registration

**File:** `app/Providers/AppServiceProvider.php` (or dedicated `AuthServiceProvider`)

```php
public function boot(): void
{
    Gate::before(function (User $user, string $ability) {
        if ($user->hasEnumRole(UserRole::ADMIN)) {
            return true;
        }
    });
}
```

---

## Frontend Design

### Pages

| Route               | Page Component                | Layout | Auth Required | Role Required |
| ------------------- | ----------------------------- | ------ | ------------- | ------------- |
| `/admin/roles`      | `pages/admin/roles/index.vue` | admin  | Yes           | admin         |
| `/admin/roles/[id]` | `pages/admin/roles/[id].vue`  | admin  | Yes           | admin         |

### Components

| Component               | Purpose                     | Props                                         |
| ----------------------- | --------------------------- | --------------------------------------------- |
| `RoleTable`             | Display all roles in UTable | `roles: Role[]`                               |
| `RolePermissionManager` | Toggle permissions per role | `role: Role, permissions: GroupedPermissions` |

### Composables

| Composable        | Purpose                               | Returns                                    |
| ----------------- | ------------------------------------- | ------------------------------------------ |
| `usePermission()` | Check user permission from auth store | `{ hasPermission(name: string): boolean }` |
| `useRoleGuard()`  | Declarative role check for components | `{ isAllowed: ComputedRef<boolean> }`      |

### State Management (Pinia)

The existing `auth` store already provides `hasRole()`. We extend the `AuthUser` type to include `permissions: string[]` and add a `hasPermission()` method.

| Store  | New State          | New Actions | New Getters       |
| ------ | ------------------ | ----------- | ----------------- |
| `auth` | `user.permissions` | —           | `hasPermission()` |

### Frontend Route Middleware

**File:** `middleware/role.ts`

```typescript
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore();
  const requiredRole = to.meta.requiredRole as string | string[] | undefined;

  if (!requiredRole) return; // No role check needed
  if (!auth.isAuthenticated) return navigateTo('/auth/login');

  const roles = Array.isArray(requiredRole) ? requiredRole : [requiredRole];
  if (!auth.hasRole(roles as UserRoleType[])) {
    // Toast: "ليس لديك صلاحية للوصول إلى هذه الصفحة"
    return navigateTo('/dashboard');
  }
});
```

---

## Middleware Chain

```
Request → CORS → CorrelationId → Sanctum (auth:sanctum)
                                       ↓
                              RoleMiddleware (role:admin)
                                       ↓
                          PermissionMiddleware (permission:projects.create)
                                       ↓
                                   Controller
```

---

## Error Handling

| Scenario                          | Error Code           | HTTP Status | Message (EN)                                     |
| --------------------------------- | -------------------- | ----------- | ------------------------------------------------ |
| User role not in allowed list     | `RBAC_ROLE_DENIED`   | 403         | "Your current role does not allow this action"   |
| User lacks required permission    | `RBAC_ROLE_DENIED`   | 403         | "Your current role does not allow this action"   |
| Inactive user accesses RBAC route | `AUTH_UNAUTHORIZED`  | 403         | "Your account is not active"                     |
| Admin self-lockout attempt        | `VALIDATION_ERROR`   | 422         | "Cannot remove Admin role from your own account" |
| Invalid role value in assignment  | `VALIDATION_ERROR`   | 422         | Field-level validation error                     |
| Invalid permission IDs in sync    | `VALIDATION_ERROR`   | 422         | Field-level validation error                     |
| Role not found                    | `RESOURCE_NOT_FOUND` | 404         | "Role not found"                                 |
| User not found                    | `RESOURCE_NOT_FOUND` | 404         | "User not found"                                 |

---

## Testing Strategy

| Layer               | Tool       | Coverage Target | Scope                                                  |
| ------------------- | ---------- | --------------- | ------------------------------------------------------ |
| Unit (PHP)          | PHPUnit    | 90%             | RoleService, PermissionService, User::hasPermission    |
| Middleware (PHP)    | PHPUnit    | 95%             | RoleMiddleware, PermissionMiddleware — all 5 roles     |
| Feature/Integration | PHPUnit    | 90%             | Admin API endpoints, RBAC matrix (5 roles × endpoints) |
| Seeders             | PHPUnit    | 100%            | Idempotency, correct counts, pivot integrity           |
| Unit (JS)           | Vitest     | 80%             | hasPermission composable, auth store extensions        |
| Frontend Middleware | Vitest     | 80%             | role.ts middleware redirect behavior                   |
| E2E                 | Playwright | Critical paths  | Admin role management flow                             |

### RBAC Test Matrix

For each admin endpoint, test:

| Scenario              | Expected               |
| --------------------- | ---------------------- |
| Admin access          | 200 Success            |
| Customer access       | 403 RBAC_ROLE_DENIED   |
| Contractor access     | 403 RBAC_ROLE_DENIED   |
| Architect access      | 403 RBAC_ROLE_DENIED   |
| Field Engineer access | 403 RBAC_ROLE_DENIED   |
| Unauthenticated       | 401 AUTH_TOKEN_EXPIRED |
| Inactive admin        | 403 AUTH_UNAUTHORIZED  |

### Middleware-Specific Tests

- Role middleware: single role param, multiple roles (OR), invalid role string
- Permission middleware: valid permission, missing permission, admin bypass
- Active user check: active passes, inactive rejected
- Gate::before: admin bypasses all ability checks

---

## Security Considerations

- [x] Input validation via Form Requests (`AssignRoleRequest`, `SyncPermissionsRequest`)
- [x] RBAC middleware on all `/admin/*` routes
- [x] SQL injection prevention (Eloquent parameterized queries only)
- [x] `role` excluded from User `$fillable` (prevents mass-assignment privilege escalation)
- [x] Admin self-lockout prevention in service layer
- [x] Inactive user check in middleware (token valid but account disabled)
- [x] Rate limiting inherited from `api` middleware group
- [x] Server-side enforcement — frontend checks are presentation-only

---

## i18n / RTL Considerations

- [x] Role `display_name_ar` seeded for all 5 roles (already in `RoleSeeder`)
- [x] Permission display names in Arabic (add `display_name_ar` to permission seeds or use i18n keys)
- [x] Admin UI uses `$t()` translation keys, not hardcoded strings
- [x] Toast messages in Arabic: "ليس لديك صلاحية للوصول إلى هذه الصفحة"
- [x] RTL layout via Tailwind logical properties (Nuxt UI native)
- [x] Frontend role management page supports RTL table layout

---

## Risk Assessment

| Risk                                       | Likelihood | Impact   | Mitigation                                                                             |
| ------------------------------------------ | ---------- | -------- | -------------------------------------------------------------------------------------- |
| Enum + pivot desync during role assignment | Low        | High     | DB::transaction wraps both operations; tests verify atomicity                          |
| PermissionSeeder overwrites admin changes  | Medium     | Medium   | Pivot seeder uses `syncWithoutDetaching`; metadata uses `updateOrCreate` (intentional) |
| Missing middleware on new routes (future)  | Medium     | High     | CI check: `artisan route:list` grep for unprotected routes                             |
| N+1 queries in permission check            | Low        | Medium   | Eager load `roles.permissions` in middleware; test query count                         |
| Admin loses own role (self-lockout)        | Low        | Critical | Service-layer validation prevents this; tested explicitly                              |
| Frontend shows stale permissions           | Low        | Low      | Per-request eager load; no client-side cache beyond store                              |

---

## Implementation Files Inventory

### Backend — New Files

| File                                                    | Type         | Priority |
| ------------------------------------------------------- | ------------ | -------- |
| `app/Http/Middleware/RoleMiddleware.php`                | Middleware   | P1       |
| `app/Http/Middleware/PermissionMiddleware.php`          | Middleware   | P1       |
| `app/Services/RoleService.php`                          | Service      | P1       |
| `app/Services/PermissionService.php`                    | Service      | P1       |
| `app/Repositories/RoleRepository.php`                   | Repository   | P1       |
| `app/Repositories/PermissionRepository.php`             | Repository   | P1       |
| `app/Http/Controllers/Api/AdminRbacController.php`      | Controller   | P1       |
| `app/Http/Requests/AssignRoleRequest.php`               | Form Request | P1       |
| `app/Http/Requests/SyncPermissionsRequest.php`          | Form Request | P1       |
| `app/Http/Resources/RoleResource.php`                   | API Resource | P1       |
| `app/Http/Resources/PermissionResource.php`             | API Resource | P1       |
| `app/Http/Resources/UserRoleResource.php`               | API Resource | P2       |
| `database/seeders/RolePermissionSeeder.php`             | Seeder       | P1       |
| `tests/Feature/Middleware/RoleMiddlewareTest.php`       | Feature Test | P1       |
| `tests/Feature/Middleware/PermissionMiddlewareTest.php` | Feature Test | P1       |
| `tests/Feature/Admin/AdminRbacControllerTest.php`       | Feature Test | P1       |
| `tests/Unit/Services/RoleServiceTest.php`               | Unit Test    | P1       |
| `tests/Unit/Services/PermissionServiceTest.php`         | Unit Test    | P1       |
| `tests/Feature/Seeders/RolePermissionSeederTest.php`    | Feature Test | P2       |

### Backend — Modified Files

| File                                          | Changes                                        | Priority |
| --------------------------------------------- | ---------------------------------------------- | -------- |
| `bootstrap/app.php`                           | Add `role` and `permission` middleware aliases | P1       |
| `app/Models/User.php`                         | Add `hasPermission()` method                   | P1       |
| `app/Providers/AppServiceProvider.php`        | Add `Gate::before` for admin superuser         | P1       |
| `routes/api.php`                              | Add admin RBAC routes group                    | P1       |
| `database/seeders/PermissionSeeder.php`       | Update to 32 permissions, 10 groups            | P1       |
| `database/seeders/DatabaseSeeder.php`         | Add `RolePermissionSeeder` call                | P1       |
| `app/Http/Resources/UserResource.php`         | Add `permissions` to auth/user response        | P2       |
| `app/Http/Controllers/Api/AuthController.php` | Eager-load permissions in `user()` method      | P2       |

### Frontend — New Files

| File                           | Type             | Priority |
| ------------------------------ | ---------------- | -------- |
| `composables/usePermission.ts` | Composable       | P2       |
| `middleware/role.ts`           | Route Middleware | P2       |
| `pages/admin/roles/index.vue`  | Page             | P3       |
| `pages/admin/roles/[id].vue`   | Page             | P3       |

### Frontend — Modified Files

| File             | Changes                                   | Priority |
| ---------------- | ----------------------------------------- | -------- |
| `types/index.ts` | Add `permissions` to `AuthUser` interface | P2       |
| `stores/auth.ts` | Add `hasPermission()` getter              | P2       |

---

## Implementation Order

### Wave 1 — Core Middleware & Services (P1)

1. `RoleMiddleware` + `PermissionMiddleware` + bootstrap/app.php aliases
2. `RoleRepository` + `PermissionRepository`
3. `RoleService` + `PermissionService`
4. `User::hasPermission()` method
5. `Gate::before` admin superuser registration
6. Tests for middleware (all 5 roles × authorized/unauthorized)
7. Tests for services (assignment, sync, self-lockout)

### Wave 2 — Admin API & Seeders (P1-P2)

8. `AssignRoleRequest` + `SyncPermissionsRequest`
9. `RoleResource` + `PermissionResource` + `UserRoleResource`
10. `AdminRbacController` with all 6 endpoints
11. Admin routes in `routes/api.php`
12. Update `PermissionSeeder` (32 permissions, 10 groups)
13. Create `RolePermissionSeeder` (default matrix)
14. Feature tests for all admin endpoints (RBAC matrix)

### Wave 3 — Frontend (P2-P3)

15. Update `AuthUser` type with `permissions: string[]`
16. Update `UserResource` to include permissions in auth/user response
17. `usePermission()` composable
18. `role.ts` route middleware
19. Admin roles page (`index.vue`, `[id].vue`)
20. Frontend unit tests

---

## Dependencies

- **STAGE_03 (Authentication)**: Must be complete — Sanctum auth, auth store, auth middleware all required.
- **Existing Models**: Role, Permission, User models exist with relationships.
- **Existing Seeders**: RoleSeeder, PermissionSeeder, UserSeeder exist and are functional.
- **Existing Exception Handling**: RoleNotAllowedException + Handler already configured.

---

## Validation Checklist

- [ ] All admin routes protected by `auth:sanctum` + `role:admin`
- [ ] `artisan route:list` shows middleware on every protected route
- [ ] RBAC test matrix: 5 roles × 6 endpoints = 30 test cases (all passing)
- [ ] Middleware tests: active/inactive, valid/invalid role, admin bypass
- [ ] Seeder idempotency: run twice, no duplicates
- [ ] Permission pivot: 32 permissions × 5 roles correctly mapped
- [ ] Admin self-lockout: validated in service + tested
- [ ] Atomic role assignment: transaction rollback tested
- [ ] Frontend: `hasPermission()` works with auth store data
- [ ] Frontend: role middleware redirects unauthorized users
- [ ] `composer run lint` passes
- [ ] `composer run test` passes
- [ ] `npm run lint` passes
- [ ] `npm run typecheck` passes
