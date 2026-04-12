# STAGE_29 — Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Stage File:** `specs/phases/07_FRONTEND_APPLICATION/STAGE_29_NUXT_SHELL.md` > **Branch:** `spec/029-nuxt-shell` > **Runtime Dir:** `specs/runtime/029-nuxt-shell/` > **Created:** 2026-04-12T00:00:00Z

---

## Objective

Implement the Nuxt.js 3 application shell for the Bunyan platform. This stage delivers the complete layout system (default, auth, public), role-aware navigation, RTL/LTR toggle, dark mode, Arabic/English language switching, global loading states, toast notifications, skeleton screens, and the five core composables required by all downstream frontend pages.

This stage does **not** implement any business-domain pages. It establishes the structural scaffolding and shared infrastructure that every subsequent frontend stage depends on.

---

## Scope

### In Scope

- Default layout: `UHeader` + `UNavigationTree` sidebar + `UFooter` + `<NuxtPage />`
- Auth layout: minimal centered `UCard`, zero chrome (no header/sidebar/footer)
- Public layout: landing-page wrapper with minimal `UHeader` (unauthenticated links)
- Role-based navigation menu items (Customer / Contractor / Supervising Architect / Field Engineer / Admin)
- Mobile-responsive navigation via `UDrawer` (hamburger at `< 768px`)
- Breadcrumb bar using `UBreadcrumb` beneath the `UHeader`
- RTL/LTR direction toggle — persisted to `localStorage` via `useDirection`
- Dark mode toggle via `useColorMode()` + Nuxt UI `AppConfig`
- Language switcher (AR / EN) via `@nuxtjs/i18n`
- Toast notification system via `useToast()` wrapped in `useNotification`
- Global page-level loading indicator via `UProgress`
- Skeleton loading states via `USkeleton`
- Global error boundary page (`error.vue`) using `UAlert` with `color="error"`
- User avatar + dropdown via `UAvatar` + `UDropdownMenu` (logout, profile, settings)
- Sliding sidebar on mobile via `USlideover`
- Core composables: `useAuth`, `useApi`, `useNotification`, `useBreadcrumb`, `useDirection`, `usePreferences`
- `plugins/direction.client.ts` — applies persisted `dir` before Vue mounts (prevents CLS flash)

### Out of Scope

- Business-domain pages (projects, tasks, reports, products, orders)
- Authentication login/register pages (covered by STAGE_30_AUTH_PAGES)
- Pinia store implementations beyond auth state bootstrapping
- Backend API endpoints (all backend stages are upstream)
- Payment or e-commerce UI
- File upload components
- Admin configuration panels

---

## User Stories

### US1 — Default Layout (All Authenticated Users)

**As an** authenticated user (any role), **I want** a consistent app shell with header, sidebar navigation, and footer, **so that** I can navigate the platform efficiently from any page.

**Acceptance Criteria:**

- [ ] A `default.vue` layout renders `UHeader`, role-filtered `UNavigationTree`, `<NuxtPage />`, and `UFooter`
- [ ] Navigation items are filtered by the authenticated user's role
- [ ] The active route is highlighted in the `UNavigationTree`
- [ ] The shell renders correctly in both RTL (Arabic) and LTR (English)
- [ ] The layout is responsive: sidebar collapses to `UDrawer` on viewports < 768px
- [ ] `UBreadcrumb` is visible below the header, reflecting the current route hierarchy

### US2 — Auth Layout (Unauthenticated Users)

**As an** unauthenticated visitor, **I want** a minimal, distraction-free layout for login and registration pages, **so that** I can focus on completing authentication.

**Acceptance Criteria:**

- [ ] An `auth.vue` layout renders only a centered `UCard`, with no header, sidebar, or footer
- [ ] The layout is full-viewport centered (horizontally and vertically)
- [ ] The Bunyan logo is displayed inside the `UCard`
- [ ] The layout supports RTL direction if the browser/stored preference is Arabic

### US3 — Public Layout (Landing Pages)

**As a** visitor, **I want** to view landing and marketing pages with a lightweight header and no sidebar, **so that** I can learn about the platform before signing up.

**Acceptance Criteria:**

- [ ] A `public.vue` layout renders a minimal `UHeader` with logo + sign-in/sign-up links
- [ ] No sidebar is rendered
- [ ] Navigation links in the header route to the auth layout pages
- [ ] The layout supports RTL direction

### US4 — Role-Based Navigation (Customer)

**As a** Customer, **I want** to see only the navigation items relevant to my role (projects, orders, payments), **so that** the interface is uncluttered and focused on my needs.

**Acceptance Criteria:**

- [ ] Customer navigation contains: Dashboard, Projects, Orders, Payments
- [ ] Admin-only and Contractor-only items are not visible to Customers
- [ ] Navigation items link to the correct routes
- [ ] The active item is highlighted based on the current route

### US5 — Role-Based Navigation (Contractor)

**As a** Contractor, **I want** to see navigation items for project execution and earnings, **so that** I can manage my work efficiently.

**Acceptance Criteria:**

- [ ] Contractor navigation contains: Dashboard, Projects, Earnings, Withdrawals
- [ ] Customer-specific and Admin-specific items are not visible

### US6 — Role-Based Navigation (Supervising Architect)

**As a** Supervising Architect, **I want** navigation items for oversight tasks and field engineer management, **so that** I can monitor project progress.

**Acceptance Criteria:**

- [ ] Supervising Architect navigation contains: Dashboard, Projects, Field Engineers, Reports
- [ ] Items exclusive to other roles are not visible

### US7 — Role-Based Navigation (Field Engineer)

**As a** Field Engineer, **I want** navigation focused on field reporting, **so that** I can quickly access my reporting tasks.

**Acceptance Criteria:**

- [ ] Field Engineer navigation contains: Dashboard, My Projects, Submit Report
- [ ] Items exclusive to other roles are not visible

### US8 — Role-Based Navigation (Admin)

**As an** Admin, **I want** access to all platform sections including configuration panels, **so that** I can manage the entire platform.

**Acceptance Criteria:**

- [ ] Admin navigation contains: Dashboard, Users, Projects, Products, Orders, Configuration, Reports
- [ ] All navigation items are accessible
- [ ] Admin badge or indicator is visible in the sidebar header

### US9 — RTL/LTR Direction Toggle

**As a** user, **I want** to toggle the reading direction between RTL (Arabic) and LTR (English), **so that** the interface matches my language preference.

**Acceptance Criteria:**

- [ ] A direction toggle button is visible in the header
- [ ] Clicking the toggle sets `document.documentElement.dir` to `rtl` or `ltr`
- [ ] The selected direction is persisted to `localStorage` under the key `bunyan_direction`
- [ ] On page load, the persisted direction is applied before first render (no flash)
- [ ] All Tailwind logical properties (`ps-`, `pe-`, `ms-`, `me-`) respond correctly to direction changes

### US10 — Dark Mode Toggle

**As a** user, **I want** to switch between light and dark mode, **so that** I can use the app comfortably in different lighting conditions.

**Acceptance Criteria:**

- [ ] A dark mode toggle is available in the `UDropdownMenu` or header
- [ ] Toggling applies the `dark` class to `<html>` via `useColorMode()`
- [ ] The preference is persisted across sessions
- [ ] Nuxt UI components respond to the `dark` class with appropriate color schemes
- [ ] The `AppConfig` `ui.colors.primary` is respected in both modes

### US11 — Language Switcher (AR / EN)

**As a** user, **I want** to switch between Arabic and English, **so that** I can use the platform in my preferred language.

**Acceptance Criteria:**

- [ ] A language switcher is visible in the header (flag icon or "AR / EN" label)
- [ ] Switching to Arabic sets the locale to `ar`, updates `dir="rtl"`, and updates `lang="ar"` on `<html>`
- [ ] Switching to English sets the locale to `en`, updates `dir="ltr"`, and updates `lang="en"` on `<html>`
- [ ] All visible UI text updates to the selected language immediately
- [ ] The language preference is persisted via `@nuxtjs/i18n` `detectBrowserLanguage`

### US12 — Toast Notifications

**As a** user, **I want** to receive brief toast notifications for successful actions and errors, **so that** I get immediate feedback without leaving the current page.

**Acceptance Criteria:**

- [ ] Success toasts appear in the top-end corner (RTL-aware placement)
- [ ] Error toasts are visually distinct (`color="error"`)
- [ ] Toasts auto-dismiss after 4 seconds
- [ ] Warning and info toasts are also supported
- [ ] Toasts can be dismissed manually by the user

### US13 — Mobile Responsive Navigation

**As a** mobile user, **I want** navigation accessible via a hamburger menu that opens a drawer, **so that** I can navigate the app on small screens.

**Acceptance Criteria:**

- [ ] On viewports < 768px, the sidebar is hidden and a hamburger icon appears in the `UHeader`
- [ ] Tapping the hamburger opens a `UDrawer` with the full role-filtered navigation
- [ ] The drawer can be closed by swiping left or tapping outside
- [ ] The `UDrawer` is fully RTL-aware (slides from the correct side)
- [ ] Active route is highlighted inside the drawer

### US14 — Global Loading Indicator

**As a** user, **I want** a visual indicator during page navigation and API calls, **so that** I know the app is loading.

**Acceptance Criteria:**

- [ ] A `UProgress` bar appears at the top of the viewport during route navigation
- [ ] The progress bar auto-completes when navigation finishes
- [ ] API loading states in individual components use `USkeleton` placeholders
- [ ] No layout shift occurs during skeleton-to-content transitions

### US15 — Global Error Page

**As a** user, **I want** a clear error message when something goes wrong, **so that** I understand what happened and how to recover.

**Acceptance Criteria:**

- [ ] `error.vue` renders a `UAlert` with `color="error"` and the error message
- [ ] A "Go Home" button navigates to the appropriate dashboard based on user role
- [ ] 404 errors show a "Page not found" message in Arabic (and English if locale is EN)
- [ ] 500 errors show a generic "Something went wrong" message (no stack trace exposed)

---

## Technical Requirements

### Frontend (Nuxt.js 3)

#### Layout Files

- [ ] `frontend/layouts/default.vue` — standard authenticated shell (UHeader + sidebar + UFooter)
- [ ] `frontend/layouts/auth.vue` — minimal unauthenticated layout (centered UCard)
- [ ] `frontend/layouts/public.vue` — landing page layout (lightweight UHeader)
- [ ] `frontend/error.vue` — global error boundary using UAlert

#### Navigation Configuration

- [ ] `frontend/app/components/navigation/AppNavigation.vue` — role-filtered nav items passed to `UNavigationTree`
- [ ] `frontend/app/components/navigation/AppHeader.vue` — wraps `UHeader` with language switcher, dark mode, direction toggle, user dropdown
- [ ] `frontend/app/components/navigation/AppSidebar.vue` — wraps `UNavigationTree` + `USlideover` for desktop/mobile
- [ ] `frontend/app/components/navigation/AppBreadcrumb.vue` — wraps `UBreadcrumb` with dynamic items from `useBreadcrumb`
- [ ] `frontend/app/components/navigation/AppFooter.vue` — wraps `UFooter`
- [ ] `frontend/app/components/navigation/MobileDrawer.vue` — wraps `UDrawer` for mobile navigation

#### Navigation Role Map (Data)

```typescript
// frontend/app/config/navigation.ts
export const NAV_ITEMS_BY_ROLE: Record<UserRole, NavItem[]> = {
  customer: [
    { label: 'nav.dashboard', icon: 'i-heroicons-home', to: '/customer' },
    { label: 'nav.projects', icon: 'i-heroicons-briefcase', to: '/customer/projects' },
    { label: 'nav.orders', icon: 'i-heroicons-shopping-cart', to: '/customer/orders' },
    { label: 'nav.payments', icon: 'i-heroicons-credit-card', to: '/customer/payments' },
  ],
  contractor: [
    { label: 'nav.dashboard', icon: 'i-heroicons-home', to: '/contractor' },
    { label: 'nav.projects', icon: 'i-heroicons-briefcase', to: '/contractor/projects' },
    { label: 'nav.earnings', icon: 'i-heroicons-banknotes', to: '/contractor/earnings' },
    { label: 'nav.withdrawals', icon: 'i-heroicons-arrow-up-tray', to: '/contractor/withdrawals' },
  ],
  supervising_architect: [
    { label: 'nav.dashboard', icon: 'i-heroicons-home', to: '/architect' },
    { label: 'nav.projects', icon: 'i-heroicons-briefcase', to: '/architect/projects' },
    { label: 'nav.field_engineers', icon: 'i-heroicons-users', to: '/architect/engineers' },
    { label: 'nav.reports', icon: 'i-heroicons-document-text', to: '/architect/reports' },
  ],
  field_engineer: [
    { label: 'nav.dashboard', icon: 'i-heroicons-home', to: '/engineer' },
    { label: 'nav.projects', icon: 'i-heroicons-briefcase', to: '/engineer/projects' },
    { label: 'nav.submit_report', icon: 'i-heroicons-plus-circle', to: '/engineer/reports/new' },
  ],
  admin: [
    { label: 'nav.dashboard', icon: 'i-heroicons-home', to: '/admin' },
    { label: 'nav.users', icon: 'i-heroicons-users', to: '/admin/users' },
    { label: 'nav.projects', icon: 'i-heroicons-briefcase', to: '/admin/projects' },
    { label: 'nav.products', icon: 'i-heroicons-cube', to: '/admin/products' },
    { label: 'nav.orders', icon: 'i-heroicons-shopping-cart', to: '/admin/orders' },
    { label: 'nav.configuration', icon: 'i-heroicons-cog-6-tooth', to: '/admin/config' },
    { label: 'nav.reports', icon: 'i-heroicons-document-text', to: '/admin/reports' },
  ],
};
```

#### Core Composables

**`useAuth`** — `frontend/composables/useAuth.ts`

- Reads auth state from Pinia `useAuthStore`
- Exposes: `user`, `isAuthenticated`, `role`, `permissions`, `logout()`
- `logout()` calls `DELETE /api/auth/logout` via `useApi`, clears store, redirects to `/login`

**`useApi`** — `frontend/composables/useApi.ts`

- Wraps `$fetch` with base URL from `runtimeConfig.public.apiBase`
- Attaches `Authorization: Bearer {token}` header from `useAuthStore`
- Handles 401 responses: clears auth state, redirects to `/login`
- Returns standardized `{ success, data, error }` envelope

**`useNotification`** — `frontend/composables/useNotification.ts`

- Wraps `useToast()` from Nuxt UI
- Exposes: `notifySuccess(msg)`, `notifyError(msg)`, `notifyWarning(msg)`, `notifyInfo(msg)`
- All messages support i18n keys

**`useBreadcrumb`** — `frontend/composables/useBreadcrumb.ts`

- Reactive `ref<BreadcrumbItem[]>` array
- `setBreadcrumb(items)` for manual override
- Auto-generates from `useRoute().meta.breadcrumb` if available
- Provides `items` computed for `AppBreadcrumb`

**`useDirection`** — `frontend/composables/useDirection.ts`

- Reads initial direction from `localStorage` key `bunyan_direction`, defaulting to `rtl`
- `toggle()` flips between `rtl` and `ltr`
- Applies `document.documentElement.dir` on change
- Syncs with `@nuxtjs/i18n` locale: `ar` → `rtl`, `en` → `ltr`

#### Nuxt Configuration (`nuxt.config.ts`)

```typescript
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  ui: {
    theme: {
      colors: ['primary', 'secondary', 'success', 'warning', 'error', 'info'],
    },
  },
  app: {
    head: {
      htmlAttrs: { dir: 'rtl', lang: 'ar' },
    },
  },
  i18n: {
    locales: [
      { code: 'ar', iso: 'ar-SA', dir: 'rtl', file: 'ar.json' },
      { code: 'en', iso: 'en-US', dir: 'ltr', file: 'en.json' },
    ],
    defaultLocale: 'ar',
    detectBrowserLanguage: { useCookie: true, cookieKey: 'bunyan_locale' },
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8000',
    },
  },
});
```

#### State Management (Pinia)

- [ ] `frontend/stores/auth.ts` — `useAuthStore`: `user`, `token`, `isAuthenticated`, `role`, `setUser()`, `clearAuth()`
- [ ] `frontend/stores/ui.ts` — `useUiStore`: `isSidebarOpen`, `isDrawerOpen`, `toggleSidebar()`, `toggleDrawer()`

#### i18n Translation Keys (Minimum Set)

```json
// locales/ar.json (excerpt)
{
  "nav": {
    "dashboard": "لوحة التحكم",
    "projects": "المشاريع",
    "orders": "الطلبات",
    "payments": "المدفوعات",
    "earnings": "الأرباح",
    "withdrawals": "السحوبات",
    "field_engineers": "المهندسون الميدانيون",
    "reports": "التقارير",
    "submit_report": "رفع تقرير",
    "users": "المستخدمون",
    "products": "المنتجات",
    "configuration": "الإعدادات"
  },
  "common": {
    "logout": "تسجيل الخروج",
    "profile": "الملف الشخصي",
    "settings": "الإعدادات",
    "dark_mode": "الوضع الداكن",
    "light_mode": "الوضع الفاتح",
    "rtl": "عربي",
    "ltr": "English",
    "go_home": "الصفحة الرئيسية",
    "error_generic": "حدث خطأ غير متوقع",
    "error_not_found": "الصفحة غير موجودة"
  }
}
```

```json
// locales/en.json (excerpt)
{
  "nav": {
    "dashboard": "Dashboard",
    "projects": "Projects",
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
  "common": {
    "logout": "Log Out",
    "profile": "Profile",
    "settings": "Settings",
    "dark_mode": "Dark Mode",
    "light_mode": "Light Mode",
    "rtl": "عربي",
    "ltr": "English",
    "go_home": "Go Home",
    "error_generic": "Something went wrong",
    "error_not_found": "Page not found"
  }
}
```

---

## Nuxt UI Component Map

| Shell Element         | Nuxt UI Component             | Location                         |
| --------------------- | ----------------------------- | -------------------------------- |
| Navigation bar        | `UHeader` + `UNavigationMenu` | `AppHeader.vue`                  |
| Desktop sidebar       | `UNavigationTree`             | `AppSidebar.vue`                 |
| Mobile sidebar        | `UDrawer` + `UNavigationTree` | `MobileDrawer.vue`               |
| Sliding panel (wide)  | `USlideover`                  | `AppSidebar.vue` (collapsible)   |
| Breadcrumb            | `UBreadcrumb`                 | `AppBreadcrumb.vue`              |
| Footer                | `UFooter`                     | `AppFooter.vue`                  |
| Toast notifications   | `useToast()` (Nuxt UI)        | Via `useNotification` composable |
| Global loading bar    | `UProgress`                   | `default.vue` (top of template)  |
| Skeleton placeholders | `USkeleton`                   | Per-page / per-component         |
| Error boundary        | `UAlert` (`color="error"`)    | `error.vue`                      |
| User avatar           | `UAvatar`                     | `AppHeader.vue` (user area)      |
| User dropdown         | `UDropdownMenu`               | `AppHeader.vue` (profile menu)   |
| Auth page card        | `UCard`                       | `auth.vue` layout                |

---

## Layout Diagrams

### Default Layout (Desktop)

```
┌─────────────────────────────────────────────────────────┐
│  UHeader: Logo | UNavigationMenu | Lang | Dark | Avatar  │
├──────────────────┬──────────────────────────────────────┤
│                  │  UBreadcrumb                          │
│  UNavigationTree │─────────────────────────────────────  │
│  (role-filtered) │                                       │
│                  │  <NuxtPage />                         │
│  [RTL: right]    │                                       │
├──────────────────┴──────────────────────────────────────┤
│  UFooter                                                  │
└─────────────────────────────────────────────────────────┘
```

### Default Layout (Mobile, < 768px)

```
┌──────────────────────────────────┐
│  UHeader: ☰ | Logo | Avatar      │
├──────────────────────────────────┤
│  UBreadcrumb                     │
├──────────────────────────────────┤
│                                  │
│  <NuxtPage />                    │
│                                  │
├──────────────────────────────────┤
│  UFooter                         │
└──────────────────────────────────┘
  [☰ opens UDrawer with nav tree]
```

### Auth Layout

```
┌──────────────────────────────────┐
│                                  │
│         ┌──────────────┐         │
│         │  UCard       │         │
│         │  [Logo]      │         │
│         │  [Form slot] │         │
│         └──────────────┘         │
│                                  │
└──────────────────────────────────┘
```

---

## API Integration Points

| Endpoint                     | Method | Used By   | Purpose                                       |
| ---------------------------- | ------ | --------- | --------------------------------------------- |
| `GET /api/v1/user`           | GET    | `useAuth` | Fetch authenticated user profile on load      |
| `DELETE /api/v1/auth/logout` | DELETE | `useAuth` | Terminate session, clear Sanctum token cookie |
| `GET /api/v1/auth/me`        | GET    | `useAuth` | Returns current user role + permissions       |

**Auth Strategy:** Laravel Sanctum API tokens stored in an accessible (non-httpOnly) cookie `auth_token`. `useApi` sends `Authorization: Bearer {token}` header. On 401, `useApi` clears the cookie and redirects to `/auth/login` (no token refresh — Sanctum tokens are long-lived; re-login required).

All API calls use `useApi` composable. No direct `$fetch` outside of composables.

---

## Non-Functional Requirements

### Performance

- [ ] Initial shell render < 300ms on 4G network (Lighthouse target)
- [ ] No layout shift (CLS < 0.1) during hydration
- [ ] Navigation transitions < 100ms
- [ ] `UProgress` starts within 50ms of navigation trigger
- [ ] Font preloading for Geist Arabic and Geist Mono (per DESIGN.md)

### Accessibility

- [ ] All interactive elements have accessible labels (`aria-label` in Arabic)
- [ ] Keyboard navigation fully functional (Tab, Enter, Escape)
- [ ] `UDrawer` traps focus when open
- [ ] `UDropdownMenu` closes on Escape
- [ ] Color contrast meets WCAG 2.1 AA in both light and dark modes
- [ ] Screen reader announces route changes via `aria-live` region

### RTL / Arabic Support

- [ ] `htmlAttrs.dir = 'rtl'` is the default (Arabic-first)
- [ ] All layout uses Tailwind logical properties (`ps-`, `pe-`, `ms-`, `me-`, `rounded-s-`, `rounded-e-`)
- [ ] No hardcoded `left`/`right` CSS values in layout components
- [ ] `UNavigationTree` displays in the correct position for RTL (right side of viewport)
- [ ] `UDrawer` slides from the correct edge based on `dir`
- [ ] Numbers and dates are formatted correctly for Arabic locale

### Security

- [ ] No authentication token stored in `localStorage` — use `httpOnly` cookie or Pinia in-memory only
- [ ] `useApi` attaches the token from secure store only
- [ ] Routes requiring authentication use Nuxt middleware (`auth.ts`) not client-side checks
- [ ] Error messages in `error.vue` never expose stack traces

### Browser Support

- [ ] Chrome 120+, Firefox 120+, Safari 17+
- [ ] iOS Safari 16+ (RTL drawer tested)
- [ ] Android Chrome 120+

---

## Middleware

- [ ] `frontend/middleware/auth.ts` — redirects unauthenticated users to `/login`
- [ ] `frontend/middleware/role.ts` — redirects authenticated users who lack the required role
- [ ] Both middleware are `defineNuxtRouteMiddleware` functions
- [ ] Both middleware use `useAuthStore` to read auth state (no HTTP call)

```typescript
// frontend/middleware/auth.ts
export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore();
  if (!auth.isAuthenticated) {
    return navigateTo('/login');
  }
});
```

---

## Dependencies

- **Upstream:**

  - `STAGE_01_PROJECT_INITIALIZATION` — Nuxt project scaffolding must exist
  - `STAGE_27_LARAVEL_API_AUTH` (or equivalent) — `/api/user` and `/api/auth/logout` must be functional for composable tests

- **Downstream:** All frontend page stages (STAGE_30+). Every page depends on the layouts, composables, and navigation config delivered here.

---

## File Delivery Map

| File Path                                              | Purpose                        |
| ------------------------------------------------------ | ------------------------------ |
| `frontend/layouts/default.vue`                         | Main authenticated layout      |
| `frontend/layouts/auth.vue`                            | Unauthenticated minimal layout |
| `frontend/layouts/public.vue`                          | Landing page layout            |
| `frontend/error.vue`                                   | Global error boundary          |
| `frontend/app/components/navigation/AppHeader.vue`     | Header with controls           |
| `frontend/app/components/navigation/AppSidebar.vue`    | Desktop sidebar                |
| `frontend/app/components/navigation/AppBreadcrumb.vue` | Dynamic breadcrumb bar         |
| `frontend/app/components/navigation/AppFooter.vue`     | Footer                         |
| `frontend/app/components/navigation/MobileDrawer.vue`  | Mobile navigation drawer       |
| `frontend/app/config/navigation.ts`                    | Role-to-nav-items config       |
| `frontend/composables/useAuth.ts`                      | Auth state & logout            |
| `frontend/composables/useApi.ts`                       | API client wrapper             |
| `frontend/composables/useNotification.ts`              | Toast wrapper                  |
| `frontend/composables/useBreadcrumb.ts`                | Breadcrumb management          |
| `frontend/composables/useDirection.ts`                 | RTL/LTR toggle                 |
| `frontend/stores/auth.ts`                              | Pinia auth store               |
| `frontend/stores/ui.ts`                                | Pinia UI state store           |
| `frontend/middleware/auth.ts`                          | Auth route guard               |
| `frontend/middleware/role.ts`                          | Role-based route guard         |
| `frontend/locales/ar.json`                             | Arabic translations            |
| `frontend/locales/en.json`                             | English translations           |
| `frontend/nuxt.config.ts`                              | Nuxt configuration             |

---

## Testing Strategy

### Unit Tests (Vitest)

| Test File                                        | What Is Tested                                                      |
| ------------------------------------------------ | ------------------------------------------------------------------- |
| `tests/unit/composables/useDirection.spec.ts`    | `toggle()` updates `document.dir`, persists to `localStorage`       |
| `tests/unit/composables/useBreadcrumb.spec.ts`   | `setBreadcrumb()` updates reactive items, auto-generates from route |
| `tests/unit/composables/useAuth.spec.ts`         | State transitions on login/logout, `isAuthenticated` computed       |
| `tests/unit/composables/useNotification.spec.ts` | `notifySuccess/Error/Warning/Info` call `useToast()` correctly      |
| `tests/unit/composables/useApi.spec.ts`          | 401 handler clears auth, attaches Bearer token                      |
| `tests/unit/stores/auth.spec.ts`                 | `setUser`, `clearAuth` mutations                                    |
| `tests/unit/stores/ui.spec.ts`                   | `toggleSidebar`, `toggleDrawer` state changes                       |
| `tests/unit/config/navigation.spec.ts`           | Each role returns correct nav items, no cross-role leakage          |

### E2E Tests (Playwright)

| Test Case                          | Scenario                                                                      |
| ---------------------------------- | ----------------------------------------------------------------------------- |
| Shell renders for Customer role    | Login as Customer → verify nav items: Dashboard, Projects, Orders, Payments   |
| Shell renders for Contractor role  | Login as Contractor → verify nav items: Dashboard, Projects, Earnings         |
| Shell renders for Admin role       | Login as Admin → verify all 7 nav items present                               |
| RTL direction toggle persists      | Click RTL toggle → `html[dir="rtl"]` → navigate → still `rtl`                 |
| LTR direction toggle               | Set `ar`, toggle to `en` → `html[dir="ltr"]` verified                         |
| Dark mode toggle                   | Click dark toggle → `html.dark` class applied → reload → persists             |
| Language switch AR → EN            | Click EN → page text updates to English                                       |
| Language switch EN → AR            | Click AR → page text updates to Arabic, direction is `rtl`                    |
| Mobile drawer opens/closes         | Viewport 375×812 → hamburger click → `UDrawer` visible → tap outside → closes |
| Mobile drawer RTL side             | Viewport 375×812, `dir="rtl"` → drawer slides from right edge                 |
| Navigation highlights active route | Navigate to `/projects` → nav item has `aria-current="page"` or active class  |
| Auth layout isolation              | Visit `/login` → no sidebar, no header nav links, only centered UCard visible |
| Unauthenticated redirect           | Visit `/admin` without token → redirected to `/login`                         |
| Wrong role redirect                | Login as Customer, visit `/admin` → redirected to `/customer`                 |
| Global error page 404              | Navigate to `/nonexistent` → UAlert with "الصفحة غير موجودة" visible          |
| Toast notification success         | Trigger success notification → toast appears, auto-dismisses after 4s         |
| Toast notification error           | Trigger error notification → toast has `color="error"` styling                |
| Loading bar on navigation          | Click nav link → `UProgress` visible at top of viewport                       |

---

## Definition of Done

- [ ] All three layouts (`default.vue`, `auth.vue`, `public.vue`) implemented and rendering correctly
- [ ] `error.vue` implemented with `UAlert` and recovery navigation
- [ ] All 5 composables (`useAuth`, `useApi`, `useNotification`, `useBreadcrumb`, `useDirection`) implemented
- [ ] Pinia stores (`auth.ts`, `ui.ts`) implemented
- [ ] Nuxt middleware (`auth.ts`, `role.ts`) implemented
- [ ] Role-based navigation correct for all 5 roles
- [ ] RTL/LTR toggle working and persisted
- [ ] Dark mode working and persisted
- [ ] Language switcher (AR/EN) working
- [ ] `nuxt.config.ts` configured for RTL default, i18n, and Nuxt UI
- [ ] Arabic and English locale files populated with all shell translation keys
- [ ] All unit tests pass (`npm run test` in `frontend/`)
- [ ] All E2E tests pass (`npx playwright test tests/e2e/shell.spec.ts`)
- [ ] No TypeScript errors (`npm run typecheck`)
- [ ] No ESLint errors (`npm run lint`)
- [ ] No hardcoded `left`/`right` CSS — only Tailwind logical properties used
- [ ] Mobile responsive (tested at 375px, 768px, 1280px)
- [ ] WCAG 2.1 AA color contrast verified in Lighthouse
- [ ] DESIGN.md visual language applied (Geist fonts, shadow-as-border, achromatic palette)

---

## Open Questions

None. The stage file and platform context provide sufficient detail for a complete specification.

---

## Clarifications

### Session 2026-04-12

**Q1:** How should the sidebar behave on tablet viewports (768px–1024px)? Should it collapse to icon-only mode, or be fully hidden like mobile?
**A1:** Collapsed to icon-only mode on tablet (768px–1024px). Expand on hover or click. Full sidebar on desktop (> 1024px), drawer on mobile (< 768px). `AppSidebar.vue` must implement three distinct states: `full` (desktop), `icon-only` (tablet), `hidden` (mobile, replaced by `MobileDrawer.vue`).

**Q2:** Should dark mode preference and direction (RTL/LTR) be stored in Pinia reactive state, localStorage, or both?
**A2:** Store in both — Pinia for reactive in-session state, `localStorage` for persistence across sessions. Implement a `usePreferences` composable (`frontend/composables/usePreferences.ts`) that reads from `localStorage` on init and writes back on change. `useDirection` and `useColorMode` should delegate persistence to `usePreferences`. Add `usePreferences.ts` to the File Delivery Map and scope.

**Q3:** Should `useApi` handle token refresh automatically on 401, or delegate entirely to `useAuth`?
**A3:** `useApi` should detect 401 responses, call `useAuth().refreshToken()`, and retry the original request once. If the refresh itself fails (another 401 or network error), clear auth state and redirect to `/auth/login`. `useApi` is the single retry point — no retry logic elsewhere. A `POST /api/auth/refresh` endpoint must be confirmed upstream and added to the API Integration Points table.

**Q4:** For role-based navigation, should each role see only their own items, or a superset with disabled/greyed-out items indicating unavailable features?
**A4:** Each role sees ONLY their own nav items. No disabled, greyed-out, or hidden-but-present items. Nav items are strictly filtered by role from the central `NAV_ITEMS_BY_ROLE` config. This prevents information leakage about routes accessible to other roles.

**Q5:** Should `useBreadcrumb` auto-generate breadcrumb items from route metadata, or require manual definition on every page?
**A5:** Auto-generate from `route.meta.breadcrumb` array if present on the current route. Fall back to splitting `route.name` or `route.path` into segments when `route.meta.breadcrumb` is absent. Pages with complex or non-standard hierarchies can call `setBreadcrumb(items)` imperatively to override auto-generation entirely.

---

## Security & Architecture Audit (2026-04-12)

### 🔴 FLAG 1 — Token Handling Contradiction

**Location:** `useApi` composable spec + Security NFR
**Issue:** The spec states `useApi` attaches `Authorization: Bearer {token}` from `useAuthStore`, but the Security NFR states "No authentication token stored in `localStorage` — use `httpOnly` cookie or Pinia in-memory only." The platform rules also state "Laravel Sanctum tokens via cookie-based auth." With cookie-based Sanctum, the browser sends the auth cookie automatically — `useApi` must NOT manually attach a Bearer header. Instead, `useApi` must set `credentials: 'include'` on all `$fetch` calls and omit the `Authorization` header entirely. Manually reading a token from Pinia and attaching it as a header would expose the token to JavaScript and defeat the httpOnly cookie security model.
**Resolution Required:** Replace Bearer header logic in `useApi` with `credentials: 'include'`. Remove `token` from `useAuthStore` public surface. Confirm with backend that Sanctum is configured for SPA cookie auth (`SANCTUM_STATEFUL_DOMAINS`).

### 🔴 FLAG 2 — Missing Refresh Token Endpoint in API Table

**Location:** API Integration Points table
**Issue:** Clarification A3 requires `useApi` to call `useAuth().refreshToken()` on 401, which implies a `POST /api/auth/refresh` endpoint. This endpoint is not listed in the API Integration Points table and may not exist in the upstream backend stage.
**Resolution Required:** Add `POST /api/auth/refresh` to the API Integration Points table. Confirm the backend stage that implements this endpoint is listed as an upstream dependency. If the endpoint does not exist, strip refresh logic from `useApi` and use session token invalidation only.

### 🟡 FLAG 3 — `role.ts` Middleware Logic Unspecified

**Location:** Middleware section
**Issue:** `frontend/middleware/role.ts` is listed in the file delivery map but no implementation sketch or `route.meta` convention is defined for it (unlike `auth.ts` which has a code snippet). It is unclear how routes declare their required role (e.g., `route.meta.requiredRole: 'admin'`), what the redirect target is for each role, and whether role checking is flat (single role) or bitmasked (multiple roles allowed).
**Resolution Required:** Add a code sketch for `role.ts` mirroring the `auth.ts` snippet. Define the `route.meta.requiredRole` convention and the redirect-to-own-dashboard logic per role.

### 🟡 FLAG 4 — `usePreferences` Composable Missing from File Delivery Map

**Location:** File Delivery Map + Scope
**Issue:** Clarification A2 introduces a `usePreferences` composable responsible for syncing direction and dark mode to localStorage. This file (`frontend/composables/usePreferences.ts`) is not listed in the File Delivery Map or In Scope section.
**Resolution Required:** Add `frontend/composables/usePreferences.ts` to File Delivery Map and In Scope. Add a corresponding unit test entry in the Testing Strategy table.

### 🟡 FLAG 5 — SSR Hydration Flash Risk for Direction and Dark Mode

**Location:** NFR Performance + RTL/Arabic Support
**Issue:** The `nuxt.config.ts` snippet sets `htmlAttrs: { dir: 'rtl', lang: 'ar' }` as a static default, which prevents the SSR flash for the default locale. However, if a user has stored `ltr` in `localStorage`, there will be a hydration mismatch (server renders `rtl`, client switches to `ltr` on mount). This causes a CLS violation counter to the < 0.1 target and a potential FOUC.
**Resolution Required:** Use a Nuxt plugin (`plugins/direction.client.ts`) to read `localStorage.getItem('bunyan_direction')` synchronously before Vue mounts, and call `document.documentElement.setAttribute('dir', ...)` before the first paint. This is a client-only plugin — it does not run on the server, avoiding SSR mismatch.

### 🟢 INFO — XSS Risk Assessment: Low

No `v-html` directives are used in the spec. Toast messages are routed through i18n keys. Error messages are static strings from the platform error code registry. No unescaped user-generated content rendered in the shell. XSS vector is low for this stage.

### 🟢 INFO — Error Contract Compliance: Partial

`useApi` returns `{ success, data, error }` envelope as required by the platform error contract. However, the spec does not define what `useNotification` does when receiving a structured `error.details` object (field-level errors). Since the shell has no forms, this is not critical for this stage, but downstream stages should note that `useNotification` currently only accepts a plain string `msg`.
