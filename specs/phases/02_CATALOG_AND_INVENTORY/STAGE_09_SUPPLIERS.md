# STAGE_09 — Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Supplier profiles, verification, product association
> **Risk Level:** MEDIUM

## Stage Status

Status: DRAFT
Step: tasks
Risk Level: MEDIUM
Last Updated: 2026-04-15T00:35:00Z

Tasks Generated:

- Total: 39 atomic tasks across 6 phases
- P0 Database: 5 tasks (migration, enum, model, factory, User relationship)
- P1 Backend Core: 4 tasks (interface, repository, service, bindings)
- P2 HTTP Layer: 9 tasks (4 Form Requests, Policy, Resource, Controller, Routes)
- P3 Frontend: 10 tasks (types, composable, store, 3 components, 4 pages)
- P4 Tests + i18n: 8 tasks (seeder, 4 translation files, 3 test suites)
- Validation Pipeline: 3 tasks (lint, PHPUnit, frontend checks)
- 10 tasks parallelizable (4 Form Requests, Policy+Resource, 2 components, 4 i18n)

Deferred Scope:

- File upload for logos (URL string in this stage)
- Admin notifications for new submissions
- Ratings write path (aggregation stub only)

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
