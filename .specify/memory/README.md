# .specify/memory

> Session and cross-stage memory for AI agents.

This directory stores persistent memory files generated during SpecKit workflows.

## Files

- `constitution.md` — Project constitution (created by speckit.constitution agent)
- Additional memory files may be added during spec execution.

## Rules

- AI agents may read from this directory at any time.
- AI agents may write to this directory only during SpecKit workflow execution.
- Files in this directory are NOT authoritative — specs and ADRs take precedence.
