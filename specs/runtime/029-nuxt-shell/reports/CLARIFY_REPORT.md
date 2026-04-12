# Clarify Report — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12T00:10:00Z

## Clarification Summary

| Metric                | Value                              |
| --------------------- | ---------------------------------- |
| Questions Asked       | 5                                  |
| Questions Resolved    | 5                                  |
| Spec Sections Updated | Clarifications + API table + Scope |

## Resolved Clarifications

| #   | Topic                        | Resolution                                                                                                           | Impact                         |
| --- | ---------------------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------ |
| 1   | Tablet sidebar behavior      | Icon-only mode (768–1024px); expand on hover/click; 3 states: full / icon-only / drawer                              | Layout CSS + sidebar component |
| 2   | Dark mode + direction store  | Both Pinia (reactive) + localStorage (persistent) via new `usePreferences` composable                                | New composable added to scope  |
| 3   | 401 handling / token refresh | `useApi` on 401: clear `auth_token` cookie → redirect to `/auth/login`. No refresh needed (Sanctum long-lived token) | useApi implementation          |
| 4   | Role nav items visibility    | Each role sees ONLY their own nav items (no disabled items — prevents role info leakage)                             | Nav config + security          |
| 5   | Breadcrumb source            | Auto-generates from `route.meta.breadcrumb`; falls back to path segments; `setBreadcrumb()` override                 | useBreadcrumb API              |

## Security / Architecture Flags Resolved

| Severity | Flag                                           | Resolution                                                                             |
| -------- | ---------------------------------------------- | -------------------------------------------------------------------------------------- |
| 🔴       | Bearer token vs. httpOnly cookie contradiction | Clarified: Sanctum API token in accessible cookie, Bearer header — not SPA cookie mode |
| 🔴       | Missing refresh endpoint                       | Removed — no refresh endpoint exists; 401 → re-login directly                          |
| 🟡       | `role.ts` middleware unspecified               | Added to spec: filters nav items + guards route via `route.meta.requiredRole`          |
| 🟡       | `usePreferences` missing from scope            | Added `usePreferences` composable to scope + file delivery map                         |
| 🟡       | SSR hydration flash (dir attribute)            | Added `plugins/direction.client.ts` to scope                                           |

## Remaining Ambiguities

None — all clarifications resolved.

## Checklists Generated

| Checklist     | Path                        | Items   |
| ------------- | --------------------------- | ------- |
| Requirements  | checklists/requirements.md  | 109     |
| Security      | checklists/security.md      | 17      |
| Performance   | checklists/performance.md   | 16      |
| Accessibility | checklists/accessibility.md | 20      |
| **Total**     |                             | **162** |
