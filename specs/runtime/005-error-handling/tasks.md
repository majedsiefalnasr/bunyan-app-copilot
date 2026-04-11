# Tasks — ERROR_HANDLING Stage

> **Stage:** ERROR_HANDLING (05)  
> **Phase:** 01_PLATFORM_FOUNDATION  
> **Specification:** [spec.md](spec.md) | [plan.md](plan.md)  
> **Generated:** 2026-04-11 | **Remediated:** 2026-04-11  
> **Total Tasks:** 89 (77 original + 5 critical security + 3 critical QA tests + 4 final validation)
> **Status:** REMEDIATED — Security & QA gaps addressed

---

## Phase 1: API Contract & Error Code Enum

### Setup & Foundation

- [ ] T001 Create `backend/app/Enums/` directory structure and document enum usage patterns — AC: Directory exists, phpstan.neon configured to recognize enums
- [ ] T002 Create `backend/app/Traits/` directory and establish ApiResponse trait conventions — AC: Directory exists, composer autoload includes trait namespace
- [ ] T003 Create `backend/app/Support/` directory for utility classes (SensitiveFields masking, ErrorFormatter) — AC: Directory exists with explicit `app/Support` namespace in config/app.php

### Error Code Enum

- [ ] T004 [P] [US1] Define `backend/app/Enums/ApiErrorCode.php` enum with 12 error codes (AUTH_INVALID_CREDENTIALS, AUTH_TOKEN_EXPIRED, AUTH_UNAUTHORIZED, RBAC_ROLE_DENIED, RESOURCE_NOT_FOUND, VALIDATION_ERROR, WORKFLOW_INVALID_TRANSITION, WORKFLOW_PREREQUISITES_UNMET, PAYMENT_FAILED, RATE_LIMIT_EXCEEDED, CONFLICT_ERROR, SERVER_ERROR) — AC: All 12 codes present with correct HTTP status mapping (401, 403, 404, 422, 429, 409, 500); enum includes `httpStatus()`, `defaultMessage()` methods; deterministic message registry from AGENTS.md

- [ ] T005 [P] [US1] Add enum:value property to `ApiErrorCode` with semantic naming conventions — AC: Each code has explicit string value (e.g., `case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS'`); passes `phpstan` analysis level 8

- [ ] T006 [P] [US1] Implement `ApiErrorCode::httpStatus(ErrorCode $code): int` method mapping codes to HTTP status — AC: Mapping matches error registry (AUTH_INVALID_CREDENTIALS→401, VALIDATION_ERROR→422, RATE_LIMIT_EXCEEDED→429, etc.); tested via unit test

- [ ] T007 [P] [US1] Implement `ApiErrorCode::defaultMessage(ErrorCode $code, ?string $locale = null): string` method with Arabic/English defaults — AC: Returns user-friendly message in Arabic (ar_SA) or English (en_US); messages match spec registry; tested via unit test

- [ ] T008 [US1] Write unit tests for `ApiErrorCode` enum in `backend/tests/Unit/Enums/ApiErrorCodeTest.php` — AC: All codes map to correct HTTP status; all codes have non-empty default messages; locale fallback works correctly; 100% enum code coverage

### API Response Helper Trait

- [ ] T009 [P] [US3] Define `backend/app/Traits/ApiResponseTrait.php` with contract methods `success()` and `error()` — AC: Matches error contract spec exactly; returns `{ success: true, data: {...}, error: null }` for success; returns `{ success: false, data: null, error: { code, message, details } }` for error; responses are JSON serializable

- [ ] T010 [P] [US3] Implement `ApiResponseTrait::success(mixed $data, string $message = null, int $statusCode = 200): JsonResponse` method — AC: Accepts any data type; returns 200 status by default; fields: `success=true`, `data`, `error=null`; Laravel JsonResponse with correct headers; passes `phpstan` with generic type hints

- [ ] T011 [P] [US3] Implement `ApiResponseTrait::error(string $message, ?array $details = null, int $statusCode = 500, ApiErrorCode $code): JsonResponse` method — AC: Fields: `success=false`, `data=null`, `error` object with `code`, `message`, `details`; HTTP status set correctly; details populated only for validation errors (422) or when explicitly provided

- [ ] T012 [P] [US3] Create `backend/app/Http/Controllers/Api/BaseController.php` using `ApiResponseTrait` — AC: All API controllers inherit from BaseController or can use trait; trait available in base; extends Laravel `Controller` correctly; namespace matches Laravel conventions

- [ ] T013 [US3] Write unit tests for `ApiResponseTrait` in `backend/tests/Unit/Traits/ApiResponseTraitTest.php` — AC: Success response format verified; error response format verified; statusCode parameter respected; details object structure validated; special characters and UTF-8 Arabic text handled correctly; 100% trait coverage

### API Response Contracts Documentation

- [ ] T014 [P] [US1] Create `backend/routes/api.php` test endpoint for success response validation — AC: Endpoint returns example success response matching contract; accessible via GET /api/v1/test/success (or equivalent); responds with 200 and proper format; documented in testing suite

- [ ] T015 [P] [US1] Create `backend/routes/api.php` test endpoints for all 12 error scenarios — AC: Each error code has dedicated test endpoint (e.g., GET /api/v1/test/error/auth-invalid-credentials); returns appropriate HTTP status and error code; documented in testing suite

- [ ] T016 [US1] Write `backend/tests/Feature/ErrorResponseContractTest.php` verifying all success and error response formats — AC: Success responses always have `success=true`, `data`, `error=null`; error responses always have `success=false`, `data=null`, `error` object; all 12 error codes tested; status codes verified; field presence and types checked

- [ ] T017 [P] [US1] Generate `specs/runtime/005-error-handling/contracts/error-response.json` OpenAPI schema — AC: JSON Schema defines success response object; defines error response object with code, message, details sub-fields; validation error detail structure documented; complies with OpenAPI 3.0.0 spec

- [ ] T018 [P] [US1] Generate `specs/runtime/005-error-handling/contracts/error-codes-registry.json` with all codes, HTTP status, messages, and examples — AC: All 12 codes documented; each code has: name, HTTP status, user message (en), user message (ar), technical description, example scenario; example request/response pairs included; used for reference documentation

---

## Phase 2: Global Exception Handler & Response Helpers

### Exception Handler Implementation

- [ ] T019 [P] [US2] Modify `backend/app/Exceptions/Handler.php` to implement JSON response rendering for API requests — AC: `render()` method detects JSON requests via `$request->expectsJson()` or Accept header; returns JSON responses for API requests (not HTML); calls `response()->json()` with proper contract format; passes `phpstan` analysis

- [ ] T020 [P] [US2] Implement exception handling for `ValidationException` in Handler — AC: Catches `ValidationException`; returns 422 HTTP status; `error.code = VALIDATION_ERROR`; `error.details` contains field-level validation messages (from Laravel validator); tested via feature test

- [ ] T021 [P] [US2] Implement exception handling for `AuthenticationException` in Handler — AC: Catches `AuthenticationException`; returns 401 HTTP status; `error.code = AUTH_INVALID_CREDENTIALS` or `AUTH_TOKEN_EXPIRED` (determined by exception context); message localized; tested via feature test

- [ ] T022 [P] [US2] Implement exception handling for `AuthorizationException` in Handler — AC: Catches `AuthorizationException`; returns 403 HTTP status; `error.code = AUTH_UNAUTHORIZED`; does NOT expose role information in message; tested via feature test

- [ ] T023 [P] [US2] Implement exception handling for `ModelNotFoundException` in Handler — AC: Catches `ModelNotFoundException`; returns 404 HTTP status; `error.code = RESOURCE_NOT_FOUND`; message user-friendly (en/ar); tested via feature test

- [ ] T024 [P] [US2] Implement exception handling for throttling/rate limit exceptions in Handler — AC: Catches rate limit exception (Laravel ThrottleRequestsException or custom); returns 429 HTTP status; `error.code = RATE_LIMIT_EXCEEDED`; includes `Retry-After` header with delay in seconds; AC from CHK-API-024

- [ ] T025 [P] [US2] Implement default exception handling for unhandled exceptions in Handler — AC: Catches all other `Throwable` exceptions; returns 500 HTTP status; `error.code = SERVER_ERROR`; message generic (does NOT expose stack trace or error details to client); logs full exception server-side with structured context

- [ ] T026 [US2] Implement structured logging in exception handler with correlation ID and context — AC: Each exception logged with: timestamp, exception type, message, stack trace, user_id (if authenticated), endpoint, correlation_id (from request); logged to `storage/logs/errors.log`; structured JSON format for production

- [ ] T027 [P] [US2] Write feature tests for global exception handler in `backend/tests/Feature/ExceptionHandlerTest.php` — AC: All exception types tested; correct HTTP status for each; correct error codes; validation errors include field details; server errors do NOT expose stack traces; 100% exception handler coverage

### Security Hardening — Post-Remediation (CRITICAL)

- [ ] T078 [P] [US2] Create `backend/app/Http/Middleware/RateLimitByRoleMiddleware.php` with role-based rate limiting — AC: Enforces global 100 req/min limit via user IP; enforces per-user 10 req/min limit on auth/payment endpoints (identified by route tags or controller namespaces); returns 429 RATE_LIMIT_EXCEEDED when exceeded; includes `Retry-After` header with seconds to reset; passes feature test with 1000+ concurrent requests

- [ ] T079 [P] [US2] Enhance T025 exception handler with production/dev APP_DEBUG conditional — AC: Modified `backend/app/Exceptions/Handler.php` to check `app('env') === 'production'`; in production: error response has generic "Server error. Please try again." message, NO stack trace; in local/dev: error response includes full stack trace for debugging; tested via feature test with APP_DEBUG toggle

- [ ] T080 [P] [US2] Create custom `backend/app/Exceptions/RoleNotAllowedException.php` exception distinct from `AuthorizationException` — AC: New exception class extends `AuthorizationException` with role-specific context; handler catches this separately; returns 403 with `error.code = RBAC_ROLE_DENIED` (vs AUTH_UNAUTHORIZED for generic auth); enables role-based error logging and monitoring; documented in exception hierarchy diagram

- [ ] T081 [P] [US4] Enhance T033 correlation ID middleware with UUID v4 validation — AC: Modify `CorrelationIdMiddleware` to validate incoming `X-Correlation-ID` header matches UUID v4 regex pattern; reject malformed correlation IDs with 400 Bad Request; log validation failure with IP and attempted ID; prevents header injection attacks; tested via feature test with invalid correlation IDs (XSS payloads, SQL patterns)

- [ ] T082 [P] [US2] Implement error response payload masking in `backend/app/Exceptions/Handler.php` — AC: Before JSON serialization, call `SensitiveFields::mask($error['details'])` to mask error details; prevents field names (e.g., "password") from appearing in 422 responses; ensures consistent masking across request logging and error responses; tested via feature test verifying no sensitive field names in 422 error details

### Response Helper Validation

- [ ] T028 [US3] Update all existing API controllers to use `ApiResponseTrait` or inherit `BaseController` — AC: Controllers use `success()` and `error()` methods consistently; manual audit of `backend/app/Http/Controllers/` confirms usage; all API endpoints return proper format

- [ ] T029 [US3] Create example controller `backend/app/Http/Controllers/Api/ExampleController.php` demonstrating ApiResponseTrait usage patterns — AC: Shows success response, validation error, auth error, not found error, and server error patterns; used as reference for developers; includes inline comments explaining each pattern

---

## Phase 3: Backend Logging & Correlation IDs

### Logging Configuration

- [ ] T030 [P] [US4] Configure `backend/config/logging.php` with multiple channels (single, daily, stack, errors, audit) — AC: `single` channel for development; `daily` channel rotates by date; `stack` combines multiple channels; `errors` channel for exception logs only; `audit` channel for financial/workflow logs; retention policies set (30 days general, 90 days audit); tested via configuration validation

- [ ] T031 [P] [US4] Set up JSON formatter for production logging in `backend/config/logging.php` — AC: Production environment uses JSON format (queryable); local environment uses human-readable format; both formats include timestamp, level, message, context fields

- [ ] T032 [P] [US4] Configure log file rotation and retention policies in `backend/config/logging.php` — AC: Daily channel rotates each day; files retained for 30 days (general) and 90 days (audit); Laravel `rotateDaily()` and `days()` methods configured; cleanup handled by Laravel schedule

### Correlation ID Middleware

- [ ] T033 [P] [US4] Create `backend/app/Http/Middleware/CorrelationIdMiddleware.php` generating UUID per request — AC: Generates UUID v4 if not in request; preserves existing correlation ID from `X-Correlation-ID` header if present; makes available via `$request->correlationId()` or `request()->correlationId()`; tested via middleware test

- [ ] T034 [P] [US4] Implement correlation ID propagation in `CorrelationIdMiddleware` via Log context binding — AC: Correlation ID added to Log context using `Log::withContext(['correlation_id' => $id])`; available in all subsequent logs; returned in response header `X-Correlation-ID`; tested via integration test

- [ ] T035 [P] [US4] Register `CorrelationIdMiddleware` in `backend/app/Http/Kernel.php` middleware stack — AC: Middleware in global `$middleware` array (not just `$routeMiddleware`); executes for all requests before other middleware; accessible via `Request` bag throughout request lifecycle

### Request/Response Logging Middleware

- [ ] T036 [P] [US4] Create `backend/app/Http/Middleware/RequestResponseLoggingMiddleware.php` logging HTTP details — AC: Logs method, URI, query parameters, status code, response time (milliseconds), payload size; includes user_id and user_role if authenticated; excludes sensitive fields; uses correlation_id from context; tested via middleware test

- [ ] T037 [P] [US4] Implement sensitive data masking in `backend/app/Support/SensitiveFields.php` — AC: Registry of sensitive field names (password, token, api_key, credit_card, ssn, etc.); masking rules: passwords→`***`, tokens→`tok_****...`, card→`****-1234`; masking applied via method `mask(array $data): array`; tested via unit test with regex validation

- [ ] T038 [P] [US4] Integrate sensitive field masking into `RequestResponseLoggingMiddleware` — AC: Request/response payloads masked before logging; passwords never appear in logs; tokens truncated; card numbers masked; applies masking before serialization to JSON; tested via feature test with payload capturing

- [ ] T039 [P] [US4] Register `RequestResponseLoggingMiddleware` in `backend/app/Http/Kernel.php` — AC: Middleware in global `$middleware` array after correlation ID middleware; executes for all requests; logs to `storage/logs/requests.log` or configured channel

### Database Migrations for Audit Logs (Optional)

- [ ] T040 [P] [US4] Create migration `backend/database/migrations/[timestamp]_create_audit_logs_table.php` for audit log storage — AC: Table has columns: id, correlation_id, request_id, user_id, action, resource_type, resource_id, old_values (JSON), new_values (JSON), status, error_code, ip_address, user_agent, duration_ms, created_at; indexes on (user_id, created_at), (correlation_id), (resource_type, resource_id); uses `bigIncrements` for id

- [ ] T041 [P] [US4] Create migration `backend/database/migrations/[timestamp]_create_request_logs_table.php` for request log storage — AC: Table has columns: id, correlation_id, request_id, method, uri, status_code, response_time_ms, payload_size_bytes, user_id, user_role, ip_address, created_at; indexes on (user_id, created_at), (correlation_id), (status_code), (created_at)

- [ ] T042 [P] [US4] Create `backend/app/Models/AuditLog.php` Eloquent model for audit logs — AC: Model uses `timestamps` (created_at only, no updated_at); `fillable` array includes all audit columns; relationships to User model; query scopes for filtering by user, correlation_id, action; `withCasts` for JSON columns

- [ ] T043 [P] [US4] Create Laravel job `backend/app/Jobs/LogAuditEventJob.php` for async audit logging — AC: Job accepts: user_id, action, resource_type, resource_id, old_values, new_values, correlation_id, ip_address, user_agent; writes to database asynchronously; handles failures gracefully (retry logic); uses `dispatched_async`

### Logging Tests

- [ ] T044 [US4] Write unit tests for `CorrelationIdMiddleware` in `backend/tests/Unit/Middleware/CorrelationIdMiddlewareTest.php` — AC: Correlation ID generated for each request; existing correlation ID preserved; UUID v4 format validated; makes available in request; available in Log context; correlation ID returned in response header

- [ ] T045 [US4] Write unit tests for `RequestResponseLoggingMiddleware` in `backend/tests/Unit/Middleware/RequestResponseLoggingMiddlewareTest.php` — AC: HTTP method, URI, status code logged; response time captured; user_id included; sensitive fields masked; correlat... ID present in log; output tested via mock logger

- [ ] T046 [US4] Write integration tests for logging behavior in `backend/tests/Feature/LoggingIntegrationTest.php` — AC: End-to-end logging verified via file inspection; correlation IDs propagate through middleware chain; request/response details captured; sensitive data masked; performance overhead < 50ms (99th percentile, CHK-PERF-001)

- [ ] T047 [US4] Write performance test for logging overhead in `backend/tests/Feature/LoggingPerformanceTest.php` — AC: Logging adds < 50ms per request (99th percentile); 1000-request stress test; measures with/without logging; failure if overhead exceeds threshold; addresses CHK-PERF-001

---

## Phase 4: Frontend Error Handling

### Setup & Components

- [ ] T048 [P] [US5] Create error boundary component `frontend/components/errors/GlobalErrorBoundary.vue` — AC: Catches component render errors via `onErrorCaptured`; displays user-friendly fallback UI; provides "Reload" and "Back" buttons; logs error with correlation ID; RTL-safe using Tailwind logical properties

- [ ] T049 [P] [US5] Wrap root app in error boundary in `frontend/app.vue` — AC: Error boundary component wraps router-view or page content; catches all descendant component errors; does NOT block normal app rendering; tested via snapshot test

- [ ] T050 [P] [US5] Create `frontend/composables/useToast.ts` composable for toast notifications — AC: `showToast(message, type, duration)` method; supports error, warning, success, info types; auto-dismisses after duration (default 5 seconds); accessible via `useToast()`; Pinia store integration

- [ ] T051 [P] [US5] Create `frontend/components/errors/ErrorToast.vue` component displaying toast notifications — AC: Displays notification from Pinia store; auto-dismisses; positioned top-right; supports all types (error, warning, success, info); RTL-safe; Geist design system styling (shadow-as-border)

- [ ] T052 [P] [US5] Create `frontend/stores/errorStore.ts` Pinia store for error state management — AC: State: toasts array, currentError object; actions: `addToast()`, `removeToast()`, `setError()`, `clearError()`; reactive and properly typed with TypeScript; mounted in `nuxt.config.ts`

### API Error Interceptor

- [ ] T053 [P] [US5] Modify `frontend/composables/useApi.ts` to add error interceptor for API responses — AC: Interceptor detects response.success=false or 4xx/5xx status; extracts error code and message from response; handles special cases: 401 (redirect to /login), 403 (navigate to /error-403), 5xx (show retry); preserves correlation ID

- [ ] T054 [P] [US5] Implement error message transformation in `useApi.ts` using `useErrorHandler` — AC: Maps error codes to user-friendly messages via i18n; calls `useToast().showToast()` for error display; does NOT expose internal error details; handles missing/malformed responses gracefully

- [ ] T055 [P] [US5] Create `frontend/composables/useErrorHandler.ts` for centralized error handling logic — AC: `handleError(error, context)` method transforms backend error to UI action; maps error codes to messages, toast types, navigation actions; supports Arabic/English localization; reusable across pages/components

### Error Pages

- [ ] T056 [P] [US5] Create `frontend/pages/error-404.vue` not found error page — AC: Displays "Resource Not Found" message; includes icon or illustration; has "Back to Home" button; RTL-safe; uses i18n for text; matches Geist design system

- [ ] T057 [P] [US5] Create `frontend/pages/error-403.vue` access denied error page — AC: Displays "Access Denied" message; shows contact admin link; includes explanation; RTL-safe; uses i18n for text; matches Geist design system

- [ ] T058 [P] [US5] Create `frontend/pages/error-500.vue` server error page — AC: Displays "Something Went Wrong" message; has "Retry" button and "Back" button; includes correlation ID display (for support reference); RTL-safe; uses i18n for text; matches Geist design system

### i18n Localization

- [ ] T059 [P] [US5] Update `frontend/locales/ar.json` with Arabic error messages for all error codes — AC: Translation keys for: validation errors, auth errors, workflow errors, payment errors, rate limit, server error; messages user-friendly and RTL-compliant; uses i18n dot notation (e.g., `errors.validation_error`); tested via snapshot

- [ ] T060 [P] [US5] Update `frontend/locales/en.json` with English error messages for all error codes — AC: Translation keys for: validation errors, auth errors, workflow errors, payment errors, rate limit, server error; messages match Arabic semantics; tested via snapshot

### Frontend Tests

- [ ] T061 [US5] Write unit tests for error boundary in `frontend/tests/unit/GlobalErrorBoundary.test.ts` — AC: Tests error capture; fallback UI rendering; reload/back button functionality; error logging; 100% component coverage

- [ ] T062 [US5] Write unit tests for toast system in `frontend/tests/unit/useToast.test.ts` — AC: Tests `useToast()` composable; adding/removing toasts; auto-dismiss timer; multi-toast stacking; persistence across time

- [ ] T063 [US5] Write feature tests for API error interceptor in `frontend/tests/unit/useApi.test.ts` — AC: Tests 401/403/4xx/5xx response handling; error message extraction; toast display; special case redirects; correlation ID preservation

- [ ] T064 [US5] Write E2E tests for error pages in `frontend/tests/e2e/errorPages.test.ts` (Playwright) — AC: 404 page renders correctly; 403 page renders correctly; 500 page renders correctly; buttons functional; text localized (en/ar); RTL layout verified

---

## Phase 5: Documentation & Final Testing

### Documentation

- [ ] T065 [P] Create `specs/runtime/005-error-handling/quickstart.md` developer quick reference — AC: Shows: error response format example, common error codes, exception handler usage, API response helper usage, logging patterns, frontend error handling, i18n setup; includes code snippets for each pattern

- [ ] T066 [P] Create `docs/api/error-codes.md` comprehensive error code reference — AC: Lists all 12 error codes; for each code: code value, HTTP status, description, example scenario, example request/response pair, client handling strategy; searchable table of contents; syntax-highlighted examples

- [ ] T067 [P] Create `docs/guides/error-handling-guide.md` comprehensive error handling implementation guide — AC: Backend patterns (exception handler, api response helper, logging); frontend patterns (error boundary, API interceptor, toast, error pages); testing strategies; localization best practices; migration guide for existing endpoints

- [ ] T068 Create `specs/runtime/005-error-handling/research.md` phase research document — AC: Documents technology choices (monitoring strategy, logging library selection, framework features used); rationale for each choice; alternatives considered; links to external documentation

### Cross-Cutting Testing

- [ ] T069 [US1-US6] Write comprehensive integration test `backend/tests/Feature/ErrorHandlingIntegrationTest.php` — AC: Tests end-to-end error flow: client request → backend handling → error response → client reception; covers all 6 user stories; authentication flow tested; workflow errors tested; payment errors tested; rate limiting tested

- [ ] T070 [US1-US6] Write E2E test suite for complete error workflows in `frontend/tests/e2e/errorWorkflows.test.ts` — AC: E2E tests for: validation error display, auth error redirect, not found page, access denied page, server error with retry, rate limit feedback; screenshots captured for each state; tested in both Arabic and English locales

- [ ] T071 Arabic/RTL accessibility verification test — AC: All error messages display correctly in RTL mode; text alignment proper (right-aligned for Arabic); component layouts RTL-safe using Tailwind logical properties; tested with manual RTL toggle; verified in both Firefox and Chrome

- [ ] T072 Performance regression test for logging overhead — AC: Validates logging adds < 50ms (99th percentile, CHK-PERF-001); runs 5000 requests with full logging; measures 50th, 95th, 99th percentile response times; fails if 99th percentile > 50ms baseline

- [ ] T073 Sensitive data masking verification in logs — AC: Captures actual log output; validates passwords replaced with `***`; validates tokens masked as `tok_****...`; validates card numbers masked as `****-1234`; no plaintext sensitive data in any log file; automated scan of log files

### Security & RBAC Testing — Post-Remediation (CRITICAL)

- [ ] T083 [US2] Create RBAC role-based integration test matrix in `backend/tests/Feature/RBACErrorMatrixTest.php` — AC: Tests all 5 roles (Customer, Contractor, Field Engineer, Supervising Architect, Admin) × 5 endpoint types (Customer, Contractor, Admin, Architect, Field Engineer) = 25 scenarios; each role accessing endpoint exclusive to another role returns 403 RBAC_ROLE_DENIED; error code verified; error message does NOT expose role names; all 25 test cases passing

- [ ] T084 [US2] Create attack simulation test suite in `backend/tests/Feature/SecurityAttackSimulationTest.php` — AC: Tests brute-force attack (1000 req/min to /api/login) → 429 RATE_LIMIT_EXCEEDED; tests header injection (malicious correlation ID with XSS payload) → rejected; tests X-Forwarded-For spoofing → rate limiting still effective; all attack scenarios logged and monitored; security audit trail verified

- [ ] T085 [US4] Create PII masking regression test suite in `backend/tests/Feature/PIIMaskingRegressionTest.php` — AC: Automated scan of all log files for sensitive data (passwords, tokens, credit cards, SSN, email); fails if any sensitive pattern found unmasked; detects false positives (e.g., "admin" in error messages); tests field-level error details masking in 422 responses; prevents PII leaks on each deployment

### Validation & Sign-Off

- [ ] T086 [US1-US6] Verify all 6 user stories acceptance criteria met — AC: Requirement traceability document created; each UC mapped to tests; all tests passing; manual verification of visual components; documentation complete

- [ ] T087 [US1-US6] Verify all 96 checklist items from `specs/runtime/005-error-handling/checklists/` — AC: Every checkbox item in requirements.md, security.md, api-contract-compliance.md, performance.md, frontend-backend-integration.md, accessibility-localization.md verified; traceability document created showing test/code mapping

- [ ] T088 [US1-US6] Composer/npm run lint, typecheck, test suite passing — AC: `composer run lint` passes (PHPStan level 8, Pint formatting); `npm run lint` passes (ESLint, Prettier); `npm run typecheck` passes (TypeScript strict mode); `php artisan test` passes (all tests); `npm run test` passes (all frontend tests)

- [ ] T089 Correlation ID end-to-end validation — AC: Correlation ID successfully propagates from request middleware through exception handler through logs through response; evidence: sample logs showing matching correlation IDs per request; sample response headers showing X-Correlation-ID

---

## Dependencies & Parallelization

### Critical Path (Sequential) — Updated with RBAC & Security

1. **T001-T003**: Directory setup (blocking: subsequent tasks need these)
2. **T004-T008**: Error code enum (blocking: exception handler, response helper depend on this)
3. **T009-T013**: API response helper (blocking: controllers, tests depend on this)
4. **T019-T027**: Exception handler (blocking: system-wide, needs enum and trait first)
5. **T048-T060**: Frontend components (blocking: tests and E2E depend on these)
6. **T083-T085** (CRITICAL SECURITY): RBAC matrix, attack simulation, PII masking tests (blocking: release approval depends on these — post-remediation validation gates)

### Parallelizable Work Streams

**Backend Phase 1** (after T013):
- Contracts/documentation (T014-T018) can run in parallel

**Backend Phase 2** (after T027):
- Logging configuration (T030-T032)
- Middleware (T033-T039)
- Database migrations (T040-T043)
- All can run in parallel, tests follow

**Frontend Phase** (independent):
- Can start as soon as backend API contract (T019-T018) is defined
- Error boundary, toast, pages can be built in parallel
- i18n setup can be parallel

**Documentation** (after Phase completion):
- T065-T068 can run in parallel after implementation complete

**Testing** (last for integration tests):
- Unit tests (T008, T013, T027, T044-T047, T061-T063) run as components complete
- Integration tests (T069-T073) after all phases complete
- E2E tests (T070) after frontend complete

### Parallel Execution Example

**Wave 1 (Setup)**: T001, T002, T003, T004-T008 (async enum work)  
**Wave 2A (Backend Contracts)**: T009-T013, T014-T018 (async, some T014-T018 parallel)  
**Wave 2B (Frontend Start)**: T048-T060 (independent, can start once T016 API format understood)  
**Wave 3**: T030-T047, T061-T064 (concurrent: logging middleware + frontend tests)  
**Wave 4**: T065-T068 (documentation, requires implementation done)  
**Wave 5**: T069-T077 (final validation, integration tests)

---

## Implementation Strategy

### Key Decisions

1. **Error Response Format**: Unified contract enforced globally via `ApiResponseTrait` (all endpoints must use)
2. **Error Codes**: Semantic, stable enum (never change; new codes for new scenarios)
3. **Logging**: Local file-based with multi-channel strategy (no external service)
4. **Correlation IDs**: UUID v4 per request, propagated through middleware → logs → response
5. **Rate Limiting**: Hybrid (global 100 req/min; auth/payment 10 req/min)
6. **Frontend**: Composables + store + error boundary pattern for testability
7. **i18n**: User-facing errors translated (ar/en); technical errors English only

### Testing Strategy

- **Unit Tests**: Error enum, response helper, middleware, composables (~30-40 tests)
- **Feature/Integration Tests**: Exception handler, API endpoints, logging behavior (~20-30 tests)
- **E2E Tests**: Complete workflows, error UI, localization (Playwright, ~10-15 tests)
- **Performance Tests**: Logging overhead validation (<50ms requirement), stress testing
- **Security Tests**: Sensitive data masking, RBAC error distinction, stack trace hiding

### Deployment Checklist

- [ ] All 77 tasks completed
- [ ] All checklists verified (96 items × 6 documents)
- [ ] 6 user stories acceptance criteria met
- [ ] Test coverage ≥ 90% for all new code
- [ ] Performance: Logging overhead < 50ms (99th percentile)
- [ ] Security: No sensitive data in logs; no stack traces to clients
- [ ] Arabic/RTL: All UI components tested in RTL mode
- [ ] CI/CD: `composer run lint && composer run test && npm run lint && npm run typecheck && npm run test` all passing
- [ ] Documentation: quickstart.md, error-codes.md, error-handling-guide.md complete

---

## Summary

**Total Tasks**: 80 (including 3 critical security post-remediation tests)  
**Phase 1**: 18 tasks (API Contract & Error Codes)  
**Phase 2**: 25 tasks (Backend Logging & Correlation IDs)  
**Phase 3**: 17 tasks (Frontend Error Handling)  
**Phase 4**: 20 tasks (Documentation & Testing, including RBAC validation, attack simulation, PII masking)

**Effort Estimate**: 12-14 development days for team of 2-3 engineers (includes security validation gates)  
**Parallelization Potential**: 40-50% of work can run concurrent (frontend + backend core in parallel); security tests (T083-T085) run in parallel with documentation but results gate final closure  
**Risk Level**: LOW (well-defined contract, clear acceptance criteria, no architectural unknowns); security tests reduce risk further

---

**TASKS_TOTAL: 80**
