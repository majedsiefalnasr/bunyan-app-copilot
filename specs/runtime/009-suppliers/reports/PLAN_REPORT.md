# Plan Report — STAGE_09 Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Generated:** 2026-04-15T00:20:00Z

## Plan Summary

| Metric         | Value                                                         |
| -------------- | ------------------------------------------------------------- |
| New Tables     | 1 (`supplier_profiles`)                                       |
| New Endpoints  | 7 (/api/v1/suppliers\*)                                       |
| New Services   | 1 (`SupplierService`)                                         |
| New Pages      | 4 (directory, profile, contractor form, admin management)     |
| New Components | 3 (`SupplierCard`, `VerificationStatusBadge`, `SupplierForm`) |
| Total Files    | 37 (33 CREATE + 4 MODIFY)                                     |
| Phases         | 5 (P0 DB → P1 Core → P2 HTTP → P3 Frontend → P4 Tests)        |

## Architecture Decisions

| Decision                                                 | Reference                  | Summary                                                                       |
| -------------------------------------------------------- | -------------------------- | ----------------------------------------------------------------------------- |
| Supplier = Contractor + SupplierProfile                  | plan.md §P0                | No new user role; one-to-one FK from supplier_profiles to users               |
| `show()` returns 404 not 403 for invisible profiles      | ADR-009-01                 | Prevents profile existence enumeration                                        |
| `scopeVisibleTo(?User $actor)` as model scope            | plan.md §P0.3              | OR visibility logic (verified + own) owned by model scope, used by repository |
| `aggregateRatings()` is stub-only                        | plan.md §P1.3              | No rating writes in this stage; future ratings stage triggers updates         |
| Logo = URL string                                        | plan.md §P2.1              | File upload deferred to STAGE_15_FILE_MANAGEMENT                              |
| Admin create-on-behalf requires `user_id`                | plan.md §P1.3              | Service validates target user has contractor role                             |
| `suspended → verified` direct transition allowed         | spec.md §US5               | No unsuspend endpoint; admin can re-verify directly                           |
| `StoreSupplierRequest.user_id` admin-only field          | plan.md §P2.1              | Service enforces contractor role check before use                             |
| `SuspendSupplierRequest` mirrors `VerifySupplierRequest` | plan.md §P2.3a             | No request body; authorization via Policy                                     |
| Pagination: `data + meta`                                | contracts/api-contracts.md | Matches existing BaseApiController::paginated() helper                        |

## Guardian Verdicts

| Guardian              | Verdict                     | Findings Resolved                                    |
| --------------------- | --------------------------- | ---------------------------------------------------- |
| Architecture Guardian | ✅ PASS (after remediation) | 6 findings: 2 HIGH + 2 MEDIUM + 2 LOW — all resolved |
| API Designer          | ✅ PASS (after remediation) | 5 findings: 2 HIGH + 1 MEDIUM + 2 LOW — all resolved |

### Key Remediations Applied

1. **suspended → verified conflict** resolved: direct re-verification allowed (spec US5 updated)
2. **Admin create-on-behalf `user_id`** added to StoreSupplierRequest, service logic, and contracts
3. **`scopeVisibleTo(?User $actor)`** added to SupplierProfile model for OR visibility query
4. **ADR-009-01** formalized at `docs/architecture/ADR/ADR-009-01-supplier-show-404-over-403.md`
5. **`SuspendSupplierRequest`** added to HTTP layer plan
6. **Verify/suspend responses** updated to return full SupplierResource

## Risk Assessment

| Risk                                                 | Level  | Details                                      |
| ---------------------------------------------------- | ------ | -------------------------------------------- |
| RBAC visibility matrix (public + contractor + admin) | MEDIUM | Managed via scopeVisibleTo scope             |
| Soft-delete + visibility filtering                   | MEDIUM | Service-layer filter via scopeVisibleTo      |
| Admin create-on-behalf contractor role validation    | MEDIUM | Service validates contractor role before use |
| `commercial_reg` uniqueness                          | LOW    | DB UNIQUE + Form Request validation          |
| Rating aggregation stub                              | LOW    | No-op; future stage activates                |
| Logo URL validation                                  | LOW    | `url` rule in Form Request                   |

## Implementation Phases

| Phase             | Files               | Description                                                     |
| ----------------- | ------------------- | --------------------------------------------------------------- |
| P0 — Database     | 4 CREATE + 1 MODIFY | Enum, Migration, Model, Factory, User relationship              |
| P1 — Backend Core | 4 CREATE + 1 MODIFY | Repository interface/impl, Service, AppServiceProvider bindings |
| P2 — HTTP Layer   | 7 CREATE + 1 MODIFY | 4 Form Requests, Policy, Resource, Controller, Route file       |
| P3 — Frontend     | 9 CREATE + 2 MODIFY | Type, Composable, Store, 3 Components, 4 Pages                  |
| P4 — Tests + i18n | 6 CREATE + 2 MODIFY | Factory, Seeder, Feature tests, Unit tests, Translations        |
