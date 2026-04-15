# STAGE_07 — Categories

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Status:** NOT STARTED
> **Scope:** Product/service category hierarchy, nested categories
> **Risk Level:** LOW

## Stage Status

Status: IN PROGRESS
Step: tasks
Risk Level: LOW
Last Updated: 2026-04-15T00:00:00Z

Tasks Generated:

- Total: 79 atomic tasks organized in 3 waves
- Wave 1 (Backend): 45 tasks — 8 HIGH, 13 MEDIUM, 24 LOW risk
- Wave 2 (Frontend): 19 tasks — UI components, admin page, i18n
- Wave 3 (Testing): 15 tasks — unit, E2E, performance, accessibility
- Estimated Effort: 10.5 days (3.5 + 3 + 4)
- Parallelizable: 35 tasks (44%)
- MVP Checkpoint: After Wave 1 backend (T045), all core features working

Deferred Scope:

- Materialized path optimization (use adjacency list for MVP)
- Category-level pricing overrides
- Bulk operations
- Category export/import tools

Architecture Governance Compliance:

- All tasks traced to user stories (US1-6)
- Critical path identified: T001→T002→T003→T004→T006-T013
- Guardian verdicts documented (PASS)
- High-risk tasks flagged for experienced developers

Notes:
Atomic tasks ready for implementation. Drift analysis pending.

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
