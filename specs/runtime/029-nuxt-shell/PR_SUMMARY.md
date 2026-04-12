# PR — NUXT_SHELL

## Summary

**Stage:** NUXT_SHELL  
**Phase:** 07_FRONTEND_APPLICATION  
**Branch:** `spec/029-nuxt-shell` → `develop`  
**Tasks:** 30 / 30 completed

## What Changed

### Backend

- No functional backend code changes for this stage.
- Validation gates were executed in backend to preserve regression safety.

### Frontend

- Added shell navigation components:
  - `AppBreadcrumb.vue`
  - `AppFooter.vue`
  - `AppHeader.vue`
  - `AppNavigation.vue`
  - `AppSidebar.vue`
  - `MobileDrawer.vue`
- Added role-aware nav config in `app/config/navigation.ts`.
- Implemented core composables:
  - `useAuth.ts`
  - `useBreadcrumb.ts`
  - `useDirection.ts`
  - `useNotification.ts`
  - `usePreferences.ts`
- Added layouts:
  - `layouts/default.vue`
  - `layouts/auth.vue`
  - `layouts/public.vue`
- Added direction plugin and Pinia stores (`stores/auth.ts`, `stores/ui.ts`).
- Updated locale dictionaries and `app.vue`/`error.vue` shell integration.
- Added 6 unit test files for new shell/composable behavior.

### Database

- No migrations added.

## Breaking Changes

- None.

## Testing

- [x] Unit tests pass (`php artisan test --testsuite=Unit`)
- [x] Feature tests pass (`php artisan test --testsuite=Feature`)
- [x] Frontend tests pass (`npm run test`)
- [x] Lint passes (`./vendor/bin/pint --test` + `npm run lint`)
- [x] Type check passes (`./vendor/bin/phpstan analyse` + `nuxi typecheck`)

## Checklist

- [x] RBAC middleware applied on all new routes
- [x] Form Request validation on all new endpoints
- [x] Arabic/RTL support verified
- [x] Error contract followed
- [x] No N+1 queries introduced
- [x] API documentation updated
- [x] Migration tested (`php artisan migrate --pretend`)

## Screenshots

- Not included (code-only stage artifacts and tests in this branch).

## Related

- Stage File: `specs/phases/07_FRONTEND_APPLICATION/STAGE_29_NUXT_SHELL.md`
- Testing Guide: `specs/runtime/029-nuxt-shell/guides/TESTING_GUIDE.md`
