# Technical Plan — {{STAGE_NAME}}

> **Phase:** {{PHASE_NAME}} > **Based on:** `specs/runtime/{{STAGE_DIR_NAME}}/spec.md` > **Created:** {{ISO_TIMESTAMP}}

## Architecture Overview

[High-level architecture decisions for this stage.]

## Database Design

### New Tables

| Table | Purpose | Key Columns |
| ----- | ------- | ----------- |
|       |         |             |

### Modified Tables

| Table | Changes | Migration Name |
| ----- | ------- | -------------- |
|       |         |                |

### Eloquent Relationships

```
Model A --hasMany--> Model B
Model B --belongsTo--> Model A
```

## API Design

### New Endpoints

| Method | Route | Controller@Action | Middleware | Description |
| ------ | ----- | ----------------- | ---------- | ----------- |
|        |       |                   |            |             |

### Request/Response Contracts

#### [Endpoint Name]

**Request:**

```json
{}
```

**Response:**

```json
{
  "success": true,
  "data": {},
  "error": null
}
```

## Service Layer Design

| Service | Methods | Dependencies |
| ------- | ------- | ------------ |
|         |         |              |

## Frontend Design

### Pages

| Route | Page Component | Layout | Auth Required |
| ----- | -------------- | ------ | ------------- |
|       |                |        |               |

### Components

| Component | Purpose | Props |
| --------- | ------- | ----- |
|           |         |       |

### State Management (Pinia)

| Store | State | Actions | Getters |
| ----- | ----- | ------- | ------- |
|       |       |         |         |

## Middleware Chain

```
Request → CORS → Auth (Sanctum) → RBAC → Rate Limit → Controller
```

## Error Handling

| Scenario | Error Code | HTTP Status | Message |
| -------- | ---------- | ----------- | ------- |
|          |            |             |         |

## Testing Strategy

| Layer       | Tool       | Coverage Target |
| ----------- | ---------- | --------------- |
| Unit (PHP)  | PHPUnit    | 80%             |
| Unit (JS)   | Vitest     | 80%             |
| Integration | PHPUnit    | Key flows       |
| E2E         | Playwright | Critical paths  |

## Security Considerations

- [ ] Input validation via Form Requests
- [ ] RBAC middleware on all protected routes
- [ ] SQL injection prevention (Eloquent parameterized queries)
- [ ] XSS prevention (Blade escaping / Nuxt auto-escaping)
- [ ] CSRF protection
- [ ] Rate limiting on sensitive endpoints

## i18n / RTL Considerations

- [ ] All user-facing strings use translation keys
- [ ] RTL layout verified for Arabic
- [ ] Date/number formatting uses locale-aware helpers

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
| ---- | ---------- | ------ | ---------- |
|      |            |        |            |
