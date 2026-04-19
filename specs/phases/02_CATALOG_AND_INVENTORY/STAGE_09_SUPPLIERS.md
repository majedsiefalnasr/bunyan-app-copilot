# STAGE_09 — Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** PRODUCTION READY
> **Scope:** Supplier profiles, verification, product association
> **Risk Level:** MEDIUM

## Stage Status

Status: PRODUCTION READY
Step: closure
Risk Level: MEDIUM
Closure Date: 2026-04-19T09:15:00Z

Implementation: COMPLETE
Tasks: 39 / 39 completed

Scope Closed:

- SupplierProfile model with full PHPDoc, enum casts, soft deletes
- Migration: create_supplier_profiles_table (indexes + constraints)
- Repository pattern: SupplierRepositoryInterface + SupplierRepository
- SupplierService: create, update, verifySupplier, suspendSupplier + uniqueness guard
- SupplierController (thin, delegates to service) + 4 Form Requests
- SupplierPolicy (5 methods) registered via AppServiceProvider
- SupplierResource API resource
- Routes: suppliers.php (show uses raw /{id} per ADR-009-01)
- i18n: lang/ar/suppliers.php, lang/en/suppliers.php
- Frontend: types, composable, Pinia store, 3 components, 4 pages
- Frontend i18n: locales/ar.json, locales/en.json suppliers section
- 36 PHPUnit tests (8 unit, 28 feature) — all pass

Deferred Scope:

- File upload for logos (URL string in this stage)
- Admin notifications for new submissions
- Ratings write path (aggregation stub only)

Architecture Governance Compliance:

- ADR alignment verified (ADR-009-01: 404-not-403 visibility pattern)
- RBAC enforcement confirmed across all layers
- Service layer architecture maintained
- Error contract compliance verified
- i18n/RTL support verified

Notes:
Stage is PRODUCTION READY. No structural modifications allowed.
Modifications require a new stage.

## Objective

Implement supplier management with profiles, verification workflow, and product association.

## Scope

### Backend

- Supplier profile model (extends User role = Contractor)
- Supplier verification workflow (pending → verified → suspended)
- Supplier service (CRUD, verification, rating aggregation)
- Supplier repository with search and filtering
- Supplier API resource
- Supplier Form Request validation

### Frontend

- Supplier directory page (public)
- Supplier profile page with products, ratings
- Supplier registration form
- Supplier dashboard (Contractor role)
- Supplier management page (Admin)

### API Endpoints

| Method | Route                           | Description             |
| ------ | ------------------------------- | ----------------------- |
| GET    | /api/v1/suppliers               | List suppliers          |
| GET    | /api/v1/suppliers/{id}          | Get supplier profile    |
| PUT    | /api/v1/suppliers/{id}          | Update supplier profile |
| PUT    | /api/v1/suppliers/{id}/verify   | Verify supplier (Admin) |
| GET    | /api/v1/suppliers/{id}/products | Get supplier products   |

### Database Schema

| Table             | Columns                                                                                                                                                                |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| supplier_profiles | id, user_id, company_name_ar, company_name_en, commercial_reg, tax_number, city, district, address, phone, verification_status, verified_at, rating_avg, total_ratings |

## Dependencies

- **Upstream:** STAGE_04_RBAC_SYSTEM, STAGE_06_API_FOUNDATION
- **Downstream:** STAGE_08_PRODUCTS, STAGE_18_QUOTATIONS
