# STAGE_09 — Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Supplier profiles, verification, product association
> **Risk Level:** MEDIUM

## Stage Status

Status: IN PROGRESS
Step: analyze
Risk Level: MEDIUM
Last Updated: 2026-04-19T00:00:00Z

Drift Analysis: PASSED (all criteria)
Implementation: AUTHORIZED

Findings:

- DRIFT-001 (MEDIUM): PHPStan LSP override in SupplierRepository — mitigation: @phpstan-ignore-next-line annotations at override methods
- DRIFT-002 (LOW): Duplicate template stubs in tasks.md — REMEDIATED pre-analysis

Deferred Scope:

- File upload for logos (URL string in this stage)
- Admin notifications for new submissions
- Ratings write path (aggregation stub only)

Architecture Governance Compliance:

- Drift analysis passed — all guardian verdicts PASS
- ADR-009-01 verified: 404-not-403 pattern for visibility enforcement
- RBAC enforcement confirmed across all layers
- Error contract compliance verified

Notes:
Drift analysis complete. Implementation authorized. All findings have documented mitigations.

Architecture Governance Compliance:

- ADR-009-01 formalized and binding on T016
- Architecture Guardian: PASS (after 6 findings remediated)
- API Designer: PASS (after 5 findings remediated)
- Task set compliant — drift analysis gate required before implementation

Notes:
Atomic task set generated (39 tasks). Drift analysis gate pending.

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
