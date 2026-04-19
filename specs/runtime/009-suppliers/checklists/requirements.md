# Requirements Checklist — STAGE_09 Suppliers

> **Spec:** `specs/runtime/009-suppliers/spec.md`
> **Stage:** STAGE_09 — Suppliers
> **Phase:** 02_CATALOG_AND_INVENTORY
> **Last Updated:** 2026-04-15

Use this checklist before marking the stage as COMPLETE. Every item must be checked or explicitly
marked N/A with a justification.

---

## Architecture Compliance

- [ ] `SupplierProfile` model is in `app/Models/` and extends `BaseModel` (or uses `SoftDeletes`)
- [ ] `SupplierService` is in `app/Services/` — contains all business logic; no HTTP concerns
- [ ] `SupplierRepository` implements an interface in `app/Repositories/Contracts/`
- [ ] `SupplierController` is in `app/Http/Controllers/Api/V1/` and is thin (delegates to service)
- [ ] No Eloquent queries appear inside the controller or service; all DB access via repository
- [ ] `SupplierPolicy` is registered in `AuthServiceProvider` (or auto-discovered)
- [ ] `SupplierVerificationStatus` PHP enum is in `app/Enums/`
- [ ] Route file (`routes/api/v1/suppliers.php`) is included from the parent `api.php`

---

## RBAC Enforcement

- [ ] `GET /api/v1/suppliers` — public, but filters `verification_status = verified` for non-admins
- [ ] `GET /api/v1/suppliers/{id}` — public with visibility guard (pending/suspended hidden from public)
- [ ] `POST /api/v1/suppliers` — requires `auth:sanctum`; policy denies non-contractors
- [ ] `PUT /api/v1/suppliers/{id}` — requires `auth:sanctum`; policy blocks cross-contractor updates
- [ ] `PUT /api/v1/suppliers/{id}/verify` — requires `auth:sanctum`; Admin role only
- [ ] `PUT /api/v1/suppliers/{id}/suspend` — requires `auth:sanctum`; Admin role only
- [ ] `GET /api/v1/suppliers/{id}/products` — public with same visibility guard as show
- [ ] No client-side-only authorization; all checks enforced server-side via policy or service

---

## Form Request Validation

- [ ] `StoreSupplierRequest` validates all required and optional fields per spec § 6.3
- [ ] `UpdateSupplierRequest` uses `sometimes` rules for partial updates
- [ ] `commercial_reg` uniqueness check excludes the current record on update
- [ ] Phone regex `/^05\d{8}$/` applied in both Store and Update requests
- [ ] `website` and `logo` validated as URL format
- [ ] `verification_status` is NOT accepted as a fillable field via store/update requests

---

## Error Contract Compliance

- [ ] All success responses return `{ "success": true, "data": {...}, "error": null }`
- [ ] All error responses return `{ "success": false, "data": null, "error": { "code": "...", "message": "...", "details": ... } }`
- [ ] Error codes used are from the approved registry in `AGENTS.md`:
  - [ ] `RESOURCE_NOT_FOUND` (404) on missing supplier
  - [ ] `VALIDATION_ERROR` (422) on invalid input (with field-level details)
  - [ ] `CONFLICT_ERROR` (409) on duplicate contractor profile
  - [ ] `RBAC_ROLE_DENIED` (403) on wrong role
  - [ ] `AUTH_UNAUTHORIZED` (403) on own-resource violation
  - [ ] `WORKFLOW_INVALID_TRANSITION` (422) on invalid status change
- [ ] 500 errors do NOT expose stack traces to the client

---

## Arabic / RTL Support

- [ ] `company_name_ar` and `company_name_en` stored in separate columns (bilingual storage)
- [ ] All Arabic validation messages defined in `lang/ar/suppliers.php`
- [ ] All English validation messages defined in `lang/en/suppliers.php`
- [ ] Frontend pages use `dir="rtl"` via Nuxt HTML config (not manual overrides per page)
- [ ] `VerificationStatusBadge` component displays Arabic label for each status
- [ ] Nuxt UI components used (RTL-native) — no Bootstrap or non-RTL UI library used
- [ ] Arabic company name displayed first / prominently in UI

---

## Migration Safety

- [ ] Migration file follows naming convention `YYYY_MM_DD_HHMMSS_create_supplier_profiles_table.php`
- [ ] Existing migration files are NOT modified
- [ ] `down()` method correctly drops the `supplier_profiles` table
- [ ] `supplier_profiles.user_id` foreign key has `constrained()` + `cascadeOnDelete()` or explicit behavior defined
- [ ] `supplier_profiles.verified_by` foreign key is nullable with `nullOnDelete()` to avoid orphan data
- [ ] `verification_status` column has explicit DEFAULT value (`pending`)
- [ ] `rating_avg` and `total_ratings` have appropriate DEFAULT values (0.00, 0)
- [ ] Indexes defined: `user_id` (unique), `commercial_reg` (unique), `verification_status`, `city`
- [ ] Soft deletes (`deleted_at`) column is present

---

## Test Coverage Mapped

- [ ] `tests/Unit/Services/SupplierServiceTest.php` created and covers all 12 unit test cases from spec § 9.1
- [ ] `tests/Feature/Api/V1/SupplierControllerTest.php` created and covers RBAC matrix from spec § 9.2
- [ ] `tests/Feature/Api/V1/SupplierVerificationWorkflowTest.php` created and covers all 7 transition tests from spec § 9.3
- [ ] All tests pass: `php artisan test --filter=Supplier`
- [ ] Test factories exist: `database/factories/SupplierProfileFactory.php`
- [ ] No tests rely on production database; all use SQLite in-memory or transactions

---

## Route Model Binding

- [ ] Route model binding registered for `supplier` → `SupplierProfile` in `AppServiceProvider::boot()`: `Route::model('supplier', SupplierProfile::class)`
- [ ] Binding resolves on all six `{supplier}` route segments (index excluded)
- [ ] Soft-deleted records are NOT resolvable via binding (default Eloquent behavior retained)

---

## Rating Aggregation Boundary

- [ ] `SupplierService::aggregateRatings()` is a no-op stub only — zero aggregation/write logic in this stage
- [ ] Method signature declared per spec: `aggregateRatings(int $supplierId): void`
- [ ] No rating write path exists in any controller, request, or service in STAGE_09
- [ ] `rating_avg` and `total_ratings` are excluded from `$fillable` (mass assignment blocked)

---

## Suspension Visibility

- [ ] Suspended suppliers are hidden from `GET /suppliers` for ALL actors except Admin
- [ ] Suspended supplier's products endpoint (`GET /suppliers/{id}/products`) returns `RESOURCE_NOT_FOUND` for non-admin requests
- [ ] Suspension check occurs in service layer (`list()` and `show()`), not only in policy
- [ ] Admin public-facing listing (all statuses) is explicitly differentiated from public listing (verified only) in the same `index` endpoint

---

## Unique Constraints (Dual Enforcement)

- [ ] `commercial_reg` unique constraint enforced at DB level (migration `->unique()`)
- [ ] `commercial_reg` unique constraint enforced at application level (`unique:supplier_profiles` in `StoreSupplierRequest`)
- [ ] `commercial_reg` unique rule on `UpdateSupplierRequest` excludes current record: `Rule::unique('supplier_profiles')->ignore($supplier->id)`
- [ ] `user_id` unique constraint enforced at DB level (migration `->unique()`)
- [ ] `user_id` uniqueness enforced at application level in `SupplierPolicy::create()` — checks no existing profile for the user

---

## Pagination Structure

- [ ] `GET /suppliers` response uses `data + meta` structure (not `data.data + data.meta` nesting)
- [ ] `meta` includes: `current_page`, `per_page`, `total`, `last_page`
- [ ] Pagination delegate calls `BaseApiController::paginated()` (or equivalent) to produce consistent shape
- [ ] `per_page` is capped at 100 (enforced in request validation or service layer)
- [ ] `GET /suppliers/{id}/products` also returns `data + meta` with empty array when no products exist

---

## Visibility Guards

- [ ] Unauthenticated users see only `verified` suppliers on list endpoint
- [ ] Unauthenticated users receive `RESOURCE_NOT_FOUND` for non-verified supplier detail
- [ ] Contractor can view their own profile regardless of `verification_status`
- [ ] Contractor CANNOT view another contractor's profile unless it is `verified`
- [ ] Supervising Architect and Field Engineer are treated identically to public (verified only)
- [ ] Customer is treated identically to public (verified only)
- [ ] RBAC matrix from spec § 7 is fully implemented for all seven endpoints

---

## Verified At / Verified By Immutability

- [ ] `verified_at` is NOT accepted in `StoreSupplierRequest` or `UpdateSupplierRequest`
- [ ] `verified_by` is NOT accepted in `StoreSupplierRequest` or `UpdateSupplierRequest`
- [ ] `verified_at` and `verified_by` are populated ONLY by `SupplierService::verify()`
- [ ] `verified_at` set to `now()` on verify; not modified on suspend
- [ ] `verified_by` set to the calling admin's `id` on verify; not modified on suspend

---

## Phone Validation

- [ ] `phone` validated with regex `/^05\d{8}$/` in `StoreSupplierRequest`
- [ ] `phone` validated with regex `/^05\d{8}$/` in `UpdateSupplierRequest` (when present)
- [ ] Arabic validation message `phone.regex` defined in `lang/ar/suppliers.php`
- [ ] English validation message `phone.regex` defined in `lang/en/suppliers.php`

---

## i18n Translation Keys Defined

- [ ] `lang/ar/suppliers.php` created with all keys from spec § 8
- [ ] `lang/en/suppliers.php` created with all keys from spec § 8
- [ ] Frontend `locales/ar.json` updated with `suppliers.*` keys from spec § 8
- [ ] Frontend `locales/en.json` updated with `suppliers.*` keys from spec § 8
- [ ] No hard-coded Arabic strings in Blade views or Vue templates (all via translation keys)

---

## API Versioning Compliance

- [ ] All routes are under `/api/v1/` prefix
- [ ] Route names use `api.v1.suppliers.*` naming convention
- [ ] Route file is at `routes/api/v1/suppliers.php` (not in the root `api.php` directly)
- [ ] `SupplierResource` uses `toArray()` — no raw `$this->resource` access
- [ ] API response shape matches spec § 5 exactly (no extra undocumented fields in production responses)

---

## Soft Delete on Main Entity

- [ ] `SupplierProfile` model uses `SoftDeletes` trait
- [ ] `supplier_profiles` table has `deleted_at` TIMESTAMP NULLABLE column (via migration)
- [ ] Soft-deleted suppliers are excluded from all public queries (Eloquent global scope handles this)
- [ ] Admin uses suspend (not delete) to hide a supplier; soft-deleted records are permanently invisible via API to all actors including Admin
- [ ] Repository `findById()` does NOT use `withTrashed()` — no endpoint exposes deleted profiles
- [ ] Cascade or null behavior for FK `verified_by` on supplier deletion is defined

---

## Additional Quality Gates

- [ ] `php artisan route:list | grep suppliers` shows all 7 expected routes
- [ ] `php artisan test --filter=Supplier` passes with 0 failures
- [ ] `composer run lint` (PHPStan) passes with no errors in supplier-related files
- [ ] `npm run typecheck` passes for new frontend components
- [ ] `npm run lint` passes for new frontend files
- [ ] No direct DB queries (`DB::` facade) in Controller or Service layers
- [ ] `SupplierPolicy` is tested directly or covered by feature tests

---

## Sign-Off Criteria

> All items above must be checked before this stage can be marked COMPLETE.

| Reviewer            | Date | Status  |
| ------------------- | ---- | ------- |
| Implementation      | —    | PENDING |
| Architecture review | —    | PENDING |
| QA sign-off         | —    | PENDING |
