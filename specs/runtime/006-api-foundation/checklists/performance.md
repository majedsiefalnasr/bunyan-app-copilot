# Performance Requirements Quality Checklist — STAGE_06: API Foundation

> **Spec:** `specs/runtime/006-api-foundation/spec.md`
> **Date:** 2026-04-14
> **Stage:** API Foundation (01_PLATFORM_FOUNDATION)
> **Purpose:** Validate that performance requirements are measurable, bounded, and implementable — not that the implementation is fast.

---

## Rate Limiter Backend (Redis, Not Database)

- [ ] **CHK034** [RATE-LIMIT-PERF] Is there a requirement that explicitly mandates the use of a non-database cache driver (e.g., `CACHE_DRIVER=redis` or `array` in testing) for rate limiter storage in staging and production environments, beyond the general NFR-004 statement?
- [ ] **CHK035** [RATE-LIMIT-PERF] Is NFR-004 ("rate limiter lookups MUST use the cache driver, not the database") testable through an automated assertion — e.g., a test that asserts zero SQL queries are executed during rate limit evaluation for `api-authenticated` and `api-public`?
- [ ] **CHK036** [RATE-LIMIT-PERF] Is there a requirement specifying the Redis key TTL strategy for rate limiter entries — i.e., that TTL is set to the window duration (60s or 1min) so Redis memory usage is automatically bounded without manual eviction?
- [ ] **CHK037** [RATE-LIMIT-PERF] Is there a requirement specifying which Redis database index (0–15) or key namespace is used for rate limiter keys, to prevent cross-contamination with session or cache keys in shared Redis instances?

---

## Health Check Response Time (< 200ms)

- [ ] **CHK038** [HEALTH-PERF] Is NFR-001 (health check must respond within 200ms) expressed as a testable assertion — e.g., a PHP test that fails if the health endpoint response time exceeds the threshold — rather than only a documentation target?
- [ ] **CHK039** [HEALTH-PERF] Is there a requirement that the DB probe (`DB::select('SELECT 1')`) and cache probe (`Cache::put/get`) each have an explicit connection timeout (e.g., 100ms max) to prevent a single slow dependency from causing the health endpoint itself to hang beyond 200ms?
- [ ] **CHK040** [HEALTH-PERF] Is it specified whether the DB check and cache check within `HealthController` execute sequentially or in parallel, given the 200ms total budget with two potentially slow I/O operations?
- [ ] **CHK041** [HEALTH-PERF] Is there a requirement that the `GET /api/health` endpoint bypasses any caching layer (e.g., HTTP caches, response caches) to ensure every probe reflects the current system state, not a stale snapshot?

---

## Request/Response Logging Performance Impact

- [ ] **CHK042** [LOGGING-PERF] Is there a requirement that `RequestResponseLoggingMiddleware` uses a non-blocking logging mechanism (e.g., Laravel's `stack` channel backed by an async driver, or a queued log handler) to prevent log I/O from adding latency to user-facing request processing?
- [ ] **CHK043** [LOGGING-PERF] Is there a performance overhead budget defined for `RequestResponseLoggingMiddleware` (analogous to the 2ms budget for `CorrelationIdMiddleware` in NFR-003) quantifying the maximum acceptable per-request overhead?
- [ ] **CHK044** [LOGGING-PERF] Is there a requirement that caps the maximum captured request/response body size in log entries (e.g., 4KB) to prevent large payloads from causing memory pressure or excessive disk I/O during logging?
- [ ] **CHK045** [LOGGING-PERF] Is there a requirement that the path-exclusion list (`health`, `metrics`, `ping`, `status`) for `RequestResponseLoggingMiddleware` is evaluated before any body capture or serialization to avoid unnecessary allocation even for exempted paths?

---

## OpenAPI Documentation Generation Caching

- [ ] **CHK046** [SWAGGER-PERF] Is there a requirement specifying that `php artisan l5-swagger:generate` runs at deployment time (as part of CI/CD or release scripts) rather than lazily on first request, to eliminate cold-start latency for the first visitor of `/api/documentation`?
- [ ] **CHK047** [SWAGGER-PERF] Is the generated OpenAPI JSON file stored at a defined path (`storage/api-docs/api-docs.json`) treated as a static file served by the web server (bypassing PHP) or served via Laravel's response routing? The requirement for which approach is used should be documented.
- [ ] **CHK048** [SWAGGER-PERF] Is there a requirement defining cache invalidation behavior for the generated OpenAPI JSON — i.e., under what conditions (new deployment, annotation change, manual trigger) the file is regenerated to prevent serving stale documentation?

---

## CORS Preflight Caching (Access-Control-Max-Age)

- [ ] **CHK049** [CORS-PERF] Is FR-033 (`max_age: 86400`) verified to be present in the actual CORS preflight response `Access-Control-Max-Age` header via an automated feature test, not only set in `config/cors.php`?
- [ ] **CHK050** [CORS-PERF] Is the 86400-second (24-hour) preflight cache duration documented with its browser compatibility caveats — specifically that Chrome enforces a 7200-second cap and Firefox enforces 86400 — so implementers understand the effective caching behavior per browser?
