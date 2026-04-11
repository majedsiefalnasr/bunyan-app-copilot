---
name: gitnexus-refactoring
description: Safe code restructuring
---

# GitNexus Refactoring — Bunyan

## When to Use

When the user wants to:

- Rename a function/class
- Extract code into a module
- Move code between files
- Split a large file

## Workflow

1. **Map dependencies**: Find all usages of the target
2. **Plan changes**: List all files that need updating
3. **Execute**: Apply changes systematically
4. **Verify**: Run tests and lint

## Safety Checks

Before refactoring:

- [ ] All tests pass on current code
- [ ] Impact analysis completed
- [ ] No CLOSED/HARDENED stage affected
- [ ] All dependents identified
