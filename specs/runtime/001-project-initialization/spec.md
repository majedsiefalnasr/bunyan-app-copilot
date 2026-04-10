# STAGE_01 — Project Initialization

> **Phase:** 01_PLATFORM_FOUNDATION
> **Stage File:** `specs/phases/01_PLATFORM_FOUNDATION/STAGE_01_PROJECT_INITIALIZATION.md`
> **Branch:** `spec/001-project-initialization`
> **Created:** 2026-04-10T00:00:00Z

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
- [ ] `.env.example` populated with all required keys (SANCTUM_STATEFUL_DOMAINS, DB_, REDIS_, etc.)

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
