# Data Model: API Foundation (STAGE_06)

**Branch:** `spec/006-api-foundation`
**Date:** 2026-04-14

---

## Database Changes

**None.** This stage introduces no new database tables, no schema changes, and no migrations.

All new concepts in this stage (base classes, rate limiting, CORS, health checks, Swagger) are either code-only or use transient storage (Redis).

---

## Redis Key Patterns

Rate limiters use Laravel's cache layer (keyed by `CACHE_DRIVER=redis` in production). Laravel stores rate limiter counters under the configured `CACHE_PREFIX` (default: `laravel_cache`).

### Named Rate Limiter Key Patterns

| Limiter Name                   | Key Pattern                       | TTL | Notes                                |
| ------------------------------ | --------------------------------- | --- | ------------------------------------ |
| `api-authenticated`            | `{prefix}:limiter:user:{userId}`  | 60s | Authenticated users keyed by user ID |
| `api-authenticated` (fallback) | `{prefix}:limiter:ip:{ipAddress}` | 60s | Unauthenticated requests keyed by IP |
| `api-public`                   | `{prefix}:limiter:ip:{ipAddress}` | 60s | Public routes keyed by IP            |
| `api-admin`                    | `{prefix}:limiter:admin:{userId}` | 60s | Admin routes keyed by user ID        |

_Note: All existing limiters (`api`, `auth-login`, `auth-register`, `auth-forgot-password`, `auth-email-resend`, `user-avatar-upload`) remain unchanged._

### Health Check Probe Cache Key

The health check writes a transient probe value to verify cache connectivity:

| Key                               | Value | TTL | Notes                                                      |
| --------------------------------- | ----- | --- | ---------------------------------------------------------- |
| `{prefix}:_health_probe_{uniqid}` | `1`   | 5s  | Written and immediately deleted; confirms cache read/write |

---

## Environment Variables Added

| Variable                     | Default                 | Required         | Notes                                                                               |
| ---------------------------- | ----------------------- | ---------------- | ----------------------------------------------------------------------------------- |
| `CORS_ALLOWED_ORIGINS`       | `http://localhost:3000` | Yes (production) | Comma-separated origins; must be explicit (no `*`) with `supports_credentials=true` |
| `L5_SWAGGER_GENERATE_ALWAYS` | `false`                 | No               | Set `true` in local dev for auto-regeneration                                       |

---

## Configuration Files Added

| File                            | Status                                     |
| ------------------------------- | ------------------------------------------ |
| `backend/config/cors.php`       | New — created manually (no vendor:publish) |
| `backend/config/l5-swagger.php` | New — published via `vendor:publish`       |

---

## No Migrations Required

This stage is confirmed migration-free. The implementation task runner should skip migration validation steps for STAGE_06.
