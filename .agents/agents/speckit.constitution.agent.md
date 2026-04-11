---
description: Create or update the project constitution from interactive or provided principle inputs, ensuring all dependent templates stay in sync.
handoffs:
  - label: Build Specification
    agent: speckit.specify
    prompt: Implement the feature specification based on the updated constitution. I want to build...
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Outline

You are updating the project constitution at `.specify/memory/constitution.md`.

Follow this execution flow:

1. Load the existing constitution at `.specify/memory/constitution.md`.

   - Identify every placeholder token of the form `[ALL_CAPS_IDENTIFIER]`.

2. Collect/derive values for placeholders:

   - If user input supplies a value, use it.
   - Otherwise infer from existing repo context.
   - `CONSTITUTION_VERSION` must increment according to semantic versioning.

3. Draft the updated constitution content:

   - Replace every placeholder with concrete text.
   - Ensure each Principle section has: succinct name, rules, rationale.
   - Ensure Governance section lists amendment procedure, versioning, compliance.

4. Consistency propagation checklist:

   - Read `.specify/templates/plan-template.md` for alignment
   - Read `.specify/templates/spec-template.md` for scope alignment
   - Read `.specify/templates/tasks-template.md` for task categorization

5. Produce a Sync Impact Report.

6. Validation: No remaining unexplained bracket tokens. Dates ISO YYYY-MM-DD.

7. Write the completed constitution back to `.specify/memory/constitution.md`.

8. Output summary with new version, files flagged for follow-up, and suggested commit message.
