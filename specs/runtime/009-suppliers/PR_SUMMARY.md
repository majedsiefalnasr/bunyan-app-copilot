# PR — Suppliers (STAGE_09)

## Summary

**Stage:** Suppliers
**Phase:** 02_CATALOG_AND_INVENTORY
**Branch:** `spec/009-suppliers` → `develop`
**Tasks:** 39 / 39 completed
**ADR:** ADR-009-01 (404-not-403 visibility enforcement pattern)

## What Changed

### Backend

- **SupplierProfile model** (`app/Models/SupplierProfile.php`) — Eloquent model with `SupplierVerificationStatus` enum cast, soft deletes, `belongsTo(User)` relationship, PHPDoc annotations
- **Migration** (`database/migrations/2026_04_15_000001_create_supplier_profiles_table.php`) — `supplier_profiles` table with indexes on `user_id`, `commercial_reg`, `verification_status`, `city`
- **Repository layer** — `SupplierRepositoryInterface` + `SupplierRepository` (paginated listings with status/city/search filters)
- **SupplierService** (`app/Services/SupplierService.php`) — `create()`, `update()`, `verifySupplier()`, `suspendSupplier()` with `commercial_reg` uniqueness guard
- **SupplierController** (`app/Http/Controllers/Api/V1/SupplierController.php`) — thin controller; `show()` uses raw `int $id` (ADR-009-01 prevents existence enumeration)
- **Form Requests** — `StoreSupplierRequest`, `UpdateSupplierRequest`, `VerifySupplierRequest`, `SuspendSupplierRequest`
- **SupplierPolicy** — 5 methods (`viewAny`, `view`, `create`, `update`, `verify`/`suspend`) registered in `AppServiceProvider`
- **SupplierResource** — API resource with `@mixin SupplierProfile` for PHPStan compatibility
- **Routes** (`routes/api/v1/suppliers.php`) — public GET routes (no auth) + protected POST/PUT (auth:sanctum + role)
- **i18n** — `lang/ar/suppliers.php`, `lang/en/suppliers.php`
- **Tests** — 8 unit tests (`SupplierServiceTest`) + 28 feature tests (`SupplierControllerTest`, `SupplierVerificationWorkflowTest`)
- **Existing file changes** — `BaseController.php` (added `AuthorizesRequests` trait), `AppServiceProvider.php` (policy + route model + repo binding), `routes/api.php` (include suppliers.php)

### Frontend

- **Types** — `frontend/types/supplier.ts` (`SupplierProfile`, `SupplierVerificationStatus`, API types)
- **Composable** — `frontend/composables/useSupplier.ts` (all CRUD + verify/suspend operations, wraps API calls)
- **Pinia store** — `frontend/stores/useSupplierStore.ts` (reactive state, actions calling composable)
- **Components**:
  - `SupplierForm.vue` — create/edit form using reactive Zod v4 native validation (no vee-validate)
  - `SupplierCard.vue` — public supplier listing card
  - `VerificationStatusBadge.vue` — status badge with Arabic labels
- **Pages**:
  - `pages/suppliers/index.vue` — public supplier listing
  - `pages/suppliers/[id].vue` — public supplier detail
  - `pages/dashboard/supplier/profile.vue` — contractor self-service profile management
  - `pages/admin/suppliers/index.vue` — admin management with Nuxt UI v4 table (tanstack API)
- **i18n** — `locales/ar.json`, `locales/en.json` with full `suppliers` namespace

### Database

- Added migration: `2026_04_15_000001_create_supplier_profiles_table.php`
  - Table: `supplier_profiles`
  - Key columns: `user_id` (FK→users), `company_name_ar`, `company_name_en`, `commercial_reg` (unique), `verification_status` (enum), `city`, `phone`, `tax_number`, `logo`, `website`, `description_ar`, `description_en`
  - Soft deletes: `deleted_at`

## Breaking Changes

- None — all new routes and tables; no existing schema or API modified

## Testing

- [x] Unit tests pass — `php artisan test --filter=SupplierServiceTest` (8 tests)
- [x] Feature tests pass — `php artisan test --filter=SupplierControllerTest` (19 tests) + `--filter=SupplierVerificationWorkflowTest` (9 tests)
- [x] All backend tests pass — 369 total, 0 failures
- [x] Frontend tests pass — `npm run test` (460 tests, 0 failures)
- [x] Lint passes — `vendor/bin/pint --test` (0 errors)
- [x] Type check passes — `vendor/bin/phpstan analyse --memory-limit=1G` (0 errors, level 6)
- [x] Frontend type check passes — `npx nuxi typecheck` (0 TypeScript errors)
- [x] ESLint passes — `npx eslint . --max-warnings=0` (0 errors)
- [x] Migration validated — `php artisan migrate --pretend` (DDL generated without error)

## Checklist

- [x] RBAC middleware applied on all new routes (auth:sanctum + role check in SupplierPolicy)
- [x] Form Request validation on all new endpoints (4 Form Request classes)
- [x] Arabic/RTL support verified (all UI strings in ar.json/en.json, Tailwind logical properties)
- [x] Error contract followed (`{ success, data, error }` unified response on all endpoints)
- [x] No N+1 queries (Eager loading in SupplierRepository; repository reviewed by Performance Optimizer)
- [x] Migration tested (`php artisan migrate --pretend` — no errors)
- [x] ADR-009-01 compliance — `show()` uses raw `int $id`, returns 404 for non-existent OR unauthorized

## Key Architectural Decision

**ADR-009-01: 404-not-403 Visibility Enforcement**

The `show()` endpoint returns 404 (not 403) when a user tries to access a supplier profile they're not authorized to see. This prevents existence enumeration — callers cannot determine whether a resource exists by differentiating 404 vs 403 responses.

## Bug Fixes Included

17 bugs fixed during implementation, including:

- Path alias correction (`~~/types/supplier` not `~/types/supplier`)
- Zod v4 / vee-validate incompatibility resolved (native safeParse)
- Nuxt UI v4 table API migration (tanstack ColumnDef, `:data`, `#field-cell`, `row.original`)
- `useNotification()` API correction (`notifySuccess` not `notify`)
- `ar.json` invalid JSON fixed (missing closing braces)

## Screenshots

RTL/Arabic-first UI — all supplier pages support Arabic layout via Tailwind CSS logical properties and `dir="rtl"` on `<html>`.

## Related

- Stage File: `specs/phases/02_CATALOG_AND_INVENTORY/STAGE_09_SUPPLIERS.md`
- Testing Guide: `specs/runtime/009-suppliers/guides/TESTING_GUIDE.md`
- Closure Report: `specs/runtime/009-suppliers/reports/CLOSURE_REPORT.md`
- Validation Report: `specs/runtime/009-suppliers/audits/VALIDATION_REPORT.md`
- ADR-009-01: `docs/architecture/ADR/ADR-009-01-supplier-visibility-enforcement.md`
