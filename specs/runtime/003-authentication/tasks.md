# Tasks: Authentication

> **Stage:** 003-authentication
> **Phase:** 01_PLATFORM_FOUNDATION
> **Input:** `specs/runtime/003-authentication/` — spec.md (13 US, 64 AC), plan.md, data-model.md, research.md
> **Generated:** 2026-04-13

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel with other [P] tasks in the same wave
- **[Story]**: User story label (omitted for foundational tasks)
- All file paths are relative to repository root

---

## Phase 1: Foundational Backend

**Purpose**: Model changes, shared API resource, and rate limiter configuration that all auth endpoints depend on.

**⚠ BLOCKING**: No backend service or controller work can begin until this phase is complete.

- [ ] T001 [P] Add `MustVerifyEmail` interface to User model in `backend/app/Models/User.php` — import `Illuminate\Contracts\Auth\MustVerifyEmail`, add `implements MustVerifyEmail` to class declaration
- [ ] T002 [P] Create `UserResource` API resource in `backend/app/Http/Resources/UserResource.php` — expose id, name, email, phone, role (->value), is_active, email_verified_at (ISO string), created_at (ISO string); exclude password and remember_token
- [ ] T003 [P] Register auth rate limiters in `backend/app/Providers/AppServiceProvider.php` — define `auth-login` (5/min by IP), `auth-register` (5/min by IP), `auth-forgot-password` (3/min by IP+email), `auth-email-resend` (3/min by user ID) using `RateLimiter::for()`

**Checkpoint**: Foundational backend infrastructure ready — service layer can begin.

---

## Phase 2: Backend Validation & Service Layer

**Purpose**: Form request validators and core business logic service.

### Form Requests (all parallel — independent files)

- [ ] T004 [P] [US1] Create `RegisterRequest` in `backend/app/Http/Requests/Auth/RegisterRequest.php` — validate name (required, string, max:255), email (required, email, unique:users), phone (required, Saudi regex `^(\+9665|05)\d{8}$`), password (required, min:8, confirmed), role (required, in:customer,contractor,supervising_architect,field_engineer — admin excluded)
- [ ] T005 [P] [US2] Create `LoginRequest` in `backend/app/Http/Requests/Auth/LoginRequest.php` — validate email (required, email), password (required, string)
- [ ] T006 [P] [US4] Create `ForgotPasswordRequest` in `backend/app/Http/Requests/Auth/ForgotPasswordRequest.php` — validate email (required, email)
- [ ] T007 [P] [US5] Create `ResetPasswordRequest` in `backend/app/Http/Requests/Auth/ResetPasswordRequest.php` — validate email (required, email), token (required, string), password (required, min:8, confirmed)
- [ ] T008 [P] [US8] Create `UpdateProfileRequest` in `backend/app/Http/Requests/Auth/UpdateProfileRequest.php` — validate name (sometimes, required, string, max:255), phone (sometimes, required, Saudi regex)

### Service Layer

- [ ] T009 [US1-US8] Create `AuthService` in `backend/app/Services/AuthService.php` — implement register (create user, set role explicitly via `UserRole::from()`, send verification, create token), login (find by email, verify password, check is_active, create token), logout (delete current token), forgotPassword (Password::sendResetLink, always return success), resetPassword (Password::reset, revoke all tokens), getProfile, updateProfile, verifyEmail (validate hash, mark verified), resendVerification — inject `UserRepository`, use `UserResource` for formatting

**Checkpoint**: Backend business logic complete — controller can be wired.

---

## Phase 3: Backend Controller & Routes

**Purpose**: Thin controller and route registration with middleware chains.

- [ ] T010 [US1-US8] Create `AuthController` in `backend/app/Http/Controllers/Api/AuthController.php` — thin controller with 9 actions: register (201), login (200), logout (200), forgotPassword (200), resetPassword (200), user (200), updateProfile (200), verifyEmail (200), resendVerification (200) — each delegates to `AuthService`, uses form requests for validation, returns `{ success, data, error }` contract via `ApiResponseTrait`
- [ ] T011 [US1-US8] Add auth route group to `backend/routes/api.php` — prefix `v1/auth`, register POST routes (register with throttle:auth-register, login with throttle:auth-login, forgot-password with throttle:auth-forgot-password, reset-password), GET email/verify/{id}/{hash} with signed middleware, auth:sanctum protected routes (POST logout, GET user, PUT user, POST email/resend with throttle:auth-email-resend)

**Checkpoint**: Backend API fully functional — all 9 endpoints available. Run `php artisan route:list --path=api/v1/auth` to verify.

---

## Phase 4: Backend Tests

**Purpose**: Feature tests for all endpoints covering success, validation, auth errors, and rate limits. Unit tests for service layer.

- [ ] T012 [P] [US1] Create `RegisterTest` in `backend/tests/Feature/Auth/RegisterTest.php` — test successful registration (201 + token + user), validation errors (422 for each field), duplicate email (409 CONFLICT_ERROR), admin role blocked (422), email verification sent, password hashed
- [ ] T013 [P] [US2] Create `LoginTest` in `backend/tests/Feature/Auth/LoginTest.php` — test successful login (200 + token + user), invalid credentials (401 AUTH_INVALID_CREDENTIALS), inactive user (403 AUTH_UNAUTHORIZED), rate limiting (429 after 5 attempts), email_verified flag included
- [ ] T014 [P] [US3] Create `LogoutTest` in `backend/tests/Feature/Auth/LogoutTest.php` — test successful logout (200, token revoked), unauthenticated (401), revoked token rejected on subsequent requests
- [ ] T015 [P] [US4] Create `ForgotPasswordTest` in `backend/tests/Feature/Auth/ForgotPasswordTest.php` — test existing email (200, notification sent), non-existing email (200, same response — no enumeration), rate limiting (429 after 3 attempts)
- [ ] T016 [P] [US5] Create `ResetPasswordTest` in `backend/tests/Feature/Auth/ResetPasswordTest.php` — test successful reset (200, password changed, all tokens revoked), invalid token (422), expired token (422), validation errors
- [ ] T017 [P] [US6] Create `EmailVerificationTest` in `backend/tests/Feature/Auth/EmailVerificationTest.php` — test successful verification (200, email_verified_at set), invalid signature (403), already verified idempotent (200), resend (200), resend rate limiting (429 after 3 attempts)
- [ ] T018 [P] [US7][US8] Create `ProfileTest` in `backend/tests/Feature/Auth/ProfileTest.php` — test GET user (200 with all fields), PUT user success (200, name/phone updated), PUT user validation errors (422), email/role immutable, unauthenticated (401)
- [ ] T019 [P] Create `AuthServiceTest` in `backend/tests/Unit/Services/AuthServiceTest.php` — unit tests for register (role explicit assignment, token creation), login (password verification, is_active check), logout (token deletion), profile update

**Checkpoint**: Backend fully tested. Run `php artisan test --filter=Auth` to validate all tests pass.

---

## Phase 5: Frontend Infrastructure (US12, US13)

**Purpose**: Types, state management, API integration, validation schemas, middleware, layout, and i18n — all must be in place before building pages.

### Types & Schemas (parallel — independent files)

- [ ] T020 [P] [US12] Extend `AuthUser` interface in `frontend/types/index.ts` — add `phone: string`, `is_active: boolean`, `email_verified_at: string | null`, `created_at: string` fields
- [ ] T021 [P] Create Zod validation schemas in `frontend/app/config/validation/auth.ts` — loginSchema (email, password), registerSchema (name, email, phone with Saudi regex, password min 8, password_confirmation, role enum), forgotPasswordSchema (email), resetPasswordSchema (email, token, password, password_confirmation)
- [ ] T022 [P] Add auth i18n keys to Arabic locale in `frontend/locales/ar.json` — add `auth.login.*`, `auth.register.*`, `auth.forgot_password.*`, `auth.reset_password.*`, `auth.verify_email.*`, `auth.logout.*`, `auth.roles.*`, `auth.validation.*` keys per plan.md i18n section
- [ ] T023 [P] Add auth i18n keys to English locale in `frontend/locales/en.json` — mirror all Arabic keys with English translations

### State Management & Composables

- [ ] T024 [US12] Add `setToken(token)` action and `isLoading` ref to auth store in `frontend/stores/auth.ts` — setToken writes to `useCookie('auth_token')`, isLoading tracks async operations (depends on T020)
- [ ] T025 [US12] Add auth actions to `useAuth` composable in `frontend/composables/useAuth.ts` — implement `login(email, password)`, `register(data)`, `forgotPassword(email)`, `resetPassword(data)`, `resendVerification()` actions; align logout to POST method per spec; each action calls API, updates store, handles errors (depends on T024)

### Middleware & Layout (parallel — independent files)

- [ ] T026 [P] [US13] Create `auth` route middleware in `frontend/middleware/auth.ts` — check `isAuthenticated` from auth store, redirect to `/auth/login` if false, validate via API `fetchUser()` if token exists but no user loaded
- [ ] T027 [P] [US13] Create `guest` route middleware in `frontend/middleware/guest.ts` — check `isAuthenticated` from auth store, redirect to `/dashboard` if true
- [ ] T028 [P] Create auth layout in `frontend/layouts/auth.vue` — minimal centered layout with RTL support, app logo, `<slot />` for page content; create `AuthCard.vue` component in `frontend/app/components/auth/AuthCard.vue` — reusable `UCard` wrapper with title prop and consistent styling

**Checkpoint**: Frontend infrastructure ready — all pages can now be built.

---

## Phase 6: Frontend Auth Pages

**Purpose**: User-facing auth pages using Nuxt UI components, Zod validation, i18n, and RTL layout.

- [ ] T029 [P] [US9] Create login page in `frontend/app/pages/auth/login.vue` — `UForm` with `UFormField`/`UInput` for email + password, `UButton` submit with loading state, loginSchema validation, server error mapping to fields, guest middleware, RTL Arabic layout, "Forgot password?" + "Register" links, on success: store token → redirect to dashboard
- [ ] T030 [P] [US10] Create register page in `frontend/app/pages/auth/register.vue` — `UForm` with fields for name, email, phone, password, password_confirmation, role (`USelect` with 4 roles excluding admin, Arabic labels), registerSchema validation, server error mapping, guest middleware, RTL, on success: store token → redirect to verify-email
- [ ] T031 [P] [US11] Create forgot-password page in `frontend/app/pages/auth/forgot-password.vue` — `UForm` with email field only, forgotPasswordSchema, guest middleware, RTL, on submit: show success message regardless, "Back to login" link
- [ ] T032 [P] [US5] Create reset-password page in `frontend/app/pages/auth/reset-password.vue` — `UForm` with email (hidden/prefilled from query), token (hidden from query), password, password_confirmation, resetPasswordSchema, guest middleware, RTL, on success: redirect to login with success toast
- [ ] T033 [P] [US6] Create verify-email page in `frontend/app/pages/auth/verify-email.vue` — auth middleware, display verification notice, "Resend verification email" `UButton` with loading state, handle verification callback from email link (extract id/hash from route, call API)
- [ ] T034 [P] Create dashboard placeholder page in `frontend/app/pages/dashboard.vue` — minimal page with auth middleware, display user name from auth store, logout button — serves as redirect target for successful login

**Checkpoint**: All auth pages functional. Manual test: register → verify email → login → view dashboard → logout.

---

## Phase 7: Frontend Tests & Polish

**Purpose**: Unit tests for auth store and composable, integration validation.

- [ ] T035 [P] [US12] Create auth store unit tests in `frontend/tests/unit/stores/auth.test.ts` — test setToken, setUser, clearAuth, isAuthenticated computed, userRole computed, isLoading state
- [ ] T036 [P] [US12] Create useAuth composable tests in `frontend/tests/unit/composables/useAuth.test.ts` — test login (success + error), register (success + error), logout, forgotPassword, resetPassword, resendVerification, 401 interceptor clears token
- [ ] T037 [P] [US9][US10][US11] Create auth page component tests in `frontend/tests/components/auth/` — test login form renders RTL, register form shows 4 roles (no admin), forgot-password form submits email, form validation errors display correctly

**Checkpoint**: Full auth feature complete. Run all validators:

```bash
cd backend && php artisan test --filter=Auth
cd frontend && npx vitest run --reporter=verbose
```

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Foundational Backend)     → No dependencies — start immediately
Phase 2 (Validation & Service)     → Depends on Phase 1 (T001, T002 required by T009)
Phase 3 (Controller & Routes)      → Depends on Phase 2 (T004-T009 required by T010)
Phase 4 (Backend Tests)            → Depends on Phase 3 (routes must exist for feature tests)
Phase 5 (Frontend Infrastructure)  → T020-T023, T026-T028 can start in parallel with Phase 2+
                                     T024 depends on T020; T025 depends on T024
Phase 6 (Frontend Pages)           → Depends on Phase 5 (T025, T026-T028 required)
Phase 7 (Frontend Tests)           → Depends on Phase 6 (pages must exist)
```

### Cross-Stack Parallelism

Backend (Phases 1-4) and Frontend Infrastructure (Phase 5, T020-T023 + T026-T028) can proceed **in parallel** since they have no cross-dependencies. Frontend composables (T024-T025) and pages (Phase 6) need the backend API running for integration testing but can be coded in parallel.

### Task Dependency Graph

```
T001 ─┐
T002 ─┼──→ T009 ──→ T010 ──→ T011 ──→ T012-T019 (parallel)
T003 ─┘              ↑
T004 ─┐              │
T005 ─┤              │
T006 ─┼──────────────┘
T007 ─┤
T008 ─┘

T020 ──→ T024 ──→ T025 ──┐
T021 ─────────────────────┤
T022 ─────────────────────┼──→ T029-T034 (parallel) ──→ T035-T037 (parallel)
T023 ─────────────────────┤
T026 ─────────────────────┤
T027 ─────────────────────┤
T028 ─────────────────────┘
```

### Parallel Execution Examples

**Wave A** (immediate start — 6 parallel tasks):

```
T001 + T002 + T003 + T020 + T021 + T022 + T023 + T026 + T027 + T028
```

**Wave B** (after T001-T003 complete):

```
T004 + T005 + T006 + T007 + T008
```

**Wave C** (after Wave B + T009):

```
T009 → T010 → T011
```

**Wave D** (after T011 complete — backend tests, after T025 complete — frontend pages):

```
Backend:   T012 + T013 + T014 + T015 + T016 + T017 + T018 + T019
Frontend:  T024 → T025 → T029 + T030 + T031 + T032 + T033 + T034
```

**Wave E** (after all pages):

```
T035 + T036 + T037
```

---

## Implementation Strategy

### User Story → Task Mapping

| User Story | Description              | Tasks                              |
| ---------- | ------------------------ | ---------------------------------- |
| US1        | Registration             | T004, T009, T010, T011, T012       |
| US2        | Login                    | T005, T009, T010, T011, T013       |
| US3        | Logout                   | T009, T010, T011, T014             |
| US4        | Forgot Password          | T006, T009, T010, T011, T015       |
| US5        | Reset Password           | T007, T009, T010, T011, T016, T032 |
| US6        | Email Verification       | T001, T009, T010, T011, T017, T033 |
| US7        | Get Profile              | T009, T010, T011, T018             |
| US8        | Update Profile           | T008, T009, T010, T011, T018       |
| US9        | Frontend Login Page      | T029, T037                         |
| US10       | Frontend Register Page   | T030, T037                         |
| US11       | Frontend Forgot Password | T031, T037                         |
| US12       | Auth State Management    | T020, T024, T025, T035, T036       |
| US13       | Route Protection         | T026, T027                         |

### Shared Infrastructure Tasks (no US label)

| Task | Description            | Serves        |
| ---- | ---------------------- | ------------- |
| T002 | UserResource           | US1-US8       |
| T003 | Rate limiters          | US2, US4, US6 |
| T021 | Zod schemas            | US9-US11      |
| T022 | Arabic i18n            | US9-US11      |
| T023 | English i18n           | US9-US11      |
| T028 | Auth layout + AuthCard | US9-US11      |
| T034 | Dashboard placeholder  | US9, US13     |

### Security Checklist

- [ ] Admin role excluded from RegisterRequest validation (T004)
- [ ] Role set via explicit `$user->role = UserRole::from()` — never mass-assigned (T009)
- [ ] No email enumeration on forgot-password — always returns success (T009, T015)
- [ ] All tokens revoked on password reset (T009, T016)
- [ ] Rate limiting on login (5/min), forgot-password (3/min), email resend (3/min) (T003, T011)
- [ ] `password` and `remember_token` excluded from UserResource (T002)
- [ ] Signed URLs for email verification (T011)
- [ ] Input validation via Form Requests before reaching service (T004-T008)

### Total: 37 tasks across 7 phases
