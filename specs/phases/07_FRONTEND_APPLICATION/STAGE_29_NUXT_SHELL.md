# STAGE_29 — Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Nuxt.js app shell, layout system, navigation, RTL
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: plan
Risk Level: MEDIUM
Last Updated: 2026-04-12T00:20:00Z

Scope Planned:

- 30 files across 6 sequential phases
- 3 layouts (default, auth, public)
- 2 Pinia stores (auth with cookie-derived token, ui)
- 5 core composables (useAuth, useNotification, useBreadcrumb, useDirection, usePreferences)
- 7 navigation components + navigation config
- plugins/direction.client.ts for hydration CLS prevention
- Critical fix: app.vue NuxtLayout wrapper (layouts silently broken without it)
- Token-from-cookie pattern (auth state consistency, no redirect loops)
- Module-level breadcrumb state (cross-component reactivity)

Deferred Scope:

- Business-domain pages
- Auth login/register pages (STAGE_30)
- Token refresh endpoint (not available)

Architecture Governance Compliance:

- Technical plan compliant — task generation authorized
- Architecture Guardian: PASS (5 violations found and remediated)
- API Designer: PASS (1 violation found and remediated)

Notes:
Technical plan complete. Task breakdown in progress.

## Objective

Implement the Nuxt.js application shell with layout system, role-based navigation, RTL support, and core composables using **Nuxt UI** (`@nuxt/ui`) component library.

## Scope

### Frontend

- Default layout with `UHeader`, sidebar using `UNavigationTree`, `UFooter`
- Auth layout (minimal — `UCard` centered, zero chrome)
- Public layout (landing pages)
- Main navigation using `UNavigationMenu` with role-based menu items
- Sidebar drawer using `USlideover` / persistent sidebar with `UNavigationTree`
- Breadcrumb using `UBreadcrumb`
- RTL/LTR toggle — Nuxt UI `dir` prop (`rtl`/`ltr`), persisted in `useColorMode`-style store
- Dark mode toggle using `useColorMode()` from `@vueuse/core` + Nuxt UI `AppConfig`
- Language switcher (Arabic/English) via `@nuxtjs/i18n`
- Base composables: `useAuth`, `useApi`, `useNotification`, `useBreadcrumb`, `useDirection`
- Global loading with `UProgress`
- Skeleton states using `USkeleton`
- Global error page with `UAlert`
- Mobile responsive navigation using `UDrawer`
- Toast notifications via `useToast()` from Nuxt UI

### Nuxt UI Component Map

| Shell Element   | Nuxt UI Component                |
| --------------- | -------------------------------- |
| Navigation bar  | `UHeader` + `UNavigationMenu`    |
| Sidebar         | `UNavigationTree` + `USlideover` |
| Breadcrumb      | `UBreadcrumb`                    |
| Footer          | `UFooter`                        |
| Mobile menu     | `UDrawer`                        |
| Notifications   | `UNotification` / `useToast()`   |
| Loading         | `UProgress` / `USkeleton`        |
| Error boundary  | `UAlert` (color="error")         |
| Avatar          | `UAvatar`                        |
| Dropdown (user) | `UDropdownMenu`                  |

### Key Composables

| Composable        | Purpose                                      |
| ----------------- | -------------------------------------------- |
| `useAuth`         | Auth state, user, permissions, logout        |
| `useApi`          | `$fetch`-based API client with Sanctum token |
| `useNotification` | `useToast()` wrapper for app-wide toasts     |
| `useBreadcrumb`   | Dynamic breadcrumb management                |
| `useDirection`    | RTL/LTR toggle with `document.dir`           |

### Layout Structure

```
┌──────────────────────────────────────┐
│         UHeader (UNavigationMenu)    │
├──────────┬───────────────────────────┤
│          │                           │
│ UNavigationTree │   Main Content     │
│ (RTL-aware)     │   <NuxtPage />     │
│          │                           │
├──────────┴───────────────────────────┤
│              UFooter                 │
└──────────────────────────────────────┘
```

### RTL Implementation

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  ui: {
    theme: { colors: ['primary', 'secondary', 'success', 'warning', 'error', 'info'] },
  },
  app: {
    head: { htmlAttrs: { dir: 'rtl', lang: 'ar' } },
  },
});
```

## Testing

### Unit Tests (Vitest)

- `useDirection` — toggles `document.dir`, persists to localStorage
- `useBreadcrumb` — generates breadcrumb from route
- `useAuth` — state transitions on login/logout

### E2E Tests (Playwright)

| Test Case                          | Scenario                                        |
| ---------------------------------- | ----------------------------------------------- |
| Shell renders for each role        | Login as Customer/Contractor/Admin, check nav   |
| RTL direction toggle               | Click RTL toggle → body `dir="rtl"` verified    |
| Dark mode toggle                   | Click toggle → `.dark` class applied            |
| Language switch AR/EN              | Switch language → page text updates             |
| Mobile drawer opens/closes         | Viewport 375px → hamburger → drawer visible     |
| Navigation highlights active route | Navigate to `/projects` → nav item active state |

```typescript
// tests/e2e/shell.spec.ts
import { test, expect } from '@playwright/test';

test('RTL direction persists across navigation', async ({ page }) => {
  await page.goto('/');
  await page.click('[data-testid="rtl-toggle"]');
  await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
  await page.goto('/projects');
  await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
});
```

## Dependencies

- **Upstream:** STAGE_01_PROJECT_INITIALIZATION
- **Downstream:** All frontend pages
