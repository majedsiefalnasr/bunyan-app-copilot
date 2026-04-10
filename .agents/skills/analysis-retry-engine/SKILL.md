---
name: analysis-retry-engine
description: Retry strategy for failed analysis and validation
---

# Analysis Retry Engine — Bunyan

## Purpose

Intelligent retry and remediation for failed analysis, validation, and guardian checks.

## Retry Strategy

### Attempt 1: Direct Fix
- Parse error output
- Identify failing rule/check
- Apply targeted fix

### Attempt 2: Contextual Fix
- Read surrounding code context
- Understand why the fix failed
- Apply broader fix

### Attempt 3: Alternative Approach
- Consider alternative implementation
- Consult relevant skill for correct pattern
- Rewrite the problematic section

### Attempt 4: Escalate
- If 3 attempts fail → STOP
- Report failure with full context
- Request human intervention

## Categories

| Check Type | Max Retries | Escalation |
|-----------|-------------|------------|
| Lint error | 3 | Auto-fix then report |
| Type error | 3 | Report with context |
| Test failure | 2 | Report with failing test |
| Architecture violation | 1 | Always escalate |
| Migration error | 0 | Always escalate |
