# Performance Checklist — NUXT_SHELL (Stage 029)

> **Purpose:** Unit tests for performance requirements quality. Each item evaluates whether the spec _defines_ measurable, achievable performance targets and constraints — not whether the code is fast.
>
> **Domain:** Nuxt.js 3 + Nuxt UI + Vue 3 TypeScript + RTL Arabic-first
> **Stage:** STAGE_29_NUXT_SHELL
> **Created:** 2026-04-12

---

## PERF001 — Initial Shell Render < 300ms: Measurement Method Defined

**Category:** Load Performance / NFR Completeness

Is the "initial shell render < 300ms on 4G" NFR accompanied by a defined measurement method and tooling?

- "< 300ms" is an ambiguous target without specifying whether this is Time-to-First-Byte (TTFB), First Contentful Paint (FCP), Largest Contentful Paint (LCP), or Time-to-Interactive (TTI).
- **Requirement quality check:** Does the NFR specify which performance metric is being targeted (FCP or TTI), which tooling measures it (Lighthouse, WebPageTest, browser DevTools throttled to "Fast 4G"), and what the baseline conditions are (cold load, warm cache, SSR vs. CSR)?

---

## PERF002 — CLS < 0.1: Hydration Flash Mitigation Strategy Defined

**Category:** Layout Stability / Hydration

Is the CLS < 0.1 target tied to a concrete mitigation strategy for the known SSR hydration flash risk identified in Security Audit Flag 5?

- The spec identifies that `localStorage`-stored direction preferences will cause a server-rendered `dir="rtl"` page to flash to `dir="ltr"` on hydration, causing a CLS violation.
- **Requirement quality check:** Is `plugins/direction.client.ts` specified as the _required_ mitigation for this CLS risk? Is the plugin's execution timing (before Vue mounts, after DOM is available) explicitly constrained?

---

## PERF003 — `direction.client.ts` Plugin: Pre-Mount Execution Constraint

**Category:** Layout Stability / Plugin Ordering

Does the spec constrain `plugins/direction.client.ts` to execute synchronously before Vue mounts (i.e., before `<NuxtPage />` renders) to prevent direction flash?

- A client plugin that runs asynchronously or after mount would still cause a visible direction flip.
- **Requirement quality check:** Is it specified that the plugin uses `document.documentElement.setAttribute('dir', ...)` in a synchronous manner and is NOT `async`? Is the Nuxt plugin `mode: 'client'` execution order relative to app mount documented?

---

## PERF004 — Font Preloading: Geist Arabic Defined in `nuxt.config.ts`

**Category:** Font Loading / FCP

Is the preloading strategy for Geist Arabic (and Geist Mono if used) defined in `nuxt.config.ts` using `<link rel="preload">` or `fonts` module configuration?

- Without preloading, Geist Arabic will load after the CSS parse, causing a Flash of Unstyled Text (FOUT) that contributes to CLS and degrades FCP.
- **Requirement quality check:** Does the `nuxt.config.ts` spec include a `head.link` entry with `rel: 'preload'`, `as: 'font'`, `type: 'font/woff2'`, and `crossorigin: 'anonymous'` for the Geist Arabic font file? Or does it reference a `@nuxt/fonts` module configuration?

---

## PERF005 — Font Preloading: Font File Path and Format Specified

**Category:** Font Loading / Asset Clarity

Is the exact font file path, format (woff2), and subset for Geist Arabic documented in the spec or in `DESIGN.md` reference?

- Preloading the wrong format (e.g., woff instead of woff2) negates the benefit; preloading fonts not actually used causes unnecessary bandwidth.
- **Requirement quality check:** Is there a reference to the specific Geist Arabic font variant(s) used (Regular, Bold, etc.) and where they are served from (CDN URL, local `public/fonts/`, or npm package)?

---

## PERF006 — `UProgress` Starts Within 50ms: Navigation Hook Defined

**Category:** Perceived Performance / Navigation Feedback

Is the requirement that `UProgress` starts within 50ms of navigation trigger tied to a specific Nuxt router hook implementation?

- `UProgress` must be controlled via `useRouter().beforeEach` or Nuxt's `useNuxtApp().hook('page:start')`. Without specifying the hook, implementations may defer the progress bar start to `page:loading:start` which fires later.
- **Requirement quality check:** Does the spec (or `default.vue` layout spec) define which Nuxt lifecycle hook triggers `UProgress` start, and confirm it fires before the route component begins resolving?

---

## PERF007 — `UProgress` Completes on Navigation Finish: Defined

**Category:** Perceived Performance / Navigation Feedback

Is the condition under which `UProgress` reaches 100% and hides defined (e.g., `page:finish` hook, on navigation error, on component mount)?

- A progress bar that never completes (on navigation error or slow page) creates a broken UX.
- **Requirement quality check:** Does the spec define the `UProgress` lifecycle for both successful navigation and navigation error cases?

---

## PERF008 — Mobile Sidebar Lazy Load: `UDrawer` JS Bundle Deferred

**Category:** Bundle Size / Mobile Performance

Is there a spec constraint that `MobileDrawer.vue` (wrapping `UDrawer`) is only loaded (JS parsed and executed) when the user is on a mobile viewport or explicitly opens the drawer?

- Loading `UDrawer` JS unconditionally on desktop wastes parse time. Nuxt's `<LazyMobileDrawer>` or conditional `defineAsyncComponent` should be specified.
- **Requirement quality check:** Does the `MobileDrawer.vue` delivery spec mention lazy loading via `<Lazy*>` auto-import or `defineAsyncComponent`? Is the breakpoint condition (< 768px) tied to the lazy load trigger?

---

## PERF009 — Sidebar Responsive States: No Redundant DOM for Hidden States

**Category:** Rendering / DOM Size

Does the spec for `AppSidebar.vue` define that the full sidebar DOM is NOT rendered on mobile (< 768px) — where the `MobileDrawer.vue` is used instead?

- Rendering both the full sidebar and the mobile drawer simultaneously in the DOM (with one hidden via CSS) wastes memory and increases style recalculation time.
- **Requirement quality check:** Is it specified that `AppSidebar.vue` uses `v-if` (not `v-show`) to conditionally render the sidebar vs. deferring to `MobileDrawer.vue` for mobile, so the full sidebar tree is NOT in the DOM on mobile?

---

## PERF010 — Pinia Stores Initialized Lazily

**Category:** Bundle Size / Store Initialization

Does the spec define that Pinia stores (`useAuthStore`, `useUiStore`) are instantiated only when first accessed — not eagerly initialized in a plugin that always runs?

- Eagerly initializing all Pinia stores in a plugin (e.g., `nuxt.config.ts` plugins array) forces them into the critical path JS bundle.
- **Requirement quality check:** Does the spec state that composables call `useAuthStore()` / `useUiStore()` on demand, and that no Nuxt plugin eagerly calls all stores on every page load?

---

## PERF011 — Auth State Bootstrap: Single API Call Constraint

**Category:** Load Performance / API Efficiency

Is the auth state initialization (`GET /api/v1/auth/me` or `GET /api/v1/user`) specified to occur at most once per session (not once per route navigation)?

- If `useAuth` calls `GET /api/v1/auth/me` on every page navigation, it blocks initial routing with an API call on every page.
- **Requirement quality check:** Is it specified that the auth user fetch is called once (e.g., in a Nuxt plugin `nuxtApp.hook('app:created')`) and stored in Pinia, with subsequent navigations reading only from Pinia state?

---

## PERF012 — Navigation Transitions < 100ms: Source Defined

**Category:** Route Transition Performance

Is the "navigation transitions < 100ms" NFR applied to perceived transition completion or to Time-to-Navigation-Complete (all async components resolved)?

- Sub-100ms for full async component resolution is unrealistic if business-domain pages have their own data fetching. This target likely applies to the shell transition (layout swap, `UProgress` start/stop) only.
- **Requirement quality check:** Does the spec clarify that this 100ms target applies to the shell navigation animation (layout transition, page-exit/enter CSS) and NOT to page data fetching time?

---

## PERF013 — Skeleton Screens: No CLS During Content Load

**Category:** Layout Stability / Skeleton

Does the spec for `USkeleton` placeholders require that skeletons match the final content dimensions to prevent layout shift when content replaces them?

- Skeletons that render at a fixed height different from the actual content will cause CLS when content loads.
- **Requirement quality check:** Is it specified that `USkeleton` elements must use estimated heights/widths that match the rendered content dimensions to maintain CLS < 0.1 through the skeleton-to-content transition?

---

## PERF014 — Toast Notifications: No Impact on Layout (Position Absolute/Fixed)

**Category:** Layout Stability / Toast Positioning

Does the spec ensure toast notifications are rendered in a fixed/absolute-positioned overlay so they do not cause layout shift in the main content area?

- Toasts rendered in the document flow (not fixed/absolute) push content down, causing CLS.
- **Requirement quality check:** Does the spec explicitly state that `useToast()` renders in a portal or overlay outside the document flow? Is placement defined as "top-end corner" (RTL-aware: top-left in RTL, top-right in LTR)?

---

## PERF015 — `nuxt.config.ts`: SSR Enabled (Not a Pure SPA)

**Category:** Load Performance / SSR

Does the spec confirm that Nuxt is configured for SSR (not `ssr: false`) to enable server-rendered HTML for the initial shell load, which is required to meet the < 300ms FCP target?

- A pure SPA (Nuxt with `ssr: false`) cannot achieve < 300ms FCP on 4G because it requires JS execution before any content renders.
- **Requirement quality check:** Is `ssr: true` (or the absence of `ssr: false`) stated in the `nuxt.config.ts` spec? Is the SSR/hydration deployment mode documented?

---

## PERF016 — Three Sidebar States: No Redundant CSS Recalculations

**Category:** Rendering / Style Performance

For the three sidebar states (`full` at > 1024px, `icon-only` at 768px–1024px, `hidden` at < 768px per Clarification A1), does the spec define that state transitions use CSS transitions with `will-change: width` or `transform` rather than altering the DOM structure on resize?

- Removing/adding DOM nodes on viewport resize events triggers layout and paint. CSS-only transitions on fixed nodes are more performant.
- **Requirement quality check:** Does the `AppSidebar.vue` spec or Clarification A1 answer define whether the three states are implemented via CSS transitions (preferred) or DOM conditional rendering (`v-if`/`v-show`)?

---

_Total items: 16_
