# specs/runtime

> **Auto-generated directory.** Do not edit manually.

This directory contains runtime artifacts generated during SpecKit workflow execution.

## Structure

Each active stage gets its own subdirectory:

```
specs/runtime/
  STAGE_07_CATEGORIES/
    .workflow-state.json    # Workflow progress tracker
    spec.md                 # Filled specification
    clarify.md              # Clarification notes
    plan.md                 # Technical plan
    tasks.md                # Task breakdown
    checklist.md            # Quality checklist
    analyze.md              # Drift analysis report
    implement.md            # Implementation notes
  STAGE_08_PRODUCTS/
    ...
```

## Rules

1. Directories are created by `create-new-feature.sh` or by the orchestrator Pre-Step.
2. `.workflow-state.json` is the source of truth for workflow progress.
3. Runtime artifacts are committed at each step boundary.
4. After merge, runtime directories are preserved for audit trail.
