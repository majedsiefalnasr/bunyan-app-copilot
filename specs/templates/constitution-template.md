# Bunyan Project Constitution

> **Version:** 1.0
> **Last Updated:** {{ISO_DATE}}

## Core Principles

### 1. Separation of Concerns

- Controllers handle HTTP only — no business logic
- Services contain all business logic
- Repositories encapsulate data access (Eloquent)
- Form Requests handle validation

### 2. Security First

- RBAC middleware on all protected routes
- Input validation via Form Requests on every endpoint
- Authentication via Laravel Sanctum
- SQL injection prevention through Eloquent parameterized queries

### 3. Arabic-First Localization

- All user-facing strings use translation keys
- RTL layout support mandatory
- Date/number formatting locale-aware
- Arabic is the primary language, English is secondary

### 4. Workflow Engine Integrity

- Workflow state transitions are validated server-side
- State machine rules are enforced in the service layer
- Approval chains are immutable once started
- Status transitions follow defined paths only

### 5. Error Contract

All API responses follow:

```json
{
  "success": boolean,
  "data": object | null,
  "error": { "code": string, "message": string } | null
}
```

### 6. Testing Discipline

- Unit tests for all service methods
- Feature tests for all API endpoints
- Frontend component tests for interactive elements
- No feature ships without tests

### 7. Migration Safety

- Forward-only migrations
- Never modify existing migration files
- Destructive changes require explicit approval
- Seed data separated from schema migrations

## Technology Stack

| Layer         | Technology         |
| ------------- | ------------------ |
| Backend       | Laravel (PHP 8.2+) |
| Frontend      | Nuxt.js 3 (Vue 3)  |
| Database      | MySQL 8.0+         |
| ORM           | Eloquent           |
| Auth          | Laravel Sanctum    |
| State Mgmt    | Pinia              |
| CSS           | Bootstrap 5 RTL    |
| Testing (PHP) | PHPUnit            |
| Testing (JS)  | Vitest             |
| E2E           | Playwright         |
| CI/CD         | GitHub Actions     |

## Architectural Constraints

- No direct database queries outside repositories
- No HTTP framework dependencies in domain services
- No business logic in Blade/Vue templates
- No hardcoded strings — use config or translation keys
- No `dd()` or `dump()` in committed code
