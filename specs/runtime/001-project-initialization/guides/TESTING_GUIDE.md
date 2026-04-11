# Testing Guide — STAGE_01_PROJECT_INITIALIZATION

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-10T00:00:00Z

This document collects the commands, smoke scenarios, and CI expectations for the foundation stage. Place this file at `specs/runtime/001-project-initialization/guides/TESTING_GUIDE.md`.

## Prerequisites

- Docker & Docker Compose (recommended) or local services (MySQL, Redis)
- PHP 8.3+, Composer installed
- Node 18+, npm (or pnpm)

## Quick Setup

### Option 1: Docker (Recommended)

```bash
# from repository root
npm run setup
npm run docker:up
npm run docker:logs
```

### Option 2: Local Services

```bash
# from repository root
npm run setup
# backend
cd backend && composer run setup
# frontend
cd frontend && npm install && cp .env.example .env
```

## Monorepo Commands

Run from the **repository root**:

| Command                | Purpose                                                      |
| ---------------------- | ------------------------------------------------------------ |
| `npm run install`      | Install dependencies for both backend & frontend             |
| `npm run dev`          | Start backend & frontend dev servers concurrently            |
| `npm run setup`        | Full setup: install deps, initialize backend, frontend ready |
| `npm run lint`         | Run all linters (backend + frontend)                         |
| `npm run lint:fix`     | Fix all linting issues                                       |
| `npm run typecheck`    | TypeScript/PHP static analysis                               |
| `npm run analyze`      | PHPStan analysis (backend)                                   |
| `npm run test`         | Run all tests (backend + frontend)                           |
| `npm run test:e2e`     | Run Playwright E2E tests                                     |
| `npm run validate`     | Full validation: lint + typecheck + analyze + test           |
| `npm run docker:up`    | Start Docker services (backend, frontend, MySQL, Redis)      |
| `npm run docker:down`  | Stop all Docker services                                     |
| `npm run docker:build` | Build Docker images                                          |

## Running Tests

### Full Test Suite (Monorepo)

Run all tests from **repository root**:

```bash
npm run test              # Backend + frontend tests
npm run validate          # Full validation: lint + typecheck + analyze + test
```

### Backend — Unit & Feature Tests

Run backend tests from root:

```bash
npm run test:backend
```

Or directly:

```bash
cd backend
php artisan test
# or (with more detail)
./vendor/bin/phpunit --testdox
```

Run only Feature tests:

```bash
cd backend
php artisan test --testsuite=Feature
```

Run static analysis and lint:

```bash
npm run lint:backend
npm run analyze
```

Or directly:

```bash
cd backend
composer run lint        # Pint / php-cs-fixer
vendor/bin/phpstan analyze
```

### Frontend — Unit & E2E

Run unit and E2E tests from root:

```bash
npm run test:frontend    # Unit tests
npm run test:e2e         # Playwright E2E
```

Or directly:

```bash
cd frontend
npm run test             # Unit tests
npm run test:e2e         # Playwright E2E
npm run lint             # ESLint
npm run typecheck        # TypeScript validation
```

### Lint Everything

From root:

```bash
npm run lint             # Both stacks
npm run lint:fix         # Fix both stacks
```

## Manual Smoke Scenarios

### Setup for Manual Testing

**Using Docker (Recommended):**

```bash
npm run setup            # Install all dependencies
npm run docker:build     # Build images
npm run docker:up        # Start services
npm run docker:logs      # Watch logs
```

Wait for services to be healthy (check logs for "ready to handle connections").

**Using Local Services:**

```bash
npm run setup
# Terminal 1: Start backend
npm run dev:backend
# Terminal 2: Start frontend
npm run dev:frontend
```

### Scenario: Successful Login + Redirect to Dashboard

Preconditions:

- Backend running and seeded with test user (email: test@example.com / password: password)
- Frontend dev server running
- Services accessible at `http://localhost:3000` (frontend) and `http://localhost:8000` (backend)

Steps:

1. Navigate to `http://localhost:3000/auth/login`
2. Enter test credentials and submit
3. Verify token stored in Pinia and localStorage
4. Verify `GET /api/v1/auth/me` returns user payload
5. Verify page redirects to `/` (dashboard stub)

Expected results:

- 200 responses from API endpoints
- No CORS or console errors
- RTL layout renders correctly when locale is `ar`

## CI Expectations

### CI Pipeline Steps

From repository root:

```bash
npm run install          # Install all backend + frontend dependencies
npm run lint            # Lint both stacks
npm run typecheck       # Type checking (frontend TypeScript + backend PHPStan)
npm run test            # Run all tests (backend + frontend)
npm run test:e2e        # (Optional on PRs; run on main) Playwright E2E
```

### Coverage Thresholds

- Backend tests + static analysis must pass
- Frontend lint, typecheck and unit tests must pass
- Playwright smoke test should validate core flows (optional on PRs; run on main merges)
- Coverage thresholds: Foundation target >= 70%

### CI Configuration Example

```yaml
# .github/workflows/test.yml
- name: Install dependencies
  run: npm run install

- name: Lint
  run: npm run lint

- name: Type check
  run: npm run typecheck

- name: Analyze
  run: npm run analyze

- name: Test
  run: npm run test
```

## Where to find reports

- Backend PHPUnit results: `backend/build/logs` or CI artifact
- Frontend Vitest results: `frontend/coverage` or CI artifact
- Playwright results: CI artifact from `frontend-e2e` job

## Troubleshooting

### Setup Issues

**"MySQL connection refused"**

```bash
npm run docker:up && sleep 10  # Wait for MySQL to be healthy
npm run setup
```

**"Port 3000 or 8000 already in use"**

```bash
# Find and kill the process
lsof -i :3000
lsof -i :8000
kill -9 <PID>
```

**PHP tests fail with DB errors**

- Ensure backend `.env` is configured or SQLite for testing is enabled in `phpunit.xml`
- Run: `cd backend && php artisan migrate --env=testing`

**Frontend tests fail with missing env vars**

```bash
cd frontend
cp .env.example .env
# Edit .env with API_BASE_URL=http://localhost:8000/api/v1
npm run test
```

### Docker Troubleshooting

**"docker-compose: command not found"**

- Use `docker compose` (new syntax) or install Docker Desktop

**Build fails**

```bash
npm run docker:down
docker system prune -f
npm run docker:build
```

**Logs not readable**

```bash
npm run docker:logs
# or specific service
docker-compose logs backend -f
```

## Automation note

This testing guide is generated and must exist for every stage under `specs/runtime/<stage>/guides/TESTING_GUIDE.md`. A lightweight CI check has been added to validate presence of this file for every stage to prevent omissions.

### Monorepo Script Philosophy

All commands should be runnable from the **repository root** via npm scripts for simplicity. Developers should rarely need to `cd` into subdirectories. The root `package.json` orchestrates:

- **install**: Both Composer and npm
- **dev**: Concurrent backend + frontend servers
- **lint/test/analyze**: All stacks at once
- **docker**: Container lifecycle

Individual commands in subdirectories remain available for focused work (e.g., `cd frontend && npm run test:watch`).

---

Generated by automation — if you want changes or additional scenarios, tell me which items to expand.
