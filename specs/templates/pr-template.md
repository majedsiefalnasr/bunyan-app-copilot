# PR — {{STAGE_NAME}}

## Summary

**Stage:** {{STAGE_NAME}}
**Phase:** {{PHASE_NAME}}
**Branch:** `spec/{{STAGE_DIR_NAME}}` → `{{BASE_BRANCH}}`
**Tasks:** {{TASKS_COMPLETED}} / {{TASKS_TOTAL}} completed

## What Changed

### Backend

- [Key backend changes]

### Frontend

- [Key frontend changes]

### Database

- [Migrations added]

## Breaking Changes

- None / [List breaking changes]

## Testing

- [ ] Unit tests pass (`php artisan test --testsuite=Unit`)
- [ ] Feature tests pass (`php artisan test --testsuite=Feature`)
- [ ] Frontend tests pass (`npm run test`)
- [ ] Lint passes (`./vendor/bin/pint --test`)
- [ ] Type check passes (`./vendor/bin/phpstan analyse`)

## Checklist

- [ ] RBAC middleware applied on all new routes
- [ ] Form Request validation on all new endpoints
- [ ] Arabic/RTL support verified
- [ ] Error contract followed
- [ ] No N+1 queries
- [ ] API documentation updated
- [ ] Migration tested (`php artisan migrate --pretend`)

## Screenshots

[If UI changes are included]

## Related

- Stage File: `specs/phases/{{PHASE_NAME}}/{{STAGE_FILE_NAME}}`
- Testing Guide: `specs/runtime/{{STAGE_DIR_NAME}}/guides/TESTING_GUIDE.md`
