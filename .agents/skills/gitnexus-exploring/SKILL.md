---
name: gitnexus-exploring
description: Architecture exploration and code understanding
---

# GitNexus Exploring — Bunyan

## When to Use

When the user asks:

- "How does X work?"
- "What calls this function?"
- "Show me the auth flow"
- "Explain the project creation flow"

## Workflow

1. **Load context**: `gitnexus://repo/bunyan-app/context`
2. **Query the graph**: Use appropriate query tool
3. **Trace execution**: Follow call chains
4. **Report findings**: Summarize architecture and data flow

## Example Queries

```
# Find all callers of a function
query: "What calls ProjectService::createProject?"

# Trace an execution flow
query: "Trace the request flow from POST /api/v1/projects to database"

# Understand a module
query: "What does the workflow engine module contain?"
```

## Output Format

Present findings as:

1. Summary (1-2 sentences)
2. Call chain / dependency graph
3. Key files involved
4. Relevant code snippets
