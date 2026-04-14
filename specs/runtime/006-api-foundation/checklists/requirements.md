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

---

## 12. Security Requirements (Derived from `checklists/security.md`)

The following security requirements were identified through the security checklist review and represent gaps or implicit assumptions in the existing FRs/NFRs. These are additional constraints for the implementation.

### Rate Limiting Security

- [ ] **SR-001**: The `TrustProxies` middleware MUST be configured with an explicit trusted proxy list before deploying `api-public` (IP-keyed) rate limiting — open proxy trust (`'*'`) MUST NOT be used in staging/production to prevent IP spoofing via `X-Forwarded-For`.
- [ ] **SR-002**: Named rate limiter cache keys MUST use a namespaced prefix format (e.g., `rl:api-authenticated:{user_id}`) to prevent key collision with application cache entries sharing the same Redis/cache store.
- [ ] **SR-003**: The fail-open NFR-011 trade-off (allow requests when rate limit cache is unavailable) MUST be reviewed and formally accepted by the platform owner, with the decision recorded in this spec.

### CORS Security

- [ ] **SR-004**: `CORS_ALLOWED_ORIGINS=*` MUST trigger a critical log warning (or config exception) at application boot when `APP_ENV` is `staging` or `production` — the NFR-005 documentation note alone is insufficient enforcement.
- [ ] **SR-005**: The combination of `supports_credentials: true` with any wildcard in `allowed_origins` or `allowed_headers` MUST be explicitly forbidden in code or documentation, as this produces an invalid and potentially dangerous CORS configuration.

### Request Logging Security

- [ ] **SR-006**: `RequestResponseLoggingMiddleware` MUST mask the `Authorization` header value (replace with `[REDACTED]`) in all logged request entries — this is not currently an explicit FR/NFR.
- [ ] **SR-007**: The masking list for sensitive fields in request body logging MUST include at minimum: `password`, `password_confirmation`, `token`, `secret`, `api_key`. This list MUST be documented in the logging middleware or a config file.
- [ ] **SR-008**: `Cookie` and `Set-Cookie` headers MUST be stripped from logged request and response entries to prevent Sanctum session cookies from appearing in application logs.

### Health Endpoint Security

- [ ] **SR-009**: `/api/health` MUST catch all exceptions from DB and cache probes and log them server-side (at `error` level), returning `false` for the failed check without exposing the exception message or stack trace in the response body.
- [ ] **SR-010**: The `version` field in the health check response MUST be sourced from `config/app.php` or an explicit `APP_VERSION` env var — NEVER from runtime inspection of `composer.json` or any file that could expose internal dependency versions.

### API Documentation Security

- [ ] **SR-011**: The decision to make `/api/documentation` publicly accessible in production MUST be reviewed and formally accepted by the platform owner, with a documented rationale. A future security hardening stage MUST be tracked to add access control if required.

### RBAC Structural Enforcement

- [ ] **SR-012**: All routes in `routes/api/admin.php` MUST be enclosed within a single route group that applies `['auth:sanctum', 'role:admin']` at the group level — individual route-level middleware application for admin routes is NOT permitted, as it creates a structural gap risk.

---

## Checklist Sign-off

> **Status:** Ready for implementation
> **Sign-off Date:** 2026-04-14
> **Checklists generated:** `security.md` (33 items), `performance.md` (17 items), `accessibility.md` (14 items)

| Checklist File     | Items       | Status                                    |
| ------------------ | ----------- | ----------------------------------------- |
| `requirements.md`  | 11 sections | Complete — all sections reviewed          |
| `security.md`      | 33          | Generated — pending implementation review |
| `performance.md`   | 17          | Generated — pending implementation review |
| `accessibility.md` | 14          | Generated — pending implementation review |

**The spec is implementation-ready.** All `[NEEDS CLARIFICATION]` markers are resolved (CLR-01 through CLR-05). The checklists above define the quality gates that MUST be verified as part of implementation, testing, and pre-merge review for STAGE_06.

**Pre-merge requirement:** All items in sections 1–11 of this file marked `[x]` before the PR is merged. Security requirements SR-001 through SR-012 must be addressed during implementation with explicit code evidence (tests, config, middleware). Checklist items CHK001–CHK064 in the domain checklists are requirements quality validators — each open `[ ]` item represents a gap in the spec that must either be resolved via a spec amendment or formally deferred.
