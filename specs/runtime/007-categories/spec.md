# Feature Specification: Product Category Hierarchy

**Stage**: STAGE_07_CATEGORIES  
**Phase**: 02_CATALOG_AND_INVENTORY  
**Created**: 2026-04-15  
**Status**: Draft  
**Spec Type**: Product/Service Category System

---

## Overview

Implement a hierarchical category system for organizing construction products and services. Categories support multi-level nesting (parent-child relationships) with Arabic/English bilingual support, full RBAC enforcement, and a tree-aware admin UI with drag-and-drop reordering.

---

## User Scenarios & Testing

### User Story 1 — Admin Creates and Organizes Top-Level Categories (Priority: P1)

**Description**:  
An admin user creates the foundational category structure for the construction materials marketplace. This establishes the primary organization system that all products will reference.

**Why this priority**: Foundational — category hierarchy cannot exist without top-level categories. This is the MVP entry point.

**Independent Test**: Admin can create ≥3 top-level categories (e.g., "مواد بناء" [Building Materials], "كهرباء" [Electrical], "سباكة" [Plumbing]) and retrieve them via API in a flat list. Each category persists with Arabic/English names, slug, icon, and correct sort order.

**Acceptance Scenarios**:

1. **Given** admin is logged in,  
   **When** admin navigates to Category Management page,  
   **Then** admin sees empty category tree with "Create Category" button

2. **Given** category creation form is open,  
   **When** admin enters name_ar="مواد بناء", name_en="Building Materials", icon="box", sort_order=1, parent_id=null,  
   **Then** category is created, appears in tree, and slug is auto-generated as "building-materials"

3. **Given** multiple top-level categories exist,  
   **When** admin retrieves GET /api/v1/categories (with parent_id filter),  
   **Then** API returns flat array with correct parent-child relationships (parent_id=null for top-level)

4. **Given** category is created,  
   **When** category is accessed in database,  
   **Then** created_at, updated_at timestamps are populated, deleted_at is null, is_active=true by default

---

### User Story 2 — Admin Creates Nested Sub-Categories (Priority: P1)

**Description**:  
Admin creates sub-categories under existing top-level categories to build a multi-level hierarchy. For example, "Concrete" under "Building Materials", or "Cables" under "Electrical".

**Why this priority**: Core to the category system — without nested categories, the hierarchy is flat and unusable for complex product organization.

**Independent Test**: Admin creates a parent category, then creates ≥2 child categories under it. API returns the tree with correct parent-child links (verified via parent_id field). Child categories appear indented in the admin UI tree view.

**Acceptance Scenarios**:

1. **Given** top-level category "Building Materials" exists,  
   **When** admin creates sub-category with name_ar="أسمنت", name_en="Concrete", parent_id=[building-materials-id],  
   **Then** sub-category is created with parent_id set correctly

2. **Given** sub-category is created,  
   **When** admin retrieves GET /api/v1/categories?parent_id=[id],  
   **Then** API returns only children of that parent

3. **Given** category tree exists with 3 levels (e.g., Building Materials > Concrete > Portland Cement),  
   **When** admin requests full tree via GET /api/v1/categories,  
   **Then** API returns hierarchical structure with nested children arrays (tree format, not flat)

4. **Given** admin is viewing category tree UI,  
   **When** tree is rendered,  
   **Then** parent categories are collapsible, child categories indent under parents, and sort order is preserved

---

### User Story 3 — Admin Reorders Categories within Same Level (Priority: P2)

**Description**:  
Admin reorganizes the display order of categories at the same hierarchy level (e.g., reorder top-level categories by dragging, or reorder children within a parent).

**Why this priority**: Improves UX — allows merchants and users to see most important categories first. Not critical for MVP but enhances usability immediately after launch.

**Independent Test**: Admin drags a category to a new position within the same parent. sort_order values are recalculated and persisted. UI reflects the new order without page reload.

**Acceptance Scenarios**:

1. **Given** top-level categories exist (sort_order 1, 2, 3),  
   **When** admin drags category 3 to position 1,  
   **Then** sort_order values are recalculated (3 moves to 1, others increment), and PUT /api/v1/categories/{id}/reorder is called with new position

2. **Given** reorder request is sent with valid sort_order,  
   **When** reorder completes successfully,  
   **Then** updated_at timestamp is refreshed, and API returns 200 OK with updated category

3. **Given** admin drags within a nested level,  
   **When** reorder happens,  
   **Then** sibling categories (same parent_id) are reordered only; other levels unaffected

---

### User Story 4 — Admin Moves Category to Different Parent (Priority: P2)

**Description**:  
Admin restructures the hierarchy by moving a category and its descendants to a different parent. Example: moving "Electrical Cables" sub-category from "Electrical" parent to "Hardware" parent.

**Why this priority**: Advanced hierarchy editing — enables reorganization when business needs evolve. Deferred to P2 because initial hierarchy is often stable post-launch.

**Independent Test**: Admin moves a category with children to a new parent. All descendants move with it. parent_id is updated for moved category only. Tree structure is maintained.

**Acceptance Scenarios**:

1. **Given** "Cables" category is a child of "Electrical" (parent_id=electrical_id),  
   **When** admin requests to move "Cables" to parent "Hardware" (parent_id=hardware_id),  
   **Then** parent_id is updated, descendants remain intact, sort_order is recalculated within new parent

2. **Given** category move is completed,  
   **When** admin retrieves tree,  
   **Then** "Cables" now appears under "Hardware", and original position under "Electrical" is empty

---

### User Story 5 — Admin Edits Category Details (Priority: P2)

**Description**:  
Admin updates category metadata (names, icon, status) after creation. Example: correcting a typo in Arabic name, updating icon, or toggling is_active status.

**Why this priority**: Maintenance — essential for fixing errors and managing category visibility, but not required for MVP launch.

**Independent Test**: Admin edits a category's name_ar, name_en, icon, or is_active. Changes persist to database. API returns updated record.

**Acceptance Scenarios**:

1. **Given** category edit form is open,  
   **When** admin changes name_ar and icon,  
   **Then** PUT /api/v1/categories/{id} is called, and database reflects changes

2. **Given** admin toggles is_active to false,  
   **When** toggle is saved,  
   **Then** category is_active=false, and products linked to this inactive category may be queried (behavior TBD in filtering)

3. **Given** category is updated,  
   **When** update completes,  
   **Then** updated_at is refreshed, slug may be regenerated if name changed, or kept stable (TBD)

---

### User Story 6 — Admin Soft-Deletes Category (Priority: P3)

**Description**:  
Admin marks a category as deleted (soft delete via deleted_at) instead of hard-deleting, preserving historical data and preventing orphaned products.

**Why this priority**: Data integrity — soft deletes protect audit trails and linked records. Deferred to P3 as not needed for initial category creation.

**Independent Test**: Admin soft-deletes a category. deleted_at is set. Category is hidden from normal list views, but retrievable via include deleted scope or admin-only queries.

**Acceptance Scenarios**:

1. **Given** category is active,  
   **When** admin clicks "Delete Category",  
   **Then** confirmation dialog appears asking to confirm

2. **Given** confirmation is accepted,  
   **When** DELETE /api/v1/categories/{id} is called,  
   **Then** category.deleted_at is set to current timestamp, record is not hard-deleted

3. **Given** deleted category is soft-deleted,  
   **When** admin views category tree,  
   **Then** soft-deleted categories are hidden from default view (scope excludes deleted)

---

### User Story 7 — Frontend: Category Breadcrumb Component (Priority: P2)

**Description**:  
A reusable Vue 3 Composition API component displays the full path from root to current category (e.g., "Products / Building Materials / Concrete"). Enables navigation and UX clarity in product pages and filters.

**Why this priority**: UX enhancement — improves navigation and context awareness. P2 because it's used by product display features, not core to category creation.

**Independent Test**: Breadcrumb component receives a category_id, renders full path including links and separators, and is independently testable without product context.

**Acceptance Scenarios**:

1. **Given** Breadcrumb component is rendered with category_id=[concrete_id],  
   **When** component mounts,  
   **Then** it fetches ancestors via API and renders "/ Building Materials / Concrete"

2. **Given** breadcrumb is rendered,  
   **When** user clicks a parent link,  
   **Then** navigation occurs (router.push or callback fired)

---

### User Story 8 — Frontend: Category Selector Dropdown Component (Priority: P2)

**Description**:  
A reusable Vue 3 dropdown component for selecting a category when creating/editing products. Displays the full tree with indentation and search capability. Used in product creation/edit forms.

**Why this priority**: Product creation UI dependency — required for Product assignments, but can be simple initially and enhanced later.

**Independent Test**: Selector component renders full category tree, allows searching by name (Arabic + English), and emits selected category ID when choice is made.

**Acceptance Scenarios**:

1. **Given** Category Selector is rendered in Product form,  
   **When** dropdown is opened,  
   **Then** all active categories are displayed as a tree with indentation

2. **Given** tree is displayed,  
   **When** user types "concrete" in search,  
   **Then** tree filters to show only matching categories and ancestors

3. **Given** category is selected,  
   **When** selection is made,  
   **Then** component emits or updates selected category ID, form persists the value

---

### Edge Cases

- **Empty category hierarchy**: System handles gracefully (admin creates first category, tree is initially empty)
- **Circular parent references**: System prevents setting a category as parent of its own ancestor (e.g., prevents cycles)
- **Deleting parent with active children**: System may soft-delete parent; children remain linked (behavior TBD in business logic)
- **Reordering with gaps in sort_order**: System recalculates sort_order to fill gaps when reordering
- **Duplicate slugs**: System ensures slug uniqueness (auto-append counter or hash if collision detected)
- **Unicode in category names**: SQL stores utf8mb4, API returns proper JSON, frontend renders RTL correctly
- **Arabic name truncation in UI**: Long Arabic names should wrap or truncate gracefully in tables/trees
- **Missing category icon**: System provides sensible default icon or displays gracefully without icon

---

## Requirements

### Functional Requirements

#### Backend

- **FR-001**: System MUST define Category Eloquent model with fields: id, parent_id (nullable, self-relation), name_ar, name_en, slug (unique, auto-generated from name_en), icon (nullable), sort_order (default 0, ordered ascending), is_active (boolean, default true), created_at, updated_at, deleted_at (soft delete)

- **FR-002**: System MUST enforce self-referential hierarchy via parent_id foreign key (categories.id) with cascade on delete or soft-delete handling

- **FR-003**: System MUST provide CategoryRepository with methods:
  - `getTree(includeDeleted=false)`: Return full hierarchy with nested children
  - `getChildren(parentId)`: Return direct children of a category
  - `getAncestors(categoryId)`: Return path from root to category
  - `getDescendants(categoryId)`: Return all descendants recursively
  - `reorder(categoryId, newSortOrder)`: Update sort_order and recalculate siblings
  - `move(categoryId, newParentId)`: Change parent and cascade reorder

- **FR-004**: System MUST provide CategoryService with methods:
  - `create(payload)`: Validate, generate slug, set sort_order, create category
  - `update(categoryId, payload)`: Update fields, handle slug consistency
  - `delete(categoryId)`: Soft-delete via deleted_at
  - `restore(categoryId)`: Restore soft-deleted category
  - `reorder(categoryId, newSortOrder)`: Call repository reorder
  - `move(categoryId, newParentId)`: Call repository move

- **FR-005**: System MUST validate:
  - name_ar length (required, min 2, max 100)
  - name_en length (required, min 2, max 100)
  - parent_id must reference existing category if provided (or be null for top-level)
  - parent_id cannot be the category's own id (no self-parenting)
  - icon (optional, max 50 chars for icon class name)
  - sort_order (optional int, >= 0)

- **FR-006**: System MUST create a Form Request for category creation/update with localized Arabic/English validation messages

- **FR-007**: System MUST create API Resource (CategoryResource) serializing all fields with nested children array for tree endpoints

- **FR-008**: System MUST enforce RBAC:
  - Only Admin role can POST/PUT/DELETE categories
  - Any authenticated user can GET categories (read-only permission check optional, usually public)
  - All API routes under /api/v1/categories with admin middleware

- **FR-009**: System MUST provide seeder (CategorySeeder) with default construction categories in Arabic + English:
  - مواد بناء (Building Materials)
    - أسمنت (Cement)
    - رمل (Sand)
    - حديد (Steel)
    - خشب (Wood)
  - كهرباء (Electrical)
    - أسلاك (Cables)
    - لوحات (Panels)
  - سباكة (Plumbing)
    - أنابيب (Pipes)
    - تجهيزات (Fixtures)
  - تشطيبات (Finishing)
    - دهانات (Paints)
    - بلاط (Tiles)

#### API Endpoints

- **FR-010**: GET /api/v1/categories
  - Return full tree hierarchy (nested children)
  - Exclude soft-deleted by default
  - Optional query param: parent_id to filter by parent
  - Response: `{ "success": true, "data": [{ id, parent_id, name_ar, name_en, slug, icon, sort_order, is_active, children: [...] }], "error": null }`

- **FR-011**: POST /api/v1/categories
  - Create category with validation
  - Request: `{ name_ar, name_en, parent_id (optional), icon (optional), sort_order (optional) }`
  - Response: Created category resource with 201 status
  - Authorization: Admin only
  - Error codes: VALIDATION_ERROR (422), AUTH_UNAUTHORIZED (403), CONFLICT_ERROR (409 for slug)

- **FR-012**: GET /api/v1/categories/{id}
  - Retrieve single category with nested children
  - Response: Category resource with children array
  - Error: RESOURCE_NOT_FOUND (404) if deleted

- **FR-013**: PUT /api/v1/categories/{id}
  - Update category fields (name_ar, name_en, icon, is_active, parent_id [with validation])
  - Request: `{ name_ar (optional), name_en (optional), icon (optional), is_active (optional), parent_id (optional) }`
  - Response: Updated category resource
  - Authorization: Admin only
  - Error codes: VALIDATION_ERROR (422), RESOURCE_NOT_FOUND (404), AUTH_UNAUTHORIZED (403), WORKFLOW_INVALID_TRANSITION (422 if parent_id creates cycle)

- **FR-014**: DELETE /api/v1/categories/{id}
  - Soft-delete category
  - Response: `{ "success": true, "data": null, "error": null }` (204 No Content or 200 OK)
  - Authorization: Admin only
  - Error: RESOURCE_NOT_FOUND (404), AUTH_UNAUTHORIZED (403)

- **FR-015**: PUT /api/v1/categories/{id}/reorder
  - Reorder category within siblings
  - Request: `{ sort_order: <int> }` or `{ position: <int> }` (position may be more intuitive)
  - Recalculate sort_order for affected siblings
  - Response: Updated category with new sort_order
  - Authorization: Admin only
  - Error: VALIDATION_ERROR (422)

#### Frontend

- **FR-016**: CategoryTreeComponent (Vue 3 Composition API):
  - Display full category tree with indentation
  - Expand/collapse parents
  - Drag-and-drop to reorder siblings (or drag to move parents)
  - Edit/delete buttons per category
  - Icons for each category
  - Search/filter input (optional)
  - Uses Nuxt UI UTree or custom tree with Tailwind

- **FR-017**: CategoryFormModal (Vue 3 + Nuxt UI):
  - Two text inputs: name_ar (Arabic), name_en (English)
  - Text input: icon (optional, with icon selector or class name input)
  - Dropdown: parent_id (uses CategorySelector)
  - Checkbox: is_active
  - Submit/Cancel buttons
  - Validation errors display (form-level + field-level)
  - Used for create and edit workflows

- **FR-018**: CategoryBreadcrumb component:
  - Receives category_id as prop
  - Fetches ancestors from API asynchronously
  - Renders breadcrumb path: "Category Name / Parent Name / Root"
  - Makes each breadcrumb link clickable (navigates to category page or filters)
  - RTL-aware layout

- **FR-019**: CategorySelector dropdown component:
  - Receives v-model:modelValue for selected category ID
  - Renders full tree with indentation
  - Search input with result filtering (Arabic + English)
  - Used in ProductForm for category selection
  - Emits @update:modelValue when selection changes

- **FR-020**: Admin Category Management Page:
  - Route: /admin/categories (or /dashboard/categories)
  - Displays CategoryTreeComponent
  - "Create Category" button (opens modal)
  - Edit/Delete context menu or buttons per category
  - Reorder via drag-and-drop
  - Search categories input

### Non-Functional Requirements

- **NFR-001**: API responses must comply with standardized error contract (AGENTS.md)
- **NFR-002**: All database queries must support RTL collation (utf8mb4_unicode_ci)
- **NFR-003**: Category slugs must be URL-safe and cacheable without recomputation
- **NFR-004**: Tree queries should use eager loading / select N+1 optimization (Laravel with/withCount)
- **NFR-005**: Frontend components must support both Arabic (RTL) and English (LTR) layouts
- **NFR-006**: All text inputs must accept Arabic Unicode (textarea for category management is unnecessary, text columns sufficient)
- **NFR-007**: Seeder must be idempotent — running multiple times produces same data (no duplicates)
- **NFR-008**: All RBAC checks must happen server-side; client-side UI may hide buttons but authorization is backend-enforced
- **NFR-009**: API response times for tree listing must be <500ms for typical category hierarchies (100-1000 categories)
- **NFR-010**: Frontend tree rendering must handle 1000+ categories without performance degradation (virtualization optional)

### Key Entities

- **Category**: Represents a product/service category in a self-hierarchical tree structure
  - Attributes: id, parent_id (nullable), name_ar, name_en, slug, icon, sort_order, is_active, timestamps, deleted_at
  - Relationships: parent (self), children (self, many)
  - Role restrictions: Admins create/update/delete; all authenticated users read

---

## Success Criteria

### Measurable Outcomes

- **SC-001**: Admin can create ≥10 hierarchical categories (multi-level) in <10 seconds via UI
- **SC-002**: Category tree API endpoint returns full hierarchy in <500ms (measured with ≤1000 categories)
- **SC-003**: All category CRUD operations enforce RBAC (non-admin receives 403 RBAC_ROLE_DENIED)
- **SC-004**: Category reorder updates sort_order correctly and persists without data loss or orphaning
- **SC-005**: Breadcrumb component renders correct ancestor chain without N+1 queries
- **SC-006**: Category selector dropdown loads and filters 500+ categories in <1s
- **SC-007**: Seeder successfully populates 10+ default categories in both Arabic and English with no duplicates
- **SC-008**: All validation errors return code VALIDATION_ERROR (422) with field-level details
- **SC-009**: Soft-deleted categories are excluded from normal queries but restorable by admin
- **SC-010**: Tree drag-and-drop reorder updates database within 2 seconds

---

## Assumptions

- **Architecture**: Category tree is stored in single `categories` table with self-referential parent_id; no materialized path or closure table required for MVP
- **Scope**: Categories are limited to products only (not projects or phases), as per STAGE_07 scope
- **Slug generation**: Slugs are auto-generated from name_en using Laravel helper (Str::slug), kept stable after creation (not dynamically regenerated on name change, or regenerated per TBD business rule)
- **Reordering**: sort_order is the single source of truth for display order; no weighted/priority system
- **Circular references**: Prevented at API validation layer; database constraint (CHECK) optional but recommended
- **Soft deletes**: Deleted categories are hidden from public queries but retained in database for audit; admin can restore
- **Icon storage**: Icon is stored as a CSS class name or icon library identifier (e.g., "lucide-box", "fas-cube"), not an image URL or blob
- **Permissions**: Only Admin role can manage categories; no delegation to other roles (e.g., Contractor cannot create categories)
- **Arabic-first data**: Seeder provides Arabic names (name_ar) as primary; English (name_en) as localized fallback

---

## [NEEDS CLARIFICATION: 3 items]

1. **Slug management on name change**: When an admin updates a category's name_en, should the slug auto-regenerate (potentially breaking existing URL links)? Or should slug be immutable once created? → **Suggested assumption**: Slug is immutable; name can change without affecting slug.

2. **Behavior when deleting category with active children**: If admin soft-deletes a parent category with active child categories, should children also be soft-deleted, or orphaned? → **Suggested assumption**: Parent can be soft-deleted independently; children retain parent_id (may be null if parent is restored, or remains for audit).

3. **Category-to-product soft-delete cascade**: If a product is soft-deleted but linked to a category, should the category remain visible? → **Suggested assumption**: Category visibility is independent of product state; categories are deleted only via admin action.

---

## Out of Scope

- Category permissions per role (e.g., "Contractor A can only see categories Y and Z") — Admin-only category management
- Category quotas or limits (e.g., "max 100 categories per parent") — No artificial limits
- Category image/banner uploads — Icons are class names, not images
- Multi-language category names beyond Arabic/English — Extensible via lang/ directory, but only ar/ and en/ seeded
- Category discounts or pricing rules — Pricing is product-level, not category-level
- Category analytics (e.g., "number of products per category") — Out of scope for MVP
- Real-time category tree synchronization across clients — No WebSocket/live updates
- Category import/export (bulk CSV) — Manual admin UI only for MVP
- Category-level access control — All authenticated users can read; only Admin can write

---

## Implementation Dependencies

### Upstream Dependencies

- **STAGE_06_API_FOUNDATION**: RESTful API structure, error contract, Sanctum auth

### Downstream Dependencies

- **STAGE_08_PRODUCTS**: Products will reference categories via foreign key
- **Frontend catalog pages**: Product listings filtered by category

---

## Implementation Notes

- **Seeder strategy**: Bootstrap seeder runs in database/seeders/; can be called manually or in migration if needed
- **Testing strategy**: Unit tests for CategoryService, Feature tests for API endpoints, Component tests for Vue 3 components
- **Database performance**: Consider adding index on (parent_id, sort_order, is_active, deleted_at) for tree queries
- **Soft delete scope**: Use `->withoutTrashed()` in default queries; `->withTrashed()` optional for admin audit
