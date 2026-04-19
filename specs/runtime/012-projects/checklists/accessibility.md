# Accessibility Checklist — Projects (المشاريع)

**Feature**: STAGE_12_PROJECTS  
**Spec**: `specs/runtime/012-projects/spec.md`  
**Generated**: 2026-04-19  
**Purpose**: Validate that accessibility requirements for RTL/Arabic UI and inclusive design are fully specified.

---

## RTL & Bidirectional Text

- [ ] **CHK-A11Y-001**: Is `dir="rtl"` specified as the base direction for all project pages, or is it inherited from a global layout? Is the mechanism documented?
- [ ] **CHK-A11Y-002**: Are bidirectional text scenarios addressed? (Project names have both `name_ar` and `name_en` — how are mixed-direction strings displayed? Is `<bdi>` or `dir="auto"` specified for English text within an Arabic context?)
- [ ] **CHK-A11Y-003**: Is the multi-step wizard navigation direction specified for RTL? (Steps should flow right-to-left, "Next" button on the left, "Previous" on the right.)
- [ ] **CHK-A11Y-004**: Are form field layouts specified to use Tailwind logical properties (`ps-`, `pe-`, `ms-`, `me-`) instead of physical properties (`pl-`, `pr-`, `ml-`, `mr-`)?
- [ ] **CHK-A11Y-005**: Is the status badge direction specified? (Status flow DRAFT → CLOSED should display right-to-left in Arabic context.)
- [ ] **CHK-A11Y-006**: Are number formatting requirements specified for Arabic? (Budget amounts, completion percentages — Eastern Arabic numerals ٠١٢٣ vs Western 0123?)

## Keyboard Navigation

- [ ] **CHK-A11Y-007**: Is keyboard navigation specified for the tabbed project detail page? (Arrow keys to switch tabs, Tab to move between tab content and controls, Enter/Space to activate.)
- [ ] **CHK-A11Y-008**: Is keyboard navigation specified for the multi-step wizard? (Can users navigate between steps without a mouse? Is focus trapped within the active step?)
- [ ] **CHK-A11Y-009**: Is keyboard accessibility specified for the project listing cards? (Can users navigate between cards with arrow keys? Is the card itself focusable or just its interactive elements?)
- [ ] **CHK-A11Y-010**: Is focus management specified for status filter dropdowns and date range pickers?
- [ ] **CHK-A11Y-011**: Is keyboard support specified for phase sort-order reordering? (If drag-and-drop is used, is a keyboard alternative provided?)

## Screen Reader Support

- [ ] **CHK-A11Y-012**: Are ARIA landmarks specified for the project listing page? (`role="main"`, `role="search"` for filters, `role="list"` for project cards.)
- [ ] **CHK-A11Y-013**: Are ARIA labels specified for project status badges? (e.g., `aria-label="حالة المشروع: قيد التنفيذ"` — not just a colored badge with no text alternative.)
- [ ] **CHK-A11Y-014**: Is the tab panel structure specified with proper ARIA roles? (`role="tablist"`, `role="tab"`, `role="tabpanel"`, `aria-selected`, `aria-controls`)
- [ ] **CHK-A11Y-015**: Are `aria-live` regions specified for dynamic content updates? (e.g., when a status transition succeeds, is the success message announced to screen readers?)
- [ ] **CHK-A11Y-016**: Are form validation error announcements specified? (Inline errors on the wizard steps — are they linked to fields via `aria-describedby`? Is `aria-invalid` set on errored fields?)
- [ ] **CHK-A11Y-017**: Is the completion percentage for phases accessible? (Is it a `<progress>` element or `role="progressbar"` with `aria-valuenow`, `aria-valuemin`, `aria-valuemax`?)
- [ ] **CHK-A11Y-018**: Are empty-state messages accessible? (Empty project listing, empty phases list — are they announced to screen readers, not just visual placeholders?)

## Visual Design & Contrast

- [ ] **CHK-A11Y-019**: Are color contrast requirements specified for status badges? (WCAG 2.1 AA requires 4.5:1 for text, 3:1 for UI components — are badge colors defined with specific values?)
- [ ] **CHK-A11Y-020**: Is color-independent status indication specified? (Status should not rely solely on color — are icons, text labels, or patterns also required?)
- [ ] **CHK-A11Y-021**: Are focus indicator styles specified? (Visible focus rings on interactive elements — is the focus style defined or left to browser defaults?)
- [ ] **CHK-A11Y-022**: Is text sizing specified to support user zoom up to 200% without layout breakage?
- [ ] **CHK-A11Y-023**: Is touch target sizing specified for mobile? (Minimum 44×44px for interactive elements per WCAG 2.5.8.)

## Wizard Accessibility

- [ ] **CHK-A11Y-024**: Is the wizard step indicator accessible? (Does it convey current step, total steps, and step label to screen readers? e.g., "الخطوة 2 من 4: الموقع")
- [ ] **CHK-A11Y-025**: Is focus management specified when navigating between wizard steps? (Focus should move to the first field of the new step, not reset to the top of the page.)
- [ ] **CHK-A11Y-026**: Is data preservation on step navigation specified as perceivable? (When returning to a previous step, is there an indication that data was preserved — not just silently pre-filled?)
- [ ] **CHK-A11Y-027**: Is the Review & Submit step accessible? (Summary of entered data — is it structured as a description list or table for screen reader comprehension?)

## Error Handling Accessibility

- [ ] **CHK-A11Y-028**: Are API error messages specified with Arabic translations? (422 VALIDATION_ERROR field-level messages — are they localized for the user's language preference?)
- [ ] **CHK-A11Y-029**: Is the error summary pattern specified? (On form submission failure, is there a summary at the top of the form with links to each errored field?)
- [ ] **CHK-A11Y-030**: Are inline error messages associated with their fields? (Is `aria-describedby` or equivalent Nuxt UI mechanism specified?)

## Timeline/Gantt Accessibility

- [ ] **CHK-A11Y-031**: Is the timeline visualization specified with a non-visual alternative? (Gantt-style views are inherently visual — is a table or list alternative specified for screen reader users?)
- [ ] **CHK-A11Y-032**: Is the timeline's phase duration information available as text? (Not just bar widths — "مرحلة الأساسات: من 2026-05-01 إلى 2026-06-15, اكتمال 45%")
