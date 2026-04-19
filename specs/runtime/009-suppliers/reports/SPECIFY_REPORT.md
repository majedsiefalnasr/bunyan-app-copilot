# Specify Report — STAGE_09 Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Generated:** 2026-04-15T00:05:00Z

## Specification Summary

| Metric                 | Value                                                                                                          |
| ---------------------- | -------------------------------------------------------------------------------------------------------------- |
| User Stories           | 8 (US1–US8)                                                                                                    |
| Acceptance Criteria    | 35+                                                                                                            |
| Technical Requirements | 12 sections (Model, Enum, Repository, Service, Controller, Requests×3, Policy, Resource, Routes, DB, Frontend) |
| Dependencies           | Upstream: STAGE_04_RBAC_SYSTEM, STAGE_06_API_FOUNDATION; Downstream: STAGE_08_PRODUCTS, STAGE_18_QUOTATIONS    |
| Open Questions         | 2                                                                                                              |

## Scope Defined

- **SupplierProfile model** extending the Contractor user role via a separate `supplier_profiles` table
- **Verification workflow**: `pending → verified ↔ suspended` (admin-controlled)
- **7 API endpoints** under `/api/v1/suppliers` (public + authenticated)
- **SupplierService** with CRUD, verification, and rating aggregation stub
- **SupplierRepository** with search/filter via Eloquent scopes
- **SupplierPolicy** with role-based authorization rules
- **API Resources**: `SupplierResource` with full JSON shape
- **Form Requests**: `StoreSupplierRequest`, `UpdateSupplierRequest`, `VerifySupplierRequest`
- **4 Frontend pages**: public directory, public profile, contractor dashboard form, admin management
- **3 Frontend components**: `SupplierCard`, `VerificationStatusBadge`, `SupplierForm`
- **Pinia store**: `useSupplierStore`
- **PHPUnit feature tests** covering full RBAC matrix + workflow transitions

## Deferred Scope

- File upload system for supplier logos (using URL string only in this stage)
- Admin notifications on new supplier submissions (deferred to notifications stage)
- Ratings write path (aggregation stub only; ratings written in a future stage)
- Product association details (stub `hasMany(Product)` only)
- Quotation integration (STAGE_18)

## Risk Assessment

| Risk                                                    | Level  | Notes                                                |
| ------------------------------------------------------- | ------ | ---------------------------------------------------- |
| RBAC complexity (public + contractor + admin endpoints) | MEDIUM | Requires careful Policy design                       |
| Soft-delete + visibility filtering                      | MEDIUM | Service-layer filter, not middleware                 |
| `commercial_reg` uniqueness constraint                  | LOW    | DB UNIQUE index + application validation             |
| Rating aggregation stub                                 | LOW    | No data written here; stub ready for STAGE_X_RATINGS |
| Frontend RTL/Arabic forms                               | LOW    | Standard Nuxt UI + i18n pattern                      |

## Checklist Status

- Requirements checklist: ✅ Created at `checklists/requirements.md` (10 categories, 47 items)

## Open Questions

1. Should admins receive notifications when a new supplier profile is submitted (`pending`)? → **Deferred to notifications stage**
2. Is `logo` expected to go through the platform file-upload system, or remain a URL string? → **Currently specced as URL; flagged for clarification in Step 2**
