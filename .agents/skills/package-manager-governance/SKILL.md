---
name: package-manager-governance
description: Package manager governance (Composer + npm)
---

# Package Manager Governance — Bunyan

## Backend: Composer (PHP)

### Commands

```bash
composer install                 # Install from lock file
composer require vendor/package  # Add dependency
composer update vendor/package   # Update specific package
```

### Rules

- Never run `composer update` without specifying a package
- Always commit `composer.lock`
- Use `--dev` flag for dev-only dependencies
- Pin major versions: `"laravel/framework": "^11.0"`

## Frontend: npm (Node.js)

### Commands

```bash
npm install                      # Install from lock file
npm install package-name         # Add dependency
npm install -D package-name      # Add dev dependency
```

### Rules

- Always commit `package-lock.json`
- Use `--save-exact` for critical packages
- Never use `npm install` without lock file in CI

## Dependency Audit

```bash
# Backend
composer audit                   # Check for vulnerabilities

# Frontend
npm audit                        # Check for vulnerabilities
npm audit fix                    # Fix vulnerabilities (review changes)
```

## Forbidden

- `npm install -g` — no global installs in project context
- `composer global require` — no global PHP packages
- Installing packages without checking license compatibility
