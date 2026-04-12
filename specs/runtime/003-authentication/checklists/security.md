# Security Requirements Quality Checklist — Authentication

> **Stage:** 003-authentication
> **Spec File:** `specs/runtime/003-authentication/spec.md` > **Generated:** 2026-04-13
> **Purpose:** Validates that security requirements are sufficiently defined, quantified, and unambiguous.

---

## Authentication & Credential Security

### CHK-SEC-001 — Password Policy Specification

- [x] Minimum password length is defined (8 characters)
- [x] Password confirmation requirement is specified
- [x] Hashing algorithm is specified (bcrypt via Laravel `hashed` cast)
- [ ] Password complexity rules beyond length are not specified (uppercase, number, special character requirements) — requirements may be underspecified for a construction marketplace handling financial transactions
- [ ] Password history/reuse prevention is not addressed — acceptable for auth stage but should be noted for future hardening

### CHK-SEC-002 — Credential Exposure Prevention

- [x] `password` excluded from User model serialization (`$hidden`)
- [x] `remember_token` excluded from User model serialization
- [x] `UserResource` explicitly excludes sensitive fields
- [x] No password logging requirement is stated
- [x] Token values are not logged (implied by "no token leakage in API responses or logs")
- [ ] Spec does not define log scrubbing rules for request bodies containing passwords — should ensure `password` field is never logged in request middleware

### CHK-SEC-003 — Login Brute Force Protection

- [x] Rate limiting is quantified: 5 attempts per minute per IP
- [x] Throttle middleware is specified: `throttle:5,1`
- [ ] Account lockout after N failed attempts is not defined — only rate limiting exists
- [ ] Failed login attempt logging/alerting is not specified
- [ ] Rate limit scope is per-IP only — distributed attacks from multiple IPs are not mitigated (acceptable for MVP)

### CHK-SEC-004 — Token Lifecycle Security

- [x] Token generation mechanism is defined (Sanctum personal access token)
- [x] Token revocation on logout is defined (current token only)
- [x] Token revocation on password reset is defined (all tokens)
- [x] Token expiration is configurable via environment variable
- [x] Cookie persistence uses `secure: true` and `sameSite: 'lax'`
- [ ] Token rotation strategy is not defined (e.g., refresh token pattern)
- [ ] Token abilities/scopes are not specified — all tokens have full access
- [x] Cookie is NOT `httpOnly` — justified because client-side JS must read it for Authorization header

### CHK-SEC-005 — Password Reset Security

- [x] Reset token expiration is defined: 60 minutes
- [x] Reset endpoint is rate-limited: 3 per minute per email
- [x] All existing tokens are revoked on successful password reset
- [x] Password-changed notification email is sent to user
- [x] No email enumeration: identical response for existing/non-existing emails
- [ ] Reset token single-use enforcement is not explicitly stated (Laravel's default broker handles this, but spec should confirm)
- [ ] Reset link delivery channel is email only — no SMS fallback addressed

### CHK-SEC-006 — Email Verification Security

- [x] Signed URL pattern is specified for verification links
- [x] Resend endpoint is rate-limited: 3 per minute
- [x] Idempotent behavior on re-verification is defined
- [x] Verification uses Laravel's built-in `MustVerifyEmail` contract
- [ ] Verification link expiration time is not specified (Laravel default is 60 min — should be stated explicitly)
- [x] Unverified email does not block login (returns flag `email_verified: false`)

### CHK-SEC-007 — Role Assignment Security

- [x] SEC-FINDING-A documented: role is NOT mass-assignable
- [x] Explicit role assignment specified: `$user->role = UserRole::from($validated['role'])`
- [x] Admin role excluded from self-registration (Q1 clarification)
- [x] RegisterRequest validates role against 4 non-admin values
- [x] Role is immutable after registration (cannot be changed via profile update)
- [ ] Role escalation protection for future stages is referenced but deferred — spec correctly marks RBAC enforcement as out of scope

### CHK-SEC-008 — Information Disclosure Prevention

- [x] Forgot password: no email enumeration (identical response)
- [x] Login: `AUTH_INVALID_CREDENTIALS` for any credential failure (no distinction between wrong email vs. wrong password)
- [ ] Registration: `CONFLICT_ERROR` on duplicate email reveals email existence — spec should explicitly acknowledge this as an accepted risk with justification
- [ ] Error responses for 500 errors: spec states "no stack traces to clients" in error code registry but does not repeat it in auth spec security section
- [x] Inactive user receives `AUTH_UNAUTHORIZED` — does not reveal the specific reason is inactivity (generic 403)

### CHK-SEC-009 — Transport Security

- [x] HTTPS enforcement is specified (infrastructure-level)
- [x] Cookie `secure: true` flag is set
- [x] Cookie `sameSite: 'lax'` is set
- [ ] HSTS header requirements are not specified (infrastructure concern but should be noted)
- [ ] CORS policy for auth endpoints is not specified

### CHK-SEC-010 — Input Validation Boundaries

- [x] Email format validation specified (valid format)
- [x] Phone format validation specified with regex
- [x] Name max length defined (255 characters)
- [x] Password minimum length defined (8 characters)
- [x] Role enum validation defined (4 valid values)
- [ ] Email max length is not specified (RFC 5321 allows 254 chars — should be explicit)
- [ ] XSS/injection protection for name field is implied by Form Requests but not explicitly stated
- [ ] Phone field format enforcement prevents injection, but sanitization policy is not stated

---

## Summary

| Category               | Pass   | Warn   | Total  |
| ---------------------- | ------ | ------ | ------ |
| Password Policy        | 3      | 2      | 5      |
| Credential Exposure    | 5      | 1      | 6      |
| Brute Force Protection | 2      | 3      | 5      |
| Token Lifecycle        | 6      | 2      | 8      |
| Password Reset         | 5      | 2      | 7      |
| Email Verification     | 5      | 1      | 6      |
| Role Assignment        | 5      | 1      | 6      |
| Information Disclosure | 3      | 2      | 5      |
| Transport Security     | 3      | 2      | 5      |
| Input Validation       | 5      | 3      | 8      |
| **Total**              | **42** | **19** | **61** |

**Status: PASS WITH WARNINGS** — Security requirements are well-defined for an auth stage MVP. Warnings are either acceptable trade-offs or deferred to downstream stages. No blockers.
