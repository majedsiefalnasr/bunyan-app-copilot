# Specify Report — NUXT_SHELL

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12T00:00:00Z

## Specification Summary

| Metric                 | Value                                                                        |
| ---------------------- | ---------------------------------------------------------------------------- |
| User Stories           | 15 (US1–US15)                                                                |
| Acceptance Criteria    | ~80 items across all stories                                                 |
| Technical Requirements | 22 files in delivery map                                                     |
| Dependencies           | STAGE_01_PROJECT_INITIALIZATION (upstream), all frontend stages (downstream) |
| Open Questions         | 0 — spec is complete                                                         |

## Scope Defined

- Default layout: `UHeader` + `UNavigationTree` sidebar + `UFooter` + `<NuxtPage />`
- Auth layout: minimal centered `UCard`, zero chrome
- Public layout: landing-page wrapper with minimal navigation
- Role-based navigation for all 5 roles (Customer, Contractor, Supervising Architect, Field Engineer, Admin)
- Mobile-responsive navigation via `UDrawer` (hamburger at < 768px)
- Breadcrumb bar using `UBreadcrumb` beneath the `UHeader`
- RTL/LTR direction toggle — persisted to `localStorage` via `useDirection`
- Dark mode toggle via `useColorMode()` + Nuxt UI `AppConfig`
- Language switcher (AR / EN) via `@nuxtjs/i18n`
- Toast notification system via `useToast()` wrapped in `useNotification`
- Global page-level loading indicator via `UProgress`
- Skeleton loading states via `USkeleton`
- Global error boundary page (`error.vue`) using `UAlert` with `color="error"`
- User avatar + dropdown via `UAvatar` + `UDropdownMenu`
- Core composables: `useAuth`, `useApi`, `useNotification`, `useBreadcrumb`, `useDirection`

## Deferred Scope

- Business-domain pages (projects, tasks, reports, products, orders)
- Authentication login/register pages (STAGE_30_AUTH_PAGES)
- Pinia store implementations beyond auth state bootstrapping
- Backend API endpoints (all backend stages are upstream)
- Payment or e-commerce UI
- File upload components
- Admin configuration panels

## Risk Assessment

| Risk                                 | Severity | Mitigation                                                     |
| ------------------------------------ | -------- | -------------------------------------------------------------- |
| RTL conflicts with Nuxt UI defaults  | Medium   | Use Tailwind logical properties; test with Arabic locale       |
| Role-nav state leakage between users | Medium   | useAuth composable clears state on logout                      |
| Dark mode + RTL combination lag      | Low      | Use CSS variables; no JS-heavy toggle                          |
| i18n hydration mismatch (SSR/CSR)    | Low      | Use `@nuxtjs/i18n` with `detectBrowserLanguage: false` default |

## Checklist Status

- Requirements checklist: Created at `checklists/requirements.md` (109 items across 13 sections)
