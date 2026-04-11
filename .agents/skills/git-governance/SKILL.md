---
name: git-governance
description: Git workflow governance and hygiene rules
---

# Git Governance — Bunyan

## Branch Strategy

```
main ← develop ← feature/* | fix/* | hotfix/*
```

### Branch Naming

- Features: `feature/NNN-short-description`
- Fixes: `fix/NNN-short-description`
- Hotfixes: `hotfix/NNN-short-description`

## Commit Convention

Format: `type(scope): description`

### Types

- `feat` — New feature
- `fix` — Bug fix
- `refactor` — Code restructuring
- `docs` — Documentation
- `test` — Tests
- `chore` — Build/tooling
- `style` — Formatting
- `perf` — Performance
- `ci` — CI changes

### Scopes

- `api` — Backend API
- `frontend` — Nuxt.js frontend
- `db` — Database/migrations
- `auth` — Authentication
- `project` — Project management domain
- `order` — E-commerce domain
- `workflow` — Workflow engine
- `i18n` — Translations

### Examples

```
feat(api): add project creation endpoint
fix(workflow): correct phase completion validation
docs(api): update authentication guide
test(auth): add RBAC middleware tests
refactor(frontend): extract project card component
```

## PR Rules

1. Every PR must reference an issue or spec
2. All CI checks must pass
3. At least one approval required
4. No force pushes to `main` or `develop`
5. Squash merge preferred for feature branches

## Pre-commit Checks

```
lint-staged:
  - PHP: php-cs-fixer, phpstan
  - JS/TS: eslint, prettier
  - Migrations: filename validation
```
