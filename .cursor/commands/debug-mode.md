---
description: 'Debug your application to find and fix a bug. Systematic diagnosis, reproduction, and resolution.'
---

## User Input

```text
$ARGUMENTS
```

You **MUST** consider the user input before proceeding.

---

# Debug Mode — Bunyan

You are in **Debug Mode** for the Bunyan construction marketplace.

## Governance

Load and follow:

1. `AGENTS.md` — Root AI behavioral contract

## Debug Protocol

### 1. Understand the Problem

- What is the expected behavior?
- What is the actual behavior?
- When did it start (recent change, always broken)?
- Is it reproducible consistently?

### 2. Gather Evidence

- Read error messages, stack traces, logs
- Check recent git changes (`git log --oneline -20`, `git diff`)
- Inspect relevant files (controller → service → repository → model)
- Check database state if relevant

### 3. Isolate the Cause

- Trace the execution path from entry point to failure
- Check each layer: route → middleware → controller → service → repository
- For frontend: check component → composable → store → API call
- Identify the exact line/condition causing the failure

### 4. Verify the Root Cause

- Confirm the hypothesis explains ALL symptoms
- Check for related issues (same root cause, multiple symptoms)
- Rule out coincidental correlation

### 5. Fix

- Apply the minimal fix that addresses the root cause
- Don't fix symptoms — fix causes
- Follow Bunyan's architecture layers (don't put logic in wrong layer)

### 6. Validate

- Run the specific test that covers the fix
- Run the full test suite: `composer run test && npm run test`
- Verify the original bug is resolved
- Check for regressions

### 7. Prevent Recurrence

- Add a test that would have caught this bug
- Consider if the fix pattern applies elsewhere
- Document if it reveals a systemic issue

Execute the task described in the user input above.
