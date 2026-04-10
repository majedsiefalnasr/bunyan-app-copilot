# AI Bootstrap — Bunyan بنيان

## Architecture-First Reasoning Model

Before generating any code, AI agents MUST reason about architecture:

1. **Where does this code belong?** (Which layer? Which module?)
2. **What existing patterns should I follow?** (Check ADRs, skill files)
3. **Does this respect RBAC?** (Which roles can access this?)
4. **Does this follow the workflow engine?** (State transitions valid?)
5. **Is this Arabic-friendly?** (RTL, translations, locale formatting)

## Loading Priority

```
AGENTS.md → AI_BOOTSTRAP.md → AI_CONTEXT_INDEX.md → PROJECT_CONTEXT_PRIMER.md → AI_ENGINEERING_RULES.md
```

## Architecture Layers

```
Frontend (Nuxt.js 3)
  ↓ HTTP/API calls
API Layer (Laravel Controllers + Middleware)
  ↓ Service injection
Business Logic (Services + Events + Jobs)
  ↓ Repository pattern
Data Access (Repositories → Eloquent Models)
  ↓ Eloquent ORM
Database (MySQL)
```

## Decision Framework

| Question                          | If Yes                                | If No                       |
| --------------------------------- | ------------------------------------- | --------------------------- |
| Is there an ADR for this?         | Follow ADR                            | Check if ADR needed         |
| Does a skill file cover this?     | Apply skill patterns                  | Proceed with best practices |
| Is this a new module?             | Register in architecture map          | Modify existing             |
| Does this affect RBAC?            | Update policies                       | Verify existing auth        |
| Does this add a migration?        | Follow db-migration-governance skill  | N/A                         |
| Does this change workflow states? | Follow workflow-engine-patterns skill | N/A                         |

## MCP Integration

AI agents have access to these MCP tools:

- **Context7**: For up-to-date Laravel, Nuxt.js, Vue 3, Bootstrap documentation
- **GitNexus**: For codebase knowledge graph, dependency analysis, impact assessment
- **GitHub**: For repository operations, PR management, issue tracking
- **Database MCP**: For schema inspection (read-only)

**Rule**: Always use MCP tools for technical information. Training knowledge alone is insufficient.
