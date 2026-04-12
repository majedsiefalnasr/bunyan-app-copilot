# Analyze Report — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION
> **Step:** analyze
> **Date:** 2026-04-12T00:30:00Z
> **Final Gate:** ✅ APPROVED — Implementation AUTHORIZED

---

## Structural Drift Audit (5.1)

### Existing Files — No Conflicts

| File                                      | Status         | Notes                                         |
| ----------------------------------------- | -------------- | --------------------------------------------- |
| `frontend/composables/useApi.ts`          | ✅ No conflict | Production-ready, not modified in this stage  |
| `frontend/composables/useToast.ts`        | ✅ No conflict | Retained; `useNotification.ts` wraps it       |
| `frontend/composables/useErrorHandler.ts` | ✅ No conflict | Not modified                                  |
| `frontend/composables/useLocaleRoute.ts`  | ✅ No conflict | Used by navigation config                     |
| `frontend/stores/errorStore.ts`           | ✅ No conflict | Not modified                                  |
| `frontend/types/ambient.d.ts`             | ✅ No conflict | New `types/index.ts` is additive              |
| `frontend/layouts/` (empty)               | ✅ No conflict | All 3 layouts are new files                   |
| `frontend/locales/ar.json`                | ✅ No conflict | T023 merges new keys only                     |
| `frontend/locales/en.json`                | ✅ No conflict | T024 merges new keys only                     |
| `frontend/app/components/`                | ✅ No conflict | New `navigation/` subdir is additive          |
| `frontend/public/`                        | ✅ Resolved    | `logo.svg` was missing → created (see Fix F1) |

### Files Verified as Creation Targets (No Pre-existing Conflicts)

- `frontend/plugins/direction.client.ts` — plugins/ dir does not exist yet ✅
- `frontend/types/index.ts` — only `ambient.d.ts` exists, no collision ✅
- `frontend/stores/auth.ts`, `ui.ts` — only `errorStore.ts` exists ✅
- `frontend/composables/useAuth.ts`, `useNotification.ts`, `useBreadcrumb.ts`, `useDirection.ts`, `usePreferences.ts` — none exist ✅
- `frontend/app/config/navigation.ts` — `app/config/` dir does not exist ✅
- All navigation components, layouts, test files — none exist ✅

### API Delegation Check

`useNotification.ts` (T007) delegation to `useToast.ts` verified:

| `useNotification` export | Delegates To    | Exists in `useToast.ts` |
| ------------------------ | --------------- | ----------------------- |
| `notifySuccess`          | `showSuccess()` | ✅                      |
| `notifyError`            | `showError()`   | ✅                      |
| `notifyWarning`          | `showWarning()` | ✅                      |
| `notifyInfo`             | `showInfo()`    | ✅                      |
| `dismiss`                | `removeToast()` | ✅                      |

### Structural Audit Verdict: PASS

---

## Guardian Audits (5.1A)

### Security Auditor — VERDICT: PASS

| Check                               | Status | Notes                                                 |
| ----------------------------------- | ------ | ----------------------------------------------------- |
| Auth token exposure                 | PASS   | Cookie-reactive computed, no log, no localStorage JWT |
| RBAC client-side acknowledgment     | PASS   | Plan §1.1 has explicit boundary comment               |
| XSS vectors                         | PASS   | No v-html, all bindings are text interpolation        |
| Redirect vulnerabilities            | PASS   | All redirects hard-coded, no open redirect            |
| Module-level SSR state (breadcrumb) | —      | Remediated → `useState` pattern (see Fix F2)          |
| Direction persistence safety        | PASS   | Client-only plugin, stores only 'rtl'/'ltr'           |
| Navigation config exposure          | PASS   | Only frontend route paths, no API secrets             |
| Error page information leakage      | PASS   | Static title/message mapping, no stack traces         |
| Cookie flag compliance              | PASS   | Non-httpOnly accepted as architecture constraint      |

Advisory noted: `import.meta.client` guards recommended in `useDirection.ts` localStorage access.

---

### Performance Optimizer — INITIAL: BLOCKED → POST-REMEDIATION: PASS

**Blocking Finding:** `/logo.svg` missing from `frontend/public/` (affects T019, T020).

**Fix Applied (F1):** Created `frontend/public/logo.svg` — Bunyan wordmark SVG with construction mark icon, Geist font, `#171717` color.

| Check                  | Status        | Notes                                                                                             |
| ---------------------- | ------------- | ------------------------------------------------------------------------------------------------- |
| SSR hydration / CLS    | PASS          | `direction.client.ts` minimizes CLS for direction-override users                                  |
| Bundle size            | PASS          | No new heavy libraries; Iconify tree-shaken by Nuxt UI                                            |
| Render efficiency      | PASS          | AppHeader uses computed; T012 AppNavigation must use computed labels (enforced at implementation) |
| UProgress v-if         | PASS          | v-if unmounts component, no idle DOM nodes                                                        |
| Pinia store reactivity | PASS          | `isSidebarOpen` is `Ref<boolean>`, localStorage init and watch required at implementation         |
| i18n label resolution  | PASS          | Must be computed in AppNavigation (same pattern as AppHeader)                                     |
| logo.svg asset         | ✅ REMEDIATED | `frontend/public/logo.svg` created                                                                |

---

### QA Engineer — VERDICT: PASS

| Check                          | Status  | Notes                                                                                     |
| ------------------------------ | ------- | ----------------------------------------------------------------------------------------- |
| Composable test coverage       | PASS    | T025–T030 cover all 5 composables + AppNavigation                                         |
| RBAC test matrix (T030)        | PASS    | Customer ∉ admin items, Admin ∈ all 7, FieldEngineer ∈ submit_report                      |
| Cross-instance breadcrumb test | PASS    | T027 tests useState-backed cross-instance sharing                                         |
| Auth failure path (T028)       | PASS    | `logout()` clears store even on API failure — fire-and-forget                             |
| Locale key coverage            | PARTIAL | `error.*` keys not added to T023/T024 (non-blocking)                                      |
| Layout tests                   | FAIL    | No unit tests for 3 layouts — auth/default guards untested (advisory for follow-up stage) |
| E2E / Playwright               | FAIL    | No Playwright scenarios defined (advisory)                                                |

---

### Code Reviewer — INITIAL: BLOCKED → POST-REMEDIATION: PASS

**Blocking Finding:** `UDrawer` in Nuxt UI v4.6.1 uses `direction` prop (not `side`); `side="start"` is silently ignored, causing bottom-drawer rendering.

**Fix Applied (F2):** Updated T015, T008, T018, T027 in `tasks.md` and corresponding sections in `plan.md`:

| Fix                          | Applied To             | Change                                                                           |
| ---------------------------- | ---------------------- | -------------------------------------------------------------------------------- |
| F2a — UDrawer direction      | T015 + plan §3.5       | `side="start"` → `:direction="direction === 'rtl' ? 'right' : 'left'"`           |
| F2b — useBreadcrumb useState | T008, T027 + plan §2.3 | `ref()` at module level → `useState('breadcrumbs.manual', ...)`                  |
| F2c — T018 inject bridge     | T018                   | Added: `const navItems = inject<NavItem[]>('navItems', [])` + `:items` pass-down |

| Check                          | Status        | Notes                                                             |
| ------------------------------ | ------------- | ----------------------------------------------------------------- |
| Nuxt UI API correctness        | PASS          | No UHeader/UFooter/UNavigationTree/USidebar in tasks              |
| useCookie token pattern        | PASS          | `computed(() => useCookie().value)` — correct                     |
| `<script setup>` pattern       | PASS          | All tasks specify Composition API patterns                        |
| Pinia setup-function syntax    | PASS          | Both stores use `defineStore(id, () => {...})`                    |
| useNotification delegation     | PASS          | All 5 methods verified against useToast exports                   |
| AppNavigation provide() bridge | ✅ REMEDIATED | T018 now injects and passes navItems to AppSidebar + MobileDrawer |
| Breadcrumb SSR state           | ✅ REMEDIATED | `useState` applied for per-request SSR isolation                  |
| UDrawer direction prop         | ✅ REMEDIATED | direction computed from useDirection()                            |
| Tailwind logical properties    | PASS          | ps-/pe-/start-/end- used throughout                               |
| logout() error contract        | PASS          | try/catch/finally — clearAuth() guaranteed                        |

---

## 5.1B — Composite Verdict Aggregation

| Guardian              | Initial Verdict        | Final Verdict        |
| --------------------- | ---------------------- | -------------------- |
| Security Auditor      | PASS                   | ✅ PASS              |
| Performance Optimizer | BLOCKED (logo.svg)     | ✅ PASS (remediated) |
| QA Engineer           | PASS                   | ✅ PASS              |
| Code Reviewer         | BLOCKED (UDrawer side) | ✅ PASS (remediated) |

**Final Gate: ✅ APPROVED**
**Implementation: AUTHORIZED**

---

## Violations Remediated

| ID  | Severity  | Finder                           | Violation                                                                         | Fix                                                                                |
| --- | --------- | -------------------------------- | --------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| V1  | 🔴 HIGH   | Performance Optimizer            | `logo.svg` missing from `frontend/public/`                                        | Created `frontend/public/logo.svg`                                                 |
| V2  | 🔴 HIGH   | Code Reviewer                    | `UDrawer side="start"` prop doesn't exist in Nuxt UI v4.6.1                       | Changed to `:direction="direction === 'rtl' ? 'right' : 'left'"` in T015 + plan.md |
| V3  | 🟡 MEDIUM | Security Auditor + Code Reviewer | Module-level `ref()` in useBreadcrumb causes SSR state bleed                      | Changed to `useState('breadcrumbs.manual', ...)` in T008, T027 + plan.md           |
| V4  | 🟡 MEDIUM | Code Reviewer                    | T018 missing `inject('navItems')` bridge — `provide()` in T012 produces dead code | Added inject + prop pass-down to T018                                              |

## Non-Blocking Advisories (Implementation Time)

| ID  | From        | Advisory                                                                                                                      |
| --- | ----------- | ----------------------------------------------------------------------------------------------------------------------------- |
| A1  | Security    | Add `import.meta.client` guards to `useDirection.ts` localStorage access                                                      |
| A2  | Performance | AppNavigation `resolvedItems` must be `computed()` (same as AppHeader)                                                        |
| A3  | Performance | `ui.ts` toggleSidebar must write to localStorage: `localStorage.setItem('bunyan_sidebar_open', String(!isSidebarOpen.value))` |
| A4  | QA          | Add layout guard tests (T031/T032) in a follow-up stage or test file                                                          |
| A5  | QA          | Add Playwright smoke scenarios (role-filtered nav, RTL toggle, loading indicator)                                             |
