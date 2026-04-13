# Accessibility Requirements Quality Checklist — Authentication

> **Stage:** 003-authentication
> **Spec File:** `specs/runtime/003-authentication/spec.md` > **Generated:** 2026-04-13
> **Purpose:** Validates that accessibility requirements are defined for auth pages, covering WCAG 2.1 AA, RTL/Arabic, and inclusive UX.

---

## RTL & Arabic Language Support

### CHK-A11Y-001 — RTL Layout Requirements

- [x] `dir="rtl"` is required on all auth pages
- [x] Arabic labels and placeholders are required via i18n keys
- [x] Nuxt UI components are specified (native RTL support)
- [x] Tailwind logical properties implied by Nuxt UI usage
- [ ] Spec does not specify bidirectional text handling for email addresses (LTR content within RTL layout)
- [ ] Spec does not define text alignment rules for form inputs containing LTR content (emails, passwords)
- [ ] Language switcher (AR/EN) on auth pages is not specified — spec says "multi-language ready" but no toggle UI defined

### CHK-A11Y-002 — Arabic Typography Requirements

- [ ] Font requirements for Arabic text are not specified in auth spec (DESIGN.md governs via Geist fonts — but Arabic fallback font is not stated)
- [ ] Minimum font size for Arabic form labels is not specified
- [ ] Letter-spacing requirements for Arabic text are not specified (DESIGN.md specifies negative letter-spacing for headings — Arabic text may need different treatment)
- [x] Arabic role labels are required in role selector (US10)

## Form Accessibility

### CHK-A11Y-003 — Form Label Association

- [ ] `<label>` to `<input>` association (`for`/`id`) is not explicitly required — Nuxt UI `UFormField` handles this, but spec should state accessibility intent
- [ ] Required field indicators (visual + `aria-required`) are not specified
- [ ] Help text association (`aria-describedby`) for validation messages is not specified
- [x] Form field validation errors are displayed per-field (implies proximity to field)
- [x] VeeValidate + Zod specified for form validation (structured approach)

### CHK-A11Y-004 — Error Message Accessibility

- [x] Server-side validation errors are mapped to form fields
- [x] Toast notifications for generic errors are specified
- [ ] `aria-live` regions for dynamic error messages are not specified
- [ ] Error message announcement strategy for screen readers is not defined
- [ ] Focus management on validation failure (move focus to first error field) is not specified
- [ ] Error message color contrast requirements are not specified (relying on Nuxt UI defaults)

### CHK-A11Y-005 — Loading State Accessibility

- [x] Loading indicators on submit buttons are specified (US9, US10, US11)
- [ ] Loading state announcement for screen readers is not specified (`aria-busy`, `aria-live`)
- [ ] Button disabled state during loading is not explicitly required (prevents double-submit but also accessibility concern)
- [ ] Spec does not define whether loading replaces button text or appears alongside it

## Keyboard Navigation

### CHK-A11Y-006 — Keyboard Operability

- [ ] Tab order for form fields is not specified (assumed natural order but should be stated)
- [ ] Enter key submission for forms is not specified
- [ ] Focus trap for modal dialogs (if any) is not addressed — auth pages are full pages, not modals
- [ ] Skip-to-content link for auth pages is not specified
- [ ] Role selector keyboard interaction (arrow keys, space/enter) is not specified — Nuxt UI `USelect` handles this natively

### CHK-A11Y-007 — Focus Management

- [ ] Initial focus placement on page load is not specified (should focus first form field)
- [ ] Focus management after successful submission (redirect) is not specified
- [ ] Focus management after error display is not specified (move to first error)
- [ ] Focus visible indicator (`:focus-visible`) requirements are not specified — Nuxt UI provides defaults

## Visual Accessibility

### CHK-A11Y-008 — Color Contrast

- [ ] Color contrast ratios for form inputs are not specified — relying on Nuxt UI defaults and DESIGN.md palette
- [ ] Error state color contrast (red text on background) is not specified
- [ ] Link contrast (e.g., "Forgot password?" link) is not specified
- [ ] Spec does not require success/error states to use more than color alone (icons, text, patterns)

### CHK-A11Y-009 — Touch Target Sizing

- [x] Mobile-responsive design is required
- [ ] Minimum touch target size (44x44px) for buttons and links is not specified
- [ ] Spacing between interactive elements on mobile is not specified
- [ ] Role selector touch target sizing is not specified

### CHK-A11Y-010 — Password Field Accessibility

- [ ] Show/hide password toggle is not specified
- [ ] Password requirements visibility (before/during typing) is not specified
- [ ] Password strength indicator is not specified
- [ ] `autocomplete` attributes for password fields (`new-password`, `current-password`) are not specified — important for password managers

## Screen Reader Compatibility

### CHK-A11Y-011 — Semantic HTML Structure

- [ ] Page heading hierarchy (`<h1>` for page title) is not specified
- [ ] Form landmark (`<form>`) with accessible name is not specified
- [ ] Link purpose is clear from link text ("Forgot password?" vs generic "Click here") — implied but not stated
- [x] Nuxt UI components provide semantic HTML by default

### CHK-A11Y-012 — Status Messages

- [ ] Success message after registration ("email verification sent") as `role="status"` is not specified
- [ ] Success message after forgot password submission is not specified as live region
- [ ] Success message after password reset is not specified as live region
- [ ] Rate limit error (429) message accessibility is not specified

---

## Summary

| Category                    | Pass   | Warn   | Total  |
| --------------------------- | ------ | ------ | ------ |
| RTL & Arabic Layout         | 4      | 3      | 7      |
| Arabic Typography           | 1      | 3      | 4      |
| Form Label Association      | 2      | 3      | 5      |
| Error Message Accessibility | 2      | 4      | 6      |
| Loading States              | 1      | 3      | 4      |
| Keyboard Navigation         | 0      | 5      | 5      |
| Focus Management            | 0      | 4      | 4      |
| Color Contrast              | 0      | 4      | 4      |
| Touch Targets               | 1      | 3      | 4      |
| Password Fields             | 0      | 4      | 4      |
| Semantic HTML               | 1      | 3      | 4      |
| Status Messages             | 0      | 4      | 4      |
| **Total**                   | **12** | **43** | **55** |

**Status: PASS WITH WARNINGS** — RTL/Arabic core requirements are well-defined. Nuxt UI provides many accessibility features by default (semantic HTML, keyboard nav, focus indicators), which mitigates many gaps. However, the spec underspecifies explicit accessibility requirements — it relies heavily on component library defaults. Most actionable gaps: `aria-live` for dynamic messages, focus management on errors, `autocomplete` attributes, and show/hide password toggle.
