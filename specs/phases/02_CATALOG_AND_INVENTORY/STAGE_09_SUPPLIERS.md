# STAGE_09 — Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Supplier profiles, verification, product association
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: plan
Risk Level: MEDIUM
Last Updated: 2026-04-15T00:20:00Z

Scope Planned:

- 37 file operations (33 CREATE + 4 MODIFY) across 5 phases
- Database: supplier_profiles table, SupplierVerificationStatus enum
- Backend: SupplierService, SupplierRepository, SupplierPolicy, SupplierResource
- HTTP: 4 Form Requests, Controller, Route file
- Frontend: 3 Components, 4 Pages, Pinia store, composable
- Tests + i18n: Feature tests, unit tests, translation keys

Deferred Scope:

- File upload for logos (URL string in this stage)
- Admin notifications for new submissions
- Ratings write path (aggregation stub only)

Architecture Governance Compliance:

- ADR-009-01 formalized
- Architecture Guardian: PASS (after 6 findings remediated)
- API Designer: PASS (after 5 findings remediated)
- Technical plan compliant — task generation authorized

Notes:
Technical plan complete. Task breakdown in progress.

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
