# Testing Guide — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2025-07-18

## Prerequisites

```bash
# Backend
cd backend
composer install
php artisan migrate:fresh --seed
php artisan serve  # Start on port 8000

# Frontend
cd frontend
npm install
npm run dev  # Start on port 3000
```

## Running Tests

### Backend Unit Tests

```bash
cd backend
php artisan test --testsuite=Unit --filter=AuthService
```

### Backend Feature Tests

```bash
cd backend
php artisan test --filter=Auth
```

### Frontend Tests

```bash
cd frontend
npx vitest run --reporter=verbose
```

### All Tests

```bash
# Backend (56 tests, 230 assertions)
cd backend && php artisan test --filter=Auth

# Frontend (122 tests, 14 files)
cd frontend && npx vitest run
```

## Manual Test Scenarios

### Scenario 1 — User Registration

**Preconditions:**

- Backend API running on `localhost:8000`
- Database migrated and seeded
- No existing user with the test email

**Steps:**

1. Navigate to `http://localhost:3000/auth/register`
2. Fill in: name=`Test User`, email=`test@example.com`, phone=`0512345678`, password=`password123`, confirm password=`password123`, role=`customer`
3. Click "Register"

**Expected Result:**

- User created in database with `is_active = true`
- Email verification email sent
- Redirected to email verification page
- API returns `{ success: true, data: { user: {...}, token: "..." } }`

### Scenario 2 — User Login

**Preconditions:**

- Registered user exists: `test@example.com` / `password123`

**Steps:**

1. Navigate to `http://localhost:3000/auth/login`
2. Enter email=`test@example.com`, password=`password123`
3. Click "Login"

**Expected Result:**

- Token stored in cookie `auth_token`
- Redirected to `/dashboard`
- API returns `{ success: true, data: { user: {...}, token: "..." } }`

### Scenario 3 — Login with Invalid Credentials

**Steps:**

1. Navigate to `http://localhost:3000/auth/login`
2. Enter email=`test@example.com`, password=`wrongpassword`
3. Click "Login"

**Expected Result:**

- Error shown: "Invalid credentials"
- API returns 401 `{ success: false, error: { code: "AUTH_INVALID_CREDENTIALS" } }`

### Scenario 4 — Admin Registration Blocked

**Steps:**

1. Send POST to `/api/auth/register` with `role: "admin"`

**Expected Result:**

- Validation error: role field fails (admin not in allowed values)
- API returns 422 with `error.details.role`

### Scenario 5 — Forgot Password

**Steps:**

1. Navigate to `http://localhost:3000/auth/forgot-password`
2. Enter email of existing user
3. Click "Send Reset Link"

**Expected Result:**

- Success message shown regardless of email existence (anti-enumeration)
- If email exists, password reset email sent
- API returns `{ success: true }`

### Scenario 6 — Rate Limiting

**Steps:**

1. Make 6 POST requests to `/api/auth/login` within 1 minute with wrong credentials

**Expected Result:**

- First 5 requests return 401
- 6th request returns 429 `Too Many Requests`

### Scenario 7 — Profile Update

**Preconditions:**

- Authenticated user (valid token in cookie)

**Steps:**

1. Send PUT to `/api/auth/profile` with `name: "Updated Name"` and Bearer token

**Expected Result:**

- Profile updated
- API returns updated user data

### Scenario 8 — Logout

**Preconditions:**

- Authenticated user with valid token

**Steps:**

1. Navigate to dashboard
2. Click "Logout"

**Expected Result:**

- Token revoked (deleted from `personal_access_tokens` table)
- Redirected to login page
- Subsequent API calls with old token return 401

## API Test Endpoints

| Method | Endpoint                             | Auth   | Expected Status |
| ------ | ------------------------------------ | ------ | --------------- |
| POST   | `/api/auth/register`                 | No     | 201             |
| POST   | `/api/auth/login`                    | No     | 200             |
| POST   | `/api/auth/logout`                   | Bearer | 200             |
| POST   | `/api/auth/forgot-password`          | No     | 200             |
| POST   | `/api/auth/reset-password`           | No     | 200             |
| GET    | `/api/auth/profile`                  | Bearer | 200             |
| PUT    | `/api/auth/profile`                  | Bearer | 200             |
| GET    | `/api/auth/email/verify/{id}/{hash}` | Signed | 200             |
| POST   | `/api/auth/email/resend`             | Bearer | 200             |

## Common Issues

| Issue                                 | Cause                                                   | Fix                                                          |
| ------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------------ |
| `assertJsonValidationErrors` fails    | Custom Handler uses `error.details` not `errors`        | Use `assertJsonStructure(['error' => ['details' => [...]]])` |
| Rate limiting not triggering in tests | Rate limiter keyed by IP+email, different emails bypass | Use same email for all rate limit test requests              |
| Handler not registered in Laravel 13  | Auto-discovery removed                                  | Add singleton binding in `bootstrap/app.php`                 |
| PHPUnit `@dataProvider` ignored       | PHPUnit 11+ requires `#[DataProvider]` attribute        | Use PHP attribute instead of docblock                        |
| Zod not found in frontend tests       | Not bundled with Nuxt UI                                | Run `npm install zod`                                        |
| `reactive is not defined` in Vitest   | Vue auto-imports unavailable in test env                | Add stubs in `tests/setup.ts`                                |
