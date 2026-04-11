# STAGE_01 — Project Initialization

## Requirements Checklist

---

## Backend Requirements (Laravel)

### Project Structure & Configuration

- [ ] `backend/` directory created at repo root
- [ ] Laravel 11.x installed via Composer
- [ ] PHP version requirement: 8.2+ verified in `composer.json` engines
- [ ] `.env.example` template exists with documented keys
- [ ] `config/app.php` locale defaults to `en` (i18n switchable in STAGE_30)
- [ ] `config/database.php` MySQL connection primary
- [ ] CORS configured in `config/cors.php` (frontend origin added)
- [ ] `APP_KEY` generated via `php artisan key:generate`
- [ ] `storage/` and `bootstrap/cache/` directories writable

### Composer Dependencies (Core)

- [ ] `laravel/framework` v11
- [ ] `laravel/sanctum` (auth scaffold)
- [ ] `laravel/pint` (PSR-12 linting)
- [ ] `phpstan/phpstan` (static analysis)
- [ ] `php-cs-fixer/php-cs-fixer` (code formatting)
- [ ] `phpunit/phpunit` (testing framework)
- [ ] `laravel/tinker` (REPL)
- [ ] `barryvdh/laravel-debugbar` (dev dependency)

### Composer Scripts (package.json scripts equivalent)

**File:** `backend/composer.json`

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

- [ ] `composer run lint` validates formatting
- [ ] `composer run lint:fix` auto-fixes formatting
- [ ] `composer run analyze` runs PHPStan (L9)
- [ ] `composer run test` runs PHPUnit
- [ ] `composer run test:coverage` generates coverage report
- [ ] `composer run dev` starts dev server on http://localhost:8000

### Exception Handling & Error Response

**File:** `backend/app/Exceptions/Handler.php`

- [ ] Base exception handler catches all exceptions
- [ ] Returns JSON response for all errors (no HTML fallback)
- [ ] Error response follows contract: `{ success, data, message, errors }`
- [ ] HTTP status codes mapped correctly (400, 401, 403, 404, 422, 500)
- [ ] Validation errors flattened into `errors` object
- [ ] Stack traces hidden in production (visible in dev)
- [ ] Logs errors with structured format (timestamp, user, endpoint)

### Base API Controller

**File:** `backend/app/Http/Controllers/Api/BaseController.php`

- [ ] `response()` method returns standardized JSON
- [ ] Success response: `response($data, 'message', 200)`
- [ ] Error response: `errorResponse('msg', $errors, 422)`
- [ ] All API controllers extend `BaseController`

### Authentication Framework (Sanctum)

- [ ] `php artisan vendor:publish --provider=LaravelSanctum` executed
- [ ] `config/sanctum.php` configured with STATEFUL domains
- [ ] Middleware registered in `app/Http/Middleware/`
- [ ] User model prepared for tokens (migration placeholder)
- [ ] API token endpoints ready (login/logout to be implemented in STAGE_03)

### Validation Framework (Form Requests)

**File:** `backend/app/Http/Requests/BaseFormRequest.php`

- [ ] Base class extends `FormRequest`
- [ ] `authorize()` returns true (RBAC checks deferred to STAGE_04)
- [ ] `failedValidation()` returns JSON response
- [ ] All API Form Requests extend `BaseFormRequest`

### API Response Resources

**File:** `backend/app/Http/Resources/BaseResource.php`

- [ ] Wraps model data in standard format
- [ ] Includes pagination metadata when applicable
- [ ] Relationships loaded eagerly (eager-load scopes)

### Database & Migrations

**File:** `backend/database/`

- [ ] `database/migrations/` directory exists and is empty (migrations start in STAGE_02)
- [ ] `database/seeders/DatabaseSeeder.php` exists (empty seeders OK)
- [ ] `database/factories/` directory exists
- [ ] `php artisan migrate:fresh` runs successfully
- [ ] MySQL collation: `utf8mb4_unicode_ci` default

### Eloquent ORM Pattern Foundation

**File:** `backend/app/Models/`

- [ ] Base model placeholder prepared (User model deferred to STAGE_02)
- [ ] Relationships use `belongsTo()`, `hasMany()`, `morphTo()` syntax
- [ ] Scopes implemented as methods (not raw queries in controllers)
- [ ] No direct Eloquent queries in controllers (repository pattern)

### Service Layer Foundation

**File:** `backend/app/Services/`

- [ ] Directory created with sample service template
- [ ] Services accept repositories via constructor injection
- [ ] All business logic isolated (controllers delegate to services)
- [ ] Services return plain data (no HTTP responses)

### Repository Pattern Foundation

**File:** `backend/app/Repositories/`

- [ ] Directory created with sample repository template
- [ ] All database queries encapsulated in repositories
- [ ] Repositories return Eloquent models or collections
- [ ] Eager loading scopes prevent N+1 queries

### Middleware & Policies

- [ ] `app/Http/Middleware/` directory prepared
- [ ] Base RBAC middleware template created (`CheckRole.php` placeholder)
- [ ] `app/Policies/` directory created
- [ ] `composer run analyze` passes with baseline PHPStan config

### Enums

**File:** `backend/app/Enums/UserRole.php`

```php
enum UserRole: string {
    case CUSTOMER = 'customer';
    case CONTRACTOR = 'contractor';
    case SUPERVISING_ARCHITECT = 'supervising_architect';
    case FIELD_ENGINEER = 'field_engineer';
    case ADMIN = 'admin';
}
```

- [ ] UserRole enum defined with 5 roles
- [ ] Enum used for type safety (not raw strings)

### Testing Setup

**File:** `backend/phpunit.xml`

- [ ] SQLite in-memory database for testing (optional: MySQL in Docker)
- [ ] `tests/Feature/` directory created
- [ ] `tests/Unit/` directory created
- [ ] Database transactions enabled for feature tests
- [ ] Parallel testing configured (optional)
- [ ] Example test file created and passes

### Code Quality Configuration

**File:** `backend/.php-cs-fixer.php`

- [ ] PSR-12 rules enabled
- [ ] Auto-fixes common formatting issues
- [ ] `composer run lint:fix` modifies files in place

**File:** `backend/phpstan.neon`

- [ ] Level 9 configured (strict analysis)
- [ ] Baseline generated after initial run
- [ ] `composer run analyze` passes with 0 errors

### Environment Variables

**File:** `backend/.env.example`

```
APP_NAME=Bunyan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bunyan
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie
```

- [ ] All required keys documented
- [ ] No actual secrets (use `.env.local` for overrides)

### Build & Deployment Files

- [ ] `Dockerfile` created for production image
- [ ] `.dockerignore` excludes dev files

---

## Frontend Requirements (Nuxt.js)

### Project Structure & Configuration

- [ ] `frontend/` directory created at repo root
- [ ] Nuxt 3 installed via npm
- [ ] Node version specified in `.nvmrc` (18.x LTS minimum)
- [ ] `package.json` `engines.node` field populated
- [ ] `nuxt.config.ts` with RTL support and Nuxt UI preset

### npm Dependencies (Core)

- [ ] `nuxt` v3.12+
- [ ] `vue` v3.4+
- [ ] `@nuxt/ui` (Tailwind-powered components)
- [ ] `pinia` (state management)
- [ ] `@nuxtjs/i18n` (Arabic/English localization)
- [ ] `vitest` (unit testing)
- [ ] `@nuxt/test-utils` (Nuxt testing utilities)
- [ ] `@playwright/test` (E2E testing)
- [ ] `eslint` + `@nuxt/eslint` (linting)
- [ ] `prettier` (formatting)
- [ ] `typescript` (type checking)
- [ ] `@vueuse/core` (utility composables)

### npm Scripts (Development Workflow)

**File:** `frontend/package.json`

```json
{
  "scripts": {
    "dev": "nuxt dev",
    "build": "nuxt build",
    "preview": "nuxt preview",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "format": "prettier --write \"./**/*.{vue,ts,js,json,css}\"",
    "typecheck": "nuxi typecheck",
    "test": "vitest run",
    "test:watch": "vitest"
  }
}
```

- [ ] `npm run dev` starts Nuxt dev server on http://localhost:3000
- [ ] `npm run build` builds for production
- [ ] `npm run preview` previews production build
- [ ] `npm run lint` validates code quality (zero errors)
- [ ] `npm run lint:fix` auto-fixes ESLint violations
- [ ] `npm run format` formats code with Prettier
- [ ] `npm run typecheck` validates TypeScript strict mode
- [ ] `npm run test` runs Vitest unit tests
- [ ] `npm run test:watch` continuous test mode

### Nuxt Configuration

**File:** `frontend/nuxt.config.ts`

```typescript
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  ui: {
    global: true,
  },
  i18n: {
    locales: [
      { code: 'ar', iso: 'ar-SA', dir: 'rtl' },
      { code: 'en', iso: 'en-US', dir: 'ltr' },
    ],
    defaultLocale: 'ar',
    strategy: 'prefix_except_default',
  },
});
```

- [ ] `@nuxt/ui` module configured
- [ ] `@nuxtjs/i18n` configured with Arabic & English
- [ ] Default locale: `ar` (Arabic)
- [ ] Locale switching via `useI18n()` composable

### TypeScript Configuration

**File:** `frontend/tsconfig.json`

```json
{
  "compilerOptions": {
    "strict": true,
    "skipLibCheck": true,
    "target": "ES2020",
    "moduleResolution": "bundler",
    "lib": ["ES2020", "DOM", "DOM.Iterable"]
  }
}
```

- [ ] `strict: true` enforced (no implicit any)
- [ ] All Vue components typed
- [ ] All props, emits, slots typed

### Tailwind CSS Configuration

**File:** `frontend/tailwind.config.ts`

```typescript
export default {
  content: ['./components/**/*.{js,vue,ts}', './pages/**/*.{js,vue,ts}'],
  theme: {
    extend: {},
  },
  plugins: [],
};
```

- [ ] Tailwind CSS v4 integrated
- [ ] Logical properties enabled (margin-inline, padding-block, etc.)
- [ ] RTL preset configured

### Layout Components

- [ ] `layouts/default.vue` — Main layout (header, nav, footer, content area)
- [ ] `layouts/auth.vue` — Authentication layout (login/register pages)
- [ ] `layouts/admin.vue` — Admin panel layout (sidebar, top bar)
- [ ] Each layout uses `<slot />` for page content

### Pages & Navigation

- [ ] `pages/` directory created
- [ ] `app.vue` root component (selects layout)
- [ ] `pages/index.vue` home page placeholder
- [ ] `pages/[...404].vue` 404 fallback

### Composables (Reusable Logic)

**File:** `composables/api.ts`

- [ ] Provides `useLaravelApi()` composable for API calls
- [ ] Handles authorization headers
- [ ] Returns standardized response format (data, errors)
- [ ] Handles error responses with proper status mapping

**File:** `composables/i18n.ts`

- [ ] Helper `useLocale()` for switching locales
- [ ] Helper `$t()` for translation key access

**File:** `composables/auth.ts`

- [ ] Placeholder for auth logic (implemented in STAGE_03)

### State Management (Pinia)

**File:** `stores/`

- [ ] Base store template created
- [ ] User store placeholder (auth state deferred to STAGE_03)
- [ ] All stores are named (scoped via auto-import)

### Components

**File:** `components/`

- [ ] Base component template created
- [ ] Nuxt UI components imported and ready (UButton, UCard, UForm, etc.)
- [ ] All components use Vue 3 Composition API (`<script setup>`)

### Localization

**File:** `locales/ar.json`

```json
{
  "common": {
    "button": {
      "submit": "إرسال",
      "cancel": "إلغاء",
      "save": "حفظ"
    }
  }
}
```

**File:** `locales/en.json`

```json
{
  "common": {
    "button": {
      "submit": "Submit",
      "cancel": "Cancel",
      "save": "Save"
    }
  }
}
```

- [ ] Arabic translations in `locales/ar.json`
- [ ] English translations in `locales/en.json`
- [ ] Translation keys scoped (namespace.key format)
- [ ] No hardcoded text in components

### Testing Setup

**File:** `frontend/vitest.config.ts`

- [ ] Vitest configured for unit testing
- [ ] Vue Test Utils integrated
- [ ] `tests/` or `__tests__/` directory created
- [ ] Sample component test passes

**File:** `frontend/playwright.config.ts`

- [ ] Playwright configured for E2E testing
- [ ] Base URL set to http://localhost:3000
- [ ] Sample smoke test passes (visit home, check page loads)

### Code Quality

**File:** `frontend/.eslintrc.json`

```json
{
  "extends": ["@nuxt/eslint-config"],
  "parser": "@typescript-eslint/parser",
  "parserOptions": {
    "sourceType": "module"
  }
}
```

- [ ] ESLint extends `@nuxt/eslint-config`
- [ ] TypeScript parser enabled
- [ ] `npm run lint` passes with 0 errors

**File:** `frontend/.prettierrc.json`

```json
{
  "semi": false,
  "singleQuote": true,
  "arrowParens": "always",
  "trailingComma": "es5"
}
```

- [ ] Prettier configured (semicolons off per Nuxt style)
- [ ] `npm run format` formats all files

### RTL Support

- [ ] HTML `dir` attribute dynamically respects locale (RTL for Arabic)
- [ ] CSS uses Tailwind logical properties (`margin-inline`, `padding-block`, etc.)
- [ ] Flexbox/grid layouts work correctly RTL (no fixed `left`/`right`)
- [ ] Form inputs RTL-compatible
- [ ] Visual regression: test in both LTR and RTL modes

### Environment Variables

**File:** `frontend/.env.example`

```
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000/api/v1
NUXT_PUBLIC_DEFAULT_LOCALE=ar
```

- [ ] API base URL configurable
- [ ] Default locale configurable
- [ ] All env vars documented

---

## DevOps & Tooling Requirements

### Git & Version Control

**File:** `.gitignore`

- [ ] Backend: `vendor/`, `node_modules/`, `.env`, `storage/`, `bootstrap/cache/`
- [ ] Frontend: `node_modules/`, `.nuxt/`, `dist/`, `.env.local`
- [ ] Global ignores: `.DS_Store`, `*.log`, `.vscode/`

### Pre-Commit Hooks (Husky + lint-staged)

**File:** `.husky/pre-commit`

```bash
#!/bin/sh
cd backend && \
php-cs-fixer fix --dry-run --diff && \
vendor/bin/phpstan analyse --memory-limit=512M && \
php artisan test --parallel

cd ../frontend && \
npm run lint:fix && \
npx prettier --write . && \
npx nuxi typecheck && \
npm run test
```

- [ ] Husky installed via `npx husky install`
- [ ] Pre-commit hook prevents commits on linting/test failures
- [ ] Backend checks: Pint, PHPStan, PHPUnit
- [ ] Frontend checks: ESLint, Prettier, TypeScript, Vitest

**File:** `.lintstagedrc.json`

```json
{
  "backend/app/**/*.php": ["php-cs-fixer fix", "phpstan analyse"],
  "frontend/**/*.{vue,ts,js}": ["eslint --fix", "prettier --write"],
  "frontend/**/*.json": ["prettier --write"]
}
```

- [ ] Selective file linting (only changed files)

### Docker & Local Development

**File:** `docker-compose.yml`

```yaml
version: '3.8'
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: bunyan
      MYSQL_ROOT_PASSWORD: root
    ports:
      - '3306:3306'
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - '6379:6379'

  node:
    image: node:18-alpine
    working_dir: /app/frontend
    volumes:
      - .:/app
    command: npm run dev
    ports:
      - '3000:3000'

volumes:
  mysql_data:
```

- [ ] MySQL 8.x service defined
- [ ] Redis service defined
- [ ] Node service optional (developers can run locally)
- [ ] All commands: `docker-compose up -d`, `docker-compose ps`, `docker-compose down`

### CI/CD Pipeline Foundation

**File:** `.github/workflows/pre-commit-guard.yml`

```yaml
name: Pre-Commit Guard
on: [pull_request, push]
jobs:
  backend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/setup-php@v3
        with:
          php-version: 8.2
          extensions: mysql, redis
      - run: cd backend && composer install
      - run: cd backend && composer run lint
      - run: cd backend && composer run analyze
      - run: cd backend && composer run test

  frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 18
      - run: cd frontend && npm install
      - run: cd frontend && npm run lint
      - run: cd frontend && npm run typecheck
      - run: cd frontend && npm run test
```

- [ ] GitHub Actions workflow created
- [ ] Runs on pull_request and push events
- [ ] Backend: Lint, PHPStan, PHPUnit
- [ ] Frontend: ESLint, TypeScript, Vitest
- [ ] Workflow status visible in PR checks

### Monorepo Structure

```
bunyan-app/
├── backend/                  # Laravel API
│   ├── app/
│   ├── database/
│   ├── routes/
│   ├── tests/
│   ├── composer.json
│   └── phpunit.xml
├── frontend/                 # Nuxt app
│   ├── components/
│   ├── pages/
│   ├── stores/
│   ├── package.json
│   └── vitest.config.ts
├── docs/                     # Documentation
├── .github/
│   └── workflows/
├── .husky/                   # Git hooks
├── .gitignore
└── README.md
```

- [ ] Directories present and properly named
- [ ] Monorepo tools configured (Composer workspaces optional)

### Documentation

**File:** `README.md`

Sections:

1. Project Overview
2. Tech Stack
3. Quick Start (Installation & Setup)
4. Development Commands
5. Testing
6. Linting & Formatting
7. Docker Setup
8. Troubleshooting
9. Architecture Overview
10. Contributing Guidelines

- [ ] README includes step-by-step setup instructions
- [ ] Commands documented (dev, build, test, lint)
- [ ] Docker commands explained
- [ ] Troubleshooting section with common issues
- [ ] Link to DESIGN.md for UI conventions

---

## Integration & Validation

### Cross-Layer Communication

- [ ] Frontend: API composable calls `http://localhost:8000/api/v1`
- [ ] Backend: Returns JSON responses per error contract
- [ ] CORS middleware allows `localhost:3000` (frontend origin)
- [ ] Sanctum middleware ready for token-based auth

### Local Development Workflow

- [ ] Developer runs: `git clone` → `npm install` (root) → `composer install` (backend)
- [ ] Developer runs: `docker-compose up -d` (services)
- [ ] Developer runs: `npm run dev` (frontend) + `composer run dev` (backend)
- [ ] Both services accessible: http://localhost:3000 and http://localhost:8000

### Pre-Merge Checklist

- [ ] `composer run lint:fix && composer run analyze && composer run test` passes
- [ ] `npm run lint:fix && npm run typecheck && npm run test` passes
- [ ] `npm run build` completes successfully
- [ ] GitHub Actions workflow passes all checks
- [ ] Docker Compose services start without errors
- [ ] Documentation (README.md) complete and accurate
- [ ] No hardcoded secrets in codebase
- [ ] All config files included in version control (except `.env`)

---

## Sign-Off

- **Specification:** `spec.md` finalized
- **Requirements:** All checkboxes reviewed and completed
- **Acceptance:** Meets all 4 user story acceptance criteria
- **Ready for Implementation:** YES ✓
