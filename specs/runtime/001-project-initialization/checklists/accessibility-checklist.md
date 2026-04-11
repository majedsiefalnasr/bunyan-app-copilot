# Accessibility — Requirements Checklist

**Stage:** STAGE_01_PROJECT_INITIALIZATION  
**Domain:** WCAG 2.1 Level AA & RTL Accessibility  
**Version:** 1.0  
**Created:** 2026-04-10

---

## CHK001–010: Semantic HTML & Vue Component Structure

- [ ] **CHK001** — All Vue components use semantic HTML elements (not `<div>` for everything)  
      _Priority: **CRITICAL**_  
      _Note:_ Use `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>` appropriately

- [ ] **CHK002** — Heading hierarchy maintained (h1 → h2 → h3; no skipped levels)  
      _Priority: **HIGH**_  
      _Note:_ Screens readers and SEO depend on logical hierarchy

- [ ] **CHK003** — Form elements use `<label>` tags (not placeholder-only labels)  
      _Priority: **CRITICAL**_  
      _Note:_ Association: `<label for="email">Email</label><input id="email" />`

- [ ] **CHK004** — List content uses semantic `<ul>`, `<ol>`, `<li>` (not styled `<div>`)  
      _Priority: **HIGH**_  
      _Note:_ Screen readers announce list structure (number of items, current item)

- [ ] **CHK005** — Links use `<a href>` elements (not `<button>` styled as links)  
      _Priority: **HIGH**_  
      _Note:_ Navigation vs action: links = text underline; buttons = cursor:pointer

- [ ] **CHK006** — Buttons have descriptive text (not empty `<button>` with icon-only)  
      _Priority: **HIGH**_  
      _Note:_ Icon buttons: add `aria-label="Close"` if text not visible

- [ ] **CHK007** — Nuxt UI components (UButton, UCard, UForm) used with semantic wrapper elements  
      _Priority: **MEDIUM**_  
      _Note:_ Components already semantic; verify in layout templates

- [ ] **CHK008** — Table markup uses `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`  
      _Priority: **HIGH**_  
      _Note:_ Deferred: table implementation; structure documented for STAGE_32

- [ ] **CHK009** — Form sections clearly grouped via `<fieldset>` and `<legend>`  
      _Priority: **MEDIUM**_  
      _Note:_ Multiple related fields benefit from fieldset grouping

- [ ] **CHK010** — Color not the only differentiator (avoid red/green for status without icons/text)  
      _Priority: **MEDIUM**_  
      _Note:_ Red = error, green = success; also use icons or text label

---

## CHK011–020: ARIA Labels & Landmark Regions

- [ ] **CHK011** — Page landmarks defined: `<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`  
      _Priority: **CRITICAL**_  
      _Note:_ Screen readers can jump between landmarks; improves navigation

- [ ] **CHK012** — `<main>` element wraps primary page content (one per page)  
      _Priority: **HIGH**_  
      _Note:_ Prevents landmark confusion; screen readers start here

- [ ] **CHK013** — Navigation elements have descriptive `aria-label` (e.g., `<nav aria-label="Main navigation">`)  
      _Priority: **MEDIUM**_  
      _Note:_ Distinguishes multiple navs (Main, Sidebar, Footer)

- [ ] **CHK014** — Dynamic content regions marked with `aria-live` (for real-time updates)  
      _Priority: **MEDIUM**_  
      _Note:_ Example: `<div aria-live="polite" aria-atomic="true">Status updated</div>`

- [ ] **CHK015** — Search forms labeled with `aria-label="Search"`  
      _Priority: **MEDIUM**_  
      _Note:_ Especially if input lacks visible label

- [ ] **CHK016** — Icon-only buttons have descriptive `aria-label`  
      _Priority: **HIGH**_  
      _Note:_ Example: `<button aria-label="Close menu"><Icon name="x" /></button>`

- [ ] **CHK017** — Expandable/collapsible elements use `aria-expanded` attribute  
      _Priority: **MEDIUM**_  
      _Note:_ Accordion pattern: `aria-expanded="true|false"` on toggle button

- [ ] **CHK018** — Modal dialogs have `aria-modal="true"` and `role="dialog"`  
      _Priority: **HIGH**_  
      _Note:_ Communicates modal context to screen readers; focus trap required

- [ ] **CHK019** — Loading spinners marked as `aria-busy="true"` on parent container  
      _Priority: **MEDIUM**_  
      _Note:_ Screen reader: "Please wait, loading in progress"

- [ ] **CHK020** — Error messages associated with inputs via `aria-describedby`  
      _Priority: **CRITICAL**_  
      _Note:_ Example: `<input aria-describedby="email-error" /><span id="email-error">Invalid email</span>`

---

## CHK021–030: Keyboard Navigation Testing & Focus Management

- [ ] **CHK021** — All interactive elements focusable via Tab key (no click-required-only interactions)  
      _Priority: **CRITICAL**_  
      _Note:_ Test: press Tab and verify focus moves through entire page

- [ ] **CHK022** — Focus order logical (left-to-right, top-to-bottom on LTR; RTL opposite)  
      _Priority: **HIGH**_  
      _Note:_ HTML source order matters; CSS `order` property can break focus flow

- [ ] **CHK023** — Skip links present (e.g., "Skip to main content" button)  
      _Priority: **HIGH**_  
      _Note:_ Hidden by default; visible on focus: `a:focus { visibility: visible }`

- [ ] **CHK024** — Tab trap avoided (focus does not get stuck in modal or component)  
      _Priority: **HIGH**_  
      _Note:_ Last focusable element in modal → Tab → first focusable (circular)

- [ ] **CHK025** — Escape key closes modals/popovers  
      _Priority: **MEDIUM**_  
      _Note:_ Standard pattern; Nuxt UI components implement natively

- [ ] **CHK026** — Enter key submits forms (not click-only)  
      _Priority: **HIGH**_  
      _Note:_ Form inputs respond to Enter key naturally; custom listeners in JS must respect

- [ ] **CHK027** — Arrow keys navigate menus/select dropdowns (if implemented)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: menu implementation; principle documented for STAGE_31

- [ ] **CHK028** — Focus indicator visible (not removed via `outline: none` without replacement)  
      _Priority: **CRITICAL**_  
      _Note:_ Browsers default provides outline; if hidden, provide visible alternative (border, shadow)

- [ ] **CHK029** — Focus visible indicator sufficient contrast (visible on dark and light backgrounds)  
      _Priority: **HIGH**_  
      _Note:_ Test: Tab through page; outline/border visible at all times

- [ ] **CHK030** — Complex interactions (e.g., drag-and-drop) have keyboard alternative  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: file uploads; keyboard alt (button to select) documented

---

## CHK031–040: Screen Reader Compatibility & Testing

- [ ] **CHK031** — Page title reflects current page (e.g., "Projects - Bunyan")  
      _Priority: **HIGH**_  
      _Note:_ Screen readers announce title on page load; Vue Router updates docs title

- [ ] **CHK032** — Page language set in HTML: `<html lang="ar">` or `lang="en"`  
      _Priority: **HIGH**_  
      _Note:_ Screen reader uses appropriate pronunciation; Nuxt i18n sets this

- [ ] **CHK033** — Alt text provided for all decorative/meaningful images  
      _Priority: **CRITICAL**_  
      _Note:_ Meaningful images: descriptive alt; decorative: `alt=""` (empty, skip by reader)

- [ ] **CHK034** — SVG icons have `aria-label` or wrapped in `<span>` with label  
      _Priority: **MEDIUM**_  
      _Note:_ Example: `<svg aria-label="Heart icon">...</svg>` or icon inside labeled button

- [ ] **CHK035** — Form error messages linked to inputs via `aria-describedby`  
      _Priority: **CRITICAL**_  
      _Note:_ Screen reader announces error immediately after input label

- [ ] **CHK036** — Dropdown/select label properly associated (not just placeholder)  
      _Priority: **HIGH**_  
      _Note:_ Placeholder text disappears; label always available to screen reader

- [ ] **CHK037** — Tables have header row with `<th>` elements (not `<td>`)  
      _Priority: **HIGH**_  
      _Note:_ Screen reader announces column/row context; headers must precede data

- [ ] **CHK038** — Caption/summary provided for data tables (optional `<caption>` element)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: table documentation in STAGE_32

- [ ] **CHK039** — Language changes marked via `lang` attribute (mixed Arabic/English text)  
      _Priority: **HIGH**_  
      _Note:_ Example: `<span lang="en">Email</span>` in Arabic text; screen reader uses correct pronunciation

- [ ] **CHK040** — Screen reader tested with NVDA or VoiceOver (smoke test: login flow)  
      _Priority: **MEDIUM**_  
      _Note:_ Manual testing; foundation stage: documentation and setup for testers

---

## CHK041–050: RTL Language Support Verification

- [ ] **CHK041** — HTML `dir="rtl"` attribute toggles dynamically based on locale  
      _Priority: **CRITICAL**_  
      _Note:_ Nuxt i18n integration: `nuxt.config.ts` sets locale, HTML dir updates

- [ ] **CHK042** — Tailwind logical properties used (not `margin-left`, `padding-right`)  
      _Priority: **CRITICAL**_  
      _Note:_ Logical: `margin-inline-start`, `padding-block-end`; auto-flip on rtl

- [ ] **CHK043** — CSS flexbox direction respects `dir` attribute (not hardcoded)  
      _Priority: **HIGH**_  
      _Note:_ Example: `flex-row` auto-reverses to `flex-row-reverse` on rtl

- [ ] **CHK044** — CSS Grid layout respects `dir` attribute (not hardcoded)  
      _Priority: **MEDIUM**_  
      _Note:_ Grid columns auto-reverse; Tailwind v4 handles via logical properties

- [ ] **CHK045** — Text alignment uses logical properties (`text-start`, `text-end`)  
      _Priority: **MEDIUM**_  
      _Note:_ Not `text-left`, `text-right`; Tailwind v4 supports logical variants

- [ ] **CHK046** — Form elements (input, select, textarea) inherit `dir` from parent  
      _Priority: **HIGH**_  
      _Note:_ Input cursor direction auto-adjusts; no manual override needed

- [ ] **CHK047** — RTL text rendered correctly in console logs / developer output  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: manual verification; no special handling required for console

- [ ] **CHK048** — Icons/graphics do NOT mirror on RTL (only text mirrors)  
      _Priority: **HIGH**_  
      _Note:_ Exception: arrow icons used for navigation; context-dependent

- [ ] **CHK049** — Numeric input fields (phone, ZIP) left-to-right even in RTL context  
      _Priority: **MEDIUM**_  
      _Note:_ `<input type="tel" dir="ltr" />` prevents numeric reversal

- [ ] **CHK050** — RTL support verified on iPhone Safari + Android Chrome (manual testing)  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: setup checklist; actual testing deferred to STAGE_31

---

## CHK051–060: Color Contrast & Visual Design Compliance

- [ ] **CHK051** — Normal text contrast ratio ≥ 4.5:1 (WCAG AA standard)  
      _Priority: **CRITICAL**_  
      _Note:_ Test: Lighthouse report, axe DevTools, or WAVE browser extension

- [ ] **CHK052** — Large text contrast ratio ≥ 3:1 (18pt+ bold or 24pt+ regular)  
      _Priority: **HIGH**_  
      _Note:_ Headings, nav items often larger; less strict ratio allowed

- [ ] **CHK053** — UI component borders contrast ≥ 3:1 against background  
      _Priority: **MEDIUM**_  
      _Note:_ Example: button border vs button background

- [ ] **CHK054** — Focus indicator contrast ≥ 3:1 against background  
      _Priority: **CRITICAL**_  
      _Note:_ Visible on dark and light backgrounds; must be noticeable

- [ ] **CHK055** — Color not used as sole differentiator for functionality  
      _Priority: **HIGH**_  
      _Note:_ Example: error (red + icon + text), success (green + icon + text)

- [ ] **CHK056** — Geist font family ensures sufficient letter-spacing (readability)  
      _Priority: **MEDIUM**_  
      _Note:_ Bunyan DESIGN.md specifies negative letter-spacing; verify readable

- [ ] **CHK057** — No animated content flashing faster than 3x/second (no seizure trigger)  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: animated GIFs/transitions should avoid flashing

- [ ] **CHK058** — Background/foreground color contrast maintained on hover/focus/active states  
      _Priority: **HIGH**_  
      _Note:_ Button hover: text contrast still ≥ 4.5:1

- [ ] **CHK059** — Dark mode contrast verified (if dark theme supported)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: dark mode; foundation prepared for future implementation

- [ ] **CHK060** — Illustrations/infographics include descriptive captions or alt text  
      _Priority: **MEDIUM**_  
      _Note:_ Foundation: principle; implementation deferred to content creation

---

## CHK061–070: Form Labels & Error Association

- [ ] **CHK061** — All form inputs have associated `<label>` elements (not placeholders)  
      _Priority: **CRITICAL**_  
      _Note:_ Placeholder alone insufficient; label persistent and associated via `for` attribute

- [ ] **CHK062** — Label text matches input purpose (not abbreviated or cryptic)  
      _Priority: **HIGH**_  
      _Note:_ Example: "Email address" not "EA"; "Password" not "PW"

- [ ] **CHK063** — Required fields marked visually and programmatically  
      _Priority: **HIGH**_  
      _Note:_ Visual: asterisk (\*); Programmatic: `required` attribute + `aria-required="true"`

- [ ] **CHK064** — Error messages clearly describe the problem  
      _Priority: **HIGH**_  
      _Note:_ Not "Error"; instead: "Email address must be valid" or "Password must have uppercase"

- [ ] **CHK065** — Error message position near the input or linked via `aria-describedby`  
      _Priority: **CRITICAL**_  
      _Note:_ Focus moves to error or error announced immediately after input

- [ ] **CHK066** — Form submission error summary provided (before focused input)  
      _Priority: **HIGH**_  
      _Note:_ List all errors at top; screen reader reads all; links to fields

- [ ] **CHK067** — Validation occurs server-side (not client-only)  
      _Priority: **MEDIUM**_  
      _Note:_ Security + accessibility: backend validates; frontend pre-validation improves UX

- [ ] **CHK068** — Helper text (hints) associated with input via `aria-describedby`  
      _Priority: **MEDIUM**_  
      _Note:_ Example: `<input aria-describedby="pwd-hint" /><span id="pwd-hint">at least 8 characters</span>`

- [ ] **CHK069** — Password field includes option to show/hide (not forced obscured text)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: implementation; UX principle documented

- [ ] **CHK070** — Form labels not hidden on mobile (responsive doesn't remove labels)  
      _Priority: **HIGH**_  
      _Note:_ Labels persist; use responsive layout, not hidden label display property

---

## CHK071–080: Loading States & Feedback Indicators

- [ ] **CHK071** — Loading spinners include descriptive text (not spinning icon alone)  
      _Priority: **HIGH**_  
      _Note:_ Example: `<div aria-busy="true">Loading your projects...</div>`

- [ ] **CHK072** — Skeleton loaders marked as `aria-busy="true"` (placeholder content)  
      _Priority: **MEDIUM**_  
      _Note:_ Screen reader: "Loading in progress"; content updates when ready

- [ ] **CHK073** — Long operations (> 3 seconds) provide progress indication  
      _Priority: **MEDIUM**_  
      _Note:_ Example: progress bar with percentage; percentage announced periodically

- [ ] **CHK074** — Cancel button provided for long operations  
      _Priority: **MEDIUM**_  
      _Note:_ User can abort; button remains keyboard accessible

- [ ] **CHK075** — Toast/notification messages announced to screen readers  
      _Priority: **MEDIUM**_  
      _Note:_ Use `aria-live="polite"` or `aria-live="assertive"` for transient notifications

- [ ] **CHK076** — Toast messages include dismiss option (keyboard accessible)  
      _Priority: **HIGH**_  
      _Note:_ Timer auto-dismiss acceptable; also provide manual close button

- [ ] **CHK077** — Success/error/warning messages use icon + text (not color alone)  
      _Priority: **HIGH**_  
      _Note:_ Green ✓ + "Saved successfully"; Red ✗ + "Invalid entry"

- [ ] **CHK078** — Timeout warnings provided before session expiry  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: auth session management; principle for STAGE_03

- [ ] **CHK079** — Temporary overlays (loading, dimmed backdrop) don't trap focus permanently  
      _Priority: **HIGH**_  
      _Note:_ Keyboard users can navigate; overlay closed via Escape

- [ ] **CHK080** — Network error messages provided (not silent failures)  
      _Priority: **MEDIUM**_  
      _Note:_ Retry button + clear error message; user knows request failed

---

## CHK081–090: Focus Management & Modal Dialogs

- [ ] **CHK081** — Modal dialog has focus trap (Tab cycles within dialog only)  
      _Priority: **CRITICAL**_  
      _Note:_ Last focusable → Tab → first focusable (circular focus loop)

- [ ] **CHK082** — Initial focus placed on first focusable element in modal  
      _Priority: **HIGH**_  
      _Note:_ Convention: first input or primary action button (e.g., "Delete confirmed")

- [ ] **CHK083** — Modal has `role="dialog"` and `aria-modal="true"`  
      _Priority: **HIGH**_  
      _Note:_ Screen reader: "Dialog opened"; focus trap signaled

- [ ] **CHK084** — Modal heading provided via `aria-labelledby`  
      _Priority: **HIGH**_  
      _Note:_ Example: `<div role="dialog" aria-labelledby="modal-title"><h2 id="modal-title">Confirm Delete</h2></div>`

- [ ] **CHK085** — Escape key closes modal (standard pattern)  
      _Priority: **HIGH**_  
      _Note:_ User expects Esc to exit modal; Nuxt UI components implement natively

- [ ] **CHK086** — Focus restored to trigger element after modal closed  
      _Priority: **MEDIUM**_  
      _Note:_ User returns to "Open" button after modal closes; continuous context

- [ ] **CHK087** — Backdrop dismissal (click outside) optional; close button always present  
      _Priority: **HIGH**_  
      _Note:_ Keyboard users cannot dismiss by clicking; close button required

- [ ] **CHK088** — Nested modals (if any) managed correctly (focus trapped in topmost modal)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: complex modals; principle documented

- [ ] **CHK089** — Sidebars/drawers follow similar focus management as modals  
      _Priority: **MEDIUM**_  
      _Note:_ Either focus trap or closable via Escape

- [ ] **CHK090** — Popover/tooltip dismissal via Escape or click outside (keyboard support required)  
      _Priority: **MEDIUM**_  
      _Note:_ Deferred: tooltip implementation; pattern established

---

## CHK091–95: Localization & RTL Text Scaling

- [ ] **CHK091** — Text scaling up to 200% does not break layout  
      _Priority: **HIGH**_  
      _Note:_ Test: browser zoom 200%; no horizontal scroll; content remains readable

- [ ] **CHK092** — Responsive layout adapts to narrow viewports (mobile, zoomed)  
      _Priority: **HIGH**_  
      _Note:_ Foundation: Tailwind responsive breakpoints; verify no hard pixel widths

- [ ] **CHK093** — Viewport meta tag prevents zooming lock (`user-scalable=yes`)  
      _Priority: **HIGH**_  
      _Note:_ Users must able to zoom (accessibility right); never `user-scalable=no`

- [ ] **CHK094** — Localization keys properly scoped (no placeholder text in code)  
      _Priority: **MEDIUM**_  
      _Note:_ All text sourced from `locales/ar.json`, `locales/en.json`; principle for all future pages

- [ ] **CHK095** — Mixed language text marked with `lang` attribute (e.g., English email in Arabic page)  
      _Priority: **MEDIUM**_  
      _Note:_ `<span lang="en">user@example.com</span>` in Arabic content; screen reader pronunciation accurate

---

## Summary

**Total Items:** 95  
**Sections:** 10 (Semantics, ARIA, Keyboard, Screen Readers, RTL, Contrast, Forms, Loading, Focus, Localization)  
**Priority Breakdown:**

- CRITICAL: 18 items
- HIGH: 43 items
- MEDIUM: 34 items

**Key Outcomes:**

- Semantic HTML and ARIA labels establish foundation for screen reader support
- Keyboard navigation exhaustively covered (Tab, Escape, arrow keys, focus management)
- RTL support via Tailwind logical properties and dynamic `dir` attribute
- WCAG 2.1 Level AA baseline established (contrast, focus indicators, form labels)
- Color not sole differentiator; icons + text + color for status indication
- Modal and form patterns follow accessibility best practices
- Testing checklist provided for manual verification (screen readers, keyboard, RTL)

**Next Steps:** Items marked **MEDIUM** or with "Deferred" notes should trigger:

- STAGE_31 (UI Pages): comprehensive image alt text, tooltip/popover patterns
- STAGE_32 (Tables): table semantics and header/footer management
- STAGE_34 (Admin Pages): dark mode contrast verification
- QA Phase: automated accessibility testing (axe, Lighthouse, WAVE)
- Manual Testing: screen reader smoke test (login flow), keyboard navigation audit, RTL verification on device

**Integration with STAGE_01 Acceptance:**

- Linting & Code Review: verify semantic HTML usage
- Design Review: confirm contrast ratios, focus indicators in Figma
- Manual Testing: smoke test keyboard navigation, RTL on target devices
