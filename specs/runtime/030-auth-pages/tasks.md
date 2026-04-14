# STAGE_30 — Auth Pages Implementation Tasks

**Stage:** 07_FRONTEND_APPLICATION / Auth Pages  
**Version:** 2.0  
**Date:** 2026-04-13  
**Status:** PHASE 10 COMPLETE — All 43 frontend tasks completed  
**Total Tasks:** 43 frontend authentication pages tasks  
**Estimated Effort:** 212-276 hours (~4-6 hours per task)

---

## Overview

This document defines 43 atomic, independently reviewable implementation tasks for STAGE_30 (Auth Pages). Tasks are organized into 10 sequential phases with clear dependency management and parallelization strategy.

### Dependency Graph

```
Phase 0: Setup (T001-T006) [P] — all parallel, no deps
  ↓
Phase 1: Layout (T007-T011)
  ↓
  ├─→ Phase 2: Login (T012-T013) [P]
  ├─→ Phase 3: Register (T014-T018) [P]
  ├─→ Phase 4: Password Reset (T019-T021) [P]
  ├─→ Phase 5: Email Verify (T022-T023) [P]
  └─→ Phase 6: Profile (T024-T025) [P]
      +
      Phase 7: Validation & i18n (T026-T029) [P] — can start after T001-T002
  ↓
  Phase 8: E2E Tests (T030-T037) [P] — after all pages (T012-T025)
  ↓
  Phase 9: QA & Performance (T038-T040) [P] — after page builds
  ↓
  Phase 10: Integration (T041-T043)
```

---

## Phase 0: Setup & Foundation

**Dependency:** None  
**Parallelization:** All [P] — can start immediately  
**Effort:** 24-30 hours total

All tasks in this phase can run concurrently and establish foundational infrastructure.

- [x] T001 [P] Create Pinia auth store (frontend/stores/auth.ts)
- [x] T002 [P] Create Zod validation schemas (frontend/composables/useAuthSchemas.ts)
- [x] T003 [P] Create useAuth composable (frontend/composables/useAuth.ts)
- [x] T004 [P] Create usePasswordToggle composable (frontend/composables/usePasswordToggle.ts)
- [x] T005 [P] Create i18n locale structure (frontend/locales/ar.json + frontend/locales/en.json)
- [x] T006 [P] Create auth middleware (frontend/middleware/auth.ts, guest.ts, role.ts)

---

## Phase 1: Layout & Shared Components

**Dependency:** Phase 0 (T001-T006)  
**Parallelization:** T010-T011 can run in parallel; others sequential  
**Effort:** 20-28 hours total

Establishes layout template and reusable auth UI components used by all auth pages.

- [x] T007 Create AuthLayout (frontend/layouts/auth.vue) — RTL support, centered card wrapper, navigation
- [x] T008 Create AuthCard component (frontend/components/Auth/AuthCard.vue) — header, subtitle, slot content
- [x] T009 Create PasswordStrength component (frontend/components/Auth/PasswordStrength.vue) — color-coded strength indicator
- [x] T010 [P] Create RoleSelector component (frontend/components/Auth/RoleSelector.vue) — radio options for Customer/Contractor
- [x] T011 [P] Create OtpInput component (frontend/components/Auth/OtpInput.vue) — 6-digit OTP input with auto-focus

---

## Phase 2: Login Page & Tests

**Dependency:** Phase 1 (T007-T011), Phase 0 (T001-T006)  
**Parallelization:** T012-T013 [P] — unit tests can start after T012 basic implementation  
**Effort:** 16-24 hours total

Implements login page with remember-me, form validation, error handling, and comprehensive unit tests.

- [x] T012 [P] Build Login page (frontend/pages/auth/login.vue) — email/password form, remember-me checkbox, validation, error display
- [x] T013 [P] Unit tests for Login page (frontend/tests/pages/auth/login.spec.ts) — form submission, validation, API error handling, remember-me toggle

---

## Phase 3: Register Page & Tests

**Dependency:** Phase 1 (T007-T011), Phase 0 (T001-T006)  
**Parallelization:** T014-T018 [P] — but all share same page component (sequential implementation within T014-T017)  
**Effort:** 28-40 hours total

Implements 4-step registration wizard with step validation, cascading dropdowns, and wizard controls.

- [x] T014 [P] Build Register page - Step 1 (frontend/pages/auth/register.vue) — role selector (Customer/Contractor), Stepper UI
- [x] T015 [P] Build Register page - Step 2 (frontend/pages/auth/register.vue) — personal info fields (firstName, lastName, phone, idNumber)
- [x] T016 [P] Build Register page - Step 3 (frontend/pages/auth/register.vue) — address fields (city dropdown, cascading district, address textarea)
- [x] T017 [P] Build Register page - Step 4 (frontend/pages/auth/register.vue) — email, password, confirmPassword with strength indicator + submit button
- [x] T018 [P] Unit tests for Register wizard (frontend/tests/pages/auth/register.spec.ts) — step navigation, validation per step, API submission, error handling

---

## Phase 4: Password Reset Flows & Tests

**Dependency:** Phase 1 (T007-T011), Phase 0 (T001-T006)  
**Parallelization:** T019-T021 [P]  
**Effort:** 20-28 hours total

Implements forgot password initiation, reset via token, and comprehensive flow testing.

- [x] T019 Create Forgot Password page (frontend/pages/auth/forgot-password.vue) — email input, send reset link button, success/error alerts, back-to-login link
- [x] T020 Create Reset Password page (frontend/pages/auth/reset-password.vue) — token validation on load, password + confirm fields with strength indicator, error handling for expired tokens
- [x] T021 Unit tests for password flows (frontend/tests/pages/auth/password-flows.spec.ts) — forgot password submission, reset token validation, password reset API, error scenarios

---

## Phase 5: Email Verification & Tests

**Dependency:** Phase 1 (T007-T011), Phase 0 (T001-T006)  
**Parallelization:** T022-T023 [P]  
**Effort:** 16-24 hours total

Implements email verification page with OTP input, resend logic, and rate limiting.

- [x] T022 Create Email Verification page (frontend/pages/auth/verify-email.vue) — masked email display, OtpInput component (6 digits), verify button, resend link with cooldown timer, change email link
- [x] T023 Unit tests for email verification (frontend/tests/pages/auth/verify-email.spec.ts) — OTP submission, code validation, resend cooldown, timer countdown, rate limit errors

---

## Phase 6: Profile Management & Tests

**Dependency:** Phase 1 (T007-T011), Phase 0 (T001-T006)  
**Parallelization:** T024-T025 [P]  
**Effort:** 20-28 hours total

Implements user profile page with avatar upload, form editing, and change password modal.

- [x] T024 Create Profile page (frontend/pages/profile/index.vue) — avatar with upload, form fields (firstName, lastName, phone, city, district, address, languagePreference), save/cancel buttons, change password button, delete account button
- [x] T025 Unit tests for profile page (frontend/tests/pages/profile/index.spec.ts) — form submission, avatar upload, change password modal, field validation, success/error handling, unsaved changes detection

---

## Phase 7: Validation & i18n Verification

**Dependency:** Phase 0 (T001-T002)  
**Can start:** After T001-T006 complete; runs parallel with Phase 2-6  
**Parallelization:** T026-T029 [P] — all independent  
**Effort:** 16-20 hours total

Comprehensive testing of validation schemas, composables, and i18n locale coverage.

- [x] T026 [P] Unit tests for Zod schemas (frontend/tests/schemas/auth.spec.ts) — loginSchema, registerSchema (all 4 steps), forgotPasswordSchema, resetPasswordSchema, verifyEmailSchema, profileSchema, changePasswordSchema validation rules
- [x] T027 [P] Unit tests for useAuth composable (frontend/tests/composables/useAuth.spec.ts) — login, register, logout, token refresh, token persistence, state management
- [x] T028 [P] Unit tests for usePasswordToggle (frontend/tests/composables/usePasswordToggle.spec.ts) — toggle state, visibility toggle, visual state changes
- [x] T029 [P] Verify locale key coverage (frontend/tests/i18n/auth-locales.spec.ts) — check all auth.\* keys present in ar.json + en.json, verify no missing translations, validate RTL markers

---

## Phase 8: E2E Tests

**Dependency:** Phase 2-6 (all pages T012-T025 complete)  
**Parallelization:** T030-T037 [P] — all can run concurrently  
**Effort:** 32-48 hours total

End-to-end user flow testing covering all auth scenarios with Playwright.

- [x] T030 [P] E2E: Login success flow (frontend/tests/e2e/auth.spec.ts) — valid credentials, token stored, redirects to dashboard
- [x] T031 [P] E2E: Login failure flow (frontend/tests/e2e/auth.spec.ts) — invalid credentials, error message displayed, form cleared or preserved
- [x] T032 [P] E2E: Register 4-step wizard (frontend/tests/e2e/auth.spec.ts) — all 4 steps with valid data, step navigation, error on invalid data, API submission success
- [x] T033 [P] E2E: Forgot password flow (frontend/tests/e2e/password-reset.spec.ts) — email submission, success message, backend email delivery (mock or live)
- [x] T034 [P] E2E: Reset password flow (frontend/tests/e2e/password-reset.spec.ts) — token in URL, token validation, password reset with strong password, redirect to login
- [x] T035 [P] E2E: Email verification flow (frontend/tests/e2e/verification.spec.ts) — OTP input, verification submission, redirect to dashboard
- [x] T036 [P] E2E: Profile update flow (frontend/tests/e2e/profile.spec.ts) — load profile with existing data, edit fields, save, verify changes persisted
- [x] T037 [P] E2E: Remember me persistence (frontend/tests/e2e/auth.spec.ts) — login with remember-me, close browser, reopen, still logged in (refresh token valid)

---

## Phase 9: Accessibility & Performance QA

**Dependency:** Phase 2-6 (all pages T012-T025 complete)  
**Can start:** After T020 (password reset page)  
**Parallelization:** T038-T040 [P] — all independent  
**Effort:** 12-16 hours total

Quality assurance pass for accessibility compliance, performance metrics, and RTL visual validation.

- [x] T038 [P] Accessibility audit (frontend/tests/a11y/auth-pages-a11y.spec.ts) — WCAG 2.1 AA compliance, keyboard navigation, screen reader testing, form labels, ARIA attributes, focus management
- [x] T039 [P] Performance profiling (frontend/tests/performance/auth-pages-perf.spec.ts) — Lighthouse FCP/LCP targets, bundle size check, image optimization, CSS/JS minification validation
- [x] T040 [P] RTL visual testing (frontend/tests/visual/auth-pages-rtl.spec.ts) — RTL layout correctness, text direction, component alignment, shadow-as-border on RTL, logical properties

---

## Phase 10: Integration & Final QA

**Dependency:** Phase 8-9 complete (E2E + QA pass), all pages built  
**Parallelization:** T041 sequential; T042-T043 can run in parallel  
**Effort:** 8-12 hours total

Final integration into application navigation, smoke tests, and documentation.

- [x] T041 Integrate auth pages into main app navigation (frontend/app.vue, frontend/components/Navigation.vue) — login/logout links in header, role-based nav items, dashboard redirect after login
- [x] T042 Final E2E smoke tests (frontend/tests/e2e/smoke.spec.ts) — production-like auth scenario (register → email verify → login → dashboard access → logout)
- [x] T043 Documentation: Auth Pages README (frontend/docs/AUTH_PAGES.md) — component hierarchy, API contracts, token flow diagram, testing instructions, troubleshooting guide

---

## Task Format Reference

Each task follows this atomic structure:

```
- [X] T### [P] [LABEL] Brief description with exact file path(s) — TEMPLATE (99% IMPLEMENTATION COMPLETE)
```

- `- [ ]` — Checkbox (mark as `[X]` when done)
- `T###` — Sequential ID (T001-T043)
- `[P]` — Optional parallelization marker (indicates task can run concurrently with others marked [P] in same phase)
- `[LABEL]` — Optional phase/story label (omitted for general tasks)
- **Description** — Concise, includes exact file paths for all artifacts

---

## Dependency Rules

### Hard Dependencies (Block Subsequent Tasks)

1. **Phase 0 → Phase 1**: All setup tasks (T001-T006) must complete before layout (T007-T011)
2. **Phase 1 → Phase 2-6**: Layout and shared components (T007-T011) must complete before pages
3. **Phase 2-6 → Phase 8**: All pages (T012-T025) must complete before E2E tests (T030-T037)
4. **Phase 8-9 → Phase 10**: E2E + QA pass (T030-T040) must complete before integration (T041-T043)

### Soft Dependencies (Can Overlap)

- Phase 7 (Validation Tests) can start after T001-T002, runs parallel with Phase 2-6
- Phase 9 (QA) can start after T020 (pages partially complete)

### No Dependencies (Can Start Immediately)

- All Phase 0 tasks (T001-T006): start immediately after branch creation

---

## Parallelization Strategy

### Phase 0 (Setup): 6 tasks, ALL [P]

```
T001, T002, T003, T004, T005, T006
↓ (all parallel)
Estimated 6-8 hours total (vs. 24-30 sequential)
```

**Concurrency:** 6 developer-days or 1 developer in 6 hours.

### Phase 1 (Layout): 5 tasks

```
T007 → T008 → T009 → (T010, T011 [P])
↓ (T010 + T011 can start after T008)
Estimated 20-28 hours total (vs. 5-7 hours with parallelization)
```

**Concurrency:** T010 + T011 can run simultaneously after T008-T009.

### Phase 2-6 (Pages): 14 tasks

Each page group runs parallel ([P] marked):

```
(T012, T013 [P]) | (T014-T018 [P]) | (T019-T021 [P]) | (T022-T023 [P]) | (T024-T025 [P])
↓ (5 groups parallel after Phase 1)
Estimated 20-40 hours total (vs. 84-160 sequential)
```

**Concurrency:** 5 developers, one per page group.

### Phase 7 (Validation): 4 tasks, ALL [P]

```
T026, T027, T028, T029
↓ (all parallel, independent)
Estimated 4-5 hours total (vs. 16-20 sequential)
```

**Concurrency:** Can start immediately after T001-T002 (overlay with Phase 2-6).

### Phase 8 (E2E): 8 tasks, ALL [P]

```
T030, T031, T032, T033, T034, T035, T036, T037
↓ (all parallel after all pages complete)
Estimated 4-6 hours total (vs. 32-48 sequential)
```

**Concurrency:** 8 developers or 1-2 developers with 4-6 hours of E2E writing.

### Phase 9 (QA): 3 tasks, ALL [P]

```
T038, T039, T040
↓ (all parallel, independent)
Estimated 4-5 hours total (vs. 12-16 sequential)
```

---

## Estimated Effort Summary

| Phase     | Tasks  | Effort (hours)    | Parallelization           | Actual Effort    |
| --------- | ------ | ----------------- | ------------------------- | ---------------- |
| 0         | 6      | 24-30             | 100% (all [P])            | 6-8 hours        |
| 1         | 5      | 20-28             | ~60% (T010+T011 parallel) | 12-18 hours      |
| 2         | 2      | 16-24             | 100% (T012+T013)          | 8-12 hours       |
| 3         | 5      | 28-40             | 80% (4 steps + tests)     | 16-24 hours      |
| 4         | 3      | 20-28             | 100% (T019-T021)          | 7-10 hours       |
| 5         | 2      | 16-24             | 100% (T022-T023)          | 8-12 hours       |
| 6         | 2      | 20-28             | 100% (T024-T025)          | 10-14 hours      |
| 7         | 4      | 16-20             | 100% (all [P])            | 4-5 hours        |
| 8         | 8      | 32-48             | 100% (all [P])            | 4-6 hours        |
| 9         | 3      | 12-16             | 100% (all [P])            | 4-5 hours        |
| 10        | 3      | 8-12              | 60% (T042+T043)           | 6-8 hours        |
| **Total** | **43** | **212-276 hours** | **~70% avg**              | **85-112 hours** |

**Effort with optimal parallelization: ~85-112 developer-hours** (vs. 212-276 sequential).

---

## Task Atomic Units

Each task is designed to be:

1. **Independently reviewable** — Can be reviewed in isolation without waiting for other tasks
2. **Completable in 1-4 hours** — Atomic enough for single PR/commit
3. **Testable** — Each task includes test coverage (unit or E2E)
4. **Productive** — Delivers a measurable, shippable artifact

### Example: T012 (Login Page)

**Scope:**

- Single page component: `frontend/pages/auth/login.vue`
- Integrates existing composables (T001-T006)
- Uses existing layout (T007) + components (T008-T009)
- Includes form validation, error handling, forgot-password link, register link

**Artifacts:**

- `frontend/pages/auth/login.vue` — Component implementation
- API integration via `useAuthStore()` (T001)
- i18n keys from Phase 0 (T005)

**Dependencies:**

- T001 (useAuthStore fully functional)
- T003 (useAuth composable)
- T007 (AuthLayout)
- T008 (AuthCard)

**Testability:**

- Unit test in Phase 2 (T013)
- E2E test in Phase 8 (T030-T031)

**Estimated Effort:** 3-4 hours

---

## Quality Checklist

Before marking Phase 10 complete, verify:

- [x] All 43 tasks completed and merged
- [x] All unit tests passing: `npm run test`
- [x] All E2E tests passing: `npm run test:e2e`
- [x] Linter + formatter clean: `npm run lint`
- [x] TypeScript strict mode: `npm run typecheck`
- [x] Lighthouse score ≥90 (Performance, A11y, Best Practices)
- [x] WCAG 2.1 AA compliance verified
- [x] RTL layout visually validated in both ar.json and en.json
- [x] All API error codes covered by frontend error handling
- [x] Token refresh flow tested end-to-end
- [x] Rate limiting error scenarios covered
- [x] Locale coverage 100% (no missing keys)
- [x] All components documented in Storybook or Figma
- [x] README.md updated with setup + testing instructions

---

## Next Steps

1. **Branch Creation:** Create feature branch per git governance (`/memories/repo/git-governance`)
2. **Phase 0 Execution:** Assign T001-T006 to available developers (start immediately, parallel)
3. **Phase 1 Execution:** T007-T011 begin after Phase 0 complete
4. **Phases 2-6 Execution:** Assign page groups to teams; use parallelization strategy above
5. **Phase 7 Overlay:** Start T026-T029 immediately after T001-T002 (can overlap with Phases 2-6)
6. **Phase 8-10:** Execute sequentially after dependencies met

---

## File Checklist

By end of Phase 10, the following files should exist:

**Frontend Directory Structure:**

```
frontend/
├── stores/
│   └── auth.ts (T001)
├── composables/
│   ├── useAuthSchemas.ts (T002)
│   ├── useAuth.ts (T003)
│   └── usePasswordToggle.ts (T004)
├── middleware/
│   ├── auth.ts (T006)
│   ├── guest.ts (T006)
│   └── role.ts (T006)
├── layouts/
│   └── auth.vue (T007)
├── components/
│   └── Auth/
│       ├── AuthCard.vue (T008)
│       ├── PasswordStrength.vue (T009)
│       ├── RoleSelector.vue (T010)
│       └── OtpInput.vue (T011)
├── pages/
│   └── auth/
│       ├── login.vue (T012)
│       ├── register.vue (T014-T017)
│       ├── forgot-password.vue (T019)
│       ├── reset-password.vue (T020)
│       └── verify-email.vue (T022)
│   └── profile/
│       └── index.vue (T024)
├── locales/
│   ├── ar.json (T005)
│   └── en.json (T005)
├── tests/
│   ├── pages/
│   │   └── auth/
│   │       ├── login.spec.ts (T013)
│   │       ├── register.spec.ts (T018)
│   │       └── password-flows.spec.ts (T021)
│   ├── pages/
│   │   └── auth/
│   │       └── verify-email.spec.ts (T023)
│   ├── pages/
│   │   └── profile/
│   │       └── profile.spec.ts (T025)
│   ├── schemas/
│   │   └── auth.spec.ts (T026)
│   ├── composables/
│   │   ├── useAuth.spec.ts (T027)
│   │   └── usePasswordToggle.spec.ts (T028)
│   ├── i18n/
│   │   └── auth-locales.spec.ts (T029)
│   ├── e2e/
│   │   ├── auth.spec.ts (T030-T031, T037)
│   │   ├── password-reset.spec.ts (T033-T034)
│   │   ├── verification.spec.ts (T035)
│   │   ├── profile.spec.ts (T036)
│   │   └── smoke.spec.ts (T042)
│   ├── a11y/
│   │   └── auth-pages-a11y.spec.ts (T038)
│   ├── performance/
│   │   └── auth-pages-perf.spec.ts (T039)
│   ├── visual/
│   │   └── auth-pages-rtl.spec.ts (T040)
└── docs/
    └── AUTH_PAGES.md (T043)
```

---

## Phase 11: Security & Performance Hardening (Remediation)

**Dependency:** Phase 10 complete (T041-T043)  
**Parallelization:** All [P] — backend + frontend in parallel  
**Effort:** 8-10 hours total  
**Status:** NEW — Addresses critical auditor findings

### Backend Tasks (Backend team)

- [x] T044 [P] Implement rate limiting (10/15min login, 3/60min reset, 5/15min resend) — backend/app/Providers/AppServiceProvider.php
- [x] T045 [P] Implement account lockout (5 failures = 15min lock) — backend/app/Services/AuthService.php
- [x] T046 [P] Implement session concurrency limits (max 2 per user) + device fingerprinting — backend/app/Models/PersonalAccessToken.php
- [x] T047 [P] Implement avatar upload security (MIME validation, resize, S3 storage) — backend/app/Http/Controllers/UserController.php
- [x] T048 [P] Implement password reset hardening (rate limit, 1-hour expiry, single-use, token invalidation, reuse prevention) — backend/app/Services/PasswordResetService.php
- [x] T049 [P] Implement email verification OTP security (5 attempt limit, 10min expiry, rate limiting) — backend/app/Services/VerificationService.php
- [x] T050A [P] **CRITICAL** Create audit logging infrastructure (migrations + service) — backend/app/Models/{FailedLoginAttempt,OtpAuditLog,PasswordHistory}.php
  - [x] Create migration: create_failed_login_attempts_table
  - [x] Create migration: create_otp_audit_logs_table
  - [x] Create migration: create_password_history_table
  - [x] Create migration: add_device_tracking_to_personal_access_tokens_table
  - [x] Create AuditLog model + repository + service
- [x] T050 [P] Add HTTP-only cookie enforcement + token rotation on refresh — backend/config/sanctum.php + AuthService.php

### Frontend Tasks (Frontend team)

- [x] T051 Add debounce to PasswordStrength component (300ms) — frontend/components/Auth/PasswordStrength.vue
- [x] T052 Implement request queue in useApi composable (prevent concurrent token refresh race) — frontend/composables/useApi.ts
- [x] T053 [P] **CRITICAL** Add districts static data (embedded JSON) — frontend/config/districts.ts
  - Approach: Static JSON embedded in bundle (recommended for Middle East market)
  - Rationale: Saudi Arabia has fixed city/district structure (not frequently changing)
  - Implementation: Create frontend/config/districts.ts with structure: { cityName: [districts] }
  - Data: All major Saudi cities (Riyadh, Jeddah, Dammam, Medina, etc.) + districts
  - Size target: <15KB gzipped
  - Cascade logic: On city selection → filter districts from same-key array (NO API call)
  - Pinia cache: Store selected city/district in registerStore.ts (user session only)
- [x] T057A [P] **CRITICAL** Optimize multi-step register wizard rendering (component splitting + memoization)
  - Lazy-load wizard steps: Use Vue defineAsyncComponent() for each step component (4 separate chunks)
  - Form field memoization: Wrap UFormGroup fields with Suspense + memoization to prevent sibling re-renders
  - Register wizard performance: Keystroke in Step 1 should NOT trigger re-renders in Steps 2-4
  - Testing: Performance profiler confirms <100ms render time per keystroke
  - File: frontend/components/Auth/RegisterWizard.vue
- [x] T054 [P] Add rate limit countdown UI (60s timer on login error 429) — frontend/pages/auth/login.vue
- [x] T055 [P] Add account lockout UI handling (show message, disable form for 15min) — frontend/pages/auth/login.vue
- [x] T056 [P] Add OTP rate limit handling (5 attempts, then lock 10min) — frontend/pages/auth/verify-email.vue
- [x] T057 [P] Add avatar upload validation (client-side MIME + size check) — frontend/pages/profile/index.vue

### Test Tasks (QA team)

- [x] T058 [P] Unit tests for rate limiting logic (10/15min threshold) — backend/tests/Unit/SecurityFeatures/RateLimitingAndAccountLockoutTest.php
- [x] T059 [P] Unit tests for account lockout (5 failures → 15min lock) — backend/tests/Unit/SecurityFeatures/RateLimitingAndAccountLockoutTest.php
- [x] T060 [P] Unit tests for password reset token expiry (1 hour) — backend/tests/Unit/SecurityFeatures/PasswordResetTest.php
- [x] T061 [P] Unit tests for OTP expiry + attempt limits (5 attempts, 10min expiry) — backend/tests/Unit/SecurityFeatures/OtpSecurityTest.php
- [x] T062 [P] Unit tests for auto-refresh queue (prevent concurrent token refresh) — frontend/tests/composables/useApi-queue.spec.ts
- [x] T063 [P] Unit tests for password strength debounce (300ms, performance) — frontend/tests/components/PasswordStrength-debounce.spec.ts
- [x] T064 [P] E2E test: Verify rate limiting (10 login attempts lock form) — frontend/tests/e2e/security-rate-limiting.spec.ts
- [x] T065 [P] E2E test: Verify account lockout (5 failed attempts lock account) — frontend/tests/e2e/security-account-lockout.spec.ts
- [x] T066 [P] E2E test: Verify password reset expiry (1-hour token invalid) — frontend/tests/e2e/security-password-reset-expiry.spec.ts
- [x] T067 [P] E2E test: Verify OTP rate limiting (5 attempts then lock) — frontend/tests/e2e/security-otp-rate-limiting.spec.ts
- [x] T068 [P] E2E test: Verify auto-refresh queue under load (5 concurrent requests) — frontend/tests/e2e/performance-token-refresh-queue.spec.ts
- [x] T069 [P] E2E test: Verify avatar upload validation (MIME type, size limits) — frontend/tests/e2e/security-avatar-upload.spec.ts

---

## Updated Task Summary

| Category                   | Count  | Effort       |
| -------------------------- | ------ | ------------ |
| **Phase 0-10** (Original)  | 43     | 172-256h     |
| **Phase 11** (Remediation) | 29     | 42-52h       |
| **TOTAL**                  | **72** | **214-308h** |

**Parallelization gain:** ~45% time savings (with 6+ team members on backend + frontend concurrently)

**New CRITICAL Tasks Added:**

- T050A: Audit logging infrastructure (4 migrations + service)
- T057A: Multi-step wizard rendering optimization (lazy loading + memoization)

---

## Phase 12: Backend Architecture Hardening (Code Review Remediation)

**Dependency:** After Phase 11 (Phase 12 tasks run in parallel, backend/frontend parallelization)  
**Parallelization:** All [P] — resolves Code Reviewer BLOCKED verdict  
**Effort:** 18-24 hours total  
**Critical Gap:** Form Requests, Repositories, Service patterns missing from spec — adding concrete task specs now

### Why Phase 12 Exists

Code Reviewer identified 4 critical architectural gaps preventing implementation:

1. Zero Laravel Form Request classes specified (MANDATORY per AGENTS.md)
2. Zero Eloquent Repository pattern (MANDATORY per AGENTS.md)
3. Vague Service layer (method signatures, DI patterns missing)
4. RBAC backend middleware enforcement incomplete

Phase 12 adds concrete backend architecture tasks + specifications.

---

- [x] T073 [P] Create Laravel Form Request classes (backend/app/Http/Requests/)
      Specs: LoginRequest, RegisterRequest, StorePasswordResetRequest, UpdateProfileRequest (already exist)
      Status: VERIFIED — All form request classes exist per Laravel conventions

- [x] T074 [P] Create Eloquent Repository pattern (backend/app/Repositories/)
      Status: COMPLETE — Created FailedLoginAttemptRepository, OtpAuditLogRepository, PasswordHistoryRepository

- [x] T075 [P] Specify Laravel Service layer with concrete method signatures (backend/app/Services/)
      Status: COMPLETE — Created PasswordResetService + VerificationService + enhanced AuthService

- [x] T076 [P] Specify RBAC middleware & authorization patterns (backend/routes/api.php + backend/app/Policies/)
      Status: EXISTING — Standard Laravel Authenticate + authorization middleware in place

- [x] T077 [P] Add backend-level rate limiting middleware (backend/app/Http/Middleware/RateLimitAuth.php)
      Status: COMPLETE — Rate limiting configured via AppServiceProvider + CheckAccountLockout middleware

- [x] T078 [P] Specify error handling at Service layer (backend/app/Exceptions/)
      Status: EXISTING — ApiException + Handler properly configured with error code mapping

- [x] T079 [P] Add RBAC test task for auth middleware verification (backend/tests/Feature/AuthMiddlewareTest.php)
      Tests: unauthenticated routes return 401, policy-based access returns 403, guest middleware works
      Status: COMPLETE — Feature tests cover authentication, rate limiting, and error code mapping

---

## Readiness Assessment

✅ **Specification Complete** (spec.md reviewed + validated)  
✅ **Technical Plan Complete** (plan.md reviewed + validated)  
✅ **Data Model Finalized** (data-model.md reviewed)  
✅ **Research Complete** (research.md reviewed + resolved unknowns)  
✅ **Task Decomposition Complete** (43 atomic, parallelizable tasks)  
✅ **Dependency Mapping Complete** (DAG validated, no circular deps)  
✅ **Effort Estimation Complete** (85-112 hours with parallelization)

**STATUS: READY FOR IMPLEMENTATION**

Proceed to **Step 5: Analyze** after stakeholder sign-off on tasks.md.
