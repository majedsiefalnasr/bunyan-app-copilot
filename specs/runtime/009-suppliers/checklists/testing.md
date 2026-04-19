# Testing Checklist — STAGE_09 Suppliers

> **Spec:** `specs/runtime/009-suppliers/spec.md`
> **Stage:** STAGE_09 — Suppliers
> **Phase:** 02_CATALOG_AND_INVENTORY
> **Last Updated:** 2026-04-15

Use this checklist before marking the stage as COMPLETE. Every test case must be passing or
explicitly marked N/A with a justification.

---

## Prerequisite: Test Infrastructure

- [ ] `database/factories/SupplierProfileFactory.php` created with all columns populated
- [ ] Factory states defined: `->pending()`, `->verified()`, `->suspended()`
- [ ] Factory uses valid Saudi phone number (`0512345678`) and valid `commercial_reg` format
- [ ] All tests use SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — no production DB
- [ ] All tests extend project `TestCase` and use `RefreshDatabase` or `DatabaseTransactions`
- [ ] Test users created with correct roles via `UserFactory` role states

---

## CHK001 — GET /suppliers — Visibility by Actor

- [ ] Unauthenticated request returns only suppliers with `verification_status = verified`
- [ ] Unauthenticated request does NOT return `pending` suppliers
- [ ] Unauthenticated request does NOT return `suspended` suppliers
- [ ] Customer request returns only `verified` suppliers
- [ ] Contractor request returns `verified` suppliers plus their own (any status)
- [ ] Supervising Architect request returns only `verified` suppliers
- [ ] Field Engineer request returns only `verified` suppliers
- [ ] Admin request returns all suppliers (pending + verified + suspended)
- [ ] Admin can filter by `verification_status=pending` and receive only pending records

---

## CHK002 — GET /suppliers — Filters and Pagination

- [ ] `city` filter returns only suppliers matching that city (case-insensitive acceptable)
- [ ] `district` filter returns only suppliers matching that district
- [ ] `search` filter matches `company_name_ar` via LIKE
- [ ] `search` filter matches `company_name_en` via LIKE
- [ ] `per_page=5` returns at most 5 records
- [ ] `per_page=200` is capped at 100 records
- [ ] Response `meta` contains `current_page`, `per_page`, `total`, `last_page`
- [ ] `verification_status` filter ignored (or 403) for non-admin callers

---

## CHK003 — GET /suppliers/{id} — Profile Visibility

- [ ] Unauthenticated request for `verified` supplier returns 200 with full profile
- [ ] Unauthenticated request for `pending` supplier returns `RESOURCE_NOT_FOUND` (404)
- [ ] Unauthenticated request for `suspended` supplier returns `RESOURCE_NOT_FOUND` (404)
- [ ] Owning contractor request for own `pending` profile returns 200
- [ ] Owning contractor request for own `suspended` profile returns 200
- [ ] Different contractor request for another contractor's `pending` profile returns 404
- [ ] Different contractor request for another contractor's `suspended` profile returns 404
- [ ] Admin request for `pending` supplier returns 200
- [ ] Admin request for `suspended` supplier returns 200
- [ ] Request for non-existent ID returns `RESOURCE_NOT_FOUND` (404)
- [ ] Request for soft-deleted supplier returns `RESOURCE_NOT_FOUND` (404)

---

## CHK004 — POST /suppliers — Create Profile

- [ ] Contractor with valid payload creates profile with `verification_status = pending`
- [ ] Created profile response is HTTP 201 with full `SupplierResource`
- [ ] Customer role receives `RBAC_ROLE_DENIED` (403)
- [ ] Supervising Architect role receives `RBAC_ROLE_DENIED` (403)
- [ ] Field Engineer role receives `RBAC_ROLE_DENIED` (403)
- [ ] Unauthenticated request receives 401
- [ ] Admin can create profile on behalf of a contractor
- [ ] Missing required field (`company_name_ar`) returns `VALIDATION_ERROR` (422) with field detail
- [ ] Invalid phone format (e.g., `+966512345678`) returns `VALIDATION_ERROR` with `phone` in details
- [ ] Non-URL logo value returns `VALIDATION_ERROR` with `logo` in details
- [ ] `commercial_reg` already in use returns `VALIDATION_ERROR` (not `CONFLICT_ERROR`)
- [ ] Contractor creating second profile returns `CONFLICT_ERROR` (409)
- [ ] `verification_status` in request body is ignored — profile still created as `pending`

---

## CHK005 — PUT /suppliers/{id} — Update Profile

- [ ] Contractor updates own profile with partial payload — only sent fields changed
- [ ] Contractor updating own profile returns 200 with updated `SupplierResource`
- [ ] Contractor cannot update another contractor's profile — returns `AUTH_UNAUTHORIZED` (403)
- [ ] Admin can update any supplier profile
- [ ] Unauthenticated request returns 401
- [ ] Including `verification_status` in body does NOT change the status
- [ ] Including `verified_at` in body does NOT change the value
- [ ] Including `verified_by` in body does NOT change the value
- [ ] Including `user_id` in body does NOT change the owning user
- [ ] Invalid phone format returns `VALIDATION_ERROR` (422)
- [ ] `commercial_reg` unique rule excludes the current record (can re-submit same value)
- [ ] `commercial_reg` conflicting with a DIFFERENT supplier returns `VALIDATION_ERROR`

---

## CHK006 — PUT /suppliers/{id}/verify — Verify Supplier

- [ ] Admin verifies `pending` supplier — status becomes `verified`, `verified_at` set, `verified_by` set
- [ ] Response is 200 with `verification_status`, `verified_at`, `verified_by` fields
- [ ] `verified_at` is a valid UTC timestamp close to request time
- [ ] `verified_by` matches the admin's user ID
- [ ] Admin verifies already-`verified` supplier — idempotent, returns 200 (no error)
- [ ] Admin verifies `suspended` supplier — status becomes `verified` (re-verify allowed)
- [ ] `verified_at` is updated on re-verify (not left as the original timestamp)
- [ ] Contractor calling verify endpoint returns `RBAC_ROLE_DENIED` (403)
- [ ] Customer calling verify endpoint returns `RBAC_ROLE_DENIED` (403)
- [ ] Unauthenticated request returns 401
- [ ] Non-existent supplier ID returns `RESOURCE_NOT_FOUND` (404)

---

## CHK007 — PUT /suppliers/{id}/suspend — Suspend Supplier

- [ ] Admin suspends `verified` supplier — status becomes `suspended`
- [ ] Response is 200 with updated `verification_status`
- [ ] Suspended supplier no longer appears in `GET /suppliers` for public callers
- [ ] Suspended supplier's detail (`GET /suppliers/{id}`) returns 404 for public callers
- [ ] Admin suspending already-`suspended` supplier is idempotent — returns 200 (no error)
- [ ] Admin can suspend a `pending` supplier (`pending → suspended` is allowed per spec § 6.1)
- [ ] Contractor calling suspend endpoint returns `RBAC_ROLE_DENIED` (403)
- [ ] Customer calling suspend endpoint returns `RBAC_ROLE_DENIED` (403)
- [ ] Unauthenticated request returns 401

---

## CHK008 — GET /suppliers/{id}/products — Products Endpoint

- [ ] Unauthenticated request for `verified` supplier returns 200 with `data: []` and correct `meta`
- [ ] `meta.total` is `0` when no products exist
- [ ] Unauthenticated request for `pending` supplier returns `RESOURCE_NOT_FOUND` (404)
- [ ] Unauthenticated request for `suspended` supplier returns `RESOURCE_NOT_FOUND` (404)
- [ ] Owning contractor can access own supplier's products regardless of status
- [ ] Admin can access products for any status

---

## CHK009 — Soft Delete Behavior

- [ ] Soft-deleted supplier does NOT appear in `GET /suppliers` for any actor including Admin
- [ ] Soft-deleted supplier returns `RESOURCE_NOT_FOUND` on `GET /suppliers/{id}` for any actor
- [ ] Soft-deleted supplier returns `RESOURCE_NOT_FOUND` on `GET /suppliers/{id}/products` for any actor
- [ ] `withTrashed()` is NOT used in any repository query to "recover" deleted records via API
- [ ] After soft delete, the `commercial_reg` value is still blocked for new registrations (unique constraint applies to soft-deleted rows)

---

## CHK010 — SupplierService Unit Tests

- [ ] `SupplierServiceTest` covers `list()` — applies verified-only filter for non-admin
- [ ] `SupplierServiceTest` covers `list()` — passes all filters to repository for admin
- [ ] `SupplierServiceTest` covers `show()` — throws not-found for non-visible supplier
- [ ] `SupplierServiceTest` covers `show()` — returns profile for own contractor
- [ ] `SupplierServiceTest` covers `create()` — throws conflict for duplicate user
- [ ] `SupplierServiceTest` covers `create()` — sets `user_id` from actor, not request
- [ ] `SupplierServiceTest` covers `update()` — does NOT pass `verification_status` to repository
- [ ] `SupplierServiceTest` covers `verify()` — sets `verified_at` and `verified_by`
- [ ] `SupplierServiceTest` covers `verify()` — idempotent for already-verified
- [ ] `SupplierServiceTest` covers `verify()` — throws invalid transition for `suspended` (if applicable per spec)
- [ ] `SupplierServiceTest` covers `suspend()` — idempotent for already-suspended
- [ ] `SupplierServiceTest` covers `aggregateRatings()` — no-op stub, does not throw

---

## CHK011 — SupplierRepository Unit Tests

- [ ] `SupplierRepositoryTest` covers `paginate()` — `verified` filter applied via `scopeVerified`
- [ ] `SupplierRepositoryTest` covers `paginate()` — city filter returns correct subset
- [ ] `SupplierRepositoryTest` covers `paginate()` — search term matches `company_name_ar`
- [ ] `SupplierRepositoryTest` covers `paginate()` — search term matches `company_name_en`
- [ ] `SupplierRepositoryTest` covers `findById()` — returns null for soft-deleted record
- [ ] `SupplierRepositoryTest` covers `findByUserId()` — returns correct profile for given contractor
- [ ] `SupplierRepositoryTest` covers `findByUserId()` — returns null when no profile exists
- [ ] `SupplierRepositoryTest` covers `updateVerificationStatus()` — correct enum and timestamps set
- [ ] `SupplierRepositoryTest` covers `create()` — all fillable fields persisted correctly
- [ ] `SupplierRepositoryTest` covers `delete()` — soft deletes (not hard deletes)

---

## CHK012 — Verification Workflow Integration Tests

- [ ] `pending → verified` transition succeeds
- [ ] `verified → suspended` transition succeeds
- [ ] `suspended → verified` transition succeeds (re-verify)
- [ ] `pending → suspended` transition succeeds (admin rejection path)
- [ ] `verified → pending` transition returns `WORKFLOW_INVALID_TRANSITION` (422)
- [ ] `suspended → pending` via verify endpoint returns appropriate error
- [ ] Each valid transition updates DB record and response shape correctly

---

## CHK013 — Error Contract Validation

- [ ] All 200/201 responses have `{ success: true, data: {...}, error: null }` shape
- [ ] All 4xx/5xx responses have `{ success: false, data: null, error: { code, message, details } }` shape
- [ ] `VALIDATION_ERROR` responses include field-level `details` object
- [ ] `RESOURCE_NOT_FOUND`, `CONFLICT_ERROR`, `RBAC_ROLE_DENIED`, `AUTH_UNAUTHORIZED` have `details: null`
- [ ] No 500 response in any test scenario exposes stack trace to client

---
