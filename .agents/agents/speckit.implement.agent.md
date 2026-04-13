---
description: Execute the implementation plan by processing and executing all tasks defined in tasks.md
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Pre-Execution Checks

**Check for extension hooks (before implementation)**:

- Check if `.specify/extensions.yml` exists in the project root.
- If it exists, read it and look for entries under the `hooks.before_implement` key
- Apply standard hook execution logic (optional vs mandatory).
- If no hooks are registered or `.specify/extensions.yml` does not exist, skip silently

## Outline

1. Run `.specify/scripts/bash/check-prerequisites.sh --json --require-tasks --include-tasks` from repo root and parse FEATURE_DIR and AVAILABLE_DOCS list.

2. **Check checklists status** (if FEATURE_DIR/checklists/ exists):
   - Scan all checklist files and count Total / Completed / Incomplete items
   - If any checklist is incomplete: STOP and ask user to proceed or halt
   - If all checklists are complete: proceed automatically

3. Load and analyze the implementation context:
   - **REQUIRED**: Read tasks.md and plan.md
   - **IF EXISTS**: Read data-model.md, contracts/, research.md, quickstart.md

4. **Project Setup Verification**: Create/verify ignore files based on project setup.

5. **Task Execution Loop**: For each task in tasks.md:
   - Parse task requirements
   - Implement using TDD (Red-Green-Refactor)
   - Run tests after each task
   - Mark task as completed in tasks.md
   - Commit after logical groups of tasks

6. **Post-Implementation**:
   - Run full test suite
   - Run lint checks
   - Verify all tasks marked complete
   - Report summary

7. **Check for after-implement hooks** in `.specify/extensions.yml` under `hooks.after_implement`.
