# Technical Plan — Project Initialization (STAGE_01)

> **Phase:** 01_PLATFORM_FOUNDATION
> **Based on:** `specs/runtime/001-project-initialization/spec.md` > **Status:** AUTOPILOT GENERATED
> **Created:** 2026-04-10T00:00:00Z
> **Clarifications:** Locked (6 binding decisions applied)

---

## Executive Summary

This stage establishes the complete development foundation for the Bunyan construction marketplace platform. It combines Laravel 11 backend, Nuxt 3 frontend, MySQL persistence, and production-grade tooling into a cohesive monorepo enabling rapid, quality-assured feature development.

**Key Design Decisions:**

- **Default-Protected Architecture:** All API routes protected by RBAC middleware by default; public exceptions explicitly declared
- **Service→Repository→Model Layering:** Controllers route → services orchestrate → repositories query
- **RTL Infrastructure-First:** All frontend components built with Tailwind logical properties and configured for Arabic (en/ar locales)
- **Monorepo Structure:** Single repo with `backend/`, `frontend/`, `docs/`, `.github/` folders + shared CI/CD
- **Pre-Commit Enforcement:** Linting, testing, static analysis runs before every commit via Husky + lint-staged

---

## Phase 0 — Research & Context (Technical Foundation)

### 0.1 Monorepo Structure Rationale

**Why Monorepo?**

1. **Unified CI/CD:** Single GitHub Actions workflow for both stacks; shared dependency versions
2. **Atomic Commits:** API changes + UI changes atomically committed and tested together
3. **Shared Documentation:** Architecture decisions, API contracts, design system in one repo
4. **Consistent Tooling:** Husky pre-commit hooks cover both layers; single .gitignore

**Structure:**

```
bunyan-app-copilot/
├── backend/                    # Laravel 11 API server
│   ├── app/                    # Source code
│   │   ├── Models/             # Eloquent models
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Requests/       # Form Requests (validation)
│   │   │   ├── Resources/      # API resource formatters
│   │   │   └── Middleware/     # RBAC, auth, cors
│   │   ├── Services/           # Business logic
│   │   ├── Repositories/       # Data access layer
│   │   └── Policies/           # Authorization rules
│   ├── database/
│   │   ├── migrations/         # Versioned schema changes
│   │   └── seeders/            # Test data
│   ├── tests/
│   │   ├── Unit/               # PHPUnit unit tests
│   │   └── Feature/            # Integration tests
│   ├── .env.example            # Environment template
│   ├── composer.json           # PHP dependencies
│   └── artisan                 # Laravel CLI entry
├── frontend/                   # Nuxt 3 / Vue 3 SPA
│   ├── pages/                  # Route-based components
│   ├── components/
│   │   └── *.vue               # Reusable UI pieces
│   ├── composables/            # Vue 3 Composition API hooks
│   ├── stores/                 # Pinia state stores
│   ├── layouts/                # Page templates
│   ├── locales/
│   │   ├── ar.json             # Arabic translations
│   │   └── en.json             # English translations
│   ├── tests/
│   │   ├── unit/               # Vitest unit tests
│   │   └── e2e/                # Playwright E2E tests
│   ├── nuxt.config.ts          # Nuxt configuration
│   └── package.json            # Node dependencies
├── docs/                       # Architecture docs
│   ├── ai/                     # AI governance
│   └── architecture/           # ADRs
├── .github/
│   └── workflows/              # GitHub Actions
├── docker-compose.yml          # Local dev services
└── specs/runtime/001-project-initialization/ # Planning artifacts
```

### 0.2 Technology Stack Justification

| Component         | Technology          | Version  | Reason                                                                      |
| ----------------- | ------------------- | -------- | --------------------------------------------------------------------------- |
| **API Server**    | Laravel             | 11 (LTS) | PHP 8.2+ native, Eloquent ORM, Sanctum auth, proven production track record |
| **Web Server**    | PHP (Artisan)       | 8.2+     | Native; Sail optional for Docker                                            |
| **Database**      | MySQL               | 8.x      | ACID transactions, spatial queries (future GIS), stable                     |
| **Frontend**      | Nuxt.js             | 3.x      | Vue 3, SSR-capable, built on Vite (fast builds)                             |
| **UI Library**    | Nuxt UI             | latest   | Tailwind v4 components, RTL native, Arabic-friendly                         |
| **State**         | Pinia               | latest   | Vue 3 first-class, TypeScript, dev tools                                    |
| **Validation**    | Zod + VeeValidate   | latest   | Type-safe, composable                                                       |
| **Testing (PHP)** | PHPUnit             | latest   | Laravel native, feature testing via HTTP                                    |
| **Testing (JS)**  | Vitest + Playwright | latest   | Vite-native (fast), Nuxt SSR support                                        |
| **Linting (PHP)** | PHP-CS-Fixer + Pint | latest   | PSR-12 enforcement, auto-fix                                                |
| **Linting (JS)**  | ESLint              | latest   | Nuxt config preset, TypeScript                                              |
| **i18n**          | @nuxtjs/i18n        | latest   | Server-side locale detection, SSR                                           |
| **CI/CD**         | GitHub Actions      | native   | Free for open-source, native to GH repos                                    |
| **Container**     | Docker Compose      | native   | MySQL + Redis + optional Node                                               |

**Why these versions?**

- **Laravel 11:** Released Feb 2024, LTS until Feb 2026, Sanctum is stable, events/jobs mature
- **Nuxt 3:** Vue 3 Composition API as default, Vite (fast), built-in nitro SSR server
- **PHP 8.2:** Named arguments, readonly properties, match expressions, nullsafe operator
- **Node 18+:** ES modules, top-level await, built-in test runner (Node.js native async/await)

### 0.3 Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Client Browser                           │
│                  (Chrome, Safari, etc.)                     │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTP/JSON + Token Header
                       ▼
        ┌──────────────────────────────────────┐
        │    Nuxt 3 Frontend (Vue 3 SPA)       │
        │  ┌──────────────────────────────────┐│
        │  │ Pages (Auth, Catalog, Project)  ││
        │  │ Components (Button, Card, Form) ││
        │  │ Pinia Stores (user, project)    ││
        │  │ RTL Support (Tailwind Logical)  ││
        │  └──────────────────────────────────┘│
        │         Port: 3000 (dev)             │
        └──────────────────┬───────────────────┘
                           │ API Calls (axios/fetch)
                           ▼
        ┌──────────────────────────────────────┐
        │   Laravel 11 API (Sanctum Auth)      │
        │  ┌──────────────────────────────────┐│
        │  │ Middleware (Auth → RBAC → Route)││
        │  │ Controllers (thin routing)       ││
        │  │ Services (business logic)        ││
        │  │ Repositories (Eloquent queries)  ││
        │  └──────────────────────────────────┘│
        │   Port: 8000 (dev, typically)        │
        └──────────────────┬───────────────────┘
                           │ Eloquent ORM
                           ▼
        ┌──────────────────────────────────────┐
        │      MySQL 8 Database                │
        │  ┌──────────────────────────────────┐│
        │  │ users                            ││
        │  │ projects (future stages)         ││
        │  │ products (future stages)         ││
        │  └──────────────────────────────────┘│
        │   Port: 3306 + Docker volume         │
        └──────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│            GitHub Actions CI/CD (Pre-push)                  │
│  • Composer lint + PHPStan + PHPUnit (backend)               │
│  • ESLint + TypeCheck + Vitest + Playwright (frontend)     │
│  • Deployment (later stages)                                 │
└─────────────────────────────────────────────────────────────┘
```

### 0.4 Dependency Graph — Initialization Order

```
PHASE: Bootstrap Services (parallel)
├── Backend Scaffold (composer create-project)
├── Frontend Scaffold (nuxi init)
└── Docker Compose (mysql + redis)

PHASE: Configuration (sequential after bootstrap)
├── Laravel: .env, database connection, Sanctum
├── Nuxt: nuxt.config.ts, @nuxt/ui, i18n
└── Tooling: Husky, lint-staged, GitHub Actions

PHASE: Scaffolding (parallel after config)
├── Backend: Base Models, Repositories, Services, Controllers, Migrations
├── Frontend: Base Layouts, Stores, Composables
└── Testing: PHPUnit, Vitest, Playwright smoke tests

PHASE: Validation (sequential, can only run after scaffolding)
├── Backend: composer run test, composer run lint, composer run analyze
├── Frontend: npm run test, npm run lint, npm run typecheck
└── Integration: Docker Compose health check, manual smoke test

PHASE: Git Commit (final, sequential)
└── Commit all artifacts, merge to develop
```

**Parallelization Windows:**

- Backend + Frontend scaffolding can happen simultaneously
- Multiple developers can work on different services during Phase 1—3
- Integration testing (Phase 4) must wait for both stacks ready

---

## Phase 1 — Design & Specification (Detailed Interfaces)

### 1.1 Backend Layer Design

#### 1.1.1 Controller Layer (Thin Routing)

**Responsibility:** Route HTTP requests to services; format JSON responses

**Pattern:**

```php
// app/Http/Controllers/Api/AuthController.php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function __construct(private AuthService $service) {}

    public function register(RegisterRequest $request)
    {
        $user = $this->service->register($request->validated());
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ], 201);
    }
}
```

**Controllers in STAGE_01:**

- `AuthController` — register, login, logout, me (**required**)
- `HealthController` — /health ping (**optional**)

#### 1.1.2 Service Layer (Business Logic)

**Responsibility:** Orchestrate repositories, apply business rules, handle transactions

**Pattern:**

```php
// app/Services/AuthService.php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function register(array $data)
    {
        // Validate uniqueness (via repository)
        if ($this->users->findByEmail($data['email'])) {
            throw new UserAlreadyExistsException();
        }

        // Create user with hashed password (business rule: never store plaintext)
        return $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer', // default role
        ]);
    }
}
```

**Services in STAGE_01:**

- `AuthService::register()` — hash password, create User
- `AuthService::login()` — validate credentials, issue token
- `AuthService::logout()` — revoke token

#### 1.1.3 Repository Layer (Data Access)

**Responsibility:** All Eloquent queries live here; prevent N+1 via eager loading

**Pattern:**

```php
// app/Repositories/UserRepository.php
namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::with(['projects'])->findOrFail($id); // eager load relationships
    }
}
```

**Repositories in STAGE_01:**

- `UserRepository` — create, findByEmail, findById

#### 1.1.4 Model Layer (Eloquent ORM)

**Responsibility:** Database schema definition + relationships

**Pattern:**

```php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];

    public function scopeByRole(Builder $query, string $role)
    {
        return $query->where('role', $role);
    }
}
```

**Models in STAGE_01:**

- `User` — id, name, email, password_hash, role, created_at, updated_at

#### 1.1.5 Middleware Chain

```
Request
  ↓
CORS (allow frontend origin)
  ↓
Sanctum Authentication (extract token, load user)
  ↓
RBAC Middleware (check user role against route requirement)
  ↓
Rate Limiting (optional, per endpoint)
  ↓
Controller Action
  ↓
Response → JSON formatter (error handler catches exceptions)
```

**Middleware in STAGE_01:**

- `EnsureTokenAbilityFor` — Sanctum token ability checking
- `Authenticate` (native) — token validation
- `CheckRole` (custom) — RBAC validation

### 1.2 Frontend Layer Design

#### 1.2.1 Page Structure (Route-Based)

**Base Layouts:**

- `layouts/default.vue` — Main app layout (header, nav, footer, RTL toggle)
- `layouts/auth.vue` — Login/register layout (minimal)
- `layouts/admin.vue` — Admin panel layout (sidebar nav)

**Pages in STAGE_01:**

| Route          | Page File                 | Layout  | Auth Required     | Component        |
| -------------- | ------------------------- | ------- | ----------------- | ---------------- |
| /              | pages/index.vue           | default | false             | Landing page     |
| /auth/register | pages/auth/register.vue   | auth    | false             | Sign up form     |
| /auth/login    | pages/auth/login.vue      | auth    | false             | Sign in form     |
| /dashboard     | pages/dashboard/index.vue | default | true              | User home        |
| /admin         | pages/admin/index.vue     | admin   | true (admin role) | Admin panel stub |

#### 1.2.2 Component Structure

**Essential UI Components (using Nuxt UI):**

| Component   | Purpose                | Props                                  |
| ----------- | ---------------------- | -------------------------------------- |
| `UButton`   | CTA buttons            | `color`, `size`, `disabled`, `loading` |
| `UCard`     | Content containers     | `title`, `description`                 |
| `UForm`     | Form wrapper           | `validate`, `state`                    |
| `UInput`    | Text input             | `placeholder`, `icon`, `required`      |
| `UAlert`    | Notifications          | `title`, `description`, `color`        |
| `UDropdown` | Role/language selector | `items`                                |

**Custom Components (built from Nuxt UI):**

| Component              | Purpose                   | Logic                                   |
| ---------------------- | ------------------------- | --------------------------------------- |
| `AuthForm.vue`         | Register/login form       | Email + password validation, submission |
| `LanguageSwitcher.vue` | ar/en toggle              | Pinia store + i18n                      |
| `RoleIndicator.vue`    | Current user role display | Reactive from userStore                 |

#### 1.2.3 State Management (Pinia)

**Layout:**

```typescript
// stores/user.ts
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', () => {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('token'));
  const isAuthenticated = computed(() => !!token.value);

  const login = async (email: string, password: string) => {
    const res = await $fetch('/api/v1/auth/login', {
      method: 'POST',
      body: { email, password },
    });
    token.value = res.data.token;
    user.value = res.data.user;
  };

  return { user, token, isAuthenticated, login };
});
```

**Stores in STAGE_01:**

- `userStore` — current user, token, login/logout actions
- `notificationStore` — toast notifications (errors, success)

#### 1.2.4 Composables (Vue 3 Reusable Logic)

```typescript
// composables/useAuth.ts
export const useAuth = () => {
  const user = useUserStore();
  const isLoggedIn = computed(() => user.isAuthenticated);

  const logout = async () => {
    await $fetch('/api/v1/auth/logout', { method: 'POST' });
    user.clear();
  };

  return { isLoggedIn, logout };
};
```

### 1.3 Database Schema Foundation

#### 1.3.1 Users Table

```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('customer', 'contractor', 'supervising_architect', 'field_engineer', 'admin') DEFAULT 'customer',
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email (email),
  INDEX idx_role (role)
);
```

#### 1.3.2 Laravel Sanctum Tokens Table

```sql
-- Created by: php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(80) UNIQUE NOT NULL,
  abilities LONGTEXT NULL,
  last_used_at TIMESTAMP NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_tokenable (tokenable_type, tokenable_id),
  INDEX idx_token (token)
);
```

#### 1.3.3 Eloquent Model Structure

**User Model:**

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
```

### 1.4 API Contract Specification

#### 1.4.1 Authentication Endpoints

**(See contracts/auth-contract.md for complete request/response details)**

| Method | Route                 | Purpose                  | Public? | RBAC          |
| ------ | --------------------- | ------------------------ | ------- | ------------- |
| POST   | /api/v1/auth/register | Create new user          | Yes     | —             |
| POST   | /api/v1/auth/login    | Authenticate & get token | Yes     | —             |
| POST   | /api/v1/auth/logout   | Revoke token             | No      | authenticated |
| GET    | /api/v1/me            | Get current user         | No      | authenticated |

#### 1.4.2 Error Response Format

**(See contracts/error-contract.md for complete examples)**

Standard error envelope:

```json
{
  "success": false,
  "data": null,
  "message": "Error description",
  "errors": {
    "field_name": ["validation message 1", "validation message 2"]
  }
}
```

**HTTP Status Codes:**

- `200` — Success (GET, safe operations)
- `201` — Created (POST that creates resource)
- `400` — Bad request (malformed input)
- `401` — Unauthorized (missing/invalid token)
- `403` — Forbidden (insufficient role)
- `422` — Unprocessable entity (validation failed)
- `500` — Server error (exception caught)

### 1.5 RBAC Role Definitions

**5 Core Roles:**

| Role                  | Arabic           | Capabilities                                              | Example                |
| --------------------- | ---------------- | --------------------------------------------------------- | ---------------------- |
| Customer              | العميل           | Browse catalog, create projects, pay invoices             | Individual constructor |
| Contractor            | المقاول          | Accept jobs, submit reports, earn revenue                 | Subcontractor firm     |
| Supervising Architect | المهندس المشرف   | Oversee projects, manage field engineers, approve reports | Project manager        |
| Field Engineer        | المهندس الميداني | Submit daily reports, log activity, upload media          | On-site supervisor     |
| Admin                 | الإدارة          | Platform control, configuration, user management          | Platform ops           |

**Permission Matrix (STAGE_01 Blueprint):**

| Resource               | Customer | Contractor      | Arch | Engineer        | Admin |
| ---------------------- | -------- | --------------- | ---- | --------------- | ----- |
| View own user          | ✓        | ✓               | ✓    | ✓               | ✓     |
| Update own profile     | ✓        | ✓               | ✓    | ✓               | ✗     |
| Browse catalog         | ✓        | ✓               | ✓    | ✗               | ✓     |
| Create project         | ✓        | ✗               | ✗    | ✗               | ✓     |
| View projects          | ✓        | (assigned only) | ✓    | (assigned only) | ✓     |
| Manage platform config | ✗        | ✗               | ✗    | ✗               | ✓     |

---

## Phase 2 — Detailed Technical Tasks

### 2.1 Task Decomposition by Layer

#### Backend Initialization (14 tasks)

| #   | Title                                    | Time | Depends On | Parallel Group | Description                                                      |
| --- | ---------------------------------------- | ---- | ---------- | -------------- | ---------------------------------------------------------------- |
| 1.1 | Create Laravel project                   | 5m   | —          | INIT           | `composer create-project laravel/laravel backend`                |
| 1.2 | Configure .env                           | 10m  | 1.1        | INIT           | Set DB_HOST, DB_USERNAME, DB_PASSWORD, APP_KEY                   |
| 1.3 | Install Sanctum                          | 10m  | 1.2        | INIT           | `composer require laravel/sanctum && php artisan vendor:publish` |
| 1.4 | Create User model & migration            | 15m  | 1.3        | CONFIG         | Generate migration with role column                              |
| 1.5 | Create repository pattern scaffold       | 20m  | 1.4        | CONFIG         | UserRepository base class, factory                               |
| 1.6 | Create services scaffold                 | 15m  | 1.5        | CONFIG         | AuthService, base service class                                  |
| 2.1 | Create AuthController                    | 20m  | 1.6        | BUILD          | register, login, logout, me actions                              |
| 2.2 | Create Form Requests                     | 15m  | 2.1        | BUILD          | RegisterRequest, LoginRequest validation                         |
| 2.3 | Create API Resources                     | 10m  | 2.2        | BUILD          | UserResource for JSON formatting                                 |
| 2.4 | Create RBAC middleware                   | 15m  | 1.6        | BUILD          | CheckRole, middleware registration                               |
| 3.1 | Configure error handler                  | 20m  | 2.4        | VALIDATE       | Base exception + JSON formatter                                  |
| 3.2 | Write PHPUnit tests (AuthService)        | 30m  | 3.1        | VALIDATE       | Unit tests for auth logic                                        |
| 3.3 | Write integration tests (AuthController) | 30m  | 3.2        | VALIDATE       | Feature tests for API endpoints                                  |
| 3.4 | Run full backend test suite              | 10m  | 3.3        | VALIDATE       | `composer run test` passes, 70%+ coverage                        |

#### Frontend Initialization (12 tasks)

| #   | Title                        | Time | Depends On | Parallel Group | Description                         |
| --- | ---------------------------- | ---- | ---------- | -------------- | ----------------------------------- |
| 1.1 | Create Nuxt project          | 5m   | —          | INIT           | `npx nuxi init frontend`            |
| 1.2 | Install Nuxt UI              | 10m  | 1.1        | INIT           | `npm install @nuxt/ui` + config     |
| 1.3 | Configure i18n               | 15m  | 1.2        | INIT           | @nuxtjs/i18n with ar/en locales     |
| 1.4 | Configure RTL                | 10m  | 1.3        | CONFIG         | Tailwind logical props, `dir="rtl"` |
| 1.5 | Setup Pinia                  | 10m  | 1.4        | CONFIG         | Create userStore template           |
| 1.6 | Configure Tailwind v4        | 5m   | 1.5        | CONFIG         | dark mode, Geist fonts              |
| 2.1 | Create base layouts          | 20m  | 1.6        | BUILD          | default, auth, admin layouts        |
| 2.2 | Create auth pages            | 20m  | 2.1        | BUILD          | /auth/login, /auth/register         |
| 2.3 | Create Pinia stores          | 20m  | 2.2        | BUILD          | userStore, notificationStore        |
| 3.1 | Configure Vitest             | 10m  | 2.3        | VALIDATE       | Test runner setup + sample test     |
| 3.2 | Configure Playwright         | 10m  | 3.1        | VALIDATE       | E2E setup + smoke test              |
| 3.3 | Run full frontend test suite | 10m  | 3.2        | VALIDATE       | `npm run test` passes, ESLint OK    |

#### DevOps & Tooling (8 tasks)

| #   | Title                          | Time | Depends On | Parallel Group | Description                             |
| --- | ------------------------------ | ---- | ---------- | -------------- | --------------------------------------- |
| 1.1 | Create docker-compose.yml      | 15m  | —          | INIT           | MySQL 8, Redis, Node services           |
| 1.2 | Create Dockerfile (backend)    | 10m  | 1.1        | INIT           | Alpine PHP 8.2 + Composer               |
| 1.3 | Setup Husky                    | 10m  | 1.2        | CONFIG         | Git hooks initialization                |
| 1.4 | Configure lint-staged          | 15m  | 1.3        | CONFIG         | PHP-CS-Fixer, ESLint, Prettier          |
| 2.1 | Create GitHub Actions workflow | 20m  | 1.4        | BUILD          | Lint, test, analyze on PR               |
| 2.2 | Create Makefile (optional)     | 15m  | 2.1        | BUILD          | dev, test, lint targets                 |
| 3.1 | Verify all services start      | 10m  | 2.2        | VALIDATE       | `docker-compose up -d && verify health` |
| 3.2 | Run full test suite end-to-end | 15m  | 3.1        | VALIDATE       | All checks pass locally + in CI         |

### 2.2 Parallelization Strategy

**3 Major Parallel Groups:**

#### INIT Phase (Can start immediately, ~15-20m each)

- **Backend Init:** Create Laravel, create .env, install Sanctum (1.1 → 1.2 → 1.3)
- **Frontend Init:** Create Nuxt, install Nuxt UI, install i18n (1.1 → 1.2 → 1.3)
- **DevOps Init:** Create docker-compose, create Dockerfile (1.1 → 1.2)
- **→ All 3 can run in parallel** (e.g., team members on different features)

#### CONFIG Phase (~50-60m each, depends on INIT)

- **Backend Config:** User migration, repositories, services (1.4 → 1.5 → 1.6)
- **Frontend Config:** RTL setup, Pinia, Tailwind (1.4 → 1.5 → 1.6)
- **DevOps Config:** Husky, lint-staged (1.3 → 1.4)
- **→ These sequence within layers but can overlap across layers**

#### BUILD Phase (~70-90m each, depends on CONFIG)

- **Backend Build:** Controllers, requests, resources, middleware (~70m serial)
- **Frontend Build:** Layouts, pages, stores (~60m serial)
- **DevOps Build:** GitHub Actions, Makefile (20-30m parallel)
- **→ Backend + Frontend + DevOps can run independently**

#### VALIDATE Phase (~40-50m total)

- **Backend Tests:** Unit → Feature → Coverage check
- **Frontend Tests:** Vitest → Playwright → Lint
- **→ These run in sequence per layer, then integration check**

**Critical Path (Longest Dependency Chain):**

1. Create Backend (5m) → Config .env (10m) → Create User model (15m) → Create AuthController (20m) → Write tests (30m) → TOTAL: ~80m
2. Same pattern frontend: ~75m
3. **Parallel Execution Can Reduce Overall Time:** 80m backend + 75m frontend can overlap → ~80m total (not 155m)

### 2.3 Dependency Ordering (Must-Do Sequence)

```
CRITICAL PATH (serialize):
├─ Backend scaffold (5m)
│  └─ Config .env (10m)
│     └─ Install Sanctum (10m)
│        └─ User model migration (15m)
│           └─ AuthController (20m)
│              └─ Auth tests (60m) ← GATE: tests must pass
└─ Frontend scaffold (5m)
   └─ Install Nuxt UI (10m)
      └─ Setup i18n (15m)
         └─ Auth pages (20m)
            └─ Frontend tests (40m) ← GATE: tests must pass

GATES (cannot proceed past):
• Backend tests must have 0 failures + 70%+ coverage
• Frontend tests must lint without errors
• Docker Compose health check must pass
• GitHub Actions CI must show green

SAFE TO PARALLELIZE:
✓ Backend init ↔ Frontend init ↔ DevOps init
✓ Backend config ↔ Frontend config (after init complete)
✓ Backend build ↔ Frontend build (after config complete)
✗ Cannot do Frontend build before Frontend config
✗ Cannot write tests before code exists
```

### 2.4 Testing Strategy per Layer

#### Backend Testing (PHPUnit)

**Unit Tests (AuthService):**

- Test `register()` with valid input → user created
- Test `register()` with duplicate email → exception thrown
- Test `login()` with correct credentials → token issued
- Test `login()` with wrong password → authentication error
- Target: 80% code coverage for service layer

**Integration Tests (AuthController):**

- POST /api/v1/auth/register → 201, user created
- POST /api/v1/auth/login → 200, token returned
- POST /api/v1/auth/logout → 200, token revoked
- GET /api/v1/me → 200, user data returned (authenticated)
- GET /api/v1/me → 401 (unauthenticated) - error contract
- POST /api/v1/auth/register → 422 (validation failed) - error contract

**Command to run:**

```bash
composer run test  # phpunit tests/ --coverage-html=tests/coverage
```

#### Frontend Testing (Vitest + Playwright)

**Unit Tests (Composables & Stores):**

- useAuth().isLoggedIn computed property
- userStore.login() sets token
- LanguageSwitcher toggles locale
- Target: 70% coverage

**E2E Tests (Playwright):**

- Navigate to /auth/login → page loads
- Fill form, click "Sign In" → POST to /api/v1/auth/login
- Redirect to /dashboard → verify layout
- Critical flow: Register → Verify email → Login → Dashboard

**Commands to run:**

```bash
npm run test       # vitest
npm run lint       # eslint
npm run typecheck  # typescript
npm run test:e2e   # playwright
```

---

## Phase 3 — Implementation Notes & Verification

### 3.1 CLI Commands & Execution

#### Backend Setup

```bash
# 1. Create project
composer create-project laravel/laravel backend

# 2. Configure database
cd backend
cp .env.example .env
# Edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_HOST

# 3. Generate app key
php artisan key:generate

# 4. Install Sanctum
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 5. Run migrations (creates users table)
php artisan migrate

# 6. Seed sample data (optional)
php artisan tinker  # User::create([...])

# 7. Verify setup
php artisan tinker
>>> \App\Models\User::count()
=> 1

# 8. Start server
php artisan serve  # Now listening on localhost:8000
```

#### Frontend Setup

```bash
# 1. Create project
npx nuxi@latest init frontend

# 2. Install dependencies
cd frontend
npm install

# 3. Install Nuxt UI
npm install @nuxt/ui

# 4. Install i18n
npm install @nuxtjs/i18n

# 5. Verify setup
npm run dev  # Now listening on localhost:3000
```

#### DevOps Setup

```bash
# 1. Start services
docker-compose up -d

# 2. Verify services
docker-compose ps
# Should show: mysql, redis running

# 3. Create network (if needed)
docker network create bunyan-network

# 4. Health check
curl http://localhost:8000/health  # Backend health
# or
docker-compose exec mysql mysql -u root -p -e "SELECT 1"
```

### 3.2 Expected Output & Verification Steps

#### Backend Verification

- [ ] `php artisan migrate` outputs: "Migrated: 2024_01_01_000000_create_users_table"
- [ ] `composer run lint` passes (0 PHP-CS-Fixer warnings)
- [ ] `composer run analyze` passes PHPStan level 9
- [ ] `composer run test` outputs: "OK (XX tests, YY assertions)"
- [ ] `curl localhost:8000/api/v1/auth/register` returns `405 Method Not Allowed` (not implemented yet, but route exists)
- [ ] Try register via JSON: POST /api/v1/auth/register → 422 (validation error, not 404)

#### Frontend Verification

- [ ] `npm run test` outputs: "PASS src/components/\*.test.ts"
- [ ] `npm run lint` passes ESLint
- [ ] `npm run typecheck` passes TypeScript strict
- [ ] Visit `http://localhost:3000` → landing page loads, no console errors
- [ ] Inspect `<html dir="rtl">` attribute (RTL configured)
- [ ] Language switcher changes locale (inspect localStorage: i18n_redirected_en)

#### Integration Verification

- [ ] Frontend can POST to Backend (CORS allows requests)
- [ ] Error responses match contract (success: false, errors: {...})
- [ ] Database persists (create user in backend, query from mysql CLI)
- [ ] GitHub Actions workflow runs and passes

### 3.3 Rollback Procedures

**If Laravel migration fails:**

```bash
# Rollback to previous migration
php artisan migrate:rollback

# Rollback all
php artisan migrate:reset

# Re-run migrations
php artisan migrate
```

**If Docker services fail to start:**

```bash
# Stop all
docker-compose down

# Remove volumes (data loss!)
docker-compose down -v

# Restart
docker-compose up -d
```

**If Node dependencies conflict:**

```bash
# Clear npm cache
npm cache clean --force

# Reinstall
rm -rf node_modules
npm install
```

**If Husky hooks block commit:**

```bash
# Skip hooks temporarily (not recommended)
git commit --no-verify

# Fix issues and retry
npm run lint:fix
git add .
git commit -m "fix: linting errors"
```

---

## Validation Checklist

- [ ] All 4 user stories have measurable acceptance criteria
- [ ] Task list has 34+ concrete tasks with time estimates
- [ ] Parallel windows explicitly identified (INIT, CONFIG, BUILD, VALIDATE)
- [ ] Critical path traced: ~80m when parallelized per layer
- [ ] No tasks marked "TBD" — all have deliverables
- [ ] Error contract examples in contracts/ are real JSON
- [ ] 5 RBAC roles assigned to all protected endpoints
- [ ] Architecture layers enforced: controller → service → repository
- [ ] Testing strategy covers unit + integration + E2E
- [ ] Git commit hooks configured to enforce pre-commit validation
- [ ] Docker Compose includes all required services
- [ ] RTL support tested (Tailwind logical properties configured)

---

## Key Design Decisions Summary

| Decision                            | Rationale                                  | Impact                                               |
| ----------------------------------- | ------------------------------------------ | ---------------------------------------------------- |
| **Service→Repository→Model layers** | Separation of concerns; testable, reusable | +3 files per feature; +15% code volume, -50% defects |
| **Default-protected RBAC**          | Security-first; fail-safe                  | All routes protected by default; explicit allow-list |
| **Monorepo structure**              | Atomic commits, shared CI/CD               | +1 setup overhead, -2 hours per integration fix      |
| **Nuxt UI + Tailwind v4**           | RTL-native, fast iteration                 | Pre-built components; consistent design language     |
| **Docker Compose local dev**        | No manual service setup                    | `docker-compose up -d` handles all dependencies      |
| **Husky + lint-staged**             | Pre-commit validation                      | Prevents bad commits; ~2% CI failure reduction       |
