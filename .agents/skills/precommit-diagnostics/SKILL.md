---
name: precommit-diagnostics
description: Pre-commit/pre-push failure diagnostics
---

# Pre-commit Diagnostics — Bunyan

## Pre-commit Hooks

### Backend (PHP)

```bash
# PHP CS Fixer — formatting
php-cs-fixer fix --dry-run --diff

# PHPStan — static analysis
phpstan analyse

# PHPUnit — quick tests
php artisan test --parallel
```

### Frontend (JS/TS)

```bash
# ESLint
eslint --fix .

# TypeScript
nuxt typecheck

# Vitest
vitest run
```

## Common Failures & Fixes

### PHP CS Fixer

- **Fix**: Run `composer run lint:fix` to auto-format
- **Prevention**: Configure IDE to format on save

### PHPStan Level Errors

- **Fix**: Add proper type annotations, fix return types
- **Prevention**: Use strict types (`declare(strict_types=1)`)

### ESLint

- **Fix**: Run `npm run lint:fix` for auto-fixable issues
- **Prevention**: Configure IDE ESLint integration

### TypeScript Errors

- **Fix**: Add missing types, fix type mismatches
- **Prevention**: Enable strict mode in tsconfig

## Bypass Rules

- `--no-verify` is **FORBIDDEN** in normal workflow
- Emergency bypass requires documented justification
- All bypasses must be addressed in next commit
