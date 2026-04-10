# Development Setup Guide

Welcome to Bunyan (بنيان) - The Arabic Construction Services Marketplace!

This guide walks you through setting up the complete development environment.

## Prerequisites

- PHP 8.3+
- Node.js 18+
- Docker & Docker Compose (optional, for local MySQL/Redis)
- Composer
- npm or pnpm

## Quick Start

### 1. Clone & Install Dependencies

```bash
# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Frontend setup
cd ../frontend
npm install
```

### 2. Configure Environment

Edit `backend/.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bunyan_dev
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_ALLOWED_ORIGINS=http://localhost:3000
```

### 3. Database Setup (Local MySQL)

**Option A: Docker Compose (Recommended)**
```bash
# From project root
docker-compose up -d

# Run migrations
cd backend
php artisan migrate
```

**Option B: Local MySQL**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE bunyan_dev;"

# Run migrations
cd backend
php artisan migrate
```

### 4. Start Development Servers

**Terminal 1: Backend**
```bash
cd backend
php artisan serve
# API available at http://localhost:8000
```

**Terminal 2: Frontend**
```bash
cd frontend
npm run dev
# App available at http://localhost:3000
```

### 5. Verify Setup

- **Backend:** Visit http://localhost:8000 (should show Laravel welcome or API endpoint)
- **Frontend:** Visit http://localhost:3000 (should show Nuxt app)
- **Login Page:** http://localhost:3000/auth/login

## Testing

### Backend Tests
```bash
cd backend

# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/Auth/RegisterTest.php

# With coverage
php artisan test --coverage
```

### Frontend Tests
```bash
cd frontend

# Unit tests
npm run test

# E2E tests  
npm run test:e2e

# Watch mode
npm run test -- --watch
```

## Code Quality

### Backend
```bash
cd backend

# Lint
vendor/bin/pint

# Fix issues
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyze
```

### Frontend
```bash
cd frontend

# Lint
npm run lint

# Fix issues
npm run lint -- --fix

# Type checking
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
