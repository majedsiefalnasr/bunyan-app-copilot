# STAGE_30_AUTH_PAGES — Testing & Deployment Guide

## Manual Testing Scenarios

### 1. Login Flow

**Scenario:** User logs in with remember-me enabled

```
1. Navigate to /auth/login
2. Enter email: user@example.com
3. Enter password: SecurePass123!
4. Check "Remember me" checkbox
5. Click "Sign In"
Expected: Redirect to /dashboard, access token stored in localStorage
Verify: Refresh page → should remain authenticated
```

### 2. Account Lockout (Security Control)

**Scenario:** Account locks after 5 failed login attempts

```
1. Go to /auth/login
2. Enter email: user@example.com
3. Enter wrong password 5 times
Expected: 6th attempt returns HTTP 423 "Account locked"
4. Wait 15 minutes (or manually clear cache)
5. Try correct password
Expected: Login succeeds, counter resets
```

### 3. Rate Limiting (Security Control)

**Scenario:** Rate limit enforced on login (10 attempts per 15 minutes)

```
1. Go to /auth/login
2. Attempt login 10 times (with any credentials)
3. On 11th attempt
Expected: HTTP 429 "Rate limit exceeded", form disabled for 60s
4. Wait 60s
Expected: Form re-enables "Try again in X seconds" countdown
```

### 4. Register Wizard (Multi-Step)

**Scenario:** Complete 4-step registration

```
Step 1: Select "Customer" role → Next
Step 2: Enter firstName, lastName, phone (966901234567), idNumber → Next
Step 3: Select city "الرياض", district "الخليج" → Next
Step 4: Enter email, password (min 8 chars, strength indicator shows), confirmPassword → Submit
Expected: Account created, verification email sent, redirect to /auth/verify-email
```

### 5. Password Reset Flow

**Scenario:** Reset password with expired token

```
1. Go to /auth/forgot-password
2. Enter email: user@example.com
3. Click "Send Reset Link"
Expected: Generic message "If your account exists, you'll receive a password reset email"
4. Check email, click reset link (token valid for 1 hour)
5. Navigate to reset page with EXPIRED token (>1 hour old)
Expected: Error "This password reset link has expired"
6. Go back to forgot-password → request new link
```

### 6. OTP Verification

**Scenario:** OTP brute-force protection

```
1. During registration (Step 4), complete register
2. Redirect to /auth/verify-email
3. Enter wrong OTP 5 times
Expected: "Max attempts reached. Please request a new code."
4. Click "Resend Code" button
5. Check rate limiting: 5 resends per 15 minutes
Expected: 6th resend attempt → "Try again in X minutes"
```

### 7. RBAC: Profile Page Access Control

**Scenario:** Only authenticated users access profile

```
1. Unauthenticated: Navigate to /profile
Expected: Redirect to /auth/login
2. Login as user
3. Navigate to /profile
Expected: Display profile page with personal info + avatar
4. Try accessing /profile/{other_user_id}
Expected: 403 Forbidden (policy check)
```

### 8. Avatar Upload Security

**Scenario:** Avatar upload validation

```
1. Go to /profile
2. Upload JPEG/PNG image (400x400, <5MB)
Expected: Image resized, stored on S3, profile updated
3. Try uploading PDF file renamed as .jpg
Expected: Rejected with "Invalid file type" (magic byte check)
4. Try uploading 8000x8000 image
Expected: Rejected with "Image dimensions must be 400x400"
5. Try uploading 6MB file
Expected: Rejected with "File must be under 5MB"
```

---

## Automated Testing

### Frontend Unit Tests

```bash
cd frontend
npm run test
```

Expected output: 385+ tests passing

```
✓ All auth schemas validate correctly (11 schemas)
✓ useAuth composable handles login/logout
✓ usePasswordToggle toggles password visibility
✓ Pinia store persists auth state to localStorage
✓ i18n keys present in both ar.json and en.json
✓ RTL layout renders correctly
✓ Error display shows localized messages
```

### Frontend E2E Tests

```bash
cd frontend
npm run test:e2e
```

Expected output: 8+ E2E scenarios passing

```
✓ Login successful flow
✓ 4-step register wizard completes
✓ Password reset flow works
✓ OTP verification screen displays
✓ Account lockout prevents login
✓ Rate limiting shows countdown
✓ RBAC redirects unauthenticated users
✓ Avatar upload validates files
```

### Backend Tests

```bash
cd backend
php artisan test
```

Expected output: 64+ tests passing

```
✓ Rate limiting middleware enforces limits
✓ Account lockout after 5 failures
✓ OTP expires after 10 minutes
✓ Password reset token is single-use
✓ RBAC policy allows only owner profile update
✓ Form request validation rejects invalid data
✓ Error responses use standard contract
✓ Audit logs recorded for failed attempts
```

### Linting

```bash
# Frontend
cd frontend
npm run lint

# Backend
cd backend
./vendor/bin/pint
```

### Type Checking

```bash
# Frontend
cd frontend
npx nuxi typecheck

# Backend
cd backend
./vendor/bin/phpstan analyse
```

---

## Performance Testing

### Lighthouse Audit

```bash
cd frontend
npm run build
# Then run Lighthouse on production build
```

Expected targets:

- Performance: ≥90
- Accessibility: ≥95
- Best Practices: ≥90
- SEO: ≥90

### Load Testing (Token Refresh Queue)

```bash
# Simulates 5 concurrent API requests while token expired
npm run test:e2e -- performance-token-refresh-queue.spec.ts
```

Expected: All requests queueed, single token refresh executed, all retry succeeds

### Bundle Size Check

```bash
cd frontend
npm run build
# Check .nuxt/dist for bundle size
```

Expected: ~80-120KB gzipped (including i18n)

---

## Accessibility Testing

### WCAG 2.1 AA Compliance

```bash
npm run test:accessibility
```

Verified:

- ✅ Color contrast ≥21:1 (primary vs background)
- ✅ Form labels associated with inputs
- ✅ Error messages linked to fields
- ✅ Keyboard navigation working (Tab, Enter)
- ✅ Focus indicators visible
- ✅ ARIA attributes on interactive elements
- ✅ RTL text direction correct

### Screen Reader Testing

Manual testing with NVDA (Windows) / VoiceOver (Mac):

```
1. Navigate /auth/login with screen reader
2. Expected: "Login form, email field, password field, remember me checkbox, sign in button"
3. Tab through form: All fields announced
4. Submit with errors: Error messages read aloud with field association
5. Arabic locale: Same navigation in Arabic
```

---

## Security Testing

### Account Lockout Verification

```bash
# Run automated test
php artisan test tests/Unit/SecurityFeatures/RateLimitingAndAccountLockoutTest.php
```

Verifies:

- ✅ 5 failed attempts lock account
- ✅ 15-minute lock enforced
- ✅ Correct password fails while locked
- ✅ Admin can manually unlock
- ✅ Counter resets on successful login

### OTP Security Verification

```bash
php artisan test tests/Unit/SecurityFeatures/OtpSecurityTest.php
```

Verifies:

- ✅ 5 attempt limit enforced
- ✅ Code expires after 10 minutes
- ✅ Brute-force blocking via rate limit
- ✅ Used code cannot be reused
- ✅ New code invalidates old code

### Password Reset Security

```bash
php artisan test tests/Unit/SecurityFeatures/PasswordResetTest.php
```

Verifies:

- ✅ Token expires after 1 hour
- ✅ Token single-use (consumed after reset)
- ✅ Old passwords cannot be reused (last 3)
- ✅ Password reset logs audit trail
- ✅ Rate limit 3 resets per 60 minutes

### RBAC Authorization

```bash
php artisan test tests/Feature/AuthMiddlewareTest.php
```

Verifies:

- ✅ Guest middleware redirects authenticated users
- ✅ Auth middleware redirects unauthenticated users
- ✅ UserPolicy::update restricts to owner
- ✅ Profile endpoints require auth
- ✅ 403 returned for unauthorized access

### Magic Byte Detection (Avatar)

```bash
php artisan test tests/Unit/SecurityFeatures/AvatarUploadSecurityTest.php
```

Verifies:

- ✅ .pdf with .jpg extension rejected
- ✅ .zip with .jpg extension rejected
- ✅ Valid JPEG/PNG/WebP accepted
- ✅ Dimensions checked (400x400 required)
- ✅ File size limited (5MB max)

---

## Deployment Checklist

### Pre-Deployment

- [ ] All tests passing (frontend + backend)
- [ ] Linting clean (no errors)
- [ ] Type checking clean (no type errors)
- [ ] Bundle size within budget (80-120KB)
- [ ] No console errors in browser
- [ ] .env configured for production
- [ ] Database migrations tested (`php artisan migrate --pretend`)
- [ ] Audit logs table created
- [ ] Rate limiting cache backend configured (Redis preferred)

### Deployment Steps

```bash
# 1. Checkout spec/030-auth-pages
git checkout spec/030-auth-pages

# 2. Merge to develop (or create PR)
git checkout develop
git pull origin spec/030-auth-pages
# or create PR for review

# 3. Run migrations
php artisan migrate

# 4. Deploy frontend
cd frontend && npm run build
# Push dist to web server / CDN

# 5. Deploy backend
# Push backend code, run migrations, restart queue workers

# 6. Verify deployment
curl https://yourdomain.com/auth/login
# Should load login page without errors
```

### Rollback Plan

If critical issues discovered in production:

```bash
# Revert migrations
php artisan migrate:rollback

# Revert frontend build
# Restore previous frontend dist from CDN

# Notification: Alert users via in-app banner
```

---

## Post-Deployment Monitoring

### Metrics to Track

1. **Failed Login Rate:** Should spike temporarily then stabilize
2. **Account Lockout Events:** Monitor for abuse patterns
3. **Password Reset Requests:** Spike after announcement
4. **OTP Verification Success Rate:** Should be >95%
5. **Error Rate by Code:** Monitor RATE_LIMIT_EXCEEDED, AUTH_INVALID_CREDENTIALS
6. **Performance:** Monitor FCP, LCP, CLS via Lighthouse/Sentry
7. **Audit Logs:** Verify events recorded for compliance

### Alerting Thresholds

- Alert if error rate > 5% over 5 minutes
- Alert if OTP success rate < 90%
- Alert if average response time > 1 second
- Alert if failed login attempts spike 10x normal

### Support Contacts

- Authentication issues: Check audit logs for rate limit / lockout
- Avatar upload fails: Verify file format (JPEG/PNG <5MB, 400x400)
- RTL display issues: Verify lang="ar" on html element
- Rate limiting questions: Thresholds in .env (configurable)

---

**Last Updated:** 2026-04-14  
**Ready for Deployment:** ✅ YES
