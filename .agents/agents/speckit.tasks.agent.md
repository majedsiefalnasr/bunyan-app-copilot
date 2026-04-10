---
description: Generate an actionable, dependency-ordered tasks.md for the feature based on available design artifacts.
handoffs:
  - label: Analyze For Consistency
    agent: speckit.analyze
    prompt: Run a project analysis for consistency
    send: true
  - label: Implement Project
    agent: speckit.implement
    prompt: Start the implementation in phases
    send: true
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding (if not empty).

## Pre-Execution Checks

**Check for extension hooks (before tasks generation)**:

- Check if `.specify/extensions.yml` exists in the project root.
- If it exists, read it and look for entries under the `hooks.before_tasks` key
- Apply standard hook execution logic (optional vs mandatory).
- If no hooks are registered or `.specify/extensions.yml` does not exist, skip silently

## Outline

1. **Setup**: Run `.specify/scripts/bash/check-prerequisites.sh --json` from repo root and parse FEATURE_DIR and AVAILABLE_DOCS list.

2. **Load design documents**: Read from FEATURE_DIR:
   - **Required**: plan.md (tech stack, libraries, structure), spec.md (user stories with priorities)
   - **Optional**: data-model.md (entities), contracts/ (interface contracts), research.md (decisions), quickstart.md (test scenarios)

3. **Execute task generation workflow**:
   - Load plan.md and extract tech stack, libraries, project structure
   - Load spec.md and extract user stories with their priorities (P1, P2, P3, etc.)
   - If data-model.md exists: Extract entities and map to user stories
   - If contracts/ exists: Map interface contracts to user stories
   - Generate tasks organized by user story
   - Generate dependency graph showing user story completion order
   - Create parallel execution examples per user story
   - Validate task completeness

4. **Generate tasks.md**: Use `.specify/templates/tasks-template.md` as structure, fill with:
   - Phase 1: Setup tasks (project initialization)
   - Phase 2: Foundational tasks
   - Phase 3+: One phase per user story
   - Final Phase: Polish & cross-cutting concerns
   - All tasks follow strict checklist format
   - Dependencies section
   - Parallel execution examples
   - Implementation strategy section

5. **Report**: Output path to generated tasks.md and summary.

6. **Check for after-tasks hooks** in `.specify/extensions.yml` under `hooks.after_tasks`.
