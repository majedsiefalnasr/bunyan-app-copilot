# STAGE_29 — NUXT_SHELL: Task List

> **Phase:** 07_FRONTEND_APPLICATION
> **Stage:** 029-nuxt-shell
> **Total Tasks:** 30
> **Generated:** 2026-04-12
> **Spec:** `specs/runtime/029-nuxt-shell/spec.md` > **Plan:** `specs/runtime/029-nuxt-shell/plan.md`

---

## Legend

- `[P]` — Task can run **in parallel** with other `[P]` tasks in the same phase
- `[US#]` — User story tag (from spec.md `US1`–`US15`)
- Tasks in each phase depend on **all** tasks in prior phases being complete
- Tasks without `[US#]` are foundational (no single story — cross-cutting)

---

## Phase 0 — Foundation

> Sequential dependency chain. All downstream phases depend on these three tasks.

- [x] T001 Update `frontend/nuxt.config.ts` — add `runtimeConfig.public.apiBaseUrl`, `colorMode: { classSuffix: '' }`, and `app.head.htmlAttrs: { lang: 'ar', dir: 'rtl' }`; keep all existing i18n config unchanged
- [x] T002 Create `frontend/plugins/direction.client.ts` — client-only plugin, reads `localStorage.getItem('bunyan_direction')`, applies to `document.documentElement.dir` before Vue hydration to prevent CLS flash; falls back to HTML `dir` attribute default
- [x] T003 Create `frontend/types/index.ts` — export `UserRole` enum, `UserRoleType` union, `NavItem`, `BreadcrumbItem`, `AuthUser`, `Direction`, `Locale`, `UiPreferences`, `NavItemsByRole`, `DropdownMenuGroup`, and `PageMeta` module augmentation (`route.meta.breadcrumb`)

---

## Phase 1 — Pinia Stores

> Sequential. Both stores must be complete before Phase 2 composables begin.

- [x] T004 Create `frontend/stores/auth.ts` — `useAuthStore`: `user: Ref<AuthUser | null>`, `token` computed from `useCookie('auth_token')` (NOT a separate ref — cookie-reactive pattern), `isAuthenticated` and `role` as computed refs, `setUser()`, `clearAuth()`, `initFromCookie()`, `hasRole()`; include RBAC acknowledgment comment (presentation-layer only)
- [x] T005 Create `frontend/stores/ui.ts` — `useUiStore`: `isSidebarOpen` (persisted to `localStorage` under `bunyan_sidebar_open`, default `true`), `isDrawerOpen` (default `false`), `isPageLoading` (default `false`); actions: `toggleSidebar()`, `openDrawer()`, `closeDrawer()`, `toggleDrawer()`, `setPageLoading(value: boolean)`

---

## Phase 2 — Core Composables

> All five tasks `[P]` — run concurrently after Phase 1 is complete.

- [x] T006 [P] [US1] Create `frontend/composables/useAuth.ts` — wraps `useAuthStore()`; exports `user`, `isAuthenticated`, `role`, `hasRole()`; `logout()` calls `DELETE /api/v1/auth/logout` via `apiFetch`, then `store.clearAuth()`, then `navigateTo` locale-prefixed login regardless of API failure; `fetchCurrentUser()` calls `GET /api/v1/auth/me` and calls `store.setUser()` on success
- [x] T007 [P] [US12] Create `frontend/composables/useNotification.ts` — semantic wrapper over existing `useToast()`; exports `notifySuccess(msg, duration?)`, `notifyError(msg, duration?)`, `notifyWarning(msg, duration?)`, `notifyInfo(msg, duration?)`, `dismiss(id)`; delegates ENTIRELY to `useToast()` — no duplicate toast logic
- [x] T008 [P] [US1] Create `frontend/composables/useBreadcrumb.ts` — `_manualBreadcrumbs` declared using `useState<BreadcrumbItem[] | null>('breadcrumbs.manual', () => null)` at the top-of-module call site (outside the composable function) — `useState` provides per-request isolation on SSR and shared cross-instance reactivity on the client; exports `breadcrumbs: ComputedRef<BreadcrumbItem[]>` (route meta first, then manual override, then `[]`), `setBreadcrumbs(items)`, `clearBreadcrumbs()`
- [x] T009 [P] [US9] Create `frontend/composables/useDirection.ts` — reads `localStorage.getItem('bunyan_direction')` on init, falls back to locale-derived direction (`ar` → `rtl`, `en` → `ltr`); `direction: Ref<Direction>`, `toggle()`, `setDirection(dir)`; tracks `hasManualOverride` (stored as `bunyan_direction_manual` in localStorage); `watch(locale)` auto-syncs direction only if no manual override
- [x] T010 [P] [US9] [US10] [US11] Create `frontend/composables/usePreferences.ts` — facade composable; re-exports `direction` and `toggleDirection()` from `useDirection()`, `locale` and `setLocale()` from `useI18n()`, `colorMode` and `toggleColorMode()` (cycles `light → dark → system`) from `useColorMode()`; adds NO new logic

---

## Phase 3 — Navigation Components + Config

> T011 must complete first (data dependency). T012–T017 are `[P]` after T011.

- [x] T011 [US4] [US5] [US6] [US7] [US8] Create `frontend/app/config/navigation.ts` — export constant `NAV_ITEMS_BY_ROLE: Record<UserRole, NavItem[]>` for all five roles (Customer: dashboard/projects/orders/payments; Contractor: dashboard/projects/earnings/withdrawals; SupervisingArchitect: dashboard/projects/field_engineers/reports; FieldEngineer: dashboard/my_projects/submit_report; Admin: dashboard/users/projects/products/orders/configuration/reports); routes without locale prefix; Iconify icons from `i-heroicons-*`
- [x] T012 [P] [US4] [US5] [US6] [US7] [US8] Create `frontend/app/components/navigation/AppNavigation.vue` — renderless provider component; reads `useAuthStore().role`; maps `NAV_ITEMS_BY_ROLE[role]`; resolves labels via `t(item.labelKey)`; adds locale prefix via `useLocaleRoute()`; provides resolved items downward via `provide('navItems', resolvedItems)`; wraps `<slot />`
- [x] T013 [P] [US1] [US9] [US10] [US11] Create `frontend/app/components/navigation/AppHeader.vue` — sticky `<header>`, shadow-as-border `shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]`, `bg-white dark:bg-[#0a0a0a]`; logo start, controls cluster end (language switcher `UButton`, direction toggle `UButton`, dark mode `UButton`, user `UDropdownMenu` + `UAvatar`); hamburger `UButton` visible only `< md`; props: `showHamburger?: boolean`, `title?: string`; emits `hamburger-click`
- [x] T014 [P] [US1] [US4] [US5] [US6] [US7] [US8] Create `frontend/app/components/navigation/AppSidebar.vue` — desktop `<aside class="hidden md:flex flex-col w-64">`, logical shadow-border `shadow-[1px_0_0_0_rgba(0,0,0,0.08)]` on inline-end, `bg-white dark:bg-[#0a0a0a]`; `UNavigationMenu orientation="vertical" :items="items"`; `UAvatar` and `UBadge` (admin indicator) in sidebar footer; receives `items: NavItem[]` prop (no `inject` — prop-based for testability)
- [x] T015 [P] [US13] Create `frontend/app/components/navigation/MobileDrawer.vue` — `UDrawer v-model:open="isDrawerOpen" :direction="direction === 'rtl' ? 'right' : 'left'"` (direction from `useDirection()`; UDrawer accepts `'left'|'right'|'top'|'bottom'` only — `side` prop does not exist in Nuxt UI v4.6.1); `UNavigationMenu orientation="vertical" :items="items"` inside drawer body; controlled by `useUiStore().isDrawerOpen`; receives `items: NavItem[]` prop
- [x] T016 [P] [US1] Create `frontend/app/components/navigation/AppBreadcrumb.vue` — `<UBreadcrumb v-if="breadcrumbs.length" :items="breadcrumbs" class="px-4 py-2" />`; reads from `useBreadcrumb().breadcrumbs`; renders nothing when breadcrumbs array is empty
- [x] T017 [P] [US1] [US3] Create `frontend/app/components/navigation/AppFooter.vue` — `<footer>` with top shadow-as-border `shadow-[0_-1px_0_0_rgba(0,0,0,0.08)]`, `py-4 px-6`, `text-sm text-[#666666]`; displays `t('layout.footer_copyright')` and version string; no Nuxt UI components — pure HTML + Tailwind

---

## Phase 4 — Layouts

> T018 (default) is the most complex and must complete before any downstream layout integration. T019 and T020 are `[P]` with each other but can start once Phase 3 is done.

- [x] T018 [US1] [US13] [US14] Create `frontend/layouts/default.vue` — full authenticated shell: `AppNavigation` wrapping `AppHeader` + `div.flex.flex-1` containing `AppSidebar` + `<main>` (with `AppBreadcrumb`, `UProgress v-if="isPageLoading" animation="carousel" class="fixed top-0 start-0 end-0 z-50 h-0.5"`, and `<slot />`), `AppFooter`, `MobileDrawer`; bridge navItems: `const navItems = inject<NavItem[]>('navItems', [])` and pass as `:items` prop to both `AppSidebar` and `MobileDrawer`; page load hook: `useNuxtApp().hook('page:start', ...)` / `page:finish`; auth guard via `useAuthStore().isAuthenticated`; forwards hamburger-click from `AppHeader` to `useUiStore().toggleDrawer()`
- [x] T019 [P] [US2] Create `frontend/layouts/auth.vue` — minimal layout: full-viewport centered flex `bg-[#fafafa] dark:bg-[#0a0a0a] p-4`; `UCard class="w-full max-w-md"` with shadow-as-border `:ui="{ root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]' }"`; `#header` slot shows centered `<img src="/logo.svg">`; `<slot />` for page content; guest guard: redirect to role dashboard if `isAuthenticated`
- [x] T020 [P] [US3] Create `frontend/layouts/public.vue` — marketing layout: sticky minimal `<header>` with logo + `UButton` links (Sign In / Register) + `AppFooter`; no sidebar; `<main class="flex-1"><slot /></main>`; routes via `useLocaleRoute()`; DESIGN.md `bg-white/80 backdrop-blur` header

---

## Phase 5 — Core File Updates

> T021 must complete before any integration testing. T022–T024 are `[P]` with each other after Phase 4.

- [x] T021 [US9] [US11] Update `frontend/app/app.vue` — add `<NuxtLayout>` wrapper around `<NuxtPage />`; call `useDirection()` and use `direction` ref in `useHead({ htmlAttrs: { dir: direction, lang: htmlLang } })`; keep all existing `useI18n().locale`, `useHead`, and `GlobalErrorBoundary` / `ErrorToast` logic; do NOT add `<UNotifications />`
- [x] T022 [P] [US15] Update `frontend/app/error.vue` — update `<template>` only: centered `div.min-h-screen.flex.items-center.justify-center.p-6.bg-[#fafafa]` containing `UCard` with shadow-as-border; `UAlert :color="statusCode === 404 ? 'warning' : 'error'"` with title/description/icon; `#footer` slot with `UButton` (go back) and `UButton` (go home); keep ALL existing `<script setup>` error handling logic intact
- [x] T023 [P] [US4] [US5] [US6] [US7] [US8] [US11] Update `frontend/locales/ar.json` — **merge** (do not replace) new top-level keys: `nav.*` (dashboard, projects, my_projects, orders, payments, earnings, withdrawals, field_engineers, reports, submit_report, users, products, configuration), `layout.*` (toggle_sidebar, toggle_direction, toggle_dark_mode, switch_language, open_menu, close_menu, language_ar, language_en, direction_rtl, direction_ltr, loading, footer_copyright), `roles.*` (customer, contractor, supervising_architect, field_engineer, admin)
- [x] T024 [P] [US4] [US5] [US6] [US7] [US8] [US11] Update `frontend/locales/en.json` — **merge** (do not replace) same top-level key structure as T023 with English values; `layout.footer_copyright` = `"© 2026 Bunyan. All rights reserved."`

---

## Phase 6 — Tests

> All six test tasks are `[P]` — run concurrently after Phase 5 is complete.

- [x] T025 [P] [US9] Create `frontend/tests/unit/composables/useDirection.test.ts` — test cases: `setDirection('rtl')` sets `document.documentElement.dir` and localStorage; `setDirection('ltr')` equivalent; `toggle()` flips current direction; on init reads stored localStorage value; defaults to `'rtl'` when no stored value; `hasManualOverride` becomes `true` after manual toggle
- [x] T026 [P] [US12] Create `frontend/tests/unit/composables/useNotification.test.ts` — test cases: `notifySuccess()` delegates to `useToast().showSuccess()`; `notifyError()` delegates to `useToast().showError()`; `notifyWarning()` delegates to `useToast().showWarning()`; `notifyInfo()` delegates to `useToast().showInfo()`; `dismiss(id)` delegates to `useToast().removeToast(id)`
- [x] T027 [P] [US1] Create `frontend/tests/unit/composables/useBreadcrumb.test.ts` — test cases: `route.meta.breadcrumb` set → `breadcrumbs` returns it; no route meta → returns `[]`; after `setBreadcrumbs([...])` → `breadcrumbs` reflects manual value; `clearBreadcrumbs()` reverts to route meta; `useState`-backed state is shared across two independent `useBreadcrumb()` call sites (cross-instance reactivity test)
- [x] T028 [P] [US1] Create `frontend/tests/unit/composables/useAuth.test.ts` — test cases: `isAuthenticated` is `false` when store has no user; `logout()` fires `DELETE /api/v1/auth/logout` then clears store; `logout()` clears store even when API call fails (fire-and-forget); `hasRole('admin')` returns `true` when user role is admin; `hasRole(['admin', 'contractor'])` returns `true` for contractor user
- [x] T029 [P] [US9] [US10] [US11] Create `frontend/tests/unit/composables/usePreferences.test.ts` — test cases: `toggleDirection()` delegates to `useDirection().toggle()`; `toggleColorMode()` cycles `light → dark → system → light`; `setLocale('en')` calls `useI18n().setLocale('en')`; all exports (`direction`, `toggleDirection`, `locale`, `setLocale`, `colorMode`, `toggleColorMode`) are defined and callable
- [x] T030 [P] [US4] [US5] [US6] [US7] [US8] Create `frontend/tests/unit/components/AppNavigation.test.ts` — test cases: Customer role → nav items include `nav.dashboard`, `nav.projects`, `nav.orders`, `nav.payments`; Customer role → does NOT include admin-only routes (users, configuration); Admin role → nav includes all seven sections; Field Engineer → includes `nav.submit_report`; resolved item labels use `t()` values (not raw i18n keys); `provide('navItems', ...)` is called with correct resolved items

---

## Dependencies

```
Phase 0 (T001–T003)
  └─► Phase 1 (T004–T005)
        └─► Phase 2 (T006–T010, all parallel)
              └─► Phase 3 (T011 → then T012–T017 parallel)
                    └─► Phase 4 (T018 → then T019–T020 parallel)
                          └─► Phase 5 (T021 → then T022–T024 parallel)
                                └─► Phase 6 (T025–T030, all parallel)
```

**Critical path:** T001 → T002 → T003 → T004 → T005 → T006 → T011 → T012 → T018 → T021

---

## Parallel Execution Examples

**Phase 2 — all 5 composables in parallel:**

```
T006 useAuth.ts
T007 useNotification.ts   ← all start simultaneously after T004+T005
T008 useBreadcrumb.ts
T009 useDirection.ts
T010 usePreferences.ts
```

**Phase 3 — 6 navigation components in parallel (after T011):**

```
T012 AppNavigation.vue
T013 AppHeader.vue
T014 AppSidebar.vue       ← all start simultaneously after T011
T015 MobileDrawer.vue
T016 AppBreadcrumb.vue
T017 AppFooter.vue
```

**Phase 6 — all 6 tests in parallel:**

```
T025 useDirection.test.ts
T026 useNotification.test.ts
T027 useBreadcrumb.test.ts   ← all start simultaneously after T021–T024
T028 useAuth.test.ts
T029 usePreferences.test.ts
T030 AppNavigation.test.ts
```

---

## Implementation Strategy

1. **Foundation first**: `nuxt.config.ts` and `types/index.ts` are pure configuration — no Nuxt dependency calls. Safe to start immediately.
2. **Never recreate existing composables**: `useApi.ts`, `useToast.ts`, `useErrorHandler.ts` already exist. `useNotification.ts` wraps `useToast()` without copying it.
3. **Cookie-reactive token pattern**: The auth store's `token` is a `computed` over `useCookie('auth_token')` — this ensures 401 responses from `useApi.ts` automatically invalidate `isAuthenticated` without explicit store coordination.
4. **Module-level breadcrumb state**: `_manualBreadcrumbs` is declared outside `useBreadcrumb()` so all call sites share one ref. This is a deliberate pattern — do not move it inside the function.
5. **RBAC is server-side**: Client-side role filtering in navigation and layouts is presentation-only. All actual access control is enforced by Laravel Policies and middleware.
6. **Direction plugin order**: `plugins/direction.client.ts` runs before Vue mounts, so `document.documentElement.dir` is set before first render — no layout shift.

---

## Risk Table

| Task ID | Risk      | Reason                                                                                                                                                                 |
| ------- | --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T004    | 🔴 HIGH   | Auth store — token derived from `useCookie('auth_token')` instead of a ref; incorrect pattern breaks all auth state and can cause redirect loops                       |
| T018    | 🔴 HIGH   | Default layout — assembles all Phase 3 components; if `AppNavigation` provide/inject pattern fails, nav items silently disappear for all roles                         |
| T021    | 🟡 MEDIUM | `app.vue` update — adding `<NuxtLayout>` wrapper; incorrect placement breaks ALL pages in ALL layouts; must keep `GlobalErrorBoundary` and `ErrorToast` intact         |
| T008    | 🟡 MEDIUM | `useBreadcrumb.ts` — module-level `_manualBreadcrumbs` ref is a non-standard pattern; if accidentally moved inside function, cross-instance reactivity breaks silently |
| T009    | 🟡 MEDIUM | `useDirection.ts` — `hasManualOverride` logic interacts with locale watcher; incorrect condition causes infinite direction-flipping loop on locale change              |
| T011    | 🟡 MEDIUM | `navigation.ts` — routes without locale prefix depend on `useLocaleRoute()` being applied at runtime; if prefix is hard-coded, locale-prefixed routing breaks          |
| T002    | 🟡 MEDIUM | `direction.client.ts` — must be `*.client.ts` (SSR-safe); accessing `localStorage` in an SSR context throws a `ReferenceError`                                         |
| T019    | 🟢 LOW    | `auth.vue` — guest guard redirect logic; incorrect check (use `isAuthenticated` from store, not raw cookie) may bypass redirect                                        |
| T022    | 🟢 LOW    | `error.vue` update — template-only change; script logic preserved; low blast radius                                                                                    |
| T006    | 🟢 LOW    | `useAuth.ts` logout — API failure must not block `clearAuth()`; missing `finally` block causes auth state to remain dirty on API failure                               |
| T023    | 🟢 LOW    | `ar.json` — merge operation only; replacing existing content would break ALL existing Arabic translations across the platform                                          |
| T024    | 🟢 LOW    | `en.json` — same merge risk as T023                                                                                                                                    |
