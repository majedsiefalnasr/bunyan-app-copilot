---
name: gitnexus-guide
description: GitNexus tools and schema reference
---

# GitNexus Guide — Bunyan

## Available Tools

| Tool | Purpose |
|------|---------|
| `context` | Get repo context and index status |
| `query` | Query the knowledge graph |
| `impact` | Analyze blast radius of changes |
| `detect_changes` | Detect recent changes |
| `route_map` | Map execution routes |
| `rename` | Track renames across codebase |
| `cypher` | Raw Cypher queries |

## Graph Schema

- **Symbol**: Functions, classes, methods, variables
- **File**: Source code files
- **Module**: Logical modules/packages
- **CALLS**: Symbol calls another symbol
- **IMPORTS**: File imports from another file
- **CONTAINS**: Module contains files
- **DEPENDS_ON**: Module depends on another

## Quick Reference

```
# Check if index is fresh
context → check lastIndexed timestamp

# Find what a function calls
query: "What does ProjectService::createProject call?"

# Find what calls a function
impact: "ProjectService::createProject"

# Trace an execution flow
route_map: "POST /api/v1/projects"
```
