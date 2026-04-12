# STAGE_03 — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** User auth (Sanctum), registration, login, password reset
> **Risk Level:** HIGH

## Stage Status

Status: DRAFT
Step: plan
Risk Level: HIGH
Last Updated: 2026-04-12T00:00:00Z

Scope Planned:

- 9 API endpoints (register, login, logout, forgot/reset password, email verify/resend, profile get/update)
- AuthService + UserRepository extensions
- 5 Form Requests, 1 API Resource
- 4 frontend pages with RTL/Arabic
- Pinia auth store, API composable, route middleware

Deferred Scope:

- RBAC middleware on domain routes (downstream)
- OAuth, 2FA, admin user management

Architecture Governance Compliance:

- Technical plan compliant — task generation authorized

Notes:
Technical plan complete. Guardian and API Designer both PASS. Task breakdown in progress.

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
