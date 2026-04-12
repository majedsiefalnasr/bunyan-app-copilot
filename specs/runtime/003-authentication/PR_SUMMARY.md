# PR — Authentication

## Summary

**Stage:** Authentication
**Phase:** 01_PLATFORM_FOUNDATION
**Branch:** `spec/003-authentication` → `develop`
**Tasks:** 37 / 37 completed

Complete authentication system for the Bunyan construction marketplace, implementing user registration, login/logout, password management, email verification, and profile management using Laravel Sanctum (backend) and Nuxt.js (frontend).

## What Changed

### Backend

- **User Model**: Added `MustVerifyEmail` interface
- **Exception System**: `ApiException` custom exception + `Handler` with `InvalidSignatureException` support (403)
- **5 Form Requests**: RegisterRequest (admin excluded, Saudi phone regex), LoginRequest, ForgotPasswordRequest, ResetPasswordRequest, UpdateProfileRequest
- **AuthService**: 9 business logic methods (register, login, logout, forgotPassword, resetPassword, getProfile, updateProfile, verifyEmail, resendVerification)
- **AuthController**: Thin controller with 9 actions delegating to AuthService
- **UserResource**: API resource (excludes password, remember_token)
- **Rate Limiting**: 4 rate limiters in AppServiceProvider (login 5/min, register 5/min, forgot-password 3/min, email-resend 3/min)
- **Routes**: Complete auth route group in `api.php` with Sanctum middleware
- **Handler binding**: Explicit singleton binding in `bootstrap/app.php` (Laravel 13 requirement)

### Frontend

- **Zod Validation**: Login, register, forgot-password, reset-password schemas
- **useAuth Composable**: Full auth API integration (register, login, logout, forgotPassword, resetPassword, fetchCurrentUser)
- **Auth Store (Pinia)**: User state management (isAuthenticated, setUser, clearUser)
- **Middleware**: `auth.ts` (require auth) + `guest.ts` (guest-only)
- **AuthCard Component**: Reusable form card with RTL support
- **6 Pages**: login, register, forgot-password, reset-password, verify-email, dashboard
- **i18n**: 88 Arabic + 88 English translation keys
- **Test Infrastructure**: Vue auto-import stubs + Nuxt composable stubs in `tests/setup.ts`

### Database

- No new migrations (existing User model schema sufficient)

## Breaking Changes

- None

## Testing

- [x] Unit tests pass — 10 backend unit tests (AuthServiceTest)
- [x] Feature tests pass — 46 backend feature tests (7 test files)
- [x] Frontend tests pass — 122 tests across 14 files
- [x] Lint passes — Pint clean, ESLint clean
- [x] Type check passes — PHPStan 0 errors (level 5)
- [x] Migration validated — `php artisan migrate --pretend` → nothing to migrate

## Checklist

- [x] RBAC middleware applied on all new routes (Sanctum auth + rate limiting)
- [x] Form Request validation on all new endpoints (5 Form Requests)
- [x] Arabic/RTL support verified (88 keys per locale, AuthCard RTL-aware)
- [x] Error contract followed (ApiException, Handler, error code registry)
- [x] No N+1 queries (single user fetch per request)
- [x] API documentation updated (routes documented in TESTING_GUIDE.md)
- [x] Migration tested — no new migrations needed

## Security

- Admin role excluded from self-registration
- No email enumeration on forgot-password (always returns success)
- All tokens revoked on password reset
- Rate limiting on sensitive endpoints
- Password hashed with bcrypt (Laravel default)
- Signed URLs for email verification
- Input validation via Form Requests before reaching service layer

## Related

- Stage File: `specs/phases/01_PLATFORM_FOUNDATION/STAGE_03_AUTHENTICATION.md`
- Testing Guide: `specs/runtime/003-authentication/guides/TESTING_GUIDE.md`
- Implement Report: `specs/runtime/003-authentication/reports/IMPLEMENT_REPORT.md`
- Validation Report: `specs/runtime/003-authentication/audits/VALIDATION_REPORT.md`
