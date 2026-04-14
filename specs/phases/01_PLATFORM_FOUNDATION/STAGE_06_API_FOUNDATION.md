# STAGE_06 — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** NOT STARTED
> **Scope:** API routing, versioning, middleware stack, rate limiting
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: plan
Risk Level: MEDIUM
Last Updated: 2026-04-14T00:05:00Z

Scope Planned:

- Versioned API routing (`/api/v1/*`) — route sub-files extracted
- BaseApiController (`Api\V1\`) with `paginated()` response helper
- BaseApiResource with `$wrap='data'` and pagination meta
- Rate limiting: `api-authenticated` (60/min/user), `api-public` (10/min/IP), `api-admin` (300/min/user)
- CORS: `config/cors.php` with env-driven `allowed_origins`, `X-Correlation-ID` exposed, boot-time wildcard guard
- Health check `GET /api/health` using `HEALTH_CHECK_FAILED` on 503 (data:null, probe data in error.details)
- OpenAPI 3.0 via `darkaonline/l5-swagger ~9.0`, annotations isolated in `OpenApiAnnotations.php`

Deferred Scope:

- Individual feature endpoint implementation
- Webhook infrastructure
- Third-party API key management

Architecture Governance Compliance:

- Architecture Guardian: PASS
- API Designer: PASS
- Technical plan compliant — task generation authorized

Notes:
Technical plan complete. Task breakdown in progress.

## Objective

Establish the API foundation including routing conventions, middleware stack, rate limiting, API versioning, and resource formatting patterns.

## Scope

### Backend

- API route organization (versioned: /api/v1/\*)
- Base API controller with response helpers
- API Resource base class
- Rate limiting configuration
- CORS configuration
- Request throttling middleware
- API documentation setup (OpenAPI/Swagger)
- Health check endpoint

### Middleware Stack

```
Request
→ CORS
→ Rate Limit
→ Auth (Sanctum)
→ RBAC
→ Correlation ID
→ Request Logging
→ Controller
→ Response Logging
→ Response
```

## Dependencies

- **Upstream:** STAGE_04_RBAC_SYSTEM, STAGE_05_ERROR_HANDLING
- **Downstream:** All API endpoints
