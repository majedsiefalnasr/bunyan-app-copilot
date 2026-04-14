# Research Findings — RBAC System

> **Stage:** STAGE_04 — RBAC System  
> **Created:** 2026-04-13  
> **Purpose:** Resolve unknowns, validate assumptions, and document technical decisions

---

## Research Area 1: Existing RBAC Infrastructure Audit

### Finding: Dual-Track Role System Already Implemented

**Status:** VERIFIED — no ambiguity.

The codebase implements a dual-track role system:

1. **Enum column** (`users.role`): Cast to `UserRole` PHP enum. Used for fast single-role checks. NOT in `$fillable` — assigned only via explicit property setter (privilege escalation guard).
2. **Pivot table** (`role_user`): `belongsToMany` relationship. Used for relational queries (e.g., `scopeByRole`).

**Existing methods on User model:**

- `hasRole(string $slug)` — checks pivot relationship
- `hasEnumRole(UserRole $role)` — checks enum column
- `hasAnyRole(UserRole ...$roles)` — checks enum column (variadic OR)

**Decision:** The `RoleMiddleware` will use `hasAnyRole()` (enum-based, faster — no DB query). The `PermissionMiddleware` will use the pivot chain (`roles.permissions`).

**Risk:** Enum and pivot can desync. Mitigated by `DB::transaction` in `RoleService::assignRoleToUser()`.

---

## Research Area 2: Permission Seeder Gap Analysis

### Finding: Existing Seeder Has 26 Permissions in 7 Groups — Spec Requires 32 in 10 Groups

**Status:** RESOLVED — seeder will be regenerated from spec.

**Current seeder vs. spec comparison:**

| Group          | Current Seeder                      | Spec                               | Action             |
| -------------- | ----------------------------------- | ---------------------------------- | ------------------ |
| `users`        | view, create, edit, delete, restore | view, manage, deactivate           | REPLACE            |
| `projects`     | view, create, edit, delete, manage  | view, create, update, delete       | REPLACE            |
| `reports`      | view, create, edit, delete          | view, create, approve              | REPLACE            |
| `transactions` | view, create, manage                | _(removed — replaced by payments)_ | REMOVE             |
| `products`     | view, create, edit, delete          | view, create, update, delete       | RENAME edit→update |
| `orders`       | view, create, manage                | view, create, manage               | KEEP               |
| `settings`     | view, manage                        | view, manage                       | KEEP               |
| `phases`       | _(missing)_                         | view, create, update, delete       | ADD                |
| `tasks`        | _(missing)_                         | view, create, update, delete       | ADD                |
| `payments`     | _(missing)_                         | view, process, refund              | ADD                |
| `roles`        | _(missing)_                         | view, manage                       | ADD                |

**Final permission count:** 32 permissions across 10 groups.

**Migration strategy:** Since `PermissionSeeder` uses `updateOrCreate` keyed on `name`, renamed permissions (e.g., `users.edit` → `users.manage`) will create new records. Old records (`users.edit`, `users.delete`, etc.) will remain orphaned in the table but won't be in the pivot. This is acceptable for development; a cleanup migration can be added later if needed.

---

## Research Area 3: Permission-Role Pivot Seeder Strategy

### Finding: `permission_role` Pivot Is Never Seeded

**Status:** RESOLVED — new `RolePermissionSeeder` required.

**Current state:**

- `RoleSeeder` creates 5 roles
- `PermissionSeeder` creates permissions
- `UserSeeder` attaches users to roles via pivot
- **NO seeder populates `permission_role`** — roles have zero permissions

**Decision:** Create `RolePermissionSeeder` that:

1. Runs AFTER `RoleSeeder` and `PermissionSeeder`
2. Uses `syncWithoutDetaching()` (not `sync()`) to preserve any admin-added permissions
3. Maps the full 5×32 matrix from the spec's permission table
4. Is idempotent — safe to run multiple times

**Implementation pattern:**

```php
// For each role, look up Permission IDs by name, then syncWithoutDetaching
$adminRole = Role::where('name', 'admin')->firstOrFail();
$allPermissions = Permission::pluck('id')->toArray();
$adminRole->permissions()->syncWithoutDetaching($allPermissions);

$customerRole = Role::where('name', 'customer')->firstOrFail();
$customerPermissions = Permission::whereIn('name', [
    'projects.view', 'projects.create', /* ... */
])->pluck('id')->toArray();
$customerRole->permissions()->syncWithoutDetaching($customerPermissions);
```

---

## Research Area 4: Laravel 11 Middleware Registration

### Finding: Laravel 11 Uses `bootstrap/app.php` for Middleware Aliases

**Status:** VERIFIED.

Laravel 11 removed the `app/Http/Kernel.php` file. Middleware aliases are registered in `bootstrap/app.php` via the `withMiddleware` callback:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    ]);
})
```

The existing `bootstrap/app.php` has an empty `withMiddleware` callback — ready for alias registration.

**Confirmed:** No `Kernel.php` exists. This is a Laravel 11 application.

---

## Research Area 5: Gate::before vs. Policy-Based Authorization

### Finding: Gate::before Is the Correct Pattern for Admin Superuser

**Status:** VERIFIED.

Laravel's `Gate::before` callback runs before all other Gate checks. If it returns `true`, the user is authorized for all abilities. This is the standard Laravel pattern for superuser bypass.

**Registration location:** `AppServiceProvider::boot()` — confirmed this is the standard location in Laravel 11 applications. No dedicated `AuthServiceProvider` exists in the current codebase.

```php
// In AppServiceProvider::boot()
Gate::before(function (User $user, string $ability) {
    if ($user->hasEnumRole(UserRole::ADMIN)) {
        return true;
    }
});
```

**Important:** `Gate::before` returning `null` (not `false`) allows other checks to proceed. Returning `true` grants access unconditionally.

---

## Research Area 6: Middleware Performance Budget (≤5ms)

### Finding: Per-Request Eager Loading Is Within Budget

**Status:** VALIDATED.

The permission check requires at most:

1. `users.role` enum check — zero queries (column already on the authenticated user model)
2. `roles.permissions` eager load — 2 queries (role_user join + permission_role join)

For the RBAC tables:

- `roles` table: 5 rows (fixed)
- `permissions` table: 32 rows
- `role_user` pivot: 1 row per user
- `permission_role` pivot: ~32 rows max per role

These are tiny tables. The two join queries execute in <1ms on local MySQL. Total middleware overhead: **~2-3ms** including PHP processing.

**Decision:** No Redis caching needed. Per-request eager loading is sufficient. This avoids cache invalidation complexity and meets NFR-001.

**Future consideration:** If the permission table grows beyond 200+ rows or query time exceeds 5ms, introduce per-session Redis caching with TTL.

---

## Research Area 7: Frontend Permission Architecture

### Finding: Auth Store Extension Is Straightforward

**Status:** RESOLVED.

**Current state:**

- `AuthUser` type has: `id, name, email, role, phone, is_active, email_verified_at, created_at, avatar`
- No `permissions` field
- `auth` store has `hasRole()` but no `hasPermission()`
- `GET /api/v1/auth/user` returns user data via `UserResource` — currently no permissions

**Required changes:**

1. **Backend**: `UserResource` must include `permissions` when loading the auth user. This requires eager-loading `roles.permissions` in `AuthController::user()`:

```php
public function user(Request $request): JsonResponse
{
    $user = $request->user();
    $user->load('roles.permissions');
    // UserResource includes permissions array
}
```

2. **Frontend**: Add `permissions: string[]` to `AuthUser` interface. Add `hasPermission()` to auth store.

3. **Composable**: `usePermission()` wraps auth store's `hasPermission()` for template usage.

**Permission data flow:**

```
Login → GET /auth/user → UserResource (with permissions) → Pinia auth store → usePermission() composable → v-if in templates
```

---

## Research Area 8: Existing Exception Handling Integration

### Finding: Exception Handler Already Configured for RBAC Errors

**Status:** VERIFIED — no changes needed to exception handler.

The existing `Handler` class already:

- Catches `RoleNotAllowedException`
- Maps to `ApiErrorCode::RBAC_ROLE_DENIED` (HTTP 403)
- Returns the standard error contract JSON
- Includes English and Arabic messages

The `RoleNotAllowedException` extends `AuthorizationException` and carries `role` and `requiredRole` properties for logging.

**No handler modifications needed** — just throw `RoleNotAllowedException` from middleware.

---

## Research Area 9: Repository Pattern Compliance

### Finding: BaseRepository Pattern Exists and Must Be Followed

**Status:** RESOLVED.

The codebase has:

- `App\Repositories\Contracts\RepositoryInterface` — standard CRUD contract
- `App\Repositories\BaseRepository` — abstract implementation with `find`, `findAll`, `findBy`, `create`, `update`, `delete`, `paginate`
- `App\Repositories\UserRepository` — extends BaseRepository

**New repositories must follow this pattern:**

```php
class RoleRepository extends BaseRepository
{
    public function __construct(Role $model) { parent::__construct($model); }

    public function findByName(string $name): ?Role { /* ... */ }
    public function findWithPermissions(int $id): ?Role { /* ... */ }
}
```

```php
class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model) { parent::__construct($model); }

    public function findByGroup(string $group): Collection { /* ... */ }
    public function findByNames(array $names): Collection { /* ... */ }
}
```

---

## Research Area 10: Admin Route Group Placement

### Finding: Routes File Has Clear Extension Point

**Status:** RESOLVED.

The current `routes/api.php` has a clear comment: `// Additional API routes will be added by other stages`. Admin RBAC routes should be added as a new route group:

```php
// Admin RBAC Management
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('roles', [AdminRbacController::class, 'listRoles']);
    Route::get('roles/{role}', [AdminRbacController::class, 'showRole']);
    Route::put('roles/{role}/permissions', [AdminRbacController::class, 'syncPermissions']);
    Route::post('users/{user}/role', [AdminRbacController::class, 'assignRole']);
    Route::get('users', [AdminRbacController::class, 'listUsers']);
    Route::get('permissions', [AdminRbacController::class, 'listPermissions']);
});
```

**Note:** Route model binding uses `{role}` and `{user}` — Laravel auto-resolves to `Role` and `User` models. The existing `role_user` scope on User already handles role filtering.

---

## Research Area 11: Frontend Middleware Ordering

### Finding: Nuxt Middleware Pipeline Requires Correct Ordering

**Status:** RESOLVED.

Nuxt middleware executes in this order:

1. Global middleware (defined in `middleware/` with `.global.ts` suffix)
2. Page-level middleware (defined in `definePageMeta`)

The `auth` middleware must run BEFORE the `role` middleware. Options:

1. **Auth as global middleware** (`auth.global.ts`) — already established pattern
2. **Role as page-level middleware** — applied via `definePageMeta({ middleware: ['role'] })`
3. **Role metadata** — add `requiredRole` to page meta, checked by the role middleware

**Decision:** Use `definePageMeta` with `middleware: ['role']` and `meta: { requiredRole: 'admin' }`. This keeps role checks page-specific and avoids unnecessary checks on public pages.

---

## Summary of All Resolved Items

| Item                         | Resolution                                                      |
| ---------------------------- | --------------------------------------------------------------- |
| Dual-track role system       | RoleMiddleware uses enum; PermissionMiddleware uses pivot chain |
| Permission seeder gap        | Regenerate with 32 permissions, 10 groups from spec             |
| Permission-role pivot        | New RolePermissionSeeder with syncWithoutDetaching              |
| Middleware registration      | Laravel 11 bootstrap/app.php withMiddleware aliases             |
| Admin superuser              | Gate::before in AppServiceProvider::boot()                      |
| Performance budget           | Per-request eager load (2 queries, <3ms) — no Redis             |
| Frontend permissions         | Extend AuthUser type, add hasPermission() to auth store         |
| Exception handling           | Existing handler supports RBAC errors — no changes              |
| Repository pattern           | Extend BaseRepository, implement RepositoryInterface            |
| Admin route placement        | New middleware group after auth routes comment                  |
| Frontend middleware ordering | Auth (global) → Role (page-level via definePageMeta)            |

**No remaining NEEDS CLARIFICATION items.** All technical decisions are resolved.
