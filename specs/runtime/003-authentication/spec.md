# Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Stage File:** `specs/phases/01_PLATFORM_FOUNDATION/STAGE_03_AUTHENTICATION.md` > **Branch:** `spec/003-authentication` > **Created:** 2026-04-12T00:00:00Z

## Objective

Implement a complete authentication system using Laravel Sanctum for token-based API authentication. The system supports registration, login, logout, password reset, email verification, and user profile management for all five platform roles (Customer, Contractor, Supervising Architect, Field Engineer, Admin). This stage establishes the authentication foundation; role-based access control (RBAC) enforcement on domain routes is deferred to a downstream stage.

## Scope

### In Scope

- User registration with input validation (name, email, password, phone, role selection)
- User login with Sanctum API token generation
- User logout with token revocation
- Password reset flow (email-based: request reset → receive email → submit new password)
- Email verification flow (send verification email → user clicks link → mark verified)
- Authenticated user profile retrieval and update
- Auth middleware configuration (`auth:sanctum`) for protected routes
- Pinia auth store with token persistence, user state, and role tracking
- Frontend auth pages: login, register, forgot password, reset password, email verification
- API client composable with Authorization header injection and 401 interceptor
- Nuxt route middleware for guest-only and authenticated-only pages
- Rate limiting on login and registration endpoints
- Arabic/RTL layout for all auth pages

### Out of Scope

- Role-based access control (RBAC) middleware enforcement on domain routes (future stage)
- OAuth / social login (Google, Apple, etc.)
- Two-factor authentication (2FA)
- Admin user management CRUD
- Session-based authentication (API tokens only)
- User avatar upload (file upload stage)
- Account deletion / deactivation flows

## User Stories

### US1 — User Registration

**As a** new user, **I want** to register an account by providing my name, email, phone, password, and selecting a role, **so that** I can access the Bunyan platform.

**Acceptance Criteria:**

- [ ] User can submit registration form with: name (required, max 255), email (required, unique, valid format), phone (required, valid Saudi format), password (required, min 8, confirmed), role (required, one of the 5 valid roles)
- [ ] System returns a Sanctum API token on successful registration
- [ ] System sends email verification notification after registration
- [ ] Duplicate email returns `CONFLICT_ERROR` with field-level detail on `email`
- [ ] Invalid input returns `VALIDATION_ERROR` with field-level details
- [ ] Passwords are hashed before storage (bcrypt via Laravel's `hashed` cast)
- [ ] Registered user's `is_active` defaults to `true`
- [ ] Response follows error contract: `{ success, data, error }`

### US2 — User Login

**As a** registered user, **I want** to log in with my email and password, **so that** I can receive an API token and access protected features.

**Acceptance Criteria:**

- [ ] User can submit email and password to obtain a Sanctum API token
- [ ] Token is returned in the response body (not as a cookie)
- [ ] Invalid credentials return `AUTH_INVALID_CREDENTIALS` (401)
- [ ] Inactive user (`is_active = false`) is rejected with `AUTH_UNAUTHORIZED` (403)
- [ ] Unverified email does not block login but response includes `email_verified: false` flag
- [ ] Login endpoint is rate-limited (max 5 attempts per minute per IP)
- [ ] Response includes user object with id, name, email, phone, role, email_verified_at

### US3 — User Logout

**As an** authenticated user, **I want** to log out, **so that** my current API token is revoked and cannot be used again.

**Acceptance Criteria:**

- [ ] Authenticated user can call logout to revoke their current token
- [ ] The specific token used in the request is deleted (not all tokens)
- [ ] Subsequent requests with the revoked token return 401
- [ ] Unauthenticated requests to logout return 401

### US4 — Forgot Password (Request Reset)

**As a** user who forgot their password, **I want** to request a password reset link via email, **so that** I can regain access to my account.

**Acceptance Criteria:**

- [ ] User submits their email address to request a password reset
- [ ] If email exists, a password reset notification is sent with a tokenized link
- [ ] If email does not exist, the response is identical (no email enumeration)
- [ ] Reset tokens expire after 60 minutes
- [ ] Endpoint is rate-limited (max 3 requests per minute per email)
- [ ] Response always returns `{ success: true }` regardless of email existence

### US5 — Reset Password

**As a** user with a valid reset token, **I want** to submit a new password, **so that** my account password is updated.

**Acceptance Criteria:**

- [ ] User submits: email, token, new password, password confirmation
- [ ] Invalid or expired token returns `VALIDATION_ERROR` with message
- [ ] Password is updated and all existing tokens for the user are revoked
- [ ] User receives a password-changed notification email
- [ ] Response follows error contract

### US6 — Email Verification

**As a** newly registered user, **I want** to verify my email address, **so that** my account is fully activated.

**Acceptance Criteria:**

- [ ] Verification email is sent automatically after registration
- [ ] User can click the verification link to verify their email
- [ ] Verification endpoint validates the signature (signed URL)
- [ ] Already-verified users re-clicking the link receive a success response (idempotent)
- [ ] User can request a new verification email (resend endpoint)
- [ ] Resend endpoint is rate-limited (max 3 per minute)

### US7 — Get Authenticated User Profile

**As an** authenticated user, **I want** to retrieve my profile information, **so that** I can view my account details.

**Acceptance Criteria:**

- [ ] Authenticated user can fetch their profile via `GET /api/v1/auth/user`
- [ ] Response includes: id, name, email, phone, role, is_active, email_verified_at, created_at
- [ ] Password and remember_token are never exposed
- [ ] Unauthenticated request returns 401

### US8 — Update User Profile

**As an** authenticated user, **I want** to update my name and phone number, **so that** my profile information stays current.

**Acceptance Criteria:**

- [ ] Authenticated user can update: name, phone
- [ ] Email and role are NOT updatable via this endpoint (immutable after registration)
- [ ] Validation rules match registration rules for editable fields
- [ ] Response returns the updated user object via API Resource
- [ ] Unauthenticated request returns 401

### US9 — Frontend Login Page

**As a** user, **I want** a login page with form validation, **so that** I can authenticate and access the platform.

**Acceptance Criteria:**

- [ ] Login page is accessible at `/login`
- [ ] Page is guest-only (authenticated users are redirected to dashboard)
- [ ] Form includes email and password fields with client-side validation
- [ ] Submit button shows loading state during API call
- [ ] Server-side validation errors are displayed per-field
- [ ] Successful login stores token in Pinia auth store and redirects to dashboard
- [ ] Page is fully RTL with Arabic labels/placeholders
- [ ] "Forgot password?" link navigates to `/forgot-password`
- [ ] "Register" link navigates to `/register`

### US10 — Frontend Registration Page

**As a** new user, **I want** a registration page with form validation, **so that** I can create an account.

**Acceptance Criteria:**

- [ ] Registration page is accessible at `/register`
- [ ] Page is guest-only (authenticated users are redirected)
- [ ] Form includes: name, email, phone, password, password confirmation, role selector
- [ ] Role selector presents all 5 roles with Arabic labels
- [ ] Client-side validation mirrors server-side rules
- [ ] Server-side validation errors are displayed per-field
- [ ] Successful registration stores token and redirects to email verification notice page
- [ ] Page is fully RTL with Arabic labels/placeholders

### US11 — Frontend Forgot Password Page

**As a** user, **I want** a forgot password page, **so that** I can request a password reset email.

**Acceptance Criteria:**

- [ ] Page is accessible at `/forgot-password`
- [ ] Form includes email field only
- [ ] On submit, displays success message regardless of email existence (matches API behavior)
- [ ] Page is fully RTL with Arabic labels

### US12 — Frontend Auth State Management

**As a** developer, **I want** a Pinia auth store that manages token and user state, **so that** auth state is consistent across the frontend.

**Acceptance Criteria:**

- [ ] Auth store tracks: `token`, `user`, `isAuthenticated`, `isLoading`
- [ ] Token is persisted to `localStorage` (or `useCookie` for SSR)
- [ ] `login()`, `register()`, `logout()`, `fetchUser()` actions are implemented
- [ ] On 401 response, token is cleared and user is redirected to login
- [ ] Store exposes `userRole` computed for downstream RBAC usage

### US13 — Frontend Route Protection

**As a** developer, **I want** Nuxt route middleware that protects pages, **so that** unauthenticated users cannot access protected routes.

**Acceptance Criteria:**

- [ ] `auth` middleware redirects unauthenticated users to `/login`
- [ ] `guest` middleware redirects authenticated users to dashboard
- [ ] Middleware checks auth store state (not just token existence — validates via API if needed)
- [ ] Login page, register page, and forgot/reset password pages use `guest` middleware
- [ ] All other pages default to `auth` middleware (configurable per-page)

## Technical Requirements

### Backend (Laravel)

- [ ] **Routes:** All auth routes under `/api/v1/auth/` prefix, within `api` middleware group
- [ ] **AuthController:** Thin controller — delegates to `AuthService` for all logic
- [ ] **AuthService:** Handles registration, login, logout, profile retrieval/update logic. Injects `UserRepository`.
- [ ] **UserRepository:** Eloquent queries for user lookup (by email, by id), creation, and updates
- [ ] **Form Requests:** `RegisterRequest`, `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest`, `UpdateProfileRequest`
- [ ] **API Resource:** `UserResource` — formats user data for API responses (excludes password, remember_token)
- [ ] **Sanctum token creation:** Token name includes device/client identifier (default: `"api"`)
- [ ] **Rate limiting:** `throttle:5,1` on login, `throttle:3,1` on forgot-password and email verification resend
- [ ] **Email verification:** Uses Laravel's built-in `MustVerifyEmail` contract and `VerificationController`
- [ ] **Password reset:** Uses Laravel's built-in `Password` broker and `ResetPassword` notification
- [ ] **Error responses:** All errors follow the unified error contract with appropriate error codes from the registry
- [ ] **Role assignment:** During registration, role is set explicitly (`$user->role = UserRole::from($validated['role'])`) — NOT via mass assignment (SEC-FINDING-A)
- [ ] **Middleware:** Protected routes use `auth:sanctum` middleware
- [ ] **Tests:** Feature tests for all endpoints covering success, validation errors, auth errors, and rate limits

### Frontend (Nuxt.js)

- [ ] **Pages:** `/login`, `/register`, `/forgot-password`, `/reset-password` (with token query param)
- [ ] **Auth store:** Pinia store at `stores/auth.ts` with full token lifecycle management
- [ ] **API composable:** `composables/useApi.ts` — wraps `$fetch`/`useFetch` with Authorization header and 401 interceptor
- [ ] **Middleware:** `middleware/auth.ts` (require auth), `middleware/guest.ts` (require guest)
- [ ] **Form validation:** VeeValidate + Zod schemas for all auth forms
- [ ] **RTL / Arabic support:** All pages use `dir="rtl"`, Arabic labels via i18n keys in `locales/ar.json`
- [ ] **Nuxt UI components:** Use `UForm`, `UFormField`, `UInput`, `UButton`, `USelect`, `UCard` for all forms
- [ ] **Loading states:** All submit buttons show loading indicator during API calls
- [ ] **Error display:** Server-side validation errors mapped to form fields; toast notifications for generic errors

## API Contract

### POST `/api/v1/auth/register`

**Request:**

```json
{
  "name": "أحمد محمد",
  "email": "ahmad@example.com",
  "phone": "0512345678",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "customer"
}
```

**Success Response (201):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmad@example.com",
      "phone": "0512345678",
      "role": "customer",
      "is_active": true,
      "email_verified_at": null,
      "created_at": "2026-04-12T00:00:00Z"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  },
  "error": null
}
```

### POST `/api/v1/auth/login`

**Request:**

```json
{
  "email": "ahmad@example.com",
  "password": "SecurePass123!"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmad@example.com",
      "phone": "0512345678",
      "role": "customer",
      "is_active": true,
      "email_verified_at": "2026-04-12T00:00:00Z",
      "created_at": "2026-04-12T00:00:00Z"
    },
    "token": "2|def456...",
    "token_type": "Bearer"
  },
  "error": null
}
```

### POST `/api/v1/auth/logout`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "message": "تم تسجيل الخروج بنجاح"
  },
  "error": null
}
```

### POST `/api/v1/auth/forgot-password`

**Request:**

```json
{
  "email": "ahmad@example.com"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "message": "تم إرسال رابط إعادة تعيين كلمة المرور"
  },
  "error": null
}
```

### POST `/api/v1/auth/reset-password`

**Request:**

```json
{
  "email": "ahmad@example.com",
  "token": "reset-token-here",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "message": "تم إعادة تعيين كلمة المرور بنجاح"
  },
  "error": null
}
```

### GET `/api/v1/auth/user`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmad@example.com",
      "phone": "0512345678",
      "role": "customer",
      "is_active": true,
      "email_verified_at": "2026-04-12T00:00:00Z",
      "created_at": "2026-04-12T00:00:00Z"
    }
  },
  "error": null
}
```

### PUT `/api/v1/auth/user`

**Headers:** `Authorization: Bearer {token}`

**Request:**

```json
{
  "name": "أحمد محمد العلي",
  "phone": "0598765432"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد العلي",
      "email": "ahmad@example.com",
      "phone": "0598765432",
      "role": "customer",
      "is_active": true,
      "email_verified_at": "2026-04-12T00:00:00Z",
      "created_at": "2026-04-12T00:00:00Z"
    }
  },
  "error": null
}
```

### GET `/api/v1/auth/email/verify/{id}/{hash}` (Signed URL)

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "message": "تم التحقق من البريد الإلكتروني بنجاح"
  },
  "error": null
}
```

### POST `/api/v1/auth/email/resend`

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "message": "تم إرسال رابط التحقق"
  },
  "error": null
}
```

## Dependencies

- **Upstream:** STAGE_02_DATABASE_SCHEMA — Users table with role enum, is_active, phone columns; Sanctum personal_access_tokens table
- **Downstream:** All authenticated features — RBAC stage, project management, workflow engine, e-commerce

## Non-Functional Requirements

- [ ] Login/register API response time < 300ms (includes token generation)
- [ ] Profile fetch response time < 200ms
- [ ] Arabic/RTL layout support on all auth pages
- [ ] Rate limiting enforced on security-sensitive endpoints
- [ ] Error contract compliance on all endpoints (`{ success, data, error }`)
- [ ] No password or token leakage in API responses or logs
- [ ] Mobile-responsive design for all auth pages
- [ ] Token expiration configurable via `SANCTUM_TOKEN_EXPIRATION` env variable
- [ ] All user-facing text via i18n translation keys (ar/en)
- [ ] Passwords validated: minimum 8 characters, confirmed

## Security Considerations

- Role is NOT mass-assignable — set via explicit assignment only (SEC-FINDING-A)
- Password reset tokens expire after 60 minutes
- Rate limiting prevents brute force on login (5/min) and password reset (3/min)
- No email enumeration on forgot-password endpoint (always returns success)
- Token revocation on password reset (invalidate all sessions)
- HTTPS enforced for all auth endpoints (infrastructure-level)
- `password` and `remember_token` hidden from serialization on User model

## Open Questions

None — all requirements are fully specified based on the stage file, existing User model, UserRole enum, and platform constraints.

## Clarifications

### Session 2026-04-12

Five ambiguities were identified during a structured spec audit and auto-resolved using platform constraints and security best practices.

---

**Q1: Can users self-register as Admin?**
_Category: Security / RBAC_

The spec states the role selector presents "all 5 roles" (US10), but admin self-registration is a critical security risk.

**Resolution:** Admin role is **excluded from self-registration**. The registration endpoint and frontend role selector only permit 4 roles: `customer`, `contractor`, `supervising_architect`, `field_engineer`. Admin accounts are created exclusively via database seeder or by an existing admin through a future admin management feature. The `RegisterRequest` Form Request must validate `role` against the 4 non-admin values: `Rule::in(['customer', 'contractor', 'supervising_architect', 'field_engineer'])`.

**Affected artifacts:**

- US1 acceptance criteria: role validation restricts to 4 roles
- US10 acceptance criteria: role selector shows 4 roles (not 5)
- `RegisterRequest` validation rule
- Registration API contract (422 on `role: "admin"`)

---

**Q2: Where does each role redirect after login?**
_Category: UX Flow / Frontend Routing_

The spec references "redirected to dashboard" in multiple user stories (US9, US10, US13) but does not specify whether roles have different dashboard routes.

**Resolution:** All roles redirect to a **single `/dashboard` route** after login. Role-specific dashboard views and routing are deferred to the RBAC/Dashboard stage (downstream). The auth stage is only responsible for establishing the authenticated session and redirecting to `/dashboard`. The dashboard page itself will be a placeholder until the next stage.

**Affected artifacts:**

- US9, US10, US13: "dashboard" means `/dashboard` (single route)
- `auth` middleware: redirects to `/dashboard` on success
- `guest` middleware: redirects authenticated users to `/dashboard`

---

**Q3: Token persistence — `localStorage` or `useCookie`?**
_Category: Frontend Architecture / SSR Compatibility_

US12 states token is persisted to "`localStorage` (or `useCookie` for SSR)" without a definitive choice.

**Resolution:** Use **`useCookie`** (Nuxt's built-in composable) for token persistence. This is the Nuxt-native approach that supports SSR and ensures the token is available during server-side rendering. Configuration: `useCookie('auth_token', { maxAge: 60 * 60 * 24 * 7, secure: true, sameSite: 'lax' })`. The cookie is **not** `httpOnly` because client-side JavaScript must read it to inject the `Authorization: Bearer` header on API requests.

**Affected artifacts:**

- US12: Token persistence strategy decided as `useCookie`
- Auth store (`stores/auth.ts`): uses `useCookie` instead of `localStorage`
- API composable: reads token from cookie for header injection

---

**Q4: What constitutes a "valid Saudi phone format"?**
_Category: Validation / Data Integrity_

US1 and US8 reference "valid Saudi format" for phone validation without specifying the exact pattern.

**Resolution:** Saudi mobile phone validation accepts two formats:

- **Local format:** `05XXXXXXXX` — 10 digits starting with `05`. Regex: `/^05\d{8}$/`
- **International format:** `+9665XXXXXXXX` — country code prefix. Regex: `/^\+9665\d{8}$/`

The combined validation regex is: `/^(\+9665|05)\d{8}$/`

This covers all Saudi mobile carriers (STC, Mobily, Zain). Landline numbers are not accepted. The `RegisterRequest` and `UpdateProfileRequest` Form Requests use this regex in their validation rules: `'phone' => ['required', 'regex:/^(\+9665|05)\d{8}$/']`.

**Affected artifacts:**

- US1, US8: Phone validation rule defined
- `RegisterRequest`, `UpdateProfileRequest`: regex rule for phone
- Frontend Zod schemas: matching regex validation

---

**Q5: Is there a limit on concurrent active tokens per user?**
_Category: Token Lifecycle / Security_

The spec does not address how many active API tokens a single user can hold simultaneously (e.g., logged in from multiple devices).

**Resolution:** **No hard limit** on concurrent tokens. Each successful login creates a new Sanctum personal access token. This mirrors standard Sanctum behavior and supports multi-device usage. Token cleanup behavior:

- **Logout (US3):** Revokes only the current request's token (not all tokens)
- **Password reset (US5):** Revokes **all** tokens for the user (force re-authentication on all devices)
- **"Logout from all devices":** Out of scope for this stage; may be added in a future profile management stage

A periodic token pruning job (e.g., deleting tokens older than 30 days) is recommended but deferred to an infrastructure/maintenance stage.

**Affected artifacts:**

- US2, US3, US5: Token lifecycle behavior clarified
- No new endpoints or middleware required for this stage
