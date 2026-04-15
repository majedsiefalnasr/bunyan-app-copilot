# Security Requirements Quality Checklist — STAGE_06: API Foundation

> **Spec:** `specs/runtime/006-api-foundation/spec.md`
> **Date:** 2026-04-14
> **Stage:** API Foundation (01_PLATFORM_FOUNDATION)
> **Purpose:** Validate that security requirements are fully, precisely, and unambiguously specified — not that the implementation is correct.

---

## Rate Limiting Bypass Prevention

- [ ] **CHK001** [RATE-LIMIT] Is there a requirement that the `api-public` rate limiter's IP-keyed lookup is protected against `X-Forwarded-For` header spoofing by restricting proxy trust to a known set of trusted proxies (via `TrustProxies` middleware configuration)?
- [ ] **CHK002** [RATE-LIMIT] Is the fallback behavior for `api-authenticated` (user ID → IP) when the user is unauthenticated explicitly specified as a formal requirement, not merely implied by CLR-02?
- [ ] **CHK003** [RATE-LIMIT] Is there a requirement that named rate limiter cache keys are prefixed/namespaced (e.g., `rl:api-authenticated:{user_id}`) to prevent collision with other application cache entries?
- [ ] **CHK004** [RATE-LIMIT] Is the fail-open behavior (NFR-011) explicitly documented as a security trade-off decision — specifically, who approved allowing all requests to pass through when the rate limiter cache is unavailable?
- [ ] **CHK005** [RATE-LIMIT] Is there a requirement that rate limit abuse events (hits at 80%+ of limit threshold) are logged at `warning` level in addition to the full-block event, enabling proactive monitoring before service disruption?

---

## CORS Misconfiguration Risks

- [ ] **CHK006** [CORS] Is there a requirement that `CORS_ALLOWED_ORIGINS=*` triggers a startup-time configuration exception (or at minimum a logged critical warning) when `APP_ENV` is `staging` or `production`, beyond the NFR-005 documentation note?
- [ ] **CHK007** [CORS] Is there a requirement explicitly forbidding the combination of `allowed_origins: ['*']` with `supports_credentials: true`, which produces an invalid CORS configuration that browsers reject and could be misconfigured silently?
- [ ] **CHK008** [CORS] Is there a requirement that `.env.example` documents the correct format and permitted values for `CORS_ALLOWED_ORIGINS`, including that wildcard is only valid for local development?
- [ ] **CHK009** [CORS] Is there a requirement that `allowed_headers` does NOT include a wildcard (`*`) when `supports_credentials: true` is set, since credentials + wildcard headers is also an invalid CORS combination?

---

## Correlation ID Header Injection Risks

- [ ] **CHK010** [CORRELATION-ID] Is the UUID v4 validation rule for incoming `X-Correlation-ID` specified as a formal requirement (not just described as "already implemented"), including the exact rejection behavior (regenerate silently)?
- [ ] **CHK011** [CORRELATION-ID] Is there a requirement that only the server-generated (validated) correlation ID — never the raw client-supplied value — is written to log entries, to prevent log injection attacks via crafted header values?
- [ ] **CHK012** [CORRELATION-ID] Is a maximum byte length defined for the incoming `X-Correlation-ID` header value to prevent memory exhaustion or log buffer flooding through excessively long header strings?
- [ ] **CHK013** [CORRELATION-ID] Is there a requirement that `X-Correlation-ID` is listed in `Access-Control-Allow-Headers` (FR-030 covers request exposure) AND verified to be stripped/regenerated when the value fails UUID v4 validation before being reflected in `Access-Control-Expose-Headers`?

---

## Health Endpoint Information Disclosure

- [ ] **CHK014** [HEALTH] Is there a requirement that explicitly enumerates which fields and data MUST NOT appear in the `/api/health` response (e.g., database host/port, connection string parameters, `APP_KEY`, internal service IPs, PHP version)?
- [ ] **CHK015** [HEALTH] Is the inclusion of `environment` (`local|staging|production`) in the health check response formally reviewed and documented as an intentional, acceptable public disclosure of deployment environment?
- [ ] **CHK016** [HEALTH] Is there a requirement that exception messages and stack traces from failed DB or cache probes are caught internally, logged server-side, and NEVER surfaced in the `checks` object or any health response field?
- [ ] **CHK017** [HEALTH] Is there a requirement that the `version` field in the health check response is sourced from a controlled config value (not from a runtime reflection of `composer.json` or similar file that could expose internal dependency versions)?

---

## OpenAPI Documentation Endpoint Exposure in Production

- [ ] **CHK018** [API-DOCS] Is the decision to keep `/api/documentation` publicly accessible in all environments (including production) formally documented as a security trade-off with an explicit sign-off, rather than left as an unreviewed assumption in the spec?
- [ ] **CHK019** [API-DOCS] Is there a requirement defining whether `/api/documentation` should be protected by any access control mechanism in production (e.g., `APP_ENV` gate, IP allowlist, HTTP Basic Auth, or a dedicated feature flag env var)?
- [ ] **CHK020** [API-DOCS] Is there a requirement that the generated OpenAPI JSON file (`/api/documentation.json`) does not include internal server paths, exception types, or sensitive field names that would aid enumeration or reconnaissance attacks?
- [ ] **CHK021** [API-DOCS] Is there a requirement that l5-swagger's error output (e.g., annotation parse failures) does not expose file paths or class names to the HTTP response when accessed in production?

---

## Auth Token Exposure in Request Logs

- [ ] **CHK022** [LOGGING] Is there a requirement that `RequestResponseLoggingMiddleware` MUST mask or omit the `Authorization` header value (replacing it with `[REDACTED]`) in all logged request entries?
- [ ] **CHK023** [LOGGING] Is there a requirement specifying the full list of headers that MUST be masked in logged requests — including at minimum: `Authorization`, `Cookie`, `Set-Cookie`, and `X-API-Key`?
- [ ] **CHK024** [LOGGING] Is there a requirement that request body fields containing sensitive data (e.g., `password`, `password_confirmation`, `token`, `secret`, `api_key`) are masked with `[REDACTED]` before any logging occurs?
- [ ] **CHK025** [LOGGING] Is there a requirement that response body logging is either disabled or strictly size-capped to prevent accidental logging of large sensitive payloads (e.g., bulk export responses containing PII)?

---

## RBAC Enforcement on All Non-Public Routes

- [ ] **CHK026** [RBAC] Is there a requirement that any route in `routes/api/user.php` or `routes/api/admin.php` that is missing required auth/role middleware causes a failing test (rather than silently returning 200)?
- [ ] **CHK027** [RBAC] Is there a requirement that the `['auth:sanctum', 'role:admin']` middleware stack is applied at the route group level (not per-route) for all of `routes/api/admin.php`, making it structurally impossible to add an unprotected admin route?
- [ ] **CHK028** [RBAC] Is there a route inventory assertion (automated test or script) that verifies every route in the system has an explicit middleware assignment, with no route defaulting to "no auth"?
- [ ] **CHK029** [RBAC] Is there a requirement that the `role:admin` middleware check returns `403 AUTH_UNAUTHORIZED` (not `401 AUTH_TOKEN_EXPIRED`) when the user is authenticated but lacks the admin role, following the error code registry?

---

## Laravel Sanctum Session Security

- [ ] **CHK030** [SANCTUM] Is there a requirement that the `stateful` domains in `config/sanctum.php` are restricted to match `CORS_ALLOWED_ORIGINS` and are not left as Laravel's default permissive pattern that could allow unintended SPA origins?
- [ ] **CHK031** [SANCTUM] Is there a requirement that Sanctum API tokens are revoked (deleted from `personal_access_tokens`) upon user logout rather than merely ignoring them?
- [ ] **CHK032** [SANCTUM] Is the separation between Sanctum SPA authentication (cookie-based) and API token authentication (Bearer token) formally documented in route or middleware grouping to prevent accidental stateful session usage on token-auth routes?
- [ ] **CHK033** [SANCTUM] Is there a requirement that Sanctum token abilities/scopes are defined for this stage, or is explicitly documented that token abilities are deferred to a future stage with a reference to the deferral ADR?
