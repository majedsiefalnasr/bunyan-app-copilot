---
name: ai-context-lifecycle-governance
description: Use when a workflow must regenerate AI context artifacts, enforce freshness gates, apply deterministic source-of-truth ordering, or scope AI context to the current SpecKit stage.
---

# AI Context Lifecycle Governance — Bunyan

## Context Artifacts

| Artifact          | Path                              | Purpose                      |
| ----------------- | --------------------------------- | ---------------------------- |
| AI Bootstrap      | `docs/ai/AI_BOOTSTRAP.md`         | Architecture reasoning model |
| AI Context Index  | `docs/ai/AI_CONTEXT_INDEX.md`     | Governance pipeline entry    |
| Project Context   | `docs/PROJECT_CONTEXT_PRIMER.md`  | Platform identity            |
| Engineering Rules | `docs/ai/AI_ENGINEERING_RULES.md` | Detailed constraints         |

## Freshness Rules

- Architecture artifacts must be regenerated after structural changes
- Context index must reference current ADRs and specs
- Stale artifacts (>7 days after structural change) must be flagged

## Source of Truth Ordering

**ADR > Specs > AI_CONTEXT_INDEX > AI_ENGINEERING_RULES > AGENTS.md > Implementation**

## Regeneration Triggers

1. New ADR created or accepted
2. Module added or removed
3. Domain model changed
4. Tech stack updated
5. RBAC rules modified
