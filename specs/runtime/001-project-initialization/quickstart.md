# Quick Start Guide — Project Initialization

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Purpose:** Get developers up and running in 30 minutes  
> **Created:** 2026-04-10T00:00:00Z

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Clone & Initial Setup](#clone--initial-setup)
3. [Backend Setup](#backend-setup)
4. [Frontend Setup](#frontend-setup)
5. [Verify Installation](#verify-installation)
6. [Running the Application](#running-the-application)
7. [Troubleshooting](#troubleshooting)
8. [Next Steps](#next-steps)

---

## Prerequisites

Before starting, ensure you have:

### System Requirements

| Tool           | Version | Installation               |
| -------------- | ------- | -------------------------- |
| Git            | Latest  | `git --version`            |
| PHP            | 8.2+    | `php -v`                   |
| Composer       | Latest  | `composer --version`       |
| Node.js        | 18+     | `node -v`                  |
| npm            | 9+      | `npm -v`                   |
| Docker         | Latest  | `docker --version`         |
| Docker Compose | 2.0+    | `docker-compose --version` |

### Installation Guides

**macOS:**

```bash
# Install Homebrew (if not already installed)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP
brew install php@8.2 composer

# Install Node.js
brew install node

# Install Docker Desktop (GUI)
# Download from https://www.docker.com/products/docker-desktop
```

**Ubuntu/Debian:**

```bash
# Update package manager
sudo apt update && sudo apt upgrade -y

# Install PHP and extensions
sudo apt install -y php8.2 php8.2-cli php8.2-pdo php8.2-mysql php8.2-xml php8.2-zip

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php && \
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Docker
sudo apt install -y docker.io docker-compose
sudo usermod -aG docker $USER
```

**Windows (WSL2 recommended):**

```powershell
# Enable WSL2
wsl --install

# Install from Microsoft Store:
# - Ubuntu 22.04 LTS
# - Docker Desktop (with WSL2 backend)

# Then follow Ubuntu instructions above
```

### Verify Installation

```bash
# Check all tools
php -v
composer --version
node -v
npm -v
docker --version
docker-compose --version

# Expected: All show version numbers >= requirements
```

---

## Clone & Initial Setup

```bash
# 1. Clone the repository
git clone https://github.com/bunyan/bunyan-app-copilot.git
cd bunyan-app-copilot

# 2. Create local branches
git checkout develop  # or main
git checkout -b dev/setup-local  # Create a working branch

# 3. Copy environment template
cp .env.example .env

# 4. Edit .env if needed (defaults should work for local dev)
# Uncomment/verify:
#   APP_ENV=local
#   APP_DEBUG=true
#   SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

---

## Backend Setup

### Step 1: Navigate to Backend Directory

```bash
cd backend
```

### Step 2: Install Composer Dependencies

```bash
composer install

# Expected output:
# - Generates autoload files
# - Installs ~100+ packages
# - Takes 2-3 minutes
```

### Step 3: Generate Application Key

```bash
php artisan key:generate

# Expected output:
# ✓ Application key set successfully.
```

### Step 4: Copy Environment File

```bash
cp .env.example .env

# Edit .env:
APP_NAME=Bunyan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bunyan_dev
DB_USERNAME=root
DB_PASSWORD=rootpassword
```

### Step 5: Start MySQL (using Docker)

```bash
# From project root (cd ..)
cd ..
docker-compose up -d mysql redis

# Verify MySQL is running
docker-compose ps

# Expected: mysql container status "Up"
```

### Step 6: Run Migrations

```bash
cd backend

# Create tables
php artisan migrate

# Expected output:
# Migrating: 2024_01_01_000000_create_users_table
# Migrated:  2024_01_01_000000_create_users_table (123.45ms)
# Migrating: 2024_01_15_000000_create_personal_access_tokens_table
# Migrated:  2024_01_15_000000_create_personal_access_tokens_table (45.67ms)
```

### Step 7: Verify Setup

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
# Should return: PDOConnection object (not exception)

>>> exit()

# Verify Sanctum installed
ls app/Models/User.php
ls app/Http/Middleware/

# Expected: User.php exists, middleware directory exists
```

---

## Frontend Setup

### Step 1: Navigate to Frontend Directory

```bash
cd ../frontend  # From backend directory
```

### Step 2: Install npm Dependencies

```bash
npm install

# Expected output:
# - Installs ~500+ packages
# - Creates node_modules/ folder
# - Generates package-lock.json
# - Takes 3-5 minutes
```

### Step 3: Verify Nuxt UI Installation

```bash
npm list @nuxt/ui

# Expected: @nuxt/ui@x.x.x
```

### Step 4: Environment Configuration

```bash
# Frontend uses Laravel backend URL
# Verify nuxt.config.ts contains:

cat nuxt.config.ts | grep -i "runtimeConfig\|apiBaseUrl"

# Expected:
#   runtimeConfig: {
#     public: {
#       apiBaseUrl: 'http://localhost:8000'
#     }
#   }
```

### Step 5: Verify TypeScript & ESLint

```bash
npm run typecheck
npm run lint

# Expected: No errors (warnings OK)
```

---

## Verify Installation

### Backend Health Checks

```bash
cd backend

# 1. Check composer scripts
composer run test:unit  # Runs sample tests
# Expected: OK (X tests, Y assertions)

# 2. Check style guide
composer run lint
# Expected: 0 violations

# 3. Check static analysis
composer run analyze
# Expected: [OK] No errors

# 4. Check database
php artisan migrate:status
# Expected: Batches listed with ✓ status
```

### Frontend Health Checks

```bash
cd ../frontend

# 1. Run type checker
npm run typecheck
# Expected: 0 errors

# 2. Run linter
npm run lint
# Expected: 0 errors

# 3. Run unit tests
npm run test
# Expected: PASS (or similar)
```

### Docker Verification

```bash
# Check services
docker-compose ps

# Expected:
# NAME          STATUS
# bunyan-mysql  Up
# bunyan-redis  Up

# Test database connection
docker-compose exec mysql mysql -uroot -prootpassword -e "SELECT VERSION();"

# Expected: MySQL version number (8.0.x)
```

---

## Running the Application

### Terminal 1: Backend Server

```bash
cd backend
php artisan serve

# Expected output:
# ┌─────────────────────────────────────────┐
# │  Laravel development server started:    │
# │  http://127.0.0.1:8000                  │
# └─────────────────────────────────────────┘
```

### Terminal 2: Frontend Development Server

```bash
cd frontend
npm run dev

# Expected output:
# ➜  Local:   http://localhost:3000/
# ➜  Network: use --host to access on network
```

### Terminal 3: Docker Services (if not already running)

```bash
# From project root
docker-compose ps

# If any service is down:
docker-compose up -d
```

### Access the Application

Open browser and navigate to:

**Frontend:** http://localhost:3000  
**Backend API:** http://localhost:8000/api/v1/health  
**Backend Docs:** http://localhost:8000 (redirect to API docs, TBD)

### Test Registration

1. Visit http://localhost:3000/auth/register
2. Fill in form:
   - Name: "Test User"
   - Email: "test@example.com"
   - Password: "TestPass123"
3. Click "Register"
4. Expected: Redirect to dashboard, user logged in

### Test API Directly

```bash
# In a new terminal, test backend API

# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "api-test@example.com",
    "password": "TestPass123",
    "password_confirmation": "TestPass123"
  }'

# Expected response:
# {
#   "success": true,
#   "data": {
#     "user": {...},
#     "token": "1|xxxx..."
#   },
#   "message": "User registered successfully"
# }

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "api-test@example.com",
    "password": "TestPass123"
  }'

# Get current user (replace TOKEN)
TOKEN="1|xxxx..."
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer $TOKEN"
```

---

## Troubleshooting

### Port Already in Use

**Error:** `Address already in use (cannot bind to 127.0.0.1:8000)`

**Solution:**

```bash
# Find process using port
lsof -i :8000

# Kill process
kill -9 <PID>

# Or use different port
php artisan serve --port=8001
```

### Database Connection Refused

**Error:** `SQLSTATE[HY000]: General error: 2002 No such file or directory`

**Solution:**

```bash
# 1. Verify MySQL is running
docker-compose ps mysql

# 2. If not running, start it
docker-compose up -d mysql

# 3. Verify connectivity
docker-compose exec mysql mysql -uroot -prootpassword -e "SELECT 1"

# 4. Reset migrations if corrupted
php artisan migrate:refresh --seed
```

### npm Install Fails

**Error:** `ERR! code ERESOLVE, ERR! ERESOLVE unable to resolve dependency tree`

**Solution:**

```bash
# Use legacy peer deps flag
npm install --legacy-peer-deps

# Or clear cache and retry
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

### Composer Autoload Issues

**Error:** `Class not found` or `Cannot redeclare`

**Solution:**

```bash
# Regenerate autoload
cd backend
composer dump-autoload

# Or reinstall
rm -rf vendor composer.lock
composer install
```

### CORS Errors in Frontend

**Error:** `Access to XMLHttpRequest from origin 'http://localhost:3000' has been blocked by CORS policy`

**Solution:**

```bash
# 1. Verify backend .env includes frontend URL:
grep SANCTUM_STATEFUL_DOMAINS backend/.env
# Should contain: localhost:3000

# 2. Restart backend
# Kill artisan serve, run again

# 3. Clear browser cache
# Dev Tools → Right-click refresh → Empty cache and hard refresh
```

### TypeScript Errors in Frontend

**Error:** `Cannot find module '@nuxt/ui' or its corresponding type declarations`

**Solution:**

```bash
# 1. Verify installation
npm list @nuxt/ui

# 2. If missing, install
npm install @nuxt/ui

# 3. Regenerate types
npm run build  # This generates .nuxt/

# 4. TypeScript should see types now
npm run typecheck
```

### Docker Won't Start

**Error:** `Error: Cannot connect to Docker daemon`

**Solution:**

```bash
# 1. On macOS, start Docker Desktop app
# Then retry:
docker ps

# 2. On Linux, start daemon
sudo systemctl start docker
sudo systemctl enable docker

# 3. Verify:
docker --version
```

---

## Development Workflow

### Making Changes

1. Create feature branch:

   ```bash
   git checkout -b feature/my-feature
   ```

2. Make changes to `backend/` or `frontend/`

3. Run tests and linters:

   ```bash
   # Backend
   cd backend && composer run lint && composer run test

   # Frontend
   cd frontend && npm run lint && npm run test
   ```

4. Commit changes:

   ```bash
   git add .
   git commit -m "feat: add feature"
   ```

5. Push and create PR:
   ```bash
   git push origin feature/my-feature
   ```

### Git Hooks (Pre-commit)

Husky hooks automatically run before each commit:

- PHP-CS-Fixer (backend)
- ESLint (frontend)
- TypeScript (frontend)

If hooks fail, fix issues and retry:

```bash
npm run lint:fix  # Auto-fix linting issues
composer run lint:fix  # Auto-fix PHP issues
git add .
git commit -m "fix: resolve linting"
```

---

## Next Steps

After successful setup:

1. **Read the Documentation:**
   - [DESIGN.md](../../DESIGN.md) — Visual design system
   - [AGENTS.md](../../AGENTS.md) — AI-backed development workflow
   - [specs/phases/](../../specs/phases/) — Stage-by-stage plan

2. **Explore the Codebase:**
   - Backend: `backend/app/` — Controllers, Services, Models
   - Frontend: `frontend/pages/`, `frontend/components/` — Vue components
   - Tests: `backend/tests/`, `frontend/tests/` — Test examples

3. **Run Your First Test:**

   ```bash
   cd backend && composer run test
   cd frontend && npm run test
   ```

4. **Start Development:**
   - Create a feature branch
   - Follow the architecture guidelines in [AGENTS.md](../../AGENTS.md)
   - Leverage **GitHub Copilot** for code generation

5. **Join the Team:**
   - Slack channel: #bunyan-dev
   - Weekly standup: Monday 10am UTC

---

## Common Commands

### Backend Commands

```bash
cd backend

# Development
php artisan serve                    # Start server
php artisan tinker                   # Interactive shell
php artisan migrate                  # Run migrations
php artisan migrate:rollback         # Rollback last migration
php artisan migrate:fresh --seed     # Reset database

# Testing
composer run lint                    # Check code style
composer run lint:fix                # Auto-fix style issues
composer run analyze                 # PHPStan static analysis
composer run test                    # Run PHPUnit tests
composer run test:coverage           # Tests with coverage report

# Code generation (Laravel)
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:migration create_table_name
php artisan make:request RequestName
php artisan make:service ServiceName
```

### Frontend Commands

```bash
cd frontend

# Development
npm run dev                          # Start dev server
npm run build                        # Build for production
npm run preview                      # Preview production build

# Testing & Quality
npm run lint                         # ESLint check
npm run lint:fix                     # Auto-fix linting issues
npm run format                       # Prettier formatting
npm run typecheck                    # TypeScript type checking
npm run test                         # Vitest unit tests
npm run test:watch                   # Watch mode
npm run test:e2e                     # Playwright E2E tests
```

### Docker Commands

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f               # All services
docker-compose logs -f mysql         # Specific service

# Stop services
docker-compose down

# Reset everything (warning: data loss)
docker-compose down -v
docker-compose up -d
```

---

## Support & Resources

**Documentation:**

- [PROJECT_CONTEXT_PRIMER.md](../../docs/PROJECT_CONTEXT_PRIMER.md)
- [AI_BOOTSTRAP.md](../../docs/ai/AI_BOOTSTRAP.md)
- [DESIGN.md](../../DESIGN.md)

**Tools:**

- Laravel: https://laravel.com/docs
- Nuxt: https://nuxt.com/docs
- Nuxt UI: https://ui.nuxt.com
- Tailwind CSS: https://tailwindcss.com/docs

**Getting Help:**

- Check [Troubleshooting](#troubleshooting) section
- Review GitHub Issues
- Ask in #bunyan-dev Slack channel

---

**Happy coding! 🚀**
