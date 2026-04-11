# TESTING_GUIDE — ERROR_HANDLING Stage (005)

**Stage:** ERROR_HANDLING  
**Phase:** 01_PLATFORM_FOUNDATION  
**Date:** 2026-04-12  

---

## Overview

This guide provides concrete test scenarios and manual verification steps for the ERROR_HANDLING stage implementation. All automated tests (68+) are passing; this guide covers manual testing and deployment verification.

---

## Pre-Deployment Testing Checklist

### A. Database & Migrations

```bash
# 1. Verify migrations execute cleanly
php artisan migrate:fresh --env=testing
# Expected: 2 new migrations (audit_logs, request_logs)

# 2. Verify tables created
php artisan tinker
>>> Schema::getColumns('audit_logs')
>>> Schema::getColumns('request_logs')
# Expected: All columns present, indexes created

# 3. Verify indexes
>>> DB::select("SHOW INDEXES FROM audit_logs")
# Expected: 7 indexes (user_id+created_at, correlation_id, etc.)
```

### B. Logging Configuration

```bash
# 1. Verify logging channels
grep -A 5 "'channels'" backend/config/logging.php
# Expected: single, daily, stack, errors, audit channels configured

# 2. Test log file creation
php artisan tinker
>>> Log::channel('audit')->info("Test audit log", ['user_id' => 1])
# Expected: New entry in storage/logs/audit.log with timestamp

# 3. Verify JSON formatter (production)
APP_ENV=production php artisan tinker
>>> Log::info("Test", ['key' => 'value'])
# Expected: Log output is valid JSON (not human-readable)
```

### C. Exception Handling

```bash
# Test each exception type returns correct error code
php artisan tinker

# 1. Authentication error
>>> throw new App\Exceptions\AuthenticationException("Invalid credentials")
# Expected: 401 response, error.code = AUTH_INVALID_CREDENTIALS

# 2. RBAC error (new)
>>> throw new App\Exceptions\RoleNotAllowedException("Customer cannot access Admin endpoint")
# Expected: 403 response, error.code = RBAC_ROLE_DENIED, message does NOT expose role

# 3. Validation error
>>> throw new \Illuminate\Validation\ValidationException(validator(['email' => 'required']))
# Expected: 422 response, error.code = VALIDATION_ERROR, error.details populated

# 4. Rate limit error
>>> throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(429)
# Expected: 429 response, error.code = RATE_LIMIT_EXCEEDED, Retry-After header set
```

### D. Correlation ID & Request Logging

```bash
# 1. Test correlation ID generation
curl -X GET http://localhost:8000/api/test \
  -H "Accept: application/json"
# Expected: Response includes header: X-Correlation-ID: <uuid-v4>

# 2. Test correlation ID preservation
curl -X GET http://localhost:8000/api/test \
  -H "X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json"
# Expected: Response header X-Correlation-ID matches sent value

# 3. Verify correlation ID in logs
tail -f storage/logs/requests.log | grep "550e8400"
# Expected: Request logged with matching correlation_id

# 4. Test request logging
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "secret123"}'
# Expected: storage/logs/requests.log contains entry with method, uri, status, response_time_ms
```

### E. Sensitive Data Masking

```bash
# 1. Test masking in logs (requires triggering error with sensitive data)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "super_secret_password_12345"}'

# 2. Inspect log file
grep "super_secret" storage/logs/requests.log
# Expected: ZERO matches (password should be masked)

grep "password.*\*\*\*" storage/logs/requests.log
# Expected: Entries showing passwords masked to ***

# 3. Test token masking
grep "token.*tok_" storage/logs/requests.log
# Expected: Full tokens NOT visible; masked pattern tok_****... present

# 4. Test credit card masking
grep "card.*\*\*\*\*-" storage/logs/requests.log
# Expected: Full card numbers NOT visible; masked pattern present
```

### F. RBAC Role-Based Access Control

```bash
# Test: Customer accessing Admin endpoint should return RBAC_ROLE_DENIED

# 1. Authenticate as Customer role
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "customer@example.com", "password": "password"}'
# Expected: 200 response with auth token

# 2. Use token to access Admin endpoint
TOKEN="<from-step-1>"
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
# Expected: 403 response
# Expected: error.code = "RBAC_ROLE_DENIED" (NOT "AUTH_UNAUTHORIZED")
# Expected: error.message = "Access denied" (does NOT expose role names)

# 3. Test with other role combinations
# Repeat with Contractor → Supervising Architect endpoint, etc.
# Expected: All unauthorized role accesses return RBAC_ROLE_DENIED
```

### G. Rate Limiting

```bash
# Test: 100 request/min global limit, 10 request/min for auth endpoint

# 1. Global rate limit (e.g., POST /api/projects)
for i in {1..110}; do
  curl -s -X POST http://localhost:8000/api/projects \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{...}' \
    -w "Status: %{http_code}\n" &
done
# Expected: Requests 1-100 return 200-299, requests 101+ return 429
# Expected: 429 response includes header: Retry-After: 60

# 2. Auth rate limit (e.g., POST /api/auth/login)
for i in {1..15}; do
  curl -s -X POST http://localhost:8000/api/auth/login \
    -H "Content-Type: application/json" \
    -d '{"email": "user@example.com", "password": "wrong"}' \
    -w "Status: %{http_code}\n"
done
# Expected: Requests 1-10 return 401, requests 11-15 return 429
# Expected: Retry-After header present on 429 responses
```

### H. Frontend Error Handling

```bash
# 1. Open browser developer console (F12)

# 2. Test error boundary
# Navigate to a page with intentional component error
# Expected: Fallback UI displays with "Reload" and "Back" buttons
# Expected: No white screen or unhandled exception shown

# 3. Test error toast
# Trigger a validation error (e.g., submit form without required fields)
# Expected: Toast notification appears (top-right)
# Expected: Message is user-friendly and localized (Arabic/English based on browser locale)
# Expected: Toast dismisses after 5 seconds or on manual close

# 4. Test error pages
# Navigate to /error-404
# Expected: 404 page displays with "Back to Home" button
# Navigate to /error-403
# Expected: 403 page displays with "Access Denied" message
# Navigate to /error-500
# Expected: 500 page displays with "Something Went Wrong" message
# Expected: All error pages use Geist design system styling

# 5. Test RTL layout (Arabic)
# Change browser locale to Arabic
# Navigate to error page
# Expected: Text aligned right-to-left
# Expected: No left-to-right quirks or broken layout
# Expected: Buttons and icons properly positioned for RTL
```

### I. i18n Localization

```bash
# 1. Backend error messages
# Trigger validation error
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -H "Accept-Language: ar" \
  -d '{"email": "invalid-email"}'
# Expected: Error message in Arabic (e.g., "البريد الالكتروني غير صحيح")

# Same request with Accept-Language: en
# Expected: Error message in English (e.g., "The email field is required")

# 2. Frontend error messages
# Console: lang = localStorage.getItem('locale') // Verify locale
# Console: i18n.t('errors.validation_error') // Should match selected language
```

### J. Performance Testing (Logging Overhead)

```bash
# Measure logging overhead: Target <50ms @ 99th percentile

# 1. Install performance testing tool
composer require spatie/simple-benchmarking

# 2. Run benchmark test
php artisan test tests/Feature/LoggingPerformanceTest.php

# Expected output:
# Response time: 45ms (average)
# 99th percentile: 48ms (< 50ms target)
# Logging overhead: ~2-3ms per request
```

### K. API Response Contract Validation

```bash
# Test: All responses follow unified contract { success, data, error }

# 1. Success response (200)
curl -X GET http://localhost:8000/api/projects/1 \
  -H "Authorization: Bearer $TOKEN"
# Expected JSON:
{
  "success": true,
  "data": { /* project details */ },
  "error": null
}

# 2. Validation error response (422)
curl -X POST http://localhost:8000/api/projects \
  -H "Content-Type: application/json" \
  -d '{}'
# Expected JSON:
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "name": ["The name field is required"],
      "description": ["The description field is required"]
    }
  }
}

# 3. Error response (500)
# This should NOT expose stack trace (app-debug off in production)
# Expected JSON:
{
  "success": false,
  "data": null,
  "error": {
    "code": "SERVER_ERROR",
    "message": "Something went wrong"
  }
}
```

---

## Post-Deployment Verification

### A. Production Log Files

```bash
# SSH into production server

# 1. Verify audit logs created
tail -100 storage/logs/audit.log
# Expected: Recent audit entries (JSON format)

# 2. Verify request logs created
tail -100 storage/logs/requests.log | head -5
# Expected: HTTP requests logged (JSON format with correlation_id)

# 3. Scan for unmasked sensitive data
grep -i "password.*[a-z0-9]" storage/logs/*.log | grep -v "password.*\*\*" | wc -l
# Expected: ZERO matches (all passwords masked)

grep -i "token.*Bearer" storage/logs/*.log | wc -l
# Expected: ZERO matches (all tokens masked)

# 4. Verify correlation IDs
tail -50 storage/logs/requests.log | jq .correlation_id | sort -u | wc -l
# Expected: 40-50 unique correlation IDs (one per request)
```

### B. Monitoring & Alerting

```bash
# Set up alerts in your monitoring system (e.g., DataDog, New Relic)

# 1. Alert: Error rate > 5% in 5-minute window
# 2. Alert: RBAC_ROLE_DENIED errors spike (indicates attack)
# 3. Alert: RATE_LIMIT_EXCEEDED errors spike (indicates load or attack)
# 4. Alert: Unmasked sensitive data detected in logs (security alert)
# 5. Alert: Correlation ID missing in response headers (logging failure)
```

### C. User Acceptance Testing

```bash
# 1. Test complete user flow with error handling
# Scenario: User tries invalid login, then valid login, then accesses resource they don't have permission to

# Step 1: Invalid login
# - Error toast displays "Invalid credentials"
# - No unhandled exception shown
# - User can retry

# Step 2: Valid login
# - Redirected to dashboard
# - No errors

# Step 3: Access denied
# - Error page shows (403 Forbidden)
# - Message is clear
# - User can navigate back

# 2. Test mobile responsiveness
# Test all error pages on mobile (iPhone, Android)
# Expected: Layout responsive, text readable, buttons clickable

# 3. Test accessibility
# Use screen reader (NVDA, JAWS)
# Expected: Error messages readable
# Expected: ARIA labels present on buttons and icons
```

---

## Rollback Plan

If issues discovered post-deployment:

```bash
# 1. Revert database migrations
php artisan migrate:rollback --step=2
# (Reverts audit_logs and request_logs tables)

# 2. Revert code changes
git revert HEAD~1..HEAD --no-edit

# 3. Clear application cache
php artisan cache:clear
php artisan config:cache
```

---

## Support & Troubleshooting

| Issue | Root Cause | Solution |
| --- | --- | --- |
| Correlation ID missing in logs | Middleware not registered | Verify CorrelationIdMiddleware in Http/Kernel.php |
| PII data visible in logs | Masking not applied | Verify SensitiveFields.php registry and masking middleware |
| Log file not created | Logging channel misconfigured | Check storage/logs/ permissions, verify config/logging.php |
| Rate limiting not working | Rate limiter not configured | Install Laravel rate-limiter, configure in config/cache.php |
| Frontend error toast not showing | Toast store not mounted | Verify errorStore configured in app.vue |
| ErrorBoundary not catching errors | Not wrapped in app | Verify GlobalErrorBoundary wraps router-view in app.vue |

---

## Summary

This testing guide covers:
- ✅ Database and logging setup
- ✅ Exception handling for all error codes
- ✅ Correlation ID generation and propagation
- ✅ Sensitive data masking
- ✅ RBAC error handling
- ✅ Rate limiting
- ✅ Frontend error UI
- ✅ i18n localization
- ✅ Performance benchmarking
- ✅ API contract validation
- ✅ Post-deployment verification

**All manual test scenarios should pass before declaring deployment successful.**
