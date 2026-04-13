# Tasks: RBAC System

**Input**: Design documents from `specs/runtime/004-rbac-system/`
**Prerequisites**: plan.md, spec.md, data-model.md, research.md
**Stage**: STAGE_04 — RBAC System
**Phase**: 01_PLATFORM_FOUNDATION
**Risk Level**: HIGH

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US6)
- All enforcement is server-side; frontend checks are presentation-only

---

## Wave 1: Backend Core — Middleware, Services, Repositories (P1)

**Purpose**: Core RBAC enforcement infrastructure. BLOCKS all subsequent waves.

### Repositories

- [x] T001 [P] [US2] Create `RoleRepository` extending `BaseRepository` with `findByName(string)`, `findWithPermissions(int)` methods — `backend/app/Repositories/RoleRepository.php`
- [x] T002 [P] [US3] Create `PermissionRepository` extending `BaseRepository` with `findByGroup(string)`, `findByNames(array)` methods — `backend/app/Repositories/PermissionRepository.php`

### Services

- [x] T003 [P] [US1] Create `RoleService` with `assignRoleToUser()` (atomic enum + pivot sync via `DB::transaction`), `listRoles()`, `getRoleWithPermissions()`, `getUserRole()` — `backend/app/Services/RoleService.php`
- [x] T004 [P] [US3] Create `PermissionService` with `syncPermissionsToRole()`, `listPermissionsGrouped()`, `userHasPermission()` — `backend/app/Services/PermissionService.php`

### Middleware

- [x] T005 [US2] Create `RoleMiddleware` — check `is_active`, parse comma-separated roles, call `hasAnyRole()`, throw `RoleNotAllowedException` on failure — `backend/app/Http/Middleware/RoleMiddleware.php`
- [x] T006 [US3] Create `PermissionMiddleware` — check `is_active`, admin superuser bypass, eager-load `roles.permissions`, check permission match, throw `RoleNotAllowedException` on failure — `backend/app/Http/Middleware/PermissionMiddleware.php`
- [x] T007 [US2] Register middleware aliases `role` and `permission` in `bootstrap/app.php` `withMiddleware` callback — `backend/bootstrap/app.php`

### User Model & Gate

- [x] T008 [US3] Add `hasPermission(string $permissionName): bool` method to User model — checks via `roles->flatMap->permissions->contains` — `backend/app/Models/User.php`
- [x] T009 [US3] Register `Gate::before` callback in `AppServiceProvider::boot()` — Admin superuser bypasses all Gate checks — `backend/app/Providers/AppServiceProvider.php`

### Wave 1 Tests

- [x] T010 [P] [US2] Feature tests for `RoleMiddleware` — test all 5 roles (authorized/unauthorized), multiple roles OR, inactive user rejection, unauthenticated — `backend/tests/Feature/Middleware/RoleMiddlewareTest.php`
- [x] T011 [P] [US3] Feature tests for `PermissionMiddleware` — valid permission, missing permission, admin bypass, inactive user — `backend/tests/Feature/Middleware/PermissionMiddlewareTest.php`
- [x] T012 [P] [US1] Unit tests for `RoleService` — role assignment, enum+pivot sync, self-lockout prevention, transaction rollback — `backend/tests/Unit/Services/RoleServiceTest.php`
- [x] T013 [P] [US3] Unit tests for `PermissionService` — sync permissions, grouped listing, user permission check — `backend/tests/Unit/Services/PermissionServiceTest.php`

**Checkpoint**: RBAC middleware enforces role and permission gates on any route. All 5 roles validated.

---

## Wave 2: Admin API + Seeders (P1–P2)

**Purpose**: Admin management endpoints, seeders for permission matrix, API resources.

**Depends on**: Wave 1 complete (repositories, services, middleware registered)

### Seeders

- [x] T014 [US3] Update `PermissionSeeder` — replace 26 permissions (7 groups) with 32 permissions (10 groups) per spec matrix. Use `updateOrCreate` keyed on `name` — `backend/database/seeders/PermissionSeeder.php`
- [x] T015 [US3] Create `RolePermissionSeeder` — seed the 5×32 default permission matrix using `syncWithoutDetaching()`. Must be idempotent — `backend/database/seeders/RolePermissionSeeder.php`
- [x] T016 [US3] Update `DatabaseSeeder` — add `RolePermissionSeeder::class` call after `PermissionSeeder` — `backend/database/seeders/DatabaseSeeder.php`

### Form Requests

- [x] T017 [P] [US1] Create `AssignRoleRequest` — validate `role` as required string, `in:customer,contractor,supervising_architect,field_engineer,admin` — `backend/app/Http/Requests/AssignRoleRequest.php`
- [x] T018 [P] [US4] Create `SyncPermissionsRequest` — validate `permission_ids` as required array of integers, each `exists:permissions,id` — `backend/app/Http/Requests/SyncPermissionsRequest.php`

### API Resources

- [x] T019 [P] [US4] Create `RoleResource` — serialize `id, name, display_name, display_name_ar, description, permissions_count`, conditionally include `permissions` — `backend/app/Http/Resources/RoleResource.php`
- [x] T020 [P] [US4] Create `PermissionResource` — serialize `id, name, display_name, group, description` — `backend/app/Http/Resources/PermissionResource.php`
- [x] T021 [P] [US1] Create `UserRoleResource` — serialize `id, name, email, role, is_active, created_at` — `backend/app/Http/Resources/UserRoleResource.php`

### Controller & Routes

- [x] T022 [US4] Create `AdminRbacController` with 6 actions: `listRoles`, `showRole`, `syncPermissions`, `assignRole`, `listUsers`, `listPermissions` — `backend/app/Http/Controllers/Api/AdminRbacController.php`
- [x] T023 [US4] Add admin RBAC route group — `middleware(['auth:sanctum', 'role:admin'])->prefix('admin')` — with all 6 routes — `backend/routes/api.php`

### Auth User Permissions

- [x] T024 [US5] Update `UserResource` to include `permissions` array (flattened permission names) when loading auth user — `backend/app/Http/Resources/UserResource.php`
- [x] T025 [US5] Update `AuthController::user()` to eager-load `roles.permissions` before returning `UserResource` — `backend/app/Http/Controllers/Api/AuthController.php`

### Wave 2 Tests

- [x] T026 [US4] Feature tests for `AdminRbacController` — RBAC matrix (5 roles × 6 endpoints), valid CRUD operations, error cases — `backend/tests/Feature/Admin/AdminRbacControllerTest.php`
- [x] T027 [US1] Feature tests for role assignment — assign role, self-lockout prevention, invalid role, non-admin rejection — `backend/tests/Feature/Admin/AssignRoleTest.php`
- [x] T028 [US4] Feature tests for permission sync — sync valid IDs, invalid IDs, empty array, non-admin rejection — `backend/tests/Feature/Admin/SyncPermissionsTest.php`
- [x] T029 [P] [US3] Feature tests for `RolePermissionSeeder` — idempotency, correct counts (32 permissions, 5 roles), pivot integrity — `backend/tests/Feature/Seeders/RolePermissionSeederTest.php`

**Checkpoint**: Admin can manage roles and permissions via API. Permission matrix seeded. Auth user response includes permissions.

---

## Wave 3: Frontend (P2–P3)

**Purpose**: Frontend permission checks, role middleware, admin role management UI.

**Depends on**: Wave 2 complete (API endpoints functional, auth user returns permissions)

### Types & Store (US5)

- [x] T030 [US5] Update `AuthUser` interface — add `permissions: string[]` field — `frontend/types/index.ts`
- [x] T031 [US5] Add `hasPermission(permissionName: string): boolean` getter to auth Pinia store — `frontend/stores/auth.ts`

### Composables & Middleware (US5)

- [x] T032 [US5] Create `usePermission()` composable — wraps auth store `hasPermission()` for template usage — `frontend/composables/usePermission.ts`
- [x] T033 [US5] Create `role.ts` route middleware — `defineNuxtRouteMiddleware` checking `to.meta.requiredRole` against auth store `hasRole()`, redirect to `/dashboard` on failure — `frontend/middleware/role.ts`

### Navigation Updates (US5)

- [x] T034 [US5] Update navigation components — conditionally render menu items based on `hasRole()` per role (Customer, Contractor, Architect, Field Engineer, Admin sections) — `frontend/components/common/AppSidebar.vue` or equivalent navigation component

### Admin Role Management Pages (US6)

- [x] T035 [US6] Create admin roles list page — `UTable` with role name, Arabic name, description, permission count. Apply `definePageMeta({ middleware: ['role'], requiredRole: 'admin' })` — `frontend/pages/admin/roles/index.vue`
- [x] T036 [US6] Create admin role detail page — display role info with grouped permissions using toggle controls (`UToggle`/`UCheckbox`), save via `PUT /api/v1/admin/roles/{id}/permissions` — `frontend/pages/admin/roles/[id].vue`

### Frontend i18n (US5–US6)

- [x] T037 [P] [US5] Add Arabic/English translation keys for RBAC labels — role names, permission names, navigation labels, toast messages — `frontend/locales/ar.json` and `frontend/locales/en.json`

### Wave 3 Tests

- [x] T038 [P] [US5] Vitest unit tests for `usePermission()` composable — permission check, missing permission, empty permissions array — `frontend/tests/unit/composables/usePermission.test.ts`
- [x] T039 [P] [US5] Vitest unit tests for `role.ts` middleware — redirect behavior, multi-role check, unauthenticated redirect — `frontend/tests/unit/middleware/role.test.ts`
- [x] T040 [P] [US5] Vitest unit tests for auth store `hasPermission()` getter — `frontend/tests/unit/stores/auth.test.ts`

**Checkpoint**: Frontend enforces role-based navigation, admin can manage roles via UI, all role-based UX matches spec.

---

## Wave 4: Polish & Cross-Cutting Concerns

**Purpose**: Final validation, integration, documentation.

**Depends on**: Waves 1–3 complete.

- [x] T041 [P] Run full backend test suite — `cd backend && php artisan test` — verify no regressions
- [x] T042 [P] Run full frontend test suite — `cd frontend && npm run test` — verify no regressions
- [x] T043 Run `php artisan route:list` and verify all admin routes have `role:admin` middleware — no unprotected admin routes
- [x] T044 [P] Run linters — `cd backend && composer run lint` and `cd frontend && npm run lint` — zero violations
- [x] T045 Seed fresh database — `php artisan migrate:fresh --seed` — verify 5 roles, 32 permissions, correct pivot matrix

---

## Dependencies & Execution Order

### Wave Dependencies

```
Wave 1 (Core) ──────► Wave 2 (API + Seeders) ──────► Wave 3 (Frontend) ──────► Wave 4 (Polish)
```

### Within-Wave Parallel Opportunities

**Wave 1:**

```
T001 ─┐
T002 ─┤── (parallel: repositories)
      │
T003 ─┤── (parallel: services, depend on T001/T002 for injection but files are independent)
T004 ─┘
      │
T005 ─┬── (sequential: middleware depends on repos/services conceptually)
T006 ─┘
      │
T007 ── (register aliases, depends on T005/T006)
      │
T008 ─┬── (parallel: model + gate)
T009 ─┘
      │
T010 ─┬── (parallel: all test files)
T011 ─┤
T012 ─┤
T013 ─┘
```

**Wave 2:**

```
T014 ─┬── (sequential: seeder before pivot seeder)
T015 ─┘
T016 ── (depends on T015)
      │
T017 ─┬── (parallel: form requests)
T018 ─┘
      │
T019 ─┬── (parallel: API resources)
T020 ─┤
T021 ─┘
      │
T022 ── (controller, depends on T017-T021)
T023 ── (routes, depends on T022)
      │
T024 ─┬── (parallel: auth user changes)
T025 ─┘
      │
T026 ─┬── (parallel: test files)
T027 ─┤
T028 ─┤
T029 ─┘
```

**Wave 3:**

```
T030 ── (types first)
T031 ── (store, depends on T030)
      │
T032 ─┬── (parallel: composable + middleware)
T033 ─┘
      │
T034 ── (navigation, depends on T031)
      │
T035 ─┬── (parallel: pages)
T036 ─┘
      │
T037 ── (i18n, parallel with pages)
      │
T038 ─┬── (parallel: test files)
T039 ─┤
T040 ─┘
```

---

## Implementation Strategy

### User Story → Task Mapping

| User Story                            | Priority | Tasks                                               |
| ------------------------------------- | -------- | --------------------------------------------------- |
| US1 — Admin Assigns Role to User      | P1       | T001, T003, T012, T017, T021, T027                  |
| US2 — RBAC Middleware Protects Routes | P1       | T001, T005, T007, T010                              |
| US3 — Permission-Based Authorization  | P1       | T002, T004, T006, T008, T009, T011, T013–T016, T029 |
| US4 — Admin Manages Roles/Perms       | P2       | T018–T020, T022, T023, T026, T028                   |
| US5 — Role-Based Navigation (FE)      | P2       | T024, T025, T030–T034, T037–T040                    |
| US6 — Admin Role Management Page      | P3       | T035, T036                                          |

### Risk Checkpoints

| After Task | Verify                                                   |
| ---------- | -------------------------------------------------------- |
| T007       | `role:admin` middleware blocks non-admin on a test route |
| T009       | Admin bypasses all Gate checks                           |
| T016       | `php artisan db:seed` produces 5 roles × 32 permissions  |
| T023       | All 6 admin endpoints return correct responses           |
| T025       | `GET /api/v1/auth/user` includes `permissions` array     |
| T033       | Non-admin navigating to `/admin/*` is redirected         |
| T045       | Full seed produces correct RBAC matrix                   |

### Total: 45 tasks across 4 waves
