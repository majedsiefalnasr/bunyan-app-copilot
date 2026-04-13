# Testing Guide — RBAC System

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2026-04-13T22:30:00Z

## Prerequisites

```bash
# Backend
cd backend
composer install
php artisan migrate:fresh --seed

# Frontend
cd frontend
npm install
```

## Running Tests

### Backend — All Tests

```bash
cd backend
php artisan test --parallel
```

### Backend — RBAC-Specific Tests

```bash
# Middleware tests
php artisan test --filter=RoleMiddlewareTest
php artisan test --filter=PermissionMiddlewareTest

# Service tests
php artisan test --filter=RoleServiceTest
php artisan test --filter=PermissionServiceTest

# Admin API tests
php artisan test --filter=AdminRbacControllerTest
php artisan test --filter=AssignRoleTest
php artisan test --filter=SyncPermissionsTest

# Seeder tests
php artisan test --filter=RolePermissionSeederTest
```

### Frontend — All Tests

```bash
cd frontend
npx vitest run
```

### Frontend — RBAC-Specific Tests

```bash
npx vitest run tests/unit/composables/usePermission.test.ts
npx vitest run tests/unit/middleware/role.test.ts
```

## Manual Test Scenarios

### Scenario 1 — Admin Lists All Roles

**Preconditions:**

- Database seeded (`php artisan migrate:fresh --seed`)
- Admin user logged in with valid Sanctum token

**Steps:**

1. `GET /api/admin/roles` with `Authorization: Bearer <admin_token>`
2. Verify response

**Expected Result:**

- HTTP 200
- `success: true`
- `data` contains array of 5 roles (customer, contractor, supervising_architect, field_engineer, admin)
- Each role has `permissions` array populated

---

### Scenario 2 — Admin Views Single Role with Permissions

**Preconditions:**

- Admin user logged in

**Steps:**

1. `GET /api/admin/roles/1` with admin token
2. Verify response includes role details and permissions

**Expected Result:**

- HTTP 200
- `data.name` = "admin"
- `data.permissions` contains all 32 permissions

---

### Scenario 3 — Admin Assigns Role to User

**Preconditions:**

- Admin user logged in
- Target user exists (e.g., user ID 2)

**Steps:**

1. `POST /api/admin/users/2/assign-role` with body `{ "role": "contractor" }` and admin token
2. Verify response

**Expected Result:**

- HTTP 200
- `success: true`
- User's `role` field updated to "contractor"
- `role_user` pivot updated atomically

---

### Scenario 4 — Admin Self-Lockout Prevention

**Preconditions:**

- Admin user logged in (e.g., user ID 1)

**Steps:**

1. `POST /api/admin/users/1/assign-role` with body `{ "role": "customer" }` and admin's own token
2. Verify error response

**Expected Result:**

- HTTP 422
- `success: false`
- `error.code: "VALIDATION_ERROR"`
- `error.message` indicates admin cannot remove own admin role

---

### Scenario 5 — Non-Admin Blocked from Admin Routes

**Preconditions:**

- Customer user logged in with valid Sanctum token

**Steps:**

1. `GET /api/admin/roles` with customer token
2. Verify blocked

**Expected Result:**

- HTTP 403
- `success: false`
- `error.code: "RBAC_ROLE_DENIED"`

---

### Scenario 6 — Sync Permissions to Role

**Preconditions:**

- Admin user logged in

**Steps:**

1. `PUT /api/admin/roles/2/permissions` with body `{ "permissions": [1, 2, 3] }` and admin token
2. Verify response

**Expected Result:**

- HTTP 200
- `success: true`
- Role's permissions updated to the synced set

---

### Scenario 7 — Inactive User Blocked by Middleware

**Preconditions:**

- User exists with `is_active = false`
- User has valid Sanctum token

**Steps:**

1. Access any protected route with inactive user's token
2. Verify blocked by RoleMiddleware/PermissionMiddleware

**Expected Result:**

- HTTP 403
- Access denied for inactive users

## API Test Endpoints

| Method | Endpoint                            | Auth        | Description              |
| ------ | ----------------------------------- | ----------- | ------------------------ |
| GET    | `/api/admin/roles`                  | admin token | List all roles           |
| GET    | `/api/admin/roles/{id}`             | admin token | Show role with perms     |
| PUT    | `/api/admin/roles/{id}/permissions` | admin token | Sync permissions to role |
| POST   | `/api/admin/users/{id}/assign-role` | admin token | Assign role to user      |
| GET    | `/api/admin/users`                  | admin token | List users with roles    |
| GET    | `/api/admin/permissions`            | admin token | List all permissions     |
