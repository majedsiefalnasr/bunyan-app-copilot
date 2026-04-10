# AI Context Index — Bunyan بنيان

## Purpose

This is the AI governance pipeline entry point. All AI agents load this to understand what context artifacts exist and how to navigate the governance structure.

## Artifact Map

| Artifact          | Path                                   | Purpose                                                      |
| ----------------- | -------------------------------------- | ------------------------------------------------------------ |
| Root Contract     | `AGENTS.md`                            | AI behavioral rules, platform identity, non-negotiable rules |
| AI Bootstrap      | `docs/ai/AI_BOOTSTRAP.md`              | Architecture-first reasoning model                           |
| AI Context Index  | `docs/ai/AI_CONTEXT_INDEX.md`          | This file — governance pipeline                              |
| Project Context   | `docs/PROJECT_CONTEXT_PRIMER.md`       | Full platform description, domain model, tech stack          |
| Engineering Rules | `docs/ai/AI_ENGINEERING_RULES.md`      | Detailed engineering constraints                             |
| Orchestrator      | `.agents/agents/orchestrator.agent.md` | Main workflow controller                                     |
| Skills Index      | `.agents/skills/SKILLS_INDEX.md`       | Complete skill catalog                                       |

## Architecture Decisions

ADRs are stored in `docs/architecture/ADR/` and are binding:

- AI must never invent architecture not covered by an ADR
- New architectural decisions require a new ADR (use ADR Generator agent)

## SpecKit Workflow

The SpecKit Hard Mode workflow governs feature development:

1. **Specify** → Create feature spec from requirements
2. **Clarify** → Ask targeted questions for underspecified areas
3. **Plan** → Generate design artifacts and implementation plan
4. **Tasks** → Create dependency-ordered task list
5. **Analyze** → Cross-artifact consistency validation
6. **Implement** → TDD-driven task execution
7. **Closure** → Documentation, validation, completion

## Governance Pipeline

```
User Request
  → Orchestrator loads AGENTS.md + AI_BOOTSTRAP.md
  → Matches intent to SpecKit step or direct action
  → Routes to appropriate subagent
  → Subagent loads governance-preamble skill
  → Subagent loads domain-specific skills
  → Execution with governance validation
  → Post-implementation simplification (optional)
```

## Source of Truth

**ADR > Specs > AI_CONTEXT_INDEX > AI_ENGINEERING_RULES > AGENTS.md > Implementation**
