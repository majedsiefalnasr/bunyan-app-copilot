# Plan Report — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2026-04-14T00:00:00Z

## Plan Summary

| Metric           | Value                                                                                              |
| ---------------- | -------------------------------------------------------------------------------------------------- |
| New Tables       | 0                                                                                                  |
| New Endpoints    | 1 (`GET /api/health`)                                                                              |
| New Services     | 0 (HealthController uses inline probes per CLR-04)                                                 |
| New Controllers  | 3 (`BaseApiController`, `HealthController`, `OpenApiAnnotations`)                                  |
| New Resources    | 1 (`BaseApiResource`)                                                                              |
| New Config Files | 2 (`config/cors.php`, `config/l5-swagger.php`)                                                     |
| New Route Files  | 3 (`routes/api/v1/auth.php`, `users.php`, `admin.php`)                                             |
| New Tests        | 5 (`HealthCheckTest`, `RateLimitTest`, `CorsTest`, `BaseApiControllerTest`, `BaseApiResourceTest`) |
| Modified Files   | 7 (`api.php`, `AppServiceProvider.php`, `composer.json`, 4 existing Resources)                     |
| New Pages        | 0                                                                                                  |
| New Components   | 0                                                                                                  |

---

## Architecture Decisions

### D-01 — BaseApiController in `Api\V1\` namespace (not `Api\`)

`BaseApiController` lives under `App\Http\Controllers\Api\V1\` to signal its role as the V1-specific base. `HealthController` lives at `App\Http\Controllers\Api\` because it is outside the versioned prefix.

### D-02 — `paginated()` on BaseApiController, not ApiResponseTrait

`paginated()` is added as a protected method on `BaseApiController` directly (not on the shared `ApiResponseTrait`) to avoid regressing existing non-V1 consumers of the trait.

### D-03 — Health check outside versioned prefix

`GET /api/health` is registered before the `v1` prefix group so monitoring tools never need to track version changes. Exempt from rate limiters.

### D-04 — No Kernel.php changes

All FR-016–FR-019 middleware requirements are already satisfied by the existing `Kernel.php`. This stage adds only named rate limiters (via `AppServiceProvider::boot()`) and new route files.

### D-05 — HEALTH_CHECK_FAILED is a new ApiErrorCode enum value

The 503 health response uses `HEALTH_CHECK_FAILED` (new) rather than `SERVER_ERROR` to give consuming clients a machine-readable signal that a health probe specifically failed. `data: null` on 503; probe details in `error.details`.

### D-06 — l5-swagger ~9.0 pinned version

`darkaonline/l5-swagger:~9.0` is used (not `^8.x`) to ensure OpenAPI 3.0 annotation support via `zircote/swagger-php:^4.x`.

---

## Implementation Phases

| Phase | Description                                                         | Key Files                                                                                     |
| ----- | ------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| 0     | Composer: install l5-swagger                                        | `composer.json`, `config/l5-swagger.php`                                                      |
| 1     | Base classes: `BaseApiController` + `BaseApiResource`               | `app/Http/Controllers/Api/V1/BaseApiController.php`, `app/Http/Resources/BaseApiResource.php` |
| 2     | Route extraction to versioned sub-files                             | `routes/api.php`, `routes/api/v1/auth.php`, `users.php`, `admin.php`                          |
| 3     | Rate limiters: `api-authenticated`, `api-public`, `api-admin`       | `AppServiceProvider.php`, `app/Exceptions/Handler.php`                                        |
| 4     | Health check: `HealthController` + `HEALTH_CHECK_FAILED` enum value | `app/Http/Controllers/Api/HealthController.php`, `app/Enums/ApiErrorCode.php`                 |
| 5     | CORS: `config/cors.php` + boot-time wildcard guard                  | `config/cors.php`, `AppServiceProvider.php`                                                   |
| 6     | OpenAPI: annotations + `config/l5-swagger.php` tuning               | `app/Http/Controllers/Api/OpenApiAnnotations.php`                                             |
| 7     | Tests                                                               | `tests/Feature/Api/`, `tests/Unit/Http/`                                                      |

---

## Guardian Verdicts

| Guardian              | Verdict  | Key Notes                                                                                                                                                                                                                                                                         |
| --------------------- | -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Architecture Guardian | **PASS** | 3 issues found and fixed: (1) used `HEALTH_CHECK_FAILED` instead of `SERVER_ERROR` on 503; (2) added `Retry-After` header forwarding in `Handler.php`; (3) added boot-time CORS wildcard guard in `AppServiceProvider`                                                            |
| API Designer          | **PASS** | 3 issues found and fixed: (1) dead ternary removed from HealthController; (2) `data: null` on 503 (probe data → `error.details`); (3) route path mismatch verified as non-issue (spec.md FR-002 already correct); advisory A3 (operator precedence in `api-admin` key) also fixed |

---

## Risk Assessment

| Risk Level | Count | Details                                                                                                                                                                                                                                                                                   |
| ---------- | ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| HIGH       | 2     | (1) Existing Resources migrate parent from `JsonResource` to `BaseApiResource` — if `$wrap` changes break any response deserialization in existing tests, regressions will appear; (2) `api.php` refactored to `require` sub-files — any misconfigured require path silently drops routes |
| MEDIUM     | 2     | (1) `handles_credentials = true` in CORS with wildcard origins is a runtime error in browsers — must be caught by boot-time guard; (2) `HEALTH_CHECK_FAILED` is a new enum value — must be added to `ApiErrorCode.php` before `HealthController` is created                               |
| LOW        | 1     | l5-swagger config publish is idempotent on repeated runs; document generation on first request may be slow (mitigated by `generate_always=false` in production)                                                                                                                           |

---

## Files to Create

| File                                                                   | Description                                                               |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| `backend/app/Http/Controllers/Api/V1/BaseApiController.php`            | V1 base controller with `paginated()`                                     |
| `backend/app/Http/Controllers/Api/HealthController.php`                | Health check, inline probes, HEALTH_CHECK_FAILED on 503                   |
| `backend/app/Http/Controllers/Api/OpenApiAnnotations.php`              | Non-routable OpenAPI `@OA\Info`, `@OA\Server`, `@OA\SecurityScheme`       |
| `backend/app/Http/Resources/BaseApiResource.php`                       | `$wrap = 'data'`, `toArray()` default, `withResponse()`                   |
| `backend/config/cors.php`                                              | CORS config with env-driven `allowed_origins`, `X-Correlation-ID` exposed |
| `backend/routes/api/v1/auth.php`                                       | Auth routes extracted from `api.php`                                      |
| `backend/routes/api/v1/users.php`                                      | User routes extracted from `api.php`                                      |
| `backend/routes/api/v1/admin.php`                                      | Admin routes extracted from `api.php`                                     |
| `backend/contracts/api-health-response.json`                           | JSON Schema for health endpoint responses                                 |
| `backend/tests/Feature/Api/HealthCheckTest.php`                        | Feature tests: 200, 503, excludes auth                                    |
| `backend/tests/Feature/Api/RateLimitTest.php`                          | Feature tests: 429 with Retry-After headers                               |
| `backend/tests/Feature/Api/CorsTest.php`                               | Feature tests: allowed origins, credentials, exposed headers              |
| `backend/tests/Unit/Http/Controllers/Api/V1/BaseApiControllerTest.php` | Unit: paginated() shape                                                   |
| `backend/tests/Unit/Http/Resources/BaseApiResourceTest.php`            | Unit: $wrap = 'data', toArray()                                           |

## Files to Modify

| File                                                | Change                                                                      |
| --------------------------------------------------- | --------------------------------------------------------------------------- |
| `backend/app/Providers/AppServiceProvider.php`      | Add 3 `RateLimiter::for()` entries + boot-time CORS wildcard guard          |
| `backend/app/Exceptions/Handler.php`                | Forward `Retry-After` headers in `handleRateLimitException` (or equivalent) |
| `backend/app/Enums/ApiErrorCode.php`                | Add `HEALTH_CHECK_FAILED` enum case                                         |
| `backend/app/Http/Resources/UserResource.php`       | Change parent to `BaseApiResource`                                          |
| `backend/app/Http/Resources/RoleResource.php`       | Change parent to `BaseApiResource`                                          |
| `backend/app/Http/Resources/PermissionResource.php` | Change parent to `BaseApiResource`                                          |
| `backend/app/Http/Resources/UserRoleResource.php`   | Change parent to `BaseApiResource`                                          |
| `backend/routes/api.php`                            | Add health route, replace inline routes with `require()` directives         |
| `backend/composer.json`                             | Add `darkaonline/l5-swagger:~9.0`                                           |
| `backend/config/l5-swagger.php`                     | Tune annotation path, Swagger UI route, `generate_always` env binding       |
