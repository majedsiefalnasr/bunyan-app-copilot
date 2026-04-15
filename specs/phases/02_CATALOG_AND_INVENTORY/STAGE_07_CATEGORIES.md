# STAGE_07 — Categories

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Product/service category hierarchy, nested categories
> **Risk Level:** LOW

## Stage Status

Status: DRAFT
Step: plan
Risk Level: LOW
Last Updated: 2026-04-15T00:00:00Z

Scope Planned:

- 3-wave implementation roadmap (Foundation 3.5d, Frontend 3d, Testing 4d)
- Complete data model with self-referential hierarchy and optimistic locking
- 6 RESTful API endpoints with nested tree response format
- 4 reusable Vue 3 components (Tree, Form Modal, Breadcrumb, Selector)
- Admin category management page with drag-and-drop reordering
- 10+ construction categories seeder (Arabic + English)
- 85%+ test coverage with unit, feature, and E2E tests

Deferred Scope:

- Materialized path optimization (use adjacency list for MVP)
- Category-level pricing overrides
- Bulk operations
- Category export/import tools

Architecture Governance Compliance:

- ✅ Architecture Guardian: PASS (3 improvement notes)
- ✅ API Designer: PASS (minor documentation clarifications)
- RBAC enforcement confirmed
- Service layer and repository pattern architected
- Error contract compliance verified

Notes:
Technical plan complete and guardian-approved. Task generation ready.

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
