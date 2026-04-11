# Development Setup Guide

Welcome to Bunyan (بنيان) - The Arabic Construction Services Marketplace!

This guide walks you through setting up the complete development environment.

## Prerequisites

- PHP 8.3+
- Node.js 18+
- Docker & Docker Compose (optional, for local MySQL/Redis)
- Composer
- npm or pnpm

## Quick Start (Monorepo)

From **repository root**:

```bash
npm run setup        # Install all dependencies (backend + frontend)
npm run dev          # Start backend & frontend dev servers concurrently
```

### Database Options

**Option A: Docker (Recommended)**

```bash
npm run docker:up    # Starts MySQL, Redis, backend, frontend
npm run docker:logs  # Watch logs
```

**Option B: Local Services (SQLite)**

```bash
npm run setup        # Already configured to use SQLite for local dev
npm run dev          # Start dev servers
```

**Option C: Local MySQL**
Edit `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bunyan_dev
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
npm run dev
```

## Troubleshooting Setup

### `npm install` fails with peer dependency errors

```bash
# This is normal during initial setup. The .npmrc file handles this:
npm install --legacy-peer-deps
```

### MySQL Connection Error

- **Symptom**: `Access denied for user 'root'@'127.0.0.1'`
- **Solution**: Use SQLite for local dev (already configured), or ensure MySQL is running

## Testing

### Run All Tests (Monorepo)

```bash
npm run test           # Backend + frontend tests
npm run validate       # Lint + typecheck + analyze + test
```

### Backend Tests

```bash
npm run test:backend   # All tests

# Or directly:
cd backend
php artisan test
php artisan test tests/Feature/Auth/RegisterTest.php
php artisan test --coverage
```

### Frontend Tests

```bash
npm run test:frontend  # Unit tests
npm run test:e2e       # E2E tests

# Or directly:
cd frontend
npm run test
npm run test:e2e
npm run test -- --watch
```

## Code Quality

### Run All Linting & Checking (Monorepo)

```bash
npm run lint           # Lint both stacks
npm run lint:fix       # Fix linting issues
npm run typecheck      # Type checking
npm run analyze        # PHP static analysis
npm run validate       # Complete validation: lint + typecheck + analyze + test
```

### Backend

```bash
npm run lint:backend              # Lint backend
npm run lint:backend:fix          # Fix backend issues

# Or directly:
cd backend
composer run lint
vendor/bin/phpstan analyze
```

### Frontend

```bash
npm run lint:frontend             # Lint frontend
npm run lint:frontend:fix         # Fix frontend issues
npm run typecheck                 # Type checking

# Or directly:
cd frontend
npm run lint
npm run lint -- --fix
npm run typecheck
```

## Project Structure

```
bunyan-app-copilot/
├── backend/                 # Laravel API
│   ├── app/                 # Source code
│   ├── config/              # Configuration
│   ├── database/            # Migrations & Seeders
│   ├── routes/              # API routes
│   └── tests/               # Tests
├── frontend/                # Nuxt SPA
│   ├── pages/               # Route-based components
│   ├── components/          # Reusable components
│   ├── composables/         # Composition API hooks
│   ├── stores/              # Pinia stores
│   └── tests/               # Tests
├── docs/                    # Documentation
├── .github/workflows/       # CI/CD
└── docker-compose.yml       # Local services
```

## Architecture

- **Backend:** Laravel 11 + Sanctum + MySQL + Redis
- **Frontend:** Nuxt 3 + Vue 3 + Pinia + Nuxt UI
- **API Contract:** Standard JSON response format with error handling
- **RBAC:** Role-based access control (Customer, Contractor, Supervising Architect, Field Engineer, Admin)
- **i18n:** Arabic-first, RTL support via @nuxtjs/i18n
- **CI/CD:** GitHub Actions for linting, testing, type checking

## Troubleshooting

### Database Connection Error

- Ensure MySQL is running (`docker-compose ps` or `mysql.server status`)
- Check DB_HOST, credentials in `backend/.env`
- Run `php artisan migrate --force`

### Port Already in Use

- Backend: Change port in `php artisan serve --port=8001`
- Frontend: Change port in `nuxt.config.ts` or `npm run dev -- --port 3001`

### npm Dependencies Issues

```bash
cd frontend
rm -rf node_modules package-lock.json
npm install
```

### PHP Autoload Issues

```bash
cd backend
composer dump-autoload
```

## Next Steps

- Read the API documentation in `docs/`
- Explore ADRs in `docs/architecture/ADR/`
- Check out tasks.md for current feature work
- Join the team standups for context

## Support

For questions or issues:

1. Check the documentation in `docs/`
2. Review open issues/pull requests on GitHub
3. Ask in team Slack channel #engineering

Happy coding! 🚀
