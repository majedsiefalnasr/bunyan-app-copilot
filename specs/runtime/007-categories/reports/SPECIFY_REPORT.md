# Specify Report — STAGE_07_CATEGORIES

> **Phase:** 02_CATALOG_AND_INVENTORY | **Generated:** 2026-04-15

## Specification Summary

| Metric                  | Value                                                      |
| ----------------------- | ---------------------------------------------------------- |
| User Stories            | 8 (P1: 2, P2: 4, P3: 2)                                    |
| Acceptance Criteria     | 18 scenarios across all stories                            |
| Functional Requirements | 20+ backend, frontend, and API items                       |
| API Endpoints           | 6 (list/tree, create, get, update, delete, reorder)        |
| Open Questions          | 3 clarifications (slug, cascades, soft-delete interaction) |
| Components              | 4 reusable Vue 3 components                                |
| Database Tables         | 1 (categories table)                                       |
| Risk Level              | LOW                                                        |

## Scope Defined

**In Scope**:

- Category Eloquent model with self-referential parent-child hierarchy (parent_id)
- Category repository with tree traversal methods (getTree, getChildren, getAncestors, etc.)
- Category service layer handling CRUD, reorder, move operations
- Category API resource and Form Request validation
- Admin category management page with tree UI + drag-and-drop reordering
- Category breadcrumb component (for product pages)
- Category selector dropdown component (for product form)
- 6 RESTful API endpoints (list/tree, create, get, update, delete, reorder)
- Database seeder with 10+ default construction categories (Arabic + English)
- Full RBAC enforcement (admin-only writes, authenticated read)
- Soft deletes with deleted_at timestamps
- RTL/Arabic bilingual support (name_ar, name_en)
- Comprehensive unit + feature tests
- Query optimization with composite indexes

## Deferred Scope

**Out of Scope**:

- Materialized path optimization (using ltree or path encoding) — prefer adjacency list for MVP
- Category-level pricing overrides — pricing belongs to products
- Category bulk operations — covered by individual CRUD operations
- Category export/import tools — deferred to admin panel enhancements phase
- Multi-level permission hierarchy (e.g., regional category managers) — MVP assumes single admin group

## Risk Assessment

**Overall Risk**: 🟢 **LOW**

| Risk              | Level | Mitigation                                     |
| ----------------- | ----- | ---------------------------------------------- |
| Circular parent   | LOW   | Validation in service layer prevents loops     |
| N+1 queries       | LOW   | Eager loading + composite indexes planned      |
| Soft-delete UX    | MED   | Behavior TBD for products linked to deleted    |
| Unicode rendering | LOW   | RTL tested in Playwright E2E suite             |
| Concurrent moves  | LOW   | sort_order recalculation locked to transaction |

## Architecture Alignment

✅ **RBAC**: Admin-only category management enforced at middleware level
✅ **Repository Pattern**: CategoryRepository handles all data access
✅ **Service Layer**: CategoryService contains business logic (reorder, move, tree traversal)
✅ **Error Contract**: All endpoints return standard error response format
✅ **i18n**: Full Arabic/English support with bilingual database fields

## Checklist Status

✅ Requirements checklist created at [checklists/requirements.md](checklists/requirements.md)

**Checklist Coverage**:

- Backend: 60+ items (model, repository, service, controller, tests)
- Frontend: 50+ items (components, store, E2E tests, accessibility)
- Database: 10+ items (migration, indexes, seeding, backup)
- API: 8+ items (contract validation, error handling, documentation)

## Next Steps

→ **Step 2 — Clarify**: Address 3 flagged ambiguities and generate technical checklists
→ **Step 3 — Plan**: Create implementation plan with dependencies
→ **Step 4 — Tasks**: Generate atomic task breakdown

**Status**: ✅ **READY FOR CLARIFICATION**
