---
name: gitnexus-impact-analysis
description: Blast radius and safety analysis
---

# GitNexus Impact Analysis — Bunyan

## When to Use

When the user asks:
- "Is it safe to change X?"
- "What depends on this?"
- "What will break if I modify X?"

## Workflow

1. **Identify target**: What symbol/file is being changed?
2. **Query dependents**: Find all code that depends on the target
3. **Assess blast radius**: Categorize impact (direct, transitive)
4. **Risk score**: Low / Medium / High / Critical
5. **Report**: Present impact with affected files and suggestions

## Risk Categories

| Impact | Risk | Action |
|--------|------|--------|
| 0-2 dependents | Low | Proceed |
| 3-5 dependents | Medium | Review each |
| 6-10 dependents | High | Plan carefully |
| 10+ dependents | Critical | ADR required |
