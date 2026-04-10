---
name: stage-workflow-governance
description: Stage status mutations and lifecycle rules
---

# Stage Workflow Governance — Bunyan

## Stage Status Values

- **OPEN**: Active development, modifications allowed
- **COMMITTED**: Steps locked, scope amendments require approval
- **CLOSED**: Frozen — no modifications
- **HARDENED**: Frozen + validated — production-ready

## Rules

1. **Check before modify**: Always validate stage status before modifying any spec
2. **CLOSED/HARDENED stages are frozen**: AI must refuse to modify
3. **COMMITTED stages**: Only scope amendments with explicit approval
4. **OPEN stages**: Free to modify within governance rules

## Stage Lifecycle

```
OPEN → COMMITTED → CLOSED → HARDENED
                      ↑
                  REOPENED (exceptional, requires justification)
```

## AI Behavior

- Before editing any spec file, check `.specify/` for stage status
- If stage is CLOSED or HARDENED → STOP → inform user → refuse to edit
- If stage is COMMITTED → WARN → proceed only with explicit approval
- Document stage transitions in ADR if architecturally significant
