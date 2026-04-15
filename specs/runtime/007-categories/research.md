# Phase 0 — Research: Category Hierarchy System

**Date**: 2026-04-15  
**Stage**: STAGE_07_CATEGORIES  
**Researcher**: AI Planning Agent

---

## Dependency Analysis

### Upstream Dependencies (STAGE_06_API_FOUNDATION)

The category system depends on foundational infrastructure completed in STAGE_06:

| Dependency              | Usage                                                                  | Critical? |
| ----------------------- | ---------------------------------------------------------------------- | --------- |
| Error Response Contract | All API responses must use Bunyan standard format (success/data/error) | ✅ Yes    |
| RBAC Middleware         | Admin-only routes require middleware authentication & role checking    | ✅ Yes    |
| API Resource Pattern    | CategoryResource transforms models for API responses                   | ✅ Yes    |
| Form Request Validation | StoreCategoryRequest, UpdateCategoryRequest for input validation       | ✅ Yes    |
| Repository Pattern      | CategoryRepository follows established data access layer pattern       | ✅ Yes    |
| Service Layer Pattern   | CategoryService encapsulates business logic per Bunyan conventions     | ✅ Yes    |
| Eloquent Model Patterns | Model relationships, scopes, soft deletes (from STAGE_02/STAGE_03)     | ✅ Yes    |
| Database Connection     | MySQL 8.x with utf8mb4 collation (STAGE_02)                            | ✅ Yes    |

### Downstream Dependencies (STAGE_08_PRODUCTS)

The category system will be consumed by:

| Dependent                    | Need                                                      | Impact                                    |
| ---------------------------- | --------------------------------------------------------- | ----------------------------------------- |
| Product Model                | Products reference categories via category_id foreign key | Must be available before products created |
| ProductController            | List/filter products by category_id                       | Category API must be stable               |
| CategorySelector Component   | Used in ProductForm for category selection                | Frontend component reusable               |
| CategoryBreadcrumb Component | Product detail pages show category breadcrumb             | Component reusable in product display     |

**Conclusion**: Categories are upstream to Products. This stage is a **prerequisite** for STAGE_08.

---

## Architecture & Layering Context

### Bunyan Architectural Pattern Alignment

The category system MUST follow established Bunyan architecture (from AGENTS.md):

```
Routes → Middleware (Auth, RBAC) → Controller (thin) → Service (business logic) → Repository (DB access) → Model → Database
```

**Layer Responsibilities**:

| Layer          | Responsibility                                   | Examples                                                     |
| -------------- | ------------------------------------------------ | ------------------------------------------------------------ |
| **Models**     | Eloquent relationship definitions, casts, scopes | Category (with parent/children relationships, soft deletes)  |
| **Repository** | Database queries, tree traversal, filtering      | CategoryRepository::getTree(), getChildren(), getAncestors() |
| **Service**    | Business logic, validation, transactions         | CategoryService::create(), move(), reorder()                 |
| **Requests**   | Input validation per endpoint                    | StoreCategoryRequest, UpdateCategoryRequest                  |
| **Resources**  | API response transformation                      | CategoryResource with nested children                        |
| **Controller** | Route handler, delegates to service              | CategoryController (thin, mostly pass-through)               |
| **Middleware** | Auth, RBAC enforcement                           | Only Admin can POST/PUT/DELETE                               |

**Key Principle**: Controllers must NOT contain business logic; all validation and logic lives in Service or Repository.

---

## Edge Cases & Risk Factors

### 1. Circular Parent References

**Risk**: Admin sets category A's parent to B, then B's parent to A → circular dependency.

**Prevention**:

- Validation in StoreCategoryRequest: check parent_id doesn't create cycle
- API endpoint:
  ```php
  if ($request->parent_id) {
      $isCircular = Category::find($request->parent_id)
          ->descendants()
          ->where('id', $category->id)
          ->exists();
      if ($isCircular) throw new ValidationException('Parent creates cycle');
  }
  ```

**Test**: Unit test CategoryService::checkCircularReference()

---

### 2. Orphaned Records After Soft-Delete

**Risk**: Admin soft-deletes parent category; children remain with parent_id pointing to deleted record.

**Design Decision**: **Children remain orphaned** (per Clarification 2).

- Preserves audit trail
- Admins can restore parent and relink if needed
- Hidden from normal queries via scopes

**Implementation**:

- DELETE endpoint: only sets deleted_at on target category
- getTree() uses:
  ```php
  ->where('deleted_at', null)  // Exclude deleted categories
  ->with(['children' => fn($q) => $q->where('deleted_at', null)])  // Recursively exclude
  ```

**Test**: Feature test verifies deleted parent's children are hidden from public tree

---

### 3. Duplicate Slugs & Collision Handling

**Risk**: Two categories with same name_en generate identical slugs.

**Prevention**:

- Database unique constraint: `unique('slug')` in migration
- Slug generation in CategoryService::create():
  ```php
  $slug = Str::slug($data['name_en']);
  $counter = 1;
  while (Category::where('slug', $slug)->where('id', '!=', $category->id ?? 0)->exists()) {
      $slug = Str::slug($data['name_en']) . '-' . $counter++;
  }
  ```

**Immutability**: Slug cannot change after creation (FR-013 specifies slug is NOT in UPDATE payload).

**Test**: Unit test collision detection; feature test name collision handled gracefully

---

### 4. N+1 Query Prevention (Tree Rendering)

**Risk**: GET /api/v1/categories fetches parent category, then iterates children making 1 query per child → N+1 queries.

**Prevention**:

- Use eager loading with `with('children')` recursively:
  ```php
  Category::with(['children' => fn($q) => $q->orderBy('sort_order')])->whereNull('parent_id')->get()
  ```
- OR use single query with subquery to fetch all and assemble tree in PHP

**Performance Requirement**: tree endpoint must return in <500ms for 1000 categories.

**Test**: Feature test with 1000 categories; measure query count and response time (should be 1-2 queries)

---

### 5. Concurrent Reorder Conflicts (Optimistic Locking)

**Risk**: Two clients reorder same siblings simultaneously → lost updates.

**Prevention**: **Optimistic locking** (FR-015):

- Add `version` field (integer, default 0) to categories table
- Increment version on every update
- Reorder request includes current version:
  ```json
  { "sort_order": 2, "version": 5 }
  ```
- Repository checks:
  ```php
  ->where('version', $request->version)
  ->increment('version')
  ```
- If version mismatch, return 409 CONFLICT_ERROR with current category (client retries)

**Test**: Feature test simulates concurrent reorder requests; one succeeds, other gets 409

---

### 6. Empty Category Hierarchy on Initial Setup

**Risk**: Admin opens category management page, sees empty tree, no "Create Category" button visible.

**Design Decision**: Always show "Create Category" button regardless of tree state.

**Test**: Before any categories exist, verify UI shows empty state with accessible Create button

---

### 7. Unicode/RTL Name Truncation in UI

**Risk**: Long Arabic names overflow table cells, breaking layout.

**Prevention**:

- Use Tailwind `line-clamp-2` or `truncate` classes
- CSS: `word-break: break-word`
- Test with long Arabic names (>100 chars)

**Test**: E2E test renders category with 100+ char Arabic name; no layout breakage

---

## Performance Considerations

### Query Optimization

| Operation             | Query Strategy                                                                 | Expected Time              |
| --------------------- | ------------------------------------------------------------------------------ | -------------------------- |
| **Get full tree**     | Single query with recursive CTE OR N queries with eager loading + PHP assembly | <500ms for 1000 categories |
| **Get children only** | INDEX on (parent_id, sort_order, deleted_at)                                   | <100ms                     |
| **Get ancestors**     | Recursive query OR traversal from category up to root                          | <100ms                     |
| **Reorder siblings**  | Update sort_order for siblings; use transaction                                | <200ms                     |

### Database Indexes

```sql
CREATE INDEX idx_categories_parent_id ON categories(parent_id);
CREATE INDEX idx_categories_parent_sort ON categories(parent_id, sort_order, is_active);
CREATE INDEX idx_categories_deleted_at ON categories(deleted_at);
CREATE UNIQUE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_categories_is_active ON categories(is_active);
```

**Indexing Rationale**:

- `parent_id`: Essential for getChildren() queries
- `(parent_id, sort_order)`: Composite for tree traversal with ordering
- `deleted_at`: For withoutTrashed() filtering (soft delete optimization)
- `slug`: Unique slug lookup and API routes like GET /categories/{slug}
- `is_active`: Filter by status

### API Response Caching

- **GET /api/v1/categories** (full tree): Cache for 5-15 minutes (short TTL due to admin edits)
  - Invalidate on POST/PUT/DELETE category
- **GET /api/v1/categories/{id}** (single): Cache for 1 hour (less frequently updated)
- Frontend CategorySelector dropdown: Cache tree in Pinia store; invalidate after mutations

---

## RTL/Arabic Implementation Concerns

### Collation & Charset

- Migration ensures `utf8mb4_unicode_ci` collation for all text columns
- Supports full Unicode including Arabic diacritics, emojis, etc.
- Frontend JSON responses automatically use UTF-8 (JSON spec)

### Form Validation Messages

- Both `name_ar` and `name_en` are required
- Validation messages must be localized (Arabic/English) via Laravel's `lang/ar/` and `lang/en/`
- Resource responses include both names; client chooses which to display based on locale

### UI Layout

- **Breadcrumb component**: Uses RTL flexbox (flex-row-reverse or dir="rtl")
- **Tree indentation**: Use CSS margin-inline-start (logical property) to handle RTL
- **Drag-and-drop**: @dnd-kit handles RTL natively via dir="rtl" on parent
- **Form inputs**: dir="rtl" for Arabic, dir="ltr" for English

---

## Soft-Delete Cascading & Visibility Scoping

### Soft-Delete Behavior

| Scenario                               | Behavior                                                 | Rationale                                        |
| -------------------------------------- | -------------------------------------------------------- | ------------------------------------------------ |
| **Admin soft-deletes category**        | Set deleted_at; children remain with parent_id           | Preserves audit trail, allows restoration        |
| **Admin restores category**            | Set deleted_at = null                                    | Children automatically reappear in tree          |
| **Query category tree**                | Exclude deleted_at IS NULL by default (withoutTrashed()) | Hidden from end users                            |
| **Admin audit query**                  | Include deleted categories via withTrashed()             | Admin-only visibility                            |
| **Product query filtered by category** | Products linked to deleted category are inaccessible     | Effectively hides products of deleted categories |

### Query Scopes Required

```php
// Category model
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true)->whereNull('deleted_at');
}

public function scopeForTree(Builder $query): Builder
{
    return $query->withoutTrashed()->where('is_active', true);
}
```

**Test**: Feature test verifies soft-deleted categories are hidden from GET endpoints but visible via withTrashed() scope

---

## Version & Optimistic Locking

### Why Version Field?

Without optimistic locking, concurrent updates can lose data:

```
Client A reads version=5
Client B reads version=5
Client B updates, increments to version=6
Client A updates assuming version=5, succeeds (lost Client B's update)
```

### Implementation

- Add `version` integer to categories table (default 0)
- On every update: `->where('version', $oldVersion)->increment('version')`
- If no rows updated, version mismatch detected; return 409 CONFLICT_ERROR
- Client retries with fresh data

**Table Field Schema**:

```sql
ALTER TABLE categories ADD COLUMN version INT UNSIGNED DEFAULT 0 NOT NULL;
```

---

## Slug Immutability & URL Stability

### Why Immutable?

- URLs like `/categories/concrete` rely on stable slug
- Changing slug breaks existing links (breaks SEO, bookmarks, product URLs)
- If name_en needs to change, name_en is the display field; slug is the identifier

### Implementation

- Slug is generated once in `CategoryService::create()` from name_en
- PUT endpoint does NOT accept slug in request payload
- Even if name_en changes, slug remains unchanged
- Database constraint: UNIQUE on slug (no duplicates even after deletion)

**Test**: Update category name_en; verify slug remains unchanged

---

## Risk Summary Table

| Risk                          | Severity | Mitigation                     | Tested           |
| ----------------------------- | -------- | ------------------------------ | ---------------- |
| Circular references           | High     | Validation in service          | Unit test        |
| Orphaned records after delete | Medium   | By design (audit preservation) | Feature test     |
| Duplicate slugs               | Medium   | Unique constraint + counter    | Unit test        |
| N+1 queries                   | High     | Eager loading + indexes        | Performance test |
| Concurrent reorder loss       | Medium   | Optimistic locking (version)   | Feature test     |
| Empty hierarchy UX            | Low      | Always show Create button      | E2E test         |
| RTL text overflow             | Low      | CSS truncation + testing       | E2E test         |
| Soft-delete visibility        | Medium   | Query scopes (withoutTrashed)  | Feature test     |

---

## Glossary & Definitions

| Term                   | Definition                                                              |
| ---------------------- | ----------------------------------------------------------------------- |
| **Tree**               | Full hierarchical category structure returned by GET /api/v1/categories |
| **Parent**             | Category that has children; references itself via parent_id             |
| **Child**              | Category with a parent_id pointing to another category                  |
| **Root**               | Top-level category with parent_id = null                                |
| **Ancestor**           | Any parent or grandparent (recursively up to root)                      |
| **Descendant**         | Any child or grandchild (recursively down to leaves)                    |
| **Sibling**            | Categories with same parent_id                                          |
| **Sort Order**         | Numeric field determining display sequence within siblings              |
| **Slug**               | URL-safe identifier auto-generated from name_en, immutable              |
| **Soft Delete**        | Set deleted_at timestamp instead of hard-deleting record                |
| **Orphan**             | Record with parent_id pointing to deleted parent (allowed by design)    |
| **Optimistic Locking** | Version field prevents concurrent update conflicts                      |
| **N+1 Query Problem**  | Loading parent + one query per child instead of bulk fetch              |

---

## Open Questions Resolved (From Spec Clarifications)

✅ **Slug Immutability**: Decided → Slugs are immutable after creation (Clarification 1)  
✅ **Parent Soft-Delete Cascade**: Decided → Children remain with parent_id intact (Clarification 2)  
✅ **Tree Response Format**: Decided → Nested structure with recursive children arrays (Clarification 3)

---

**Next Steps**: Phase 1 begins with data-model.md generation and migration planning.
