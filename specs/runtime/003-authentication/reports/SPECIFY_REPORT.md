# Specify Report — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-12T00:00:00Z

## Specification Summary

| Metric                 | Value                                 |
| ---------------------- | ------------------------------------- |
| User Stories           | 13 (US1–US13)                         |
| Acceptance Criteria    | 64 checkboxes                         |
| Technical Requirements | 22 (14 backend + 8 frontend)          |
| API Endpoints          | 9 with full request/response contract |
| Dependencies           | Upstream: STAGE_02_DATABASE_SCHEMA    |
| Open Questions         | 0                                     |

## Scope Defined

- User registration with role selection and validation (5 roles)
- Login with Sanctum token generation
- Logout with token revocation
- Password reset flow (email-based, rate-limited)
- Email verification (send, resend, verify)
- Authenticated user profile get/update
- Frontend pages: login, register, forgot-password, reset-password
- Pinia auth store with token lifecycle
- API client composable with 401 interceptor
- Nuxt route middleware (auth + guest)
- Arabic/RTL support on all auth pages
- Rate limiting on sensitive endpoints

## Deferred Scope

- RBAC middleware enforcement on domain routes (downstream stage)
- OAuth / social login
- Two-factor authentication (2FA)
- Admin user management CRUD
- Session-based authentication
- User avatar upload
- Account deletion / deactivation

## Risk Assessment

- **HIGH:** Authentication is security-critical — all flows must prevent enumeration, enforce rate limits, hash passwords
- **SEC-FINDING-A:** Role must not be mass-assignable; explicit setter only
- **Rate limiting:** Login (5/min), forgot-password (3/min), email resend (3/min)
- **Anti-enumeration:** Forgot-password always returns success regardless of email existence

## Checklist Status

- Requirements checklist: Created at `checklists/requirements.md`
