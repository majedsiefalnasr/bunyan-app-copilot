# Performance Checklist — STAGE_07_CATEGORIES

**Purpose**: Unit test for requirement quality and completeness of performance controls.  
**Generated**: 2026-04-15  
**Spec Reference**: specs/runtime/007-categories/spec.md

---

## Query Optimization (NFR-004, eloquent-orm-patterns skill)

- [ ] **CHK-PERF-001**: Category model defines relationships correctly (parent, children)
  - **Requirement**: NFR-004 "queries must support eager loading / select N+1 optimization"
  - **Validation**: app/Models/Category.php defines BelongsTo (parent) and HasMany (children) relationships
  - **Example**:
    ```php
    public function parent(): BelongsTo {
        return $this->belongsTo(Category::class, 'parent_id');
    }
    public function children(): HasMany {
        return $this->hasMany(Category::class, 'parent_id');
    }
    ```

- [ ] **CHK-PERF-002**: Tree queries use eager loading to prevent N+1
  - **Requirement**: eloquent-orm-patterns "Always eager load relationships"
  - **Validation**: CategoryRepository::getTree() calls `load(['children', 'parent'])` or uses `with()` in query builder
  - **Example**:
    ```php
    public function getTree(): array {
        return Category::with('children')->whereNull('parent_id')->get();
        // NOT: Category::whereNull('parent_id')->get(); // Iterating children would trigger N+1
    }
    ```

- [ ] **CHK-PERF-003**: Nested children loaded recursively without additional queries
  - **Requirement**: FR-010 "full nested tree hierarchy with recursive children arrays"
  - **Validation**: Single query call to getTree() returns full tree; Eloquent eager loads all children levels
  - **Example**: Using Laravel's recursive relationship loading or explicit load->map pattern

- [ ] **CHK-PERF-004**: API response avoids N+1 when serializing nested resources
  - **Requirement**: FR-007 CategoryResource, Clarification 3
  - **Validation**: Profiler shows 1 query for getTree() + 1 for CategorySeeder regardless of tree depth
  - **Example**:
    ```php
    // Controller: Load all at once, not per-breadcrumb
    $categories = Category::with(['children' => fn($q) => $q->with('children')])
        ->whereNull('parent_id')
        ->get();
    return CategoryResource::collection($categories);
    ```

- [ ] **CHK-PERF-005**: Breadcrumb component fetches ancestors via single query
  - **Requirement**: SC-005 "Breadcrumb renders correct ancestor chain without N+1 queries"
  - **Validation**: Front-end composable calls `/api/v1/categories/{id}/ancestors` which returns path in one query; no loop of GET /api/v1/categories/{id}
  - **Example**:
    ```php
    // CategoryRepository
    public function getAncestors($categoryId): Collection {
        $ancestors = [];
        $current = Category::with('parent')->find($categoryId);
        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }
        return collect($ancestors);
    }
    ```

- [ ] **CHK-PERF-006**: Select only needed columns in queries (avoid SELECT \*)
  - **Requirement**: eloquent-orm-patterns "Use select() to limit columns"
  - **Validation**: Repository queries include select() for frontend list view (id, name_ar, name_en, slug, sort_order, icon)
  - **Example**:
    ```php
    Category::select(['id', 'name_ar', 'name_en', 'slug', 'sort_order', 'icon', 'parent_id', 'is_active'])
        ->with('children:id,name_ar,name_en')
        ->whereNull('parent_id')
        ->get();
    ```

---

## Database Indexing (eloquent-orm-patterns, NFR-004)

- [ ] **CHK-PERF-007**: Index on parent_id column for tree traversal
  - **Requirement**: eloquent-orm-patterns "Index foreign keys", NFR-004 "tree queries should use indexes"
  - **Validation**: Migration includes `$table->foreign('parent_id')->references('id')->on('categories')`; index auto-created or explicit `$table->index('parent_id')`
  - **Example**:
    ```php
    $table->foreignId('parent_id')
        ->nullable()
        ->constrained('categories')
        ->cascadeOnDelete();
    ```

- [ ] **CHK-PERF-008**: Index on (parent_id, sort_order) for ordered tree queries
  - **Requirement**: Implementation Notes "Consider adding index on (parent_id, sort_order, is_active, deleted_at)"
  - **Validation**: Migration includes composite index `$table->index(['parent_id', 'sort_order'])`
  - **Example**:
    ```php
    $table->index(['parent_id', 'sort_order', 'is_active', 'deleted_at']);
    ```

- [ ] **CHK-PERF-009**: Unique index on slug column
  - **Requirement**: FR-001 "slug (unique, auto-generated)"
  - **Validation**: Migration has `$table->string('slug')->unique()`
  - **Example**: Single write seeks will not lock table during category creation (slug uniqueness validated before insert)

- [ ] **CHK-PERF-010**: Index on is_active for filtering active categories
  - **Requirement**: FR-010 default scope filters by is_active
  - **Validation**: Migrations include `$table->index('is_active')` or composite index includes it
  - **Example**: Query plan shows index used for `WHERE is_active = 1`

- [ ] **CHK-PERF-011**: Index on deleted_at for soft delete queries
  - **Requirement**: Clarification 4 "Soft-deleted categories excluded", NFR-004
  - **Validation**: Composite index includes deleted_at: `$table->index(['deleted_at', 'parent_id'])`
  - **Example**: Queries with `WHERE deleted_at IS NULL` use index seek

---

## API Response Time (NFR-009, SC-002)

- [ ] **CHK-PERF-012**: Tree listing API response time < 500ms for 1000 categories
  - **Requirement**: NFR-009 "API response times must be <500ms for typical hierarchies (100-1000 categories)"
  - **Validation**: Load test GET /api/v1/categories with 1000 records; response time < 500ms; measure with Laravel Telescope or Clockwork
  - **Example**:
    ```bash
    # Simulate 1000 categories
    php artisan db:seed --class=CategorySeeder  # creates 1000 records
    # Measure: time curl http://localhost:8000/api/v1/categories
    # Expected: < 500ms
    ```

- [ ] **CHK-PERF-013**: Single category GET response time < 100ms
  - **Requirement**: SC-002 API response times
  - **Validation**: GET /api/v1/categories/{id} completes in < 100ms (single record + children eager load)
  - **Example**: Profiler shows 1-2 queries in < 100ms

- [ ] **CHK-PERF-014**: Category create/update response time < 300ms
  - **Requirement**: SC-001 "admin can create ≥10 categories in <10 seconds" → ~1s per operation max
  - **Validation**: POST /api/v1/categories completes in < 300ms; includes validation, slug generation, sorting recalculation
  - **Example**: Profiler shows batch insert with query time < 50ms + app logic < 250ms

---

## Response Caching Strategy

- [ ] **CHK-PERF-015**: Category tree cached for READ-HEAVY operations
  - **Requirement**: optimizable via cache since categories change infrequently
  - **Validation**: CategoryRepository::getTree() checks cache first; cache invalidated on POST/PUT/DELETE
  - **Example**:
    ```php
    public function getTree(): array {
        return Cache::remember('category-tree', 3600, function () {
            return Category::with('children')->whereNull('parent_id')->get();
        });
    }
    ```

- [ ] **CHK-PERF-016**: Cache invalidated on category create/update/delete
  - **Requirement**: Data consistency — cache must reflect current state
  - **Validation**: After any write operation, Cache::forget('category-tree') called
  - **Example**:
    ```php
    public function create(array $data): Category {
        $category = $this->model->create($data);
        Cache::forget('category-tree');
        return $category;
    }
    ```

- [ ] **CHK-PERF-017**: ETags or Last-Modified headers returned for category list
  - **Requirement**: Optimization for client-side caching
  - **Validation**: GET /api/v1/categories returns `ETag` or `Last-Modified` header; client caches if unchanged (304 Not Modified)
  - **Example**:
    ```php
    response($categories)->setEtag(md5(serialize($categories)));
    ```

---

## Frontend Performance (NFR-010, SC-006)

- [ ] **CHK-PERF-018**: Category tree component renders 1000+ categories without blocking
  - **Requirement**: NFR-010 "tree rendering must handle 1000+ categories without degradation"
  - **Validation**: Rendering test in Playwright or Vitest shows tree mounts in < 2s even with 1000 items; no UI thread blocking
  - **Example**: Vue component with virtual scrolling or lazy rendering

- [ ] **CHK-PERF-019**: Category selector dropdown loads and filters 500+ categories in < 1s
  - **Requirement**: SC-006 "Category selector loads and filters 500+ categories in <1s"
  - **Validation**: Playwright test: open dropdown → search for text → filtered results appear in < 1s
  - **Example**:
    ```typescript
    // Vitest
    const { getByRole } = render(CategorySelector);
    await userEvent.type(getByRole('searchbox'), 'concrete');
    const filtered = screen.getAllByRole('option');
    expect(filtered.length).toBeLessThan(50); // should filter significantly
    expect(performance.now() - start).toBeLessThan(1000);
    ```

- [ ] **CHK-PERF-020**: Tree component uses lazy expansion (expand only visible nodes)
  - **Requirement**: optimizable, aligns with NFR-010
  - **Validation**: Render expanded tree with 1000 items; initially only visible nodes are in DOM; hidden nodes rendered on expand
  - **Example**: Nuxt UI UTree or custom implementation with `v-show="isExpanded"`

- [ ] **CHK-PERF-021**: Frontend pagination or virtualization for large lists
  - **Requirement**: Category list on admin page handles many categories
  - **Validation**: Admin page loads first 50 categories; scroll or click "Load More" to fetch next batch
  - **Example**: `useInfiniteQuery()` from @tanstack/vue-query or Pinia pagination store

---

## Bundle Size and Code Splitting

- [ ] **CHK-PERF-022**: Category components lazy-loaded in admin pages
  - **Requirement**: Frontend performance optimization
  - **Validation**: pages/admin/categories.vue uses `defineAsyncComponent()` for CategoryTree, CategoryForm
  - **Example**:
    ```typescript
    const CategoryTree = defineAsyncComponent(() => import('@/components/CategoryTree.vue'));
    ```

- [ ] **CHK-PERF-023**: API Resources serialization optimized (no circular references)
  - **Requirement**: Response payload size optimization
  - **Validation**: CategoryResource children array does not include parent reference (prevents circular JSON); payload size < 200KB for 1000 categories
  - **Example**: Resource excludes `parent: new ...()`; only includes `parent_id`

---

## Rate Limiting (security-hardening also applies)

- [ ] **CHK-PERF-024**: Rate limiting applied to category endpoints
  - **Requirement**: Prevent abuse; security-hardening pattern
  - **Validation**: Exceeded requests return 429 RATE_LIMIT_EXCEEDED
  - **Example**:
    ```php
    Route::middleware(['auth:sanctum', 'throttle:60,1'])
        ->get('/categories', [CategoryController::class, 'index']);
    ```

---

## Seeder Performance (FR-009)

- [ ] **CHK-PERF-025**: Seeder creates 10+ categories efficiently (batch insert recommended)
  - **Requirement**: FR-009 "seeder must be idempotent"
  - **Validation**: `php artisan db:seed --class=CategorySeeder` completes in < 1s; idempotent (running twice = same data, no duplicates)
  - **Example**:
    ```php
    Category::upsert([
        ['name_ar' => 'مواد بناء', 'name_en' => 'Building Materials', 'slug' => 'building-materials'],
        // ... more categories
    ], ['slug'], ['name_ar', 'name_en', 'is_active']);
    ```

---

## Monitoring and Observability (observability-standards)

- [ ] **CHK-PERF-026**: Slow query logging configured for category queries
  - **Requirement**: Proactive performance detection
  - **Validation**: app/Logging/ configured; queries > 100ms logged with correlationId
  - **Example**:
    ```php
    DB::enableQueryLog();
    if ($duration > 100) {
        Log::warning('Slow category query', ['duration' => $duration, 'correlation_id' => $correlationId]);
    }
    ```

- [ ] **CHK-PERF-027**: Response time metrics tracked (APM or monitoring tool)
  - **Requirement**: Performance baseline establishment
  - **Validation**: GET /api/v1/categories, POST /api/v1/categories, PUT /api/v1/categories/{id} response times tracked in New Relic, Datadog, or similar
  - **Example**: Middleware logs response time with context

---

## Database Connection Pooling

- [ ] **CHK-PERF-028**: Database connection pool configured for concurrent requests
  - **Requirement**: Handle multiple concurrent category operations
  - **Validation**: .env DATABASE_POOL or connection pool config set appropriately for expected concurrency
  - **Example**: `DB_POOL=5` in .env for local dev, higher for production

---

## Soft Delete Query Impact (NFR-004, Clarification 4)

- [ ] **CHK-PERF-029**: Soft delete queries include IS NULL check on deleted_at (indexed)
  - **Requirement**: Clarification 4 "soft-deleted categories globally invisible"
  - **Validation**: Generated SQL includes `WHERE deleted_at IS NULL`; index on deleted_at used
  - **Example**: Query plan shows Index Scan on deleted_at column

- [ ] **CHK-PERF-030**: withoutTrashed() scope automatically applied to Category model
  - **Requirement**: NFR-004, eloquent-orm-patterns soft delete scope
  - **Validation**: Category::get() automatically excludes soft-deleted; Category::withTrashed()->get() includes deleted
  - **Example**: Global scope in Category model auto-applies
