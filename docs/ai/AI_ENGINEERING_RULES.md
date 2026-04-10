# AI Engineering Rules — Bunyan بنيان

## 1. Layering Rules

### Backend (Laravel)

- **Controllers**: HTTP handling ONLY. Validate input via Form Requests, delegate to Services, return Resources.
- **Services**: Business logic. Inject Repositories. No HTTP objects (Request/Response). No direct Eloquent queries.
- **Repositories**: Database queries via Eloquent. Return Models or Collections. No business logic.
- **Models**: Relationships, scopes, casts, accessors/mutators. No business logic.
- **Jobs**: Queue-based background work. Idempotent. Retry-safe.
- **Events/Listeners**: Decoupled side effects. No critical business logic in listeners.

### Frontend (Nuxt.js)

- **Pages**: Route definition, layout selection, data fetching via composables.
- **Components**: Presentation + user interaction. Props in, events out.
- **Composables**: Reusable reactive logic. API calls, state management.
- **Stores (Pinia)**: Global state. Auth, cart, preferences.
- **Middleware**: Route guards (auth, RBAC, guest).

## 2. RBAC Rules

- Every API route MUST be protected by `auth:sanctum` middleware (unless explicitly public)
- Every controller action MUST authorize via Laravel Policies
- Frontend MUST implement route middleware for role checks
- Frontend role checks are UX only — server MUST re-validate
- Never expose admin-only data in public endpoints

## 3. API Design Rules

- All routes versioned under `/api/v1/`
- Response format: `{ success, data, error }`
- Use Laravel API Resources for response transformation
- Pagination via `?page=N&per_page=N`
- Filtering via query parameters
- Sorting via `?sort=field&order=asc|desc`

## 4. Database Rules

- MySQL 8.x with `utf8mb4_unicode_ci`
- Forward-only migrations — never modify existing
- Always use foreign key constraints
- Always index foreign key columns
- Use soft deletes on main entities
- Decimal precision for money: `decimal(12, 2)`

## 5. Testing Rules

- Every feature needs: unit tests (service/repository) + feature tests (API endpoints)
- RBAC matrix tested: authorized ✅, unauthorized ❌, unauthenticated ❌
- Workflow state transitions tested: valid ✅, invalid ❌
- Factory pattern for test data
- `RefreshDatabase` trait for feature tests

## 6. i18n Rules

- Arabic is primary language
- All user-facing text via translation keys (never hardcoded)
- Validation messages in Arabic
- RTL layout as default
- Number/date/currency formatting via Arabic locale

## 7. Security Rules

- Input validation via Form Requests (server-side)
- CSRF protection via Sanctum
- Rate limiting on sensitive endpoints (login, register, payments)
- File upload validation (MIME type, size, extension)
- No raw SQL with user input
- All financial operations in DB transactions
- Audit trail for critical actions

## 8. Git Rules

- Branch strategy: `main ← develop ← feature/* | fix/* | hotfix/*`
- Commit convention: `type(scope): description`
- PRs require passing CI + approval
- No force pushes to `main` or `develop`

## 9. Performance Rules

- Eager load relationships to avoid N+1
- Use chunking for bulk operations
- Cache frequently accessed data (Redis)
- API response pagination mandatory for lists
- Frontend lazy loading for routes and heavy components

## 10. Code Quality Rules

- PHP: PHPStan level 8, PHP CS Fixer
- TypeScript: Strict mode, ESLint
- No `any` types in TypeScript
- No `dd()` or `console.log()` in committed code
- No commented-out code in committed code
