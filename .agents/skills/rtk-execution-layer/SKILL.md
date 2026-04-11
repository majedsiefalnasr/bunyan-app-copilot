---
name: rtk-execution-layer
description: Token-optimized command execution via RTK
---

# RTK Execution Layer — Bunyan

## RTK (Rust Token Killer)

Token-optimized command execution. Prefix all commands with `rtk`.

### Usage

```bash
# Always use rtk prefix
rtk git status
rtk git diff
rtk composer run test
rtk npm run lint
rtk php artisan test
```

### Benefits

- Reduces token usage by 70-99%
- Filters noise from command output
- Provides structured, AI-friendly output

### Command Categories

| Category | Commands                                        | Savings |
| -------- | ----------------------------------------------- | ------- |
| Tests    | `rtk php artisan test`, `rtk npm run test`      | 90-99%  |
| Build    | `rtk npm run build`, `rtk composer run analyze` | 70-87%  |
| Git      | `rtk git status/log/diff/add/commit/push`       | 59-80%  |
| Packages | `rtk composer list`, `rtk npm list`             | 70-90%  |

### Golden Rule

**Always prefix commands with `rtk`**. Safe passthrough if no filter exists.
