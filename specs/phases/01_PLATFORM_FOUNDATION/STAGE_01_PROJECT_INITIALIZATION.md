# STAGE_01 — Project Initialization

> **Phase:** 01_PLATFORM_FOUNDATION
> **Status:** PRODUCTION READY
> **Scope:** Laravel + Nuxt.js monorepo setup, tooling, CI foundation
> **Risk Level:** LOW

## Stage Status

Status: PRODUCTION READY
Step: stage_production_ready
Risk Level: LOW
Closure Date: 2026-04-10T11:00:00Z

Scope Closed:

- Laravel backend scaffold (services, repositories, Form Requests, API resources)
- Initial migrations and seeds for core entities
- Sanctum authentication and RBAC middleware skeletons
- Nuxt 3 frontend shell, Nuxt UI integration, auth pages, and Pinia stores
- Unit and feature tests for core flows; Vitest for frontend

Architecture Governance Compliance:

- ADR alignment verified
- RBAC enforcement confirmed
- Service layer architecture maintained (thin controllers → services → repositories)
- Error contract compliance verified (API response format)

Notes:
Stage is production ready. No structural modifications allowed. Modifications require a new stage.

## Objective

Initialize the Bunyan project with Laravel backend and Nuxt.js 3 frontend. Configure development tooling, linting, testing frameworks, and CI pipeline foundation.

## Scope

### Backend (Laravel)

- Laravel project initialization with PHP 8.2+
- MySQL database connection configuration
- Laravel Sanctum setup (auth scaffold)
- PHPUnit test configuration
- Laravel Pint (linting) configuration
- PHPStan (static analysis) configuration
- Environment configuration (.env structure)
- Base exception handler with error contract
- Base API controller with standard response format

### Frontend (Nuxt.js)

- Nuxt.js 3 project initialization
- **Nuxt UI** (`@nuxt/ui`) module setup — Tailwind CSS v4-powered component library
- RTL support via Nuxt UI `dir="rtl"` and Tailwind logical properties
- Pinia store setup
- Vitest unit test configuration
- **Playwright E2E test** configuration (`@playwright/test` + `@nuxt/test-utils`)
- ESLint configuration (with `@nuxt/eslint`)
- i18n module setup (`@nuxtjs/i18n` — Arabic + English)
- Base layout structure (default, auth, admin)

### Testing Strategy

| Layer              | Tool                    | Scope                     |
| ------------------ | ----------------------- | ------------------------- |
| Unit (composables) | Vitest                  | Business logic, utilities |
| Component          | Vitest + Vue Test Utils | Component behavior        |
| Integration (API)  | PHPUnit Feature Tests   | API contracts, RBAC       |
| E2E (user flows)   | Playwright              | Critical user journeys    |
| Visual Regression  | Playwright screenshots  | UI consistency (optional) |

### Code Formatting & Linting

#### Backend (Laravel)

| Tool         | Purpose                      | Config File         | Command             |
| ------------ | ---------------------------- | ------------------- | ------------------- |
| PHP-CS-Fixer | Code formatting (PHP)        | `.php-cs-fixer.php` | `composer lint:fix` |
| PHPStan      | Static analysis              | `phpstan.neon`      | `composer analyze`  |
| Laravel Pint | PSR-12 compliance (optional) | `pint.json`         | `vendor/bin/pint`   |

**Composer scripts** (`composer.json`):

```json
{
  "scripts": {
    "lint": "php-cs-fixer fix --dry-run --diff",
    "lint:fix": "php-cs-fixer fix",
    "analyze": "phpstan analyse --memory-limit=512M",
    "test": "php artisan test",
    "test:coverage": "php artisan test --coverage",
    "dev": "php artisan serve"
  }
}
```

#### Frontend (Nuxt.js)

| Tool       | Purpose                        | Config File        | Command                |
| ---------- | ------------------------------ | ------------------ | ---------------------- |
| ESLint     | Code quality + linting (JS/TS) | `.eslintrc.json`   | `npm run lint`         |
| Prettier   | Code formatting (Vue/CSS/JSON) | `.prettierrc.json` | `npx prettier --write` |
| TypeScript | Type checking                  | `tsconfig.json`    | `npx nuxi typecheck`   |

**npm scripts** (`package.json`):

```json
{
  "scripts": {
    "dev": "nuxt dev",
    "build": "nuxt build",
    "preview": "nuxt preview",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "format": "prettier --write \"./**/*.{vue,ts,js,json,css}\"",
    "typecheck": "nuxt typecheck",
    "test": "vitest run",
    "test:watch": "vitest"
  }
}
```

### Pre-Commit Hooks

#### Husky + lint-staged Setup

**Purpose:** Automatically lint and format code before commits, preventing violations from entering the repository.

**Backend Hooks** (`.husky/pre-commit`):

```bash
#!/bin/sh
cd backend && \
php-cs-fixer fix --dry-run --diff && \
vendor/bin/phpstan analyse --memory-limit=512M && \
php artisan test --parallel
```

**Frontend Hooks** (`.husky/pre-commit`):

```bash
#!/bin/sh
cd frontend && \
npm run lint:fix && \
npx prettier --write . && \
npx nuxi typecheck && \
npm run test
```

**lint-staged Configuration** (`.lintstagedrc.json`):

```json
{
  "backend/app/**/*.php": ["php-cs-fixer fix", "phpstan analyse"],
  "frontend/**/*.{vue,ts,js}": ["eslint --fix", "prettier --write"],
  "frontend/**/*.json": ["prettier --write"]
}
```

#### GitHub Actions Pre-Commit Guard

**File:** `.github/workflows/pre-commit-guard.yml`

This workflow runs before merge to catch any commits that bypass local hooks:

- PHP-CS-Fixer validation (no changes allowed)
- PHPStan static analysis (zero tolerance)
- ESLint linting (no warnings in production code)
- Prettier formatting check
- TypeScript type check
- Unit test pass rate (≥80% coverage for new files)

### Local Development Setup

#### Installation & Configuration

```bash
# 1. Install Node + PHP (macOS with Homebrew)
brew install php@8.2 node mysql redis

# 2. Backend setup
cd backend
composer install
composer run lint:fix
cp .env.example .env
php artisan key:generate
php artisan migrate

# 3. Frontend setup
cd ../frontend
npm install
npm run lint:fix
npm run format

# 4. Git hooks
npx husky install
```

#### Development Scripts

**Start local services (Docker Compose):**

```bash
docker-compose up -d  # MySQL, Redis, Node watcher
php artisan serve     # Laravel API on http://localhost:8000
npm run dev           # Nuxt dev server on http://localhost:3000
```

**Before Committing:**

```bash
cd backend && composer lint:fix && composer analyze && composer test
cd frontend && npm run lint:fix && npm run format && npm run typecheck && npm run test
```

### CI Validation Pipeline

#### Pre-Commit Validation (runs on PR)

| Stage           | Backend                  | Frontend           | Fails On  |
| --------------- | ------------------------ | ------------------ | --------- |
| Linting         | `php-cs-fixer --dry-run` | `eslint .`         | Any fix   |
| Formatting      | —                        | `prettier --check` | Any diffs |
| Static Analysis | `phpstan analyse`        | `nuxi typecheck`   | Errors    |
| Unit Tests      | `php artisan test`       | `npm run test`     | Failures  |
| E2E Tests       | —                        | `npm run test:e2e` | Failures  |

#### Validation Commands (Local)

```bash
# Full validation pipeline (what CI runs)
composer run lint && composer run analyze && composer run test && \
npm run lint && npm run typecheck && npm run test

# Quick pre-commit check
npm run lint:fix && npm run format && composer lint:fix
```

### DevOps

- Docker Compose for local development (PHP 8.2, MySQL 8.0, Redis 7, Node 20)
- GitHub Actions CI pipeline (backend + frontend + E2E jobs)
- Husky pre-commit hooks (automatic lint, format, test)
- lint-staged for incremental validation (only changed files)
- Pre-commit guard workflow on PR (zero-tolerance linting)
- Playwright browsers installed in CI (`chromium`, `firefox`)
- Environment files (.env.example, .env.ci)

## Dependencies

### Package Dependencies

#### Backend (composer.json)

| Package           | Type    | Purpose                    |
| ----------------- | ------- | -------------------------- |
| laravel/framework | Runtime | Core framework             |
| laravel/sanctum   | Runtime | API authentication         |
| php-cs-fixer      | Dev     | Code formatting            |
| phpstan/phpstan   | Dev     | Static analysis            |
| laravel/pint      | Dev     | PSR-12 compliance checking |
| phpunit/phpunit   | Dev     | Unit testing framework     |
| pestphp/pest      | Dev     | Alternative test framework |

#### Frontend (package.json)

| Package          | Type    | Purpose                      |
| ---------------- | ------- | ---------------------------- |
| nuxt             | Runtime | Nuxt.js 3 framework          |
| @nuxt/ui         | Runtime | Nuxt UI component library    |
| @nuxtjs/i18n     | Runtime | Internationalization (ar/en) |
| pinia            | Runtime | State management             |
| vee-validate     | Runtime | Form validation              |
| zod              | Runtime | Schema validation            |
| @vueuse/core     | Runtime | Vue utilities                |
| eslint           | Dev     | Linting                      |
| prettier         | Dev     | Code formatting              |
| typescript       | Dev     | Type checking                |
| vitest           | Dev     | Unit test framework          |
| @vue/test-utils  | Dev     | Vue component testing        |
| @playwright/test | Dev     | E2E testing                  |
| @nuxt/eslint     | Dev     | Nuxt-specific ESLint rules   |

### Husky Dependencies (Root)

```json
{
  "devDependencies": {
    "husky": "^9.0.0",
    "lint-staged": "^15.0.0"
  }
}
```

### Installation Checklist

- [ ] Backend dependencies: `cd backend && composer install`
- [ ] Frontend dependencies: `cd frontend && npm install`
- [ ] Root dependencies: `npm install` (for Husky)
- [ ] Git hooks: `npx husky install`
- [ ] Verify hooks: `ls -la .husky/`
- [ ] Test lint pre-commit: `git add . && git commit --no-verify && git reset HEAD~1`

## Validation & Enforcement

### Local Validation

**Before pushing to GitHub:**

```bash
# Complete validation (mimics CI)
composer run lint && \
composer run analyze && \
npm run lint && \
npm run format && \
npm run typecheck && \
composer run test && \
npm run test
```

### Script Consistency Rules

**Backend scripts must adhere to:**

- `lint` — dry-run check (no modifications)
- `lint:fix` — apply fixes
- `analyze` — static analysis (PHPStan)
- `test` — unit tests
- `test:coverage` — with coverage report
- `dev` — start local server

**Frontend scripts must adhere to:**

- `lint` — linting check (no modifications)
- `lint:fix` — apply fixes
- `format` — code formatting (Prettier)
- `typecheck` — TypeScript type checking
- `test` — unit/component tests
- `test:watch` — watch mode
- `test:e2e` — end-to-end tests
- `dev` — start dev server
- `build` — production build

### CI Enforcement

These scripts CANNOT be bypassed:

- Pre-commit hooks are enforced via GitHub Actions
- Force-push to `main` or `develop` is blocked
- All checks must pass before PR merge
- Code coverage must meet thresholds

## Deliverables

### Configuration Files

- `.php-cs-fixer.php` — PHP formatting rules
- `phpstan.neon` — PHPStan static analysis config
- `.eslintrc.json` — ESLint rules
- `.prettierrc.json` — Prettier formatting config
- `.husky/pre-commit` — Pre-commit hook script
- `.lintstagedrc.json` — lint-staged configuration
- `docker-compose.yml` — Local development stack
- `.env.example` — Environment template
- `.env.ci` — CI environment variables

### Scripts (composer.json + package.json)

- Backend: lint, lint:fix, analyze, test, test:coverage, dev
- Frontend: lint, lint:fix, format, typecheck, test, test:watch, test:e2e, dev, build

### Git Hooks

- `.husky/pre-commit` — Validates code before commit
- GitHub Actions workflow validates on PR

### Documentation

- [STAGE_01_PROJECT_INITIALIZATION.md](.) — This file
- Setup guide in README.md
- Contribution guide (CONTRIBUTING.md)

### Upstream

- **Upstream:** None (first stage)
- **Downstream:** All subsequent stages
