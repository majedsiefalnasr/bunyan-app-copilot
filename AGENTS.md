# Bunyan (بنيان) – Root AI Behavioral Contract

---

## AI Context Loading Order (Mandatory)

Before any reasoning, planning, or code generation, AI agents MUST load these in order:

1. `docs/ai/AI_BOOTSTRAP.md` — Architecture-first reasoning model
2. `docs/ai/AI_CONTEXT_INDEX.md` — AI governance pipeline entry point
3. `docs/PROJECT_CONTEXT_PRIMER.md` — Platform identity, domain rules
4. `docs/ai/AI_ENGINEERING_RULES.md` — Detailed engineering constraints
5. `docs/architecture/ADR/` — Binding architectural decisions
6. `DESIGN.md` — Design system (Vercel-inspired visual language, Geist fonts, shadow-as-border, color palette)

Conflict resolution: **ADR > Specs > AI_CONTEXT_INDEX > AI_ENGINEERING_RULES > AGENTS.md > Implementation**

---

## Platform Identity

Bunyan (بنيان): A full-stack Arabic construction services and building materials marketplace.

Apps: **Backend** (Laravel) | **Frontend** (Nuxt.js / Vue 3) | **API** (Laravel RESTful + Sanctum)

Tech Stack: Laravel (PHP) + MySQL + Nuxt.js (Vue 3) + **Nuxt UI** (`@nuxt/ui`) + Pinia

Language: Arabic-first (Full RTL support), multi-language ready.

---

## Domain Model

### Core Entities

- **Users** — Multi-role (Customer, Contractor, Supervising Architect, Field Engineer, Admin)
- **Projects** — Construction projects with phases and optional tasks
- **Phases** — Budget + status tracking, linked to projects
- **Tasks** — Optional sub-units within phases, with budget + timeline
- **Workflow Configurations** — Global + per-project overridable rules
- **Approval Rules** — Status-based approval chains
- **Reports** — Field reports with text, images, videos
- **Transactions** — Customer payments, contractor withdrawals
- **Products** — Building materials e-commerce catalog
- **Orders** — E-commerce orders linked to customers or projects

### User Roles

| Role                  | Arabic           | Dashboard                                    |
| --------------------- | ---------------- | -------------------------------------------- |
| Customer              | العميل           | Project tracking, payments                   |
| Contractor            | المقاول          | Project execution, earnings, withdrawals     |
| Supervising Architect | المهندس المشرف   | Project oversight, field engineer management |
| Field Engineer        | المهندس الميداني | Field reporting, status updates              |
| Admin                 | الإدارة          | Full platform control, configurations        |

### Workflow Engine

- Status system: Pending → In Progress → Complete → Paid
- Configurable per-project or globally
- Approval workflows with role-based approval gates
- Field engineer reporting frequency rules (daily, every X days, weekly)

---

## Non-Negotiable Rules

### RBAC (Hard Rule)

- Role-based access control on all routes via Laravel middleware
- Each role has dedicated dashboard and permission set
- Permission checks enforced server-side (never client-only)
- **Forbidden:** Unprotected admin routes, client-side-only authorization, role bypass

### Import Boundaries

- `backend/` → Laravel app (Models, Controllers, Services, Repositories)
- `frontend/` → Nuxt.js app (Pages, Components, Composables, Stores)
- `backend/` ↔ `frontend/` communication via REST API only
- **Forbidden:** Direct DB access from frontend, shared PHP/JS code, embedded business logic in controllers

### Layering

- **Frontend (Nuxt.js):** Pages, components, composables, Pinia stores. No business logic, no direct DB access.
- **API (Laravel):** Routes → Middleware (auth, RBAC) → Controllers → Services → Repositories. Controllers are thin.
- **Services:** Business logic layer. No HTTP concerns, no direct Eloquent queries.
- **Repositories:** Database access layer. Eloquent queries only here.
- **Models:** Eloquent models with relationships, scopes, and accessors. No business logic.

### Workflow Engine Rules

- Configuration snapshots at project creation for per-project overrides
- Status transitions validated server-side against workflow configuration
- Approval workflow: status change → pending approval (if required) → approved/rejected
- Field reporting rules enforced via scheduled jobs

### Error Contract

All API responses follow this unified contract:

**Success Response:**

```json
{
  "success": true,
  "data": {},
  "error": null
}
```

**Error Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly error message",
    "details": { "field": ["Field-level error message"] }
  }
}
```

**Response Components:**

- `success` (boolean) — Request success indicator (always present)
- `data` (object/array/null) — Response payload (null on error, object/array on success)
- `error` (object/null) — Error details with code, message, and field-level details (null on success)
  - `code` (string) — Machine-readable error code (e.g., `VALIDATION_ERROR`)
  - `message` (string) — User-friendly error message (localized if applicable)
  - `details` (object/null) — Field-level errors for validation; other metadata as needed (optional, null if not applicable)

### Error Code Registry

All error codes are semantic and stable (never change once defined).

| Code                           | HTTP | Description                                         |
| ------------------------------ | ---- | --------------------------------------------------- |
| `AUTH_INVALID_CREDENTIALS`     | 401  | Invalid login credentials (wrong email/password)    |
| `AUTH_TOKEN_EXPIRED`           | 401  | Authentication token expired or revoked             |
| `AUTH_UNAUTHORIZED`            | 403  | User authenticated but lacks permission             |
| `RBAC_ROLE_DENIED`             | 403  | Specific role not allowed for this action           |
| `RESOURCE_NOT_FOUND`           | 404  | Requested resource does not exist                   |
| `VALIDATION_ERROR`             | 422  | Input validation failed (bad data format)           |
| `WORKFLOW_INVALID_TRANSITION`  | 422  | Invalid state transition in workflow                |
| `WORKFLOW_PREREQUISITES_UNMET` | 422  | Prerequisites for workflow step not satisfied       |
| `PAYMENT_FAILED`               | 422  | Payment processing failed (declined card, etc.)     |
| `CONFLICT_ERROR`               | 409  | Resource conflict (duplicate, uniqueness violation) |
| `RATE_LIMIT_EXCEEDED`          | 429  | Too many requests from client                       |
| `SERVER_ERROR`                 | 500  | Internal server error (unhandled exception)         |

**Usage Notes:**

- Validation errors MUST include field-level details in `error.details`
- Authentication/authorization errors MUST NOT expose role information
- Server errors (5xx) MUST NOT expose stack traces to clients (log server-side only)
- All error messages MUST support Arabic/English localization
- Future stages MAY extend this registry with domain-specific error codes (via enum versioning)

---

## AI Behavioral Rules

### Architecture Authority

- ADRs (`docs/architecture/ADR/`) are binding. AI must never invent architecture.
- New modules must follow Laravel conventions (app/, routes/, database/, resources/)

### Migration Discipline

- Forward-only migrations in `backend/database/migrations/`
- Never modify existing migration files
- Use `php artisan make:migration` naming conventions
- Always include rollback (`down()`) methods

### Governance Tooling

AI must assume this validation pipeline runs locally and in CI:

```
composer run lint && composer run test && npm run lint && npm run typecheck && npm run test
```

If a change would break validation, AI must refuse to generate it.

### MCP Auto-Trigger Rules

- **Context7 MCP:** Auto-invoke for third-party library docs (Laravel, Nuxt, Vue 3, Bootstrap, etc.)
- **GitNexus MCP:** Auto-invoke for internal codebase context, blast radius, refactors
- **MySQL/DB MCP:** Auto-invoke for schema inspection, query validation (read-first, no destructive SQL)
- **GitHub MCP:** Auto-invoke for PR/issue/branch state checks
- MCP usage is mandatory. Training knowledge alone is insufficient for technical tasks.

### Stage Lifecycle

Validate stage status before modifying any spec. CLOSED/HARDENED stages are frozen.
See: `specs/STAGE_LIFECYCLE_POLICY.md`

### Testing

No feature is complete without: unit tests (PHPUnit) + feature tests (Laravel) + frontend tests (Vitest) + migration validated + lint passes.

### Escalation

If AI detects ambiguous spec, conflicting ADR, migration risk, or RBAC violation risk → **STOP** and request clarification. Guessing is forbidden.

---

## Architecture Self-Healing

When architecture audit detects violations, AI must: stop → diagnose → propose compliant fix → regenerate code.
Detail: `.agents/skills/architecture-self-healing/SKILL.md`

---

## Source of Truth Priority

**ADR (docs/architecture) > Specs > This file > Code**

This contract is authoritative.

---

## Reference Index

| Topic                  | Authoritative Source                   |
| ---------------------- | -------------------------------------- |
| Full engineering rules | `docs/ai/AI_ENGINEERING_RULES.md`      |
| Platform context       | `docs/PROJECT_CONTEXT_PRIMER.md`       |
| Agent governance       | `docs/AGENT_GOVERNANCE.md`             |
| SpecKit workflow       | `docs/SPEC_KIT_HARD_MODE_WORKFLOW.md`  |
| Architecture decisions | `docs/architecture/ADR/`               |
| Skill index            | `.agents/skills/SKILLS_INDEX.md`       |
| Orchestrator           | `.agents/agents/orchestrator.agent.md` |
| Design system          | `DESIGN.md`                            |

---

## Frontend Development

When building or modifying any Nuxt.js / Vue 3 UI:

- **Follow `DESIGN.md`** for visual language: Geist fonts, shadow-as-border, achromatic palette, negative letter-spacing
- Use Vue 3 Composition API (`<script setup lang="ts">` + `<template>`)
- Use **Nuxt UI** (`@nuxt/ui`) components — `UButton`, `UCard`, `UForm`, `UTable`, `UModal`, etc.
- RTL support via Tailwind logical properties + `dir="rtl"` on `<html>` (Nuxt UI native)
- Use Pinia for state management
- Use VeeValidate + Zod for form validation
- Use `@vueuse/core` for utility composables
- Use `class` (not `className`), `for` (not `htmlFor`)
- All text must support Arabic RTL layout
- Use Laravel API client composable for all API calls
- Install Nuxt UI: `npx nuxi@latest module add ui`
- Nuxt UI docs/MCP: https://ui.nuxt.com | https://mcp.nuxt.com

---

## Backend Development

When building or modifying any Laravel backend:

- Follow Laravel conventions (PSR-12, service pattern, repository pattern)
- Use Form Request classes for validation
- Use API Resources for response formatting
- Use Policies for authorization
- Use Eloquent scopes for query filtering
- Use Events + Listeners for decoupled side effects
- Use Jobs + Queues for background processing
- Use Laravel Sanctum for API authentication

---

<!-- gitnexus:start -->

# GitNexus MCP

This project is indexed by GitNexus as **bunyan-app**.

AI must use GitNexus for: understanding modules, impact analysis, dependency tracing, architectural discovery.

1. **Read `gitnexus://repo/{name}/context`** first to check index freshness
2. Match task to a skill in `.agents/skills/gitnexus/`
3. If index is stale → run `npx gitnexus analyze`

| Task                              | Skill                                                       |
| --------------------------------- | ----------------------------------------------------------- |
| Architecture / "How does X work?" | `.agents/skills/gitnexus/gitnexus-exploring/SKILL.md`       |
| Blast radius / "What breaks?"     | `.agents/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Bug tracing                       | `.agents/skills/gitnexus/gitnexus-debugging/SKILL.md`       |
| Refactoring                       | `.agents/skills/gitnexus/gitnexus-refactoring/SKILL.md`     |
| Tools & schema reference          | `.agents/skills/gitnexus/gitnexus-guide/SKILL.md`           |
| CLI commands                      | `.agents/skills/gitnexus/gitnexus-cli/SKILL.md`             |

<!-- gitnexus:end -->

<!-- rtk-instructions v2 -->

# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. Safe passthrough if no dedicated filter exists.

```bash
# Always use rtk, even in chains:
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## Quick Reference

| Category | Commands                                               | Savings |
| -------- | ------------------------------------------------------ | ------- |
| Tests    | `rtk php artisan test`, `rtk vitest run`               | 90-99%  |
| Build    | `rtk npm run build`, `rtk composer run lint`           | 70-87%  |
| Git      | `rtk git status/log/diff/add/commit/push`              | 59-80%  |
| GitHub   | `rtk gh pr view/checks`, `rtk gh run list`             | 26-87%  |
| Packages | `rtk composer list`, `rtk npm list`                    | 70-90%  |
| Files    | `rtk ls/read/grep/find`                                | 60-75%  |
| Analysis | `rtk err <cmd>`, `rtk log <file>`, `rtk summary <cmd>` | 70-90%  |
| Meta     | `rtk gain`, `rtk discover`, `rtk proxy <cmd>`          | —       |

<!-- /rtk-instructions -->
