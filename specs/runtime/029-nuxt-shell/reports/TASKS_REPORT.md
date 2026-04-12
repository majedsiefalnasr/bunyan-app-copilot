# Tasks Report — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12T00:25:00Z
> **Total Tasks:** 30
> **Tasks File:** `specs/runtime/029-nuxt-shell/tasks.md`

---

## Task Summary by Phase

| Phase   | Description                        | Tasks           | Parallelism                    |
| ------- | ---------------------------------- | --------------- | ------------------------------ |
| Phase 0 | Foundation (config, plugin, types) | T001–T003       | Sequential                     |
| Phase 1 | Pinia Stores                       | T004–T005       | Sequential                     |
| Phase 2 | Core Composables                   | T006–T010       | All [P]                        |
| Phase 3 | Navigation Components + Config     | T011, T012–T017 | T011 sequential, T012–T017 [P] |
| Phase 4 | Layouts                            | T018, T019–T020 | T018 sequential, T019–T020 [P] |
| Phase 5 | Core File Updates                  | T021, T022–T024 | T021 sequential, T022–T024 [P] |
| Phase 6 | Tests                              | T025–T030       | All [P]                        |

---

## Risk-Ranked Task View

| Task       | Risk      | Reason                                                                                             |
| ---------- | --------- | -------------------------------------------------------------------------------------------------- |
| T004       | 🔴 HIGH   | Auth store — cookie-reactive token pattern; any error here causes redirect loops or auth blindness |
| T018       | 🔴 HIGH   | Default layout — all shell pieces assemble here; NuxtLayout composition, page loading hook         |
| T003       | 🟡 MEDIUM | Types/index.ts — PageMeta augmentation; incorrect types cascade to all downstream files            |
| T006       | 🟡 MEDIUM | useAuth.ts — logout() must clear state on API failure; fetchCurrentUser bootstrap                  |
| T008       | 🟡 MEDIUM | useBreadcrumb.ts — module-level state pattern; incorrect scope breaks cross-component reactivity   |
| T009       | 🟡 MEDIUM | useDirection.ts — SSR/client boundary; hasManualOverride must survive page reload                  |
| T011       | 🟡 MEDIUM | navigation.ts — labelKey i18n keys must exactly match locales/ar.json and en.json keys             |
| T021       | 🟡 MEDIUM | app.vue NuxtLayout fix — silently breaks all layouts if wrapper is wrong or omitted                |
| T023       | 🟡 MEDIUM | locales/ar.json — missing translation keys cause silent render failures in RTL UI                  |
| T002       | 🟡 MEDIUM | direction.client.ts — client plugin must not run on SSR; wrong guard causes hydration mismatch     |
| All others | 🟢 LOW    | Components, layouts, tests with clear specs and no security/auth concerns                          |

---

## External Dependency Tasks

| Task      | Package                                     | Version   | Usage                                                |
| --------- | ------------------------------------------- | --------- | ---------------------------------------------------- |
| T006      | `frontend/composables/useApi.ts` (existing) | —         | `apiFetch` for API calls in useAuth                  |
| T009      | `@vueuse/core` `useLocalStorage`            | installed | Direction persistence                                |
| T010      | `@nuxtjs/color-mode` (via @nuxt/ui)         | installed | `useColorMode()`                                     |
| T012–T017 | `@nuxt/ui` v4.6.1                           | installed | UNavigationMenu, UDrawer, UBreadcrumb, UDropdownMenu |
| T018–T020 | `@nuxt/ui` v4.6.1                           | installed | UProgress, UCard, UButton                            |

---

## User Story Coverage

| User Story                 | Tasks                                    | Status  |
| -------------------------- | ---------------------------------------- | ------- |
| US1 — Default Layout       | T006, T008, T013, T014, T016, T017, T018 | 7 tasks |
| US2 — Auth Layout          | T019                                     | 1 task  |
| US3 — Public Layout        | T017, T020                               | 2 tasks |
| US4 — Customer Nav         | T011, T012, T014                         | 3 tasks |
| US5 — Contractor Nav       | T011, T012, T014                         | 3 tasks |
| US6 — Architect Nav        | T011, T012, T014                         | 3 tasks |
| US7 — Field Engineer Nav   | T011, T012, T014                         | 3 tasks |
| US8 — Admin Nav            | T011, T012, T014                         | 3 tasks |
| US9 — RTL/LTR Toggle       | T009, T010, T013                         | 3 tasks |
| US10 — Dark Mode           | T010, T013                               | 2 tasks |
| US11 — Language Switcher   | T010, T013, T023, T024                   | 4 tasks |
| US12 — Toast Notifications | T007                                     | 1 task  |
| US13 — Mobile Navigation   | T015, T018                               | 2 tasks |
| US14 — Global Loading      | T018                                     | 1 task  |
| US15 — Global Error Page   | T022                                     | 1 task  |

---

## Parallel Execution Groups

```
Phase 2 (5-way parallel):  T006 | T007 | T008 | T009 | T010
Phase 3 (6-way parallel):  T012 | T013 | T014 | T015 | T016 | T017  (after T011)
Phase 4 (2-way parallel):  T019 | T020  (after T018)
Phase 5 (3-way parallel):  T022 | T023 | T024  (after T021)
Phase 6 (6-way parallel):  T025 | T026 | T027 | T028 | T029 | T030
```

---

## Critical Implementation Notes

1. **T001 before T002 before T003**: Foundation must be strictly sequential — no parallel execution in Phase 0.
2. **T004 cookie pattern**: `token = computed(() => useCookie('auth_token').value)`. Do NOT use `ref<string>`. This is the core auth-loop prevention.
3. **T008 module scope**: `const _manualBreadcrumbs = ref(null)` must be at module level, outside `useBreadcrumb()`. Failure breaks breadcrumb in all pages.
4. **T021 is blocking**: `<NuxtLayout>` must be added to `app.vue` before any layout testing. Layouts are silently ignored without it.
5. **T023/T024 key alignment**: `layout.footer_copyright` key must exist in BOTH locale files before `AppFooter.vue` renders.
