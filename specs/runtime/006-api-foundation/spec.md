# STAGE_06 — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Stage File:** `specs/phases/01_PLATFORM_FOUNDATION/STAGE_06_API_FOUNDATION.md`
> **Branch:** `spec/006-api-foundation`
> **Created:** 2026-04-14T00:00:00Z
> **Status:** Draft

---

## Overview

Establish the complete API foundation for the Bunyan platform, including versioned route organization, a shared base controller with standardized response helpers, a base API resource class, rate limiting strategy, CORS configuration, middleware stack ordering, API documentation scaffolding, and a health check endpoint.

This stage provides the structural contract that all downstream API stages depend on. It extends and formalizes the patterns introduced in STAGE_03 (Authentication), STAGE_04 (RBAC), and STAGE_05 (Error Handling) into a systematic, versioned API surface.

**Existing Foundation (already in place from prior stages):**

- `App\Http\Controllers\Api\BaseController` — uses `ApiResponseTrait` for `success()` / `error()` responses
- `App\Traits\ApiResponseTrait` — unified response helpers with correlation ID support
- `App\Http\Middleware\CorrelationIdMiddleware` — UUID v4 correlation ID propagation
- `App\Http\Middleware\RequestResponseLoggingMiddleware` — structured request/response logging
- `App\Http\Middleware\RoleMiddleware` + `PermissionMiddleware` — RBAC enforcement from STAGE_04
- Named rate limiters: `api`, `auth-login`, `auth-register`, `auth-forgot-password`, `auth-email-resend`, `user-avatar-upload`
- Route structure: `Route::prefix('v1')` already in `backend/routes/api.php`

**What this stage adds:**

- Formal versioned namespace `App\Http\Controllers\Api\V1\` with `BaseApiController`
- `App\Http\Resources\BaseApiResource` with pagination meta support
- Additional named rate limiters for authenticated API routes, admin routes, and public routes
- CORS configuration formalized in `config/cors.php` with env-driven origins
- Health check endpoint at `GET /api/health` (outside v1 versioning, no auth)
- OpenAPI 3.0 documentation via `darkaonline/l5-swagger`
- Route file breakdown into sub-files for maintainability
- `role` and `permission` middleware aliases registered in Kernel

---

## User Stories

### US1 — Versioned API with Predictable Response Shape (Priority: P1)

**As a** frontend developer, **I want** all API routes to follow a predictable `/api/v1/` prefix with consistent JSON response shapes, **so that** I can build the frontend against a stable contract that will not change unexpectedly.

**Why this priority**: Every downstream stage and every frontend page depends on this. Without it, no API endpoint can be built reliably.

**Independent Test**: Call `GET /api/v1/auth/user` (authenticated) and `GET /api/health` — both must return the correct shape. Can be demonstrated with a single HTTP client without any UI.

**Acceptance Scenarios**:

1. **Given** the API is running, **When** I call any `/api/v1/*` endpoint, **Then** the response body always contains `success`, `data`, and `error` keys at the top level.
2. **Given** an unauthenticated request to a protected route, **When** the response is returned, **Then** HTTP status is 401 and `error.code` is `AUTH_TOKEN_EXPIRED` or `AUTH_UNAUTHORIZED`.
3. **Given** a successful request, **When** the response is returned, **Then** `success` is `true`, `data` is non-null, and `error` is `null`.
4. **Given** any API response, **When** inspecting headers, **Then** `X-Correlation-ID` is always present as a UUID v4.

---

### US2 — Base Controller and Resource Inheritance (Priority: P1)

**As a** backend developer, **I want** a `BaseApiController` in `Api\V1\` namespace and a `BaseApiResource` class, **so that** all feature controllers and resources have a consistent, validated parent to extend, reducing boilerplate and drift.

**Why this priority**: Required before any feature controller can be scaffolded. All downstream stages will extend these classes.

**Independent Test**: Create a minimal test controller extending `BaseApiController` and call `$this->success([])` — assert the response structure. Create a test resource extending `BaseApiResource` — assert `data` wrapping and `meta` presence.

**Acceptance Scenarios**:

1. **Given** a controller extends `BaseApiController`, **When** `$this->success($data)` is called, **Then** a JSON response matching the error contract is returned with HTTP 200.
2. **Given** a controller extends `BaseApiController`, **When** `$this->error(ApiErrorCode::RESOURCE_NOT_FOUND)` is called, **Then** HTTP 404 is returned with `error.code` = `RESOURCE_NOT_FOUND`.
3. **Given** a controller extends `BaseApiController`, **When** `$this->paginated($collection, $meta)` is called, **Then** response contains `data` array and `meta` pagination object.
4. **Given** a resource extends `BaseApiResource`, **When** `toArray()` is called, **Then** `data` key wraps the payload.
5. **Given** a collection of resources, **When** `BaseApiResource::collection()` with pagination is used, **Then** response includes `meta.current_page`, `meta.total`, `meta.per_page`, `meta.last_page`.

---

### US3 — Rate Limiting by Context (Priority: P2)

**As a** platform operator, **I want** request rate limits applied per-user for authenticated routes and per-IP for public routes, **so that** the API is protected against abuse without blocking legitimate users.

**Why this priority**: Security-critical but can follow after basic routing is established.

**Independent Test**: Send 61 requests to a general authenticated route within 1 minute as the same user — assert the 61st returns HTTP 429 with `RATE_LIMIT_EXCEEDED`. Send 11 requests to a public route from the same IP — assert the 11th returns HTTP 429.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they exceed 60 requests per minute to general `/api/v1/*` routes, **Then** HTTP 429 is returned with `error.code` = `RATE_LIMIT_EXCEEDED`.
2. **Given** an unauthenticated request, **When** the same IP exceeds 10 requests per minute on public routes, **Then** HTTP 429 is returned with `error.code` = `RATE_LIMIT_EXCEEDED`.
3. **Given** an admin user, **When** they make requests to `/api/v1/admin/*` routes, **Then** a higher rate limit (300 req/min) applies and admin is not blocked at normal thresholds.
4. **Given** a rate-limited response, **When** inspecting headers, **Then** `Retry-After` and `X-RateLimit-Limit`, `X-RateLimit-Remaining` headers are present.
5. **Given** `GET /api/health`, **When** called any number of times, **Then** it is never rate-limited.

---

### US4 — CORS Configuration for Frontend Integration (Priority: P2)

**As a** frontend developer, **I want** CORS configured to allow requests from the Nuxt frontend origin with credential support, **so that** authenticated requests from the browser work correctly without CORS errors.

**Why this priority**: Must be working before any browser-based API call (every frontend page). Second only to routing structure.

**Independent Test**: Send a preflight `OPTIONS` request from `http://localhost:3000` to any `/api/v1/*` route — assert `Access-Control-Allow-Origin` matches and `Access-Control-Allow-Credentials` is `true`.

**Acceptance Scenarios**:

1. **Given** a browser request from the configured frontend origin, **When** a `GET /api/v1/auth/user` is made, **Then** `Access-Control-Allow-Origin` header matches the request origin.
2. **Given** a preflight `OPTIONS` request, **When** `X-Correlation-ID` is in `Access-Control-Request-Headers`, **Then** the preflight response allows it.
3. **Given** `CORS_ALLOWED_ORIGINS` env is set to a comma-separated list, **When** a request arrives from one of those origins, **Then** CORS headers are applied correctly.
4. **Given** a request from a non-allowed origin, **When** evaluated, **Then** CORS headers are absent (browser will block).
5. **Given** any API response, **When** `X-Correlation-ID` is in the response headers, **Then** `Access-Control-Expose-Headers` includes `X-Correlation-ID` so the browser can read it.

---

### US5 — Health Check Endpoint (Priority: P2)

**As a** DevOps/infrastructure team member, **I want** a `GET /api/health` endpoint that reports DB, cache, and application status, **so that** load balancers and monitoring tools can determine if the application is healthy.

**Why this priority**: Required for production deployment and Docker health probes. Does not block other API features.

**Independent Test**: Call `GET /api/health` without any auth token — assert HTTP 200, `data.status = "healthy"`, and `data.checks` object present. Take DB offline — assert HTTP 503 and `data.status = "unhealthy"`.

**Acceptance Scenarios**:

1. **Given** a running application with DB and cache connected, **When** `GET /api/health` is called, **Then** HTTP 200 is returned with `data.status = "healthy"`.
2. **Given** no authentication token, **When** `GET /api/health` is called, **Then** the response is still returned (no 401).
3. **Given** the database is unreachable, **When** `GET /api/health` is called, **Then** HTTP 503 is returned with `data.status = "unhealthy"` and `data.checks.database = false`.
4. **Given** the cache is unreachable, **When** `GET /api/health` is called, **Then** `data.checks.cache = false` and overall status degrades to `"degraded"` (non-fatal) or `"unhealthy"` (fatal).
5. **Given** a health check response, **When** inspecting `data`, **Then** it includes: `status`, `version`, `environment`, `checks.database`, `checks.cache`.

---

### US6 — OpenAPI Documentation Accessible at /api/documentation (Priority: P3)

**As a** developer integrating with the Bunyan API, **I want** interactive OpenAPI 3.0 documentation at `/api/documentation`, **so that** I can explore and test all API endpoints without reading source code.

**Why this priority**: Developer experience improvement; does not block any core feature.

**Independent Test**: Navigate to `GET /api/documentation` — assert HTTP 200 and Swagger UI is rendered. Navigate to `GET /api/documentation.json` — assert valid OpenAPI JSON.

**Acceptance Scenarios**:

1. **Given** the application is running, **When** I navigate to `GET /api/documentation`, **Then** Swagger UI is rendered.
2. **Given** Swagger UI, **When** I inspect base info, **Then** title, version, and description match Bunyan platform identity.
3. **Given** Swagger UI, **When** security schemes are visible, **Then** Sanctum Bearer token auth scheme is defined.
4. **Given** the `/api/documentation.json` route, **When** accessed, **Then** valid OpenAPI 3.0 JSON is returned.

---

### Edge Cases

- What happens when `X-Correlation-ID` header contains a non-UUID value? → Rejected silently, new UUID generated (already implemented in `CorrelationIdMiddleware`).
- What happens when the rate limit key includes null user ID (unauthenticated)? → Fall back to IP as key.
- What happens when `CORS_ALLOWED_ORIGINS=*` in production? → Must warn/fail fast via config validation or documentation; wildcard is only permitted for local dev.
- What happens when `GET /api/health` is called while a migration is in progress? → Must return 503 if DB query fails.
- What happens when the Swagger UI route is hit in production with `APP_ENV=production`? → Documentation must remain accessible (not restricted to local only), but may require a separate env flag.

---

## Functional Requirements

### API Versioning & Route Organization

- **FR-001**: All application API routes MUST be prefixed with `/api/v1/` via `Route::prefix('v1')` in `routes/api.php`.
- **FR-002**: Route file `routes/api.php` MUST delegate to sub-files: `routes/api/v1/auth.php`, `routes/api/v1/admin.php`, `routes/api/v1/users.php` (plural, version-explicit subdirectory) for maintainability.
- **FR-003**: Named routes MUST follow the convention `api.v1.[resource].[action]` (e.g., `api.v1.auth.login`, `api.v1.admin.roles.index`).
- **FR-004**: Public routes (no auth) MUST be explicitly separated from authenticated route groups.
- **FR-005**: Admin routes MUST use the middleware stack `['auth:sanctum', 'role:admin']`.

### Base Controller

- **FR-006**: A `BaseApiController` class MUST exist at `app/Http/Controllers/Api/V1/BaseApiController.php` in namespace `App\Http\Controllers\Api\V1`.
- **FR-007**: `BaseApiController` MUST extend `App\Http\Controllers\Api\BaseController` (which uses `ApiResponseTrait`) or directly use `ApiResponseTrait`.
- **FR-008**: `BaseApiController` MUST expose the following public/protected methods:
  - `success(mixed $data, ?string $message, int $statusCode): JsonResponse`
  - `error(ApiErrorCode $code, ?string $message, ?array $details, ?int $statusCode): JsonResponse`
  - `paginated(mixed $collection, array $paginationMeta, int $statusCode): JsonResponse`
- **FR-009**: All methods MUST include PHPDoc with `@param` and `@return` annotations.
- **FR-010**: `paginated()` response MUST produce: `{ "success": true, "data": [...], "meta": { "current_page": N, "per_page": N, "total": N, "last_page": N }, "error": null }`.
  > **Contract Note (formally accepted):** The `paginated()` shape is a 4-key envelope `{ success, data, meta, error }` which extends the standard 3-key contract `{ success, data, error }` defined in `AGENTS.md`. This extension is **formally accepted for paginated responses only**. `meta` MUST NOT appear in non-paginated (scalar/object) responses. All other success/error responses use the unmodified 3-key contract.

### API Resource Base Class

- **FR-011**: A `BaseApiResource` class MUST exist at `app/Http/Resources/BaseApiResource.php`.
- **FR-012**: `BaseApiResource` MUST extend `Illuminate\Http\Resources\Json\JsonResource`.
- **FR-013**: `BaseApiResource` MUST wrap resources in a `data` key by default (by setting `$wrap = 'data'` or implementing `toArray` appropriately).
- **FR-014**: `BaseApiResource` MUST provide a static `collection()` method that, when given a `LengthAwarePaginator`, automatically appends `meta` pagination data to the response.
- **FR-015**: All existing Resources (`UserResource`, `RoleResource`, `PermissionResource`, `UserRoleResource`) MUST be updated to extend `BaseApiResource`.

### Middleware Stack

- **FR-016**: The global middleware stack in `Kernel.php` MUST enforce this order for all requests:
  `TrustProxies → HandleCors → ValidatePostSize → ConvertEmptyStringsToNull → CorrelationIdMiddleware → RequestResponseLoggingMiddleware`
- **FR-017**: The `api` middleware group MUST include: `ThrottleRequests::with(10, 1) → SubstituteBindings`.
- **FR-018**: Route middleware aliases MUST include: `'role' => RoleMiddleware::class`, `'permission' => PermissionMiddleware::class`, `'throttle' => ThrottleRequests::class`, `'check-account-lockout' => CheckAccountLockout::class`.
- **FR-019**: `CorrelationIdMiddleware` MUST be registered globally (not per-route) so every request — including health checks — receives a correlation ID.
- **FR-020**: `RequestResponseLoggingMiddleware` MUST skip logging for paths: `health`, `metrics`, `ping`, `status` (already implemented; must be verified).

### Rate Limiting

- **FR-021**: A named rate limiter `api-authenticated` MUST be registered: 60 requests per minute, keyed by authenticated user ID (`user_id|ip` fallback).
- **FR-022**: A named rate limiter `api-public` MUST be registered: 10 requests per minute, keyed by IP address. Implementation MUST use `$request->ip()` (Laravel TrustProxies-resolved value) for the IP key. Direct `X-Forwarded-For` header inspection is forbidden — it bypasses `TrustProxies` and enables IP spoofing.
- **FR-023**: A named rate limiter `api-admin` MUST be registered: 300 requests per minute, keyed by authenticated user ID.
- **FR-024**: All existing named rate limiters (`api`, `auth-login`, `auth-register`, `auth-forgot-password`, `auth-email-resend`, `user-avatar-upload`) MUST be preserved.
- **FR-025**: Rate-limited responses MUST return HTTP 429 with `error.code = "RATE_LIMIT_EXCEEDED"` following the unified error contract. `Retry-After` header MUST be present.
- **FR-026**: `GET /api/health` MUST be exempt from all rate limiting.

### CORS Configuration

- **FR-027**: CORS configuration MUST live in `config/cors.php` (Laravel's built-in config).
- **FR-028**: `allowed_origins` MUST be populated from environment variable `CORS_ALLOWED_ORIGINS` (comma-separated, e.g., `http://localhost:3000,https://app.bunyan.sa`).
- **FR-029**: `allowed_methods` MUST include: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`.
- **FR-030**: `allowed_headers` MUST include: `Content-Type`, `Authorization`, `X-Requested-With`, `X-Correlation-ID`, `Accept`, `Accept-Language`.
- **FR-031**: `exposed_headers` MUST include: `X-Correlation-ID` so the browser can read the correlation ID.
- **FR-032**: `supports_credentials` MUST be `true` to support Sanctum cookie-based auth from the browser.
- **FR-033**: `max_age` MUST be set to `86400` (24 hours) to cache preflight responses.

### Health Check Endpoint

- **FR-034**: A `GET /api/health` route MUST exist, outside the `v1` prefix, with no authentication middleware.
- **FR-035**: The health check MUST be handled by `App\Http\Controllers\Api\HealthController`.
- **FR-036**: The health check response MUST include:
  ```json
  {
    "success": true,
    "data": {
      "status": "healthy|degraded|unhealthy",
      "version": "1.0.0",
      "environment": "local|staging|production",
      "checks": {
        "database": true,
        "cache": true
      },
      "timestamp": "2026-04-14T00:00:00Z"
    },
    "error": null
  }
  ```
- **FR-037**: Database check MUST attempt a simple query (e.g., `DB::select('SELECT 1')`); on failure, `checks.database` = `false`.
- **FR-038**: Cache check MUST attempt a write/read operation to the default cache driver; on failure, `checks.cache` = `false`.
- **FR-039**: If ALL checks pass → HTTP 200, `status = "healthy"`. If non-critical check fails (cache) → HTTP 200, `status = "degraded"`. If critical check fails (database) → HTTP 503, `status = "unhealthy"`.
- **FR-040**: Health check MUST be excluded from rate limiting and request/response logging.

### API Documentation (OpenAPI / Swagger)

- **FR-041**: `darkaonline/l5-swagger` package MUST be installed and configured.
- **FR-042**: OpenAPI base info MUST include: `title: "Bunyan API"`, `version: "1.0.0"`, `description: "Bunyan construction marketplace API"`.
- **FR-043**: The Swagger UI MUST be accessible at `GET /api/documentation`.
- **FR-044**: The OpenAPI JSON spec MUST be accessible at `GET /api/documentation.json` (or configurable path).
- **FR-045**: Security scheme `BearerAuth` (HTTP Bearer) MUST be defined, corresponding to Sanctum token auth.
- **FR-046**: `@OA\Info`, `@OA\Server`, and `@OA\SecurityScheme` annotations MUST be present in the base controller or a dedicated annotation class.
- **FR-047**: Health check endpoint (`GET /api/health`) MUST be documented with its response schema.

### Error Handling Integration

- **FR-048**: All error responses from any controller extending `BaseApiController` MUST conform to the unified error contract from STAGE_05.
- **FR-049**: `ThrottleRequestsException` MUST be handled in `App\Exceptions\Handler` to return HTTP 429 with `error.code = "RATE_LIMIT_EXCEEDED"` (already implemented; must be verified and documented).
- **FR-050**: Error messages in `success()`, `error()`, `paginated()` responses MUST support Arabic and English translations via Laravel i18n (`lang/ar/` and `lang/en/`).

---

## Non-Functional Requirements

### Performance

- **NFR-001**: Health check endpoint MUST respond within 200ms under normal conditions.
- **NFR-002**: Base controller `success()` and `error()` overhead MUST be under 5ms (no external I/O).
- **NFR-003**: Correlation ID middleware overhead MUST remain under 2ms per request.
- **NFR-004**: Rate limiter lookups MUST use the cache driver (not database) for O(1) performance.

### Security

- **NFR-005**: CORS `allowed_origins` MUST NOT be set to `*` in staging or production environments.
- **NFR-006**: `X-Correlation-ID` header value from incoming requests MUST be validated as UUID v4 before propagation; invalid values are regenerated (already implemented).
- **NFR-007**: API documentation (`/api/documentation`) MUST NOT expose internal Laravel exceptions or stack traces.
- **NFR-008**: Rate limiting keys MUST use user ID (not session) for authenticated endpoints to prevent key sharing across sessions.
- **NFR-009**: Health check MUST NOT expose sensitive data (e.g., DB credentials, secret keys, internal IPs).

### Reliability

- **NFR-010**: Health check degradation (`cache` failure) MUST NOT return HTTP 5xx — only HTTP 200 with `status = "degraded"`.
- **NFR-011**: Rate limiter failures (cache unavailable) MUST fail open (allow requests) rather than fail closed (block all traffic), with a logged warning.

### Maintainability

- **NFR-012**: All public and protected methods in `BaseApiController` and `BaseApiResource` MUST have PHPDoc.
- **NFR-013**: Route files MUST be split by domain (auth, admin, user) for readability as the platform grows.
- **NFR-014**: Named rate limiters MUST be registered in `AppServiceProvider::boot()` following the existing pattern.

### Observability

- **NFR-015**: Every API response MUST contain `X-Correlation-ID` header (already enforced by `CorrelationIdMiddleware` and `ApiResponseTrait`).
- **NFR-016**: Rate limit hits MUST be logged at `warning` level with context: `user_id`, `ip`, `route`, `limit_key`.

### Internationalisation

- **NFR-017**: All user-facing error messages returned via `ApiResponseTrait::error()` MUST have translations in `lang/ar/` and `lang/en/`.
- **NFR-018**: Arabic RTL is a display concern handled by the frontend; backend MUST return messages in both languages (or the language requested via `Accept-Language` header).

---

## Scope

### In Scope

- `BaseApiController` at `app/Http/Controllers/Api/V1/BaseApiController.php`
- `BaseApiResource` at `app/Http/Resources/BaseApiResource.php`
- Updated existing resources to extend `BaseApiResource`
- Route file split: `routes/api/auth.php`, `routes/api/admin.php`, `routes/api/user.php` (included from `routes/api.php`)
- Health check controller and route: `GET /api/health`
- Named rate limiters: `api-authenticated`, `api-public`, `api-admin`
- CORS config: `config/cors.php` with env-driven origins and credential support
- Middleware aliases: `role`, `permission` registered in Kernel
- Swagger/OpenAPI package integration + base annotations
- Unit tests for `BaseApiController` response methods
- Feature tests for health check, rate limiting, CORS headers
- Arabic/English translations for any new error messages
- PHPDoc on all public/protected methods

### Out of Scope

- Frontend API client changes (handled in Nuxt stages)
- Redis cache configuration (infrastructure; covered in DevOps stage)
- API versioning migration strategy for v2 (future ADR)
- Request body validation in the base controller (handled per-endpoint via Form Requests)
- Automated API documentation generation pipeline (CI/CD stage)
- Custom exception classes beyond what STAGE_05 established
- SMS/email alerting on rate limit spikes
- OAuth2 / OpenID Connect (future auth stage)
- GraphQL endpoint
- WebSocket / SSE API surface

---

## Technical Constraints

- **TC-001**: Laravel version ≥ 11.x (already established in STAGE_01).
- **TC-002**: Must use `HandleCors` from `Illuminate\Http\Middleware\HandleCors` (Laravel built-in) driven by `config/cors.php` — no custom CORS class unless necessary.
- **TC-003**: `darkaonline/l5-swagger` is the mandated Swagger package; no alternative.
- **TC-004**: `BaseApiController` must remain backward-compatible with existing controllers (`AuthController`, `AdminRbacController`, `UserController`) — they must still work without modification after this stage.
- **TC-005**: Named rate limiters must use Laravel `RateLimiter` facade in `AppServiceProvider::boot()` — no inline `throttle:N,M` string on main authenticated/admin route groups.
- **TC-006**: All tests must pass `php artisan test --parallel`; no global state mutations.
- **TC-007**: PHPStan level 6 must pass (`composer run lint`) without new errors.
- **TC-008**: Existing `App\Http\Controllers\Api\BaseController` is preserved; `BaseApiController` extends it or duplicates `ApiResponseTrait` usage — must not break STAGE_03/STAGE_04 controllers.

---

## Acceptance Criteria

| ID    | Criterion                                                                                      | Verification Method                       |
| ----- | ---------------------------------------------------------------------------------------------- | ----------------------------------------- |
| AC-01 | `GET /api/health` returns HTTP 200 with `data.status = "healthy"` (all checks passing)         | Feature test: `HealthCheckTest`           |
| AC-02 | `GET /api/health` returns HTTP 503 when DB is unreachable                                      | Feature test with mocked DB failure       |
| AC-03 | `GET /api/health` returns HTTP 200 without authentication token                                | Feature test (no `Authorization` header)  |
| AC-04 | `BaseApiController::success()` returns `{ success: true, data: ..., error: null }`             | Unit test: `BaseApiControllerTest`        |
| AC-05 | `BaseApiController::error()` returns correct error code, message, HTTP status                  | Unit test: `BaseApiControllerTest`        |
| AC-06 | `BaseApiController::paginated()` returns `data` array + `meta` pagination object               | Unit test: `BaseApiControllerTest`        |
| AC-07 | Every response includes `X-Correlation-ID` header                                              | Feature test: any endpoint assertion      |
| AC-08 | Exceeding `api-authenticated` limit returns HTTP 429 + `RATE_LIMIT_EXCEEDED`                   | Feature test: `RateLimitTest`             |
| AC-09 | Exceeding `api-public` limit returns HTTP 429 + `RATE_LIMIT_EXCEEDED`                          | Feature test: `RateLimitTest`             |
| AC-10 | Admin routes use `api-admin` limiter (300 req/min); not blocked at 60                          | Feature test: `AdminRateLimitTest`        |
| AC-11 | CORS preflight `OPTIONS` from allowed origin returns correct `Access-Control-Allow-*` headers  | Feature test: `CorsTest`                  |
| AC-12 | `Access-Control-Expose-Headers` includes `X-Correlation-ID`                                    | Feature test: `CorsTest`                  |
| AC-13 | `GET /api/documentation` returns HTTP 200 (Swagger UI served)                                  | Feature test: `SwaggerTest`               |
| AC-14 | `BaseApiResource` wraps single resource in `data` key                                          | Unit test: `BaseApiResourceTest`          |
| AC-15 | `BaseApiResource::collection()` with paginator produces `meta` object                          | Unit test: `BaseApiResourceTest`          |
| AC-16 | All existing resources (`UserResource`, etc.) still return correct fields after extending base | Regression via existing API feature tests |
| AC-17 | PHPStan level 6 passes with no new errors                                                      | `composer run lint`                       |
| AC-18 | All existing tests (STAGE_01–05) continue to pass                                              | `php artisan test --parallel`             |

---

## Dependencies

### Upstream (Must be complete before this stage)

| Stage    | What is required                                                                                                                        | Status   |
| -------- | --------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| STAGE_04 | `RoleMiddleware`, `PermissionMiddleware`, Gate policies, `auth:sanctum` middleware                                                      | Complete |
| STAGE_05 | `ApiResponseTrait`, `ApiErrorCode` enum, `CorrelationIdMiddleware`, `RequestResponseLoggingMiddleware`, `Handler.php` throttle handling | Complete |

### Downstream (Depends on this stage)

| Stage           | What it needs from this stage                                     |
| --------------- | ----------------------------------------------------------------- |
| All API stages  | `BaseApiController`, `BaseApiResource`, versioned route structure |
| STAGE_07+       | Authenticated route groups with `api-authenticated` rate limiter  |
| DevOps stage    | `GET /api/health` endpoint for load balancer health probes        |
| Frontend stages | CORS configured to allow Nuxt dev server origin                   |

### External Packages

| Package                  | Version | Purpose                            |
| ------------------------ | ------- | ---------------------------------- |
| `darkaonline/l5-swagger` | ~9.0    | OpenAPI 3.0 documentation          |
| `laravel/framework`      | ≥11.x   | HandleCors, RateLimiter (built-in) |

---

## Key Entities

- **BaseApiController**: V1 namespace base controller; provides `success()`, `error()`, `paginated()` response helpers. Located at `app/Http/Controllers/Api/V1/BaseApiController.php`.
- **BaseApiResource**: Base JSON resource with `data` wrapping and pagination meta support. Located at `app/Http/Resources/BaseApiResource.php`.
- **HealthController**: Controller for `GET /api/health`. No inheritance requirement beyond returning JSON. Located at `app/Http/Controllers/Api/HealthController.php`.
- **NamedRateLimiters**: `api-authenticated` (60/min by user), `api-public` (10/min by IP), `api-admin` (300/min by user). Registered in `AppServiceProvider::boot()`.
- **CORS Config**: `config/cors.php`, driven by `CORS_ALLOWED_ORIGINS` env variable.

---

## Assumptions

- The existing `App\Http\Controllers\Api\BaseController` (using `ApiResponseTrait`) created in STAGE_05 is preserved. `BaseApiController` extends it rather than replacing it, to maintain backward compatibility.
- `HandleCors` middleware is already registered globally via `Kernel::$middleware` (confirmed in existing code). This stage only needs to ensure `config/cors.php` is correctly populated.
- The cache driver is Redis or file in all environments; health check tests use array driver in testing.
- `APP_VERSION` environment variable or `config/app.php` version key will be available for the health check `version` field. If absent, defaults to `"1.0.0"`.
- `darkaonline/l5-swagger` docs route is accessible in all environments (no `APP_ENV=local` restriction); this can be gated in a future security hardening stage.
- Route sub-files (`routes/api/auth.php`, etc.) are included via `require` or `Route::group()` + `base_path()` from `routes/api.php` — not via `RouteServiceProvider` (which is deprecated in Laravel 11).

---

## Clarifications

### Session 2026-04-14

The following clarifications were resolved during a deep ambiguity scan of this spec. Each item represents a concrete decision that eliminates implementation-blocking uncertainty.

---

#### CLR-01 — Middleware Registration Target: `bootstrap/app.php`, Not `Kernel.php`

**Question:**  
FR-016, FR-017, and FR-018 reference "Kernel.php" and "Kernel::$middleware". In Laravel 11, `Kernel.php` does not exist. Where are middleware aliases and group configurations registered?

**Decision:**  
All middleware registration for this stage MUST target `bootstrap/app.php` using the `->withMiddleware()` callback:

- **Middleware aliases** (`role`, `permission`, `check-account-lockout`, `throttle`) — registered via `$middleware->alias([...])`. Confirmed: `role`, `permission`, and `check-account-lockout` are already registered in `bootstrap/app.php` from prior stages.
- **Global prepend** (e.g., `CorrelationIdMiddleware`) — registered via `$middleware->prepend(CorrelationIdMiddleware::class)` or verified present in the default Laravel 11 global stack.
- **`api` group append** — registered via `$middleware->appendToGroup('api', [...])`.

All spec references to "Kernel.php" are understood to mean `bootstrap/app.php`. No `Kernel.php` file is to be created.

**Rationale:**  
The actual codebase (`backend/bootstrap/app.php`) already follows this pattern. Misreading FR-016/FR-018 as targeting `Kernel.php` would cause implementers to create a deprecated file or fail to register middleware correctly.

---

#### CLR-02 — Throttle Strategy: Named Limiters Per Route Group, Not a Blanket `api` Group Default

**Question:**  
FR-017 states the `api` middleware group MUST include `ThrottleRequests::with(10, 1)`. FR-021 and FR-022 register separate named limiters `api-authenticated` (60/min) and `api-public` (10/min). If both apply simultaneously, authenticated users would be blocked at 10/min (the group default) before the 60/min named limiter has any effect. Which throttle configuration governs?

**Decision:**  
The blanket `ThrottleRequests::with(10, 1)` described in FR-017 MUST NOT be appended to the global `api` middleware group. Instead, named rate limiters are applied explicitly per route group:

- **Public routes** (unauthenticated) → `throttle:api-public` (10 req/min, keyed by IP), applied via `->middleware('throttle:api-public')` on each public route group.
- **Authenticated routes** (non-admin) → `throttle:api-authenticated` (60 req/min, keyed by user ID), applied via `->middleware('throttle:api-authenticated')` on each `auth:sanctum` route group.
- **Admin routes** → `throttle:api-admin` (300 req/min, keyed by user ID), applied via `->middleware('throttle:api-admin')` on admin route groups.
- **`GET /api/health`** → exempt from all throttle middleware (no `throttle:*` applied).

FR-017 is superseded by this clarification. The `api` middleware group configuration in `bootstrap/app.php` MUST NOT include a default `ThrottleRequests` entry beyond what is already present. AC-08, AC-09, AC-10 remain valid verification tests.

**Rationale:**  
Stacking two `ThrottleRequests` instances on the same request produces additive limits. A 10/min default on the `api` group makes the 60/min `api-authenticated` limiter unreachable. The named-limiter-per-group model is the correct Laravel pattern and matches the intent of US3.

---

#### CLR-03 — Built-in `/up` Health Route Coexists With Custom `/api/health`

**Question:**  
`bootstrap/app.php` already registers `health: '/up'` via `->withRouting()`, which creates Laravel's built-in simple health endpoint. FR-034 requires a custom `GET /api/health` endpoint with rich JSON checks. Should the built-in `/up` be removed, or do both coexist?

**Decision:**  
Both endpoints MUST coexist:

- **`GET /up`** — Laravel's built-in uptime probe (returns `200 OK` with plain text body). MUST remain in `bootstrap/app.php` as-is. Used by Laravel Octane, health package internals, and some Docker probes.
- **`GET /api/health`** — Custom rich JSON endpoint defined in `routes/api.php` outside the `v1` prefix group, handled by `App\Http\Controllers\Api\HealthController`. Returns the full `status`, `version`, `environment`, `checks.database`, `checks.cache`, `timestamp` schema from FR-036.

`/up` is for container liveness. `/api/health` is for application readiness. They are not duplicates.

**Rationale:**  
Removing `health: '/up'` would disable an internal Laravel mechanism. The custom `/api/health` serves a different operational purpose (rich health checks for load balancers and monitoring tools). Both must exist independently.

---

#### CLR-04 — HealthController Is Exempt From the Service Layer Pattern

**Question:**  
The platform architecture mandates the Service/Repository pattern for all business logic. `HealthController` would contain direct `DB::select()` and `Cache::put()/get()` calls inline. Does this constitute a violation of the layering rules?

**Decision:**  
`HealthController` is **explicitly exempt** from the service layer pattern for this stage. The health check logic (DB ping, cache probe) is infrastructure-diagnostic code, not domain business logic. It MUST be implemented directly in `HealthController::check()`:

```php
// Database check
DB::select('SELECT 1');

// Cache check
Cache::put('health_probe', true, 5);
Cache::get('health_probe');
```

No `HealthService` or `HealthRepository` is to be created in this stage. If additional health dimensions are added in a future Infrastructure stage, extraction to a `HealthCheckService` may be considered at that point.

**Rationale:**  
Forcing infrastructure diagnostics through the service pattern adds indirection without benefit. The architecture rules apply to domain logic (projects, users, payments). Health checks are a cross-cutting infrastructure concern that is universally implemented in controllers or dedicated health-check classes across Laravel applications.

---

#### CLR-05 — l5-swagger: Annotation Scan Paths, Server URL, and Generated File Location

**Question:**  
FR-041–FR-047 require l5-swagger integration but do not specify: (a) which directories l5-swagger should scan for `@OA\*` annotations, (b) the `@OA\Server` URL value, (c) where the generated OpenAPI JSON is stored, or (d) where the `@OA\Info`, `@OA\Server`, `@OA\SecurityScheme` block lives.

**Decision:**  
The `config/l5-swagger.php` published config MUST be configured as follows:

- **Annotation scan paths**: `[ app_path('Http/Controllers') ]` — covers the entire controllers tree.
- **Generated JSON output**: `storage_path('api-docs/api-docs.json')` (l5-swagger default; do not override).
- **Documentation URL**: `GET /api/documentation` (default l5-swagger route prefix; do not override).

The `@OA\Info`, `@OA\Server`, and `@OA\SecurityScheme` block MUST be placed in a dedicated class `App\Http\Controllers\Api\V1\OpenApiAnnotations` (a final class with no methods, annotations only), **not** in `BaseApiController`, to keep the base controller clean:

```php
// app/Http/Controllers/Api/V1/OpenApiAnnotations.php
#[OA\Info(title: "Bunyan API", version: "1.0.0", description: "Bunyan construction marketplace API")]
#[OA\Server(url: "/api", description: "Bunyan API Server")]
#[OA\SecurityScheme(securityScheme: "BearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]
final class OpenApiAnnotations {}
```

The `@OA\Server` URL MUST use a relative path (`/api`) rather than `APP_URL` to remain portable across environments. l5-swagger will resolve it relative to the host.

The package version pin `~9.0` (as listed in the External Packages table) is the resolved version per research R-01; Composer lockfile enforces the exact resolved version.

**Rationale:**  
Without scan path configuration, `php artisan l5-swagger:generate` scans no files and produces an empty spec. A relative server URL avoids hardcoding environment-specific base URLs. Isolating annotations in a dedicated class is a Laravel/l5-swagger convention that prevents annotation noise in the base controller and satisfies FR-009 (PHPDoc-only in base controller).

---

**Clarification Summary:**

| ID     | Topic                                        | FRs Affected           | `[NEEDS CLARIFICATION]` Markers Resolved |
| ------ | -------------------------------------------- | ---------------------- | ---------------------------------------- |
| CLR-01 | Middleware registration in bootstrap/app.php | FR-016, FR-017, FR-018 | 0 (implicit ambiguity resolved)          |
| CLR-02 | Throttle strategy — named limiters per group | FR-017, FR-021, FR-022 | 0 (conflicting FRs resolved)             |
| CLR-03 | `/up` vs `/api/health` coexistence           | FR-034                 | 0 (implicit ambiguity resolved)          |
| CLR-04 | HealthController service layer exemption     | FR-035, FR-037, FR-038 | 0 (architectural question resolved)      |
| CLR-05 | l5-swagger scan paths + server URL           | FR-041, FR-042, FR-046 | 0 (missing config details resolved)      |

**Total clarifications added: 5**  
**`[NEEDS CLARIFICATION]` markers remaining: 0**  
**Spec status: Implementation-ready**
