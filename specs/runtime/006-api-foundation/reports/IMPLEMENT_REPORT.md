# Implement Report — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2026-04-14T13:00:00Z

## Implementation Summary

| Metric           | Value                                                                                                                |
| ---------------- | -------------------------------------------------------------------------------------------------------------------- |
| Tasks Completed  | 34 / 34                                                                                                              |
| Files Created    | 17                                                                                                                   |
| Files Modified   | 12                                                                                                                   |
| Migrations Added | 0                                                                                                                    |
| Tests Written    | 6 new test files (HealthCheckTest, RateLimitTest, CorsTest, SwaggerTest, BaseApiControllerTest, BaseApiResourceTest) |
| Deferred Tasks   | 0                                                                                                                    |

### Files Created

| File                                                                   | Task |
| ---------------------------------------------------------------------- | ---- |
| `backend/app/Http/Controllers/Api/V1/BaseApiController.php`            | T004 |
| `backend/app/Http/Resources/BaseApiResource.php`                       | T005 |
| `backend/app/Http/Controllers/Api/HealthController.php`                | T018 |
| `backend/app/Http/Controllers/Api/OpenApiAnnotations.php`              | T025 |
| `backend/config/cors.php`                                              | T022 |
| `backend/routes/api/v1/auth.php`                                       | T011 |
| `backend/routes/api/v1/users.php`                                      | T012 |
| `backend/routes/api/v1/admin.php`                                      | T013 |
| `backend/tests/Feature/Api/HealthCheckTest.php`                        | T030 |
| `backend/tests/Feature/Api/RateLimitTest.php`                          | T031 |
| `backend/tests/Feature/Api/CorsTest.php`                               | T032 |
| `backend/tests/Feature/Api/SwaggerTest.php`                            | T033 |
| `backend/tests/Unit/Http/Controllers/Api/V1/BaseApiControllerTest.php` | T028 |
| `backend/tests/Unit/Http/Resources/BaseApiResourceTest.php`            | T029 |
| `backend/lang/en/errors.php` (health_check_failed key)                 | T020 |
| `backend/lang/ar/errors.php` (health_check_failed key)                 | T021 |

### Files Modified

| File                                                               | Task(s)               |
| ------------------------------------------------------------------ | --------------------- |
| `backend/config/l5-swagger.php`                                    | T002, T026            |
| `backend/.env.example`                                             | T003, T023            |
| `backend/routes/api.php`                                           | T014                  |
| `backend/app/Providers/AppServiceProvider.php`                     | T015, T024            |
| `backend/app/Exceptions/Handler.php`                               | T016                  |
| `backend/app/Enums/ApiErrorCode.php`                               | T017                  |
| `backend/app/Http/Resources/UserResource.php`                      | T007                  |
| `backend/app/Http/Resources/RoleResource.php`                      | T008                  |
| `backend/app/Http/Resources/PermissionResource.php`                | T009                  |
| `backend/app/Http/Resources/UserRoleResource.php`                  | T010                  |
| `backend/app/Http/Middleware/RequestResponseLoggingMiddleware.php` | T019                  |
| `backend/tests/Unit/Enums/ApiErrorCodeTest.php`                    | T034 (regression fix) |
| `backend/tests/Feature/Api/ErrorResponseContractTest.php`          | T034 (regression fix) |

## Validation Results

| Check             | Status | Output                                            |
| ----------------- | ------ | ------------------------------------------------- |
| PHPUnit (Total)   | ✅     | 333 passed, 0 failed, 0 skipped (2045 assertions) |
| Vitest            | N/A    | No frontend changes this stage                    |
| Laravel Pint      | ✅     | 164 files — 0 violations                          |
| PHPStan           | ✅     | Level 6 — 164 files — 0 errors                    |
| ESLint            | N/A    | No frontend changes this stage                    |
| Migration Pretend | N/A    | No migrations added this stage                    |

## Guardian Verdicts (pre-closure)

| Guardian              | Verdict | Notes                                                                                                            |
| --------------------- | ------- | ---------------------------------------------------------------------------------------------------------------- |
| GitHub Actions Expert | PASS    | CI config not modified; existing pipeline compatible                                                             |
| DevOps Engineer       | PASS    | config/cors.php env-driven; generate_always=false in prod                                                        |
| Security Auditor      | PASS    | CORS guard throws on `*`+credentials in non-local env; rate limiters applied; health endpoint excluded from auth |

## Notable Implementation Decisions

1. **`abstract class BaseApiResource`** — declared `abstract` (not concrete) because it contains `abstract toArray()`. PHP will not compile a non-abstract class with abstract methods.
2. **`ThrottleRequests::shouldHashKeys(false)`** — applied in `RateLimitTest::setUp()` to prevent MD5 hash of cache keys, which would make key-based assertions unpredictable. Restored in `tearDown()`.
3. **`$response->getStatusCode()` in CorsTest** — CORS middleware intercepts `OPTIONS` preflight before route matching, returning a raw Symfony `Response`. The testable object is not a wrapped `TestResponse`, so `status()` is unavailable; `getStatusCode()` is the correct call.
4. **Real `LengthAwarePaginator` in BaseApiResourceTest** — PHPUnit 12 forbids mocking `getIterator()` on the interface with `MethodCannotBeConfiguredException`. Fixed by constructing real paginator instances.
5. **`HEALTH_CHECK_FAILED` enum regression** — adding the new case bumped the total to 13, breaking 2 existing tests with hard-coded counts. Both updated in T034.

## Deferred Tasks

| Task ID | Description | Reason |
| ------- | ----------- | ------ |
| None    | —           | —      |
