---
name: mcp-routing
description: MCP tool routing and selection policy
---

# MCP Routing — Bunyan

## Auto-Trigger Rules

### Context7 MCP
**When**: Querying third-party library documentation
**Trigger**: Laravel docs, Nuxt.js docs, Vue 3 docs, Bootstrap docs, Eloquent docs, VeeValidate, Zod, Pinia
**Action**: Auto-invoke for accurate, versioned documentation

### GitHub MCP
**When**: PR/issue/branch state checks
**Trigger**: PR creation, issue references, branch management
**Action**: Auto-invoke for repository operations

### GitNexus MCP
**When**: Internal codebase context, impact analysis, refactors
**Trigger**: Dependency tracing, blast radius analysis, architectural discovery
**Action**: Auto-invoke for knowledge graph queries

### Database MCP (if configured)
**When**: Schema inspection, query validation
**Trigger**: Schema verification, query plan analysis
**Action**: Read-first, no destructive SQL
**HARD RULE**: Never execute destructive SQL via MCP

## Routing Priority

1. **Context7** for external library/framework docs
2. **GitNexus** for internal codebase understanding
3. **GitHub** for repository/PR/issue operations
4. **DB MCP** for schema inspection (read-only)

## MCP Mandatory Usage

MCP usage is mandatory for technical tasks. Training knowledge alone is insufficient. Always verify against live documentation.
