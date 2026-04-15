# PR — API Foundation

## Summary

**Stage:** API Foundation
**Phase:** 01_PLATFORM_FOUNDATION
**Branch:** `spec/006-api-foundation` → `develop`
**Tasks:** 34 / 34 completed

This PR establishes the complete API foundation for the Bunyan platform: versioned route architecture, base controller/resource classes with standardised response envelopes, named rate limiters, environment-driven CORS configuration, a `/api/health` probe endpoint, and an OpenAPI 3.0 scaffold via l5-swagger.

## What Changed

### Backend

- **`BaseApiController`** — new abstract controller with `paginated()` method returning `{success, data, meta, error}` 4-key envelope with pagination metadata
- **`abstract BaseApiResource`** — new abstract JSON resource with `$wrap='data'`, `paginatedCollection()` helper, and `X-Correlation-ID` header propagation
- **`UserResource`, `RoleResource`, `PermissionResource`, `UserRoleResource`** — updated to extend `BaseApiResource`
- **Route split** — `routes/api.php` now requires `routes/api/v1/{auth,users,admin}.php` sub-files (all existing URIs preserved unchanged)
- **`/api/health`** — new unauthenticated, unthrottled health probe with DB and cache checks; returns 200 `{status: healthy}` or 503 `HEALTH_CHECK_FAILED` error
- **Rate limiters** — `api-authenticated` (60/min), `api-public` (10/min/IP), `api-admin` (300/min) registered in `AppServiceProvider`
- **Rate limit headers** — `Handler.php` forwards `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining` on 429 responses
- **`config/cors.php`** — env-driven CORS; `CORS_ALLOWED_ORIGINS` supports comma-separated origins; `X-Correlation-ID` exposed; CORS guard throws on wildcard+credentials in non-local env
- **`ApiErrorCode::HEALTH_CHECK_FAILED`** — new enum case; `lang/en/errors.php` and `lang/ar/errors.php` updated
- **OpenAPI scaffold** — `OpenApiAnnotations.php` with `@OA\Info`, `@OA\Server`, `@OA\SecurityScheme`; `config/l5-swagger.php` updated

### Frontend

No frontend changes.

### Database

No migrations added.

## New Test Files

| File                                                           | Coverage                           |
| -------------------------------------------------------------- | ---------------------------------- |
| `tests/Unit/Http/Controllers/Api/V1/BaseApiControllerTest.php` | paginated() envelope contract      |
| `tests/Unit/Http/Resources/BaseApiResourceTest.php`            | wrap, toArray, paginatedCollection |
| `tests/Feature/Api/HealthCheckTest.php`                        | AC-01–03, AC-07                    |
| `tests/Feature/Api/RateLimitTest.php`                          | AC-08–10                           |
| `tests/Feature/Api/CorsTest.php`                               | AC-11–12                           |
| `tests/Feature/Api/SwaggerTest.php`                            | AC-13                              |

**Test results:** 333 passed · 0 failed · 2045 assertions

## Breaking Changes

None. All existing API endpoints are unchanged. The `$wrap='data'` on `BaseApiResource` was applied uniformly — existing tests were audited (T006) and updated where needed.

## Testing

- [x] Unit tests pass (`php artisan test --testsuite=Unit`) — 333 total
- [x] Feature tests pass (`php artisan test --testsuite=Feature`) — 333 total
- [x] Frontend tests pass — N/A (no frontend changes)
- [x] Lint passes (`composer run lint`) — 164 files, 0 violations
- [x] PHPStan passes (`composer run lint`) — level 6, 164 files, 0 errors
- [x] Migration pretend — N/A (no migrations)

## Checklist

- [x] RBAC middleware applied on all new routes — `/api/health` is intentionally public; all `/api/v1/*` routes retain `auth:sanctum`
- [x] Form Request validation — no new input-accepting endpoints added
- [x] Arabic/RTL support — `health_check_failed` key in `lang/ar/errors.php`
- [x] Error contract followed — all responses use `{success, data, error}` with proper codes
- [x] No N+1 queries — no ORM queries in this stage
- [x] API documentation — OpenAPI scaffold in place; `l5-swagger:generate` must run at deploy
- [x] Migration tested — N/A

## Notable Implementation Notes

1. `BaseApiResource` is declared `abstract` — it contains `abstract toArray()`, which PHP requires be in an abstract class.
2. `ThrottleRequests::shouldHashKeys(false)` used in `RateLimitTest` to make cache keys predictable.
3. `CorsTest` uses `$response->getStatusCode()` (not `->status()`) for OPTIONS preflight — middleware resolves before route matching.
4. Real `LengthAwarePaginator` instances used in `BaseApiResourceTest` — PHPUnit 12 forbids mocking `getIterator()`.

## Related

- Stage File: `specs/phases/01_PLATFORM_FOUNDATION/STAGE_06_API_FOUNDATION.md`
- Testing Guide: `specs/runtime/006-api-foundation/guides/TESTING_GUIDE.md`
- Closure Report: `specs/runtime/006-api-foundation/reports/CLOSURE_REPORT.md`
- Implementation Report: `specs/runtime/006-api-foundation/reports/IMPLEMENT_REPORT.md`
