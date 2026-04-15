# Testing Guide — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION > **Generated:** 2026-04-14T14:00:00Z

## Prerequisites

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

Ensure `.env` has:

```
DB_CONNECTION=mysql
CACHE_DRIVER=redis
CORS_ALLOWED_ORIGINS=http://localhost:3000
L5_SWAGGER_GENERATE_ALWAYS=true
```

## Running Tests

### All Backend Tests

```bash
cd backend
php artisan test --parallel
```

Expected: **333 tests, 333 passed, 0 failed**

### Target Specific Suites

```bash
# Health check tests only
php artisan test --filter=HealthCheckTest

# Rate limit tests only
php artisan test --filter=RateLimitTest

# CORS tests only
php artisan test --filter=CorsTest

# Swagger/OpenAPI tests only
php artisan test --filter=SwaggerTest

# Base API Controller unit tests
php artisan test --filter=BaseApiControllerTest

# Base API Resource unit tests
php artisan test --filter=BaseApiResourceTest
```

### Lint + Static Analysis

```bash
cd backend
composer run lint
```

Expected: **164 files — 0 violations (Pint + PHPStan level 6)**

### Generate OpenAPI Docs

```bash
cd backend
php artisan l5-swagger:generate
```

Expected: `storage/api-docs/api-docs.json` updated.

## Manual Test Scenarios

### Scenario 1 — Health Check (Healthy)

**Preconditions:**

- DB and cache connections functional
- App running locally

**Steps:**

1. `curl -X GET http://localhost:8000/api/health`

**Expected Result:**

```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "checks": {
      "database": "ok",
      "cache": "ok"
    }
  },
  "error": null
}
```

HTTP Status: `200 OK`

---

### Scenario 2 — Health Check (Degraded — DB Down)

**Preconditions:**

- Set `DB_HOST=invalid-host` in `.env` to simulate DB failure
- Restart the app

**Steps:**

1. `curl -X GET http://localhost:8000/api/health`

**Expected Result:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "HEALTH_CHECK_FAILED",
    "message": "One or more health checks failed.",
    "details": {
      "database": "error",
      "cache": "ok"
    }
  }
}
```

HTTP Status: `503 Service Unavailable`

---

### Scenario 3 — Rate Limiting (Public Route)

**Preconditions:**

- `api-public` rate limiter: 10 req/min by IP

**Steps:**

1. Send 11 `GET /api/health` (or any public route) requests in quick succession from the same IP.

**Expected Result (on 11th request):**

- HTTP `429 Too Many Requests`
- Response headers include `Retry-After`, `X-RateLimit-Limit: 10`, `X-RateLimit-Remaining: 0`

---

### Scenario 4 — Rate Limiting (Authenticated Route)

**Preconditions:**

- Valid Sanctum token for a non-admin user
- `api-authenticated` rate limiter: 60 req/min

**Steps:**

1. Obtain a Sanctum token via `POST /api/v1/auth/login`
2. Send 61 authenticated requests in < 1 minute

**Expected Result (on 61st request):**

- HTTP `429 Too Many Requests`
- Headers: `Retry-After`, `X-RateLimit-Limit: 60`, `X-RateLimit-Remaining: 0`

---

### Scenario 5 — CORS Preflight

**Preconditions:**

- `CORS_ALLOWED_ORIGINS=http://localhost:3000` in `.env`

**Steps:**

1. Send a CORS preflight request:

```bash
curl -X OPTIONS http://localhost:8000/api/v1/auth/login \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -v
```

**Expected Result:**

- HTTP `200 OK` (or `204 No Content`)
- Headers: `Access-Control-Allow-Origin: http://localhost:3000`, `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers`, `Access-Control-Expose-Headers: X-Correlation-ID`

---

### Scenario 6 — CORS Rejected Origin

**Steps:**

1. Send a request from a non-allowed origin:

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Origin: http://evil.example.com" \
  -v
```

**Expected Result:**

- No `Access-Control-Allow-Origin` header in response (rejected silently by CORS middleware)

---

### Scenario 7 — OpenAPI Documentation Endpoint

**Preconditions:**

- `L5_SWAGGER_GENERATE_ALWAYS=true` in `.env` (or manually run `php artisan l5-swagger:generate`)

**Steps:**

1. `curl -X GET http://localhost:8000/api/documentation`

**Expected Result:**

- HTTP `200 OK`
- HTML Swagger UI page rendered

2. `curl -X GET http://localhost:8000/api/documentation.json`

**Expected Result:**

- HTTP `200 OK`
- Valid OpenAPI JSON document

---

### Scenario 8 — X-Correlation-ID Header Propagation

**Steps:**

1. Send a request with a correlation ID:

```bash
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <valid-token>" \
  -H "X-Correlation-ID: test-correlation-123" \
  -v
```

**Expected Result:**

- Response headers include `X-Correlation-ID: test-correlation-123` (same value echoed back)

## API Endpoints Added This Stage

| Method | Endpoint                  | Auth | Rate Limiter | Description       |
| ------ | ------------------------- | ---- | ------------ | ----------------- |
| GET    | `/api/health`             | None | None         | Health probe      |
| GET    | `/api/documentation`      | None | None         | Swagger UI        |
| GET    | `/api/documentation.json` | None | None         | OpenAPI JSON spec |

All existing `/api/v1/*` endpoints are unchanged. Routes now load from sub-files in `routes/api/v1/`.
