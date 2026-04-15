# STAGE_07 — Categories

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Product/service category hierarchy, nested categories
> **Risk Level:** LOW

## Stage Status

Status: IN PROGRESS
Step: analyze
Risk Level: LOW
Last Updated: 2026-04-15T00:00:00Z

Drift Analysis: ✅ PASSED (All 10 checkpoints)
Implementation: ✅ AUTHORIZED

Scope Validated:

- ✅ 100% spec-to-plan alignment (all 8 user stories traced)
- ✅ 100% plan-to-tasks traceability (79 tasks mapped)
- ✅ RBAC fully enforced (mutations protected, reads open)
- ✅ Error contract compliant (all codes validated)
- ✅ Repository/Service pattern verified
- ✅ 85%+ test coverage achievable (30+ unit + 20+ feature + 10+ E2E)
- ✅ Database migration complete and reversible
- ✅ Frontend components designed for reusability
- ✅ Soft-delete scoping explicit and tested
- ✅ Dependencies ordered correctly (35 parallelizable tasks)

Deferred Scope:

- Materialized path optimization (use adjacency list for MVP)
- Category-level pricing overrides
- Bulk operations
- Category export/import tools

Architecture Governance Compliance:

- ✅ Architecture Guardian: PASS
- ✅ API Designer: PASS
- ✅ Drift Analysis: PASS (all patterns verified)

Notes:
All governance gates cleared. Ready for implementation. Steps 6 (Implement) and 7 (Closure) pending user action for deployment.

## Objective

Implement hierarchical category system for organizing construction products and services.

## Scope

### Backend

- Category Eloquent model with parent-child self-relation
- Category repository with tree traversal
- Category service (CRUD, reorder, move)
- Category API resource
- Category Form Request validation
- Seeder with default construction categories (e.g., مواد بناء، كهرباء، سباكة، تشطيبات)

### Frontend

- Category management page (Admin)
- Category tree component with drag-and-drop reorder
- Category breadcrumb component
- Category selector dropdown (used by products)

### API Endpoints

| Method | Route                           | Description            |
| ------ | ------------------------------- | ---------------------- |
| GET    | /api/v1/categories              | List categories (tree) |
| POST   | /api/v1/categories              | Create category        |
| GET    | /api/v1/categories/{id}         | Get category details   |
| PUT    | /api/v1/categories/{id}         | Update category        |
| DELETE | /api/v1/categories/{id}         | Delete category        |
| PUT    | /api/v1/categories/{id}/reorder | Reorder category       |

### Database Schema

| Table      | Columns                                                                                                |
| ---------- | ------------------------------------------------------------------------------------------------------ |
| categories | id, parent_id, name_ar, name_en, slug, icon, sort_order, is_active, created_at, updated_at, deleted_at |

## Dependencies

- **Upstream:** STAGE_06_API_FOUNDATION
- **Downstream:** STAGE_08_PRODUCTS
