# STAGE_01 — Project Initialization

> **Phase:** 01_PLATFORM_FOUNDATION
> **Stage File:** `specs/phases/01_PLATFORM_FOUNDATION/STAGE_01_PROJECT_INITIALIZATION.md` > **Branch:** `spec/001-project-initialization` > **Created:** 2026-04-10T00:00:00Z

## Objective

Initialize the Bunyan (بنيان) full-stack construction marketplace platform with Laravel backend and Nuxt.js 3 frontend. Establish development tooling, linting, testing frameworks, CI/CD foundation, and enforce clean architecture patterns from day one.

## Scope

### In Scope

**Backend (Laravel)**

- Laravel 11 project with PHP 8.2+
- MySQL 8.x database connection
- Laravel Sanctum authentication scaffold
- PHPUnit test framework configuration
- Laravel Pint + PHP-CS-Fixer linting
- PHPStan static analysis (L9+)
- .env template structure with required vars
- Base exception handler with error contract compliance
- Base API controller with standard JSON response format
- RBAC middleware foundation (5 roles: Customer, Contractor, Supervising Architect, Field Engineer, Admin)
- Eloquent ORM repository pattern starter
- Service layer template for business logic
- Form Request base class for validation
- API resourceful response formatting

**Frontend (Nuxt.js 3)**

- Nuxt.js 3 project with Vue 3 Composition API
- **Nuxt UI** (`@nuxt/ui`) module setup — Tailwind CSS v4 components
- RTL support via `dir="rtl"` and Tailwind logical properties
- Pinia store configuration
- Vitest unit test setup with Vue Test Utils
- Playwright E2E configuration (`@nuxt/test-utils`)
- ESLint with `@nuxt/eslint` (TypeScript support)
- `@nuxtjs/i18n` module — Arabic/English bilingual setup
- Base layouts: default, auth (login/register), admin
- TypeScript strict mode enabled
- Tailwind CSS v4 configuration with RTL

**DevOps & Tooling**

- Husky pre-commit hooks (PHP-CS-Fixer, ESLint, Prettier, Vitest, PHPStan)
- lint-staged configuration for selective file linting
- GitHub Actions CI pipeline foundation (pre-commit guard)
- Docker Compose local dev environment (MySQL, Redis, Node)
- Monorepo structure (backend/, frontend/, docs/, .github/)
- npm & Composer scripts standardized (`lint`, `lint:fix`, `test`, `dev`, `build`)
- .gitignore covering both backend and frontend

### Out of Scope

- Actual RBAC policy implementations (deferred to STAGE_04)
- API endpoint implementations (deferred to STAGE_06)
- User management UI (deferred to STAGE_30)
- Production deployment automation (deferred to DevOps)
- Third-party service integrations (payments, SMS, etc.)
- Advanced CI/CD (staging/production workflows)
- SSL/TLS certificate generation
- Load balancing and scaling configuration

## User Stories

### US1 — Backend Developer Setup

**As a** backend developer, **I want** a fresh Laravel project with proper tooling, authentication scaffold, and testing framework, **so that** I can begin implementing APIs with confidence in code quality.

**Acceptance Criteria:**

- [ ] Laravel 11 installed with PHP 8.2+ verified
- [ ] Composer dependencies installed and stable
- [ ] MySQL connection configured and verified in .env
- [ ] Laravel Sanctum installed and registered
- [ ] PHPUnit configuration exists with `tests/` directory
- [ ] PHPStan configuration passes baseline analysis with zero errors
- [ ] PHP-CS-Fixer and Laravel Pint configured for PSR-12 compliance
- [ ] Base exception handler logs errors with structured format
- [ ] Base API response format returns standard JSON structure
- [ ] `composer run` scripts work: `lint`, `lint:fix`, `analyze`, `test`, `test:coverage`, `dev`

**Additional Notes:**

- All scripts use RTK (Rust Token Killer) prefix optimizations in local development
- Error contract compliance mandatory for all future API responses

---

### US2 — Frontend Developer Setup

**As a** frontend developer, **I want** a fresh Nuxt.js 3 project with Nuxt UI components, RTL support, testing framework, and TypeScript, **so that** I can build responsive Arabic-first UI with confidence in architecture.

**Acceptance Criteria:**

- [ ] Nuxt 3 installed with correct configuration
- [ ] Node.js version specified in `.nvmrc`
- [ ] Nuxt UI module installed and working (sample component renders correctly)
- [ ] RTL support verified: Tailwind logical properties applied, `dir="rtl"` configurable
- [ ] Pinia store initialized and working in shell layout
- [ ] Vitest configured with Vue Test Utils
- [ ] Playwright configured with `@nuxt/test-utils` (E2E smoke test passes)
- [ ] ESLint passes on all Vue files with TypeScript strict mode
- [ ] `@nuxtjs/i18n` configured with Arabic and English locales
- [ ] `npm run` scripts work: `dev`, `build`, `preview`, `lint`, `lint:fix`, `format`, `typecheck`, `test`, `test:watch`
- [ ] Base layouts created: default, auth, admin

**Additional Notes:**

- Arabic locale keys stored in `locales/ar.json` (key-based, not full translations)
- Frontend communicates exclusively via REST API with Laravel backend

---

### US3 — Clean Architecture Foundation

**As a** platform architect, **I want** the codebase structure to enforce clean architecture patterns, RBAC separation, and service layer discipline, **so that** future stages can extend safely without architectural drift.

**Acceptance Criteria:**

- [ ] Backend folder structure: `app/Models/`, `app/Http/Controllers/`, `app/Services/`, `app/Repositories/`, `app/Policies/`
- [ ] Controllers are thin (route → service → response)
- [ ] Services contain business logic, no HTTP concerns
- [ ] Repositories handle Eloquent queries and relationships
- [ ] Form Requests (base class) handle validation server-side
- [ ] API Resources template created for consistent response formatting
- [ ] Middleware directory includes auth + role checks (placeholders)
- [ ] Base exception handler configured to catch and format all errors
- [ ] Eloquent models use relationships and scopes (not direct queries)
- [ ] No raw SQL in controllers or services
- [ ] Composer PSR-4 autoloading verified for all namespaces

**Additional Notes:**

- Service layer instantiation via constructor injection (type hints)
- Repository pattern prevents N+1 queries via eager loading scopes
- RBAC middleware added to `app/Http/Middleware/` (actual role checks in STAGE_04)

---

### US4 — Local Development Environment

**As a** developer, **I want** a single `docker-compose up -d` command to spin up MySQL, Redis, and Node watcher, plus clear installation/startup docs, **so that** I can contribute without manual service configuration.

**Acceptance Criteria:**

- [ ] `docker-compose.yml` defines MySQL 8, Redis, and optional Node services
- [ ] MySQL seeded with sample data structure (empty)
- [ ] `.env.local` template provided with sensible defaults
- [ ] README.md contains step-by-step: install, configure, start, test, lint
- [ ] `Makefile` or npm/Composer script to start local services
- [ ] Development script documented: `php artisan serve` + `npm run dev`
- [ ] Pre-commit hooks functional and blocking on linting failures
- [ ] Husky installation automated in `npm install`
- [ ] Test commands verify database seeding and rollback

**Additional Notes:**

- Docker environment matches production (MySQL version, collation, timezone)
- Developers can opt-out of Docker and use local PHP/Node

---

## Technical Requirements

### Backend (Laravel)

- [ ] Laravel `laravel/framework` v11+ with `laravel/sanctum` authenticated
- [ ] `database/migrations/` contains zero custom migrations (fresh schema)
- [ ] `app/Http/Controllers/Api/BaseController.php` returns standard JSON with `success`, `data`, `message`, `errors`
- [ ] `app/Exceptions/Handler.php` formats all exceptions into standard error JSON
- [ ] `app/Http/Requests/BaseFormRequest.php` base class for validation
- [ ] `app/Http/Resources/BaseResource.php` for API response wrapping
- [ ] `app/Models/` contains no business logic (scopes/accessors only)
- [ ] `app/Services/` created as foundation for business logic (sample service)
- [ ] `app/Repositories/` created as foundation for data access (sample repository)
- [ ] `app/Policies/` directory created (policies deferred to STAGE_04)
- [ ] `app/Enums/` directory with UserRole enum (5 roles populated)
- [ ] `.php-cs-fixer.php` configured for PSR-12 (auto-fix on commit)
- [ ] `phpstan.neon` baseline generation passes L9 level (`vendor/bin/phpstan analyze`)
- [ ] `tests/Feature/` and `tests/Unit/` directories created with example tests
- [ ] `phpunit.xml` configured for database transactions and parallel testing
- [ ] `.env.example` populated with all required keys (SANCTUM*STATEFUL_DOMAINS, DB*, REDIS\_, etc.)

### Frontend (Nuxt.js)

- [ ] Nuxt `nuxt` v3.12+ with `@nuxt/ui` module installed
- [ ] `nuxt.config.ts` configured with RTL support and Nuxt UI preset
- [ ] `tailwind.config.ts` uses v4 with logical properties (margin-inline, padding-block, etc.)
- [ ] `layouts/default.vue` implemented (header, nav, footer structure)
- [ ] `layouts/auth.vue` implemented (login/register page wrapper)
- [ ] `layouts/admin.vue` implemented (admin panel wrapper)
- [ ] `stores/` directory with Pinia setup
- [ ] `composables/api.ts` client for calling Laravel API (axios/fetch wrapper)
- [ ] `composables/i18n.ts` helper for Arabic/English locale switching
- [ ] `app.vue` root component with layout selector
- [ ] `vitest.config.ts` configured with Vue Test Utils
- [ ] `playwright.config.ts` configured for E2E smoke testing
- [ ] `.eslintrc.json` with `@nuxt/eslint` preset and TypeScript parser
- [ ] `tsconfig.json` with `strict: true`
- [ ] `locales/ar.json` and `locales/en.json` sample keys
- [ ] `.nuxt/` in `.gitignore` (auto-generated)
- [ ] `package.json` `engines` field specifies Node version

### Testing Infrastructure

- [ ] Backend: Unit tests for utilities, Feature tests for API endpoints
- [ ] Frontend: Component tests for Vue components, E2E smoke test for login flow
- [ ] Coverage reporting configured (PHPUnit `phpunit.xml`, Vitest `vitest.config.ts`)
- [ ] CI pipeline foundation: GitHub Actions workflow file created (`.github/workflows/pre-commit-guard.yml`)
- [ ] Pre-commit guard checks: PHP-CS-Fixer validation, PHPStan, ESLint, Prettier, TypeScript, Vitest

### Database

- [ ] MySQL 8.x UTF-8MB4 collation (`utf8mb4_unicode_ci`)
- [ ] `php artisan migrate:fresh --seed` resets cleanly
- [ ] `database/seeders/` directory created (empty seeders OK for now)
- [ ] `database/factories/` directory created (no models to factory yet)
- [ ] Connection string example in `.env.example`

### Localization & RTL

- [ ] `@nuxtjs/i18n` configured with default locale `ar`
- [ ] RTL CSS via Tailwind logical properties (`margin-inline`, `padding-block`, `direction: rtl`)
- [ ] HTML `dir="rtl"` attribute configurable per locale
- [ ] Translation keys scoped (e.g., `common.button.submit`, `auth.label.email`)
- [ ] No hardcoded Arabic text in components (all in `locales/ar.json`)

### Error Handling & Response Format

All API responses follow this contract:

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "errors": {}
}
```

Error format:

```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "email": ["Email is required", "Email must be valid"]
  }
}
```

HTTP Status Codes:

- 200 OK — Successful request
- 201 Created — Resource created
- 400 Bad Request — Validation error
- 401 Unauthorized — Auth failed
- 403 Forbidden — RBAC denied
- 404 Not Found — Resource not found
- 422 Unprocessable Entity — Validation error (Form Request)
- 500 Internal Server Error — Server fault
- 503 Service Unavailable — Maintenance

## Dependencies

### Upstream

- None (this is the foundation stage)

### Downstream

- STAGE_02: Database Schema (depends on migration structure from this stage)
- STAGE_03: Authentication (depends on Sanctum scaffold and User model placeholder)
- STAGE_04: RBAC System (depends on middleware and permission base classes)
- STAGE_06: API Foundation (depends on base controller and response format)
- STAGE_29–34: All frontend stages (depend on Nuxt shell, layouts, composables)

## Non-Functional Requirements

- [ ] **Response Time:** Linting checks complete in < 5s (PHPStan < 10s)
- [ ] **Test Coverage:** 100% pass rate for bootstrap tests (0 failures allowed)
- [ ] **Accessibility:** RTL layout verified manually (heading hierarchy, form labels, button contrast)
- [ ] **Performance:** Docker services start in < 30s on modern machine
- [ ] **Security:** No secrets in `.env.example`, all keys documented
- [ ] **Maintainability:** All configuration files documented inline
- [ ] **Scalability:** Monorepo structure supports parallel backend/frontend development
- [ ] **Arabic/RTL Support:** All UI components respect `dir="rtl"` and use Tailwind logical properties
- [ ] **Code Quality:** Zero PHPStan L9 errors before merge
- [ ] **Developer Experience:** Single README with clear setup steps, all scripts aliased in npm/Composer

## Open Questions

- [ ] **MySQL Collation:** Will all tables use `utf8mb4_unicode_ci` or mix with `utf8mb4_bin` for case-sensitive fields? → Defaulting to `utf8mb4_unicode_ci` for consistency
- [ ] **API Versioning:** Should routes be `/api/v1/` or `/api/`? → Defaulting to `/api/v1/` per industry standard
- [ ] **Frontend Build Output:** SPA or SSR? → Defaulting to SPA (CSR) for stateless APIs, SSR optional in STAGE_29
- [ ] **Docker Registry:** Will images be pushed to Docker Hub or private registry? → Deferred to DevOps (STAGE_99)
- [ ] **Timezone:** All timestamps UTC or region-specific? → Defaulting to UTC in backend, frontend to user timezone

## Acceptance Checklist

### Before Merge

- [ ] Backend: `composer run lint:fix && composer run analyze && composer run test` all pass
- [ ] Frontend: `npm run lint:fix && npm run typecheck && npm run test` all pass
- [ ] GitHub Actions pre-commit guard passes
- [ ] Docker Compose services start: `docker-compose up -d && docker-compose ps`
- [ ] SQLite or MySQL connection successful
- [ ] Base API endpoint responds with standard JSON format
- [ ] Nuxt dev server starts: `npm run dev` (no errors)
- [ ] RTL support visually verified in browser
- [ ] All 4 user stories' acceptance criteria marked complete
- [ ] No [NEEDS CLARIFICATION] markers remain
- [ ] README.md complete with setup + troubleshooting steps
- [ ] `spec.md`, `plan.md`, `tasks.md`, and `checklists/requirements.md` all finalized

---

## Clarifications

> **Mode:** AUTOPILOT (resolved with best judgment aligned to architecture governance)  
> **Date:** 2026-04-10  
> **Scope:** STAGE_01_PROJECT_INITIALIZATION foundation layer ambiguities

### Q1: RBAC Middleware — Which routes should be public vs protected?

**Question:** In the foundation stage, the RBAC middleware scaffold exists but no actual endpoints are implemented. Should we establish a principle for which routes are public vs protected?

**Decision:** ✅ **Established Principle: Default-Protected, Explicit Exceptions**

_Rationale:_ Per Bunyan AGENTS.md non-negotiable rule: "Role-based access control on all routes via Laravel middleware." This foundation stage must encode this principle into the scaffold.

**Binding Decision:**

- **.All** API routes under `/api/v1/*` MUST use `auth:sanctum` middleware by default
- **Public exceptions** are explicit and documented:
  - `POST /api/v1/auth/register` (unauthenticated signup)
  - `POST /api/v1/auth/login` (unauthenticated login)
  - `GET /api/v1/health` (health check)
- **Health endpoint** returns `{ success: true, data: { status: 'ok' } }` with status 200
- Every controller action MUST authorize via Laravel Policies (scaffolded in STAGE_04; placeholders in STAGE_01)
- The middleware foundation includes placeholders for:
  - `VerifyApiVersion` (rejects requests not specifying API version)
  - `CheckRole` (placeholder; actual policy checks deferred to STAGE_04)

**Implementation Detail:**

```php
// BaseController.php — All API responses use:
return $this->response(data: $data, message: 'OK', status: 200);

// Exception Handler — All errors formatted as:
{ "success": false, "data": null, "message": "...", "errors": {...} }
```

---

### Q2: Error Responses — Exact validation error structure?

**Question:** The error contract specifies an `errors` object, but how should validation field errors be structured? Flat mapping or nested?

**Decision:** ✅ **Laravel Standard Validation Format**

_Rationale:_ Laravel's Form Request validation already produces this format; using it standardizes onboarding and reduces custom serialization.

**Binding Decision:**

- Validation errors in `errors` object map **field name → array of messages**
- Example validation response:
  ```json
  {
    "success": false,
    "data": null,
    "message": "Validation failed",
    "errors": {
      "email": ["Email is required", "Email must be a valid email address"],
      "password": ["Password must be at least 8 characters"]
    }
  }
  ```
- Server-side error (5xx, database, etc.) format:
  ```json
  {
    "success": false,
    "data": null,
    "message": "Internal Server Error",
    "errors": {}
  }
  ```
- Include a user-facing `message` but never expose stack traces or SQL in production

**Implementation Detail:**

- Form Requests use `Base\FormRequest::rules()` to return validation rules
- Exception Handler catches `ValidationException` and formats as above
- All 4xx/5xx HTTP responses strictly follow this contract
- No exceptions to this contract in any API layer

---

### Q3: Docker Compose — Which services are mandatory vs optional?

**Question:** The spec mentions MySQL, Redis, and Node services, but which are required for foundation stage local dev?

**Decision:** ✅ **Mandatory: MySQL | Recommended: Redis | Optional: Node watcher**

_Rationale:_ Practicality for developers. Backend cannot work without a database; Redis and Node services support future stages but aren't blocking for foundation.

**Binding Decision:**

- **MySQL 8.x** — MANDATORY
  - Required for `php artisan migrate` and `php artisan test`
  - Docker Compose default; developers can opt-out and use local MySQL
  - Environment: MySQL 8.0.x, `utf8mb4_unicode_ci`, port 3306
- **Redis** — RECOMMENDED (optional for STAGE_01, required later)
  - Used in later stages for queues, caching, sessions
  - Include in `docker-compose.yml` but not required to start
  - Developers can defer enabling until needed
- **Node watcher** — OPTIONAL (frontend devs only)
  - Not needed if developers run `npm run dev` locally
  - Useful for Dockerized dev workflows
  - Does NOT block STAGE_01 acceptance

**Implementation Detail:**

```yml
# docker-compose.yml structure:
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: bunyan
      MYSQL_ROOT_PASSWORD: root
    # Required for backend

  redis:
    image: redis:7-alpine
    # Optional; used in STAGE_14+ for queues/cache

  # Node watcher deferred if needed
```

**Startup Command (Foundation Stage):**

```bash
docker-compose up -d mysql  # Start only MySQL
php artisan serve            # Start Laravel API
npm run dev                  # Start Nuxt dev server (local Node)
```

---

### Q4: Nuxt UI — Which components are essential for foundation?

**Question:** The spec requires "sample component renders correctly," but which components define "essential" for foundation stage?

**Decision:** ✅ **Minimal Essential Set: UButton, UCard, UForm, UInput, ULayout**

_Rationale:_ These five components are sufficient to demonstrate Nuxt UI setup, RTL capability, and baseline Tailwind integration. Avoids over-specifying; other components added incrementally in STAGE_30+.

**Binding Decision:**

- **Proof-of-Concept Components** (must render correctly):
  1. `UButton` — Demonstrates component props, styling
  2. `UCard` — Demonstrates shadow-as-border design from DESIGN.md
  3. `UForm` + `UInput` — Together demonstrate Nuxt UI form system
  4. `ULayout` / base `layouts/default.vue` — Demonstrates RTL-aware structure

- **Verification Test** (E2E smoke test in Playwright):
  - Load `http://localhost:3000/` (root page)
  - Verify: Card renders, Button clickable, Form responsive
  - Verify: RTL direction attribute present in HTML
  - Verify: No console errors

- **RTL Verification**:
  - Inspect `<html dir="rtl">` attribute in dev server
  - Verify Tailwind logical properties applied (`margin-inline`, `padding-block`)
  - Verify component layout direction respected

**Implementation Detail:**

```vue
<!-- pages/index.vue — Foundation demo -->
<template>
  <div class="p-8">
    <UCard title="Bunyan Foundation">
      <UForm :schema="schema">
        <UInput v-model="form.email" label="Email" />
        <UButton type="submit">Submit</UButton>
      </UForm>
    </UCard>
  </div>
</template>
```

---

### Q5: RTL Support — Should all components strictly follow RTL from day 1?

**Question:** The spec mentions RTL support verification, but should implementation be "strict RTL" or just "RTL-configured"?

**Decision:** ✅ **RTL-Configured (not strict for foundation; content localization in later stages)**

_Rationale:_ Foundation stage focuses on architecture and tooling. Content and component localization follow in STAGE_30+ when actual pages exist. Setting up infrastructure now (logical properties, `dir` attribute) is sufficient.

**Binding Decision:**

- **RTL Configuration** (STAGE_01 responsibility):
  - `nuxt.config.ts`: Set `i18n.defaultLocale = 'ar'` and `dir: 'rtl'`
  - `tailwind.config.ts`: Enable logical properties globally
  - `layouts/default.vue`: Include `dir="rtl"` on root `<html>` tag
  - All Nuxt UI components automatically inherit RTL via configuration
- **RTL Infrastructure** (verified but not strict):
  - Confirm `<html dir="rtl">` renders correctly
  - Confirm Tailwind logical properties compile (e.g., `margin-inline-start` instead of `margin-left`)
  - No hardcoded LTR assumptions in grid, flex, or positioning
- **Content Localization** (deferred to STAGE_31+ frontend pages):
  - Actual Arabic text, number formatting, date formatting = later
  - Translation keys in `locales/ar.json` scaffolded but minimal
  - Component RTL layout tested; Arabic content added in STAGE_31

**Implementation Detail:**

```ts
// nuxt.config.ts
export default defineNuxtConfig({
  i18n: {
    locales: ['ar', 'en'],
    defaultLocale: 'ar',
    strategy: 'prefix',
  },
  dir: 'rtl', // Sets initial direction
});
```

---

### Q6: Testing Coverage — What % is acceptable for foundation stage?

**Question:** The spec requires "100% pass rate for bootstrap tests," but does this mean 100% code coverage or just zero test failures?

**Decision:** ✅ **Zero Test Failures; 70% Coverage for New Code**

_Rationale:_ "Bootstrap tests" = generated scaffolding + example implementations. Expecting 100% coverage on auto-generated code is impractical; focus instead on meaningful coverage for business logic layers (services, repositories).

**Binding Decision:**

- **Bootstrap Test Definition**: Tests for scaffolded code (models, migrations, base controller/service placeholders)
- **Passing Criteria**:
  - All tests must PASS (0 failures allowed)
  - Coverage baseline: 70% for new code written by developers
  - Generated/scaffolded code: No coverage requirement (it's infrastructure)
- **Coverage Reporting**:
  - Backend: `composer run test:coverage` → generates report
  - Frontend: `npm test` → includes coverage (optional for foundation)
- **Example Acceptable Coverage**:
  - Model relationships: Not required (generated)
  - Service layer: 70%+ (business logic)
  - Repository queries: 70%+ (data access layer)
  - Controllers: Not required (thin, delegated)
- **Foundation Stage Coverage Targets**:
  - At least 3 Unit tests per service/repository created
  - At least 2 Feature tests demonstrating protected routes (auth required)
  - At least 1 E2E test demonstrating frontend/backend integration

**Implementation Detail:**

```bash
# Backend
composer run test:coverage  # Shows %

# Frontend
npm run test                # Vitest run

# CI gate: Fail on test failures, warn on low coverage
```

---

### Summary of Binding Decisions

| Area                   | Decision                                                             | Impact                                                        |
| ---------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------- |
| **RBAC Routes**        | Default-protected all `/api/v1/*` routes; explicit public exceptions | Ensures security-first architecture                           |
| **Error Format**       | Laravel standard: field → array of messages                          | Native Laravel integration, consistent error handling         |
| **Docker Services**    | MySQL mandatory, Redis optional, Node watcher optional               | Flexible local dev, unblocks foundation delivery              |
| **Nuxt UI Components** | 5 essential components (Button, Card, Form, Input, Layout)           | Minimal but sufficient proof-of-concept                       |
| **RTL Support**        | Configuration now; content localization deferred to STAGE_31+        | Builds infrastructure without over-specifying                 |
| **Test Coverage**      | 0 failures; 70% for new code (scaffolding excluded)                  | Practical for bootstrap stage, scales to production standards |

### Governance Alignment

✅ All clarifications remain **within architecture governance bounds**:

- RBAC non-negotiable (✅ enforced as default-protected)
- Error contract binding (✅ standardized to Laravel validation format)
- No external dependencies unjustified (✅ all services already in tech stack)
- RTL/Arabic support as first-class (✅ configured infrastructure-first)

---

**Status:** CLARIFICATIONS COMPLETE  
**Next Step:** Proceed to `speckit.plan` to generate implementation design artifacts  
**Branch:** `spec/001-project-initialization` (ready for planning phase)
