---
name: subagent-parallelization
description: Parallel subagent execution strategy
---

# Subagent Parallelization — Bunyan

## When to Parallelize

Subagents can run in parallel when their tasks are **independent**:

- Code review + security audit (different concerns)
- Frontend lint + backend lint (different codebases)
- Unit tests + integration tests (if isolated)

## When NOT to Parallelize

- Implementation → then → testing (sequential dependency)
- Migration → then → seed (schema must exist first)
- Specify → Clarify → Plan (SpecKit sequential)

## Parallelization Groups

### Group 1: Analysis (can run together)

- Code Reviewer
- Security Auditor
- Architecture Guardian
- Performance Optimizer

### Group 2: Quality (can run together)

- Backend lint + analyze
- Frontend lint + typecheck
- Test execution

### Group 3: Sequential (must respect order)

1. Planner → 2. Implementer → 3. QA Engineer → 4. Code Reviewer
