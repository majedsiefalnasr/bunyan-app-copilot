# Security — Requirements Checklist

**Stage:** STAGE_01_PROJECT_INITIALIZATION  
**Domain:** Security & RBAC Foundation  
**Version:** 1.0  
**Created:** 2026-04-10

---

## CHK001–010: Authentication & Sanctum Security Setup

- [ ] **CHK001** — Laravel Sanctum installed via `laravel/sanctum` package and registered in service providers  
      _Priority: **CRITICAL**_  
      _Note:_ Verify service provider in `config/app.php`

- [ ] **CHK002** — Sanctum token table created via `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`  
      _Priority: **CRITICAL**_  
      _Note:_ Configure token expiration in `config/sanctum.php`

- [ ] **CHK003** — `config/sanctum.php` defines `SANCTUM_STATEFUL_DOMAINS` for SPA (frontend domain)  
      _Priority: **CRITICAL**_  
      _Note:_ Example: `'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS'))`

- [ ] **CHK004** — Session cookie name unique and not conflicting with frontend (e.g., `XSRF-TOKEN`)  
      _Priority: **HIGH**_  
      _Note:_ Verify in `config/session.php` and frontend API client

- [ ] **CHK005** — Token revocation strategy documented (manual revoke, expiry, logout endpoint)  
      _Priority: **HIGH**_  
      _Note:_ Deferred implementation to STAGE_03, but spec required here

- [ ] **CHK006** — Sanctum auth guard configured in `config/auth.php` with `guards.api`  
      _Priority: **CRITICAL**_  
      _Note:_ Must use `auth:sanctum` middleware on protected routes

- [ ] **CHK007** — User model includes `HasApiTokens` trait from Sanctum  
      _Priority: **CRITICAL**_  
      _Note:_ Verify: `use Laravel\Sanctum\HasApiTokens;`

- [ ] **CHK008** — Token scopes (capabilities) defined in `app/Enums/TokenScope.php` or config  
      _Priority: **HIGH**_  
      _Note:_ Foundation: e.g., `read`, `write`, `admin`; implementation deferred to STAGE_03

- [ ] **CHK009** — Sanctum rate limiting activated in middleware (foundation placeholder)  
      _Priority: **MEDIUM**_  
      _Note:_ RateLimiter middleware to be applied per endpoint in STAGE_06

- [ ] **CHK010** — Password reset flow does NOT grant tokens directly; reauth required  
      _Priority: **HIGH**_  
      _Note:_ Security principle: reauth enforced after password reset

---

## CHK011–015: RBAC Policy Enforcement Foundation

- [ ] **CHK011** — `app/Policies/` directory exists with structure ready for policies (empty/scaffold)  
      _Priority: **CRITICAL**_  
      _Note:_ Actual policy classes deferred to STAGE_04

- [ ] **CHK012** — `app/Enums/UserRole.php` enum defined with 5 roles: Customer, Contractor, Supervising Architect, Field Engineer, Admin  
      _Priority: **CRITICAL**_  
      _Note:_ Roles stored as string enum values (`case CUSTOMER = 'customer';`)

- [ ] **CHK013** — `app/Http/Middleware/CheckRole.php` middleware exists (placeholder with decision logic template)  
      _Priority: **HIGH**_  
      _Note:_ Actual role checks deferred; foundation checks route requirement, current user role

- [ ] **CHK014** — `app/Http/Middleware/VerifyApiVersion.php` middleware exists (rejects requests without API version header)  
      _Priority: **MEDIUM**_  
      _Note:_ Requirement: `X-API-Version: 1.0` header or query param `?api_version=1.0`

- [ ] **CHK015** — Auth routes (login, register, logout) are EXPLICITLY marked public in routes/api.php  
      _Priority: **HIGH**_  
      _Note:_ Comment above route: `// Public: No auth required`

---

## CHK016–020: Password Hashing & Validation Rules

- [ ] **CHK016** — Laravel password hashing uses default `bcrypt` algorithm via `Hash::make()`  
      _Priority: **CRITICAL**_  
      _Note:_ Verify in auth controller: no MD5, SHA-1, or plaintext

- [ ] **CHK017** — Password hash cost factor configured in `config/hashing.php` with minimum rounds = 12  
      _Priority: **HIGH**_  
      _Note:_ Example: `'bcrypt' => ['rounds' => env('BCRYPT_ROUNDS', 12)],`

- [ ] **CHK018** — Password validation rules applied in Form Request: minimum 8 characters, uppercase, lowercase, number, special char  
      _Priority: **HIGH**_  
      _Note:_ Spec: `password|min:8|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/`

- [ ] **CHK019** — Password confirmation field required in registration/reset flows  
      _Priority: **MEDIUM**_  
      _Note:_ Rule: `password_confirmation|required_with:password|same:password`

- [ ] **CHK020** — Password history enforced (users cannot reuse last 5 passwords)  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: schema prepared for history tracking; enforcement deferred to STAGE_03

---

## CHK021–025: CORS & CSP Configuration

- [ ] **CHK021** — CORS middleware configured in `app/Http/Middleware/HandleCors.php`  
      _Priority: **CRITICAL**_  
      _Note:_ Laravel CORS package: `fruitcake/laravel-cors`

- [ ] **CHK022** — `config/cors.php` defines allowed origins: frontend domain, localhost:3000 for dev  
      _Priority: **CRITICAL**_  
      _Note:_ Example: `'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')]`

- [ ] **CHK023** — CORS middleware allows credentials (cookies) for session-based auth  
      _Priority: **HIGH**_  
      _Note:_ `'supports_credentials' => true` required for Sanctum XSRF-TOKEN

- [ ] **CHK024** — CORS allowed methods explicit: GET, POST, PUT, DELETE, PATCH, OPTIONS  
      _Priority: **HIGH**_  
      _Note:_ Avoid `*` for methods; always list explicitly

- [ ] **CHK025** — Content Security Policy (CSP) headers configured in exception handler or middleware  
      _Priority: **HIGH**_  
      _Note:_ Foundation: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`; full CSP deferred to STAGE_05

---

## CHK026–030: SQL Injection Prevention

- [ ] **CHK026** — Zero raw SQL queries in controllers, services, or repositories  
      _Priority: **CRITICAL**_  
      _Note:_ Lint check: search `DB::raw()` or `DB::statement()` usage; if found, refactor to Eloquent

- [ ] **CHK027** — All database queries use Eloquent ORM or Query Builder with parameter binding  
      _Priority: **CRITICAL**_  
      _Note:_ Example: `DB::where('email', $email)` NOT `DB::where("email = '$email'")`

- [ ] **CHK028** — Repository layer uses only Eloquent methods: `where()`, `whereIn()`, `first()`, `get()`  
      _Priority: **HIGH**_  
      _Note:_ No string concatenation in query filters

- [ ] **CHK029** — Form Requests use Laravel validation rules (not manual parsing)  
      _Priority: **HIGH**_  
      _Note:_ Validation prevents invalid input before DB query

- [ ] **CHK030** — Database table prefixes configured in `.env` (`DB_PREFIX=`)  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: prefix support enabled in `config/database.php`; prefix assignment deferred

---

## CHK031–035: CSRF Token Setup (Forms)

- [ ] **CHK031** — CSRF middleware registered in `app/Http/Kernel.php` for API routes  
      _Priority: **CRITICAL**_  
      _Note:_ Sanctum provides built-in CSRF protection via `VerifyCsrfToken`

- [ ] **CHK032** — CSRF token issued on login endpoint and stored in `X-CSRF-TOKEN` header  
      _Priority: **HIGH**_  
      _Note:_ Sanctum automatically injects token in response; frontend stores in header

- [ ] **CHK033** — All state-changing requests (POST, PUT, DELETE) require CSRF token  
      _Priority: **CRITICAL**_  
      _Note:_ Middleware enforces token validation for non-GET requests

- [ ] **CHK034** — CSRF token excluded from public endpoints (auth/register, auth/login)  
      _Priority: **HIGH**_  
      _Note:_ Listed in `VerifyCsrfToken::$except` array

- [ ] **CHK035** — CSRF error response returns 419 (Token Mismatch) with standard error JSON  
      _Priority: **MEDIUM**_  
      _Note:_ Exception handler formats as: `{ "success": false, "message": "CSRF token mismatch" }`

---

## CHK036–040: Environment Variables & Secrets Management

- [ ] **CHK036** — `.env.example` populated with ALL required keys (no secrets, all defaults)  
      _Priority: **CRITICAL**_  
      _Note:_ Never commit `.env`; only commit `.env.example`

- [ ] **CHK037** — Sensitive keys documented in `.env.example` with help comments  
      _Priority: **HIGH**_  
      _Note:_ Example: `# SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:3001`

- [ ] **CHK038** — `.env` file ignored in `.gitignore` (prevent accidental secret commits)  
      _Priority: **CRITICAL**_  
      _Note:_ Entry: `.env` and `.env.*.local`

- [ ] **CHK039** — No API keys, JWT secrets, or database passwords hardcoded in code  
      _Priority: **CRITICAL**_  
      _Note:_ All secrets read from `env()` function or `config()` helpers

- [ ] **CHK040** — `config/` files use `env('KEY', 'default')` for safe fallbacks  
      _Priority: **HIGH**_  
      _Note:_ Example: `'debug' => (bool)env('APP_DEBUG', false)`

---

## CHK041–045: Secrets Management (No Hardcoded Keys)

- [ ] **CHK041** — Application key (`APP_KEY`) generated via `php artisan key:generate` (not committed)  
      _Priority: **CRITICAL**_  
      _Note:_ Laravel auto-generates on install

- [ ] **CHK042** — Sanctum token signing key rotatable (key stored in `.env` via `APP_KEY`)  
      _Priority: **HIGH**_  
      _Note:_ Token invalidation strategy for key rollover deferred to STAGE_03

- [ ] **CHK043** — Database credentials (DB*HOST, DB_USER, DB_PASSWORD) in `.env` only  
      \_Priority: **CRITICAL***  
      _Note:_ Never in `config/database.php` or comments

- [ ] **CHK044** — Mail service credentials (MAIL*USERNAME, MAIL_PASSWORD) in `.env` only  
      \_Priority: **HIGH***  
      _Note:_ Deferred: email service stub; credentials prepared for future integration

- [ ] **CHK045** — Third-party API keys (Stripe, AWS, etc.) placeholder keys in `.env.example`  
      _Priority: **MEDIUM**_  
      _Note:_ Real keys sourced from secure vault in deployment; deferred to STAGE_99

---

## CHK046–050: Rate Limiting Foundation

- [ ] **CHK046** — Laravel throttle middleware registered in `app/Http/Kernel.php`  
      _Priority: **HIGH**_  
      _Note:_ Route-level throttle rules deferred to STAGE_06

- [ ] **CHK047** — Rate limiter cache driver configured (Redis or database) in `config/cache.php`  
      _Priority: **HIGH**_  
      _Note:_ Recommended: Redis for production; database for dev

- [ ] **CHK048** — API endpoint rate limit defaults defined: 60 requests per minute per IP  
      _Priority: **MEDIUM**_  
      _Note:_ Tunable per endpoint in STAGE_06

- [ ] **CHK049** — Auth endpoints (login, register) have stricter limits (5 attempts per 15 min)  
      _Priority: **MEDIUM**_  
      _Note:_ Brute-force protection; implemented in STAGE_03

- [ ] **CHK050** — Rate limit exceeded response returns 429 (Too Many Requests) with standard error JSON  
      _Priority: **MEDIUM**_  
      _Note:_ Response includes `Retry-After` header

---

## CHK051–055: Role Enumeration & Access Control

- [ ] **CHK051** — User roles defined as restricted enum (prevents typos and SQL injection)  
      _Priority: **CRITICAL**_  
      _Note:_ Enum values: `CUSTOMER`, `CONTRACTOR`, `SUPERVISING_ARCHITECT`, `FIELD_ENGINEER`, `ADMIN`

- [ ] **CHK052** — Role assignment validates against enum (prevents invalid role assignment)  
      _Priority: **HIGH**_  
      _Note:_ Database constraint: `role ENUM('customer', 'contractor', ...)`

- [ ] **CHK053** — User model has `role()` scope for querying by role  
      _Priority: **MEDIUM**_  
      _Note:_ Example: `User::role('contractor')->get()`

- [ ] **CHK054** — Role-based API access documented in code comments  
      _Priority: **HIGH**_  
      _Note:_ Example: `// Only SUPERVISING_ARCHITECT and ADMIN can access`

- [ ] **CHK055** — Unauthorized role access returns 403 (Forbidden) with standard error JSON  
      _Priority: **MEDIUM**_  
      _Note:_ Never expose available roles in error message

---

## CHK056–060: Security Headers & Response Sanitization

- [ ] **CHK056** — `X-Content-Type-Options: nosniff` header enforced globally in middleware  
      _Priority: **HIGH**_  
      _Note:_ Prevents MIME type sniffing attacks

- [ ] **CHK057** — `X-Frame-Options: DENY` header prevents clickjacking (unless CORS allows iframes)  
      _Priority: **HIGH**_  
      _Note:_ Deferred: review for SPA vs iframe usage in STAGE_29

- [ ] **CHK058** — `Strict-Transport-Security` (HSTS) header configured for HTTPS-only (dev: optional)  
      _Priority: **MEDIUM**_  
      _Note:_ Production: `max-age=31536000; includeSubDomains` (1 year)

- [ ] **CHK059** — Response JSON does NOT include sensitive metadata (file paths, versions)  
      _Priority: **HIGH**_  
      _Note:_ Spec: error messages are user-facing; no stack traces in prod

- [ ] **CHK060** — API responses sanitize HTML (no unescaped user input in error messages)  
      _Priority: **MEDIUM**_  
      _Note:_ Use Laravel escape helpers: `e()`, `Str::squish()`

---

## Summary

**Total Items:** 60  
**Sections:** 10 (Authentication, RBAC, Passwords, CORS/CSP, SQL, CSRF, Env/Secrets, Rate Limiting, Roles, Headers)  
**Priority Breakdown:**

- CRITICAL: 20 items
- HIGH: 24 items
- MEDIUM: 16 items

**Key Outcomes:**

- Foundation-level security controls in place (Sanctum, RBAC scaffold, error contract)
- OWASP Top 10 baseline addressed (auth, injection, CORS, CSRF)
- Secrets management prevents accidental exposure
- Rate limiting and role enumeration documented for future implementation
- All checklists validate REQUIREMENTS quality (not implementation)

**Next Steps:** Items marked as **MEDIUM** or with "deferred" notes should trigger follow-up checklists in later stages (STAGE_03, STAGE_04, STAGE_06).
