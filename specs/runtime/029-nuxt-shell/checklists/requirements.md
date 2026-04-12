# Requirements Checklist — STAGE_29 Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Stage:** STAGE_29_NUXT_SHELL
> **Runtime Dir:** `specs/runtime/029-nuxt-shell/` > **Created:** 2026-04-12T00:00:00Z

---

## Architecture Compliance

- [ ] `default.vue`, `auth.vue`, `public.vue` layouts exist under `frontend/layouts/`
- [ ] `error.vue` global error boundary exists under `frontend/`
- [ ] No business logic in layout or component files — all logic delegated to composables/stores
- [ ] All API calls made through `useApi` composable — no raw `$fetch` in components
- [ ] Pinia stores used for all shared state (`auth.ts`, `ui.ts`)
- [ ] Navigation config lives in `frontend/app/config/navigation.ts` (data, not logic)
- [ ] Nuxt middleware (`auth.ts`, `role.ts`) used for route protection — not client-side `v-if` guards

## RBAC Enforcement

- [ ] `frontend/middleware/auth.ts` redirects unauthenticated users to `/login`
- [ ] `frontend/middleware/role.ts` redirects users lacking sufficient role
- [ ] Customer navigation contains only: Dashboard, Projects, Orders, Payments
- [ ] Contractor navigation contains only: Dashboard, Projects, Earnings, Withdrawals
- [ ] Supervising Architect navigation contains only: Dashboard, Projects, Field Engineers, Reports
- [ ] Field Engineer navigation contains only: Dashboard, My Projects, Submit Report
- [ ] Admin navigation contains all 7 items: Dashboard, Users, Projects, Products, Orders, Configuration, Reports
- [ ] No cross-role nav item leakage (unit test must assert this for each role)
- [ ] Auth layout has zero navigation links (no header nav, no sidebar)
- [ ] Unauthenticated visit to any `/admin/*` route redirects to `/login`
- [ ] Customer visiting `/admin` is redirected to `/customer`

## RTL / Arabic Support

- [ ] `nuxt.config.ts` sets `htmlAttrs: { dir: 'rtl', lang: 'ar' }` as default
- [ ] `useDirection` composable reads `localStorage` key `bunyan_direction` on init
- [ ] `useDirection.toggle()` updates `document.documentElement.dir`
- [ ] Direction preference persists across page reloads
- [ ] Direction preference persists across Nuxt navigation
- [ ] Language switch to `ar` automatically sets `dir="rtl"`
- [ ] Language switch to `en` automatically sets `dir="ltr"`
- [ ] All layout components use Tailwind logical properties (`ps-`, `pe-`, `ms-`, `me-`, `rounded-s-`, `rounded-e-`)
- [ ] No hardcoded `left` or `right` in layout CSS
- [ ] `UNavigationTree` sidebar appears on the correct side for RTL (right side in Arabic)
- [ ] `UDrawer` slides from the correct edge based on `dir` attribute
- [ ] `UBreadcrumb` separator chevron faces the correct direction in RTL

## Nuxt UI Component Usage

- [ ] Header uses `UHeader` + `UNavigationMenu`
- [ ] Desktop sidebar uses `UNavigationTree`
- [ ] Mobile navigation uses `UDrawer` containing `UNavigationTree`
- [ ] Collapsible/sliding panel uses `USlideover`
- [ ] Breadcrumb uses `UBreadcrumb`
- [ ] Footer uses `UFooter`
- [ ] Toast notifications use `useToast()` from Nuxt UI (via `useNotification` wrapper)
- [ ] Global loading bar uses `UProgress`
- [ ] Skeleton placeholders use `USkeleton`
- [ ] Error boundary uses `UAlert` with `color="error"`
- [ ] User avatar uses `UAvatar`
- [ ] User dropdown menu uses `UDropdownMenu`
- [ ] Auth layout card uses `UCard`
- [ ] No third-party UI components used — Nuxt UI only

## Composable Completeness

- [ ] `useAuth` — exposes `user`, `isAuthenticated`, `role`, `permissions`, `logout()`
- [ ] `useAuth.logout()` calls `DELETE /api/auth/logout`, clears Pinia store, navigates to `/login`
- [ ] `useApi` — attaches `Authorization: Bearer` header from auth store
- [ ] `useApi` — handles 401 by clearing auth state and redirecting to `/login`
- [ ] `useApi` — returns standardized `{ success, data, error }` envelope
- [ ] `useNotification` — exposes `notifySuccess`, `notifyError`, `notifyWarning`, `notifyInfo`
- [ ] `useBreadcrumb` — `setBreadcrumb(items)` updates reactive items
- [ ] `useBreadcrumb` — auto-generates from `useRoute().meta.breadcrumb` if present
- [ ] `useDirection` — `toggle()` works correctly, persists to localStorage

## i18n & Translations

- [ ] `@nuxtjs/i18n` module configured in `nuxt.config.ts`
- [ ] Arabic locale (`ar`) configured with `dir: 'rtl'`
- [ ] English locale (`en`) configured with `dir: 'ltr'`
- [ ] Default locale is `ar` (Arabic-first)
- [ ] `locales/ar.json` contains all `nav.*` and `common.*` keys
- [ ] `locales/en.json` contains all `nav.*` and `common.*` keys
- [ ] No hardcoded Arabic or English strings in component templates — all via `$t()` or `useI18n().t()`
- [ ] Language switcher visible in header, toggles between AR and EN
- [ ] Browser language detection configured via `detectBrowserLanguage`

## Dark Mode

- [ ] `useColorMode()` used for dark mode toggling
- [ ] Dark mode preference persisted across sessions
- [ ] `html.dark` class applied when dark mode is active
- [ ] All Nuxt UI components respond correctly to dark mode
- [ ] `AppConfig` ui theme colors set for both modes

## API Integration

- [ ] `GET /api/user` called on app boot to hydrate `useAuthStore`
- [ ] `DELETE /api/auth/logout` called on logout
- [ ] `runtimeConfig.public.apiBase` used as base URL (not hardcoded)
- [ ] Auth token not stored in `localStorage` (security requirement)

## Unit Tests (Vitest)

- [ ] `useDirection` — `toggle()` updates `document.dir` attribute
- [ ] `useDirection` — persists to `localStorage` on toggle
- [ ] `useDirection` — reads from `localStorage` on init
- [ ] `useBreadcrumb` — `setBreadcrumb()` updates reactive ref
- [ ] `useBreadcrumb` — auto-generates items from `route.meta.breadcrumb`
- [ ] `useAuth` — `isAuthenticated` is `true` when user is set
- [ ] `useAuth` — `isAuthenticated` is `false` after `clearAuth()`
- [ ] `useNotification` — each notify method calls `useToast()` with correct props
- [ ] `useApi` — attaches `Authorization` header when token is present
- [ ] `useApi` — clears auth and redirects on 401 response
- [ ] `navigation.ts` — Customer role returns exactly 4 items
- [ ] `navigation.ts` — Admin role returns exactly 7 items
- [ ] `navigation.ts` — No Admin items appear in Customer nav
- [ ] Pinia `useAuthStore` — `setUser()` sets user and `isAuthenticated`
- [ ] Pinia `useUiStore` — `toggleSidebar()` toggles `isSidebarOpen`

## E2E Tests (Playwright)

- [ ] Customer login → sidebar shows Dashboard, Projects, Orders, Payments only
- [ ] Contractor login → sidebar shows Dashboard, Projects, Earnings, Withdrawals only
- [ ] Admin login → sidebar shows all 7 items
- [ ] RTL toggle → `html[dir="rtl"]` verified
- [ ] RTL toggle persists across navigation to another page
- [ ] Dark mode toggle → `html.dark` class applied
- [ ] Dark mode persists after page reload
- [ ] Language AR → EN → page text updates
- [ ] Language EN → AR → `html[dir="rtl"]` verified
- [ ] Mobile (375px) → hamburger icon visible, sidebar hidden
- [ ] Mobile (375px) → hamburger click → drawer opens
- [ ] Mobile (375px) → tap outside drawer → drawer closes
- [ ] RTL mobile → drawer slides from right edge
- [ ] `/login` page → no sidebar visible, no header nav links
- [ ] Unauthenticated `/admin` access → redirect to `/login`
- [ ] Customer access to `/admin` → redirect to `/customer`
- [ ] `/nonexistent` → UAlert with error message visible
- [ ] Active route highlighted in sidebar
- [ ] Success toast auto-dismisses after ~4 seconds
- [ ] Error toast has distinct `color="error"` styling

## Performance

- [ ] Initial shell hydration < 300ms (Lighthouse target on 4G)
- [ ] CLS (Cumulative Layout Shift) < 0.1 during hydration
- [ ] Geist font files are preloaded (per DESIGN.md)
- [ ] `UProgress` appears within 50ms of navigation start
- [ ] No unused CSS from Nuxt UI in production bundle

## Accessibility

- [ ] All `UDrawer`, `UDropdownMenu`, `USlideover` close on `Escape` key
- [ ] Focus is trapped inside `UDrawer` when open
- [ ] `UNavigationTree` items have `aria-current="page"` on active route
- [ ] `UHeader` has `role="banner"`
- [ ] `UFooter` has `role="contentinfo"`
- [ ] Color contrast WCAG 2.1 AA verified in Lighthouse (light mode)
- [ ] Color contrast WCAG 2.1 AA verified in Lighthouse (dark mode)
- [ ] Language switch button has `aria-label` in current language

## Definition of Done Gate

- [ ] All unit tests pass: `npm run test` (within `frontend/`)
- [ ] All E2E tests pass: `npx playwright test tests/e2e/shell.spec.ts`
- [ ] No TypeScript errors: `npm run typecheck`
- [ ] No ESLint errors: `npm run lint`
- [ ] DESIGN.md visual language applied (Geist fonts, shadow-as-border, achromatic palette, negative letter-spacing)
- [ ] Stage status updated to `COMPLETE` in `STAGE_29_NUXT_SHELL.md`
