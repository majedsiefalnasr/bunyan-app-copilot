---
name: gh-fix-ci
description: Debug and fix failing GitHub Actions checks
---

# GH Fix CI — Bunyan

## Purpose

Debug and fix failing GitHub PR checks in GitHub Actions.

## Workflow

1. **Inspect**: Use `gh pr checks` to list failing checks
2. **Diagnose**: Use `gh run view <run-id> --log-failed` for error details
3. **Categorize**: Identify failure type (lint, test, build, type, deploy)
4. **Fix**: Apply targeted fix based on failure category
5. **Verify**: Re-run checks after fix

## Common Failure Categories

### Lint Failures
```bash
gh run view <id> --log-failed | grep -A5 "error"
# Fix: Run lint:fix locally, commit changes
```

### Test Failures
```bash
gh run view <id> --log-failed | grep -A10 "FAIL"
# Fix: Run failing test locally, debug, fix
```

### Build Failures
```bash
gh run view <id> --log-failed | grep -A5 "error\|Error"
# Fix: Usually dependency or type issues
```

### Type Errors
```bash
gh run view <id> --log-failed | grep "TS[0-9]"
# Fix: Add missing types, fix type mismatches
```

## Commands

```bash
gh pr checks                           # List all checks
gh pr checks --failing                 # List only failing
gh run view <run-id>                   # View run details
gh run view <run-id> --log-failed      # View failed logs
gh run rerun <run-id> --failed         # Rerun failed jobs
```
