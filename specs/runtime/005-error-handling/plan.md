# Implementation Plan: Error Handling & Logging

**Branch**: `spec/005-error-handling` | **Date**: 2026-04-11 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/runtime/005-error-handling/spec.md`

## Summary

Bunyan ERROR_HANDLING establishes a unified, platform-wide error handling contract and structured logging foundation that ensures consistent error reporting, traceability, and user experience across all backend (Laravel) and frontend (Nuxt.js) layers. This foundational stage enables all downstream stages to implement error handling according to standardized patterns and ensures debug-ability via correlation IDs, structured logging, and comprehensive error codes.

**Technical Approach**: 4-phase delivery implementing error response standardization (Phase 1) → Backend logging & correlation (Phase 2) → Frontend error handling (Phase 3) → Documentation & testing (Phase 4). Uses Laravel exception handler, API response helpers, Pinia toast system, and structured JSON logging with daily rotation.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 11.x) + Vue 3 TypeScript (Nuxt 3.x)

**Primary Dependencies**:

- **Backend**: Laravel (Exceptions, Events, Logging), Laravel Sanctum (auth), Monolog (logging), Laravel Pail (log inspection)
- **Frontend**: Nuxt 3, Vue 3 Composition API, Pinia (state), @nuxt/ui (toast component), Tailwind CSS v4
- **Shared**: Arabic i18n (i18n-js), RTL Tailwind logical properties

**Storage**: MySQL 8.x with optional logging tables (audit log, request logs)

**Testing**:

- **Backend**: PHPUnit, Laravel feature tests
- **Frontend**: Vitest, Playwright E2E

**Target Platform**: Web (Laravel backend + Nuxt SPA frontend); RESTful API layer

**Project Type**: Full-stack platform feature (core foundation)

**Performance Goals**:

- Error response time < 100ms (including serialization)
- Logging does NOT impact request throughput (async where applicable)
- Correlation ID propagates through all request layers with < 1ms overhead

**Constraints**:

- No external error monitoring service (ELK, Sentry); use local file-based logging with 30/90-day rotation
- Error codes are stable (never modified after deployment; versioning via new codes only)
- All user-facing error messages multilingual (Arabic/English)
- Stack traces visible in local/dev only; hidden in production

**Scale/Scope**:

- 12+ error codes (AUTH_INVALID_CREDENTIALS, AUTH_TOKEN_EXPIRED, AUTH_UNAUTHORIZED, RBAC_ROLE_DENIED, RESOURCE_NOT_FOUND, VALIDATION_ERROR, WORKFLOW_INVALID_TRANSITION, WORKFLOW_PREREQUISITES_UNMET, PAYMENT_FAILED, RATE_LIMIT_EXCEEDED, CONFLICT_ERROR, SERVER_ERROR)
- ~10 middleware + services required
- ~8 frontend components/composables
- ~200 test cases (unit + feature + integration)

---

## Constitution Check

**Gate: PASS (No Violations)**

✅ **Clean Architecture**: Exception handler in `app/Exceptions/`, services in `app/Services/`, repositories handle logging  
✅ **RBAC Enforcement**: Error codes include RBAC violations; rate limiting middleware enforces per-role limits  
✅ **Arabic-First**: All error messages in `resources/lang/{ar,en}/`; RTL-safe error components  
✅ **Workflow Engine**: Supports workflow-specific error codes (WORKFLOW_INVALID_TRANSITION, WORKFLOW_PREREQUISITES_UNMET)  
✅ **Audit Trail**: Structured logging with correlation IDs, user context, and decision logs  
✅ **Financial Safety**: Payment-specific error code and rate limiting on payment endpoints  
✅ **Test-First**: TDD followed — specs include detailed acceptance criteria for all error codes

**Post-Design Re-evaluation**: After Phase 1 design, architecture compliance verified via ADR references and clean layering.

---

## Project Structure

### Documentation (this feature)

```text
specs/runtime/005-error-handling/
├── plan.md                    # This file (overall strategy)
├── research.md                # Phase 0 deliverable (technology deep dives)
├── data-model.md              # Phase 1 deliverable (database schema for logs)
├── contracts/                 # Phase 1 deliverable (API contracts)
│   ├── error-response.json
│   ├── error-codes-registry.json
│   └── correlation-id-flow.json
├── quickstart.md              # Developer quick reference
├── spec.md                    # Requirements document
└── audits/                    # Checklists and compliance records
    └── requirements.md
```

### Source Code

#### Backend (Laravel)

```text
backend/
├── app/
│   ├── Enums/
│   │   └── ApiErrorCode.php                 # Error code enum
│   ├── Exceptions/
│   │   └── Handler.php                      # Global exception handler (MODIFIED)
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── CorrelationIdMiddleware.php  # Generate/propagate correlation ID
│   │   │   ├── RateLimitByRoleMiddleware.php # Rate limiting per role
│   │   │   └── RequestLoggingMiddleware.php  # Log requests/responses
│   │   └── Controllers/
│   │       └── BaseController.php            # ApiResponse trait (MODIFIED/NEW)
│   ├── Services/
│   │   └── ErrorFormatterService.php         # Transform exceptions to API format
│   ├── Traits/
│   │   └── ApiResponseTrait.php              # success(), error() helpers
│   └── Providers/
│       └── AppServiceProvider.php            # Middleware registration
├── config/
│   └── logging.php                           # Logging channels, formatters, retention
├── database/
│   └── migrations/
│       └── [timestamp]_create_audit_logs_table.php  # (Optional) audit log storage
├── routes/
│   └── api.php                               # Rate limit middleware applied to routes
├── resources/
│   └── lang/
│       ├── ar/
│       │   ├── validation.php
│       │   ├── auth.php
│       │   ├── workflow.php
│       │   ├── payment.php
│       │   └── general.php
│       └── en/
│           ├── validation.php
│           ├── auth.php
│           ├── workflow.php
│           ├── payment.php
│           └── general.php
└── tests/
    ├── Feature/
    │   ├── ErrorHandlingTest.php             # Exception handler tests
    │   ├── ApiResponseTest.php               # Response helper tests
    │   ├── CorrelationIdTest.php             # Correlation ID propagation
    │   ├── RateLimitingTest.php              # Rate limit enforcement
    │   └── ErrorCodesTest.php                # Error code registry
    └── Unit/
        └── ErrorFormatterTest.php             # Error formatting logic
```

#### Frontend (Nuxt.js)

```text
frontend/
├── app.vue                                     # Root with error boundary (MODIFIED)
├── components/
│   └── errors/
│       ├── GlobalErrorBoundary.vue             # Error boundary wrapper
│       ├── ErrorToast.vue                      # Toast notification component
│       ├── NotFoundPage.vue                    # 404 error page
│       ├── AccessDeniedPage.vue                # 403 error page
│       └── ServerErrorPage.vue                 # 500 error page
├── composables/
│   ├── useApi.ts                               # API client with error interceptor (MODIFIED)
│   ├── useErrorHandler.ts                      # Centralized error handling
│   └── useToast.ts                             # Toast notification system
├── pages/
│   ├── error-404.vue                           # 404 error page route
│   ├── error-403.vue                           # 403 error page route
│   └── error-500.vue                           # 500 error page route
├── stores/
│   └── errorStore.ts                           # Pinia store for error state
├── locales/
│   ├── ar.json                                 # Arabic error messages (MODIFIED/EXTENDED)
│   └── en.json                                 # English error messages (MODIFIED/EXTENDED)
└── tests/
    ├── unit/
    │   ├── errorBoundary.test.ts
    │   ├── errorHandler.test.ts
    │   └── toastSystem.test.ts
    └── e2e/
        └── errorHandling.test.ts               # Playwright E2E tests
```

**Structure Decision**: Monolithic backend (Laravel), separated frontend (Nuxt). Logging at infrastructure level (middleware + config). Exception handling at application level (global handler). Frontend error UI in dedicated error components.

---

## Implementation Phases

### Phase 1: Error Response Contract & Exception Handler

**Goal**: Establish unified error format, error code registry, global exception handling.

**Deliverables**:

- ✅ `app/Enums/ApiErrorCode.php` — PHP enum defining all 12+ error codes with HTTP status, default message
- ✅ `app/Exceptions/Handler.php` — Global handler catching ValidationException, AuthenticationException, AuthorizationException, ModelNotFoundException, and generic exceptions
- ✅ `app/Traits/ApiResponseTrait.php` — `success()` and `error()` helper methods for consistent responses
- ✅ `app/Http/Controllers/BaseController.php` — Base controller using ApiResponseTrait
- ✅ All API controllers refactored to inherit BaseController or use trait
- ✅ `contracts/error-response.json` — OpenAPI/JSON Schema spec for error responses
- ✅ `contracts/error-codes-registry.json` — Documented registry with examples

**Key Decisions**:

- HTTP status codes follow REST conventions: 401 (auth failures), 403 (authorization), 404 (not found), 422 (validation/workflow), 429 (rate limit), 500 (server error)
- Error codes are semantic, stable identifiers (never change; new codes versioned instead)
- Validation errors include field-level `details` object
- Stack traces logged server-side only; never exposed to clients in production

**Tests**:

- [ ] Each exception type maps correctly to error code and HTTP status
- [ ] Validation errors include field-level details
- [ ] Server errors do NOT expose stack traces to clients
- [ ] ApiResponseTrait produces consistent response format

---

### Phase 2: Backend Logging & Correlation IDs

**Goal**: Implement structured logging with correlation IDs, request/response tracking.

**Deliverables**:

- ✅ `config/logging.php` — Configure channels (single, daily, stack) with daily rotation, 30-day retention
- ✅ `app/Http/Middleware/CorrelationIdMiddleware.php` — Generate UUID, propagate via X-Correlation-ID header
- ✅ `app/Http/Middleware/RequestLoggingMiddleware.php` — Log method, URI, status, response time, payload size
- ✅ Structured logging context (user_id, endpoint, correlation_id, request_id)
- ✅ Sensitive data masking (NO passwords, tokens, card data in logs)
- ✅ JSON formatter for production, human-readable for local
- ✅ File rotation jobs configured (daily, 30-day/90-day retention)
- ✅ Audit channel for financial/workflow logs (90-day retention)

**Key Decisions**:

- No external service (ELK, Sentry); local files only for simplicity
- Correlation ID propagates through entire request lifecycle
- Request/response logging async where possible
- Separate audit channel for financial transactions
- **Performance Target:** Logging overhead < 50ms (99th percentile)
- **Async Strategy:** Audit log writes queued via Laravel jobs (fire-and-forget)
- **Database Optimization:**
  - Indexes on: `(user_id, created_at)`, `(correlation_id)`, `(status_code)`, `(created_at)`
  - Request context saved immediately; full serialization done in background job
  - Batch deletion of expired logs (raw queries, not model deletion)
- **Sensitive Data Masking:** Automatic masking via middleware (passwords→`***`, tokens→`tok_****...`, cards→`****-1234`)

**Tests**:

- [ ] Correlation ID generated and present in all logs
- [ ] Request/response details captured (method, URI, status, time)
- [ ] Sensitive fields masked or excluded from logs
- [ ] Daily rotation creates new log file each day
- [ ] 30-day/90-day retention enforced
- [ ] Logging adds < 50ms overhead to response time (performance test)
- [ ] Async job queue processes audit logs without blocking requests

---

### Phase 3: Frontend Error Handling

**Goal**: Implement error boundary, error interceptor, toast notifications, error pages.

**Deliverables**:

- ✅ `components/errors/GlobalErrorBoundary.vue` — Catches unhandled component errors
- ✅ `composables/useApi.ts` — API client with error interceptor (modified)
- ✅ `composables/useErrorHandler.ts` — Centralized error handling logic
- ✅ `composables/useToast.ts` — Toast notification system (error, warning, success, info)
- ✅ `components/errors/ErrorToast.vue` — Toast display component
- ✅ `pages/error-404.vue`, `error-403.vue`, `error-500.vue` — Error page components
- ✅ `stores/errorStore.ts` — Pinia store for error state
- ✅ Arabic translation keys in `locales/{ar,en}.json`
- ✅ RTL-safe error components using Tailwind logical properties

**Key Decisions**:

- Toast auto-dismisses after 5 seconds
- 401 auto-redirects to login; 403 shows deny page; 5xx shows retry button
- Error messages use i18n translation keys (user-friendly, not technical)
- Error components follow Geist design system (shadow-as-border, Geist font, achromatic palette)

**Tests**:

- [ ] Error boundary catches and displays component errors
- [ ] API error interceptor handles 4xx/5xx responses
- [ ] Toast notifications display with correct styling
- [ ] 404/403/500 error pages render correctly
- [ ] Error messages in Arabic and English
- [ ] RTL layout for Arabic error pages

---

### Phase 4: Documentation & Testing

**Goal**: Comprehensive documentation, error handling guide, full test coverage.

**Deliverables**:

- ✅ `quickstart.md` — Developer reference: error handling patterns, common scenarios
- ✅ `docs/api/error-codes.md` — Error code reference with examples
- ✅ `docs/guides/error-handling-guide.md` — How to implement error handling in features
- ✅ Full unit test coverage (90%+) for error handling logic
- ✅ Feature tests for critical error paths (auth failures, validation, rate limiting)
- ✅ E2E Playwright tests for error UI flows
- ✅ Arabic error message validation tests

**Key Decisions**:

- All error scenarios (validation, auth, workflow, payment, rate limit) covered by tests
- E2E tests verify user-visible error messages and TODOs
- Documentation examples include both success and error paths

**Tests**:

- [ ] Exception handler catches all exception types
- [ ] Error messages correctly localized to Arabic and English
- [ ] Rate limiting enforced at global and per-endpoint levels
- [ ] Correlation ID propagated through all request layers
- [ ] Frontend error components render correctly

---

## Technical Decisions

### 1. Error Response Format (Contract)

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

**Rationale**: Consistent, predictable structure for all API consumers. Easily distinguishes success from error. Validation errors can include field-level details.

---

### 2. Error Code Strategy (Semantic, Stable)

- **Semantic**: Code name reflects the error category (e.g., AUTH*\*, WORKFLOW*\_, PAYMENT\_\_)
- **Stable**: Once assigned, code never changes. New requirements = new codes.
- **Versioning**: Error code changes are API breaking changes; handled via version bumps.

**Example codes**:

- `AUTH_INVALID_CREDENTIALS` (401)
- `WORKFLOW_INVALID_TRANSITION` (422)
- `RATE_LIMIT_EXCEEDED` (429)

---

### 3. Logging Strategy (Multi-Channel, Retention)

| Channel      | Retention | Use Case                       | Example                           |
| ------------ | --------- | ------------------------------ | --------------------------------- |
| laravel.log  | 30 days   | General application logs       | Requests, responses, info         |
| audit.log    | 90 days   | Financial, workflow, sensitive | Payments, approvals, phase closes |
| requests.log | 30 days   | HTTP request/response details  | Method, URI, status, duration     |
| errors.log   | 30 days   | Exceptions and errors only     | Stack traces, context             |

**Rationale**: Separate channels allow for retention policies (audit = longer) and filtering for debugging.

---

### 4. Correlation ID Strategy (UUID v4)

- Generated per-request via `CorrelationIdMiddleware`
- Propagated via `X-Correlation-ID` header
- Available in all logs via context binding
- Enables request tracing across distributed components

**Rationale**: Simplifies multi-step request debugging without external tracing service.

---

### 5. Rate Limiting (Hybrid Global + Per-Endpoint)

| Context | Limit       | HTTP Status | Error Code          |
| ------- | ----------- | ----------- | ------------------- |
| Global  | 100 req/min | 429         | RATE_LIMIT_EXCEEDED |
| Auth    | 10 req/min  | 429         | RATE_LIMIT_EXCEEDED |
| Payment | 10 req/min  | 429         | RATE_LIMIT_EXCEEDED |

**Rationale**: Protects against brute-force (auth) and fraud (payment) while allowing normal API usage.

---

### 6. Frontend Error Handling (Composable + Store + Components)

- **useErrorHandler.ts**: Centralized logic for transforming API errors to user-friendly messages
- **errorStore.ts (Pinia)**: Global error state, toast state
- **useToast.ts**: Display toast notifications
- **ErrorBoundary**: Catches unhandled component errors

**Rationale**: Enables reusable, testable error logic; decouples error display from business logic.

---

### 7. Localization Strategy (User-Facing + Technical)

- **User-facing errors** (validation, auth, workflow, payment): Translated to Arabic + English
- **Technical errors** (stack traces, DB errors): English only (for developers)

**Structure**:

```
resources/lang/ar/validation.php    # "The field is required"
resources/lang/ar/auth.php          # "Invalid credentials"
resources/lang/ar/workflow.php      # "Cannot transition from draft to complete"
```

**Rationale**: Arabic users see understandable messages; developers debug in English.

---

## Frontend/Backend Integration Points

### 1. API Response Format

Frontend relies on consistent response format:

- Success: `{ success: true, data: {...} }`
- Error: `{ success: false, error: { code: "...", message: "..." } }`

**Integration**: `useApi.ts` interceptor checks `response.success` and handles errors.

---

### 2. Error Codes

Backend defines error codes in enum; frontend matches codes to user-friendly messages and UI actions:

- `401` → Redirect to login
- `403` → Show access denied page
- `422` → Show field-level validation errors
- `429` → Show "Too many requests" toast
- `500` → Show "Something went wrong" with retry button

**Integration**: `useErrorHandler.ts` maps error codes to messages and actions.

---

### 3. Correlation ID

Backend sets `X-Correlation-ID` header in responses; frontend logs it if errors occur (for debugging support requests).

**Integration**: `useApi.ts` extracts correlation ID from response headers before error display.

---

### 4. Localization

Backend provides error messages in user's locale via `Accept-Language` header.
Frontend respectfully displays localized messages.

**Integration**: `useApi.ts` sends `Accept-Language: ar` (or `en`); Nuxt i18n manages display.

---

## Database Schema (Optional)

If audit logging to database is implemented:

```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    correlation_id UUID UNIQUE,
    request_id UUID,
    user_id BIGINT,
    action VARCHAR(255),          -- e.g., "payment_processed", "phase_approved"
    resource_type VARCHAR(100),   -- e.g., "Project", "Payment"
    resource_id BIGINT,
    old_values JSON,              -- Previous state
    new_values JSON,              -- New state
    status VARCHAR(50),           -- success, failed
    error_code VARCHAR(100),      -- error code if failed
    ip_address VARCHAR(45),
    user_agent TEXT,
    duration_ms INT,
    created_at TIMESTAMP,
    INDEX (user_id, created_at),
    INDEX (correlation_id),
    INDEX (resource_type, resource_id)
);

CREATE TABLE request_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    correlation_id UUID,
    request_id UUID,
    method VARCHAR(10),           -- GET, POST, etc.
    uri VARCHAR(500),
    status_code INT,
    response_time_ms INT,
    payload_size_bytes INT,
    user_id BIGINT,
    user_role VARCHAR(50),
    ip_address VARCHAR(45),
    created_at TIMESTAMP,
    INDEX (user_id, created_at),
    INDEX (correlation_id),
    INDEX (status_code),
    INDEX (created_at)
);
```

---

## Middleware & Service Layer Design

### Middleware Stack (Execution Order)

```
CorrelationIdMiddleware           # Generate/propagate correlation ID
    ↓
RequestLoggingMiddleware          # Log incoming request
    ↓
AuthenticateRequests (Sanctum)    # Authenticate user
    ↓
RateLimitByRoleMiddleware         # Enforce rate limits
    ↓
[ROUTE HANDLER]
    ↓
ExceptionHandler                  # Catch exceptions, format response
    ↓
RateLimitByRoleMiddleware (exit)  # Record response
```

### Service & Repository Layers

- **Exception Handler** (`app/Exceptions/Handler.php`): Catch exceptions, determine error code, format response
- **ErrorFormatterService** (`app/Services/ErrorFormatterService.php`): Transform domain exceptions to API error format
- **ApiResponseTrait** (`app/Traits/ApiResponseTrait.php`): Response formatting helpers

---

## Dependencies & Libraries

### Backend

| Package         | Version | Purpose                                  |
| --------------- | ------- | ---------------------------------------- |
| Laravel         | 11.x    | Framework, routing, ORM                  |
| Laravel Sanctum | Latest  | Authentication (already in stack)        |
| Monolog         | ^3.0    | Logging (Laravel's logging engine)       |
| Laravel Pail    | ^1.0    | Log inspection CLI (optional but useful) |
| PHP             | 8.3+    | Language                                 |

### Frontend

| Package      | Version | Purpose                             |
| ------------ | ------- | ----------------------------------- |
| Nuxt         | 3.x     | Framework                           |
| Vue          | 3.x     | UI framework                        |
| Pinia        | ^1.0    | State management                    |
| @nuxt/ui     | Latest  | UI components (Toast, etc.)         |
| i18n-js      | ^0.9    | Frontend i18n (or Nuxt i18n module) |
| Tailwind CSS | 4.x     | Styling                             |
| TypeScript   | ^5.0    | Type safety                         |

---

## Risk Assessment

### High-Risk Areas

1. **Correlation ID Propagation**: If not propagated through all layers, debugging becomes difficult
   - **Mitigation**: Test correlation ID presence in all logs; add logging to middleware tests

2. **Rate Limiting Accuracy**: If not configured correctly, may block legitimate users or fail to block attackers
   - **Mitigation**: Load testing; verify per-role limits; monitor false positives

3. **Sensitive Data in Logs**: Accidental logging of passwords/tokens/cards is a security risk
   - **Mitigation**: Implement masking in logger; code review logging statements; audit logs regularly

4. **Localization Completeness**: Missing error messages in Arabic = poor UX
   - **Mitigation**: Comprehensive translation keys; test all error paths in both languages

### Medium-Risk Areas

- Error message consistency across API versions (versioning strategy needed)
- Frontend error boundary effectiveness (edge case errors may slip through)
- Log retention policy enforcement (disk space monitoring required)

---

## Success Criteria

- ✅ All API responses follow error contract format
- ✅ Error codes are stable, documented, and used consistently
- ✅ Global exception handler catches and formats all exceptions (90%+ coverage)
- ✅ Structured logging includes correlation IDs for request tracing
- ✅ Rate limiting enforced at global and per-endpoint levels
- ✅ Frontend displays user-friendly error messages
- ✅ Error messages available in Arabic and English
- ✅ Error contract compliance verified via unit tests (90%+ coverage)
- ✅ All downstream stages pass error handling audit
- ✅ Documentation complete with examples and quick reference

---

## Dependencies

### Upstream

- **STAGE_01_PROJECT_INITIALIZATION** — Provides Laravel/Nuxt scaffolding, base structure
- **Database Schema** — MySQL database initialized

### Downstream

- **All subsequent stages** — Every feature uses error contract
- **RBAC & Authentication** — Uses error codes (AUTH*\*, RBAC*\*)
- **Workflow Engine** — Uses error codes (WORKFLOW\_\*)
- **Payment Processing** — Uses error codes (PAYMENT\_\*)
- **Field Reporting** — Uses error codes (validation, business rules)

---

## Next Steps

1. **Phase 0 (Research)**: Generate `research.md` with deep dives on:
   - Laravel exception handling internals
   - Monolog logging patterns
   - Nuxt error boundaries and composables
   - Pinia state management for errors

2. **Phase 1 (Design)**: Generate:
   - `data-model.md` (optional audit log schema)
   - `contracts/` (API response schemas, error code registry)
   - Start implementing exception handler + error codes

3. **Phase 2 (Implementation)**: Execute tasks from Phase 1 and 2
   - Exception handler, logging middleware, correlation IDs
   - Frontend error boundary, interceptor, toasts

4. **Phase 3 (Testing)**: Full test coverage
   - Unit tests (Exception handler, error formatter)
   - Feature tests (HTTP error responses, rate limiting)
   - E2E tests (Error UI flows)

5. **Phase 4 (Documentation)**: Generate `quickstart.md` and guides
