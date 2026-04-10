---
name: gitnexus-cli
description: GitNexus CLI commands
---

# GitNexus CLI — Bunyan

## Commands

```bash
# Analyze/index the repository
npx gitnexus analyze

# Check index status
npx gitnexus status

# Clean the index
npx gitnexus clean

# Generate wiki
npx gitnexus wiki

# List indexed repos
npx gitnexus list
```

## When to Reindex

- After major structural changes
- When context is >24h old
- When queries return stale results
- After adding new modules
