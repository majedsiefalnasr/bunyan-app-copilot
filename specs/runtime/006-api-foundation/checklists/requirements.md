# Requirements Quality Checklist — STAGE_06: API Foundation

> **Spec:** `specs/runtime/006-api-foundation/spec.md`
> **Date:** 2026-04-14
> **Reviewer:** speckit.specify

---

## 1. Completeness

- [x] All functional requirements have acceptance criteria (AC-01 through AC-18 map to FRs)
- [x] All user stories have at least 2 acceptance scenarios
- [x] All user stories have an independent testability description
- [x] All user stories have a priority assignment (P1–P3)
- [x] Non-functional requirements section populated (NFR-001 through NFR-018)
- [x] Out-of-scope section explicitly defined
- [x] Technical constraints section populated (TC-001 through TC-008)
- [x] Assumptions section populated

---

## 2. RBAC Coverage

- [x] Admin routes documented with `['auth:sanctum', 'role:admin']` middleware stack (FR-005)
- [x] Authenticated routes documented with `auth:sanctum` middleware
- [x] Public routes explicitly separated (FR-004)
- [x] `role` and `permission` middleware aliases listed in middleware stack section (FR-018)
- [x] RBAC middleware from STAGE_04 listed as upstream dependency

---

## 3. Error Handling

- [x] Rate limit error response documented: HTTP 429 + `RATE_LIMIT_EXCEEDED` (FR-025, FR-049)
- [x] `Retry-After` header required on rate-limit responses (FR-025)
- [x] All error responses reference the unified error contract from STAGE_05 (FR-048)
- [x] Health check degraded vs unhealthy response codes documented (FR-039)
- [x] `ThrottleRequestsException` handler verification documented (FR-049)
- [x] Swagger UI must not expose stack traces (NFR-007)

---

## 4. Arabic / RTL Support

- [x] Error messages in `success()` / `error()` / `paginated()` must support Arabic and English (FR-050)
- [x] NFR-017 specifies translations must exist in `lang/ar/` and `lang/en/`
- [x] NFR-018 clarifies Arabic RTL is a frontend concern; backend sends localizable strings

---

## 5. Testing Requirements

- [x] Unit tests specified: `BaseApiController` response methods (US2 + AC-04, AC-05, AC-06)
- [x] Unit tests specified: `BaseApiResource` wrapping and pagination (US2 + AC-14, AC-15)
- [x] Feature test specified: `HealthCheckTest` — HTTP 200 healthy, HTTP 503 unhealthy, no-auth (AC-01, AC-02, AC-03)
- [x] Feature test specified: `RateLimitTest` — authenticated 429, public 429 (AC-08, AC-09)
- [x] Feature test specified: `AdminRateLimitTest` — admin higher limit (AC-10)
- [x] Feature test specified: `CorsTest` — preflight headers, exposed headers (AC-11, AC-12)
- [x] Feature test specified: `SwaggerTest` — documentation route (AC-13)
- [x] Regression test: existing resources still work after extending BaseApiResource (AC-16)
- [x] Full suite: `php artisan test --parallel` must pass (AC-18)

---

## 6. Ambiguity Check

- [x] No requirements use vague terms ("should", "might") — all use "MUST" or "MUST NOT"
- [x] Rate limit values are explicit: 60/min authenticated, 10/min public, 300/min admin
- [x] HTTP status codes are explicit for all scenarios
- [x] Health check response body structure is fully specified with example JSON (FR-036)
- [x] CORS env variable name is explicit: `CORS_ALLOWED_ORIGINS`
- [x] Package name and version range is explicit: `darkaonline/l5-swagger ^8.6`
- [x] Swagger documentation URL is explicit: `GET /api/documentation`
- [x] Route naming convention is explicit: `api.v1.[resource].[action]`
- [x] No `[NEEDS CLARIFICATION]` markers remaining in spec

---

## 7. Endpoint Documentation

All endpoints introduced or formalized in this stage:

| HTTP Method | Path                        | Auth Required | Rate Limiter        | Documented |
| ----------- | --------------------------- | ------------- | ------------------- | ---------- |
| GET         | `/api/health`               | No            | None (exempt)       | [x]        |
| GET         | `/api/documentation`        | No            | None                | [x]        |
| GET         | `/api/documentation.json`   | No            | None                | [x]        |
| ALL         | `/api/v1/*` (authenticated) | Yes           | `api-authenticated` | [x]        |
| ALL         | `/api/v1/admin/*`           | Yes + admin   | `api-admin`         | [x]        |
| ALL         | `/api/v1/auth/*` (public)   | No            | `api-public`        | [x]        |

---

## 8. Rate Limiting Rules

- [x] `api-authenticated`: 60 req/min, keyed by user ID (FR-021)
- [x] `api-public`: 10 req/min, keyed by IP (FR-022)
- [x] `api-admin`: 300 req/min, keyed by user ID (FR-023)
- [x] Existing limiters preserved: `api`, `auth-login`, `auth-register`, `auth-forgot-password`, `auth-email-resend`, `user-avatar-upload` (FR-024)
- [x] `GET /api/health` exempt from all rate limiting (FR-026, FR-040)
- [x] Rate limiter failures must fail open (NFR-011)
- [x] `Retry-After` and `X-RateLimit-*` headers required on 429 responses (FR-025)

---

## 9. Dependency Verification

- [x] STAGE_04 (RBAC): `RoleMiddleware`, `PermissionMiddleware` listed as upstream (used in FR-005, FR-018)
- [x] STAGE_05 (Error Handling): `ApiResponseTrait`, `ApiErrorCode`, `CorrelationIdMiddleware`, `RequestResponseLoggingMiddleware` listed as upstream
- [x] Downstream stages listed: all API stages, DevOps (health probe), Frontend (CORS)
- [x] External package dependency documented: `darkaonline/l5-swagger ^8.6`
- [x] Laravel built-in dependencies: `HandleCors`, `RateLimiter` documented

---

## 10. Architecture Compliance

- [x] No business logic in controllers (BaseApiController is helpers-only; HealthController delegates checks)
- [x] Service layer pattern: health check logic in `HealthService` or inline in controller (simple enough to inline — no complex domain logic)
- [x] Repository pattern: N/A for this stage (no domain data access)
- [x] Form Request validation: N/A for this stage (no user input other than health check)
- [x] Middleware stack order matches the canonical order from STAGE_06 stage file
- [x] Backward compatibility with STAGE_03/STAGE_04 controllers enforced (TC-004, TC-008)

---

## 11. Pre-Merge Gate

- [ ] `composer run lint` passes (PHPStan level 6)
- [ ] `php artisan test --parallel` — all tests green
- [ ] `npm run lint && npm run typecheck` — frontend unaffected
- [ ] `GET /api/health` manually verified in local Docker environment
- [ ] `GET /api/documentation` renders Swagger UI
- [ ] CORS preflight verified from `http://localhost:3000`
