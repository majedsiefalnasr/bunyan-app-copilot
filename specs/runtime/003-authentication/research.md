# Research — Authentication

> **Stage:** 003-authentication
> **Phase:** 01_PLATFORM_FOUNDATION
> **Created:** 2026-04-13T00:00:00Z

## Purpose

Resolve unknowns about Laravel Sanctum setup, Nuxt auth patterns, and integration points before implementation planning.

---

## R1 — Laravel Sanctum Token-Based Auth Setup

### Question

How should Sanctum be configured for pure API token authentication (no SPA cookie auth)?

### Finding

- Sanctum is already installed and configured (`backend/config/sanctum.php` exists).
- `personal_access_tokens` migration already exists at `2026_04_10_155208_create_personal_access_tokens_table.php`.
- `User` model already uses `HasApiTokens` trait.
- Token creation: `$user->createToken('api')->plainTextToken` returns `"{id}|{token}"`.
- Auth guard: `auth:sanctum` middleware protects routes.
- Token expiration: Configurable via `SANCTUM_TOKEN_EXPIRATION` env var (in `sanctum.php`).
- No SPA/cookie authentication needed — pure Bearer token flow.

### Decision

No additional Sanctum setup required. Use existing configuration. Token expiration should be set via env variable.

---

## R2 — Password Reset Infrastructure

### Question

Does Laravel's built-in password reset infrastructure exist?

### Finding

- `password_reset_tokens` table is created in the base `0001_01_01_000000_create_users_table.php` migration.
- Laravel's `Password` facade and `Password::broker()` handle the full reset flow.
- `ResetPassword` notification is built into Laravel.
- Token expiry defaults to 60 minutes (configurable in `config/auth.php → passwords.users.expire`).

### Decision

Use Laravel's built-in password reset flow. No new migrations needed.

---

## R3 — Email Verification Infrastructure

### Question

How should email verification be implemented with Sanctum API tokens?

### Finding

- Laravel provides `MustVerifyEmail` contract on the User model.
- Built-in `VerifyEmailController` uses signed URLs.
- For API-only flow, the verification URL in the email should point to the frontend, which then calls the API.
- `email_verified_at` column already exists on users table.
- Resend endpoint: authenticated user can request new verification email.

### Decision

- Add `MustVerifyEmail` interface to the User model.
- Create custom verification endpoints that work with API tokens (not session-based).
- Verification email link points to the frontend URL, which then POSTs to the API.

---

## R4 — Nuxt Auth Token Persistence

### Question

How should the auth token be persisted in Nuxt for SSR compatibility?

### Finding

- The existing `stores/auth.ts` already uses `useCookie('auth_token')` for token storage.
- The `useApi.ts` composable already reads from this cookie and injects the `Authorization: Bearer` header.
- The 401 interceptor in `useApi.ts` already clears the cookie and redirects to `/auth/login`.
- SSR-compatible: `useCookie` works on both server and client in Nuxt.

### Decision

Token persistence is already implemented. The auth store and API composable need `login()` and `register()` actions added, but the infrastructure is in place.

---

## R5 — Existing Auth Store Gap Analysis

### Question

What's missing from the current auth store and composables?

### Finding

**Auth Store (`stores/auth.ts`):**

- ✅ Token derived from `useCookie('auth_token')`
- ✅ `user`, `isAuthenticated`, `role` state
- ✅ `setUser()`, `clearAuth()`, `hasRole()` actions
- ❌ Missing: `setToken()` action (needed for login/register to set the cookie)
- ❌ Missing: `isLoading` state (per spec US12)

**useAuth composable (`composables/useAuth.ts`):**

- ✅ `logout()` action (calls `DELETE /api/v1/auth/logout`)
- ✅ `fetchCurrentUser()` action
- ❌ Missing: `login()` action
- ❌ Missing: `register()` action
- ❌ Note: Logout uses `DELETE` method but spec says `POST` — needs alignment

**useApi composable (`composables/useApi.ts`):**

- ✅ Token injection via `Authorization: Bearer` header
- ✅ 401 interceptor with cookie clear + redirect
- ✅ Error handling for 403, 404, 429, 500
- ✅ Correlation ID support
- ✅ Fully functional — no changes needed

**AuthUser type (`types/index.ts`):**

- ❌ Missing: `phone`, `is_active`, `email_verified_at`, `created_at` fields (spec requires them)

### Decision

- Add `setToken()` and `isLoading` to auth store
- Add `login()` and `register()` to useAuth composable
- Extend `AuthUser` type with missing fields
- Align logout HTTP method (POST per spec API contract)

---

## R6 — Admin Registration Blocking

### Question

Should admin registration be blocked at signup?

### Finding

Per spec: "Admin self-registration blocked (4 roles at signup)." The spec still lists 5 roles in role selector (US10: "Role selector presents all 5 roles with Arabic labels"). However, the user's context says "Admin self-registration blocked."

### Decision

The `RegisterRequest` form request will validate that `role` is one of: `customer`, `contractor`, `supervising_architect`, `field_engineer`. Admin role is excluded from the registration validation rule. Frontend role selector will show 4 roles only (excluding admin).

---

## R7 — Rate Limiting Configuration

### Question

How should rate limiting be configured for auth endpoints?

### Finding

Laravel 11 rate limiting is configured in `AppServiceProvider::boot()` via `RateLimiter::for()`. Spec requires:

- Login: 5 attempts/minute per IP
- Forgot password: 3 attempts/minute per email
- Email verification resend: 3 attempts/minute

### Decision

Define named rate limiters in `AppServiceProvider`:

- `auth-login` → 5 per minute by IP
- `auth-forgot-password` → 3 per minute by IP+email
- `auth-email-resend` → 3 per minute by user

Apply via `throttle:{name}` middleware on respective routes.

---

## R8 — Frontend Route Structure

### Question

Should auth pages be under `/auth/` prefix or at root level?

### Finding

- Spec says: `/login`, `/register`, `/forgot-password`, `/reset-password`
- Existing `useAuth.ts` navigates to `/${locale}/auth/login` (with locale prefix and `/auth/` prefix)
- Existing `useErrorHandler.ts` maps `AUTH_INVALID_CREDENTIALS` to `/auth/login`

### Decision

Use `/auth/` prefix to match existing navigation patterns: `/auth/login`, `/auth/register`, `/auth/forgot-password`, `/auth/reset-password`, `/auth/verify-email`. Pages go in `frontend/app/pages/auth/`.

---

## Summary

| #   | Topic              | Status      | Action Required                     |
| --- | ------------------ | ----------- | ----------------------------------- |
| R1  | Sanctum setup      | ✅ Resolved | None — already configured           |
| R2  | Password reset     | ✅ Resolved | None — migration exists             |
| R3  | Email verification | ✅ Resolved | Add `MustVerifyEmail` to User model |
| R4  | Token persistence  | ✅ Resolved | Already implemented via `useCookie` |
| R5  | Store gap analysis | ✅ Resolved | Add missing actions/state/types     |
| R6  | Admin registration | ✅ Resolved | Block admin at validation layer     |
| R7  | Rate limiting      | ✅ Resolved | Add named rate limiters             |
| R8  | Route structure    | ✅ Resolved | Use `/auth/` prefix                 |

**All unknowns resolved. Ready for planning.**
