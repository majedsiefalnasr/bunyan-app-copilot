---
name: architecture-intelligence
description: Architecture reasoning and validation layer
---

# Architecture Intelligence — Bunyan

## Architecture Overview

Bunyan follows a **Clean Architecture** pattern with clear layer separation.

## Layer Map

```
┌─────────────────────────────────────────┐
│           Frontend (Nuxt.js)            │
│  Pages → Components → Composables       │
│  Stores (Pinia) → API Client            │
├─────────────────────────────────────────┤
│              API Layer                   │
│  Routes → Middleware → Controllers       │
├─────────────────────────────────────────┤
│           Business Logic                 │
│  Services → Events → Jobs               │
├─────────────────────────────────────────┤
│           Data Access                    │
│  Repositories → Models → Migrations     │
├─────────────────────────────────────────┤
│              Database                    │
│  MySQL (utf8mb4) → Redis (Cache/Queue)  │
└─────────────────────────────────────────┘
```

## Domain Separation

### Construction Management Domain

- Projects, Phases, Tasks
- Workflow engine + state machines
- Reports, documents, progress tracking
- Role-based access control

### E-Commerce Domain

- Products, Categories
- Cart, Orders, Payments
- Inventory management
- Delivery tracking

### Shared Domain

- Authentication (Laravel Sanctum)
- User management + RBAC
- Notifications
- File management
- Audit logging

## Module Registration

New modules must be documented in:

1. `docs/architecture/ARCHITECTURE_MAP.json`
2. Relevant ADR if new patterns introduced
3. Updated AGENTS.md if new governance rules

## Validation Commands

```bash
# Backend
composer run lint          # PHP CS Fixer
composer run analyze       # PHPStan
php artisan test           # PHPUnit

# Frontend
npm run lint              # ESLint
npm run typecheck         # Vue TSC
npm run test              # Vitest
```
