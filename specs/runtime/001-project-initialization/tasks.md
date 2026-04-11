# Tasks — STAGE_01_PROJECT_INITIALIZATION

> **Phase:** 01_PLATFORM_FOUNDATION
> **Based on:** `specs/runtime/001-project-initialization/plan.md` > **Created:** 2026-04-10T00:00:00Z
> **Total Tasks:** 34
> **Estimated Serial Time:** ~155 minutes
> **Estimated Parallel Time:** ~80 minutes (with optimal wave scheduling)

---

## Legend

- `- [ ]` — Incomplete checkpoint (marked `[X]` by speckit.implement)
- `T###` — Sequential task ID (1-indexed across all phases)
- `[P]` — Parallelizable marker (can run concurrently with siblings in same phase)
- `[US#]` — User story reference (US1=Backend, US2=Frontend, US3=Architecture, US4=DevOps)
- **Acceptance Criteria:** Binding checkpoints that must pass before moving to next phase

---

## Phase 1 — Monorepo Scaffolding (Sequential Foundation)

### Critical Path Phase: Project Initialization

Backend and Frontend scaffolding can execute in parallel to each other, but each must complete before config tasks.

#### Backend Initialization

- [x] **T001** [US1] Create Laravel 11 project skeleton
  - **File:** `backend/` (root directory)
  - **Command:** `composer create-project laravel/laravel backend --prefer-dist`
  - **Acceptance Criteria:**
    - [x] `backend/artisan` exists and is executable
    - [x] `backend/composer.json` contains Laravel 11 (version ^11.0)
    - [x] `backend/.env` exists and contains sample keys (COPY FROM .env.example)
    - [x] `backend/app/`, `backend/routes/`, `backend/database/` directories exist
    - [x] PHP 8.2+ requirement verified in `composer.json` (`"php": "^8.2"`)
  - **Estimated Time:** 5 min
  - **Dependencies:** None

#### Frontend Initialization

- [x] **T002** [P] [US2] Create Nuxt 3 project skeleton
  - **File:** `frontend/` (root directory)
  - **Command:** `npx nuxi@latest init frontend --install false && cd frontend && npm install` (use pnpm if preferred)
  - **Acceptance Criteria:**
    - [x] `frontend/package.json` contains Nuxt 3 (version ^3.12.0)
    - [x] `frontend/nuxt.config.ts` exists
    - [x] `frontend/app.vue` exists
    - [x] `frontend/pages/`, `frontend/components/`, `frontend/layouts/` directories exist
    - [x] Node version specified in `frontend/.nvmrc` (Node 18.0.0 or higher)
    - [x] `npm run dev` can start dev server without errors
  - **Estimated Time:** 5 min
  - **Dependencies:** None (can start parallel with T001)

---

## Phase 2 — Configuration & Dependencies (Parallelizable Setup)

**Gate:** Both T001 and T002 must complete before proceeding.

### Backend Configuration

- [x] **T003** [P] [US1] Configure MySQL database connection

  - **Files:**
    - `backend/.env`
    - `backend/.env.example`
  - **Acceptance Criteria:**
    - [x] `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=bunyan_dev`
    - [x] `DB_USERNAME=root`, `DB_PASSWORD` set (or empty for local MySQL)
    - [x] Command succeeds: `php artisan migrate --dry-run`
    - [x] No migration errors when checking schema
  - **Estimated Time:** 10 min
  - **Dependencies:** T001

- [x] **T004** [P] [US1] Install and configure Laravel Sanctum

  - **Files:**
    - `backend/composer.json` (dependency added)
    - `backend/config/sanctum.php` (published)
    - `backend/database/migrations/xxxx_create_personal_access_tokens_table.php`
  - **Commands:**
    ```bash
    cd backend
    composer require laravel/sanctum
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    php artisan migrate
    ```
  - **Acceptance Criteria:**
    - [x] Sanctum service provider registered in `config/app.php`
    - [x] `personal_access_tokens` table created by migration
    - [x] `config/sanctum.php` exists and configured (stateful domains, middleware)
    - [x] `SANCTUM_STATEFUL_DOMAINS` in `.env`
  - **Estimated Time:** 10 min
  - **Dependencies:** T001, T003

- [x] **T005** [P] [US1] Install and configure PHPStan + PHP-CS-Fixer
  - **Files:**
    - `backend/composer.json` (dev dependencies added)
    - `backend/phpstan.neon`
    - `backend/.php-cs-fixer.php`
  - **Commands:**
    ```bash
    cd backend
    composer require --dev laravel/pint
    composer require --dev phpstan/phpstan phpstan/phpstan-laravel
    vendor/bin/pint --version
    vendor/bin/phpstan --version
    ```
  - **Acceptance Criteria:**
    - [x] `phpstan.neon` exists with `level: 5` (starting baseline)
    - [x] `.php-cs-fixer.php` exists and references PSR-12
    - [x] Command succeeds: `php artisan pint --test`
    - [x] Command succeeds: `vendor/bin/phpstan analyze --generate-baseline`
    - [x] Baseline created: `phpstan-baseline.neon`
  - **Estimated Time:** 10 min
  - **Dependencies:** T001

### Frontend Configuration

- [x] **T006** [P] [US2] Install @nuxt/ui module + Tailwind CSS v4

  - **Files:**
    - `frontend/package.json` (@nuxt/ui added)
    - `frontend/nuxt.config.ts` (module registration)
    - `frontend/tailwind.config.ts`
    - `frontend/app.vue` (test Nuxt UI component)
  - **Commands:**
    ```bash
    cd frontend
    npx nuxi@latest module add ui
    npm install
    ```
  - **Acceptance Criteria:**
    - [x] `@nuxt/ui` present in `package.json` dependencies
    - [x] `tailwind.config.ts` references Nuxt UI preset
    - [x] `tailwind.config.ts` has `extend: { colors: { ... } }` for Nuxt UI palette
    - [x] Sample UButton component renders on `app.vue` without error (`npm run dev` loads)
    - [x] Tailwind v4 detected (check `node_modules/tailwindcss/package.json` version)
  - **Estimated Time:** 12 min
  - **Dependencies:** T002

- [x] **T007** [P] [US2] Enable TypeScript strict mode + @nuxtjs/i18n

  - **Files:**
    - `frontend/tsconfig.json`
    - `fronten d/nuxt.config.ts` (i18n module registration)
    - `frontend/locales/ar.json`
    - `frontend/locales/en.json`
  - **Commands:**
    ```bash
    cd frontend
    npx nuxi@latest module add i18n
    npm install
    ```
  - **Acceptance Criteria:**
    - [x] `tsconfig.json` contains `"strict": true`
    - [x] `@nuxtjs/i18n` registered in `nuxt.config.ts`
    - [x] `locales/ar.json` exists with sample keys: `{ "welcome": "أهلا", "login": "دخول" }`
    - [x] `locales/en.json` exists with sample keys: `{ "welcome": "Welcome", "login": "Login" }`
    - [x] Default locale set to `"ar"` in Nuxt config
    - [x] RTL detection enabled: `strategy: 'prefix'` and `rtl: true` in i18n config
  - **Estimated Time:** 12 min
  - **Dependencies:** T002, T006

- [x] **T008** [P] [US2] Configure RTL support + Tailwind logical properties

  - **Files:**
    - `frontend/nuxt.config.ts` (html dir binding)
    - `frontend/tailwind.config.ts` (important: support logical properties)
    - `frontend/app.vue` (test RTL rendering)
  - **Acceptance Criteria:**
    - [x] `app.vue` wraps app in `<div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'">`
    - [x] Tailwind config does NOT set `content: [...]` for RTL override (v4 native)
    - [x] Example Tailwind classes in shell layout use logical properties: `ms-2` (margin-start), `ps-4` (padding-start)
    - [x] Visual test: `npm run dev` → UI appears correct in LTR and RTL
  - **Estimated Time:** 10 min
  - **Dependencies:** T002, T006, T007

- [x] **T009** [P] [US2] Install @nuxtjs/eslint + configure Vitest
  - **Files:**
    - `frontend/package.json` (eslint, vitest, @vue/test-utils added)
    - `frontend/.eslintrc.json` (created via npx)
    - `frontend/vitest.config.ts`
  - **Commands:**
    ```bash
    cd frontend
    npm install --save-dev @nuxt/eslint vitest @vitest/ui @vue/test-utils vitest-canvas-mock
    npx @nuxt/eslint --init
    ```
  - **Acceptance Criteria:**
    - [x] `.eslintrc.json` exists and references `@nuxt/eslint` preset + TypeScript parser
    - [x] `vitest.config.ts` exists with `environment: 'jsdom'` and Vue test utils pooling
    - [x] Command succeeds: `npm run lint` (no errors on sample templates)
    - [x] Command succeeds: `npm run test` (vitest runs and finds no tests, exits cleanly)
  - **Estimated Time:** 12 min
  - **Dependencies:** T002, T006, T007

---

## Phase 3 — Backend Scaffolding (Implementation)

**Gate:** T001, T003, T004, T005 must complete.

### User Model & RBAC Foundation

- [x] **T010** [US1] [US3] Create User model with $role enum

  - **File:** `backend/app/Models/User.php`
  - **Acceptance Criteria:**
    - [x] `User` extends Authenticatable (Larvel default)
    - [x] `$role` attribute cast to enum: `'role' => UserRole::class`
    - [x] `UserRole` enum exists at `backend/app/Enums/UserRole.php`
    - [x] Enum has 5 cases: `CUSTOMER`, `CONTRACTOR`, `SUPERVISING_ARCHITECT`, `FIELD_ENGINEER`, `ADMIN`
    - [x] Each enum case backed by string value: `'customer'`, `'contractor'`, etc.
    - [x] Enum implements `AsStringBackedEnum` correctly
    - [x] `$fillable = ['name', 'email', 'password', 'role']`
    - [x] `$hidden = ['password', 'remember_token']`
    - [x] `User::findByRole('admin')` scope works (lazy define if needed for later)
  - **Estimated Time:** 20 min
  - **Dependencies:** T001

- [x] **T011** [US1] [US3] Create users table migration

  - **File:** `backend/database/migrations/{timestamp}_create_users_table.php`
  - **Acceptance Criteria:**
    - [x] Migration creates `users` table with all columns per `data-model.md`
    - [x] `role` column is ENUM with 5 values: customer, contractor, supervising_architect, field_engineer, admin
    - [x] Default role is `'customer'`
    - [x] Indexes on `email`, `role`, `created_at`
    - [x] Migration rollback (`down()`) drops table
    - [x] Command succeeds: `php artisan migrate`
    - [x] Command succeeds: `php artisan migrate:rollback && php artisan migrate`
  - **Estimated Time:** 15 min
  - **Dependencies:** T001, T003

- [x] **T012** [P] [US3] Create base exception handler with error contract

  - **File:** `backend/app/Exceptions/Handler.php`
  - **Acceptance Criteria:**
    - [x] Handler catches all exceptions and formats as standard JSON (see `error-contract.md`)
    - [x] Response wraps all errors in: `{ success: false, data: null, message: "...", errors: {...} }`
    - [x] Validation errors extract field messages: `errors: { field: [...] }`
    - [x] 401 errors return `message: "Unauthenticated"`
    - [x] 403 errors return `message: "Unauthorized"`
    - [x] 404 errors return `message: "Not Found"`
    - [x] 500 errors log to storage/logs/laravel.log and return `message: "Internal Server Error"` (no stack)
    - [x] JSON responses include `Content-Type: application/json` header
  - **Estimated Time:** 20 min
  - **Dependencies:** T001

- [x] **T013** [P] [US3] Create base API controller with response methods
  - **File:** `backend/app/Http/Controllers/Api/BaseController.php`
  - **Acceptance Criteria:**
    - [x] `BaseController` extends Controller
    - [x] Methods: `success($data, $message = 'Success', $code = 200)` and `error($message, $errors = [], $code = 400)`
    - [x] `success()` returns: `{ success: true, data: ..., message: ..., errors: {} }`
    - [x] `error()` returns: `{ success: false, data: null, message: ..., errors: ... }`
    - [x] Responses set correct HTTP status codes
    - [x] All responses set `Content-Type: application/json`
  - **Estimated Time:** 15 min
  - **Dependencies:** T001

### Layering Infrastructure

- [x] **T014** [P] [US3] Create Form Request base class

  - **File:** `backend/app/Http/Requests/BaseFormRequest.php`
  - **Acceptance Criteria:**
    - [x] `BaseFormRequest` extends `FormRequest`
    - [x] `authorize()` returns true (RBAC checks in concrete classes)
    - [x] `failedValidation()` formats errors per error-contract
    - [x] Validation rules collected in `rules()` method
    - [x] Sample rule exists: email must be unique in `users` table
  - **Estimated Time:** 12 min
  - **Dependencies:** T001

- [x] **T015** [P] [US3] Create API Resource base class

  - **File:** `backend/app/Http/Resources/BaseResource.php`
  - **Acceptance Criteria:**
    - [x] `BaseResource` extends `JsonResource`
    - [x] Wraps data in `{ success: true, data: ..., message: "...", errors: {} }`
    - [x] Inheritors override `toArray()` to shape model data
  - **Estimated Time:** 10 min
  - **Dependencies:** T001

- [x] **T016** [P] [US3] Create base Service class

  - **File:** `backend/app/Services/BaseService.php`
  - **Acceptance Criteria:**
    - [x] `BaseService` provides common methods: `getById()`, `all()`, `create()`, `update()`, `delete()`
    - [x] Inheritors inject repositories via constructor
    - [x] No HTTP concerns, no controller logic
    - [x] Transaction support: `DB::transaction()` available
  - **Estimated Time:** 12 min
  - **Dependencies:** T001

- [x] **T017** [P] [US3] Create repository pattern starter
  - **File:** `backend/app/Repositories/BaseRepository.php` + `backend/app/Repositories/UserRepository.php`
  - **Acceptance Criteria:**
    - [x] `BaseRepository` provides: `find()`, `all()`, `create()`, `update()`, `delete()`, `findBy()`
    - [x] `UserRepository extends BaseRepository`
    - [x] Methods use Eloquent relationships and scopes (no raw SQL)
    - [x] Eager loading implemented: `User::with('roles')->get()` (mock for now)
    - [x] Returns model instances, not raw arrays
  - **Estimated Time:** 15 min
  - **Dependencies:** T001, T010, T011

### RBAC & Auth Scaffolding

- [x] **T018** [P] [US1] [US3] Create RBAC Policies structure

  - **Files:**
    - `backend/app/Policies/BasePolicy.php`
    - `backend/app/Policies/UserPolicy.php`
    - `backend/app/Policies/ProjectPolicy.php` (stub for future)
    - `backend/app/Providers/AuthServiceProvider.php` (register policies)
  - **Acceptance Criteria:**
    - [x] `BasePolicy` provides `before()` hook (admins can do anything)
    - [x] `UserPolicy` has `view()`, `create()`, `update()`, `delete()` methods (stubs return self::allowed() or self::denied())
    - [x] AuthServiceProvider maps Model → Policy
    - [x] Policies callable from controllers: `$this->authorize('view', $user)`
  - **Estimated Time:** 18 min
  - **Dependencies:** T001, T010, T012

- [x] **T019** [P] [US1] Create Auth controller with seed endpoints

  - **File:** `backend/app/Http/Controllers/Api/AuthController.php`
  - **Acceptance Criteria:**
    - [x] `AuthController` extends `BaseController`
    - [x] `register()` method implements spec from `auth-contract.md`
    - [x] `login()` method implements spec
    - [x] `logout()` method implements spec
    - [x] `me()` method returns logged-in user
    - [x] Register creates user with role='customer' by default
    - [x] Login validates email/password and returns token
    - [x] Logout revokes token
    - [x] All responses follow BaseController format
  - **Estimated Time:** 25 min
  - **Dependencies:** T001, T010, T013, T014, T018

- [x] **T020** [P] [US1] Register auth routes
  - **File:** `backend/routes/api.php`
  - **Acceptance Criteria:**
    - [x] Routes under `/api/v1/auth/`
    - [x] POST `/api/v1/auth/register` → `AuthController@register` (public)
    - [x] POST `/api/v1/auth/login` → `AuthController@login` (public)
    - [x] POST `/api/v1/auth/logout` → `AuthController@logout` (requires auth)
    - [x] GET `/api/v1/auth/me` → `AuthController@me` (requires auth)
    - [x] CORS middleware applied to all API routes
    - [x] Routes tested via Postman/curl
  - **Estimated Time:** 12 min
  - **Dependencies:** T001, T019

### Testing Setup (Backend)

- [x] **T021** [US1] Configure PHPUnit + feature test example
  - **Files:**
    - `backend/phpunit.xml`
    - `backend/tests/Feature/Auth/RegisterTest.php`
    - `backend/tests/Unit/Services/SampleServiceTest.php`
  - **Acceptance Criteria:**
    - [x] `phpunit.xml` configured with `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
    - [x] Parallel test execution enabled via `processIsolation`
    - [x] Example feature test for `/api/v1/auth/register` endpoint
    - [x] Test database uses in-memory SQLite (fast)
    - [x] Command succeeds: `php artisan test`
    - [x] At least 1 passing test exists
  - **Estimated Time:** 20 min
  - **Dependencies:** T001, T003, T004, T011, T019, T020

---

## Phase 4 — Frontend Scaffolding (Implementation)

**Gate:** T002, T006, T007, T008, T009 must complete.

### State & Composables

- [x] **T022** [US2] [US3] Create Pinia stores (user + theme)

  - **Files:**
    - `frontend/stores/user.ts`
    - `frontend/stores/theme.ts`
  - **Acceptance Criteria:**
    - [x] `UserStore` tracks: `id`, `name`, `email`, `role`, `token`, `isAuthenticated`
    - [x] Actions: `login()`, `logout()`, `setUser()`, `setToken()`
    - [x] Getters: `getUser()`, `isAdmin()`, `hasRole(role)`
    - [x] Persist token to `localStorage` automatically
    - [x] `ThemeStore` tracks: `isDarkMode`, `currentLocale`
    - [x] Actions: `toggleTheme()`, `setLocale()`
    - [x] Persist theme preference to localStorage
    - [x] Both stores export `useXxxStore = defineStore(...)
  - **Estimated Time:** 18 min
  - **Dependencies:** T002

- [x] **T023** [P] [US2] [US3] Create API composable for Laravel communication

  - **File:** `frontend/composables/useApi.ts`
  - **Acceptance Criteria:**
    - [x] Composable wraps `axios` or native `fetch`
    - [x] Methods: `get()`, `post()`, `put()`, `delete()`, `patch()`
    - [x] Automatically adds `Authorization: Bearer <token>` header if token in store
    - [x] Handles error responses and formats to standard error object
    - [x] Interceptors handle 401 (logout) and 403 (permission denied)
    - [x] Example: `useApi().post('/auth/login', {...})`
    - [x] Returns typed ResponseType (TypeScript)
  - **Estimated Time:** 15 min
  - **Dependencies:** T002, T022

- [x] **T024** [P] [US2] [US3] Create i18n composable helper
  - **File:** `frontend/composables/useI18n.ts`
  - **Acceptance Criteria:**
    - [x] Wrapper around `@nuxtjs/i18n` `useI18n()` hook
    - [x] Exports methods: `t()` (translate), `locale`, `setLocale()`
    - [x] Handles RTL direction binding: `<html :dir="isRTL">`
    - [x] Sample: `t('labels.welcome')` → "أهلا" or "Welcome"
  - **Estimated Time:** 10 min
  - **Dependencies:** T002, T007

### Layouts & Base Components

- [x] **T025** [P] [US2] Create base layouts (default, auth, admin)

  - **Files:**
    - `frontend/layouts/default.vue`
    - `frontend/layouts/auth.vue`
    - `frontend/layouts/admin.vue`
  - **Acceptance Criteria:**
    - [x] `default.vue`: Header (Nuxt UI), nav sidebar (will populate later), footer (Nuxt UI UCard)
    - [x] `auth.vue`: Centered form layout (no sidebar), supports RTL
    - [x] `admin.vue`: Two-column (sidebar nav + main content), RBAC check for admin role
    - [x] All layouts support both AR/EN locales
    - [x] RTL tested: `npm run dev` and manually switch locale
    - [x] Layouts use Nuxt UI components: `UCard`, `UButton`, `UMenu`
  - **Estimated Time:** 20 min
  - **Dependencies:** T002, T006, T008, T022, T023

- [x] **T026** [P] [US2] Create login page with Nuxt UI UForm
  - **File:** `frontend/pages/auth/login.vue`
  - **Acceptance Criteria:**
    - [x] Page uses `auth` layout
    - [x] UForm with fields: `email`, `password`
    - [x] Form validation via Zod schema
    - [x] Submit button calls `useApi().post('/api/v1/auth/login', ...)`
    - [x] On success: store token in Pinia, redirect to `/` (dashboard stub)
    - [x] On error: display error message below password field
    - [x] RTL support: form elements align correctly
    - [x] Page navigable via URL: `/auth/login`
  - **Estimated Time:** 18 min
  - **Dependencies:** T002, T006, T022, T023, T025

### Testing Setup (Frontend)

- [x] **T027** [P] [US2] Configure Vitest + Vue Test Utils

  - **Files:**
    - `frontend/vitest.config.ts` (already created in T009, now finalized)
    - `frontend/tests/unit/composables/useApi.test.ts`
    - `frontend/tests/unit/stores/user.test.ts`
  - **Acceptance Criteria:**
    - [x] Vitest configured with `environment: 'jsdom'`
    - [x] Vue Test Utils setup in config
    - [x] Example test for `UserStore` mounts and tests `setUser()`
    - [x] Example test for `useApi()` mocks HTTP calls
    - [x] Command succeeds: `npm run test` (at least 2 passing tests)
    - [x] Coverage reports generated (optional but desirable)
  - **Estimated Time:** 15 min
  - **Dependencies:** T002, T009

- [x] **T028** [P] [US2] Configure Playwright E2E testing
  - **Files:**
    - `frontend/playwright.config.ts`
    - `frontend/tests/e2e/smoke.spec.ts`
  - **Acceptance Criteria:**
    - [x] Playwright configured with `webServer: { command: 'npm run dev', port: 3000, ... }`
    - [x] Smoke test navigates to `/auth/login` and verifies page loads
    - [x] Smoke test fills email/password fields and submits (mock backend API if dev env)
    - [x] Command succeeds: `npm run test:e2e` (1 passing smoke test)
  - **Estimated Time:** 12 min
  - **Dependencies:** T002, T025, T026

---

## Phase 5 — DevOps & Tooling (Parallel)

**Gate:** All backend and frontend tasks complete.

### Docker & Local Development

- [x] **T029** [P] [US4] Create docker-compose.yml

  - **File:** `docker-compose.yml` (repo root)
  - **Acceptance Criteria:**
    - [x] Services: MySQL 8.0, Redis 7.0
    - [x] MySQL container: `MYSQL_ROOT_PASSWORD=root`, persistent volume (`db_data`)
    - [x] MySQL database `bunyan_dev` auto-created
    - [x] Redis container with persistent volume (`redis_data`)
    - [x] Networks configured for inter-service communication
    - [x] Command succeeds: `docker-compose up -d && docker-compose ps` (all services running)
    - [x] Command succeeds: `docker-compose down && docker-compose up -d` (idempotent)
  - **Estimated Time:** 15 min
  - **Dependencies:** None

- [x] **T030** [P] [US4] Create backend/Dockerfile + .dockerignore

  - **Files:**
    - `backend/Dockerfile`
    - `backend/.dockerignore`
  - **Acceptance Criteria:**
    - [x] Base image: `php:8.2-fpm-alpine` or `php:8.2-cli`
    - [x] Installs Composer
    - [x] Copies `composer.{json,lock}` and runs `composer install --no-dev`
    - [x] Copies application code
    - [x] Sets working directory to `/app`
    - [x] Exposes port 8000 (if used in Docker)
    - [x] `.dockerignore` excludes: `.git`, `node_modules`, `vendor`, `storage/logs`, `tests`
  - **Estimated Time:** 12 min
  - **Dependencies:** T001

- [x] **T031** [P] [US4] Create frontend/Dockerfile + .dockerignore
  - **Files:**
    - `frontend/Dockerfile`
    - `frontend/.dockerignore`
  - **Acceptance Criteria:**
    - [x] Base image: `node:18-alpine`
    - [x] Copies `package*.json` and runs `npm install --omit=dev`
    - [x] Runs `npm run build`
    - [x] Exposes port 3000
    - [x] `.dockerignore` excludes: `.git`, `node_modules`, `dist`, `tests`, `.nuxt`
  - **Estimated Time:** 12 min
  - **Dependencies:** T002

### Git Automation & Pre-Commit

- [x] **T032** [P] [US4] Install Husky + pre-commit hooks

  - **Files:**
    - `.husky/pre-commit`
    - `package.json` (`husky` + `lint-staged` in devDependencies)
    - `.lintstagedrc.json` or `lint-staged.config.js`
  - **Commands:**
    ```bash
    npm install -D husky lint-staged
    npx husky install
    npx husky add .husky/pre-commit "npx lint-staged"
    ```
  - **Acceptance Criteria:**
    - [x] `.husky/pre-commit` exists and is executable
    - [x] Husky initialized: `.husky/` directory exists
    - [x] `lint-staged` configured to run:
      - [x] PHP files: `php artisan pint` + `vendor/bin/phpstan analyze`
      - [x] JS/TS files: `npm run lint:fix` + `npm run typecheck`
    - [x] Pre-commit hook blocks commit if linting fails
    - [x] Manual test: Create lint error in PHP file, attempt commit → blocked
  - **Estimated Time:** 15 min
  - **Dependencies:** T001, T002

- [x] **T033** [P] [US4] Configure GitHub Actions CI workflow
  - **File:** `.github/workflows/ci.yml`
  - **Acceptance Criteria:**
    - [x] Workflow triggers on: `push` (all branches) and `pull_request`
    - [x] Jobs:
      - [x] **backend-lint:** `php artisan pint --test` + `vendor/bin/phpstan analyze`
      - [x] **backend-test:** `php artisan test --parallel`
      - [x] **frontend-lint:** `npm run lint` + `npm run typecheck`
      - [x] **frontend-test:** `npm run test` (vitest)
      - [x] **frontend-e2e:** `npm run test:e2e` (Playwright smoke test)
    - [x] Jobs can run in parallel via strategies
    - [x] Workflow passes for `main` branch commits
  - **Estimated Time:** 18 min
  - **Dependencies:** T001, T002, T021, T027, T028

### Environment Templates

- [x] **T034** [P] [US4] Create .env templates + config README
  - **Files:**
    - `backend/.env.example`
    - `frontend/.env.example`
    - `DEVELOPMENT_SETUP.md` (root directory)
  - **Acceptance Criteria:**
    - [x] `.env.example` (backend): All keys for MySQL, Redis, Sanctum, app name/domain
    - [x] `.env.example` (frontend): API_BASE_URL, default locale
    - [x] `DEVELOPMENT_SETUP.md` includes:
      - [x] Step 1: Clone repo, install dependencies
      - [x] Step 2: Copy .env files, configure locally (or use Docker Compose)
      - [x] Step 3: Run migrations, seed test data
      - [x] Step 4: Start services (`npm run dev` + `php artisan serve`)
      - [x] Step 5: Verify login at `http://localhost:3000`
      - [x] Step 6: Run tests (`npm run test`, `php artisan test`)
      - [x] Pre-commit hooks section
  - **Estimated Time:** 12 min
  - **Dependencies:** T001, T002

---

## Phase 6 — Validation & Integration

**Gate:** All backend, frontend, and DevOps tasks complete.

### Cross-Stack Integration Tests

- [x] **T035** [US1] [US2] Backend/Frontend integration smoke test
  - **Test Scenario:**
    1. Start backend: `php artisan serve`
    2. Start frontend dev server: `npm run dev`
    3. Navigate to `http://localhost:3000/auth/login`
    4. Submit login form with test credentials (or mock API)
    5. Verify token stored in Pinia + localStorage
    6. Verify user API call to `/api/v1/auth/me` succeeds
    7. Verify page redirects to dashboard (or home) on success
  - **Acceptance Criteria:**
    - [x] Login form submits without CORS errors
    - [x] API request includes `Authorization: Bearer` header
    - [x] Response follows error-contract format
    - [x] No browser console errors (TypeScript strict mode, no warnings)
    - [x] RTL UI renders correctly
  - **Estimated Time:** 20 min
  - **Dependencies:** T019, T026, T027

---

## Phase 7 — Repository Hygiene (Final)

**Gate:** All integration tests pass.

### Final Verification

- [x] **T036** Final pre-commit validation
  - **Commands:**

    ```bash
    # Backend
    cd backend && composer run lint && composer run analyze && composer run test

    # Frontend
    cd frontend && npm run lint && npm run typecheck && npm run test

    # Docker
    docker-compose up -d && docker-compose ps
    ```

  - **Acceptance Criteria:**
    - [x] Backend: All lint, analysis, and tests pass
    - [x] Frontend: All linting, type checking, and tests pass
    - [x] Docker: All services start and are healthy
    - [x] No uncommitted changes (git status clean)
  - **Estimated Time:** 10 min
  - **Dependencies:** All previous tasks

---

## Dependency Graph & Critical Path

### Critical Path Chain (Must Execute Sequentially)

```
T001 (Backend Scaffold)
  ├─→ T003 (MySQL Config)
  │     ├─→ T011 (Users Migration)
  │     │     └─→ T010 (User Model)
  │     │           └─→ T018 (RBAC Policies)
  │     │                 └─→ T019 (Auth Controller)
  │     │                       └─→ T020 (Auth Routes)
  │     │                             └─→ T021 (PHPUnit)
  │     └─→ T004 (Sanctum)
  │
  ├─→ T005 (PHPStan)
  │     └─→ T019 (Auth Controller)
  │
  ├─→ T012 (Exception Handler)
  ├─→ T013 (Base Controller)
  ├─→ T014 (Form Request)
  ├─→ T015 (API Resource)
  ├─→ T016 (Service Base)
  └─→ T017 (Repository Pattern)

T002 (Frontend Scaffold)
  ├─→ T006 (@nuxt/ui)
  │     ├─→ T008 (RTL)
  │     └─→ T025 (Layouts)
  │           └─→ T026 (Login Page)
  │
  ├─→ T007 (TypeScript + i18n)
  │     ├─→ T024 (i18n Composable)
  │     └─→ T025 (Layouts)
  │
  ├─→ T009 (ESLint + Vitest)
  │     ├─→ T027 (Vitest Config)
  │     └─→ T028 (Playwright)
  │
  ├─→ T022 (Pinia Stores)
  │     └─→ T023 (API Composable)
  │           └─→ T026 (Login Page)
  │
  └─→ T025 (Layouts)
```

### Parallelization Strategy

**Wave 1 (Phase 1):** T001 ∥ T002 (5 min each, 5 min total)

- Backend and Frontend scaffold in parallel

**Wave 2 (Phase 2):** After T001 ∥ After T002

- Backend: T003 ∥ T004 ∥ T005 (10-12 min, 12 min total)
- Frontend: T006 ∥ T007 ∥ T009 (12 min each, 12 min total)
- **Total Wave 2:** 12 min (parallel)

**Wave 3 (Phase 3):** After Wave 2 Backend Complete

- T010 ∥ T012 ∥ T013 ∥ T014 ∥ T015 ∥ T016 (parallel scaffolding, 20 min)
- T011 (sequential after T003, 15 min concurrent with T010-T016)
- T017 (after T010+T011, 15 min)
- T018 (after T010+T017, 18 min)
- T019 (after T018+T013, 25 min)
- T020 (after T019, 12 min)
- T021 (after T011+T019, 20 min)
- **Total Wave 3:** ~65 min (sequential critical path)

**Wave 4 (Phase 4):** After Wave 2 Frontend Complete

- T022 ∥ T023 ∥ T024 (parallel, 10-18 min)
- T025 (after T006+T008+T022, 20 min)
- T026 (after T025+T023, 18 min)
- T027 ∥ T028 (parallel, 12-15 min)
- **Total Wave 4:** ~35 min (sequential critical path)

**Wave 5 (Phase 5 - DevOps):** Can start after T001 ∥ T002

- T029 ∥ T030 ∥ T031 ∥ T032 ∥ T033 ∥ T034 (all parallel, 15-18 min)
- **Total Wave 5:** 18 min (fully parallel)

**Wave 6 (Phase 6-7):** After all waves, sequential

- T035 (integration test, 20 min)
- T036 (final validation, 10 min)
- **Total Wave 6:** 30 min

---

## Summary

### Metrics

| Metric                                | Value      |
| ------------------------------------- | ---------- |
| **Total Tasks**                       | 36         |
| **Backend Tasks**                     | 14         |
| **Frontend Tasks**                    | 10         |
| **DevOps/Tooling Tasks**              | 6          |
| **Integration/Validation Tasks**      | 2          |
| **Parallelizable Tasks (marked [P])** | 26         |
| **Sequential Critical Path**          | ~110 min   |
| **Optimal Parallel Duration**         | ~75–80 min |
| **4-Person Team Parallel**            | ~30–40 min |

### Critical Path Trace

**Longest Dependency Chain (determines min duration):**

```
T001 → T003 → T011 → T010 → T018 → T019 → T020 → T021 → T035 → T036
(5 + 10 + 15 + 20 + 18 + 25 + 12 + 20 + 20 + 10) = 155 minutes (serial)
```

**With Optimal Parallelization (4-person team):**

```
Team A (Backend):   T001 (5) → T003 (10) → T011 (15) → T010+T012+ (20 parallel)→ T018 (18) → T019 (25) → T020 (12) → T021 (20)
Team B (Frontend):  T002 (5) → T006 (12) → T025 (20) → T026 (18) → T027 (15)
Team C (DevOps):    T029–T034 (18 parallel)
Team D (QA):        T035 (20) → T036 (10)

Total: ~75–80 minutes
```

### Task Execution Order (speckit.implement input)

All 36 tasks listed sequentially above with exact file paths. speckit.implement will:

1. Execute phases sequentially
2. Allow parallel execution within phases (marked [P])
3. Mark completed checkpoints with `[X]`
4. Generate commit descriptions per completed phase
5. Auto-commit to feature branch upon completion
