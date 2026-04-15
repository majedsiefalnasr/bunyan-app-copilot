# Tasks — API Foundation (STAGE_06)

> **Phase:** 01_PLATFORM_FOUNDATION
> **Based on:** `specs/runtime/006-api-foundation/plan.md`
> **Created:** 2026-04-14T00:00:00Z
> **Total Tasks:** 34

## Legend

- `T001` — Sequential task ID in execution order
- `[P]` — Parallelizable task (can run concurrently with other `[P]` tasks in same group)
- `[US1]`–`[US6]` — User story reference
- `- [ ]` — Incomplete | `- [X]` — Complete

---

## Phase 0 — Composer Dependency

> **Goal:** Install `darkaonline/l5-swagger ~9.0` and publish its config before any OpenAPI annotations are written.
> **Dependency:** Must complete before Phase 1 (annotations reference swagger-php types) and before Phase 6.

- [x] T001 Install `darkaonline/l5-swagger` via Composer (`composer require darkaonline/l5-swagger`) — `backend/composer.json`, `backend/composer.lock`
- [x] T002 Publish L5Swagger vendor config and Swagger UI views (`php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"`) — `backend/config/l5-swagger.php`
- [x] T003 Add `L5_SWAGGER_GENERATE_ALWAYS=true` (dev default) to `backend/.env.example` with a comment noting it must be `false` in production — `backend/.env.example`

---

## Phase 1 — Base Classes

> **Goal:** Create `BaseApiController` (with `paginated()`) and `BaseApiResource` (with `$wrap = 'data'`); migrate all four existing resources.
> **Dependency:** T001–T003 complete. T005 must complete before T007–T010. T006 (audit) must complete before T007–T010.
> **User Story:** US2

- [x] T004 [US2] Create `BaseApiController` extending `App\Http\Controllers\Api\BaseController`; add `protected paginated(mixed $collection, int $statusCode = 200): JsonResponse` method that extracts `currentPage()`, `perPage()`, `total()`, `lastPage()` from paginator and returns standardised `{ success, data, meta, error }` shape; add full PHPDoc — `backend/app/Http/Controllers/Api/V1/BaseApiController.php`
- [x] T005 [US2] Declare `abstract class BaseApiResource` extending `Illuminate\Http\Resources\Json\JsonResource` (MUST be `abstract class` — not plain `class` — because it declares `abstract toArray()`; PHP will not compile otherwise); set `public static $wrap = 'data'`; declare `abstract public function toArray($request): array`; add `paginatedCollection(LengthAwarePaginator $paginator): array` static helper for pagination meta extraction; override `withResponse()` to propagate `X-Correlation-ID` header; add full PHPDoc — `backend/app/Http/Resources/BaseApiResource.php`
- [x] T006 [US2] Audit all existing resource-related feature tests for assertions that access response fields without a `data` key (e.g., `$response['email']`) — identify every test that will break when `$wrap = 'data'` takes effect — `backend/tests/Feature/`
- [x] T007 [P] [US2] Update `UserResource` to extend `BaseApiResource`; fix any test assertions identified in T006 that access unwrapped fields — `backend/app/Http/Resources/UserResource.php`
- [x] T008 [P] [US2] Update `RoleResource` to extend `BaseApiResource`; fix any test assertions identified in T006 that access unwrapped fields — `backend/app/Http/Resources/RoleResource.php`
- [x] T009 [P] [US2] Update `PermissionResource` to extend `BaseApiResource`; fix any test assertions identified in T006 that access unwrapped fields — `backend/app/Http/Resources/PermissionResource.php`
- [x] T010 [P] [US2] Update `UserRoleResource` to extend `BaseApiResource`; fix any test assertions identified in T006 that access unwrapped fields — `backend/app/Http/Resources/UserRoleResource.php`

---

## Phase 2 — Route Extraction

> **Goal:** Extract all existing routes into versioned sub-files under `routes/api/v1/`; update `routes/api.php` to require them and register the health route stub.
> **Dependency:** Phase 1 complete. T011–T013 can run in parallel with each other; T014 depends on all three.
> **User Story:** US1

- [x] T011 [P] [US1] Extract all auth routes (registration, login, logout, password reset, email verification, profile) into new sub-file with `->name('api.v1.auth.*')` convention; preserve all existing route URIs unchanged — `backend/routes/api/v1/auth.php`
- [x] T012 [P] [US1] Extract all user routes (avatar upload, profile update) into new sub-file with `->name('api.v1.users.*')` convention; preserve all existing route URIs unchanged — `backend/routes/api/v1/users.php`
- [x] T013 [P] [US1] Extract all admin RBAC routes into new sub-file with `->name('api.v1.admin.*')` convention; preserve all existing route URIs unchanged — `backend/routes/api/v1/admin.php`
- [x] T014 [US1] Update `routes/api.php` to `require __DIR__.'/api/v1/auth.php'`, `users.php`, and `admin.php` inside the appropriate groups; register `Route::get('health', [HealthController::class, 'check'])->name('api.health')` outside the `v1` prefix block with no auth and no throttle middleware — `backend/routes/api.php`

---

## Phase 3 — Rate Limiters

> **Goal:** Register three new named rate limiters in `AppServiceProvider`; fix 429 handler to emit `Retry-After` and `X-RateLimit-*` headers.
> **Dependency:** Phase 1 complete. T015 and T016 can run in parallel with Phase 5 (CORS).
> **User Story:** US3

- [x] T015 [US3] Append three `RateLimiter::for()` calls in `AppServiceProvider::boot()`: `api-authenticated` (60 req/min, keyed by `user:{userId}` with `ip:{ip}` fallback), `api-public` (10 req/min, keyed by IP), `api-admin` (300 req/min, keyed by `admin:{userId}`) — `backend/app/Providers/AppServiceProvider.php`
- [x] T016 [US3] Fix `handleRateLimitException()` in `Handler.php` to call `$e->getHeaders()` on the `TooManyRequestsHttpException` and forward the resulting `Retry-After`, `X-RateLimit-Limit`, and `X-RateLimit-Remaining` headers in the 429 JSON response body via `$this->errorResponse(... headers: $headers)` — `backend/app/Exceptions/Handler.php`

---

## Phase 4 — Health Check

> **Goal:** Add `HEALTH_CHECK_FAILED` enum case; create `HealthController` with inline DB + Redis probes; register i18n translation keys.
> **Dependency:** T017 (enum case) must complete before T018 (controller references it). T019, T020, T021 can run after T018. T020 and T021 are parallel.
> **User Story:** US5

- [x] T017 [US5] Add `HEALTH_CHECK_FAILED = 'HEALTH_CHECK_FAILED'` case to `ApiErrorCode` enum — `backend/app/Enums/ApiErrorCode.php`
- [x] T018 [US5] Create `HealthController` in namespace `App\Http\Controllers\Api`; implement `check(): JsonResponse` with private `checkDatabase()` (runs `DB::select('SELECT 1')` wrapped in try/catch \Throwable to prevent stalls; use a short-lived PDO connection using `DB::connection()->getPdo()` to allow OS-level timeout propagation) and `checkCache()` (write/read/forget probe key, wrapped in try/catch \Throwable) helpers; return HTTP 200 with `status=healthy` when all pass, HTTP 200 with `status=degraded` when only cache fails, HTTP 503 with `success=false, data=null, error.code=HEALTH_CHECK_FAILED, error.details={...probe data}` when DB fails; include `version`, `environment`, `timestamp` in data payload; both probe helpers MUST catch all exceptions so a hung probe does not kill the request; satisfies NFR-001 (≤200ms p95) — `backend/app/Http/Controllers/Api/HealthController.php`
- [x] T019 [US5] Verify `health` is present in the skip-path list in `RequestResponseLoggingMiddleware`; add it if missing so health check requests are excluded from structured logging — `backend/app/Http/Middleware/RequestResponseLoggingMiddleware.php`
- [x] T020 [P] [US5] Add `health_check_failed` key with English message to errors translation file — `backend/lang/en/errors.php`
- [x] T021 [P] [US5] Add `health_check_failed` key with Arabic message to errors translation file — `backend/lang/ar/errors.php`

---

## Phase 5 — CORS Configuration

> **Goal:** Create `config/cors.php` with env-driven origins; expose `X-Correlation-ID`; guard against wildcard + credentials misconfiguration.
> **Dependency:** Phase 1 complete. Can run entirely in parallel with Phase 3.
> **User Story:** US4

- [x] T022 [US4] Create `config/cors.php` with `paths: ['api/*', 'sanctum/csrf-cookie']`, `allowed_origins` parsed from `CORS_ALLOWED_ORIGINS` env (comma-split + trim + filter), `allowed_methods: [GET, POST, PUT, PATCH, DELETE, OPTIONS]`, `allowed_headers: [Content-Type, Authorization, X-Requested-With, X-Correlation-ID, Accept, Accept-Language]`, `exposed_headers: ['X-Correlation-ID']`, `max_age: 86400`, `supports_credentials: true` — `backend/config/cors.php`
- [x] T023 [US4] Add `CORS_ALLOWED_ORIGINS=http://localhost:3000` to `.env.example` with comment explaining comma-separated format; also append `CACHE_DRIVER=redis` with comment: `# Required for O(1) rate limiter operations (NFR-004); use array in testing (phpunit.xml already sets this)` — `backend/.env.example`
- [x] T024 [US4] Add boot-time CORS guard in `AppServiceProvider::boot()`: detect when `CORS_ALLOWED_ORIGINS` contains `*` while `supports_credentials = true` in non-local environments and throw an `InvalidArgumentException` to fail fast — `backend/app/Providers/AppServiceProvider.php`

---

## Phase 6 — OpenAPI Foundation

> **Goal:** Scaffold base OpenAPI annotation class; configure `l5-swagger.php`; annotate the health endpoint.
> **Dependency:** T001–T002 (l5-swagger installed + config published). T027 depends on T018 (HealthController must exist before annotating it).
> **User Story:** US6

- [x] T025 [US6] Create non-routable `OpenApiAnnotations` class containing only `@OA\Info` (title `"Bunyan API"`, version `"1.0.0"`, description, contact email `api@bunyan.sa`), `@OA\Server` (url `/api/v1`), and `@OA\SecurityScheme` (securityScheme `BearerAuth`, type `http`, scheme `bearer`) — `backend/app/Http/Controllers/Api/OpenApiAnnotations.php`
- [x] T026 [US6] Update `config/l5-swagger.php` (published in T002): set `paths.annotations` to `app_path('Http/Controllers')`, set `routes.api` to `'api/documentation'`, set `routes.docs` to `'api/documentation.json'`, bind `generate_always` to `env('L5_SWAGGER_GENERATE_ALWAYS', false)` — `backend/config/l5-swagger.php`
- [x] T027 [US6] Add `@OA\Get` annotation to `HealthController::check()` documenting `path="/health"`, `tags={"Health"}`, `summary`, and response schemas for HTTP 200 (`status`, `version`, `environment`, `checks`, `timestamp`) and HTTP 503 (`HEALTH_CHECK_FAILED` error contract shape) — `backend/app/Http/Controllers/Api/HealthController.php`

---

## Phase 7 — Tests

> **Goal:** Full test coverage for all new classes; HTTP-level feature tests for health, rate limiting, CORS contracts, and OpenAPI documentation endpoint; regression validation for resource migration.
> **Dependency:** All implementation tasks in Phases 1–6 must complete before corresponding tests. T028–T033 can execute in parallel; T034 must run after all of them.

- [x] T028 [P] [US2] Write unit tests for `BaseApiController`: assert `success()` returns `{success:true, data:..., error:null}` with HTTP 200; assert `error(ApiErrorCode::RESOURCE_NOT_FOUND)` returns HTTP 404 with `error.code=RESOURCE_NOT_FOUND`; assert `paginated()` returns `data` array + `meta` with `current_page`, `per_page`, `total`, `last_page` — `backend/tests/Unit/Http/Controllers/Api/V1/BaseApiControllerTest.php`
- [x] T029 [P] [US2] Write unit tests for `BaseApiResource`: assert single resource response is wrapped under `data` key; assert `paginatedCollection()` with a `LengthAwarePaginator` produces a `meta` key containing `current_page`, `per_page`, `total`, `last_page` — `backend/tests/Unit/Http/Resources/BaseApiResourceTest.php`
- [x] T030 [P] [US5] Write feature tests for `GET /api/health`: HTTP 200 + `data.status=healthy` when all probes pass; HTTP 200 + `data.status=degraded` when cache probe fails; HTTP 503 + `success=false, error.code=HEALTH_CHECK_FAILED` when DB probe fails; request without `Authorization` header returns non-401; response includes `data.checks.database` and `data.checks.cache`; assert every health response (200 and 503) includes `X-Correlation-ID` response header (AC-07) — `backend/tests/Feature/Api/HealthCheckTest.php`
- [x] T031 [P] [US3] Write feature tests for rate limiting: authenticated user's 61st request in a minute returns HTTP 429 with `error.code=RATE_LIMIT_EXCEEDED`; unauthenticated IP's 11th request returns HTTP 429; admin user is not blocked at 60 requests on admin routes; every 429 response includes `Retry-After`, `X-RateLimit-Limit`, and `X-RateLimit-Remaining` headers; `GET /api/health` is never rate-limited — `backend/tests/Feature/Api/RateLimitTest.php`
- [x] T032 [P] [US4] Write feature tests for CORS: preflight `OPTIONS` from allowed origin returns `Access-Control-Allow-Origin` matching origin and `Access-Control-Allow-Credentials: true`; `Access-Control-Expose-Headers` includes `X-Correlation-ID`; preflight allows `X-Correlation-ID` in `Access-Control-Request-Headers`; request from a non-configured origin receives no `Access-Control-Allow-Origin` header — `backend/tests/Feature/Api/CorsTest.php`
- [x] T033 [P] [US6] Write feature test for `GET /api/documentation` (AC-13): assert HTTP 200 and response body contains Swagger UI HTML; assert `GET /api/documentation.json` returns JSON with an `openapi` key; assert both routes are accessible without authentication — `backend/tests/Feature/Api/SwaggerTest.php`
- [x] T034 Run full test suite to validate no regression across STAGE_01–STAGE_05 and all new STAGE_06 tests pass (`php artisan test --parallel`); then run static analysis gate `composer run lint` (PHPStan level 6) and confirm exit code 0 — both the test run and the PHPStan gate must pass before STAGE_06 is merged (AC-17) — `backend/`

---

## Dependency Graph

```
T001 → T002 → T003
T001 → T004, T005, T025, T026         (l5-swagger types must be installed)
T005 → T006 → T007, T008, T009, T010  (audit before migrating; BaseApiResource must exist)
T004 → T011, T012, T013               (V1 namespace established)
T011, T012, T013 → T014               (sub-files must exist before require in api.php)
T004, T005 → T015                     (AppServiceProvider update after base classes exist)
T016 → T031                           (handler fix required for Retry-After header test)
T015 → T031                           (rate limiters required for rate limit test)
T017 → T018                           (HEALTH_CHECK_FAILED enum before HealthController)
T018 → T019, T027                     (controller must exist before skip-list verify and annotation)
T017, T018 → T030                     (health tests need both enum and controller)
T022 → T032                           (CORS tests need cors.php to exist)
T004 → T028                           (BaseApiController unit tests)
T005 → T029                           (BaseApiResource unit tests)
T007, T008, T009, T010 → T033         (regression run validates resource migration)
T028, T029, T030, T031, T032 → T033   (all new tests pass before regression run)
```

**Parallel Execution Waves:**

| Wave | Tasks                                          | Gate                                  |
| ---- | ---------------------------------------------- | ------------------------------------- |
| 1    | T001                                           | —                                     |
| 2    | T002, T003                                     | after T001                            |
| 3    | T004, T005                                     | after T001–T003                       |
| 4    | T006                                           | after T005                            |
| 5    | T007 ∥ T008 ∥ T009 ∥ T010 ∥ T011 ∥ T012 ∥ T013 | after T004, T005, T006                |
| 6    | T014                                           | after T011–T013                       |
| 7    | T015 ∥ T016 ∥ T022 ∥ T023 ∥ T024 ∥ T025 ∥ T026 | after T014                            |
| 8    | T017                                           | after T015 (AppServiceProvider ready) |
| 9    | T018                                           | after T017                            |
| 10   | T019 ∥ T020 ∥ T021 ∥ T027                      | after T018                            |
| 11   | T028 \| T029 \| T030 \| T031 \| T032 \| T033   | after all Phase 1–6 tasks             |
| 12   | T034                                           | after T028–T033                       |

---

## Implementation Strategy

1. **Phase 0 strictly first** — `darkaonline/l5-swagger` must be installed before any `@OA\*` annotations compile via PHPStan or CLI generation.
2. **Audit before migrating resources** — The `$wrap = 'data'` change is a breaking envelope change for existing tests. Completing T006 before T007–T010 avoids a test-fix whack-a-mole loop.
3. **Migrate resources one at a time** — Run `php artisan test --parallel` after each resource migration (T007–T010) to catch regressions early.
4. **HEALTH_CHECK_FAILED enum case before HealthController** — PHP 8.x will throw `Error: Undefined constant` if `ApiErrorCode::HEALTH_CHECK_FAILED` is referenced before the case is declared. Always do T017 before T018.
5. **Phase 3 and Phase 5 are independent** — Once Phase 1 completes, rate limiter and CORS work can proceed in parallel across two developers.
6. **OpenAPI annotation on HealthController is additive** — T027 is a docblock-only change; it does not alter runtime behavior and can be applied last in Phase 6 without unblocking any other task.
7. **PHPStan must stay green** — After each phase, verify `composer run lint` passes at level 6. Especially important after resource migration (T007–T010) and after adding the new `paginated()` signature (T004).
8. **All tests in Acceptance Criteria must map 1:1 to a test task** — AC-01 through AC-15 are covered by T028–T033. AC-13 (`GET /api/documentation`) is covered by T033. AC-16 (regression) and AC-17–18 (PHPStan + full suite) are covered by T034.
