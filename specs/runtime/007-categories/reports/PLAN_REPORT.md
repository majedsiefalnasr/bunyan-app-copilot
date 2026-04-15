# Plan Report — STAGE_07_CATEGORIES

> **Phase:** 02_CATALOG_AND_INVENTORY | **Generated:** 2026-04-15

## Plan Summary

| Metric               | Value                                               |
| -------------------- | --------------------------------------------------- |
| New Tables           | 1 (categories with self-referential FK)             |
| New Endpoints        | 6 (list/tree, create, get, update, delete, reorder) |
| New Services         | 1 (CategoryService with 8 core methods)             |
| New Repositories     | 1 (CategoryRepository with tree traversal)          |
| New Components       | 4 (Tree, Form Modal, Breadcrumb, Selector)          |
| New Pages            | 1 (Admin Category Management)                       |
| Implementation Waves | 3 (Foundation, Frontend, Testing)                   |
| Estimated Effort     | 10.5 days (3.5 + 3 + 4)                             |

## Architecture Decisions

### 1. Self-Referential Hierarchy ✅

- Parent-child relationship via `parent_id` foreign key (nullable for root categories)
- Adjacency list model — no materialized path or LTREE (chosen for MVP simplicity)
- **Rationale**: Straightforward SQL, familiar to team, adequate for typical category depth

### 2. Optimistic Locking for Concurrent Edits ✅

- Added `version` field to Category model
- Reorder endpoint validates version before updating
- **Rationale**: Prevents data loss when two admins reorder simultaneously; client can refresh + retry

### 3. Nested Tree Response Format ✅

- GET /api/v1/categories returns recursive `children` arrays (not flat + parent_id)
- **Rationale**: Simplifies frontend rendering, aligns with modern REST conventions, reduces client-side tree traversal logic

### 4. Soft-Delete Scoping with Query Scopes ✅

- All queries use `withoutTrashed()` by default
- Admin-only routes access via `withTrashed()` scope
- **Rationale**: Keeps queries clean, prevents accidental exposure of deleted categories to users

### 5. Slug Immutability ✅

- Slug generated at creation, never changes
- Name updates (`name_ar`, `name_en`) are independent
- **Rationale**: Preserves URL stability for external links and SEO

### 6. RBAC Enforcement at Middleware Level ✅

- Admin-only routes protected by `auth:sanctum` + `admin_only` middleware
- Readable endpoints (GET) accessible to authenticated users
- **Rationale**: Centralized, enforceable, follows Bunyan RBAC pattern

### 7. Service Layer Architecture ✅

- CategoryService contains all business logic (create, update, delete, reorder, tree traversal)
- Controllers remain thin (input validation + service delegation)
- Repository handles all database queries
- **Rationale**: Clean separation of concerns, fully testable, maintainable

## Implementation Roadmap

### Wave 1: Backend Foundation (3.5 days)

**Tasks**:

- Database migration (categories table with self-referential FK, indexes)
- Eloquent Category model with relationships and scopes
- CategoryRepository with tree methods (getTree, getChildren, getAncestors, reorder)
- CategoryService with business logic (create, update, delete, move, reorder)
- StoreCategoryRequest and UpdateCategoryRequest for validation
- CategoryResource for API responses (with recursive children)
- CategoryController with 6 endpoints
- API routes with RBAC middleware
- CategorySeeder with 10+ default construction categories

**Deliverables**: Full backend API, passing feature tests

### Wave 2: Frontend Components & Admin UI (3 days)

**Tasks**:

- API composables for categories (fetch, create, update, delete, reorder)
- Pinia store for category state management
- CategoryTree component (recursive, drag-drop via @dnd-kit, edit/delete buttons)
- CategoryTreeNode sub-component (recursive rendering with indentation)
- CategoryFormModal component (VeeValidate + Zod validation)
- CategorySelector dropdown component (search, tree, used in product forms)
- CategoryBreadcrumb component (ancestor chain navigation)
- /admin/categories page with tree view and management UI
- Full RTL/Arabic support throughout

**Deliverables**: Admin UI fully functional, components reusable for other pages

### Wave 3: Testing & Quality Assurance (4 days)

**Tasks**:

- Unit tests (slug generation, circular reference prevention, tree traversal logic)
- Feature tests (RBAC enforcement, API contract validation, optimistic locking, soft-delete scoping)
- E2E tests (Playwright) for full user workflows (create, nest, reorder, delete, Arabic rendering)
- Performance tests (1000-category tree response <500ms, selector search <1s)
- Accessibility tests (WCAG 2.1 AA compliance, keyboard navigation, screen reader support)

**Deliverables**: 85%+ test coverage, performance benchmarks met, WCAG AA compliance

## Guardian Verdicts

Guardian validation is pending. Awaiting verdicts from:

- **Architecture Guardian** — Validate layering, RBAC, service/repository pattern compliance
- **API Designer** — Validate endpoint contracts, error handling, response formats

## Risk Assessment

| Risk Level | Count | Details                                                                |
| ---------- | ----- | ---------------------------------------------------------------------- |
| LOW        | 3     | • Soft-delete query scoping (well-tested pattern)                      |
| LOW        | 1     | • Slug immutability (no generation after create)                       |
| LOW        | 1     | • RTL Arabic support (handled by Nuxt UI components)                   |
| MEDIUM     | 1     | • N+1 query prevention (mitigated by eager loading, verified in tests) |
| MEDIUM     | 1     | • Concurrent reorder conflicts (mitigated by optimistic locking)       |

**Highest Risk**: Concurrent reorder requests → **Mitigation**: Version field + 409 conflict response

## Dependency Analysis

### Upstream (Required)

- ✅ STAGE_06_API_FOUNDATION — Error contract, RBAC middleware, form requests, API resources
- ✅ Database connection established (MySQL)

### Parallel (Can run simultaneously)

- Backend Wave 1 and Frontend Wave 2 are independent

### Downstream (Blocked until Categories complete)

- ⏳ STAGE_08_PRODUCTS — Requires category endpoints and CategorySelector component for product form

## Quality Metrics

| Metric                | Target         | Status   |
| --------------------- | -------------- | -------- |
| Test Coverage         | ≥85%           | Planned  |
| API Response Time     | <500ms         | Planned  |
| Component Reusability | 4 shared       | Designed |
| RBAC Coverage         | 100%           | Designed |
| RTL Support           | Full           | Designed |
| Database Performance  | <100ms queries | Indexed  |

## Next Steps

→ **Step 4 — Tasks**: Generate atomic task breakdown for implementation  
→ **Step 5 — Analyze**: Run drift analysis and guardian validation gates  
→ **Step 6 — Implement**: Execute 3-wave implementation roadmap

**Status**: ✅ **READY FOR TASK GENERATION**
