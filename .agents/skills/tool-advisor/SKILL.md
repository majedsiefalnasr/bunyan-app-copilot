---
name: tool-advisor
description: Tool environment discovery and prompt amplification
---

# Tool Advisor — Bunyan

## Purpose

Discovers available tools and suggests optimal compositions for tasks.

## Available MCP Tools

| Tool     | Purpose                  | Auto-Trigger           |
| -------- | ------------------------ | ---------------------- |
| Context7 | Library documentation    | Third-party docs       |
| GitNexus | Codebase knowledge graph | Internal code analysis |
| GitHub   | Repository operations    | PR/Issue management    |
| DB MCP   | Schema inspection        | Database queries       |

## Task → Tool Mapping

| Task                         | Primary Tool         | Secondary       |
| ---------------------------- | -------------------- | --------------- |
| "How does X work?"           | GitNexus explore     | Read files      |
| "What breaks if I change X?" | GitNexus impact      | grep_search     |
| "Laravel docs for X"         | Context7             | Web fetch       |
| "Create PR"                  | GitHub MCP           | git commands    |
| "Check schema"               | DB MCP               | Migration files |
| "Debug failing test"         | Terminal (run tests) | Debugger agent  |

## Suggestions Are Non-Binding

Tool advisor suggests — agents decide. If a simpler approach works, use it.
