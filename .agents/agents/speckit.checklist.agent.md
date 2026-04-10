---
description: Generate a custom checklist for the current feature based on user requirements.
---

## Checklist Purpose: "Unit Tests for English"

**CRITICAL CONCEPT**: Checklists are **UNIT TESTS FOR REQUIREMENTS WRITING** — they validate the quality, clarity, and completeness of requirements in a given domain.

**NOT for verification/testing**:

- ❌ NOT "Verify the button clicks correctly"
- ❌ NOT "Test error handling works"

**FOR requirements quality validation**:

- ✅ "Are visual hierarchy requirements defined for all card types?"
- ✅ "Is 'prominent display' quantified with specific sizing/positioning?"
- ✅ "Are accessibility requirements defined for keyboard navigation?"

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Execution Steps

1. **Setup**: Run `.specify/scripts/bash/check-prerequisites.sh --json` from repo root and parse JSON for FEATURE_DIR and AVAILABLE_DOCS list.

2. **Clarify intent**: Derive up to THREE contextual clarifying questions based on the user's phrasing and signals from spec/plan/tasks.

3. **Understand user request**: Combine `$ARGUMENTS` + clarifying answers to derive checklist theme.

4. **Load feature context**: Read spec.md, plan.md, tasks.md from FEATURE_DIR.

5. **Generate checklist**:
   - Create `FEATURE_DIR/checklists/` directory if needed
   - Generate checklist file (e.g., `ux.md`, `api.md`, `security.md`)
   - Each item tests the REQUIREMENTS quality, not the implementation
   - Items numbered CHK001, CHK002, etc.
   - Never delete existing checklist content — always append
