---
name: gitnexus-debugging
description: Bug tracing via knowledge graph
---

# GitNexus Debugging — Bunyan

## When to Use

When the user is:

- Debugging a bug
- Tracing an error
- Asking why something fails

## Workflow

1. **Reproduce**: Understand the error message/behavior
2. **Locate**: Use GitNexus to find relevant symbols
3. **Trace**: Follow the execution path to the failure point
4. **Diagnose**: Identify root cause
5. **Report**: Present findings with evidence

## Query Patterns

```
# Find error origin
impact: "Where is ErrorCode::VALIDATION_ERROR thrown?"

# Trace data flow
query: "What transforms data between controller and database for projects?"

# Find related failures
query: "What other code depends on ProjectService?"
```
