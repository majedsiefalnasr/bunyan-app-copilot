# Implementation Plan: STAGE_29 — Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12
> **Depends On:** `data-model.md`, `research.md`

---

## Execution Order

```
Phase 0 → Foundation (nuxt.config.ts, plugin, types)
Phase 1 → Pinia Stores (auth, ui)
Phase 2 → Core Composables (useAuth, useNotification, useBreadcrumb, useDirection, usePreferences)
Phase 3 → Navigation Components + Config
Phase 4 → Layouts (default, auth, public)
Phase 5 → Update Core Files (app.vue, error.vue, locales)
Phase 6 → Tests
```

Each phase MUST be completed before the next begins (sequential dependency chain).

---

## Phase 0 — Foundation

### 0.1 Update `frontend/nuxt.config.ts`

**File:** `frontend/nuxt.config.ts`
**Action:** Modify existing file

**Changes:**

- Add `runtimeConfig.public.apiBaseUrl` (already used by `useApi.ts` but not declared in config)
- Add `colorMode: { classSuffix: '' }` so class applied to `<html>` is `dark` (not `dark-mode`)
- Add `dir: 'rtl'` to `app.head.htmlAttrs` as default (overridden reactively by `app.vue`)
- Keep all existing i18n config unchanged

**Result:**

```typescript
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  devtools: { enabled: true },
  compatibilityDate: '2024-04-03',
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000',
    },
  },
  app: {
    head: {
      htmlAttrs: { lang: 'ar', dir: 'rtl' },
    },
  },
  colorMode: {
    classSuffix: '',
  },
  i18n: {
    defaultLocale: 'ar',
    strategy: 'prefix',
    langDir: 'locales',
    locales: [
      { code: 'ar', language: 'ar-SA', dir: 'rtl', file: 'ar.json' },
      { code: 'en', language: 'en-US', dir: 'ltr', file: 'en.json' },
    ],
  },
});
```

**Dependencies:** None

---

### 0.2 Create `frontend/plugins/direction.client.ts`

**File:** `frontend/plugins/direction.client.ts`
**Action:** Create new file

**Purpose:** Apply stored direction from `localStorage` before Vue hydrates — prevents CLS (Content Layout Shift) flash when direction differs from HTML default.

**Key Implementation Decisions:**

- Must be `*.client.ts` — runs only in browser, not during SSR
- Read `localStorage.getItem('bunyan_direction')` synchronously
- Falls back to the locale-derived direction from `<html dir>` attribute
- Sets `document.documentElement.dir` directly

```typescript
export default defineNuxtPlugin(() => {
  const stored = localStorage.getItem('bunyan_direction') as 'rtl' | 'ltr' | null;
  if (stored === 'rtl' || stored === 'ltr') {
    document.documentElement.dir = stored;
  }
});
```

**Dependencies:** None

---

### 0.3 Create `frontend/types/index.ts`

**File:** `frontend/types/index.ts`
**Action:** Create new file (alongside existing `types/ambient.d.ts`)

**Contents:** All types from `data-model.md`:

- `UserRole` enum
- `UserRoleType` union
- `NavItem` interface
- `BreadcrumbItem` interface
- `AuthUser` interface
- `Direction` type
- `Locale` type
- `UiPreferences` interface
- `NavItemsByRole` type
- `DropdownMenuItem` + `DropdownMenuGroup`
- `RouteMetaBreadcrumb` + `PageMeta` module augmentation

**Dependencies:** None

---

## Phase 1 — Pinia Stores

### 1.1 Create `frontend/stores/auth.ts`

**File:** `frontend/stores/auth.ts`
**Action:** Create new file

**Purpose:** Global auth state — user identity, token, role. Drives RBAC in middleware and nav filtering.

**State:**

- `user: AuthUser | null` — reactive ref
- `token: ComputedRef<string | null>` — **derived from `useCookie('auth_token')`** (NOT a separate ref). This ensures that when `useApi.ts` clears the cookie on a 401, `isAuthenticated` automatically becomes `false` without requiring an explicit `clearAuth()` call from the 401 handler. Avoids redirect loops.
- `isAuthenticated: ComputedRef<boolean>` — derived from `user !== null && token.value !== null`
- `role: ComputedRef<UserRoleType | null>` — derived from `user.role`

**Actions:**

- `setUser(user: AuthUser): void` — sets `user` ref only; cookie is written separately by `useAuth` after the login API call resolves
- `clearAuth(): void` — nulls `user` ref + explicitly clears cookie (belt-and-suspenders), does NOT navigate
- `initFromCookie(): void` — reads `auth_token` cookie; if present, triggers `GET /api/v1/auth/me` (called from app.vue `onMounted` or plugin)
- `hasRole(roles: UserRoleType | UserRoleType[]): boolean` — utility for RBAC checks

**Cookie Sync Pattern (Option A — reactive token):**

```typescript
// token is DERIVED from the cookie — not a separate ref
const authCookie = useCookie<string | null>('auth_token', {
  maxAge: 60 * 60 * 24 * 7,
});
const token = computed(() => authCookie.value ?? null);

// isAuthenticated automatically reacts when useApi.ts sets authCookie.value = null
const isAuthenticated = computed(() => user.value !== null && token.value !== null);

// clearAuth — nulls user, clears cookie
function clearAuth() {
  user.value = null;
  authCookie.value = null;
}
```

**Key Decision (Auth State Consistency):** `token` is a `computed` over `useCookie('auth_token')`, NOT an independent `ref`. This means:

- `useApi.ts` clears the cookie on 401 → `token` becomes null → `isAuthenticated` becomes false
- No coordination required between `useApi.ts` and the auth store
- No redirect loops possible
- `initFromCookie()` is called once from the `auth` Nuxt plugin. The store itself does NOT call the API — it delegates to `useAuth` composable to keep stores free of HTTP concerns.

**RBAC Acknowledgment (Security Boundary):** The `hasRole()` utility and navigation filtering in `AppNavigation.vue` are **presentation-layer guards only** — they filter what is rendered, not what is accessible. All RBAC enforcement for data access is performed server-side by Laravel middleware and Policies. Client-side role checks MUST NOT be treated as a security boundary.

**Dependencies:** `frontend/types/index.ts` (AuthUser, UserRoleType)

---

### 1.2 Create `frontend/stores/ui.ts`

**File:** `frontend/stores/ui.ts`
**Action:** Create new file

**Purpose:** UI state for layout interactions — sidebar open/close, mobile drawer state.

**State:**

- `isSidebarOpen: Ref<boolean>` — desktop sidebar expanded/collapsed state (default: `true`)
- `isDrawerOpen: Ref<boolean>` — mobile drawer open state (default: `false`)
- `isPageLoading: Ref<boolean>` — global page-level loading indicator

**Actions:**

- `toggleSidebar(): void`
- `openDrawer(): void`
- `closeDrawer(): void`
- `toggleDrawer(): void`
- `setPageLoading(value: boolean): void`

**Key Decision:** `isSidebarOpen` persisted to `localStorage` under key `bunyan_sidebar_open` so user preference survives page refreshes.

**Dependencies:** None

---

## Phase 2 — Core Composables

### 2.1 Create `frontend/composables/useAuth.ts`

**File:** `frontend/composables/useAuth.ts`
**Action:** Create new file

**Purpose:** Wraps `useAuthStore()` and provides auth actions with API integration. The **single entry point** for auth in components and middleware.

**Exports:**

- `user: ComputedRef<AuthUser | null>` — from store
- `isAuthenticated: ComputedRef<boolean>` — from store
- `role: ComputedRef<UserRoleType | null>` — from store
- `hasRole(roles): boolean` — delegates to store
- `logout(): Promise<void>` — calls `DELETE /api/v1/auth/logout`, then `clearAuth()`, then `navigateTo` to locale-prefixed login
- `fetchCurrentUser(): Promise<void>` — calls `GET /api/v1/auth/me`, on success calls `store.setUser()`

**Logout Flow:**

```typescript
async function logout() {
  const { apiFetch } = useApi();
  try {
    await apiFetch('/api/v1/auth/logout', { method: 'DELETE' });
  } catch {
    // Ignore logout API failure — clear local state regardless
  } finally {
    store.clearAuth();
    await navigateTo(`/${locale.value}/auth/login`);
  }
}
```

**Key Decision:** Logout always clears local state regardless of API response — prevents users being stuck in authenticated state on API failure.

**Dependencies:** `stores/auth.ts`, `composables/useApi.ts`, `types/index.ts`

---

### 2.2 Create `frontend/composables/useNotification.ts`

**File:** `frontend/composables/useNotification.ts`
**Action:** Create new file

**Purpose:** Semantic wrapper over existing `useToast()` composable. Provides domain-level notification methods.

**Exports:**

- `notifySuccess(message: string, duration?: number): string`
- `notifyError(message: string, duration?: number): string`
- `notifyWarning(message: string, duration?: number): string`
- `notifyInfo(message: string, duration?: number): string`
- `dismiss(id: string): void`

**Key Decision:** Delegates ENTIRELY to existing `useToast()` — does NOT replace or duplicate the custom toast system. This provides a semantic API for business-level notifications separate from the error system.

```typescript
export function useNotification() {
  const toast = useToast();
  return {
    notifySuccess: (msg: string, duration = 3000) => toast.showSuccess(msg, duration),
    notifyError: (msg: string, duration = 5000) => toast.showError(msg, duration),
    notifyWarning: (msg: string, duration = 5000) => toast.showWarning(msg, duration),
    notifyInfo: (msg: string, duration = 5000) => toast.showInfo(msg, duration),
    dismiss: (id: string) => toast.removeToast(id),
  };
}
```

**Dependencies:** `composables/useToast.ts`

---

### 2.3 Create `frontend/composables/useBreadcrumb.ts`

**File:** `frontend/composables/useBreadcrumb.ts`
**Action:** Create new file

**Purpose:** Reactive breadcrumb array that auto-generates from `route.meta.breadcrumb`. Components use this instead of hard-coding breadcrumbs.

**Exports:**

- `breadcrumbs: ComputedRef<BreadcrumbItem[]>` — reactive, updates on navigation
- `setBreadcrumbs(items: BreadcrumbItem[]): void` — manual override for dynamic routes

**Auto-Generation Strategy:**

- `const route = useRoute()` → reads `route.meta.breadcrumb` (typed via `PageMeta` augmentation)
- If `route.meta.breadcrumb` is defined, use it directly
- If not, return the manual override (if set) or empty array

**Shared State Pattern (Nuxt `useState` for SSR safety):**

```typescript
// Declared at MODULE LEVEL using Nuxt's useState — provides per-request isolation
// on SSR (no state bleed across concurrent requests) while maintaining shared
// cross-instance reactivity on the client (same reactive ref for all call sites).
const _manualBreadcrumbs = useState<BreadcrumbItem[] | null>('breadcrumbs.manual', () => null);

export function useBreadcrumb() {
  const route = useRoute();

  const breadcrumbs = computed<BreadcrumbItem[]>(() => {
    if (_manualBreadcrumbs.value !== null) return _manualBreadcrumbs.value;
    if (Array.isArray(route.meta.breadcrumb)) return route.meta.breadcrumb as BreadcrumbItem[];
    return [];
  });

  function setBreadcrumbs(items: BreadcrumbItem[]) {
    _manualBreadcrumbs.value = items;
  }

  function clearBreadcrumbs() {
    _manualBreadcrumbs.value = null;
  }

  return { breadcrumbs, setBreadcrumbs, clearBreadcrumbs };
}
```

**Key Decision:** `useState('breadcrumbs.manual', ...)` is used instead of a plain `ref()` at module level. This gives per-request state on the server (preventing breadcrumb data leaking between concurrent SSR requests) while still sharing the same reactive ref across all client-side call sites. `setBreadcrumbs()` called from any page is visible to `AppBreadcrumb.vue` reading from the same `useState` key.

**Dependencies:** `types/index.ts` (BreadcrumbItem)

---

### 2.4 Create `frontend/composables/useDirection.ts`

**File:** `frontend/composables/useDirection.ts`
**Action:** Create new file

**Purpose:** RTL/LTR direction management with `localStorage` persistence. Provides reactive `dir` ref and manual toggle independent of locale.

**Storage Key:** `bunyan_direction`

**Exports:**

- `direction: Ref<Direction>` — current direction (`'rtl' | 'ltr'`)
- `toggle(): void` — flip direction, persist, apply to DOM
- `setDirection(dir: Direction): void` — explicit set

**Initialization:**

1. On first call, read `localStorage.getItem('bunyan_direction')`
2. If valid value exists, use it
3. If not, derive from current `useI18n().locale.value` (`ar` → `rtl`, `en` → `ltr`)

**DOM application:**

```typescript
function applyDirection(dir: Direction) {
  document.documentElement.dir = dir;
  direction.value = dir;
  localStorage.setItem('bunyan_direction', dir);
}
```

**Watch locale changes:**

```typescript
watch(locale, (newLocale) => {
  // If user has NOT manually overridden direction, auto-sync with locale
  if (!hasManualOverride.value) {
    applyDirection(newLocale === 'ar' ? 'rtl' : 'ltr');
  }
});
```

**Key Decision:** A `hasManualOverride` boolean (stored in `localStorage` as `bunyan_direction_manual`) tracks whether the user has manually toggled direction. If `true`, locale changes do NOT auto-update direction. This allows an advanced user to read Arabic content in LTR mode.

**Dependencies:** `types/index.ts` (Direction)

---

### 2.5 Create `frontend/composables/usePreferences.ts`

**File:** `frontend/composables/usePreferences.ts`
**Action:** Create new file

**Purpose:** Single composable aggregating `direction`, `locale`, and `colorMode` preferences. Used by `AppHeader.vue` for the settings area.

**Exports:**

- `direction: Ref<Direction>` — from `useDirection()`
- `toggleDirection(): void` — from `useDirection()`
- `locale: Ref<string>` — from `useI18n()`
- `setLocale(code: Locale): Promise<void>` — calls `useI18n().setLocale()` + navigates
- `colorMode: WritableComputedRef<'light' | 'dark' | 'system'>` — from `useColorMode()`
- `toggleColorMode(): void` — cycles `light → dark → system`

**Key Decision:** This composable is a **facade** — it does NOT add logic; it composes and re-exports from existing composables. Keeps component templates clean.

**Dependencies:** `composables/useDirection.ts`, `types/index.ts`

---

## Phase 3 — Navigation Components

### 3.1 Create `frontend/app/config/navigation.ts`

**File:** `frontend/app/config/navigation.ts`
**Action:** Create new file

**Purpose:** Single source of truth for all navigation items per role. Imported by `AppNavigation.vue`.

**Structure:**

```typescript
import { UserRole } from '~/types';
import type { NavItem } from '~/types';

export const NAV_ITEMS_BY_ROLE: Record<UserRole, NavItem[]> = {
  [UserRole.Customer]: [
    {
      labelKey: 'nav.dashboard',
      icon: 'i-heroicons-home',
      to: '/dashboard',
      roles: [UserRole.Customer],
    },
    {
      labelKey: 'nav.projects',
      icon: 'i-heroicons-folder',
      to: '/projects',
      roles: [UserRole.Customer],
    },
    {
      labelKey: 'nav.orders',
      icon: 'i-heroicons-shopping-bag',
      to: '/orders',
      roles: [UserRole.Customer],
    },
    {
      labelKey: 'nav.payments',
      icon: 'i-heroicons-credit-card',
      to: '/payments',
      roles: [UserRole.Customer],
    },
  ],
  [UserRole.Contractor]: [
    {
      labelKey: 'nav.dashboard',
      icon: 'i-heroicons-home',
      to: '/dashboard',
      roles: [UserRole.Contractor],
    },
    {
      labelKey: 'nav.projects',
      icon: 'i-heroicons-folder',
      to: '/projects',
      roles: [UserRole.Contractor],
    },
    {
      labelKey: 'nav.earnings',
      icon: 'i-heroicons-banknotes',
      to: '/earnings',
      roles: [UserRole.Contractor],
    },
    {
      labelKey: 'nav.withdrawals',
      icon: 'i-heroicons-arrow-up-on-square',
      to: '/withdrawals',
      roles: [UserRole.Contractor],
    },
  ],
  [UserRole.SupervisingArchitect]: [
    {
      labelKey: 'nav.dashboard',
      icon: 'i-heroicons-home',
      to: '/dashboard',
      roles: [UserRole.SupervisingArchitect],
    },
    {
      labelKey: 'nav.projects',
      icon: 'i-heroicons-folder',
      to: '/projects',
      roles: [UserRole.SupervisingArchitect],
    },
    {
      labelKey: 'nav.field_engineers',
      icon: 'i-heroicons-user-group',
      to: '/field-engineers',
      roles: [UserRole.SupervisingArchitect],
    },
    {
      labelKey: 'nav.reports',
      icon: 'i-heroicons-document-text',
      to: '/reports',
      roles: [UserRole.SupervisingArchitect],
    },
  ],
  [UserRole.FieldEngineer]: [
    {
      labelKey: 'nav.dashboard',
      icon: 'i-heroicons-home',
      to: '/dashboard',
      roles: [UserRole.FieldEngineer],
    },
    {
      labelKey: 'nav.my_projects',
      icon: 'i-heroicons-folder',
      to: '/projects',
      roles: [UserRole.FieldEngineer],
    },
    {
      labelKey: 'nav.submit_report',
      icon: 'i-heroicons-paper-airplane',
      to: '/reports/create',
      roles: [UserRole.FieldEngineer],
    },
  ],
  [UserRole.Admin]: [
    {
      labelKey: 'nav.dashboard',
      icon: 'i-heroicons-home',
      to: '/dashboard',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.users',
      icon: 'i-heroicons-users',
      to: '/admin/users',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.projects',
      icon: 'i-heroicons-folder',
      to: '/admin/projects',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.products',
      icon: 'i-heroicons-cube',
      to: '/admin/products',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.orders',
      icon: 'i-heroicons-shopping-bag',
      to: '/admin/orders',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.configuration',
      icon: 'i-heroicons-cog-6-tooth',
      to: '/admin/configuration',
      roles: [UserRole.Admin],
    },
    {
      labelKey: 'nav.reports',
      icon: 'i-heroicons-document-text',
      to: '/admin/reports',
      roles: [UserRole.Admin],
    },
  ],
};
```

**Key Decision:** Routes do NOT include locale prefix — `useLocaleRoute().push()` handles that at navigation time. Items are keyed by `UserRole` enum values.

**Dependencies:** `types/index.ts`

---

### 3.2 Create `frontend/app/components/navigation/AppNavigation.vue`

**File:** `frontend/app/components/navigation/AppNavigation.vue`
**Action:** Create new file

**Purpose:** Smart navigation component that reads current user role, maps nav items, resolves i18n labels, and emits to parent. Does NOT render UI — delegates to `AppSidebar` and `MobileDrawer`.

**Props:** None
**Emits:** None

**Logic:**

- Reads `useAuthStore().role`
- Maps `NAV_ITEMS_BY_ROLE[role]`
- Resolves each item's label via `t(item.labelKey)` → produces `{ label, icon, to }[]`
- Adds locale prefix to `to` via `useLocaleRoute()`
- Provides resolved nav items via `provide('navItems', resolvedItems)` for child components

**Key Decision:** `AppNavigation` is a renderless provider component — it wraps `<slot />` and injects resolved nav items downward. This avoids prop-drilling through layout → sidebar → nav.

**Nuxt UI Components Used:** None (logic-only)

**Dependencies:** `stores/auth.ts`, `app/config/navigation.ts`, `composables/useLocaleRoute.ts`, `types/index.ts`

---

### 3.3 Create `frontend/app/components/navigation/AppHeader.vue`

**File:** `frontend/app/components/navigation/AppHeader.vue`
**Action:** Create new file

**Purpose:** Top navigation bar. Sticky-positioned `<header>` with:

- Logo (start/left)
- Hamburger button (mobile only, `< md`)
- Page title (center, optional)
- Controls cluster (end/right): language switcher, direction toggle, dark mode toggle, user dropdown

**DESIGN.md Compliance:**

- `box-shadow: 0px 0px 0px 1px rgba(0,0,0,0.08)` on the header (shadow-as-border, not `border-b`)
- Geist font, `#171717` text
- `bg-white dark:bg-[#0a0a0a]` background

**Nuxt UI Components Used:**

- `UButton` — hamburger, language toggle, direction toggle, color mode toggle
- `UDropdownMenu` — user menu (profile, settings, logout)
- `UAvatar` — user avatar in dropdown trigger

**User Dropdown Items:**

```typescript
const userMenuItems = computed<DropdownMenuGroup[]>(() => [
  [
    {
      label: t('profile'),
      icon: 'i-heroicons-user',
      to: `/${locale.value}/profile`,
    },
    {
      label: t('settings'),
      icon: 'i-heroicons-cog-6-tooth',
      to: `/${locale.value}/settings`,
    },
  ],
  [
    {
      label: t('logout'),
      icon: 'i-heroicons-arrow-right-on-rectangle',
      click: logout,
    },
  ],
]);
```

**Props:**

- `showHamburger?: boolean` (default: `true`)
- `title?: string`

**Emits:**

- `hamburger-click` — consumed by `default.vue` layout to toggle mobile drawer

**Dependencies:** `composables/useAuth.ts`, `composables/usePreferences.ts`, `types/index.ts`

---

### 3.4 Create `frontend/app/components/navigation/AppSidebar.vue`

**File:** `frontend/app/components/navigation/AppSidebar.vue`
**Action:** Create new file

**Purpose:** Desktop sidebar (visible `md:` and above). Custom `<aside>` wrapper containing `UNavigationMenu`.

**Structure:**

```
<aside class="hidden md:flex flex-col w-64 ...">
  <!-- Sidebar header: logo + app name -->
  <!-- UNavigationMenu orientation="vertical" :items="navItems" -->
  <!-- Sidebar footer: user info + version -->
</aside>
```

**DESIGN.md Compliance:**

- `border-e shadow-[1px_0_0_0_rgba(0,0,0,0.08)]` (logical border-end as shadow)
- `bg-white dark:bg-[#0a0a0a]`
- Navigation items: weight 500, size 14px

**Nuxt UI Components:**

- `UNavigationMenu` with `orientation="vertical"` and `:items` prop
- `UAvatar` for user photo in sidebar footer
- `UBadge` for admin indicator

**Props:**

- `items: NavItem[]` — resolved nav items from parent

**Key Decision:** Does NOT use `inject('navItems')` — receives items via prop from `default.vue` layout which gets them from `AppNavigation`. This makes testing easier.

**Dependencies:** `composables/useAuth.ts`, `types/index.ts`

---

### 3.5 Create `frontend/app/components/navigation/MobileDrawer.vue`

**File:** `frontend/app/components/navigation/MobileDrawer.vue`
**Action:** Create new file

**Purpose:** Mobile navigation using `UDrawer`. Opens from the inline-start direction — right in RTL, left in LTR. Controlled by `useUiStore().isDrawerOpen`.

**Nuxt UI Components:**

- `UDrawer` with `v-model:open="isDrawerOpen"` and `:direction="direction === 'rtl' ? 'right' : 'left'"`
  - **Note:** `UDrawer` in Nuxt UI v4.6.1 uses `direction` prop (not `side`), accepting `'left' | 'right' | 'top' | 'bottom'` only. `side="start"` does not exist and is silently ignored.
  - `direction` is read from `useDirection().direction` to react to RTL/LTR toggles.
- `UNavigationMenu` orientation="vertical" inside the drawer

**Props:**

- `items: NavItem[]` — resolved nav items

**Key Decision:** `:direction="direction === 'rtl' ? 'right' : 'left'"` binds UDrawer position reactively to the current direction, achieving the logical-start effect without relying on a non-existent `side` prop.

**Dependencies:** `stores/ui.ts`, `types/index.ts`

---

### 3.6 Create `frontend/app/components/navigation/AppBreadcrumb.vue`

**File:** `frontend/app/components/navigation/AppBreadcrumb.vue`
**Action:** Create new file

**Purpose:** Thin wrapper around `UBreadcrumb` that feeds it from `useBreadcrumb()`. Only renders if `breadcrumbs.length > 0`.

**Template:**

```vue
<UBreadcrumb v-if="breadcrumbs.length" :items="breadcrumbs" class="px-4 py-2" />
```

**Nuxt UI Components:** `UBreadcrumb`

**Dependencies:** `composables/useBreadcrumb.ts`

---

### 3.7 Create `frontend/app/components/navigation/AppFooter.vue`

**File:** `frontend/app/components/navigation/AppFooter.vue`
**Action:** Create new file

**Purpose:** Minimal platform footer — copyright line + platform name + version.

**Structure:**

```html
<footer
  class="shadow-[0_-1px_0_0_rgba(0,0,0,0.08)] py-4 px-6
               text-sm text-[#666666] flex items-center justify-between"
>
  <span>{{ t('layout.footer_copyright') }}</span>
  <span class="text-xs">v{{ version }}</span>
</footer>
```

**i18n Note:** The copyright text MUST use `t('layout.footer_copyright')` from `useI18n()`. The literal Arabic string `© 2026 بنيان` must NOT appear in the component template — it belongs only in `locales/ar.json`. Both `locales/ar.json` and `locales/en.json` must include this key (see Phase 5 i18n updates).

**DESIGN.md Compliance:**

- `shadow-[0_-1px_0_0_rgba(0,0,0,0.08)]` as top border (shadow-as-border)
- Font size 12px (`text-xs`), color `#666666` (Gray 500)

**Nuxt UI Components:** None (pure HTML + Tailwind)

**Dependencies:** None

---

## Phase 4 — Layouts

### 4.1 Create `frontend/layouts/default.vue`

**File:** `frontend/layouts/default.vue`
**Action:** Create new file

**Purpose:** Full authenticated app shell. Used by all authenticated pages via `definePageMeta({ layout: 'default' })` (or as the fallback layout).

**Structure:**

```
<div class="min-h-screen flex flex-col">
  <AppNavigation>           ← provides nav items
    <AppHeader />           ← sticky top bar
    <div class="flex flex-1">
      <AppSidebar />        ← desktop (hidden on mobile)
      <main class="flex-1 flex flex-col overflow-auto">
        <AppBreadcrumb />   ← below header, above content
        <UProgress />       ← global loading indicator
        <div class="p-6">
          <slot />          ← page content
        </div>
      </main>
    </div>
    <AppFooter />           ← bottom
    <MobileDrawer />        ← off-canvas (mobile only)
  </AppNavigation>
</div>
```

**Auth Guard:**

- `defineNuxtRouteMiddleware` at layout level OR rely on `middleware/auth.ts` being applied to pages
- Layout itself checks `useAuthStore().isAuthenticated` — if false, layouts can redirect but pages should also apply middleware

**UProgress Integration:**

```vue
<UProgress v-if="isPageLoading" animation="carousel" class="fixed top-0 start-0 end-0 z-50 h-0.5" />
```

- `isPageLoading` from `useUiStore().isPageLoading`
- Set to `true` on `useNuxtApp().hook('page:start', ...)` and `false` on `page:finish`

**Key Decision:** The `UProgress` bar is rendered inside `default.vue` — NOT in `app.vue` — because public and auth layouts should NOT show the same loading bar.

**Dependencies:** All Phase 3 components, `stores/ui.ts`, `stores/auth.ts`

---

### 4.2 Create `frontend/layouts/auth.vue`

**File:** `frontend/layouts/auth.vue`
**Action:** Create new file

**Purpose:** Minimal layout for unauthenticated pages (login, register, forgot password).

**Structure:**

```vue
<div class="min-h-screen flex items-center justify-center bg-[#fafafa] dark:bg-[#0a0a0a] p-4">
  <UCard class="w-full max-w-md ..." :ui="{ root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]' }">
    <!-- Logo slot -->
    <template #header>
      <div class="flex justify-center py-4">
        <img src="/logo.svg" alt="بنيان" class="h-8" />
      </div>
    </template>
    <!-- Page content -->
    <slot />
  </UCard>
</div>
```

**DESIGN.md Compliance:**

- Background `#fafafa` (Gray 50) — subtle surface tint
- Card shadow-as-border stack
- No header, sidebar, footer — zero chrome

**Guest Guard:**

- Check `useAuthStore().isAuthenticated` — if true, redirect to dashboard

**Nuxt UI Components:** `UCard`

**Dependencies:** `stores/auth.ts`

---

### 4.3 Create `frontend/layouts/public.vue`

**File:** `frontend/layouts/public.vue`
**Action:** Create new file

**Purpose:** Marketing/landing layout with minimal header, no sidebar.

**Structure:**

```
<div class="min-h-screen flex flex-col">
  <header class="sticky top-0 z-40 bg-white/80 backdrop-blur ...">
    <!-- Logo + "Sign In" + "Register" buttons -->
  </header>
  <main class="flex-1">
    <slot />
  </main>
  <AppFooter />
</div>
```

**Nuxt UI Components:** `UButton`, `UContainer`

**Dependencies:** `composables/useLocaleRoute.ts`, `app/components/navigation/AppFooter.vue`

---

## Phase 5 — Update Core Files

### 5.1 Update `frontend/app/app.vue`

**File:** `frontend/app/app.vue`
**Action:** Modify existing file

**Critical Change:** Add `<NuxtLayout>` wrapper around `<NuxtPage />`.

**Before:**

```vue
<template>
  <NuxtRouteAnnouncer />
  <GlobalErrorBoundary>
    <NuxtPage />
  </GlobalErrorBoundary>
  <ErrorToast />
</template>
```

**After:**

```vue
<template>
  <NuxtRouteAnnouncer />
  <GlobalErrorBoundary>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </GlobalErrorBoundary>
  <ErrorToast />
</template>
```

**Also:**

- Keep existing `useI18n().locale` → `htmlDir` computed → `useHead({ htmlAttrs: { dir, lang } })`
- Add `useDirection()` call so manual direction overrides also apply via `useHead`
- Do NOT add `<UNotifications />` — the existing `<ErrorToast />` handles notifications

**Data coordination:**

```typescript
// Sync useDirection with locale-derived direction
const { direction } = useDirection();
useHead({
  htmlAttrs: {
    dir: direction, // Now controlled by useDirection (which already syncs with locale)
    lang: htmlLang,
  },
});
```

**Dependencies:** Phase 2 composables

---

### 5.2 Update `frontend/app/error.vue`

**File:** `frontend/app/error.vue`
**Action:** Modify existing file — add `<UAlert>` rendering

**Current state:** Has `<script setup>` logic but add the `<template>` using Nuxt UI `UAlert` component instead of the raw template (the current file doesn't show a template section).

**Template update:**

```vue
<template>
  <div class="min-h-screen flex items-center justify-center p-6 bg-[#fafafa]">
    <UCard class="w-full max-w-lg" :ui="{ root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]' }">
      <UAlert
        :color="statusCode === 404 ? 'warning' : 'error'"
        :title="content.title"
        :description="content.message"
        :icon="statusCode === 404 ? 'i-heroicons-map-pin' : 'i-heroicons-exclamation-triangle'"
      />
      <template #footer>
        <div class="flex gap-3 justify-end mt-4">
          <UButton variant="ghost" @click="goBack">
            {{ t('errors.go_back') }}
          </UButton>
          <UButton @click="goHome">
            {{ t('errors.go_home') }}
          </UButton>
        </div>
      </template>
    </UCard>
  </div>
</template>
```

**Keep existing `<script setup>` logic** — only update `<template>` to use Nuxt UI components.

**Dependencies:** Phase 0 (nuxt.config.ts for colorMode), Nuxt UI available globally

---

### 5.3 Update `frontend/locales/ar.json`

**File:** `frontend/locales/ar.json`
**Action:** Add new top-level sections (merge with existing content)

**New keys to add:**

```json
{
  "nav": {
    "dashboard": "لوحة التحكم",
    "projects": "المشاريع",
    "my_projects": "مشاريعي",
    "orders": "الطلبات",
    "payments": "المدفوعات",
    "earnings": "الأرباح",
    "withdrawals": "السحوبات",
    "field_engineers": "المهندسون الميدانيون",
    "reports": "التقارير",
    "submit_report": "إرسال تقرير",
    "users": "المستخدمون",
    "products": "المنتجات",
    "configuration": "الإعدادات"
  },
  "layout": {
    "toggle_sidebar": "تبديل القائمة الجانبية",
    "toggle_direction": "تبديل الاتجاه",
    "toggle_dark_mode": "تبديل الوضع المظلم",
    "switch_language": "تغيير اللغة",
    "open_menu": "فتح القائمة",
    "close_menu": "إغلاق القائمة",
    "language_ar": "العربية",
    "language_en": "English",
    "direction_rtl": "يمين لليسار",
    "direction_ltr": "يسار لليمين",
    "loading": "جاري التحميل...",
    "footer_copyright": "© 2026 بنيان. جميع الحقوق محفوظة."
  },
  "roles": {
    "customer": "العميل",
    "contractor": "المقاول",
    "supervising_architect": "المهندس المشرف",
    "field_engineer": "المهندس الميداني",
    "admin": "الإدارة"
  }
}
```

---

### 5.4 Update `frontend/locales/en.json`

**File:** `frontend/locales/en.json`
**Action:** Add new top-level sections (mirror of ar.json)

**New keys to add:**

```json
{
  "nav": {
    "dashboard": "Dashboard",
    "projects": "Projects",
    "my_projects": "My Projects",
    "orders": "Orders",
    "payments": "Payments",
    "earnings": "Earnings",
    "withdrawals": "Withdrawals",
    "field_engineers": "Field Engineers",
    "reports": "Reports",
    "submit_report": "Submit Report",
    "users": "Users",
    "products": "Products",
    "configuration": "Configuration"
  },
  "layout": {
    "toggle_sidebar": "Toggle Sidebar",
    "toggle_direction": "Toggle Direction",
    "toggle_dark_mode": "Toggle Dark Mode",
    "switch_language": "Switch Language",
    "open_menu": "Open Menu",
    "close_menu": "Close Menu",
    "language_ar": "العربية",
    "language_en": "English",
    "direction_rtl": "Right to Left",
    "direction_ltr": "Left to Right",
    "loading": "Loading...",
    "footer_copyright": "© 2026 Bunyan. All rights reserved."
  },
  "roles": {
    "customer": "Customer",
    "contractor": "Contractor",
    "supervising_architect": "Supervising Architect",
    "field_engineer": "Field Engineer",
    "admin": "Admin"
  }
}
```

---

## Phase 6 — Tests

### 6.1 Unit Tests: Composables

**File:** `frontend/tests/unit/composables/useDirection.test.ts`

Test cases:

- `setDirection('rtl')` → `document.documentElement.dir === 'rtl'` + localStorage updated
- `setDirection('ltr')` → `document.documentElement.dir === 'ltr'` + localStorage updated
- `toggle()` → flips direction
- On init, reads stored value from localStorage mock
- If no stored value, defaults to `'rtl'`

---

**File:** `frontend/tests/unit/composables/useNotification.test.ts`

Test cases:

- `notifySuccess()` → delegates to `useToast().showSuccess()`
- `notifyError()` → delegates to `useToast().showError()`
- `notifyWarning()` → delegates to `useToast().showWarning()`
- `notifyInfo()` → delegates to `useToast().showInfo()`
- `dismiss(id)` → delegates to `useToast().removeToast(id)`

---

**File:** `frontend/tests/unit/composables/useBreadcrumb.test.ts`

Test cases:

- When `route.meta.breadcrumb` is set, `breadcrumbs` returns it
- When `route.meta.breadcrumb` is absent, `breadcrumbs` returns `[]`
- After `setBreadcrumbs([...])`, `breadcrumbs` reflects the new value
- Reactively updates when route changes (mock router push)

---

**File:** `frontend/tests/unit/composables/useAuth.test.ts`

Test cases:

- `isAuthenticated` returns `false` when store has no user
- `logout()` fires `DELETE /api/v1/auth/logout` then clears store
- `logout()` clears store even when API call fails
- `hasRole('admin')` returns `true` when user has admin role
- `hasRole(['admin', 'contractor'])` returns `true` for contractor user

---

**File:** `frontend/tests/unit/composables/usePreferences.test.ts`

Test cases:

- `toggleDirection()` delegates to `useDirection().toggle()`
- `toggleColorMode()` cycles `light → dark → system → light`
- All exports are defined and callable

---

### 6.2 Component Tests

**File:** `frontend/tests/unit/components/AppNavigation.test.ts`

Test cases:

- Customer role → nav items include `nav.dashboard`, `nav.projects`, `nav.orders`, `nav.payments`
- Customer role → nav items do NOT include admin-only routes
- Admin role → nav items include all sections
- Field engineer role → nav items include `nav.submit_report`
- Resolved labels use i18n `t()` values (not raw keys)

---

**File:** `frontend/tests/unit/components/AppHeader.test.ts`

Test cases:

- Renders user avatar when `useAuthStore().user` is set
- Emits `hamburger-click` event when hamburger button is clicked
- User dropdown contains logout item
- Language switcher shows current locale

---

### 6.3 Integration Tests

**File:** `frontend/tests/unit/integration/direction.test.ts`

Test cases:

- On mount with `bunyan_direction = 'rtl'` in localStorage → `<html dir="rtl">`
- On mount with `bunyan_direction = 'ltr'` in localStorage → `<html dir="ltr">`
- `useDirection().toggle()` → DOM `dir` attribute changes

---

**File:** `frontend/tests/unit/integration/breadcrumb.test.ts`

Test cases:

- Navigate to route with `meta.breadcrumb` set → `AppBreadcrumb` renders items
- Navigate to route without breadcrumb → `AppBreadcrumb` renders nothing (hidden)

---

**File:** `frontend/tests/unit/integration/layout.test.ts`

Test cases:

- Unauthenticated user accessing default layout page → redirected to login
- Authenticated user → default layout renders `AppHeader`, `AppSidebar`, `AppFooter`
- Auth layout page → no sidebar, no footer rendered

---

## Implementation Checklist

### Phase 0

- [ ] `nuxt.config.ts` updated with `runtimeConfig.public.apiBaseUrl` + `colorMode`
- [ ] `plugins/direction.client.ts` created
- [ ] `types/index.ts` created with all types

### Phase 1

- [ ] `stores/auth.ts` created with user/token/role/isAuthenticated
- [ ] `stores/ui.ts` created with sidebar/drawer/loading state

### Phase 2

- [ ] `composables/useAuth.ts` created with `logout()` + `fetchCurrentUser()`
- [ ] `composables/useNotification.ts` created
- [ ] `composables/useBreadcrumb.ts` created
- [ ] `composables/useDirection.ts` created with localStorage persistence
- [ ] `composables/usePreferences.ts` created

### Phase 3

- [ ] `app/config/navigation.ts` created with `NAV_ITEMS_BY_ROLE`
- [ ] `app/components/navigation/AppNavigation.vue` created
- [ ] `app/components/navigation/AppHeader.vue` created
- [ ] `app/components/navigation/AppSidebar.vue` created
- [ ] `app/components/navigation/MobileDrawer.vue` created
- [ ] `app/components/navigation/AppBreadcrumb.vue` created
- [ ] `app/components/navigation/AppFooter.vue` created

### Phase 4

- [ ] `layouts/default.vue` created with full authenticated shell
- [ ] `layouts/auth.vue` created
- [ ] `layouts/public.vue` created

### Phase 5

- [ ] `app/app.vue` updated with `<NuxtLayout>` wrapper
- [ ] `app/error.vue` updated with `UAlert` + `UCard`
- [ ] `locales/ar.json` updated with nav + layout keys
- [ ] `locales/en.json` updated with nav + layout keys

### Phase 6

- [ ] `useDirection` unit tests passing
- [ ] `useNotification` unit tests passing
- [ ] `useBreadcrumb` unit tests passing
- [ ] `useAuth` unit tests passing
- [ ] `AppNavigation` component tests passing
- [ ] `AppHeader` component tests passing
- [ ] Direction integration tests passing
- [ ] Breadcrumb integration tests passing
- [ ] Layout integration tests passing
- [ ] `npm run lint` passes
- [ ] `npm run typecheck` passes
- [ ] `npm run test` passes

---

## File Creation Summary

| Phase | File                                             | Type           |
| ----- | ------------------------------------------------ | -------------- |
| 0     | `nuxt.config.ts`                                 | Modify         |
| 0     | `plugins/direction.client.ts`                    | Create         |
| 0     | `types/index.ts`                                 | Create         |
| 1     | `stores/auth.ts`                                 | Create         |
| 1     | `stores/ui.ts`                                   | Create         |
| 2     | `composables/useAuth.ts`                         | Create         |
| 2     | `composables/useNotification.ts`                 | Create         |
| 2     | `composables/useBreadcrumb.ts`                   | Create         |
| 2     | `composables/useDirection.ts`                    | Create         |
| 2     | `composables/usePreferences.ts`                  | Create         |
| 3     | `app/config/navigation.ts`                       | Create         |
| 3     | `app/components/navigation/AppNavigation.vue`    | Create         |
| 3     | `app/components/navigation/AppHeader.vue`        | Create         |
| 3     | `app/components/navigation/AppSidebar.vue`       | Create         |
| 3     | `app/components/navigation/MobileDrawer.vue`     | Create         |
| 3     | `app/components/navigation/AppBreadcrumb.vue`    | Create         |
| 3     | `app/components/navigation/AppFooter.vue`        | Create         |
| 4     | `layouts/default.vue`                            | Create         |
| 4     | `layouts/auth.vue`                               | Create         |
| 4     | `layouts/public.vue`                             | Create         |
| 5     | `app/app.vue`                                    | Modify         |
| 5     | `app/error.vue`                                  | Modify         |
| 5     | `locales/ar.json`                                | Modify (merge) |
| 5     | `locales/en.json`                                | Modify (merge) |
| 6     | `tests/unit/composables/useDirection.test.ts`    | Create         |
| 6     | `tests/unit/composables/useNotification.test.ts` | Create         |
| 6     | `tests/unit/composables/useBreadcrumb.test.ts`   | Create         |
| 6     | `tests/unit/composables/useAuth.test.ts`         | Create         |
| 6     | `tests/unit/composables/usePreferences.test.ts`  | Create         |
| 6     | `tests/unit/components/AppNavigation.test.ts`    | Create         |
| 6     | `tests/unit/components/AppHeader.test.ts`        | Create         |
| 6     | `tests/unit/integration/direction.test.ts`       | Create         |
| 6     | `tests/unit/integration/breadcrumb.test.ts`      | Create         |
| 6     | `tests/unit/integration/layout.test.ts`          | Create         |

**Total:** 3 modified + 27 created = **30 files**
