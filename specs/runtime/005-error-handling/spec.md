# STAGE_05 — Error Handling & Logging

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Stage File:** `specs/phases/01_PLATFORM_FOUNDATION/STAGE_05_ERROR_HANDLING.md`  
> **Branch:** `spec/005-error-handling`  
> **Created:** 2026-04-11T00:00:00Z

## Objective

Establish a unified, platform-wide error handling contract and structured logging foundation that ensures consistent error reporting, traceability, and user experience across all backend and frontend layers. This stage provides the foundational error patterns that all downstream stages depend on.

## Scope

### In Scope

**Backend (Laravel):**
- Custom exception handler with JSON response support
- Comprehensive error code registry and documentation
- API response helper trait/class for consistent success/error responses
- Structured logging configuration (channels, formatters, middleware)
- Request/response logging middleware with payload capture
- Correlation ID middleware for request tracing

**Frontend (Nuxt.js):**
- Global error handler (Nuxt error boundary)
- API error interceptor with automatic error formatting
- Toast notification system for user-friendly error display
- Error page components (404, 500, 403)
- User-facing error messages in Arabic and English

### Out of Scope

- Custom error analytics dashboard (future stage)
- Real-time error monitoring/alerting (DevOps stage)
- SMS/email error notifications
- Error retry logic (application-specific)
- Field audit logging (done in workflow stage)
- Advanced error recovery strategies

## User Stories

### US1 — Platform-Wide Error Response Contract

**As a** backend developer, **I want** a unified error response format, **so that** all API consumers (frontend, mobile, third-party) can handle errors consistently.

**Acceptance Criteria:**

- [ ] All API responses (success and error) follow documented contract format
- [ ] Error responses include: `success`, `data`, `error` (object with `code`, `message`, `details`)
- [ ] Error codes are semantic (e.g., `AUTH_INVALID_CREDENTIALS`, `VALIDATION_ERROR`)
- [ ] HTTP status codes correctly map to error types (401, 403, 404, 422, 429, 500)
- [ ] Validation errors include field-level details in `error.details`
- [ ] Server errors do NOT expose internal stack traces to clients
- [ ] Arabic error messages supported alongside English
- [ ] Error contract documented in API reference

---

### US2 — Backend Exception Handling

**As a** Laravel application, **I want** centralized exception handling, **so that** all exceptions are caught, logged, and returned in the standard error format.

**Acceptance Criteria:**

- [ ] Global exception handler in `app/Exceptions/Handler.php`
- [ ] Handler catches and transforms specific exceptions (Validation, Authentication, Authorization, ModelNotFound)
- [ ] Response format follows error contract for all exception types
- [ ] HTTP status codes set correctly per exception type
- [ ] Validation exceptions include field-level error details
- [ ] Authentication exceptions return 401 with `AUTH_INVALID_CREDENTIALS` or `AUTH_TOKEN_EXPIRED`
- [ ] Authorization exceptions return 403 with `AUTH_UNAUTHORIZED`
- [ ] Model not found returns 404 with `RESOURCE_NOT_FOUND`
- [ ] Unhandled exceptions return 500 with generic message (stack trace logged server-side only)
- [ ] All exceptions logged with structured format (timestamp, user ID, endpoint, request ID)

---

### US3 — API Response Helper

**As a** API controller, **I want** consistent response formatting helpers, **so that** I don't repeat boilerplate code.

**Acceptance Criteria:**

- [ ] `BaseController` or trait provides `success()` and `error()` methods
- [ ] `success($data, $message, $statusCode)` returns standardized success response
- [ ] `error($message, $details, $statusCode, $code)` returns standardized error response
- [ ] Helper automatically includes `success` and `data` fields
- [ ] All controllers can inherit from BaseController or use trait
- [ ] Consistent across all API endpoints

---

### US4 — Structured Logging

**As a** platform operator, **I want** structured, queryable logs with correlation IDs, **so that** I can trace multi-step requests and debug issues efficiently.

**Acceptance Criteria:**

- [ ] Logging configured in `config/logging.php` with multiple channels (single, stack, daily)
- [ ] Log entries include: timestamp, level, message, context (user_id, endpoint, request_id, correlation_id)
- [ ] Request/response logging middleware captures: method, URI, status code, response time, payload size
- [ ] Correlation ID middleware generates unique ID for each request
- [ ] Correlation ID propagates through request lifecycle and appears in all logs
- [ ] Sensitive data (passwords, tokens, payment info) NOT logged
- [ ] Log format supports both human-readable (local) and JSON (production) output
- [ ] Logs rotated daily with 30-day retention

---

### US5 — Frontend Error Handling

**As a** frontend user, **I want** clear error messages and appropriate UI feedback, **so that** I understand what went wrong and can take corrective action.

**Acceptance Criteria:**

- [ ] Global error boundary component catches component errors
- [ ] API error interceptor automatically handles 4xx/5xx responses
- [ ] Toast notification system displays user-friendly error messages
- [ ] Validation errors shown at field level (red underline, tooltip)
- [ ] 404 error page displays "Resource Not Found" with back button
- [ ] 403 error page displays "Access Denied" with contact admin link
- [ ] 500 error page displays "Something went wrong" with retry button
- [ ] Error messages in Arabic when user locale is Arabic
- [ ] Error messages include actionable guidance (not technical jargon)

---

### US6 — Error Code Registry & Documentation

**As a** API consumer, **I want** comprehensive error code documentation, **so that** I can implement proper error handling in my client.

**Acceptance Criteria:**

- [ ] Error code registry document lists all codes with: code, HTTP status, description, example scenario
- [ ] At least 12 error codes documented (AUTH_INVALID_CREDENTIALS, AUTH_TOKEN_EXPIRED, AUTH_UNAUTHORIZED, RBAC_ROLE_DENIED, RESOURCE_NOT_FOUND, VALIDATION_ERROR, WORKFLOW_INVALID_TRANSITION, WORKFLOW_PREREQUISITES_UNMET, PAYMENT_FAILED, RATE_LIMIT_EXCEEDED, SERVER_ERROR, and custom domain errors)
- [ ] Examples provided for each error code showing request, response, and handling
- [ ] Document explains when each error occurs
- [ ] Developers can quickly find error code definition and handling strategy

---

## Technical Requirements

### Backend (Laravel)

#### Exception Handling

- [ ] Custom `Handler.php` extends `Handler as ExceptionHandler`
- [ ] `render()` method detects request type and returns JSON for API requests
- [ ] `ValidationException` caught and transformed to 422 with field-level errors
- [ ] `AuthenticationException` caught and transformed to 401
- [ ] `AuthorizationException` caught and transformed to 403
- [ ] `ModelNotFoundException` caught and transformed to 404
- [ ] All other exceptions caught and transformed to 500 (no stack trace to client)
- [ ] Exceptions logged with structured context (user_id, endpoint, correlation_id)

#### Response Helper

- [ ] `BaseController` or trait `ApiResponse` provides response formatting
- [ ] Methods: `success($data, $message, $statusCode)` and `error($message, $details, $statusCode, $code)`
- [ ] Both methods follow error contract format
- [ ] All API controllers inherit or use the helper

#### Error Code Registry

- [ ] Custom `Enums/ErrorCode.php` or `Enums/ApiErrorCode.php` defines all error codes
- [ ] Enum includes: code value, HTTP status, default message
- [ ] Registry used consistently across controllers and services
- [ ] Documentation generated from enum

#### Structured Logging

- [ ] `config/logging.php` configured with: single, daily, stack channels
- [ ] Log entries include context: user_id, endpoint, correlation_id, request_id, response_time
- [ ] Sensitive fields (password, token, card) excluded from logs
- [ ] Custom formatter for JSON output in production
- [ ] Request/response middleware logs: method, URI, status, duration, size

#### Correlation ID Middleware

- [ ] Middleware generates UUID for each request
- [ ] Correlation ID available via `request()->header('X-Correlation-ID')`
- [ ] Middleware adds correlation ID to Log context (available in all logs)
- [ ] Correlation ID returned in error response as `X-Correlation-ID` header

### Frontend (Nuxt.js)

#### Global Error Boundary

- [ ] `app.vue` or `layouts/default.vue` includes error boundary component
- [ ] Component catches component render errors
- [ ] Displays user-friendly error message
- [ ] Provides option to reload or navigate back

#### API Error Interceptor

- [ ] Composable `useApi.ts` or middleware wraps fetch/axios
- [ ] Interceptor detects 4xx/5xx responses
- [ ] Analyzes error response format and extracts message
- [ ] Emits toast notification (via Pinia store or composable)
- [ ] Handles special cases: 401 (redirect to login), 403 (show denied page)

#### Toast Notification System

- [ ] `composables/useToast.ts` provides `showToast()` method
- [ ] Toast component displays error messages
- [ ] Auto-dismisses after 5 seconds
- [ ] Supports: error, warning, success, info levels
- [ ] Positioned top-right (or configurable)

#### Error Page Components

- [ ] `pages/error-404.vue` — Display "Resource Not Found"
- [ ] `pages/error-403.vue` — Display "Access Denied"
- [ ] `pages/error-500.vue` — Display "Server Error"
- [ ] Each page includes: icon, title, description, action button (back, home, contact support)
- [ ] RTL/Arabic support

#### i18n Support

- [ ] All error messages use translation keys (`t()` or `$t()`)
- [ ] Translations in `locales/ar.json` and `locales/en.json`
- [ ] Error messages are user-friendly, not technical
- [ ] Examples: "Email already in use" (not "Duplicate entry")

---

## Error Contract Specification

### Response Format

All API responses (success and error) MUST follow this format:

```json
{
  "success": boolean,
  "data": null | object | array,
  "error": null | {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "details": null | object
  }
}
```

**Field Definitions:**

| Field    | Type                | Description                                    | Notes                           |
| -------- | ------------------- | ---------------------------------------------- | ------------------------------- |
| success  | boolean             | `true` if operation succeeded, `false` if failed | Always present                  |
| data     | null\|object\|array | Operation result (populated on success)         | Null on errors                  |
| error    | null\|object        | Error details (null on success)                | Null on success; object on error |
| code     | string              | Machine-readable error code                    | Within `error` object            |
| message  | string              | Human-readable error message (Arabic/English)  | Within `error` object            |
| details  | object              | Field-level details (validation errors only)   | Optional; within `error` object  |

### Success Response Example

```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@example.com",
    "name": "Ahmed",
    "role": "customer"
  },
  "error": null
}
```

### Error Response Example (Validation)

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

### Error Code Registry

| Code                           | HTTP | Description                                  | Example Scenario                       |
| ------------------------------ | ---- | -------------------------------------------- | -------------------------------------- |
| `AUTH_INVALID_CREDENTIALS`     | 401  | Invalid login credentials                    | Wrong email/password combination       |
| `AUTH_TOKEN_EXPIRED`           | 401  | Authentication token expired                 | Token issued > 24 hours ago            |
| `AUTH_UNAUTHORIZED`            | 403  | Insufficient permissions for action          | User lacks required role               |
| `RBAC_ROLE_DENIED`             | 403  | Specific role not allowed for this action    | Admin endpoint accessed by customer    |
| `RESOURCE_NOT_FOUND`           | 404  | Requested resource does not exist            | GET /api/v1/projects/999 (not found)   |
| `VALIDATION_ERROR`             | 422  | Input validation failed                      | Missing required field, invalid format |
| `WORKFLOW_INVALID_TRANSITION`  | 422  | Invalid state transition in workflow         | Attempting impossible status change    |
| `WORKFLOW_PREREQUISITES_UNMET` | 422  | Prerequisites for workflow step not satisfied| Trying to complete before approval     |
| `PAYMENT_FAILED`               | 422  | Payment processing failed                    | Card declined, insufficient funds      |
| `RATE_LIMIT_EXCEEDED`          | 429  | Too many requests from client                | > 100 requests/minute                  |
| `CONFLICT_ERROR`               | 409  | Resource conflict (e.g., duplicate)          | Email already exists                   |
| `SERVER_ERROR`                 | 500  | Internal server error                        | Unhandled exception, database failure  |

---

## Dependencies

### Upstream

- **STAGE_01_PROJECT_INITIALIZATION** — Provides Laravel/Nuxt scaffolding, base structure
- **Database Schema** — MySQL database with logging tables (optional)

### Downstream

- **All subsequent stages** — Every feature depends on error contract compliance
- **RBAC & Authentication** — Uses error codes for auth/authorization errors
- **Workflow Engine** — Uses error codes for workflow state transition errors
- **Payment Processing** — Uses error codes for payment failures
- **Field Reporting** — Uses error codes for validation/business rule failures

---

## Non-Functional Requirements

- [ ] Error response time < 100ms (includes serialization)
- [ ] Logging does NOT impact request performance (async where applicable)
- [ ] Error messages available in Arabic and English
- [ ] Error codes are stable (never change; deprecated via versioning)
- [ ] Exception handler does NOT crash under stress (10k requests/second)
- [ ] Correlation ID maintains uniqueness (UUID v4 or equivalent)
- [ ] Logs retained for 30 days minimum
- [ ] Database query errors logged without exposing schema details
- [ ] Stack traces visible in local/dev only; hidden in production

---

## Implementation Strategy

### Phase 1 — Error Response Contract & Exception Handler

1. Define error codes enum (`app/Enums/ApiErrorCode.php`)
2. Implement global exception handler (`app/Exceptions/Handler.php`)
3. Create response helper trait/class (`app/Traits/ApiResponse.php`)
4. Test with manual endpoint calls

### Phase 2 — Backend Logging

1. Configure logging channels and formatters
2. Implement correlation ID middleware
3. Implement request/response logging middleware
4. Validate logs in `storage/logs/`

### Phase 3 — Frontend Error Handling

1. Create global error boundary component
2. Implement API error interceptor
3. Create toast notification system
4. Create error page components (404, 403, 500)

### Phase 4 — Documentation & Testing

1. Generate error code documentation
2. Create error handling guide for developers
3. Write tests for all error scenarios
4. Test Arabic/RTL error messages

---

## Open Questions

1. **Structured Logging Storage** — Should logs be sent to external service (e.g., ELK stack) or kept in local files? [NEEDS CLARIFICATION]
2. **Rate Limiting Strategy** — Should rate limiting be global or per-endpoint? What are the limits? [NEEDS CLARIFICATION]
3. **Error Message Localization** — Should all error messages be translated or only user-facing ones?

---

## Success Criteria

- [ ] All API responses follow error contract format
- [ ] Error codes are stable, documented, and used consistently
- [ ] Global exception handler catches and formats all exceptions
- [ ] Structured logging includes correlation IDs for request tracing
- [ ] Frontend displays user-friendly error messages
- [ ] Error messages available in Arabic and English
- [ ] Error contract compliance verified via unit tests
- [ ] All downstream stages pass error handling audit
