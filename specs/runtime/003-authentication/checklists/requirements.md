# Specification Quality Checklist — Authentication

> **Stage:** 003-authentication
> **Spec File:** `specs/runtime/003-authentication/spec.md` > **Reviewed:** 2026-04-12

## Completeness

- [x] Objective is specific and measurable
- [x] In-scope items are explicitly listed
- [x] Out-of-scope items are explicitly listed
- [x] At least 2 user stories with acceptance criteria (13 stories total)
- [x] Each user story has role, action, and outcome
- [x] Acceptance criteria are testable (checkbox format)
- [x] Backend technical requirements are specified
- [x] Frontend technical requirements are specified
- [x] API contract with request/response examples is provided
- [x] Dependencies (upstream/downstream) are documented

## Domain Alignment

- [x] All 5 user roles are addressed (Customer, Contractor, Supervising Architect, Field Engineer, Admin)
- [x] Role assignment follows SEC-FINDING-A (no mass assignment)
- [x] Existing `UserRole` enum values match spec role values
- [x] Existing User model fields (name, email, phone, password, is_active, avatar, role) align with spec
- [x] Registration role selection covers all valid UserRole enum cases

## Architecture Compliance

- [x] Layering enforced: Controller → Service → Repository → Model
- [x] Controller is thin (delegates to AuthService)
- [x] Form Request classes specified for validation
- [x] API Resource specified for response formatting
- [x] Error contract compliance (`{ success, data, error }`)
- [x] Error codes from registry used (AUTH_INVALID_CREDENTIALS, VALIDATION_ERROR, CONFLICT_ERROR)
- [x] Routes under `/api/v1/` prefix
- [x] `auth:sanctum` middleware for protected routes

## Security

- [x] Rate limiting on login endpoint
- [x] Rate limiting on forgot-password endpoint
- [x] Rate limiting on email verification resend
- [x] No email enumeration on forgot-password
- [x] Password hashing confirmed (Laravel `hashed` cast)
- [x] Token revocation on logout (current token only)
- [x] All tokens revoked on password reset
- [x] Password/remember_token excluded from API responses
- [x] Role not mass-assignable

## i18n / RTL

- [x] Arabic-first layout specified
- [x] RTL support required on all pages
- [x] i18n translation keys required (not hardcoded strings)
- [x] Arabic role labels in role selector
- [x] Arabic success/error messages in API responses

## Non-Functional Requirements

- [x] Response time targets specified
- [x] Mobile-responsive requirement
- [x] Token expiration configurability
- [x] Password complexity rules defined

## Testing Coverage (Requirements)

- [x] Feature tests for all endpoints specified
- [x] Success path coverage
- [x] Validation error coverage
- [x] Authentication error coverage
- [x] Rate limiting coverage
- [x] RBAC note: auth stage establishes roles but does not enforce domain RBAC (deferred)

## Open Items

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] No unresolved open questions
- [x] All API endpoints have request/response contracts

## Summary

| Metric                 | Value |
| ---------------------- | ----- |
| User Stories           | 13    |
| Acceptance Criteria    | 64    |
| Technical Requirements | 22    |
| API Endpoints          | 9     |
| Open Questions         | 0     |
| Clarification Markers  | 0     |

**Status: PASS** — Spec is complete and ready for planning phase.

---

## Bunyan Architecture Governance Validation

### CHK001 — RBAC Middleware Coverage

- [x] `auth:sanctum` middleware is specified for all protected routes (GET /user, PUT /user, POST /logout, POST /email/resend)
- [x] Guest-accessible endpoints are explicitly listed (register, login, forgot-password, reset-password, email verify)
- [x] Admin self-registration is explicitly blocked (Q1 clarification)
- [x] Role assignment mechanism is specified as explicit (not mass-assignable, SEC-FINDING-A)
- [ ] Spec does not define middleware group ordering or registration location (RouteServiceProvider vs. bootstrap) — acceptable for auth stage, deferred to RBAC stage

### CHK002 — Form Request Validation Completeness

- [x] `RegisterRequest` validation rules are defined (name, email, phone, password, password_confirmation, role)
- [x] `LoginRequest` validation rules are implied (email, password)
- [x] `ForgotPasswordRequest` is specified (email only)
- [x] `ResetPasswordRequest` is specified (email, token, password, password_confirmation)
- [x] `UpdateProfileRequest` is specified (name, phone — email and role immutable)
- [x] Saudi phone regex is defined: `/^(\+9665|05)\d{8}$/` (Q4 clarification)
- [x] Password minimum length is specified (min 8 characters)
- [x] Role enum validation restricts to 4 non-admin roles
- [ ] Password complexity beyond "min 8" is not specified (e.g., uppercase, number, special char) — spec says "minimum 8 characters, confirmed" only

### CHK003 — Eloquent Relationship Definitions

- [x] User → PersonalAccessToken relationship is implicit via Sanctum's `HasApiTokens` trait
- [x] User model `$hidden` array specifies `password` and `remember_token`
- [x] User model uses `UserRole` enum cast for `role` column
- [x] User model uses `hashed` cast for password
- [ ] Spec does not explicitly list `$fillable` vs `$guarded` configuration — SEC-FINDING-A implies `role` must NOT be in `$fillable`, but full list is not enumerated

### CHK004 — Service Layer Business Logic Isolation

- [x] `AuthService` is specified as the business logic layer
- [x] `AuthController` is specified as thin (delegates to `AuthService`)
- [x] `UserRepository` is specified for Eloquent queries
- [x] Service receives repository via dependency injection
- [x] Controller does NOT contain: token generation logic, password hashing, email sending, user lookup queries
- [x] Business rules in service: login (credential check + active check), register (create + token + email), logout (revoke token)

### CHK005 — Error Contract Compliance

- [x] All API endpoint examples follow `{ success, data, error }` contract
- [x] Error codes from registry are specified: `AUTH_INVALID_CREDENTIALS` (401), `VALIDATION_ERROR` (422), `CONFLICT_ERROR` (409), `AUTH_UNAUTHORIZED` (403)
- [x] Field-level validation errors are specified in `error.details`
- [x] Success responses include `error: null`
- [x] Error responses include `data: null`
- [x] HTTP status codes match error code registry (401, 403, 409, 422)

### CHK006 — Arabic/RTL Support Verification

- [x] All frontend auth pages require RTL layout (`dir="rtl"`)
- [x] Arabic labels and placeholders required via i18n keys
- [x] Role selector presents Arabic labels for all roles
- [x] API success messages are in Arabic (e.g., "تم تسجيل الخروج بنجاح")
- [x] i18n key-based approach specified (not hardcoded strings)
- [x] `locales/ar.json` is the translation source
- [ ] Spec does not specify whether API error messages (`error.message`) are also Arabic or English — implied Arabic-first but not explicit

### CHK007 — Rate Limiting Specifications

- [x] Login: max 5 attempts per minute per IP (`throttle:5,1`)
- [x] Forgot password: max 3 requests per minute per email (`throttle:3,1`)
- [x] Email verification resend: max 3 per minute (`throttle:3,1`)
- [x] Rate limit error response behavior is not explicitly defined — should return `RATE_LIMIT_EXCEEDED` (429) per error code registry
- [ ] Registration endpoint rate limiting is not specified — potential abuse vector for account creation spam

### CHK008 — Email Enumeration Prevention

- [x] Forgot password returns identical response regardless of email existence (US4)
- [x] Response always returns `{ success: true }` — no information leakage
- [ ] Registration endpoint returns `CONFLICT_ERROR` on duplicate email — this IS an enumeration vector (acceptable trade-off, but spec should acknowledge it)
- [ ] Login endpoint returns `AUTH_INVALID_CREDENTIALS` for both wrong email and wrong password — confirmed no enumeration

### CHK009 — Password Hashing

- [x] Password hashing uses Laravel's `hashed` cast (bcrypt by default)
- [x] Passwords are hashed before storage (explicitly stated in US1)
- [x] Password field is in `$hidden` on User model
- [x] Password is excluded from `UserResource` API responses
- [x] No password logging requirement stated

### CHK010 — Token Security

- [x] Token revocation on logout: current token only (US3)
- [x] Token revocation on password reset: all tokens (US5)
- [x] Token returned in response body (not cookie) for API-first design
- [x] Token persistence uses `useCookie` with `secure: true, sameSite: 'lax'` (Q3 clarification)
- [x] Token expiration is configurable via `SANCTUM_TOKEN_EXPIRATION` env variable
- [x] No hard limit on concurrent tokens (Q5 clarification)
- [x] `remember_token` excluded from serialization
- [ ] Token scope/abilities are not specified — Sanctum supports token abilities but spec uses default (all abilities)

### CHK011 — Saudi Phone Format Validation

- [x] Local format defined: `05XXXXXXXX` (10 digits starting with `05`)
- [x] International format defined: `+9665XXXXXXXX`
- [x] Combined regex provided: `/^(\+9665|05)\d{8}$/`
- [x] Both `RegisterRequest` and `UpdateProfileRequest` use the same regex
- [x] Landline numbers explicitly excluded
- [x] Frontend Zod schema must mirror backend regex
