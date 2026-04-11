---
name: documentation-writer-protocol
description: Report, PR summary, ADR generation protocol
---

# Documentation Writer Protocol — Bunyan

## Document Types

### ADR (Architecture Decision Record)

```markdown
# ADR-NNN: Title

## Status

Proposed | Accepted | Deprecated | Superseded

## Context

What is the issue we are facing?

## Decision

What was decided?

## Consequences

What are the positive and negative results?

## Alternatives Considered

What alternatives were evaluated?
```

### API Endpoint Documentation

```markdown
## POST /api/v1/projects

### Description

Create a new construction project.

### Authorization

Role: `customer`, `admin`

### Request Body

| Field  | Type   | Required | Description          |
| ------ | ------ | -------- | -------------------- |
| name   | string | ✅       | Project name         |
| budget | number | ✅       | Project budget (SAR) |

### Response

{ success: true, data: ProjectResource }

### Error Codes

- `VALIDATION_ERROR` (422)
- `AUTH_UNAUTHORIZED` (403)
```

## Rules

1. All documentation in both Arabic and English where applicable
2. API docs auto-generated from OpenAPI spec when possible
3. ADRs are immutable once accepted — new ADRs supersede old ones
4. Code comments for complex business logic only
5. README.md files at root of each major directory
