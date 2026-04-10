# Bunyan — Governance Charter

> Binding governance rules for the Bunyan (بنيان) project.

---

## Core Principles

1. **Stability over speed.** Never ship broken features.
2. **Specs before code.** Every feature follows SpecKit Hard Mode (Specify → Clarify → Plan → Tasks → Analyze → Implement → Closure).
3. **No shortcuts.** Skipping steps, bypassing validation, or merging without review is forbidden.
4. **RBAC everywhere.** Every API endpoint and UI route must enforce role-based access control.
5. **Arabic-first.** UI defaults to Arabic (RTL). All user-facing text must have both Arabic and English translations.

---

## Frontend UI Stack

| Layer        | Technology                                            |
| ------------ | ----------------------------------------------------- |
| UI Framework | **Nuxt UI** (`@nuxt/ui`) — Tailwind CSS v4-powered    |
| Styling      | Tailwind CSS v4 (via Nuxt UI), logical RTL properties |
| Forms        | VeeValidate + Zod schemas                             |
| State        | Pinia stores + composables                            |
| Testing      | Vitest (unit/component) + Playwright (E2E)            |
| i18n         | `@nuxtjs/i18n` — Arabic (RTL) + English               |

**Forbidden in frontend:** Bootstrap, jQuery, plain CSS classes overriding Nuxt UI tokens, or direct API calls outside composables/stores.

---

## Layer Rules

| Layer        | Allowed                                                 | Forbidden                                            |
| ------------ | ------------------------------------------------------- | ---------------------------------------------------- |
| Controllers  | Route handling, request validation, response formatting | Business logic, direct DB queries                    |
| Services     | Business logic, orchestration                           | HTTP concerns, direct DB queries                     |
| Repositories | Data access, Eloquent queries                           | Business logic, HTTP concerns                        |
| Models       | Schema definition, relationships, scopes                | Business logic, HTTP concerns                        |
| Frontend     | UI rendering, user interaction                          | Direct API calls outside composables, business logic |
| Composables  | State management, API integration                       | DOM manipulation, business logic                     |

---

## Quality Gates

Every stage must pass before merge:

```bash
# Backend
composer run lint          # Laravel Pint
composer run analyze       # PHPStan
composer run test          # PHPUnit

# Frontend (unit + component)
npm run lint               # ESLint (@nuxt/eslint)
npx nuxi typecheck         # TypeScript check
npm run test               # Vitest

# E2E
npm run test:e2e           # Playwright (critical user flows)
```

---

## Git Conventions

- Branch format: `spec/STAGE_XX_NAME`
- Commit format: `type(scope): description` (Conventional Commits)
- PR must reference stage spec
- Squash merge to develop

---

## Source of Truth

**ADR > Specs > AGENTS.md > Code**
