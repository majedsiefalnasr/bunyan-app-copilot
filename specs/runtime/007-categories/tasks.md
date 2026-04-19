# Tasks: Product Category Hierarchy (007-Categories)

**Input**: Design documents from `/specs/runtime/007-categories/`
**Prerequisites**: plan.md (tech stack, architecture), spec.md (8 user stories, priorities), data-model.md (schema), contracts/api.md (endpoints)
**Output**: Fully functional category hierarchy system with admin UI and full RBAC
**Timeline**: 5-7 working days (Wave 1: 2d, Wave 2: 2d, Wave 3: 1-2d)
**Coverage Target**: 85%+ test coverage

**Format**: `- [ ] T### [P?] [US#] Description with exact file path`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[US#]**: User story label (US1-US8) or omit for setup tasks
- **Path**: Exact file location where code lives

---

## Phase 1: Database & Eloquent Foundation (Backend Infrastructure Setup)

**Purpose**: Database schema, migrations, and core ORM layer
**Dependencies**: None - can start immediately

### Database Migration & Schema

- [x] T001 [P] Create migration file `backend/database/migrations/2026_04_15_000000_create_categories_table.php` with full schema (parent_id FK, slug unique index, composite indexes on parent_sort_order_active)
- [x] T002 [P] Seed default categories table with 10+ sample categories (building materials, electrical, plumbing, hardware, tools) in `backend/database/seeders/CategorySeeder.php`
- [x] T003 [P] Create database indexes and verify performance on 1000+ categories query in migration

### Eloquent Model

- [x] T004 [P] Create Category model at `backend/app/Models/Category.php` with:
  - SoftDeletes trait
  - Relationships: parent (BelongsTo), children (HasMany)
  - Scopes: active(), roots(), leaves(), ordered(), forTree()
  - Casts for parent_id, is_active, version
  - Methods: getAncestors(), getDescendants(), isAncestorOf(), isDescendantOf()

---

## Phase 2: Repository & Service Layer (Business Logic)

**Purpose**: Data access and business logic layers
**Dependencies**: Depends on Phase 1 (Model must exist)

### Repository Layer

- [x] T005 [P] Create CategoryRepository at `backend/app/Repositories/CategoryRepository.php` with methods:
  - getTree(includeDeleted, activeOnly): Collection
  - getChildren(parentId, activeOnly): Collection
  - getAncestors(categoryId): Collection
  - getDescendants(categoryId): Collection
  - findById(id): ?Category
  - Eager loading strategy to prevent N+1 queries

- [x] T006 [P] Implement reorder logic in CategoryRepository:
  - reorder(categoryId, newSortOrder): Category
  - Recalculate sort_order for siblings
  - Preserve unchanged sibling order
  - Update only affected siblings

- [x] T007 [P] Implement tree retrieval in CategoryRepository:
  - Use WITH recursive CTE or recursive children loading
  - Support active_only filter
  - Support include_deleted for admins
  - Return nested children arrays for API response

### Service Layer

- [x] T008 Create CategoryService at `backend/app/Services/CategoryService.php` with:
  - create(data): Category - business validation, slug generation, sort_order assignment
  - update(id, data, version): Category - optimistic locking, circular ref check
  - delete(id): bool - soft delete via CategoryRepository
  - restore(id): bool - restore soft-deleted category
  - reorder(id, newSortOrder, version): Category - delegate to repository
  - move(id, newParentId, version): Category - move category to different parent

- [x] T009 Implement business logic in CategoryService:
  - Slug generation from name_en with collision detection
  - Circular reference prevention before accepting parent_id
  - Optimistic locking: check version match before update
  - Transaction wrapping for all mutations (DB::transaction)
  - Event dispatch: CategoryCreated, CategoryUpdated, CategoryDeleted

---

## Phase 3: Form Requests & Validation

**Purpose**: HTTP input validation
**Dependencies**: Depends on Model existence

### Form Request Classes

- [x] T010 [P] Create StoreCategoryRequest at `backend/app/Http/Requests/StoreCategoryRequest.php` with rules:
  - name_ar: required|string|min:2|max:100
  - name_en: required|string|min:2|max:100
  - parent_id: nullable|integer|exists:categories,id,deleted_at,NULL (no circular refs)
  - icon: nullable|string|max:50
  - sort_order: nullable|integer|min:0
  - is_active: nullable|boolean

- [x] T011 [P] Create UpdateCategoryRequest at `backend/app/Http/Requests/UpdateCategoryRequest.php` with:
  - All fields optional
  - version field for optimistic locking mandatory for update
  - parent_id change validation (circular ref check)

---

## Phase 4: API Resources & Transformation

**Purpose**: API response transformation
**Dependencies**: Depends on Model existence

### API Resources

- [x] T012 [P] Create CategoryResource at `backend/app/Http/Resources/CategoryResource.php` with:
  - Transform all category fields (id, parent_id, name_ar, name_en, slug, icon, sort_order, is_active, version, timestamps)
  - Recursive children transformation (if children key exists)
  - Support tree format with nested children arrays

- [x] T013 [P] Create CategoryCollection at `backend/app/Http/Resources/CategoryCollection.php` for:
  - Wrapping tree responses
  - Preserving nested structure in collection response

---

## Wave 1: User Story 1 - Create & Organize Top-Level Categories (P1)

**Goal**: Admins can create foundational top-level categories for organization
**Independent Test**: Admin creates ≥3 categories (Building Materials, Electrical, Plumbing) and retrieves them via API with correct sort_order and slug

### Implementation

- [x] T014 [US1] Create CategoryController at `backend/app/Http/Controllers/CategoryController.php` with store() method for POST /api/v1/categories
  - Inject CategoryService
  - Validate via StoreCategoryRequest
  - Call service.create(validated_data)
  - Transform response via CategoryResource
  - Return 201 Created with success contract

- [x] T015 [US1] Implement CategoryController::index() for GET /api/v1/categories
  - Query via CategoryRepository::getTree(activeOnly=true)
  - Support parent_id query parameter filter
  - Transform via CategoryCollection + CategoryResource
  - Return tree structure with nested children

- [x] T016 [US1] Implement validation in StoreCategoryRequest:
  - parent_id must not already exist (no circular ref check needed for top-level where parent_id=null)
  - slug must be unique (validated implicitly after service generates slug)
  - Set default is_active=true, sort_order to max+1

### Testing for US1

- [x] T017 [P] [US1] Unit test for CategoryService::create in `backend/tests/Unit/Services/CategoryServiceTest.php`:
  - Test slug generation from name_en
  - Test sort_order assignment as max+1
  - Test CategoryCreated event dispatch
  - Test returns Category model

- [x] T018 [P] [US1] Feature test for CategoryController::store in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - Admin can POST /api/v1/categories with valid data → 201 Created
  - Non-admin cannot create (403 RBAC_ROLE_DENIED)
  - Invalid name_ar → 422 VALIDATION_ERROR
  - Response follows standard contract (success, data, error)

- [x] T019 [P] [US1] Feature test for CategoryController::index in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - GET /api/v1/categories returns tree structure (children arrays)
  - Filter by parent_id=null returns top-level only
  - active_only=true excludes inactive categories
  - Response includes correct count and structure

**Checkpoint**: User Story 1 complete - admin can create and list top-level categories

---

## Wave 1: User Story 2 - Create Nested Sub-Categories (P1)

**Goal**: Admins create multi-level hierarchy by assigning parent_id
**Independent Test**: Admin creates parent then ≥2 children under it. Tree API returns correct parent-child links. UI renders indented hierarchy.

### Implementation

- [x] T020 [US2] Implement parent_id validation in StoreCategoryRequest:
  - parent_id must exist: exists:categories,id,deleted_at,NULL
  - CategoryService must reject circular references
  - Prevent self-referential parent_id=id

- [x] T021 [US2] Implement circular reference prevention in CategoryService::create:
  - Before create, check if parent_id creates cycle
  - Query ancestors of parent_id, verify current is not among them
  - Throw WORKFLOW_INVALID_TRANSITION error if cycle detected

- [x] T022 [US2] Implement parent_id support in CategoryController::index:
  - Support query param ?parent_id=X to filter children only
  - Or return full tree by default (parent_id=null auto-included in filter)

### Testing for US2

- [x] T023 [P] [US2] Unit test for circular reference prevention in `backend/tests/Unit/Services/CategoryServiceTest.php`:
  - Create parent → child → grandchild chain
  - Attempt to set grandchild.parent_id = child fails ✓
  - Attempt to set parent.parent_id = grandchild fails ✓
  - Verification: getAncestors does not include self

- [x] T024 [P] [US2] Feature test for nested category creation in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - POST /api/v1/categories with valid parent_id → 201
  - parent_id=invalid → 422 RESOURCE_NOT_FOUND
  - parent_id=self → 422 WORKFLOW_INVALID_TRANSITION (circular)
  - Tree API returns children nested under parent

- [x] T025 [P] [US2] Feature test for CategoryController::show in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - GET /api/v1/categories/{id} returns single category with children
  - children array is recursive (grandchildren included)

**Checkpoint**: User Stories 1 & 2 complete - category hierarchy fully nestable

---

## Wave 1: User Story 3 - Reorder Categories Within Same Level (P2)

**Goal**: Admin drag-drop reorders categories at same level (siblings only)
**Independent Test**: Admin moves category 3 to position 1 within same parent. sort_order recalculated. API returns 200 with updated category.

### Implementation

- [x] T026 [US3] Implement CategoryController::reorder() for PUT /api/v1/categories/{id}/reorder:
  - Inject CategoryService
  - Validate via UpdateCategoryRequest (requires version for optimistic lock)
  - Call service.reorder(id, newSortOrder, version)
  - Transform via CategoryResource
  - Return 200 OK with updated category

- [x] T027 [US3] Implement reorder business logic in CategoryService::reorder:
  - Check version match for optimistic locking
  - Query siblings (same parent_id)
  - Recalculate sort_order values within sibling group
  - Increment updated_at and version++
  - Dispatch CategoryReordered event

- [x] T028 [US3] Implement reorder repository method in CategoryRepository::reorder:
  - Use transaction for atomic sibling recalculation
  - Update affected siblings' sort_order
  - Prevent gaps in sort_order sequence

### Testing for US3

- [x] T029 [P] [US3] Unit test for CategoryService::reorder in `backend/tests/Unit/Services/CategoryServiceTest.php`:
  - Sibling[1,2,3] exist with sort_order[0,1,2]
  - Reorder sibling[3] to position 0 → sort_order become [1,2,0] ✓
  - version incremented ✓
  - CategoryReordered event dispatched ✓

- [x] T030 [P] [US3] Feature test for CategoryController::reorder in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - PUT /api/v1/categories/{id}/reorder with valid newSortOrder → 200
  - version mismatch → 409 CONFLICT_ERROR (optimistic lock)
  - Missing version → 422 VALIDATION_ERROR
  - Non-admin → 403 RBAC_ROLE_DENIED

**Checkpoint**: Reordering tested and working

---

## Wave 1: User Story 4 - Move Category to Different Parent (P2)

**Goal**: Admin restructures hierarchy by moving category + descendants to new parent
**Independent Test**: Admin moves "Cables" from "Electrical" to "Hardware". All descendants move. parent_id updated. Tree API shows new hierarchy.

### Implementation

- [x] T031 [US4] Implement CategoryController::move() for PUT /api/v1/categories/{id}/move:
  - Inject CategoryService
  - Validate via UpdateCategoryRequest (requires new_parent_id)
  - Call service.move(id, newParentId, version)
  - Transform via CategoryResource
  - Return 200 OK with updated category

- [x] T032 [US4] Implement move logic in CategoryService::move:
  - Check version match for optimistic locking
  - Check new_parent_id does not create cycle (validate circular ref again)
  - Update parent_id on category
  - Recalculate sort_order if new parent has different sibling count
  - Dispatch CategoryMoved event

- [x] T033 [US4] Implement parent_id change in UpdateCategoryRequest:
  - new_parent_id field: nullable|integer|exists:categories,id,deleted_at,NULL
  - Only validates existence; service validates cycles

### Testing for US4

- [x] T034 [P] [US4] Unit test for CategoryService::move in `backend/tests/Unit/Services/CategoryServiceTest.php`:
  - Move child from parent1 to parent2 → parent_id updated ✓
  - Descendants move with parent (implicit - no extra update needed)
  - Attempt to move parent into descendant → WORKFLOW_INVALID_TRANSITION ✓

- [x] T035 [P] [US4] Feature test for CategoryController::move in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - PUT /api/v1/categories/{id}/move with new_parent_id → 200
  - new_parent_id=self → 422 WORKFLOW_INVALID_TRANSITION
  - version mismatch → 409 CONFLICT_ERROR

**Checkpoint**: Hierarchy restructuring complete

---

## Wave 1: User Story 5 - Edit Category Details (P2)

**Goal**: Admin updates metadata (names, icon, is_active status)
**Independent Test**: Admin edits name_ar, icon, is_active. Changes persist. API returns updated record with new updated_at.

### Implementation

- [x] T036 [US5] Implement CategoryController::update() for PUT /api/v1/categories/{id}:
  - Inject CategoryService
  - Validate via UpdateCategoryRequest
  - Call service.update(id, data, version)
  - Transform via CategoryResource
  - Return 200 OK with updated category

- [x] T037 [US5] Implement update logic in CategoryService::update:
  - Check version match for optimistic locking
  - Allow name_ar, name_en, icon, is_active changes
  - Slug is immutable (do not regenerate)
  - Increment version++, update updated_at
  - Dispatch CategoryUpdated event

### Testing for US5

- [x] T038 [P] [US5] Feature test for CategoryController::update in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - PUT /api/v1/categories/{id} with name_en change → 200, name persisted ✓
  - PUT with is_active=false → category marked inactive ✓
  - slug ignored in request (immutable) ✓
  - version mismatch → 409 CONFLICT_ERROR

**Checkpoint**: Data editing complete

---

## Wave 1: User Story 6 - Soft-Delete Category (P3)

**Goal**: Admin marks category as deleted (soft delete via deleted_at) preserving data
**Independent Test**: Admin soft-deletes category. deleted_at set. Category hidden from default queries. Admin can query with include_deleted.

### Implementation

- [x] T039 [US6] Implement CategoryController::destroy() for DELETE /api/v1/categories/{id}:
  - Inject CategoryService
  - Call service.delete(id)
  - Return 200 OK with empty success response

- [x] T040 [US6] Implement soft delete logic in CategoryService::delete:
  - Call repository.delete(id) which triggers Eloquent softDelete()
  - Set deleted_at timestamp
  - Dispatch CategoryDeleted event
  - Return boolean success

- [x] T041 [US6] Implement soft delete scoping in Category model:
  - Ensure all queries by default exclude deleted (SoftDeletes trait handles)
  - Support withTrashed() for admin queries
  - Active scope includes whereNull('deleted_at')

### Testing for US6

- [x] T042 [P] [US6] Feature test for soft delete in `backend/tests/Feature/Http/Controllers/CategoryControllerTest.php`:
  - DELETE /api/v1/categories/{id} → 200, deleted_at set ✓
  - GET /api/v1/categories does not include deleted ✓
  - GET /api/v1/categories?include_deleted=true returns deleted (admin only) ✓
  - Non-admin cannot see deleted categories

**Checkpoint**: Wave 1 Backend Complete - All API endpoints implemented and tested

---

## Phase 5: API Seeding & Backend Integration Tests

**Purpose**: Populate test data and verify full backend flow
**Dependencies**: Depends on all Wave 1 completion

- [x] T043 [P] Enhanced CategorySeeder in `backend/database/seeders/CategorySeeder.php`:
  - Create 10+ top-level categories (Building Materials, Electrical, Plumbing, Hardware, Tools, Safety, Fasteners, Paints, Lighting, Storage)
  - Create 3-5 nested children per parent
  - Create 2-3 grandchildren under selected children
  - Assign icons for each
  - Set varied sort_order values
  - Test data covers all user story scenarios

- [x] T044 [P] Run seeder and verify:
  - `php artisan db:seed --class=CategorySeeder` completes without error
  - Database contains expected hierarchy (count parent, children, grandchildren levels)
  - Verify queries on 50+ categories return correctly
  - Dump tree structure for sanity check

- [x] T045 Integration test for full backend flow in `backend/tests/Feature/CategoryIntegrationTest.php`:
  - Admin creates 3 categories
  - Admin creates 3 children under category 1
  - Admin reorders child 3 to position 0
  - Admin moves child 1 to different parent
  - Admin soft-deletes one category
  - Verify final tree structure matches expectations

---

## Wave 2: Frontend Setup & API Integration

**Purpose**: Vue 3 composables, Pinia stores, HTTP client
**Dependencies**: Depends on Wave 1 API completion

### API Composable

- [x] T046 [P] Create category API composable at `frontend/composables/useCategories.ts`:
  - Methods: fetchCategories(), fetchCategory(id), createCategory(data), updateCategory(id, data), reorderCategory(id, newSortOrder), moveCategory(id, newParentId), deleteCategory(id)
  - Use $fetch or API middleware for HTTP calls
  - Error handling with standard contract (success, data, error)
  - Loading states for each method

- [x] T047 [P] Create Pinia store at `frontend/stores/categoryStore.ts`:
  - State: categories (tree), selectedCategory, isLoading, error
  - Actions: loadCategories(), selectCategory(id), createCategory(data), updateCategory(data), reorderCategory(data), moveCategory(data), deleteCategory(id)
  - Getters: categoriesTree, selectedCategoryPath (ancestors), isReady

---

## Wave 2: Components - Tree Rendering

**Purpose**: Vue 3 components for category display
**Dependencies**: Depends on API composables and Pinia store

### Tree Components

- [x] T048 [P] Create CategoryTree component at `frontend/components/categories/CategoryTree.vue`:
  - Use Composition API with <script setup>
  - Props: categories (array), selectable (boolean), onSelect callback
  - Render as recursive tree using v-for with :key="category.id"
  - Support expand/collapse for each node
  - Display name_ar and name_en
  - Show icon if present
  - RTL-ready: use Tailwind logical properties

- [x] T049 [P] Create CategoryTreeNode sub-component at `frontend/components/categories/CategoryTreeNode.vue`:
  - Props: category, level (for indentation), selectable
  - Child component for individual tree node
  - Emit: select, expand, collapse
  - Drag-handle for reordering (add visual indicator)

- [x] T050 [P] Add drag-and-drop support to CategoryTree:
  - Drag node to reorder siblings
  - Drag node to move to different parent (on drop, call moveCategory)
  - Visual feedback during drag (highlight drop zone)
  - Prevent drag to self or descendants

**Checkpoint**: Tree rendering functional

---

## Wave 2: Components - Forms & Modals

**Purpose**: Vue 3 forms for category CRUD
**Dependencies**: Depends on API composables

### Form Components

- [x] T051 [P] Create CategoryFormModal at `frontend/components/categories/CategoryFormModal.vue`:
  - Props: isOpen, category (null = create, object = edit), onClose, onSubmit
  - Use VeeValidate + Zod for validation
  - Fields: name_ar, name_en, parent_id (select dropdown), icon, is_active checkbox
  - RTL: labels and input fields RTL-aware
  - Arabic error messages
  - Submit button disabled until valid
  - Show optimistic lock version field on edit

- [x] T052 [P] Create CategorySelector dropdown at `frontend/components/categories/CategorySelector.vue`:
  - Props: modelValue (category_id), @update:modelValue callback
  - Drop-down showing category tree with indentation
  - Search/filter by name_ar or name_en
  - Support select=null for none option
  - RTL: tree indentation flipped

- [x] T053 [P] Create CategoryBreadcrumb component at `frontend/components/categories/CategoryBreadcrumb.vue`:
  - Props: category_id
  - Fetch and render ancestors
  - Display: "/" separator between ancestors
  - Each ancestor is clickable link (router.push)
  - RTL: breadcrumb order reversed (right to left)

---

## Wave 2: Admin Page & Integration

**Purpose**: Admin management page tying all components together
**Dependencies**: Depends on all Wave 2 components

### Admin UI

- [x] T054 Create admin categories page at `frontend/pages/admin/categories.vue`:
  - Layout: left sidebar tree + right panel (create/edit forms)
  - Tree: CategoryTree component with categories from store
  - Create button: opens CategoryFormModal with category=null
  - Edit node: click category → open CategoryFormModal with category data
  - Reorder: drag in tree → call reorderCategory
  - Delete: click delete icon → confirmation → call deleteCategory
  - Breadcrumb at top: CategoryBreadcrumb for context
  - Loading spinner during operations
  - Error toast on failure

- [x] T055 Implement admin page logic in `frontend/pages/admin/categories.vue`:
  - Mount: fetch categories from store (useCategories)
  - Handle form submit: createCategory, updateCategory
  - Handle delete: confirm dialog then deleteCategory with soft delete
  - Handle reorder drag: calculate new sort_order, call reorderCategory
  - Handle move drag: call moveCategory
  - Update store on success
  - Show error messages on failure

---

## Wave 2: Internationalization & RTL

**Purpose**: Arabic/English support and RTL layout
**Dependencies**: Depends on components

- [x] T056 [P] Add i18n keys to `frontend/locales/ar.json` and `frontend/locales/en.json`:
  - categories.title
  - categories.create
  - categories.edit
  - categories.delete
  - categories.deleteConfirm
  - categories.name_ar
  - categories.name_en
  - categories.parent
  - categories.icon
  - categories.isActive
  - errors.validationRequired
  - errors.validationMin
  - errors.validationCircularRef

- [x] T057 [P] Apply i18n in CategoryFormModal:
  - Use $t() for all labels and error messages
  - Support Arabic/English language toggle
  - RTL class applied to form when lang=ar

- [x] T058 [P] Apply RTL tweaks in CategoryTree and components:
  - Use Tailwind logical properties (ms- instead of ml-, text-start instead of text-left)
  - Reverse indentation for RTL (padding-inline-start)
  - Reverse breadcrumb order
  - Test with dir="rtl" on <html>

**Checkpoint**: Wave 2 Admin UI complete and RTL-ready

---

## Wave 3: Component Testing & E2E

**Purpose**: Unit tests, component tests, E2E Playwright tests
**Dependencies**: Depends on Wave 2 component completion

### Unit & Component Tests

- [x] T059 [P] Component unit tests for CategoryTree in `frontend/tests/components/CategoryTree.spec.ts`:
  - Test rendering list of categories with correct structure
  - Test expand/collapse toggle
  - Test select emit on node click
  - Test tree handles empty array

- [x] T060 [P] Component unit tests for CategoryFormModal in `frontend/tests/components/CategoryFormModal.spec.ts`:
  - Test form renders with empty state (create)
  - Test form renders with category data (edit)
  - Test VeeValidate validation (required, min/max)
  - Test submit emits correct data

- [x] T061 [P] Component unit tests for CategoryBreadcrumb in `frontend/tests/components/CategoryBreadcrumb.spec.ts`:
  - Test renders ancestor chain
  - Test clicks navigate via router.push
  - Test RTL order reversal

### E2E Tests

- [x] T062 [P] E2E test for category creation flow in `frontend/tests/e2e/category-create.spec.ts`:
  - Login as admin
  - Navigate to /admin/categories
  - Click "Create Category"
  - Fill form (name_ar, name_en, icon, is_active)
  - Submit
  - Verify category appears in tree
  - Verify API call to POST /api/v1/categories

- [x] T063 [P] E2E test for nested hierarchy in `frontend/tests/e2e/category-hierarchy.spec.ts`:
  - Create parent category
  - Create child under parent
  - Verify tree shows indented hierarchy
  - Verify breadcrumb shows parent → child path

- [x] T064 [P] E2E test for drag-and-drop reorder in `frontend/tests/e2e/category-reorder.spec.ts`:
  - Create 3 sibling categories
  - Drag sibling 3 to position 1
  - Verify API called with new sort_order
  - Verify tree reflects new order

- [x] T065 [P] E2E test for soft delete in `frontend/tests/e2e/category-delete.spec.ts`:
  - Create category
  - Click delete
  - Confirm deletion
  - Verify API called DELETE /api/v1/categories/{id}
  - Verify category disappears from tree

---

## Wave 3: Performance & Accessibility Testing

**Purpose**: Performance benchmarks and accessibility compliance
**Dependencies**: Depends on Wave 2 completion

### Performance Testing

- [x] T066 [P] Performance test for tree rendering in `frontend/tests/performance/category-performance.spec.ts`:
  - Render tree with 1000 categories
  - Measure time to interactive (target: <500ms)
  - Measure memory usage
  - Verify no unnecessary re-renders

- [x] T067 [P] API performance test for tree endpoint in `backend/tests/Performance/CategoryControllerTest.php`:
  - Query 1000-category tree with GET /api/v1/categories
  - Verify response time <200ms
  - Verify query count ≤3 (prevent N+1)
  - Verify memory usage reasonable

### Accessibility Testing

- [x] T068 [P] WCAG 2.1 AA compliance audit in `frontend/tests/a11y/category-a11y.spec.ts`:
  - Tree navigation keyboard-accessible (Tab, Arrow keys)
  - Form inputs have labels and ARIA
  - Color contrast ≥4.5:1
  - No focus traps
  - Breadcrumb links screen-reader-friendly
  - CategoryTree expands/collapses via keyboard

---

## Wave 3: Full Integration & Validation

**Purpose**: End-to-end system validation
**Dependencies**: Depends on all prior phases

### Integration Testing

- [x] T069 Full migration test in `backend/tests/Feature/CategoryMigrationTest.php`:
  - Run migration
  - Verify categories table structure
  - Verify indexes created
  - Rollback migration
  - Verify table dropped properly

- [x] T070 Seeding test in `backend/tests/Feature/CategoryMigrationTest.php`:
  - Run CategorySeeder
  - Verify data integrity (tree structure correct, counts match)
  - Verify no FK constraint violations

- [x] T071 End-to-end workflow test spanning backend + frontend in `backend/tests/Feature/CategoryWorkflowTest.php`:
  - Frontend creates category via form
  - Backend persists to database
  - Frontend fetches tree API
  - Frontend re-renders with new category
  - Verify visual consistency

### Documentation & Validation

- [x] T072 [P] Update API documentation in `backend/storage/api-docs/`:
  - Add CategoryController endpoints to Swagger/OpenAPI spec
  - Document request/response schemas
  - Document error codes and examples

- [x] T073 [P] Update README files:
  - Add "Category System" section to backend README explaining architecture
  - Add "Admin Categories Page" section to frontend README with usage examples

- [x] T074 [P] Code cleanup and refactoring:
  - Remove any dead code or debug statements
  - Verify all comments are accurate
  - Run linting: `composer run lint` and `npm run lint`
  - Run type checking: `npm run typecheck`

---

## Final Phase: Quality Gates & Deployment Readiness

**Purpose**: Validation and final checks before deployment
**Dependencies**: Depends on all testing completion

- [x] T075 [P] Run full test suite:
  - Backend: `php artisan test --parallel` (expect 30-35 tests, 85%+ coverage)
  - Frontend: `npm run test` (expect 15-20 tests)
  - E2E: `npm run test:e2e` (expect 5-6 scenarios)

- [x] T076 [P] Verify coverage targets:
  - Backend CategoryController: ≥90%
  - Backend CategoryService: ≥90%
  - Backend CategoryRepository: ≥85%
  - Frontend components: ≥80%
  - Overall target: ≥85%

- [x] T077 Code review checklist:
  - RBAC enforced on all endpoints (admin-only for mutations)
  - Error responses follow standard contract
  - Soft deletes implemented correctly
  - Optimistic locking enforced on updates
  - Circular reference validation prevents cycles
  - N+1 queries eliminated (eager loading used)
  - Arabic/English bilingual support complete
  - RTL layout tested in all components

- [x] T078 Security audit:
  - Verify no SQL injection vectors
  - Verify CSRF protection on forms
  - Verify rate limiting on API endpoints
  - Verify soft delete data not exposed to unauthorized users

- [x] T079 Run pre-commit checks:
  - `composer run lint` passes
  - `npm run lint` passes
  - `npm run typecheck` passes
  - No TypeScript errors in frontend

---

## Dependencies & Execution Order

### Phase Sequence

```
Phase 1 (DB & Model) → Phase 2 (Repo & Service) → Phase 3-4 (Controller setup)
    ↓
Wave 1 Complete (Backend API)
    ↓
Wave 2 (Frontend) can start in parallel with Wave 1 API
    ↓
Wave 3 (Testing) after Wave 1 & 2 complete
```

### Parallelization Opportunities

**Phase 1**: All [P] tasks can run parallel (migration, seeder, model)
**Phase 2**: All [P] tasks can run parallel (repository methods are orthogonal)
**Phase 3-4**: All [P] tasks can run parallel (form requests, resources are independent)
**Wave 1 Tests**: T017-T019, T023-T025, T029-T030, T034-T035, T038, T042 can all run parallel
**Wave 2 Frontend**: T046-T047 (API setup) can run parallel; T048-T053 components can run parallel; T054-T055 admin page depends on components
**Wave 3 Testing**: T059-T068 can all run parallel

### Dependency Graph

```
T001 → T004 → T005/T006/T007 → T008/T009
                   ↓
              T010/T011 → T014/T015/T016
                              ↓
                         T017/T018/T019 (US1 tests)
                              ↓
                    T020/T021/T022 (US2 impl)
                         T023/T024/T025 (US2 tests)
                              ↓
                    T026/T027/T028 (US3 impl)
                         T029/T030 (US3 tests)
                              ↓
                    T031/T032/T033 (US4 impl)
                         T034/T035 (US4 tests)
                              ↓
                    T036/T037 (US5 impl)
                         T038 (US5 tests)
                              ↓
                    T039/T040/T041 (US6 impl)
                         T042 (US6 tests)
                              ↓
= Wave 1 Backend Complete =
                              ↓
T046/T047 (frontend setup) → T048/T049/T050 (tree)
                    ↓
            T051/T052/T053 (forms, selector, breadcrumb)
                    ↓
            T054/T055 (admin page)
                    ↓
            T056/T057/T058 (i18n/RTL)
                    ↓
= Wave 2 Frontend Complete =
                              ↓
T059/T060/T061/T062/T063/T064/T065 (component & E2E tests)
T066/T067 (performance tests)
T068 (accessibility tests)
T069/T070/T071 (integration tests)
```

---

## Implementation Strategy

### Recommended Team Assignment (5-7 days)

**Option A: Single Developer (Sequential)**

1. Days 1-2: Phases 1-4 (migration, models, repository, services, controllers)
2. Days 2-3: Wave 1 Backend implementation (all 6 user stories)
3. Days 3-4: Wave 1 Backend testing
4. Days 4-5: Wave 2 Frontend (components, store, admin page)
5. Days 5-6: Wave 2 Testing + Performance
6. Days 6-7: Wave 3 Validation + Deployment checks

**Option B: Two Developers (Parallel)**

- **Dev A**: Phases 1-4 + Wave 1 Backend implementation & testing (Days 1-4)
- **Dev B**: Wave 2 Frontend setup in parallel (Day 2-3), then implement components (Day 3-5)
- **Both**: Wave 3 testing together (Days 5-6)
- **Both**: Final validation (Day 7)

### MVP Checkpoint (Day 2)

Stop after T045 to validate:

- Backend API endpoints fully functional
- All Wave 1 user stories (US1-6) complete
- 30+ tests passing
- Can create, read, update, reorder, move, soft-delete categories via API

### Incremental Delivery

- **Increment 1** (Wave 1): Deliver backend API (admin CLI usage only)
- **Increment 2** (Wave 2): Add frontend admin UI (users can now manage via web)
- **Increment 3** (Wave 3): Add performance optimizations + full test coverage

---

## Quality Standards

- **Test Coverage**: 85%+ across backend (50 tests) + frontend (20 tests) + E2E (6 tests)
- **Performance**: Tree query <200ms, tree render <500ms
- **Accessibility**: WCAG 2.1 AA compliance
- **Responsiveness**: Admin page responsive on desktop/tablet/mobile
- **i18n**: Full Arabic/English support with RTL layout
- **Security**: RBAC enforced, soft deletes secure, optimistic locking prevents race conditions

---

## Notes

- **[P] Tasks**: Different files, no dependencies → launch together
- **[US#] Labels**: Track which user story each task serves
- **Exact Paths**: All file locations specified for clarity
- **Dependency Arrows**: Follow the graph for safe parallelization
- **Testing**: Write tests first (Red-Green), then implement (TDD)
- **Commits**: Commit after each phase or logical group (e.g., after US1 complete)
- **Validation**: Run `php artisan test && npm run test` before moving to next wave
