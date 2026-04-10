# Bunyan — Stage Lifecycle Policy

> Governs the lifecycle of all spec stages in the Bunyan project.

---

## Stage Statuses

| Status      | Meaning                           | Mutability                        |
| ----------- | --------------------------------- | --------------------------------- |
| NOT STARTED | Work has not begun                | Fully editable                    |
| IN PROGRESS | Active work (specify → implement) | Editable within current step only |
| IN REVIEW   | Pre-merge review                  | Read-only (except fixes)          |
| COMPLETED   | All steps done, merged            | Frozen — no edits                 |
| HARDENED    | Tested and verified in staging    | Frozen — ADR required for changes |
| DEPRECATED  | Superseded by another stage       | Frozen                            |

---

## Lifecycle Rules

### 1. Status Transitions (Valid)

```
NOT STARTED → IN PROGRESS → IN REVIEW → COMPLETED → HARDENED
NOT STARTED → DEPRECATED
IN PROGRESS → DEPRECATED
```

### 2. Frozen Stages

- **COMPLETED** and **HARDENED** stages may NOT be modified.
- If a change is required to a frozen stage, file a new ADR and create a scope amendment.
- Rolling back a frozen stage requires Rollback Protocol (R.1–R.6).

### 3. Step Ordering

Within a stage, steps execute in strict order:

```
Pre-Step → Specify → Clarify → Plan → Tasks → Analyze → Implement → Closure
```

Skipping steps is forbidden. Each step must produce its required artifacts before the next step begins.

### 4. Stage Dependencies

A stage may NOT start until all its upstream dependencies are at least COMPLETED.

Check upstream status in `INDEX.md` or in the dependency graph before beginning work.

### 5. Concurrent Stages

Stages with no dependency relationship MAY be worked on concurrently. Stages within the same dependency chain MUST be sequential.

---

## Spec File Ownership

| File                        | Owner                                 |
| --------------------------- | ------------------------------------- |
| `specs/phases/*/STAGE_*.md` | speckit.specify agent                 |
| `specs/runtime/*/`          | orchestrator.agent                    |
| `specs/templates/`          | Governance (manual only)              |
| `specs/INDEX.md`            | Automatically updated by orchestrator |

---

## Enforcement

The orchestrator validates stage status before:

- Starting any step
- Modifying any spec artifact
- Creating a PR

Violations trigger an immediate STOP with escalation.
