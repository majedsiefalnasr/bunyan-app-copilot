---
name: script-system-governance
description: Script consistency and validation
---

# Script System Governance — Bunyan

## Backend Scripts (composer.json)

```json
{
  "scripts": {
    "lint": "php-cs-fixer fix --dry-run --diff",
    "lint:fix": "php-cs-fixer fix",
    "analyze": "phpstan analyse",
    "test": "php artisan test",
    "test:coverage": "php artisan test --coverage",
    "dev": "php artisan serve"
  }
}
```

## Frontend Scripts (package.json)

```json
{
  "scripts": {
    "dev": "nuxt dev",
    "build": "nuxt build",
    "preview": "nuxt preview",
    "lint": "eslint .",
    "lint:fix": "eslint . --fix",
    "typecheck": "nuxt typecheck",
    "test": "vitest run",
    "test:watch": "vitest"
  }
}
```

## Rules

1. Script names must be consistent across backend and frontend
2. All scripts must be documented
3. CI scripts must not rely on interactive prompts
4. Deployment scripts require explicit confirmation
5. Never add scripts that bypass safety checks (e.g., `--no-verify`)
