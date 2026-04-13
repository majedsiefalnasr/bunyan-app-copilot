# Plan Report — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-12T00:00:00Z

## Plan Summary

| Metric         | Value                                                                   |
| -------------- | ----------------------------------------------------------------------- |
| New Tables     | 0 (all tables from prior stages)                                        |
| New Endpoints  | 9 (register, login, logout, forgot/reset, verify, resend, get/put user) |
| New Services   | 1 (AuthService)                                                         |
| New Pages      | 4 (login, register, forgot-password, reset-password)                    |
| New Components | 0 (uses Nuxt UI primitives)                                             |
| Form Requests  | 5 (Register, Login, ForgotPassword, ResetPassword, UpdateProfile)       |
| API Resources  | 1 (UserResource)                                                        |

## Architecture Decisions

- AuthController extends BaseController (inherits ApiResponseTrait)
- AuthService encapsulates all business logic (registration, login, logout, password reset, email verification, profile)
- UserRepository extended with findByEmail(), create(), updateProfile() methods
- MustVerifyEmail interface added to User model
- Rate limiting via named limiters (auth-login:5/min, auth-forgot:3/min, auth-resend:3/min)
- Admin self-registration blocked at Form Request validation level
- Token persistence via useCookie for SSR compatibility
- Logout uses POST (not DELETE) per spec

## Guardian Verdicts

| Guardian              | Verdict | Notes                                                         |
| --------------------- | ------- | ------------------------------------------------------------- |
| Architecture Guardian | PASS    | All 12 checks pass. 4 non-blocking observations resolved      |
| API Designer          | PASS    | 2 spec fixes applied (email verify GET, error code alignment) |

## Risk Assessment

| Risk Level | Count | Details                                                                |
| ---------- | ----- | ---------------------------------------------------------------------- |
| HIGH       | 2     | Password handling security, rate limiting effectiveness                |
| MEDIUM     | 3     | Token expiration config, email delivery reliability, SSR compatibility |
| LOW        | 2     | i18n completeness, frontend validation parity                          |

## Research Findings

8 research questions resolved in research.md covering Sanctum setup, email verification flow, token persistence strategy, admin blocking, rate limiting configuration, and frontend auth composable patterns.

## Data Model

No new migrations required. Changes limited to:

- Add `MustVerifyEmail` interface to `User` model
- Existing tables used: `users`, `personal_access_tokens`, `password_reset_tokens`
