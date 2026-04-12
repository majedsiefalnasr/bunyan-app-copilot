# Research: STAGE_29 — Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12
> **Status:** Planning

---

## 1. Installed vs Specified Package Versions

| Package        | Specified in Task | Actually Installed | Impact                                                                                      |
| -------------- | ----------------- | ------------------ | ------------------------------------------------------------------------------------------- |
| `@nuxt/ui`     | v3.x              | `^4.6.1`           | v4 is a continuation of the v3 rewrite; component names and APIs are forward-compatible     |
| `@nuxtjs/i18n` | v9.x              | `^10.2.4`          | No `useLocale()` — use `useI18n()` destructured; direction driven by `locales[].dir` config |
| `nuxt`         | —                 | `^4.4.2`           | Nuxt 4; uses `app/` directory for `app.vue` and `error.vue`                                 |
| `pinia`        | —                 | `^3.0.4`           | `defineStore` composition API pattern                                                       |
| `tailwindcss`  | —                 | `^4.2.2`           | Tailwind CSS v4 — logical properties fully supported                                        |

**Action Required**: All implementation references must use the actual installed versions above, not the versions specified in the task prompt.

---

## 2. Nuxt UI v4 Component Inventory

### 2.1 Components That EXIST — Use Directly

| Component         | Correct v4 API                                          | Used In                              |
| ----------------- | ------------------------------------------------------- | ------------------------------------ |
| `UNavigationMenu` | `orientation="vertical"` for sidebar; `items` prop      | `AppSidebar.vue`, `MobileDrawer.vue` |
| `UDrawer`         | `v-model:open`, `side="start"`                          | `MobileDrawer.vue`                   |
| `UBreadcrumb`     | `:items="breadcrumbs"` — array of `{label, to?, icon?}` | `AppBreadcrumb.vue`                  |
| `USlideover`      | `v-model:open`, `side="start"`                          | Optional mobile alternative          |
| `UDropdownMenu`   | `:items="menuItems"` — grouped sections array           | User dropdown in `AppHeader.vue`     |
| `UAvatar`         | `:src`, `:alt`, `size`                                  | User avatar in header                |
| `USkeleton`       | `class`, `height`, `width`                              | Page-level skeleton loading          |
| `UProgress`       | `v-model`, `animation="carousel"`                       | Global page loading bar              |
| `UAlert`          | `color`, `variant`, `icon`, `title`, `description`      | `error.vue`, error banners           |
| `UCard`           | `:ui="{root: '...shadow...'}"`                          | Auth layout card                     |
| `UButton`         | `icon`, `color`, `variant`, `size`                      | All CTAs, toggle buttons             |
| `UBadge`          | `color`, `variant`, `size`                              | Nav item notification counts         |
| `UIcon`           | `name="i-heroicons-*"`                                  | Icons throughout                     |
| `UContainer`      | `class`                                                 | Content width constraint             |
| `UDivider`        | —                                                       | Sidebar section separators           |
| `UTooltip`        | `text`                                                  | Collapsed sidebar icon tooltips      |

### 2.2 Components That DO NOT EXIST — Must Custom-Build

| Missing Component | Replacement Strategy                                   | Notes                               |
| ----------------- | ------------------------------------------------------ | ----------------------------------- |
| `UHeader`         | Custom `<header>` element with Tailwind CSS            | Shadow-as-border per DESIGN.md      |
| `UFooter`         | Custom `<footer>` element with Tailwind CSS            | Minimal — copyright + links         |
| `UNavigationTree` | `UNavigationMenu` with `orientation="vertical"`        | Name changed in v3 rewrite          |
| `USidebar`        | Custom `<aside>` wrapper with `UNavigationMenu` inside | No built-in sidebar shell component |

### 2.3 Toast System — Important Decision

The project already has a **custom toast system** (`composables/useToast.ts` + `stores/errorStore.ts`). This system drives `<ErrorToast />` in `app.vue`.

- Nuxt UI v4 provides its own `useToast()` from `#imports` — but this would conflict with the existing `composables/useToast.ts` export name
- **Decision**: Retain the existing custom toast system. The `useNotification` composable will delegate to the existing `useToast()`. Do NOT replace with Nuxt UI `useToast()`
- `<UNotifications />` is NOT added — `<ErrorToast />` already handles this role

---

## 3. @nuxtjs/i18n v10 Direction Handling

- Direction is declared per-locale in `nuxt.config.ts` → `locales[].dir` — **already configured** (`ar: 'rtl'`, `en: 'ltr'`)
- `@nuxtjs/i18n` v10 does NOT auto-apply `dir` to `<html>` — must be done manually
- Current `app.vue` already handles `dir` reactively via `useI18n().locale` → computed `htmlDir` → `useHead({ htmlAttrs: { dir: htmlDir } })`
- `useLocaleRoute.ts` composable already exists for i18n-prefixed navigation
- Composable API: `const { locale, setLocale, locales } = useI18n()` — no `useLocale()` function in v10
- Strategy: prefix mode (`strategy: 'prefix'`) is already set; all routes have `/ar/...` or `/en/...` prefix

---

## 4. `useColorMode()` — Dark Mode

- Source: `@nuxtjs/color-mode` bundled inside `@nuxt/ui` v4 — auto-registered
- Usage: `const colorMode = useColorMode()` → `colorMode.preference` = `'dark' | 'light' | 'system'`
- Storage: persisted to `localStorage` under key `nuxt-color-mode` automatically
- CSS class: `dark` added to `<html>` — Tailwind `dark:` variants work automatically
- Config needed in `nuxt.config.ts`: `colorMode: { classSuffix: '' }` (so class is `dark` not `dark-mode`)
- Nuxt UI v4 includes dark mode out of the box when `@nuxt/ui` module is active

---

## 5. RTL/LTR Strategy — Tailwind Logical Properties

### Required Property Mapping

| Traditional (DO NOT USE)     | Logical (USE THIS)           |
| ---------------------------- | ---------------------------- |
| `pl-`, `pr-`                 | `ps-`, `pe-`                 |
| `ml-`, `mr-`                 | `ms-`, `me-`                 |
| `border-l`, `border-r`       | `border-s`, `border-e`       |
| `rounded-l-*`, `rounded-r-*` | `rounded-s-*`, `rounded-e-*` |
| `left-`, `right-`            | `start-`, `end-`             |
| `text-left`, `text-right`    | `text-start`, `text-end`     |
| `float-left`, `float-right`  | `float-start`, `float-end`   |

### Direction Toggle Implementation

- `useDirection` composable stores preference in `localStorage` under key `bunyan_direction`
- `plugins/direction.client.ts` runs before Vue hydration — reads `localStorage` and sets `document.documentElement.dir`
- Manual direction toggle (user-initiated) is **independent** from locale — a user may want Arabic locale with LTR (less common) or English locale with RTL
- Default direction: `'rtl'` (Arabic-first platform)

---

## 6. Nuxt 4 Layout System — Critical Fix Required

### Current State (Broken for Layouts)

```vue
<!-- Current app/app.vue -->
<template>
  <NuxtRouteAnnouncer />
  <GlobalErrorBoundary>
    <NuxtPage />
    <!-- ← NO <NuxtLayout> wrapper -->
  </GlobalErrorBoundary>
  <ErrorToast />
</template>
```

### Required State (Phase 5 Update)

```vue
<template>
  <NuxtRouteAnnouncer />
  <GlobalErrorBoundary>
    <NuxtLayout>
      <!-- ← Add <NuxtLayout> wrapper -->
      <NuxtPage />
    </NuxtLayout>
  </GlobalErrorBoundary>
  <ErrorToast />
</template>
```

**Without `<NuxtLayout>`, all `definePageMeta({ layout: '...' })` calls are silently ignored.** This is a blocking fix for Phase 5.

---

## 7. Cookie-Based Sanctum Auth Pattern

- Token stored in `auth_token` cookie (client-accessible, not `httpOnly`)
- Pattern already established in `composables/useApi.ts`: `useCookie('auth_token').value`
- `useAuthStore` will sync between cookie and reactive store state on mount
- Logout flow: `DELETE /api/v1/auth/logout` → clear `auth_token` cookie → clear `useAuthStore` state → `navigateTo('/ar/auth/login')`
- **Security Note**: The cookie-based approach is already in the codebase. The `auth_token` cookie must be `Secure` + `SameSite=Lax` in production.

---

## 8. UNavigationMenu Item Shape (v4)

```typescript
interface NavigationMenuItem {
  label: string;
  icon?: string; // Iconify string: 'i-heroicons-home'
  to?: string; // Route path
  badge?: string | number;
  children?: NavigationMenuItem[];
  // No built-in `roles` field — filtering is done in composable before passing to component
}
```

The `roles` field is part of the internal `NavItem` interface (in `types/index.ts`). Before passing items to `UNavigationMenu`, composable filters out items the current user's role cannot see.

---

## 9. UBreadcrumb Item Shape (v4)

```typescript
interface BreadcrumbItem {
  label: string;
  to?: string;
  icon?: string;
}
```

Auto-generation strategy: page defines `definePageMeta({ breadcrumb: [...] })` in route meta, and `useBreadcrumb` composable reads it reactively.

---

## 10. Existing Components Survey

| File                             | Status                                        | Action                            |
| -------------------------------- | --------------------------------------------- | --------------------------------- |
| `app/app.vue`                    | Exists, needs layout fix                      | Update in Phase 5                 |
| `app/error.vue`                  | Exists, uses custom logic                     | Update in Phase 5 to use `UAlert` |
| `app/components/AppCounter.vue`  | Placeholder                                   | No action                         |
| `app/components/BaseAlert.vue`   | Placeholder (CSS border — violates DESIGN.md) | No action — not used in shell     |
| `app/components/errors/`         | Exists                                        | Review in Phase 5                 |
| `composables/useApi.ts`          | Production-ready                              | No changes in this stage          |
| `composables/useErrorHandler.ts` | Production-ready                              | No changes in this stage          |
| `composables/useToast.ts`        | Production-ready                              | Wrap in `useNotification`         |
| `composables/useLocaleRoute.ts`  | Production-ready                              | Use in nav components             |
| `stores/errorStore.ts`           | Production-ready                              | No changes in this stage          |
| `layouts/`                       | **Empty**                                     | All layouts created in Phase 4    |
| `types/ambient.d.ts`             | Exists                                        | Add `index.ts` alongside it       |

---

## 11. Icon System

Nuxt UI uses **Iconify** with HeroIcons set by default:

- Pattern: `i-heroicons-{name}` (e.g., `i-heroicons-home`, `i-heroicons-chart-bar`)
- Outline: `i-heroicons-home` | Solid: `i-heroicons-home-solid`
- Other sets supported with `@iconify-json/*` packages
- For Arabic-aware icons (compass, mosque), consider HeroIcons alternatives or custom SVGs

---

## 12. DESIGN.md Compliance Checklist

| Rule                                        | Implementation                                                                |
| ------------------------------------------- | ----------------------------------------------------------------------------- |
| Geist fonts                                 | Add to `assets/css/main.css` via `@import` or CSS `font-face`                 |
| Shadow-as-border                            | Use `shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]` — no `border` on cards        |
| Primary text `#171717`                      | Use `text-[#171717]` or configure Tailwind `neutral` scale                    |
| Heading weight 600, letter-spacing negative | `font-semibold tracking-tight` or `tracking-[-0.96px]`                        |
| Button font 14px weight 500                 | `text-sm font-medium`                                                         |
| Border-radius scale                         | Cards: `rounded-lg` (8px), buttons: `rounded-md` (6px), pills: `rounded-full` |
| Achromatic palette                          | `ui.colors.primary = 'neutral'` in `app.config.ts`                            |
