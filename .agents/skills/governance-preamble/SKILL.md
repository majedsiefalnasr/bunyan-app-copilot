---
name: governance-preamble
description: Shared governance declaration for all Bunyan agents
---

# Governance Preamble — Bunyan بنيان

This skill is loaded by all Bunyan agents as their governance baseline.

## Platform Identity

**Bunyan (بنيان)**: A full-stack Arabic construction services and building materials marketplace.

- **Backend**: Laravel (PHP) + MySQL
- **Frontend**: Nuxt.js 3 (Vue 3) + Bootstrap 5 RTL
- **Auth**: Laravel Sanctum
- **ORM**: Eloquent
- **Language**: Arabic-first, English support

## Source of Truth Priority

**ADR > Specs > AGENTS.md > Code**

## Mandatory Loading Order

1. `AGENTS.md` — Root behavioral contract
2. `docs/ai/AI_BOOTSTRAP.md` — Architecture reasoning model
3. `docs/ai/AI_CONTEXT_INDEX.md` — AI governance pipeline
4. `docs/PROJECT_CONTEXT_PRIMER.md` — Platform identity

## Non-Negotiable Rules

### RBAC Enforcement (Hard Rule)

- 5 roles: Customer (عميل), Contractor (مقاول), Supervising Architect (مهندس مشرف), Field Engineer (مهندس ميداني), Admin (إدارة)
- Every API route MUST have explicit role authorization
- Never trust client-side role checks alone
- Middleware-based authorization on all protected routes

### Layering

- **Controllers**: HTTP handling only — no business logic
- **Services**: Business logic — no HTTP, no direct DB
- **Repositories**: Database queries — no business logic
- **Models**: Eloquent models — relationships, scopes, casts

### Import Boundaries

- Controllers → Services ✅
- Services → Repositories ✅
- Repositories → Models ✅
- Controllers → Repositories ❌
- Controllers → Models ❌ (direct queries)
- Frontend → Backend internals ❌

### Error Contract

All API responses: `{ success: boolean, data: object | null, error: { code: string, message: string } | null }`

### Migration Discipline

- Forward-only migrations in `backend/database/migrations/`
- Never modify existing migration files
- Use schema naming: `YYYY_MM_DD_HHMMSS_verb_noun_table.php`

## Validation Pipeline

```
composer run lint && composer run analyze && php artisan test && npm run lint && npm run typecheck && npm run test
```

## Escalation

If AI detects ambiguous spec, conflicting ADR, migration risk, or RBAC bypass risk → **STOP** and request clarification.
