# Tasks Report — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2026-04-14T00:06:00Z

## Task Summary

| Metric         | Value |
| -------------- | ----- |
| Total Tasks    | 33    |
| Parallelizable | 13    |
| Sequential     | 20    |
| HIGH Risk      | 4     |
| MEDIUM Risk    | 11    |
| LOW Risk       | 18    |

## Risk-Ranked Task View

### 🔴 HIGH Risk Tasks

| ID   | Description                                                          | Risk Factor                                                     |
| ---- | -------------------------------------------------------------------- | --------------------------------------------------------------- |
| T006 | Audit existing tests for unwrapped field assertions before migration | Missing audit → silent data-shape regressions across test suite |
| T007 | Migrate `UserResource` → `BaseApiResource`                           | `$wrap='data'` change breaks existing response assertions       |
| T014 | Update `routes/api.php` to require sub-files + register health route | Bad `require` path silently drops all routes in a group         |
| T033 | Full regression test run (`php artisan test --parallel`)             | Validates no cross-stage breakage; blocks deploy if fails       |

### 🟡 MEDIUM Risk Tasks

| ID   | Description                                                      | Risk Factor                                                         |
| ---- | ---------------------------------------------------------------- | ------------------------------------------------------------------- |
| T005 | Create `BaseApiResource` with `$wrap = 'data'`                   | New `$wrap` value affects all API resource responses globally       |
| T008 | Migrate `RoleResource` → `BaseApiResource`                       | Same `$wrap` regression risk as T007                                |
| T009 | Migrate `PermissionResource` → `BaseApiResource`                 | Same `$wrap` regression risk as T007                                |
| T010 | Migrate `UserRoleResource` → `BaseApiResource`                   | Same `$wrap` regression risk as T007                                |
| T015 | Register 3 named rate limiters in `AppServiceProvider::boot()`   | Wrong key format could rate-limit wrong users/IPs                   |
| T016 | Fix `Handler.php` 429 to forward `Retry-After` / `X-RateLimit-*` | Incorrect header forwarding fails OAuth clients                     |
| T017 | Add `HEALTH_CHECK_FAILED` to `ApiErrorCode` enum                 | Must exist before `HealthController` is created                     |
| T018 | Create `HealthController` with inline DB + Redis probes          | Failing health check returns incorrect contract shape               |
| T024 | Boot-time CORS wildcard guard in `AppServiceProvider`            | Missing guard → credentials + wildcard → CORS browser-block in prod |
| T025 | Create `OpenApiAnnotations.php` (non-routable)                   | Incorrect `@OA\Info` blocks swagger doc generation                  |
| T026 | Tune `config/l5-swagger.php` (annotations path, generate_always) | Wrong scan path → empty OpenAPI spec                                |

### 🟢 LOW Risk Tasks

| ID   | Description                                             | Risk Factor                                       |
| ---- | ------------------------------------------------------- | ------------------------------------------------- |
| T001 | `composer require darkaonline/l5-swagger`               | Idempotent; lockfile-controlled                   |
| T002 | Publish L5Swagger vendor config + views                 | Idempotent on re-publish                          |
| T003 | Add `L5_SWAGGER_GENERATE_ALWAYS` to `.env.example`      | Documentation only; no runtime impact             |
| T004 | Create `BaseApiController` with `paginated()`           | New class; no existing code modified              |
| T011 | Extract auth routes to `routes/api/v1/auth.php`         | Low risk if T014 preserves route names            |
| T012 | Extract user routes to `routes/api/v1/users.php`        | Low risk if T014 preserves route names            |
| T013 | Extract admin routes to `routes/api/v1/admin.php`       | Low risk if T014 preserves route names            |
| T019 | Add health path to logging middleware skip-list         | Non-breaking additive config change               |
| T020 | Add `health_check_failed` English translation key       | Additive; no existing keys modified               |
| T021 | Add `health_check_failed` Arabic translation key        | Additive; no existing keys modified               |
| T022 | Create `config/cors.php`                                | New file; `HandleCors` reads it only when present |
| T023 | Add `CORS_ALLOWED_ORIGINS` to `.env.example`            | Documentation only                                |
| T027 | Add `@OA\Get` annotation to `HealthController::check()` | Annotation-only; no implementation logic          |
| T028 | Unit test `BaseApiController`                           | New test file; no existing tests touched          |
| T029 | Unit test `BaseApiResource`                             | New test file; no existing tests touched          |
| T030 | Feature test `GET /api/health`                          | New test file                                     |
| T031 | Feature test rate limiting (429 + headers)              | New test file                                     |
| T032 | Feature test CORS contracts                             | New test file                                     |

---

## External Dependencies

| Task ID | Package/Library          | Version | Purpose                                                          |
| ------- | ------------------------ | ------- | ---------------------------------------------------------------- |
| T001    | `darkaonline/l5-swagger` | `~9.0`  | OpenAPI 3.0 documentation scaffold                               |
| T001    | `zircote/swagger-php`    | `^4.x`  | Pulled as dependency of l5-swagger; provides `@OA\*` annotations |

---

## High-Downstream-Impact Tasks

| Task ID | Description                                   | Downstream Impact                                                |
| ------- | --------------------------------------------- | ---------------------------------------------------------------- |
| T005    | Create `BaseApiResource`                      | All resource migration tasks (T007–T010) depend on this          |
| T006    | Audit existing tests for unwrapped assertions | Determines which assertions in T007–T010 need fixing             |
| T017    | Add `HEALTH_CHECK_FAILED` to enum             | `HealthController` will throw at runtime if enum case is missing |
| T014    | Update `routes/api.php`                       | All route extraction work (T011–T013) is wasted if this fails    |
| T033    | Full test regression                          | Final gate before Step 5 (Analyze) can begin                     |

---

## Parallel Execution Groups

| Group | Tasks                        | Dependency Gate                                                  |
| ----- | ---------------------------- | ---------------------------------------------------------------- |
| G1    | T011, T012, T013             | After Phase 1 complete                                           |
| G2    | T007, T008, T009, T010       | After T005 + T006 complete                                       |
| G3    | T015, T022, T023, T024       | After Phase 1 complete (T015 can even run in parallel with T022) |
| G4    | T028, T029, T030, T031, T032 | After all corresponding implementation tasks complete            |
