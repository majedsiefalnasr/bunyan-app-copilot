# STAGE_03 — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** User auth (Sanctum), registration, login, password reset
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

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
