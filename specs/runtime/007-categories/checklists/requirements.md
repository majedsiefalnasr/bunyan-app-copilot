# Quality Requirements Checklist — STAGE_07_CATEGORIES

**Stage**: STAGE_07_CATEGORIES (Product Category Hierarchy)  
**Generated**: 2026-04-15  
**Scope**: Category Eloquent model, repository, service, API endpoints, admin UI, seeder

---

## Backend Implementation Checklist

### Database & Migrations

- [ ] Migration `create_categories_table` includes:
  - [ ] `id` (primary key, unsigned big integer)
  - [ ] `parent_id` (nullable unsigned big integer, foreign key self-reference with cascade/restrict delete)
  - [ ] `name_ar` (string 255, not null, utf8mb4)
  - [ ] `name_en` (string 255, not null, utf8mb4)
  - [ ] `slug` (string 255, not null, unique index)
  - [ ] `icon` (string 100, nullable)
  - [ ] `sort_order` (unsigned integer, default 0, indexed with parent_id)
  - [ ] `is_active` (boolean, default true)
  - [ ] `created_at`, `updated_at`, `deleted_at` (soft deletes)
  - [ ] Composite index on `(parent_id, sort_order, is_active, deleted_at)` for query performance
  - [ ] Foreign key constraint on `parent_id` references `categories(id)`

- [ ] Migration is forward-only (no down() that destroys schema)
- [ ] Migration naming: `YYYY_MM_DD_HHMMSS_create_categories_table.php`
- [ ] Migration is idempotent (safe to run multiple times)

### Eloquent Model (Category)

- [ ] Model file: `app/Models/Category.php`
- [ ] Model attributes:
  - [ ] Fillable/guarded: `['parent_id', 'name_ar', 'name_en', 'slug', 'icon', 'sort_order', 'is_active']`
  - [ ] Casts: `is_active` as boolean, `sort_order` as integer
  - [ ] Relationships:
    - [ ] `parent()` - BelongsTo relationship to self
    - [ ] `children()` - HasMany relationship to self
  - [ ] Scopes:
    - [ ] `withoutTrashed()` (custom or Laravel's built-in)
    - [ ] `active()` scope filtering is_active = true
    - [ ] `ordered()` scope ordering by sort_order ASC, then created_at
    - [ ] `byParent($parentId)` scope filtering by parent_id
  - [ ] Accessors/Mutators:
    - [ ] `slug` attribute auto-generated from name_en on create (or via boot/creating hook)
  - [ ] Soft delete support: `use SoftDeletes` trait

- [ ] Model may define a scope for eager loading tree to prevent N+1:
  - [ ] `with(['children' => fn($q) => $q->active()->ordered()])` (optional but recommended)

- [ ] No business logic in model (defer to Service layer)

### Repository (CategoryRepository)

- [ ] File: `app/Repositories/CategoryRepository.php` or `app/Repositories/Category/CategoryRepository.php`
- [ ] Implements interface (optional but recommended): `CategoryRepositoryInterface`
- [ ] Methods implemented:
  - [ ] `getTree(includeInactive=false, includeDeleted=false)`: Returns full nested tree
    - [ ] Eager loads children recursively (or uses query optimization)
    - [ ] Returns Collection with nested children keys
    - [ ] Respects filters for active and deleted records
  - [ ] `getChildren(parentId, includeInactive=false, includeDeleted=false)`: Direct children only
    - [ ] Returns paginated or full collection
    - [ ] Ordered by sort_order
  - [ ] `getAncestors(categoryId)`: Returns path from category to root
    - [ ] Includes the category itself at end
    - [ ] Ordered from root to deepest (or vice versa, specify)
  - [ ] `getDescendants(categoryId)`: All descendants recursively
    - [ ] Flat or nested format (clarify)
  - [ ] `create(array $data)`: Validates and creates category
    - [ ] Auto-generates slug from name_en
    - [ ] Sets sort_order if not provided (e.g., max(sort_order) + 1 for siblings)
    - [ ] Returns created Category model
  - [ ] `update(id, array $data)`: Updates category fields
    - [ ] Handles slug update logic (immutable vs. regenerated)
    - [ ] Returns updated Category model
  - [ ] `delete(id)`: Soft-deletes category
    - [ ] Sets deleted_at timestamp
    - [ ] Returns boolean success
  - [ ] `restore(id)`: Restores soft-deleted category
    - [ ] Clears deleted_at
    - [ ] Returns restored Category model
  - [ ] `reorder(categoryId, newSortOrder)`: Updates sort_order and cascades to siblings
    - [ ] Recalculates sort_order to fill gaps (e.g., 0, 1, 2... not 0, 5, 10)
    - [ ] Maintains correct ordering
  - [ ] `move(categoryId, newParentId)`: Changes parent and recalculates sort_order
    - [ ] Validates no circular reference
    - [ ] Recalculates sort_order in new parent's context
  - [ ] `findBy(field, value)`: Generic finder
  - [ ] `findOrFail(id)`: Throws exception if not found

- [ ] Repository uses Eloquent query builder with proper eager loading
- [ ] No raw SQL unless performance-critical (documented)
- [ ] Soft delete filtering applied correctly (withoutTrashed() by default)

### Service (CategoryService)

- [ ] File: `app/Services/CategoryService.php`
- [ ] Injects CategoryRepository via constructor
- [ ] Methods implemented:
  - [ ] `create(array $payload)`: Validates using Form Request, calls repository create
    - [ ] Catches validation errors and re-throws or translates
    - [ ] Returns created Category model
  - [ ] `update(id, array $payload)`: Validates using Form Request, calls repository update
    - [ ] Returns updated Category model
  - [ ] `delete(id)`: Calls repository delete
    - [ ] Optional: Dispatch event or job on delete
  - [ ] `restore(id)`: Calls repository restore
    - [ ] Optional: Dispatch event
  - [ ] `reorder(id, newSortOrder)`: Calls repository reorder
    - [ ] Validates newSortOrder is integer >= 0
  - [ ] `move(id, newParentId)`: Validates newParentId exists and not circular, calls repository move
    - [ ] Throws custom exception if circular reference detected
    - [ ] Returns moved Category model

- [ ] All public methods validate input and handle exceptions gracefully
- [ ] No HTTP or rendering logic in Service
- [ ] Events/logging optional but recommended for audit trail

### Form Request (CategoryFormRequest)

- [ ] File: `app/Http/Requests/StoreCategoryRequest.php` and `UpdateCategoryRequest.php` (or combined)
- [ ] Validation rules:
  - [ ] `name_ar`: required, string, min:2, max:100 (Arabic pattern validation optional)
  - [ ] `name_en`: required, string, min:2, max:100
  - [ ] `parent_id`: nullable, integer, exists:categories,id (validate category exists)
  - [ ] `icon`: nullable, string, max:100
  - [ ] `sort_order`: nullable, integer, min:0
  - [ ] `is_active`: nullable, boolean, (also handle 0/1 from forms)

- [ ] Custom validation rule: `Unique` slug (excluding current category on update)
- [ ] Custom validator: Circular reference check if parent_id provided
- [ ] Authorization check: `authorize()` returns true only for Admin role
- [ ] Messages in Arabic + English (via lang/ar/ and lang/en/)
- [ ] Sanitization:
  - [ ] Trim whitespace on name_ar, name_en
  - [ ] Lowercase slug (if applicable)

### API Controller (CategoryController)

- [ ] File: `app/Http/Controllers/Api/V1/CategoryController.php`
- [ ] Routes registered in `routes/api/categories.php` (or inline in `routes/api.php`)
- [ ] Middleware stack: `api`, `auth:sanctum`, `admin` (admin-only for write operations)
- [ ] Methods implemented:
  - [ ] `index()`: GET /api/v1/categories
    - [ ] Calls `CategoryService->getTree()` or repository equivalent
    - [ ] Returns CategoryResource collection with tree structure
    - [ ] Optional query params: parent_id, active_only
  - [ ] `store()`: POST /api/v1/categories
    - [ ] Validates using StoreCategoryRequest
    - [ ] Calls `CategoryService->create()`
    - [ ] Returns CategoryResource with 201 status
    - [ ] Middleware: admin
  - [ ] `show(id)`: GET /api/v1/categories/{id}
    - [ ] Calls `CategoryService->findOrFail(id)`
    - [ ] Returns CategoryResource
    - [ ] 404 if not found
  - [ ] `update(id)`: PUT /api/v1/categories/{id}
    - [ ] Validates using UpdateCategoryRequest
    - [ ] Calls `CategoryService->update(id, $validated)`
    - [ ] Returns CategoryResource
    - [ ] Middleware: admin
  - [ ] `destroy(id)`: DELETE /api/v1/categories/{id}
    - [ ] Calls `CategoryService->delete(id)`
    - [ ] Returns success response (200 OK or 204 No Content)
    - [ ] Middleware: admin
  - [ ] `reorder(id)`: PUT /api/v1/categories/{id}/reorder
    - [ ] Validates sort_order in request
    - [ ] Calls `CategoryService->reorder(id, sort_order)`
    - [ ] Returns CategoryResource
    - [ ] Middleware: admin

- [ ] All methods return standardized error response on exception
- [ ] Exception handling:
  - [ ] ValidationException → VALIDATION_ERROR (422)
  - [ ] ModelNotFoundException → RESOURCE_NOT_FOUND (404)
  - [ ] AuthorizationException → AUTH_UNAUTHORIZED (403) or RBAC_ROLE_DENIED
  - [ ] Custom circular reference exception → WORKFLOW_INVALID_TRANSITION (422)
  - [ ] Generic Exception → SERVER_ERROR (500, no stack trace to client)

### API Resource (CategoryResource)

- [ ] File: `app/Http/Resources/CategoryResource.php` or Api\V1\CategoryResource.php
- [ ] Serializes to:

  ```json
  {
    "id": 1,
    "parent_id": null,
    "name_ar": "مواد بناء",
    "name_en": "Building Materials",
    "slug": "building-materials",
    "icon": "lucide-box",
    "sort_order": 1,
    "is_active": true,
    "created_at": "2026-04-15T00:00:00Z",
    "updated_at": "2026-04-15T00:00:00Z",
    "deleted_at": null,
    "children": [
      /* nested CategoryResource array */
    ]
  }
  ```

- [ ] `children` relationship is conditionally included:
  - [ ] For tree endpoints (index), include children recursively
  - [ ] For single category (show), may include immediate children only or recursively
- [ ] All timestamps in ISO 8601 format (UTC)
- [ ] Resource wrapping consistent with error contract

### Database Seeder (CategorySeeder)

- [ ] File: `database/seeders/CategorySeeder.php`
- [ ] Seeded categories (with translation support):
  - [ ] Top-level categories:
    - [ ] Building Materials (مواد البناء) → children: Cement, Sand, Steel, Wood
    - [ ] Electrical (كهربائيات) → children: Cables, Panels
    - [ ] Plumbing (السباكة) → children: Pipes, Fixtures
    - [ ] Finishing (التشطيبات) → children: Paints, Tiles

- [ ] Idempotency:
  - [ ] Before inserting, check if categories exist (e.g., by slug or name_ar)
  - [ ] Use `updateOrCreate()` or truncate + reseed pattern (specify which)
  - [ ] Seeder can be run multiple times without creating duplicates

- [ ] Seed data includes:
  - [ ] Arabic names (name_ar) with proper UTF-8 encoding
  - [ ] English translations (name_en)
  - [ ] Icon class names (e.g., "lucide-box")
  - [ ] Explicit sort_order for each category
  - [ ] is_active = true

- [ ] Called in `DatabaseSeeder.php` or callable manually via `php artisan db:seed --class=CategorySeeder`

### Testing (Backend)

- [ ] **Unit Tests** (`tests/Unit/Services/CategoryServiceTest.php`):
  - [ ] `test_create_category_with_valid_data()`
  - [ ] `test_create_category_fails_with_invalid_parent_id()`
  - [ ] `test_update_category_fields()`
  - [ ] `test_delete_category_soft_deletes()`
  - [ ] `test_reorder_category_updates_sort_order()`
  - [ ] `test_move_category_prevents_circular_reference()`
  - [ ] `test_get_tree_returns_nested_hierarchy()`

- [ ] **Feature Tests** (`tests/Feature/Http/Controllers/Api/V1/CategoryControllerTest.php`):
  - [ ] `test_admin_can_create_category()` (403 for non-admin)
  - [ ] `test_admin_can_update_category()` (403 for non-admin)
  - [ ] `test_admin_can_delete_category()` (403 for non-admin, soft-deletes)
  - [ ] `test_admin_can_reorder_category()`
  - [ ] `test_get_categories_returns_tree()`
  - [ ] `test_get_category_by_id()`
  - [ ] `test_validation_errors_return_correct_response()`
  - [ ] `test_circular_reference_returns_error()`
  - [ ] `test_soft_deleted_categories_excluded_from_list()`

- [ ] All tests include seed data setup (factories or seeder)
- [ ] Tests validate error response format matches contract

### Validation & RBAC

- [ ] [ ] API enforces RBAC via middleware:
  - [ ] GET (list/show): All authenticated users (or public, TBD)
  - [ ] POST/PUT/DELETE: Admin only, returns 403 RBAC_ROLE_DENIED if unauthorized
- [ ] RBAC is server-side enforced, not just hidden UI
- [ ] No exception details exposed to client (logged server-side only)

---

## Frontend Implementation Checklist

### Components

#### CategoryTreeComponent

- [ ] File: `frontend/components/Categories/CategoryTreeComponent.vue`
- [ ] Vue 3 Composition API (`<script setup lang="ts">`)
- [ ] Template includes:
  - [ ] Recursive tree structure with indentation (using Tailwind)
  - [ ] Expand/collapse buttons per category (toggles visibility of children)
  - [ ] Drag-and-drop reorder (Vue Draggable or custom, within same parent)
  - [ ] Edit button → opens CategoryFormModal
  - [ ] Delete button → shows confirmation, calls API
  - [ ] Icon display (CSS class-based)
  - [ ] Category name (Arabic RTL or English LTR)
  - [ ] Sort order visible (optional)

- [ ] Script logic:
  - [ ] Fetch categories on mount: `GET /api/v1/categories`
  - [ ] Nested children rendering via recursion
  - [ ] `v-for` with `:key="category.id"`
  - [ ] `@dragstart` / `@dragend` handlers for reorder
  - [ ] Drag-and-drop calls `PUT /api/v1/categories/{id}/reorder` with new sort_order
  - [ ] Edit click emits event or opens modal (or uses component state / Pinia store)
  - [ ] Delete click shows confirmation dialog, calls `DELETE /api/v1/categories/{id}`
  - [ ] Error handling with toast/notification display
  - [ ] Loading state (spinner) while fetching

- [ ] Styling:
  - [ ] Uses Tailwind v4 class names
  - [ ] RTL support: `dir="rtl"` on container if Arabic, or conditional `ml-` / `mr-` classes
  - [ ] Indentation via nested padding/margin (e.g., `ml-{{ level * 4 }}`)
  - [ ] Hover states for interactive elements
  - [ ] Follows DESIGN.md visual style (Geist fonts, shadow-as-border, etc.)

- [ ] Composition API patterns:
  - [ ] `ref()` for reactive state (categories, loading, etc.)
  - [ ] `computed()` for derived state (e.g., sorted categories, filtered)
  - [ ] `onMounted()` for fetch lifecycle
  - [ ] Composables for shared logic (e.g., `useCategories()` if extracted)

#### CategoryFormModal

- [ ] File: `frontend/components/Categories/CategoryFormModal.vue`
- [ ] Props:
  - [ ] `isOpen` (boolean) - controls modal visibility
  - [ ] `category` (optional, Category object) - if editing, populated; if creating, null
  - [ ] `parentCategories` (optional, array) - list of available parents for parent_id selector

- [ ] Emits:
  - [ ] `@submit(categoryData)` - fired on form submit with validated data
  - [ ] `@close` - fired when modal closes
  - [ ] Error messages handled via emit or component state

- [ ] Form fields:
  - [ ] name_ar text input (Arabic placeholder, RTL)
  - [ ] name_en text input (English placeholder, LTR)
  - [ ] icon text input (optional, with help text)
  - [ ] parent_id dropdown/selector (uses CategorySelector component)
  - [ ] is_active checkbox
  - [ ] Submit / Cancel buttons (Nuxt UI UButton)

- [ ] Validation:
  - [ ] Client-side: VeeValidate + Zod schema
  - [ ] Schema mirrors backend rules (name_ar/name_en required, length bounds, etc.)
  - [ ] Displays field-level errors below each input
  - [ ] Submit button disabled during submission

- [ ] API integration:
  - [ ] POST /api/v1/categories (create)
  - [ ] PUT /api/v1/categories/{id} (update)
  - [ ] Handles response and error codes
  - [ ] On success: emit @submit, close modal, refresh parent list
  - [ ] On error: display validation errors or generic error message

- [ ] Uses Nuxt UI components:
  - [ ] `UModal` for container
  - [ ] `UForm` for form wrapper
  - [ ] `UInput` for text inputs
  - [ ] `USelect` or custom for dropdown
  - [ ] `UButton` for buttons
  - [ ] `UCheckbox` for is_active

#### CategoryBreadcrumb

- [ ] File: `frontend/components/Categories/CategoryBreadcrumb.vue`
- [ ] Props:
  - [ ] `categoryId` (required, number) - the current category

- [ ] Fetches ancestors on mount:
  - [ ] Could fetch via GET /api/v1/categories/{id} and traverse parent chain
  - [ ] Or call dedicated API endpoint (if implemented)
  - [ ] Or store full tree in Pinia and traverse in-memory

- [ ] Renders:
  - [ ] Breadcrumb path: "Root / Parent / Current Category"
  - [ ] Each link is clickable (navigates or emits event)
  - [ ] Separator ("/" or "›") between levels
  - [ ] Last item (current) may be non-linked (or linked)

- [ ] RTL support:
  - [ ] Separator direction aware (flip for RTL if needed)
  - [ ] Breadcrumb order preserved despite RTL (leftmost is root)

- [ ] Error handling:
  - [ ] If category not found, show placeholder or error
  - [ ] Loading state while fetching

#### CategorySelector

- [ ] File: `frontend/components/Categories/CategorySelector.vue`
- [ ] Props:
  - [ ] `modelValue` (optional, number) - selected category ID
  - [ ] `includeInactive` (optional, boolean) - default false

- [ ] Emits:
  - [ ] `@update:modelValue(categoryId)` - fired when selection changes

- [ ] Features:
  - [ ] Dropdown displaying full category tree with indentation
  - [ ] Search input to filter by name (Arabic + English, case-insensitive)
  - [ ] Hover/selected state for current selection
  - [ ] Click to select category
  - [ ] Can be single-select or multi-select (clarify use case)

- [ ] Integration:
  - [ ] Fetch categories on mount: `GET /api/v1/categories?include_inactive={{includeInactive}}`
  - [ ] Build tree structure with indentation
  - [ ] Filter on search input (client-side or API query)

- [ ] Uses Nuxt UI:
  - [ ] `UInput` for search
  - [ ] `USelect` or custom list for dropdown
  - [ ] Styling consistent with form inputs

### Pages & Layouts

#### Admin Category Management Page

- [ ] Route: `/admin/categories` (or `/dashboard/categories`)
- [ ] File: `frontend/pages/admin/categories.vue` or similar
- [ ] Layout: Uses admin layout with sidebar (or appropriate auth layout)

- [ ] Page structure:
  - [ ] Header: "Category Management" title
  - [ ] "Create Category" button (Nuxt UI UButton)
  - [ ] CategoryTreeComponent (main content)
  - [ ] CategoryFormModal (triggered by edit or create)

- [ ] Functionality:
  - [ ] On page load: Fetch categories via CategoryTreeComponent
  - [ ] "Create Category" opens modal in create mode (category = null)
  - [ ] Edit category: Passes category object to modal
  - [ ] Delete category: Shows confirmation, calls API
  - [ ] Reorder: Drag-and-drop in tree
  - [ ] Error/success notifications (toast)

- [ ] Middleware:
  - [ ] Requires authentication (auth middleware)
  - [ ] Requires admin role (custom middleware or policy check)
  - [ ] Redirects to login or 403 if unauthorized

- [ ] Styling:
  - [ ] Full-width responsive layout
  - [ ] Follows DESIGN.md theme
  - [ ] Tailwind responsive breakpoints

### Composables & Stores

#### useCategories Composable (Optional)

- [ ] File: `frontend/composables/useCategories.ts`
- [ ] Provides reactive category operations:
  - [ ] `categories` (ref) - cached category tree
  - [ ] `isLoading` (ref) - fetch state
  - [ ] `error` (ref) - error message
  - [ ] `fetchCategories()` - GET /api/v1/categories
  - [ ] `createCategory(payload)` - POST /api/v1/categories
  - [ ] `updateCategory(id, payload)` - PUT /api/v1/categories/{id}
  - [ ] `deleteCategory(id)` - DELETE /api/v1/categories/{id}
  - [ ] `reorderCategory(id, newSortOrder)` - PUT /api/v1/categories/{id}/reorder

- [ ] Composable used by components to avoid duplication

#### Pinia Store (Optional)

- [ ] File: `frontend/stores/categories.ts`
- [ ] State:
  - [ ] `categories` (array) - full category tree
  - [ ] `isLoading` (boolean)
  - [ ] `error` (string | null)

- [ ] Getters:
  - [ ] `getTree` - returns full tree
  - [ ] `getById(id)` - find category by ID
  - [ ] `getChildren(parentId)` - get children of a parent
  - [ ] `getAncestors(id)` - get path to root

- [ ] Actions:
  - [ ] `fetchCategories()` - populate store from API
  - [ ] `addCategory(category)` - optimistic add
  - [ ] `updateCategory(id, updates)` - optimistic update
  - [ ] `deleteCategory(id)` - optimistic delete
  - [ ] `reorderCategory(id, newSortOrder)` - optimistic reorder

### i18n & RTL Support

- [ ] Category names render in correct script (Arabic vs. English)
- [ ] All UI labels have i18n keys:
  - [ ] `categories.title` → "Category Management"
  - [ ] `categories.create` → "Create Category"
  - [ ] `categories.edit` → "Edit Category"
  - [ ] `categories.delete` → "Delete Category"
  - [ ] `categories.form.name_ar` → "Arabic Name"
  - [ ] `categories.form.name_en` → "English Name"
  - [ ] ... (all form labels, buttons, messages)

- [ ] Locale files:
  - [ ] `frontend/locales/ar.json` - Arabic messages
  - [ ] `frontend/locales/en.json` - English messages
  - [ ] Keys mirror Laravel backend messages for consistency

- [ ] RTL layout:
  - [ ] Flag-based conditional classes: `<div :class="isArabic && 'rtl'">`
  - [ ] Or global `dir="rtl"` attribute on `<html>` when Arabic active
  - [ ] Tailwind logical properties: `ms-` / `me-` (margin-start/-end), `ps-` / `pe-` (padding), etc.
  - [ ] Grid/flex direction aware of RTL flow

### Testing (Frontend)

- [ ] **Component Tests** (Vitest + Vue Test Utils):
  - [ ] `CategoryTreeComponent.spec.ts`:
    - [ ] Renders tree with nested structure
    - [ ] Expand/collapse toggles visibility
    - [ ] Drag-and-drop triggers reorder API call
    - [ ] Edit/delete buttons emit events
  - [ ] `CategoryFormModal.spec.ts`:
    - [ ] Form validation works
    - [ ] Submit calls correct API endpoint (create vs. update)
    - [ ] Error messages display
    - [ ] Modal closes on submit/cancel
  - [ ] `CategoryBreadcrumb.spec.ts`:
    - [ ] Renders correct breadcrumb path
    - [ ] Links are clickable
  - [ ] `CategorySelector.spec.ts`:
    - [ ] Renders tree
    - [ ] Search filters categories
    - [ ] Selection emits update event

- [ ] **E2E Tests** (Playwright):
  - [ ] `admin-categories.spec.ts`:
    - [ ] Admin navigates to /admin/categories
    - [ ] Admin creates category (form submission, API call)
    - [ ] Admin edits category
    - [ ] Admin deletes category (with confirmation)
    - [ ] Admin reorders categories (drag-and-drop)
    - [ ] Non-admin is redirected or sees 403

- [ ] All tests mock API responses or use test database
- [ ] Tests validate component output and user interactions (not just snapshot testing)

---

## API Contract Validation

### Request/Response Format

- [ ] All API responses follow standardized contract:

  ```json
  {
    "success": true/false,
    "data": {...} or null,
    "error": {...} or null
  }
  ```

- [ ] Success response includes all category fields (see CategoryResource)
- [ ] Error response includes:
  - [ ] `code` (machine-readable, e.g., "VALIDATION_ERROR")
  - [ ] `message` (user-friendly, localized)
  - [ ] `details` (field-level errors for validation)

- [ ] Error codes used:
  - [ ] VALIDATION_ERROR (422)
  - [ ] AUTH_UNAUTHORIZED (403)
  - [ ] RBAC_ROLE_DENIED (403)
  - [ ] RESOURCE_NOT_FOUND (404)
  - [ ] WORKFLOW_INVALID_TRANSITION (422 for circular reference)
  - [ ] SERVER_ERROR (500)

### HTTP Status Codes

- [ ] 200: Success (GET, PUT without explicit response payload)
- [ ] 201: Resource created (POST)
- [ ] 204: Success, no content (DELETE)
- [ ] 400: Bad request (malformed)
- [ ] 401: Unauthenticated
- [ ] 403: Forbidden (auth issue or RBAC)
- [ ] 404: Not found
- [ ] 422: Unprocessable (validation error)
- [ ] 429: Rate limited
- [ ] 500: Server error

### Error Response Examples

- [ ] **Validation Error**:

  ```json
  {
    "success": false,
    "data": null,
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "The given data was invalid.",
      "details": {
        "name_ar": ["الحقل مطلوب."],
        "name_en": ["The field is required."]
      }
    }
  }
  ```

- [ ] **RBAC Denial**:

  ```json
  {
    "success": false,
    "data": null,
    "error": {
      "code": "RBAC_ROLE_DENIED",
      "message": "You don't have permission to perform this action.",
      "details": null
    }
  }
  ```

- [ ] **Circular Reference**:
  ```json
  {
    "success": false,
    "data": null,
    "error": {
      "code": "WORKFLOW_INVALID_TRANSITION",
      "message": "Invalid parent: circular reference detected.",
      "details": { "parent_id": ["Cannot set a parent that is a descendant of this category."] }
    }
  }
  ```

---

## Database & Performance

- [ ] Seeder completes in <2 seconds (for ~20 categories)
- [ ] Tree query (1000 categories) completes in <500ms
- [ ] Indexes are created and optimal:
  - [ ] Primary key: id
  - [ ] Unique: slug
  - [ ] Composite: (parent_id, sort_order, is_active, deleted_at)

- [ ] N+1 query prevention:
  - [ ] CategoryRepository uses eager loading (with())
  - [ ] API Resource eager loads children where needed
  - [ ] Frontend fetches once on mount, caches in state

---

## Documentation

- [ ] API documentation updated (OpenAPI/Swagger or inline in routes)
- [ ] Category model documented in code comments
- [ ] Service public method signatures documented
- [ ] Complex logic (e.g., circular reference check) explained
- [ ] README/guides updated if relevant to category system

---

## Deployment & Migration

- [ ] Migration is safe and non-destructive
- [ ] Seeder can be rolled back (or is idempotent)
- [ ] Database schema supports future expansion (e.g., category metadata fields)
- [ ] No schema locks or breaking changes
- [ ] Deployment checklist:
  - [ ] Run migration: `php artisan migrate`
  - [ ] Seed categories: `php artisan db:seed --class=CategorySeeder`
  - [ ] Clear cache if applicable: `php artisan cache:clear`
  - [ ] Frontend build completes without errors: `npm run build`

---

## Sign-Off & Acceptance

- [ ] All backend functional requirements met and tested
- [ ] All frontend functional requirements met and tested
- [ ] API contract validated against specification
- [ ] RBAC enforcement verified (admin-only writes)
- [ ] Error handling and response formats correct
- [ ] Arabic/English bilingual content working
- [ ] Database seeder idempotent and populates correctly
- [ ] Documentation complete
- [ ] Code follows Bunyan architecture and conventions
- [ ] No linting or type errors (`npm run lint`, `composer run lint`)
- [ ] All tests passing (`npm run test`, `php artisan test`)
- [ ] Ready for integration testing with Product feature (STAGE_08)

---

**Approval Gate**: Feature is production-ready when all checkboxes are complete and signed off by code review.
