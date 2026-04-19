# Testing Guide — Projects

> **Phase:** 03_PROJECT_MANAGEMENT > **Generated:** 2025-07-25T17:10:00Z

## Prerequisites

```bash
# Backend
cd backend
composer install
cp ci.env .env
php artisan key:generate
php artisan migrate:fresh --seed

# Frontend
cd frontend
npm install
```

## Running Tests

### Backend Unit Tests

```bash
cd backend
php artisan test --testsuite=Unit --filter=ProjectStatusTest
```

### Backend Feature Tests

```bash
cd backend
php artisan test --testsuite=Feature --filter=ProjectControllerTest
php artisan test --testsuite=Feature --filter=ProjectPhaseControllerTest
php artisan test --testsuite=Feature --filter=ProjectTest
```

### Frontend Tests

```bash
cd frontend
npm run test -- --filter=useProjects
npm run test -- --filter=useProjectPhases
npm run test -- --filter=projectStore
npm run test -- --filter=ProjectCard
npm run test -- --filter=ProjectStatusBadge
npm run test -- --filter=StatusTransitionControl
```

### All Tests

```bash
# Backend (57 tests)
cd backend && php artisan test

# Frontend (26 tests)
cd frontend && npm run test
```

## Manual Test Scenarios

### Scenario 1 — Admin Creates a Project

**Preconditions:**

- Authenticated as Admin user
- Database seeded with users

**Steps:**

1. Navigate to `/projects/create`
2. Complete wizard Step 1 (Basic Info): enter `name_ar: "مشروع اختبار"`, `name_en: "Test Project"`, select type `RESIDENTIAL`
3. Complete wizard Step 2 (Location): enter `city: "Riyadh"`, `district: "Al Olaya"`, optional lat/lng
4. Complete wizard Step 3 (Budget & Timeline): enter `budget_estimated: 500000`, `start_date: 2025-08-01`, `end_date: 2026-08-01`, select owner (Customer user)
5. Review on Step 4 and submit

**Expected Result:**

- Project created with status DRAFT
- Redirected to project detail page
- Project visible in `/projects` listing

### Scenario 2 — Role-Scoped Project Listing

**Preconditions:**

- Multiple projects exist with different owners and assignments
- Users of each role exist (Admin, Customer, Contractor, Supervising Architect, Field Engineer)

**Steps:**

1. Login as Admin → GET `/api/v1/projects` → verify ALL projects returned
2. Login as Customer → GET `/api/v1/projects` → verify only owned projects returned
3. Login as Contractor → GET `/api/v1/projects` → verify only assigned projects returned
4. Apply filter `?status=DRAFT` → verify results filtered within role scope

**Expected Result:**

- Each role sees only authorized projects
- Filters work within role scope
- Pagination metadata present in all responses

### Scenario 3 — Project Status Transitions

**Preconditions:**

- A project in DRAFT status exists
- Authenticated as Admin

**Steps:**

1. PATCH `/api/v1/projects/{id}/status` with `{ "status": "planning" }` → expect 200
2. PATCH `/api/v1/projects/{id}/status` with `{ "status": "in_progress" }` → expect 200
3. PATCH `/api/v1/projects/{id}/status` with `{ "status": "on_hold" }` → expect 200
4. PATCH `/api/v1/projects/{id}/status` with `{ "status": "in_progress" }` → expect 200
5. PATCH `/api/v1/projects/{id}/status` with `{ "status": "completed" }` → expect 200
6. PATCH `/api/v1/projects/{id}/status` with `{ "status": "closed" }` → expect 200
7. Attempt PATCH with `{ "status": "draft" }` on CLOSED project → expect 422

**Expected Result:**

- Valid transitions succeed with updated status
- Invalid transitions return 422 WORKFLOW_INVALID_TRANSITION
- Non-Admin users receive 403 RBAC_ROLE_DENIED

### Scenario 4 — Project Phases CRUD

**Preconditions:**

- A project exists with `start_date: 2025-08-01`, `end_date: 2026-08-01`
- Authenticated as Admin

**Steps:**

1. POST `/api/v1/projects/{id}/phases` with `{ "name_ar": "الأساسات", "name_en": "Foundation", "sort_order": 1, "start_date": "2025-08-01", "end_date": "2025-12-01" }` → expect 201
2. POST another phase with `sort_order: 2`, dates `2025-12-01` to `2026-04-01` → expect 201
3. GET `/api/v1/projects/{id}/phases` → verify 2 phases returned ordered by sort_order
4. PUT `/api/v1/projects/{id}/phases/{phaseId}` → update `completion_percentage: 50` → expect 200
5. POST phase with `end_date: 2027-01-01` (beyond project end) → expect 422

**Expected Result:**

- Phases created within project date range succeed
- Phases outside project date range rejected with VALIDATION_ERROR
- Listing returns phases ordered by sort_order

### Scenario 5 — RTL/Arabic Support

**Preconditions:**

- Application locale set to Arabic

**Steps:**

1. Navigate to `/projects` — verify RTL layout
2. Navigate to `/projects/create` — verify wizard labels in Arabic, RTL text direction
3. Create a project with Arabic-only names — verify stored and displayed correctly
4. View project detail — verify Arabic content renders with proper alignment

**Expected Result:**

- All UI elements properly aligned RTL
- Arabic text stored and displayed correctly
- No layout breaks with Arabic content

### Scenario 6 — Unauthorized Access

**Preconditions:**

- A project owned by Customer A exists
- Authenticated as Customer B (different user)

**Steps:**

1. GET `/api/v1/projects/{id}` for Customer A's project → expect 403
2. PUT `/api/v1/projects/{id}` → expect 403
3. PATCH `/api/v1/projects/{id}/status` → expect 403
4. Logout, then repeat any endpoint → expect 401

**Expected Result:**

- Cross-user access denied with 403
- Unauthenticated access denied with 401
