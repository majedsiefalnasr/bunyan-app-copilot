## Description

<!-- Brief description of changes -->

**Stage:** `STAGE_XX_NAME`
**Step:** specify | clarify | plan | tasks | analyze | implement | closure

## Changes

<!-- List key changes -->

- [ ] Change 1
- [ ] Change 2

## Checklist

### Spec Compliance

- [ ] Spec file reviewed and followed
- [ ] All required artifacts present in `specs/runtime/STAGE_XX/`
- [ ] `.workflow-state.json` updated to current step

### Backend

- [ ] Laravel Pint passes (`vendor/bin/pint --test`)
- [ ] PHPStan passes (`vendor/bin/phpstan analyse`)
- [ ] PHPUnit tests pass (`php artisan test`)
- [ ] Migrations are forward-only (no modify existing)
- [ ] RBAC middleware applied on new routes
- [ ] Error contract followed (`success/data/error`)

### Frontend

- [ ] ESLint passes (`npm run lint`)
- [ ] TypeScript check passes (`npx nuxi typecheck`)
- [ ] Vitest tests pass (`npm run test`)
- [ ] RTL layout verified
- [ ] Arabic/English translations added

### General

- [ ] No secrets or credentials committed
- [ ] Documentation updated if needed
