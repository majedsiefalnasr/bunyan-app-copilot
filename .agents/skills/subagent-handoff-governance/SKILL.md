---
name: subagent-handoff-governance
description: Subagent handoff validation and retry handling
---

# Subagent Handoff Governance — Bunyan

## Rules

1. **Exact name match**: Subagent names must exactly match the registered agent names in the orchestrator
2. **Context transfer**: When handing off to a subagent, include all necessary context
3. **Single responsibility**: Each subagent handles one specific concern
4. **No circular handoffs**: A → B → A is forbidden
5. **Failure handling**: If a subagent fails, the orchestrator must handle the failure

## Registered Subagents

| Name                  | Purpose                   |
| --------------------- | ------------------------- |
| Planner               | DAG-based execution plans |
| Implementer           | TDD code implementation   |
| Researcher            | Codebase exploration      |
| Debugger              | Root-cause analysis       |
| Critic                | Assumption challenging    |
| Code Simplifier       | Complexity reduction      |
| Code Reviewer         | Code quality review       |
| Security Auditor      | Security analysis         |
| Architecture Guardian | Architecture validation   |
| Database Engineer     | DB design + migration     |
| QA Engineer           | Test strategy + coverage  |
| Frontend Developer    | UI implementation         |
| API Designer          | API design                |
| DevOps Engineer       | CI/CD + deployment        |
| Technical Writer      | Documentation             |
| Performance Optimizer | Performance analysis      |
| Laravel Expert        | Laravel patterns          |
| Nuxt Expert           | Nuxt.js patterns          |
| ADR Generator         | Architecture decisions    |
| Accessibility Expert  | WCAG compliance           |
| GitHub Actions Expert | CI/CD workflows           |
| Context7 Expert       | Library documentation     |

## Handoff Template

```
I need you to [action] for [context].

Current state:
- [relevant files/status]

Constraints:
- [applicable rules]

Expected output:
- [what should be returned]
```
