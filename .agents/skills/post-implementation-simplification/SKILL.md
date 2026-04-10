---
name: post-implementation-simplification
description: Dead code removal and readability refactors
---

# Post-Implementation Simplification — Bunyan

## When to Trigger

After implementation completes, run simplification to:
1. Remove dead code
2. Consolidate duplicates
3. Improve naming
4. Simplify complex conditionals

## Rules

- **Behavior preservation**: No functional changes
- **Scope limitation**: Only touch files modified in current task
- **No new features**: Only restructuring
- **Test parity**: Tests must still pass after simplification

## Checklist

- [ ] No unused imports
- [ ] No unused variables
- [ ] No duplicated code blocks (>5 lines)
- [ ] Method names follow conventions
- [ ] No overly complex conditionals (simplify with early returns)
- [ ] No commented-out code left behind
- [ ] No debug statements (dd(), console.log())

## PHP-Specific

- Remove unused `use` statements
- Consolidate repeated query patterns into scopes
- Use enum methods instead of switch statements
- Use `match` instead of `if/elseif` chains

## Vue/TypeScript-Specific

- Extract repeated template blocks into components
- Use composables for shared reactive logic
- Remove unused refs/computed
