# ERROR_HANDLING Accessibility & Localization Verification Checklist

> **Purpose:** Verify that accessibility and localization requirements are complete, unambiguous, and testable.  
> **Scope:** RTL/Arabic support, colorblind-safe design, keyboard navigation, ARIA compliance, message translation, and inclusive error UI.  
> **Total Items:** 18

---

## Localization & Translation Requirements

- [ ] **CHK-A11Y-001** — User-facing error messages are clearly categorized as requiring Arabic + English translations (C4 clarifies: "User-facing errors in both Arabic/English; technical errors in English only")

- [ ] **CHK-A11Y-002** — Technical errors (stack traces, DB errors) are explicitly NOT translated (C4 specifies scope: "Not Localized: Stack traces, database query errors, internal system errors")

- [ ] **CHK-A11Y-003** — Validation errors are marked for translation (C4: "Validation errors, auth errors, workflow state messages, payment errors, business rule violations" - all localized)

- [ ] **CHK-A11Y-004** — Translation key naming convention is specified (C4 references: "dot notation" in `resources/lang/{ar,en}/` — examples: `validation.field_required`, `auth.invalid_credentials`)

- [ ] **CHK-A11Y-005** — Error messages avoid idioms or cultural references that don't translate (spec should include guidance on human-friendly message patterns for i18n)

- [ ] **CHK-A11Y-006** — Correlation ID is NOT translated (it's technical, not user-facing per C8)

- [ ] **CHK-A11Y-007** — All numeric values in error messages support both Arabic (٠-٩) and English (0-9) number formats if needed

- [ ] **CHK-A11Y-008** — Error messages preserve meaning in both Arabic and English (e.g., "field required" translates accurately; plural forms handled correctly)

---

## RTL (Right-to-Left) Layout Support

- [ ] **CHK-A11Y-009** — Error page components include RTL layout strategy (spec requirement US5: "Error messages include actionable guidance")

- [ ] **CHK-A11Y-010** — Toast notification component supports RTL positioning (default top-right for LTR, or configurable for RTL)

- [ ] **CHK-A11Y-011** — Error boundary component preserves readable layout in RTL mode (icons, text, buttons all position correctly)

- [ ] **CHK-A11Y-012** — Tailwind logical properties (`start`, `end`, `inset-inline`) are specified for RTL-safe styling (vs. hardcoded `left`, `right`)

---

## Visual Design & Colorblind Safety

- [ ] **CHK-A11Y-013** — Error highlighting uses multiple visual cues beyond color alone (e.g., icon + outline + text, not just red background)

- [ ] **CHK-A11Y-014** — Toast notification system distinguishes severity levels (error, warning, success, info) with patterns, not ONLY color

- [ ] **CHK-A11Y-015** — Error validation underlines or outlines have sufficient contrast (WCAG AA: 4.5:1 for text, 3:1 for UI components)

---

## Keyboard Navigation & Assistive Technology

- [ ] **CHK-A11Y-016** — Toast notifications can be dismissed via keyboard (Escape key required per accessibility best practices)

- [ ] **CHK-A11Y-017** — Error boundary components do not trap keyboard focus (focus can be managed or returned to appropriate element)

- [ ] **CHK-A11Y-018** — ARIA roles and labels are specified for error components: `role="alert"` for toasts, `role="complementary"` for error boundaries where appropriate

---

## Summary

**Expected:** All 18 accessibility and localization requirements ensure the error handling system is usable for Arabic + English users, colorblind users, and keyboard navigation users.  
**Success:** Implementation can be verified via WCAG 2.1 AA audit, RTL rendering test, and screen reader testing (VoiceOver, NVDA).
