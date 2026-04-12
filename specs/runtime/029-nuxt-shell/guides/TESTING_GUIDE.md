# Testing Guide — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION  
> **Generated:** 2026-04-12T15:24:07Z

## Prerequisites

```bash
# Backend
cd backend
composer install
php artisan migrate --seed

# Frontend
cd ../frontend
npm install
```

## Running Tests

### Backend Unit Tests

```bash
cd backend
php artisan test --testsuite=Unit
```

### Backend Feature Tests

```bash
cd backend
php artisan test --testsuite=Feature
```

### Frontend Tests

```bash
cd frontend
npm run test
```

### All Validation Gates Used In This Stage

```bash
# Frontend
cd frontend
npm run lint
npm run typecheck
npm run test

# Backend
cd ../backend
composer run lint
composer run analyze
composer run test
php artisan migrate --pretend --no-interaction
```

## Manual Test Scenarios

### Scenario 1 — Role-Based Navigation

**Preconditions:**

- User is authenticated.
- User role is one of: customer, contractor, supervising_architect, field_engineer, admin.

**Steps:**

1. Sign in with each role.
2. Open the default layout shell.
3. Check header and sidebar items.

**Expected Result:**

- Each role sees only its allowed navigation entries.
- Customer does not see admin-only links.

### Scenario 2 — RTL/LTR Direction Toggle Persistence

**Preconditions:**

- Open any authenticated page using default layout.

**Steps:**

1. Toggle direction from header control.
2. Navigate to another page.
3. Refresh browser.

**Expected Result:**

- Direction updates immediately and remains persisted after navigation and refresh.

### Scenario 3 — Language Toggle (AR/EN)

**Preconditions:**

- Shell page is loaded.

**Steps:**

1. Click language switch in the header.
2. Verify route prefix and labels update.
3. Switch back.

**Expected Result:**

- Locale prefix changes (`/ar` ↔ `/en`) and labels localize correctly.

### Scenario 4 — Mobile Drawer Behavior

**Preconditions:**

- Browser viewport set to mobile width (e.g., 375px).

**Steps:**

1. Open shell page.
2. Tap hamburger button.
3. Select a navigation item.

**Expected Result:**

- Drawer opens/closes correctly and navigates to selected page.

### Scenario 5 — Logout Flow

**Preconditions:**

- User is authenticated.

**Steps:**

1. Open user dropdown menu.
2. Click logout.

**Expected Result:**

- Logout API is called.
- Auth state clears even on API failure.
- User is redirected to locale-specific login route.

## API Test Endpoints

| Method | Endpoint            | Auth | Expected Status |
| ------ | ------------------- | ---- | --------------- |
| DELETE | /api/v1/auth/logout | Yes  | 200/204         |
| GET    | /api/v1/auth/me     | Yes  | 200             |

## Common Issues

| Issue                                               | Cause                                                               | Fix                                                                                                                        |
| --------------------------------------------------- | ------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Direction toggle appears not persisted in unit test | `import.meta.client` guard is inactive in Node-based Vitest runtime | Assert composable state (`direction`, `hasManualOverride`) in unit tests; validate client-only side effects in browser/E2E |
| `navigateTo` undefined in tests                     | Nuxt global stub removed by aggressive cleanup                      | Avoid `vi.unstubAllGlobals()` in suites relying on setup-level stubs                                                       |
| Breadcrumb state not shared in tests                | `useState` mocked too late (after import)                           | Use `vi.hoisted()` to provide module-level `useState` before imports                                                       |
