# Plan Report — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12T00:20:00Z
> **Stage File:** `specs/phases/07_FRONTEND_APPLICATION/STAGE_29_NUXT_SHELL.md`

---

## Plan Summary

| Metric                | Value                                                                                     |
| --------------------- | ----------------------------------------------------------------------------------------- |
| New Layouts           | 3 (`default.vue`, `auth.vue`, `public.vue`)                                               |
| New Pinia Stores      | 2 (`stores/auth.ts`, `stores/ui.ts`)                                                      |
| New Composables       | 5 (`useAuth`, `useNotification`, `useBreadcrumb`, `useDirection`, `usePreferences`)       |
| New Components        | 7 (AppNavigation, AppHeader, AppSidebar, MobileDrawer, AppBreadcrumb, AppFooter + config) |
| New Plugins           | 1 (`plugins/direction.client.ts`)                                                         |
| New Types             | 1 (`types/index.ts`)                                                                      |
| Modified Files        | 4 (`nuxt.config.ts`, `app.vue`, `error.vue`, `locales/ar.json`, `locales/en.json`)        |
| New Unit Test Files   | 5                                                                                         |
| Total Files Touched   | 30                                                                                        |
| Implementation Phases | 6 (sequential)                                                                            |

---

## Architecture Decisions

### AD-1: Token Derived from Cookie (Auth State Consistency)

`stores/auth.ts` uses `computed(() => useCookie('auth_token').value)` for the token — NOT an independent `ref`. This ensures that when `useApi.ts` clears the cookie on a 401 response, `isAuthenticated` automatically becomes `false` without any store coordination. Eliminates the auth-loop failure mode where cookie is cleared but Pinia store still has a stale token.

### AD-2: Module-Level Breadcrumb State

`useBreadcrumb.ts` declares `_manualBreadcrumbs` at module-level (outside the composable function). This ensures all call sites (`AppBreadcrumb.vue` reading + page calling `setBreadcrumbs()`) share the same reactive reference — composable instance isolation would break cross-component reactivity.

### AD-3: AppNavigation as Renderless Provider

`AppNavigation.vue` is a renderless component that uses `provide()` to inject resolved nav items downward. It does not render UI. This avoids prop-drilling and keeps business logic (role → nav item resolution) separate from presentational components.

### AD-4: No UHeader/UFooter/UNavigationTree (Nuxt UI v3 Correction)

Nuxt UI v3 does not export `UHeader`, `UFooter`, `UNavigationTree`, or `USidebar`. The plan uses:

- Custom `<header>` + `<footer>` HTML elements with DESIGN.md shadow-as-border
- `UNavigationMenu` (with `orientation="vertical"`) for the sidebar navigation tree
- `UDrawer` for the mobile slide-out navigation

### AD-5: Existing Toast System Retained

The existing `useToast.ts` + `errorStore.ts` custom toast system is retained. `useNotification.ts` wraps it with semantic domain methods. No migration to Nuxt UI `useToast()` required — the existing system is already integrated with the error handler.

### AD-6: NuxtLayout Critical Fix

`app/app.vue` is missing `<NuxtLayout>` — layouts are silently ignored without it. The plan adds `<NuxtLayout>` wrapper in Phase 5.1 as the **first** change.

### AD-7: RBAC Client-Side is Presentation-Only

Navigation filtering in `AppNavigation.vue` and `hasRole()` in stores are **presentation guards only**. Server-side Laravel middleware and Policies enforce actual RBAC. This is explicitly documented in the plan to prevent downstream stages from treating client checks as sufficient.

### AD-8: apiFetch (not $fetch) in useAuth

`logout()` in `useAuth.ts` uses `const { apiFetch } = useApi()` — NOT `api.$fetch`. The existing `useApi.ts` returns `{ apiFetch }` only. Using `apiFetch` ensures all interceptors (auth header, Accept-Language, error handler) run consistently.

---

## Research Findings (from research.md)

| Finding                                          | Impact                                          |
| ------------------------------------------------ | ----------------------------------------------- |
| `@nuxt/ui` version is 4.6.1 (not v3 as labelled) | Forward-compatible; plan uses correct API       |
| `@nuxtjs/i18n` version is 10.2.4                 | Use `useI18n()` destructured, not `useLocale()` |
| `UHeader`, `UFooter`, `UNavigationTree` absent   | Custom HTML elements required (see AD-4)        |
| `UNavigationMenu` exists with `orientation` prop | Used for both desktop sidebar and header nav    |
| `app.vue` missing `<NuxtLayout>`                 | Critical fix in Phase 5.1 (see AD-6)            |
| Existing custom toast system is functional       | Retained; `useNotification` wraps it (see AD-5) |

---

## Guardian Verdicts

| Guardian              | Verdict  | Notes                                                             |
| --------------------- | -------- | ----------------------------------------------------------------- |
| Architecture Guardian | **PASS** | 4 violations found and remediated before re-audit                 |
| API Designer          | **PASS** | 1 violation (api.$fetch → apiFetch) remediated before final audit |

### Remediated Violations (before final PASS)

| #   | Violation                                                                | Fix Applied                                                           |
| --- | ------------------------------------------------------------------------ | --------------------------------------------------------------------- |
| 1   | Auth state inconsistency (cookie vs. store ref, potential redirect loop) | Token derived from `useCookie()` computed (Option A)                  |
| 2   | `useBreadcrumb` shared state broken (module-scope vs instance-scope)     | `_manualBreadcrumbs` moved to module level                            |
| 3   | RBAC acknowledgment absent in plan                                       | Explicit security-boundary note added to plan Section 1.1             |
| 4   | AppFooter hardcoded Arabic string                                        | `{{ t('layout.footer_copyright') }}` + i18n keys in both locale files |
| 5   | `api.$fetch` vs `api.apiFetch` mismatch in logout()                      | Corrected to `const { apiFetch } = useApi()`                          |

---

## Risk Assessment

| Risk Level | Count | Details                                                                                                                                                   |
| ---------- | ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 🔴 HIGH    | 1     | `stores/auth.ts` — auth state architecture; any mistake here causes login loops or security gaps                                                          |
| 🟡 MEDIUM  | 4     | `useDirection.ts` (SSR/client sync), `default.vue` (layout composition), `app.vue` NuxtLayout fix, `locales/` (missing keys cause silent render failures) |
| 🟢 LOW     | 25    | All remaining files (components, types, non-auth composables, tests)                                                                                      |

---

## Implementation Phases

```
Phase 0 — Foundation        3 files  (nuxt.config.ts, plugin, types)
Phase 1 — Pinia Stores       2 files  (auth.ts, ui.ts)
Phase 2 — Core Composables   5 files  (useAuth, useNotification, useBreadcrumb, useDirection, usePreferences)
Phase 3 — Navigation         7 files  (AppNavigation, AppHeader, AppSidebar, MobileDrawer, AppBreadcrumb, AppFooter, navigation config)
Phase 4 — Layouts            3 files  (default.vue, auth.vue, public.vue)
Phase 5 — Core File Updates  5 files  (app.vue, error.vue, ar.json, en.json)
Phase 6 — Tests              5 files  (useDirection, useNotification, useBreadcrumb, useAuth, usePreferences + AppNavigation)
```

Each phase is strictly sequential (subsequent phases depend on prior phases).

---

## External Dependencies

| Library        | Version | Used For                                                                                                    |
| -------------- | ------- | ----------------------------------------------------------------------------------------------------------- |
| `@nuxt/ui`     | 4.6.1   | UNavigationMenu, UDrawer, UBreadcrumb, UAlert, UCard, UButton, UAvatar, UDropdownMenu, USkeleton, UProgress |
| `@nuxtjs/i18n` | 10.2.4  | Language switching, locale-prefixed routing                                                                 |
| `@vueuse/core` | —       | `useLocalStorage` in useDirection/usePreferences                                                            |
| `pinia`        | —       | `useAuthStore`, `useUiStore`                                                                                |

---

## Plan Artifacts

| Artifact                     | Path                                                        |
| ---------------------------- | ----------------------------------------------------------- |
| research.md                  | `specs/runtime/029-nuxt-shell/research.md`                  |
| data-model.md                | `specs/runtime/029-nuxt-shell/data-model.md`                |
| plan.md                      | `specs/runtime/029-nuxt-shell/plan.md`                      |
| contracts/api-integration.md | `specs/runtime/029-nuxt-shell/contracts/api-integration.md` |
