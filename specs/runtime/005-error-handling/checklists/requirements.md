# Requirements Checklist — ERROR_HANDLING

> **Stage:** STAGE_05_ERROR_HANDLING  
> **Phase:** 01_PLATFORM_FOUNDATION  
> **Created:** 2026-04-11T00:00:00Z

## Architecture Compliance

- [ ] Error contract defined and documented
- [ ] All API responses follow error contract format (success/data/error)
- [ ] Error codes use semantic naming (AUTH*\*, VALIDATION*\_, WORKFLOW\_\_, etc.)
- [ ] Global exception handler in place and tested
- [ ] No business logic in exception handlers
- [ ] Error responses do NOT expose internal stack traces to clients
- [ ] Stack traces logged server-side in structured format
- [ ] Correlation IDs propagate through request lifecycle
- [ ] Logging middleware non-intrusive (does not block requests)

## Security

- [ ] Authentication exceptions (401) returned for invalid/expired tokens
- [ ] Authorization exceptions (403) returned for insufficient permissions
- [ ] RBAC-specific error code (`RBAC_ROLE_DENIED`) used for role denials
- [ ] Validation errors return field-level details without exposing business logic
- [ ] SQL errors caught and converted to generic `SERVER_ERROR`
- [ ] Database connection errors do NOT expose connection string/host info
- [ ] Stack traces never exposed to client (even in error detail fields)
- [ ] Sensitive fields (passwords, tokens, PII) excluded from logs
- [ ] Rate limiting error (429) supported
- [ ] Correlation IDs used to track suspicious patterns

## Backend Requirements (Laravel)

### Exception Handler

**File:** `backend/app/Exceptions/Handler.php`

- [ ] Class extends `ExceptionHandler`
- [ ] `register()` method exists
- [ ] `render()` method detects JSON requests
- [ ] Returns JSON for JSON requests (no HTML fallback)
- [ ] Catches `ValidationException` and returns 422 with field errors
- [ ] Catches `AuthenticationException` and returns 401
- [ ] Catches `AuthorizationException` and returns 403
- [ ] Catches `ModelNotFoundException` and returns 404
- [ ] Returns 500 for unhandled exceptions
- [ ] Logs exceptions with context (user_id, endpoint, correlation_id)

### Error Code Enum

**File:** `backend/app/Enums/ApiErrorCode.php`

- [ ] Enum defines at least 12 error codes
- [ ] Each code has: value (string), HTTP status, default message
- [ ] Codes include: AUTH_INVALID_CREDENTIALS, AUTH_TOKEN_EXPIRED, AUTH_UNAUTHORIZED, etc.
- [ ] Enum methods provide lookup by code or HTTP status
- [ ] Used consistently across codebase

### Response Helper

**File:** `backend/app/Traits/ApiResponse.php` or `backend/app/Http/Controllers/Api/BaseController.php`

- [ ] `success($data, $message, $statusCode)` method returns success format
- [ ] `error($message, $details, $statusCode, $code)` method returns error format
- [ ] Both methods follow error contract exactly
- [ ] Success returns: `{ success: true, data: {...}, error: null }`
- [ ] Error returns: `{ success: false, data: null, error: { code, message, details } }`
- [ ] All API controllers inherit or use the helper
- [ ] JSON serialization handles edge cases (null, arrays, nested objects)

### Logging Configuration

**File:** `backend/config/logging.php`

- [ ] Multiple channels configured (single, daily, stderr)
- [ ] Default channel suitable for production
- [ ] Daily channel rotates logs by date
- [ ] Log format includes timestamp, level, message, context
- [ ] JSON format available for production (queryable logs)
- [ ] Human-readable format available for development

### Correlation ID Middleware

**File:** `backend/app/Http/Middleware/CorrelationIdMiddleware.php`

- [ ] Middleware generates UUID for each request
- [ ] Correlation ID stored in request (accessible via `request()->correlationId`)
- [ ] Correlation ID added to Log context
- [ ] Correlation ID returned in response header (`X-Correlation-ID`)
- [ ] Existing correlation ID preserved if provided in request

### Request/Response Logging Middleware

**File:** `backend/app/Http/Middleware/RequestResponseLoggingMiddleware.php`

- [ ] Logs HTTP method, URI, status code, response time
- [ ] Logs request payload size
- [ ] Logs user ID (if authenticated)
- [ ] Does NOT log sensitive fields (passwords, tokens)
- [ ] Logs correlation ID
- [ ] Timestamp included
- [ ] Response time accurate (milliseconds)

### API Resources & Response Formatting

- [ ] All API responses use Resource classes or helper methods
- [ ] Resources format data consistently
- [ ] Resources include error information when applicable
- [ ] Pagination responses include meta information (page, per_page, total)

## Frontend Requirements (Nuxt.js)

### Global Error Boundary

**File:** `frontend/app.vue` or `frontend/layouts/default.vue`

- [ ] Error boundary component present
- [ ] Catches component render errors
- [ ] Displays user-friendly error message
- [ ] Provides option to reload or navigate back
- [ ] Errors logged with correlation ID

### API Error Interceptor

**File:** `frontend/composables/useApi.ts` or `frontend/plugins/api.ts`

- [ ] Wraps fetch or axios
- [ ] Detects 4xx/5xx responses automatically
- [ ] Parses error response according to error contract
- [ ] Extracts and emits error message to toast system
- [ ] Handles special cases: 401 (redirect to login), 403 (show forbidden page)
- [ ] Preserves correlation ID from response header
- [ ] Does NOT expose internal error details to user

### Toast Notification System

**File:** `frontend/components/UI/Toast.vue` or `frontend/composables/useToast.ts`

- [ ] Composable `useToast()` with `show()` method
- [ ] Toast component displays error, warning, success, info
- [ ] Auto-dismisses after 5 seconds (configurable)
- [ ] Multiple toasts can stack
- [ ] Positioned consistently (top-right by default)
- [ ] Accessible (ARIA labels, keyboard focus)

### Error Page Components

**Files:** `frontend/pages/error-404.vue`, `frontend/pages/error-403.vue`, `frontend/pages/error-500.vue`

- [ ] 404 page: shows "Resource Not Found" with back button
- [ ] 403 page: shows "Access Denied" with contact admin link
- [ ] 500 page: shows "Server Error" with retry button
- [ ] All pages use RTL layout
- [ ] All text in Arabic and English (via i18n)
- [ ] Icons or illustrations included
- [ ] Mobile responsive

### i18n Integration

**Files:** `frontend/locales/ar.json`, `frontend/locales/en.json`

- [ ] All error messages use translation keys
- [ ] Arabic translations include proper RTL support
- [ ] English translations provided
- [ ] Messages are user-friendly (no technical jargon)
- [ ] Numbers and dates formatted per locale
- [ ] Direction set correctly (RTL for Arabic, LTR for English)

## Error Code Documentation

**File:** `docs/API_ERROR_CODES.md` or `docs/api/error-codes.md`

- [ ] All error codes documented with: code, HTTP status, description
- [ ] Example scenario for each error code
- [ ] Example request/response for each code
- [ ] Handling strategy documented (client actions)
- [ ] Custom domain error codes documented
- [ ] Document includes table of contents
- [ ] Code examples in markdown with syntax highlighting

## Testing

### Unit Tests

- [ ] Exception handler tested with various exception types
- [ ] Response helper tested for success and error formatting
- [ ] Error codes enum tested for correctness
- [ ] Correlation ID middleware tested
- [ ] Logging middleware tested
- [ ] Edge cases tested (null data, nested errors, special characters)

### Feature/Integration Tests

- [ ] API endpoint returns success response format
- [ ] API endpoint returns error response on validation failure
- [ ] API endpoint returns 401 on auth failure
- [ ] API endpoint returns 403 on authorization failure
- [ ] API endpoint returns 404 on resource not found
- [ ] API endpoint returns 500 on unhandled exception
- [ ] Error response includes correlation ID header
- [ ] Logs created for all requests and errors

### Frontend Tests

- [ ] Error boundary catches errors
- [ ] Toast notification displays on API error
- [ ] 404 page displays when resource not found
- [ ] 403 page displays when forbidden
- [ ] 500 page displays when server error
- [ ] Error messages display in correct language

## Performance

- [ ] Error response time < 100ms (serialization, header inclusion)
- [ ] Logging does NOT delay response (async where applicable)
- [ ] Exception handling does NOT degrade under load (10k req/s)
- [ ] Correlation ID generation (<1ms per request)
- [ ] Middleware stack does NOT significantly impact performance

## i18n & Localization

- [ ] All user-facing error messages use translation keys
- [ ] Arabic translations provided for all error messages
- [ ] English translations provided for all error messages
- [ ] Messages appropriate for non-technical users
- [ ] No hardcoded English-only messages in code
- [ ] Date/time formatting includes locale info
- [ ] Number formatting follows locale rules

## Documentation & Developer Experience

- [ ] Error contract documented and linked from README
- [ ] Error code registry available to developers
- [ ] Examples provided for common error scenarios
- [ ] Backend developer guide includes error handling patterns
- [ ] Frontend developer guide includes error handling examples
- [ ] API documentation includes error codes and examples
- [ ] Troubleshooting guide mentions common error codes
- [ ] Error message best practices documented

## Compliance & Standards

- [ ] Error responses follow HTTP status code standards (RFC 7231)
- [ ] JSON responses use valid JSON format (no trailing commas, etc.)
- [ ] Correlation IDs follow UUID v4 standard
- [ ] Logging follows structured logging best practices
- [ ] No sensitive data exposed in any error response
- [ ] Arabic support verified (RTL layout, proper encoding)
- [ ] Mobile responsive error pages verified

## Deployment & Maintenance

- [ ] Error handler tested in production mode
- [ ] Logs configured for production environment
- [ ] Log rotation configured (daily with 30-day retention)
- [ ] Monitoring/alerting configured for 5xx errors
- [ ] Deployment does NOT require config changes for error handling
- [ ] Rollback procedure includes error handler verification
- [ ] Error code registry versioning strategy documented
