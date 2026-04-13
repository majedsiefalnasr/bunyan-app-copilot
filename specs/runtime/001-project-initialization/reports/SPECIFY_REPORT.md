# SPECIFY_REPORT — Project Initialization

**Stage:** Project Initialization  
**Phase:** 01_PLATFORM_FOUNDATION  
**Branch:** spec/001-project-initialization  
**Completed:** 2026-04-10T00:00:00Z

---

## Specification Summary

### Objective

Initialize Bunyan full-stack construction marketplace with:

- Laravel 11 backend (PHP 8.2+)
- Nuxt.js 3 frontend (Vue 3 Composition API)
- Clean architecture foundation (service layer, repositories, RBAC)
- Complete development tooling (linting, testing, CI/CD)

### Specification Status: ✅ COMPLETE

| Component           | Status | Coverage                                                                |
| ------------------- | ------ | ----------------------------------------------------------------------- |
| Objective           | ✅     | Platform initialization with architecture foundation                    |
| User Stories        | ✅     | 4 stories: Backend Setup, Frontend Setup, Clean Architecture, Local Dev |
| Backend Scope       | ✅     | Laravel, Sanctum, PHPUnit, Linting, RBAC foundation, error contracts    |
| Frontend Scope      | ✅     | Nuxt 3, Nuxt UI, RTL, i18n, Pinia, Vitest, Playwright                   |
| DevOps Scope        | ✅     | Docker Compose, Husky, GitHub Actions, lint-staged                      |
| Dependencies        | ✅     | Foundation stage (no upstream), 8 downstream stages                     |
| Acceptance Criteria | ✅     | 65+ items across all layers                                             |

---

## User Stories (4)

### US1 — Backend Developer Setup

**Goal:** Install and configure fresh Laravel project with all development tooling  
**Acceptance Criteria:** 10 items — Composer scripts, exception handler, base controller, error format  
**Status:** ✅ Fully specified

### US2 — Frontend Developer Setup

**Goal:** Install and configure fresh Nuxt.js 3 with Nuxt UI, RTL, E2E testing  
**Acceptance Criteria:** 12 items — Nuxt UI components, RTL verified, i18n, Playwright setup  
**Status:** ✅ Fully specified

### US3 — Clean Architecture Foundation

**Goal:** Establish service layer, repositories, RBAC separation patterns  
**Acceptance Criteria:** 11 items — Controller structure, service isolation, repository queries  
**Status:** ✅ Fully specified

### US4 — Local Development Environment

**Goal:** Enable single-command local setup with Docker Compose  
**Acceptance Criteria:** 7 items — Docker services, .env template, README, Makefile, pre-commit hooks  
**Status:** ✅ Fully specified

---

## Technical Scope Breakdown

### Backend (Laravel) — 25+ Items

- **Project Configuration**
  - Laravel 11 with PHP 8.2+ requirement
  - Composer.json with scripts: dev, test, test:coverage, lint, lint:fix, analyze
  - .env template with DB, Redis, API_URL vars
  - package.json for npm dependencies (ES linting, frontend tooling)

- **Error Handling**
  - Exception handler with standard JSON response format
  - Error contract: { success: bool, data: null, message: string, errors: {} }
  - HTTP status code mapping

- **Authentication**
  - Laravel Sanctum configuration
  - User model with roles migration
  - Personal access tokens for API auth
  - Authentication middleware foundation

- **Validation**
  - Form Request base class
  - Custom validation rule examples
  - Server-side validation (never client-only)

- **Eloquent ORM**
  - Base Model with relationships, scopes, casts
  - Repository pattern starter with example
  - Query builder methods (no raw SQL outside repositories)

- **Services & Business Logic**
  - Service class template
  - Clear separation: Service → Repository → Model
  - Dependency injection via constructor

- **API Controllers**
  - Base API controller extending Illuminate\Routing\Controller
  - Standard response methods (success, error)
  - Thin controllers delegating to services

- **RBAC Foundation**
  - 5-role enum (Customer, Contractor, Supervising Architect, Field Engineer, Admin)
  - Gate & Policy definitions
  - Middleware for route protection

- **Testing**
  - PHPUnit configuration with test database
  - Feature test example (API endpoint test)
  - Unit test example (service test)
  - Test helpers for RBAC assertions

- **Code Quality**
  - PHPStan configuration (level 9)
  - PHP-CS-Fixer rules (.php-cs-fixer.php)
  - Laravel Pint configuration (PSR-12)

### Frontend (Nuxt.js) — 20+ Items

- **Nuxt Configuration**
  - nuxt.config.ts with module registration
  - Auto-imports enabled for components and composables
  - App config for theming

- **Nuxt UI**
  - @nuxt/ui module installed and configured
  - UButton, UCard, UForm, UTable component examples
  - Tailwind CSS v4 integration
  - Theming system setup

- **Internationalization (i18n)**
  - @nuxtjs/i18n module configuration
  - Arabic (ar-SA) and English (en-US) locales
  - RTL mode automatic switching
  - Translation key structure defined

- **RTL Support**
  - Tailwind CSS logical properties (start, end, ps, pe, ms, me)
  - CSS logical properties (margin-inline, padding-block, etc.)
  - dir="rtl" on <html> element

- **Layouts**
  - default.vue — Standard page layout with header, nav, main, footer
  - auth.vue — Login/register form layout
  - admin.vue — Admin dashboard layout with sidebar

- **Pinia Store**
  - User store (authentication state)
  - Theme store (light/dark, Arabic/English)
  - API composable for backend communication

- **Form Validation**
  - VeeValidate + Zod integration
  - Form component with error display
  - Real-time validation examples

- **API Integration**
  - $fetch composable wrapper
  - Standard error handling
  - CORS configuration

- **TypeScript**
  - tsconfig.json with strict mode
  - Type definitions for API responses
  - Composable type exports

- **Testing**
  - Vitest configuration
  - Vue Test Utils setup
  - Component test example
  - Composable test example

- **Playwright E2E**
  - playwright.config.ts setup
  - Example E2E test (login flow)
  - Screenshots and video recording config

- **Linting & Formatting**
  - .eslintrc.json with @nuxt/eslint
  - .prettierrc.json configuration
  - npm lint, lint:fix, format scripts

### DevOps & Infrastructure — 15+ Items

- **Local Development (Docker Compose)**
  - MySQL 8.x service with persistent volume
  - Redis service
  - Node watcher for frontend build
  - Network configuration

- **Git Workflow**
  - Husky hooks: pre-commit, pre-push
  - lint-staged configuration selective file filtering
  - Automatic PHP-CS-Fixer, ESLint, Prettier on staged files

- **GitHub Actions**
  - CI workflow trigger on PR
  - Steps: Lint, Type Check, Test, Analysis
  - Artifact storage for coverage reports

- **Monorepo Structure**
  - backend/ — Laravel application
  - frontend/ — Nuxt.js application
  - docs/ — Architecture docs, ADRs
  - .github/ — GitHub Actions workflows
  - specs/ — This workflow system

- **Environment Configuration**
  - backend/.env.example — Database, Redis, app settings
  - frontend/.env.example — API_URL, i18n locale
  - .env.docker for Docker services

- **Package Management**
  - Composer for backend PHP dependencies
  - npm for frontend and backend tooling
  - Lock files in git (.lock files tracked)

---

## Acceptance Criteria

### Backend (US1 + US3)

- [ ] Laravel 11 created with `composer create-project laravel/laravel`
- [ ] PHP 8.2+ minimum enforced in composer.json
- [ ] MySQL configured in .env (host=db, port=3306)
- [ ] Laravel Sanctum installed and configured
- [ ] PHPUnit configured with test database
- [ ] PHPStan configured to level 9
- [ ] PHP-CS-Fixer rules defined in .php-cs-fixer.php
- [ ] Base exception handler returns standard JSON format
- [ ] Base API controller with success/error response methods
- [ ] RBAC 5-role enum created and added to User model
- [ ] Policy base class created
- [ ] Form Request base class for validation
- [ ] Service class template created
- [ ] Repository pattern starter with example
- [ ] Composer scripts: dev, test, lint, lint:fix, analyze
- [ ] Fresh migration for users with roles (nullable)
- [ ] Seeders disabled in production
- [ ] No hardcoded secrets in code

### Frontend (US2 + US3)

- [ ] Nuxt 3 created with `npx nuxi@latest init`
- [ ] @nuxt/ui module installed and registered
- [ ] Tailwind CSS v4 integrated
- [ ] TypeScript strict mode enabled in tsconfig.json
- [ ] Vue 3 Composition API in use (<script setup lang="ts">)
- [ ] @nuxtjs/i18n module installed (ar-SA, en-US)
- [ ] RTL mode auto-switches with locale change
- [ ] Tailwind logical properties in use (not left/right)
- [ ] Three layouts created: default, auth, admin
- [ ] Pinia store setup with user state
- [ ] Vitest configured with Vue Test Utils
- [ ] Playwright configured with @nuxt/test-utils
- [ ] ESLint with @nuxt/eslint configured
- [ ] Prettier configured
- [ ] npm scripts: dev, build, lint, lint:fix, format, typecheck, test

### DevOps (US4)

- [ ] Docker Compose file with MySQL, Redis, Node services
- [ ] backend/Dockerfile for PHP container
- [ ] frontend/Dockerfile for Node container
- [ ] Husky installed with pre-commit, pre-push hooks
- [ ] lint-staged configured
- [ ] .gitignore covers backend, frontend, OS files
- [ ] backend/.env.example created
- [ ] frontend/.env.example created
- [ ] GitHub Actions workflow for CI
- [ ] .php-cs-fixer.php, .eslintrc.json, .prettierrc.json committed
- [ ] Makefile or dev docs for `docker-compose up -d`
- [ ] README with local dev setup instructions
- [ ] All lock files tracked in git

### Integration (Cross-Layer)

- [ ] Frontend can call Backend API (CORS configured)
- [ ] Error responses are consistent JSON across layers
- [ ] RBAC enforced in response headers (X-User-Role)
- [ ] Local dev environment runnable with single command

---

## Dependencies

### Upstream Dependencies

**NONE** — This is the foundation stage. No prior stages required.

### Downstream Dependencies

- STAGE_02: Database Schema
- STAGE_03: Authentication (extends Sanctum scaffold)
- STAGE_04: RBAC System (uses RBAC foundation)
- STAGE_05: Error Handling (uses error contract)
- STAGE_06: API Foundation (extends API controller)
- All frontend stages depend on Nuxt setup

---

## Risk Assessment

### Risk Level: 🟢 **LOW**

| Risk                     | Mitigation                                             |
| ------------------------ | ------------------------------------------------------ |
| Monorepo complexity      | Clear structure with separate backend/, frontend/ dirs |
| Docker setup delays      | Pre-built docker-compose.yml with documented services  |
| Linting rule conflicts   | ESLint + PHP-CS-Fixer with documented config           |
| Missing dev dependencies | Dependency matrix in scope with exact versions         |
| RTL regressions          | Tailwind logical properties + i18n config verified     |

---

## Quality Gates

All specifications above must be verified before proceeding to **Step 2 — Clarify**.

✅ Specification complete. Ready for clarification.
