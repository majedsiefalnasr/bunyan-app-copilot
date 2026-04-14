# Auth Pages Implementation Guide

## Overview

This guide documents the complete authentication system for Bunyan construction marketplace, including component hierarchy, API contracts, token flows, and troubleshooting.

## Architecture

### Authentication Flow

```
User → Login/Register → Email Verification → Dashboard
                ↓
            Token Management (Access + Refresh)
                ↓
        Rate Limiting + Account Lockout
```

## Component Hierarchy

```
AuthLayout
├── AuthCard
│   └── Form Components
│       ├── UInput (email, password)
│       ├── USelect (city/district)
│       ├── PasswordStrength
│       ├── OtpInput
│       └── OtpInput (email verify)
└── Navigation Links (forgot password, register, etc)
```

## API Contracts

### Login Endpoint

- **URL**: `POST /api/v1/auth/login`
- **Request**: `{ email: string, password: string }`
- **Response**: `{ token: string, user: User }`
- **Errors**: `AUTH_INVALID_CREDENTIALS`, `AUTH_UNAUTHORIZED`, `RATE_LIMIT_EXCEEDED`

### Register Endpoint

- **URL**: `POST /api/v1/auth/register`
- **Request**: Registration form data (all 4 steps)
- **Response**: `{ message: "Verify email" }`
- **Errors**: `VALIDATION_ERROR`, `CONFLICT_ERROR`

### Verify Email Endpoint

- **URL**: `POST /api/v1/auth/verify-email`
- **Request**: `{ code: string }`
- **Response**: `{ token: string, user: User }`
- **Errors**: `WORKFLOW_PREREQUISITES_UNMET` (code expired), `RATE_LIMIT_EXCEEDED`

### Reset Password Endpoint

- **URL**: `POST /api/v1/auth/reset-password`
- **Request**: `{ token: string, password: string }`
- **Response**: `{ message: "Password reset" }`
- **Errors**: `WORKFLOW_PREREQUISITES_UNMET` (token expired)

## Token Flow Diagram

```
1. User Login
   ↓
2. Backend returns:
   - access_token (15 min)
   - refresh_token (7-30 days, HTTP-only cookie)
   ↓
3. Frontend stores:
   - access_token in Pinia store
   - refresh_token in HTTP-only cookie (automatic)
   ↓
4. On 401 (token expired):
   - Interceptor detects 401
   - Sends refresh_token to backend
   - Backend returns new access_token
   - Interceptor retries original request
   ↓
5. On refresh_token expiry:
   - Redirect to /auth/login
```

## Testing Guide

### Unit Tests

```bash
# Schema validation
npm run test -- schemas/auth.spec.ts

# Composables
npm run test -- composables/useAuth.spec.ts
npm run test -- composables/usePasswordToggle.spec.ts

# i18n coverage
npm run test -- i18n/auth-locales.spec.ts
```

### E2E Tests

```bash
# Full auth flow
npm run test:e2e -- e2e/auth.spec.ts

# Password reset flow
npm run test:e2e -- e2e/password-reset.spec.ts

# Email verification
npm run test:e2e -- e2e/verification.spec.ts

# Profile management
npm run test:e2e -- e2e/profile.spec.ts

# Smoke tests
npm run test:e2e -- e2e/smoke.spec.ts
```

### Accessibility Testing

```bash
# A11y compliance
npm run test:a11y -- a11y/auth-pages-a11y.spec.ts
```

### Performance Testing

```bash
# Performance metrics
npm run test:perf -- performance/auth-pages-perf.spec.ts

# RTL visual
npm run test:visual -- visual/auth-pages-rtl.spec.ts
```

## Password Requirements

- Minimum 8 characters
- At least one uppercase letter (A-Z)
- At least one lowercase letter (a-z)
- At least one digit (0-9)
- At least one special character (!@#$%^&\*)

Regex: `/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,128}$/`

## Troubleshooting

### "Token Expired" Error

**Symptoms**: Redirected to login after short inactivity

**Solution**: Check if refresh_token cookie is being set. Verify backend is using HTTP-only, Secure, SameSite attributes.

### RTL Layout Issues

**Symptoms**: Text appears left-aligned in Arabic, spacing is wrong

**Solution**: Verify i18n middleware is setting `dir="rtl"` on documentElement. Check all CSS uses logical properties (not margin-left/right).

### "Rate Limit Exceeded"

**Symptoms**: Login attempts blocked

**Solution**: Rate limiting is 10 fails per 15 minutes per IP. Wait 15 minutes or contact support for manual unlock.

### Email Verification Code Not Received

**Symptoms**: User can't see code in email

**Solution**: Check email backend is configured. Verify email is not in spam. Resend code after 60-second cooldown.

### Avatar Upload Fails

**Symptoms**: Upload button does nothing

**Solution**: File must be JPEG/PNG/WebP and < 5MB. Check browser console for network errors. Verify multipart/form-data is being sent.

## Locale Coverage

### Required Keys in ar.json + en.json

- `auth.login.*` — Login page
- `auth.register.*` — Register wizard (4 steps)
- `auth.forgot_password.*` — Forgot password
- `auth.reset_password.*` — Reset password
- `auth.verify_email.*` — Email verification
- `auth.profile.*` — Profile
- `auth.change_password.*` — Change password
- `auth.password_strength.*` — Strength indicators
- `auth.logout.*` — Logout

All keys must have Arabic and English translations. Missing keys will cause i18n warnings.

## Performance Targets

- **FCP (First Contentful Paint)**: < 1.8s
- **LCP (Largest Contentful Paint)**: < 2.5s
- **CLS (Cumulative Layout Shift)**: < 0.1
- **Auth bundle size**: < 50KB gzipped

## Security Considerations

1. **Password Hash**: PBKDF2 with 100K iterations (backend)
2. **Rate Limiting**: 10 login attempts per 15 minutes per IP
3. **Account Lockout**: 5 failed attempts = 15 minute lock
4. **Token Rotation**: Refresh token regenerated on each use
5. **CSRF Protection**: XSRF-TOKEN cookie + header validation (middleware)
6. **Password History**: Prevent reusing last 3 passwords (backend)
7. **Session Timeout**: 7-30 day token expiry
8. **Concurrent Sessions**: Device fingerprinting limits active sessions (backend)

## Deployment

Before deploying to production:

1. Run full test suite: `npm run test && npm run test:e2e`
2. Run linter: `npm run lint`
3. Run static analysis: `npm run typecheck`
4. Verify Lighthouse score ≥ 90
5. Verify WCAG 2.1 AA compliance
6. Test RTL layout in Arabic locale
7. Verify error handling for all error codes
8. Smoke test the full auth flow

---

See also:

- [DESIGN.md](../DESIGN.md) — Visual design language
- [docs/ai/AI_ENGINEERING_RULES.md](../../docs/ai/AI_ENGINEERING_RULES.md) — Development standards
