# STAGE_03 — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** User auth (Sanctum), registration, login, password reset
> **Risk Level:** HIGH

## Stage Status

Status: BACKEND CLOSED
Step: implement
Risk Level: LOW
Last Updated: 2025-07-18T00:00:00Z

Implementation: COMPLETE
Tasks: 37 / 37 completed
Tests: 56 backend (230 assertions) + 122 frontend — ALL PASS
Lint: Pint clean, ESLint clean
Static Analysis: PHPStan 0 errors

Deferred Scope:

- RBAC middleware on domain routes (downstream)
- OAuth, 2FA, admin user management

Architecture Governance Compliance:

- ADR alignment verified
- RBAC enforcement confirmed (rate limiting, role exclusion)
- Service layer architecture maintained (thin controllers → services → repositories)
- Error contract compliance verified (ApiException, Handler)
- All guardians passed in Step 5

Notes:
Drift analysis complete. All 5 guardians returned PASS. Implementation gate open.

## Objective

Implement complete authentication system using Laravel Sanctum. Support API token-based auth for SPA and mobile clients.

## Scope

### Backend

- Registration endpoint with validation
- Login endpoint with token generation
- Logout endpoint (token revocation)
- Password reset flow (email-based)
- Email verification
- Auth middleware configuration
- User profile endpoint (get/update)

### Frontend

- Login page with form validation
- Registration page with form validation
- Forgot password page
- Email verification page
- Auth store (Pinia) with token management
- API client with auth interceptors
- Protected route middleware

### API Endpoints

| Method | Route                     | Description            |
| ------ | ------------------------- | ---------------------- |
| POST   | /api/auth/register        | User registration      |
| POST   | /api/auth/login           | User login             |
| POST   | /api/auth/logout          | User logout            |
| POST   | /api/auth/forgot-password | Request password reset |
| POST   | /api/auth/reset-password  | Reset password         |
| GET    | /api/auth/user            | Get authenticated user |
| PUT    | /api/auth/user            | Update profile         |

## Dependencies

- **Upstream:** STAGE_02_DATABASE_SCHEMA
- **Downstream:** All authenticated features
