# Accessibility Checklist — NUXT_SHELL (Stage 029)

> **Purpose:** Unit tests for accessibility requirements quality. Each item evaluates whether the spec _defines_ the accessibility behavior completely and testably — not whether the implementation is accessible.
>
> **Domain:** Nuxt.js 3 + Nuxt UI + RTL Arabic-first + WCAG 2.1 AA
> **Stage:** STAGE_29_NUXT_SHELL
> **Created:** 2026-04-12

---

## A11Y001 — Interactive Elements: `aria-label` in Arabic as Default Language

**Category:** Labels / Screen Reader Support

Does the spec require that all interactive shell elements (hamburger button, direction toggle, dark mode toggle, language switcher, avatar dropdown trigger, breadcrumb links, close buttons) carry an `aria-label` in Arabic when the active locale is Arabic?

- The platform is Arabic-first. Screen readers will read in Arabic by default. An `aria-label="Toggle dark mode"` in English on an Arabic locale page breaks the screen reader experience.
- **Requirement quality check:** Does the spec state that `aria-label` values must use i18n keys (not hardcoded English strings)? Is there a list of interactive shell elements requiring labels with their corresponding i18n keys?

---

## A11Y002 — `aria-label` for Icon-Only Controls

**Category:** Labels / Icon Buttons

For all icon-only interactive elements in the shell (hamburger `☰`, direction toggle, dark mode toggle, language switcher), does the spec require a visible or programmatic accessible name that is not derived solely from an icon image?

- Nuxt UI `UButton` with only an icon slot renders no text. Without `aria-label` or `aria-labelledby`, screen readers announce the button without a meaningful name.
- **Requirement quality check:** Does `AppHeader.vue` spec enumerate all icon-only buttons and their required `aria-label` values or i18n key mappings?

---

## A11Y003 — Keyboard Navigation: Tab Order Defined for Shell Elements

**Category:** Keyboard / Focus Management

Does the spec define the expected Tab order through the shell's interactive elements (header controls → breadcrumb → page content → footer)?

- Without a defined Tab order, implementations may produce illogical focus sequences (e.g., sidebar items receiving focus before header controls).
- **Requirement quality check:** Is a Tab order sequence defined or implied in the layout diagram and component specs? Does the spec prohibit `tabindex` values greater than 0 (which break natural Tab order)?

---

## A11Y004 — Keyboard Navigation: Enter and Space Activate Buttons

**Category:** Keyboard / Activation

Does the spec require that all interactive shell elements (direction toggle, dark mode toggle, language switcher, hamburger) respond to both `Enter` and `Space` keypresses for activation?

- Nuxt UI components generally handle this, but custom wrapper components (`AppHeader.vue`, `MobileDrawer.vue`) must enforce it.
- **Requirement quality check:** Is keyboard activation behavior mentioned in the Acceptance Criteria for US9 (direction toggle), US10 (dark mode), US11 (language switcher), and US13 (mobile drawer)?

---

## A11Y005 — Keyboard Navigation: Escape Closes Overlays

**Category:** Keyboard / Overlay Dismissal

Does the spec explicitly require that `Escape` closes `UDropdownMenu` (user avatar menu), `UDrawer` (mobile navigation), and `USlideover` (collapsible sidebar)?

- The NFR Accessibility section mentions "UDropdownMenu closes on Escape" but does not mention `UDrawer`. Escape dismissal is a WCAG 2.1 SC 1.4.13 requirement for non-modal overlays.
- **Requirement quality check:** Are ALL dismissible overlays in the shell listed with their Escape-close requirement? Is `UDrawer` included alongside `UDropdownMenu`?

---

## A11Y006 — `UDrawer` Focus Trap: Specification Completeness

**Category:** Focus Management / Modal Drawer

Does the spec define that `UDrawer` (mobile navigation) traps keyboard focus while open — preventing Tab from cycling to the background page content?

- The NFR lists "`UDrawer` traps focus when open." This must also specify: (1) which element receives initial focus when the drawer opens, (2) that Shift+Tab wraps within the drawer, and (3) that focus returns to the hamburger trigger button when the drawer closes.
- **Requirement quality check:** Does the accessibility NFR or `MobileDrawer.vue` spec define all three aspects of the focus trap: entry focus target, Tab cycle boundary, and focus return target on close?

---

## A11Y007 — `UDropdownMenu`: Focus Returns to Trigger on Close

**Category:** Focus Management / Dropdown

Does the spec require that focus returns to the `UAvatar`/dropdown trigger button after `UDropdownMenu` is dismissed (via Escape, item selection, or outside click)?

- Focus being lost after closing a dropdown (returned to `document.body`) is a WCAG 2.4.3 failure.
- **Requirement quality check:** Is focus-return-on-close specified in the `AppHeader.vue` dropdown behavior spec or referenced Nuxt UI component behavior?

---

## A11Y008 — Route Change Announcements via `aria-live`

**Category:** Screen Reader / Navigation Announcements

Does the spec define a mechanism by which route changes are announced to screen readers — specifically an `aria-live` region that receives an updated page title or navigation confirmation message on each route transition?

- SPA route changes are silent to screen readers unless programmatically announced. The NFR states "Screen reader announces route changes via `aria-live` region" but does not specify: where the region is placed, what content it announces, or which Nuxt router hook populates it.
- **Requirement quality check:** Is the `aria-live` announcement region specified with (1) container element location in `default.vue`, (2) `aria-live="polite"` or `aria-atomic="true"` attribute, (3) content to announce (page title, route name, or breadcrumb), and (4) the Nuxt hook that triggers the update?

---

## A11Y009 — Active Navigation Item: `aria-current="page"` Required

**Category:** Navigation / Current Page Indicator

Does the spec require that the active `UNavigationTree` item (and active item inside `UDrawer`) carries `aria-current="page"` for screen reader users — not just a visual active class?

- The E2E test mentions `aria-current="page"` as the assertion, but is this defined as a spec requirement for the `AppNavigation.vue` component?
- **Requirement quality check:** Is `aria-current="page"` explicitly required in the navigation component spec or acceptance criteria for US1 (active route highlighting)?

---

## A11Y010 — Color Contrast WCAG 2.1 AA: Light Mode

**Category:** Color / Contrast

Does the spec define the minimum contrast requirements for text and interactive elements in light mode, and reference the specific color palette from `DESIGN.md`?

- "Color contrast meets WCAG 2.1 AA" is stated in the NFR but gives no design token references. Without token-level contrast definitions, implementations may use colors that technically pass WCAG at 14px but fail at smaller sizes.
- **Requirement quality check:** Does the spec reference specific DESIGN.md or `AppConfig` color tokens for foreground/background combinations that have been pre-validated for WCAG AA (4.5:1 for normal text, 3:1 for large text and UI components)?

---

## A11Y011 — Color Contrast WCAG 2.1 AA: Dark Mode

**Category:** Color / Contrast (Dark Mode)

Does the spec separately validate contrast requirements for dark mode — where Nuxt UI's default dark colors may differ from light mode in ways that affect WCAG compliance?

- Dark mode contrast is a separate concern from light mode. A color pair that passes AA in light mode may fail in dark mode if dark theme tokens are not WCAG-audited.
- **Requirement quality check:** Does the NFR or Definition of Done include a specific dark mode contrast verification step (e.g., "WCAG 2.1 AA color contrast verified in Lighthouse for both light and dark modes")?

---

## A11Y012 — RTL Logical Properties: Prohibition on Physical `left`/`right`

**Category:** RTL / Layout

Does the spec explicitly prohibit the use of physical Tailwind classes (`pl-`, `pr-`, `ml-`, `mr-`, `rounded-l-`, `rounded-r-`) and CSS properties (`padding-left`, `margin-right`, `left`, `right`) in layout components?

- The NFR states "No hardcoded `left`/`right` CSS values in layout components." This needs to be a lint-enforceable rule, not just a guideline.
- **Requirement quality check:** Is there a spec reference to a Tailwind plugin, ESLint rule, or stylelint rule that enforces logical properties at the tooling level? Or is this purely a code review guideline?

---

## A11Y013 — RTL Logical Properties: `UNavigationTree` Position

**Category:** RTL / Sidebar Placement

Does the spec confirm that `UNavigationTree` (desktop sidebar) is positioned on the RIGHT side of the viewport in RTL mode — not left — and that this is achieved via logical layout properties rather than a conditional `right:0` CSS swap?

- The layout diagram shows "[RTL: right]" for the sidebar. This must be implemented with Tailwind's `start`/`end` logical properties (e.g., `inset-s-0`) rather than toggling `right`/`left` based on `dir`.
- **Requirement quality check:** Does the `AppSidebar.vue` spec define the CSS approach for RTL positioning — logical properties or Tailwind `rtl:` variant?

---

## A11Y014 — `UDrawer` RTL Slide Direction: Correct Edge Specified

**Category:** RTL / Drawer Behavior

Does the spec confirm that `UDrawer` on mobile slides from the RIGHT edge of the viewport in RTL mode, and that this is a Nuxt UI native behavior (not a custom implementation)?

- The E2E test "Mobile drawer RTL side" asserts drawer slides from right in RTL. The spec must define whether this is a built-in Nuxt UI `UDrawer` behavior (activated by `dir="rtl"` on root) or requires a custom prop.
- **Requirement quality check:** Does the `MobileDrawer.vue` spec or Nuxt UI reference confirm that `UDrawer` respects the document `dir` attribute for slide direction? If not, is a workaround defined?

---

## A11Y015 — Focus Visible Indicators: Dark Mode Specification

**Category:** Focus / Visibility

Does the spec define visible focus ring styles for dark mode — where default browser focus outlines (typically dark blue) may be invisible against dark backgrounds?

- The NFR mentions "Focus visible indicators in dark mode" but does not specify the required color, width, or offset for focus rings in dark mode.
- **Requirement quality check:** Is there a DESIGN.md or `AppConfig` reference that defines focus ring color tokens for dark mode? Does the spec require that focus rings meet 3:1 contrast ratio against their adjacent background in dark mode (WCAG 2.4.11)?

---

## A11Y016 — `error.vue` Accessibility: Error Message as Heading

**Category:** Screen Reader / Error Page

Does the spec require that the error message in `error.vue` is rendered as a proper heading (`<h1>` or `role="heading"`) so screen readers announce it as the page landmark when navigating to the error page?

- `UAlert` renders as a `<div role="alert">` which is an ARIA live region for dynamic announcements. For a standalone error page (not an in-page alert), the main error heading should also be an `<h1>` for screen reader landmark navigation.
- **Requirement quality check:** Does `error.vue` spec define both the `UAlert` for the error message AND an `<h1>` heading or `role="heading"` element for the page title?

---

## A11Y017 — Breadcrumb: `aria-label="breadcrumb"` on Nav Landmark

**Category:** Navigation / Landmark

Does the spec require that the `<nav>` element wrapping `AppBreadcrumb.vue` / `UBreadcrumb` carries `aria-label="breadcrumb"` (in Arabic: `aria-label="مسار التنقل"`) to distinguish it from the primary navigation landmark?

- A page with two `<nav>` elements (sidebar + breadcrumb) without distinct `aria-label` values causes screen readers to announce confusing "navigation, navigation" landmarks.
- **Requirement quality check:** Is `aria-label` with the Arabic translation key for "breadcrumb" specified on the `AppBreadcrumb.vue` wrapper element?

---

## A11Y018 — Language Switcher: `lang` Attribute on `<html>` Updates Synchronously

**Category:** Screen Reader / Language

Does the spec require that switching language updates `html[lang]` synchronously (e.g., `ar` → `en`) so screen readers immediately switch their language engine for subsequent content reads?

- `@nuxtjs/i18n` updates `lang` on route navigation, but if the language switch is done without a route change (in-place toggle), the `lang` attribute may not update until the next navigation.
- **Requirement quality check:** Does the US11 acceptance criteria explicitly state that `html[lang]` and `html[dir]` update immediately on language switch — not deferred to the next route navigation?

---

## A11Y019 — Sidebar Icon-Only State: Tooltips for Collapsed Icons

**Category:** Labels / Tablet Sidebar

Per Clarification A1, the sidebar collapses to icon-only mode on tablet (768px–1024px). Does the spec require that icon-only nav items in this state provide a tooltip or `aria-label` so keyboard and screen reader users can identify the navigation destination?

- When nav item labels are hidden, sighted users can hover for a tooltip, but screen reader users and keyboard-only users need an accessible name on the item.
- **Requirement quality check:** Does the `AppSidebar.vue` spec for the icon-only state define how accessible names are provided (e.g., `title` attribute, `aria-label`, or a Nuxt UI tooltip component)?

---

## A11Y020 — Skip Navigation Link: Defined in `default.vue`

**Category:** Keyboard / Navigation Efficiency

Does the spec include a "Skip to main content" link (in Arabic: "تخطى إلى المحتوى الرئيسي") as the first focusable element in `default.vue` to allow keyboard users to bypass the navigation sidebar and header?

- Without a skip link, keyboard-only users must Tab through all header controls and sidebar navigation items on every page load to reach the page content.
- **Requirement quality check:** Is a skip navigation link defined in the `default.vue` layout spec or its accessibility NFR? Is it specified as visually hidden but focusable (not `display:none`)?

---

_Total items: 20_
