# Research: API Foundation (STAGE_06)

**Branch:** `spec/006-api-foundation`
**Resolved:** 2026-04-14
**Purpose:** Resolve all NEEDS CLARIFICATION items before Phase 1 design begins.

---

## R-01 — `darkaonline/l5-swagger` Package

### Findings

**Current stable version:** `9.0.x`

| Requirement             | Status                                      |
| ----------------------- | ------------------------------------------- |
| PHP ^8.1                | ✅ Project uses PHP ^8.3                    |
| Laravel ^10             | ✅ Project uses Laravel ^13.0               |
| OpenAPI 3.0 annotations | ✅ Supported via `zircote/swagger-php` ^4.x |

**Installation:**

```bash
cd backend
composer require darkaonline/l5-swagger
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

This publishes `config/l5-swagger.php` and `resources/views/vendor/l5-swagger/`.

**Key config options for this project:**

```php
// config/l5-swagger.php (post-publish adjustments)
'default' => 'default',

'documentations' => [
    'default' => [
        'api' => [
            'title' => 'Bunyan API',
        ],
        'routes' => [
            'api' => 'api/documentation',       // GET /api/documentation → Swagger UI
            'docs' => 'api/documentation.json', // GET /api/documentation.json → OpenAPI JSON
        ],
        'paths' => [
            'docs'       => storage_path('api-docs'),
            'docs_json'  => 'api-docs.json',
            'docs_yaml'  => 'api-docs.yaml',
            'annotations' => app_path('Http/Controllers'), // CLR-05: scan THIS directory
        ],
    ],
],

// Do not auto-regenerate in production (performance)
'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
```

**Dedicated annotation class (CLR-05):** Create `app/Http/Controllers/Api/OpenApiAnnotations.php` — a non-routable class in the scan path that contains only `@OA\Info`, `@OA\Server`, and `@OA\SecurityScheme`. No controller logic.

**Note:** `generate_always = true` in `.env.local` for development convenience. Set `L5_SWAGGER_GENERATE_ALWAYS=false` in production `.env`.

---

## R-02 — Laravel Rate Limiter Registration Location

### Findings

**In this project (Laravel 13):** Named rate limiters are registered in `AppServiceProvider::boot()` using `RateLimiter::for()`. **This is confirmed by the existing codebase.**

```php
// backend/app/Providers/AppServiceProvider.php — existing pattern
public function boot(): void
{
    RateLimiter::for('api', function (Request $request) { ... });
    RateLimiter::for('auth-login', function (Request $request) { ... });
    // ... 4 more existing limiters
}
```

**Where to add new limiters:** Extend the existing `AppServiceProvider::boot()` — add `api-authenticated`, `api-public`, and `api-admin` at the end of the existing `RateLimiter::for()` block.

**Why not `bootstrap/app.php` or `RouteServiceProvider`:** No `RouteServiceProvider` exists in this project. `bootstrap/app.php` does not expose a hook for `RateLimiter`. `AppServiceProvider::boot()` is the canonically correct place in Laravel 11+.

---

## R-03 — Laravel CORS Configuration

### Findings

**`HandleCors` middleware status:** Already registered as a global middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    TrustProxies::class,
    HandleCors::class,           // ← already registered globally
    ValidatePostSize::class,
    ConvertEmptyStringsToNull::class,
    CorrelationIdMiddleware::class,
    RequestResponseLoggingMiddleware::class,
];
```

**`config/cors.php` status:** **File does not exist** in `backend/config/`. Must be created manually.

In Laravel 13 (like 11+), `config/cors.php` is not scaffolded by default but `HandleCors` will read it if present. The file must be created at `backend/config/cors.php`.

**`allowed_origins` env pattern:** Use `explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))` to support comma-separated origins from a single env var (FR-028).

**`exposed_headers` requirement:** Must include `X-Correlation-ID` so the browser can read the correlation ID via JavaScript (FR-031).

**`supports_credentials`:** Must be `true` for Sanctum cookie auth (FR-032). **Critical:** When `supports_credentials = true`, wildcard `*` in `allowed_origins` is not permitted — browser will block. Production `CORS_ALLOWED_ORIGINS` must be explicit.

---

## R-04 — Existing Middleware Registration State

### Findings

The project uses a **hybrid middleware registration architecture** — both Kernel.php (Laravel 9/10 pattern) and bootstrap/app.php (Laravel 11+ pattern) are active simultaneously in Laravel 13.

**Kernel.php (`app/Http/Kernel.php`):**

- `$middleware` (global, all requests): `TrustProxies`, `HandleCors`, `ValidatePostSize`, `ConvertEmptyStringsToNull`, `CorrelationIdMiddleware`, `RequestResponseLoggingMiddleware`
- `$middlewareGroups['api']`: `ThrottleRequests::with(10, 1)`, `SubstituteBindings`
- `$middlewareAliases`: `throttle`, `check-account-lockout`, and all standard Laravel aliases

**bootstrap/app.php:**

- `$middleware->alias()`: `role`, `permission`, `check-account-lockout` (note: `check-account-lockout` appears in both — no conflict, last registration wins in Laravel 13)

**Impact on this stage:**

| Requirement                                                                                 | Where                                 | Status                           |
| ------------------------------------------------------------------------------------------- | ------------------------------------- | -------------------------------- |
| FR-016: Global middleware order (TrustProxies → HandleCors → ... → CorrelationId → Logging) | Kernel.php `$middleware`              | ✅ Already satisfied — NO CHANGE |
| FR-017: `api` group includes `ThrottleRequests::with(10,1) → SubstituteBindings`            | Kernel.php `$middlewareGroups['api']` | ✅ Already satisfied — NO CHANGE |
| FR-018: Route aliases include `throttle`, `role`, `permission`, `check-account-lockout`     | Kernel.php + bootstrap/app.php        | ✅ Already satisfied — NO CHANGE |
| FR-019: `CorrelationIdMiddleware` globally registered                                       | Kernel.php `$middleware`              | ✅ Already satisfied — NO CHANGE |
| CLR-01: New registrations use bootstrap/app.php                                             | Any new aliases this stage adds       | Plan accordingly                 |

**Conclusion:** No middleware registration changes are required to satisfy FR-016–FR-019. They are already satisfied by the existing Kernel.php. This stage adds only new named rate limiters (AppServiceProvider) and a new `throttle` alias confirmation (already in Kernel.php).

---

## R-05 — `paginated()` Method Gap

### Findings

`ApiResponseTrait` implements `success()` and `error()` only. **`paginated()` does not exist** in either `ApiResponseTrait` or `BaseController`. FR-008 and FR-010 require it.

**Resolution:** Add `paginated()` as a protected method on `BaseApiController` directly (not on the trait). This keeps the trait unchanged (no regression risk) and satisfies FR-008.

---

## R-06 — Existing Resources Do Not Extend BaseApiResource

### Findings

All four existing resources extend `Illuminate\Http\Resources\Json\JsonResource` directly:

- `app/Http/Resources/UserResource.php`
- `app/Http/Resources/RoleResource.php`
- `app/Http/Resources/PermissionResource.php`
- `app/Http/Resources/UserRoleResource.php`

FR-015 requires them to extend `BaseApiResource` after it's created. This is a **migration step** — must happen after `BaseApiResource` is implemented and tested, not before.

---

## R-07 — Route Sub-File Structure

### Findings

Current `routes/api.php` contains all routes in one file. The spec requires extraction into sub-files (FR-002):

- `routes/api/v1/auth.php`
- `routes/api/v1/users.php`
- `routes/api/v1/admin.php`

**Risk:** Route names are not currently set using `->name()` on most routes. FR-003 mandates the `api.v1.[resource].[action]` naming convention. Adding names is a non-breaking additive change but must not break existing test assertions that target route URIs rather than names.

**`GET /api/health` placement:** Must be **outside** the `Route::prefix('v1')` group in `routes/api.php` — directly on the `Route::middleware('api')` group or without any group. Must have no auth middleware and no throttle.

---

## Summary of Resolved Unknowns

| ID   | Unknown                            | Resolution                                                        |
| ---- | ---------------------------------- | ----------------------------------------------------------------- |
| R-01 | l5-swagger version + config        | v9.x; install + publish; scan `app_path('Http/Controllers')`      |
| R-02 | Rate limiter registration location | `AppServiceProvider::boot()` — existing pattern                   |
| R-03 | CORS config                        | Create `config/cors.php` manually; `HandleCors` already global    |
| R-04 | Middleware registration gaps       | All FR-016–FR-019 already satisfied; no Kernel.php changes needed |
| R-05 | `paginated()` missing from trait   | Add to `BaseApiController` directly                               |
| R-06 | Resources extend wrong base        | Migrate after `BaseApiResource` created                           |
| R-07 | Route naming convention            | Additive `->name()` calls during sub-file extraction              |
