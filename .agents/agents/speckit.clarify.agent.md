---
description: Identify underspecified areas in the current feature spec by asking up to 5 highly targeted clarification questions and encoding answers back into the spec.
handoffs:
  - label: Build Technical Plan
    agent: speckit.plan
    prompt: Create a plan for the spec. I am building with...
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Outline

Goal: Detect and reduce ambiguity or missing decision points in the active feature specification.

Note: This clarification workflow is expected to run BEFORE invoking `/speckit.plan`.

Execution steps:

1. Run `.specify/scripts/bash/check-prerequisites.sh --json --paths-only` from repo root. Parse JSON for `FEATURE_DIR` and `FEATURE_SPEC`.

2. Load the current spec file. Perform a structured ambiguity & coverage scan:

   - Functional Scope & Behavior
   - Domain & Data Model
   - Interaction & UX Flow
   - Non-Functional Quality Attributes
   - Integration & External Dependencies
   - Edge Cases & Failure Handling
   - Constraints & Tradeoffs
   - Terminology & Consistency
   - Completion Signals

3. Generate (internally) a prioritized queue of candidate clarification questions (maximum 5). Apply constraints:

   - Maximum of 5 total questions across the whole session.
   - Each question must be answerable with EITHER:
     - A short multiple-choice selection (2-5 options), OR
     - A one-word / short-phrase answer
   - Only include questions whose answers materially impact architecture, data modeling, task decomposition, test design, UX behavior, or compliance validation.

4. Sequential questioning loop (interactive):

   - Present EXACTLY ONE question at a time.
   - After each answer, encode the decision into the spec immediately.
   - Continue until all questions are asked or user opts out.

5. After all clarifications:
   - Update spec.md with all encoded answers
   - Report summary of changes made
