# Requirements Checklist — {{STAGE_NAME}}

> **Phase:** {{PHASE_NAME}}
> **Created:** {{ISO_TIMESTAMP}}

## Architecture Compliance

- [ ] RBAC middleware applied on all protected routes
- [ ] Form Request validation for all user inputs
- [ ] Eloquent models use repository pattern
- [ ] Service layer contains all business logic
- [ ] Controllers are thin (delegate to services)
- [ ] Error contract followed (success/data/error format)
- [ ] No business logic in controllers or models

## Security

- [ ] Authentication required on protected endpoints (Sanctum)
- [ ] Authorization checks via RBAC middleware
- [ ] Input sanitization via Form Requests
- [ ] SQL injection prevention (parameterized queries)
- [ ] XSS prevention (proper escaping)
- [ ] CSRF protection enabled
- [ ] Rate limiting on sensitive endpoints
- [ ] Sensitive data not exposed in API responses

## Database

- [ ] Migration is forward-only (no destructive changes)
- [ ] Indexes defined for frequently queried columns
- [ ] Foreign keys and constraints properly set
- [ ] Eloquent relationships correctly defined
- [ ] Soft deletes used where appropriate
- [ ] Timestamps (created_at, updated_at) present

## API Quality

- [ ] RESTful endpoint naming conventions
- [ ] Proper HTTP status codes
- [ ] Pagination on list endpoints
- [ ] Filtering and sorting where applicable
- [ ] API Resources for response formatting
- [ ] Consistent error response format

## Frontend

- [ ] Arabic/RTL layout support verified
- [ ] Mobile responsive design
- [ ] Loading states for async operations
- [ ] Error handling and user feedback
- [ ] Form validation (client-side)
- [ ] Accessible (ARIA attributes, keyboard navigation)

## Testing

- [ ] Unit tests for service layer
- [ ] Feature tests for API endpoints
- [ ] Frontend component tests
- [ ] Edge cases covered
- [ ] Error scenarios tested

## Performance

- [ ] No N+1 query issues (eager loading used)
- [ ] Database queries optimized
- [ ] API response time < 200ms
- [ ] Caching strategy applied where beneficial
- [ ] Pagination used for large datasets

## i18n

- [ ] All user-facing strings use translation keys
- [ ] Arabic translations provided
- [ ] English translations provided
- [ ] Date/number formatting locale-aware
