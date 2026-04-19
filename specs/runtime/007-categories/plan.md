# Implementation Plan: Product Category Hierarchy

**Stage**: STAGE_07_CATEGORIES
**Phase**: 02_CATALOG_AND_INVENTORY
**Date**: 2026-04-15
**Status**: Ready for Implementation

---

## Executive Summary

This plan outlines the implementation roadmap for a hierarchical product category system for the Bunyan construction marketplace. The system supports multi-level category nesting, Arabic/English bilingual support, admin-driven management with drag-and-drop reordering, and full RBAC enforcement.

**Key Characteristics**:

- **Self-referential hierarchy**: Single categories table with parent_id foreign key
- **Soft-delete preservation**: Deleted categories hidden but audit-retained
- **Optimistic locking**: Version field prevents concurrent update conflicts
- **Immutable slugs**: URL-stable identifiers after creation
- **Tree API**: Nested children arrays for modern REST responses
- **RTL-Ready**: Full UTF-8/Arabic support with RTL layout components

**Timeline Estimate**: 5-7 working days (backend: 2d, frontend: 2d, testing: 1-2d)

---

## Architecture Overview

### Layer Breakdown

```
┌─ Routes (api/v1/categories) ──────────────────────────────┐
│                                                            │
├─ Middleware (Auth, RBAC) ─────────────────────────────────┤
│  - Sanctum token validation                               │
│  - Admin-only enforcement (POST/PUT/DELETE)               │
│                                                            │
├─ Controller (CategoryController) ──────────────────────────┤
│  - Thin handler, delegates to service                     │
│  - Input validation via Form Requests                     │
│  - Response transformation via Resources                  │
│                                                            │
├─ Service (CategoryService) ────────────────────────────────┤
│  - Business logic: create, update, delete, reorder, move  │
│  - Validation: circular references, slug uniqueness       │
│  - Transactions & event dispatch                          │
│                                                            │
├─ Repository (CategoryRepository) ─────────────────────────┤
│  - Database queries: getTree, getChildren, getAncestors   │
│  - Reordering logic & sibling recalculation               │
│  - Eager loading & N+1 prevention                         │
│                                                            │
├─ Model (Category) ────────────────────────────────────────┤
│  - Eloquent relationships: parent, children               │
│  - Scopes: active, roots, forTree                         │
│  - Casts: parent_id→int, is_active→bool, etc.             │
│                                                            │
└─ Database (categories table) ────────────────────────────┘
   - Self-referential parent_id FK
   - Soft-delete via deleted_at
   - Optimistic lock via version field
   - Bilingual: name_ar, name_en
   - Immutable: slug (unique)
```

### Request/Response Flow

```
1. Client sends: POST /api/v1/categories { name_ar, name_en, parent_id, ... }
   ↓
2. Middleware validates Authorization header (Bearer token)
   ↓
3. Middleware checks RBAC (Admin role required)
   ↓
4. Controller receives request, instantiates FormRequest (StoreCategoryRequest)
   ↓
5. FormRequest validates input (min/max length, parent_id exists, no cycles, ...)
   ↓
6. Controller calls Service::create($validated_data)
   ↓
7. Service performs business logic:
   - Check circular reference
   - Generate slug (auto-generated from name_en)
   - Set sort_order (max + 1)
   - Dispatch CategoryCreated event
   ↓
8. Service calls Repository::create($processed_data)
   ↓
9. Repository performs DB insert (or ORM save)
   ↓
10. Controller transforms model via CategoryResource
   ↓
11. Response: 201 { success: true, data: { id, name_ar, name_en, slug, ... }, error: null }
```

---

## Wave 1: Foundation (Backend Infrastructure)

### 1.1 Database Migration

**File**: `backend/database/migrations/2026_04_15_000000_create_categories_table.php`

**Changes**:

- Create categories table with all fields (parent_id, name_ar, name_en, slug, icon, sort_order, is_active, version, timestamps, soft delete)
- Add self-referential foreign key on parent_id
- Create composite index (parent_id, sort_order, is_active)
- Create indexes on: deleted_at, is_active, slug (unique)

**Constraints**:

- parent_id: nullable, references categories(id), ON DELETE SET NULL
- slug: unique across all records (including soft-deleted!)
- charset: utf8mb4, collate: utf8mb4_unicode_ci

**Lock Risk**: LOW (table creation)

---

### 1.2 Eloquent Model

**File**: `backend/app/Models/Category.php`

**Contents**:

- Use SoftDeletes trait
- Define relationships: parent (BelongsTo), children (HasMany)
- Scopes: active(), roots(), leaves(), ordered(), forTree()
- Methods: getAncestors(), getDescendants(), isAncestorOf(), isDescendantOf()
- Casts: parent_id→int, is_active→bool, version→int
- Fillable: parent_id, name_ar, name_en, slug, icon, sort_order, is_active, version

---

### 1.3 Repository Layer

**File**: `backend/app/Repositories/CategoryRepository.php`

**Methods**:

```php
public function getTree(bool $includeDeleted = false, bool $activeOnly = true): Collection
public function getChildren(int $parentId, bool $activeOnly = true): Collection
public function getAncestors(int $categoryId): Collection
public function getDescendants(int $categoryId): Collection
public function reorder(int $categoryId, int $newSortOrder): Category
public function move(int $categoryId, int $newParentId): Category
public function findById(int $id): ?Category
public function create(array $data): Category
public function update(int $id, array $data): Category
public function delete(int $id): bool
```

**Key Implementation Details**:

- getTree() uses recursive WITH clause or CTE (MySQL 8.0+)
- Eager loading with `with('children')` to prevent N+1
- All queries filter `whereNull('deleted_at')` by default
- withTrashed() scope for admin-only recovery

---

### 1.4 Service Layer

**File**: `backend/app/Services/CategoryService.php`

**Methods**:

```php
public function create(array $data): Category
public function update(int $id, array $data): Category
public function delete(int $id): bool
public function restore(int $id): bool
public function reorder(int $id, int $newSortOrder, int $version): Category
public function move(int $id, int $newParentId, int $version): Category
```

**Business Logic**:

- Slug generation: `Str::slug($data['name_en'])` with collision detection
- Circular reference check: Before accepting parent_id, verify not creating cycle
- Optimistic locking: Check version match before update
- Sibling recalculation: After reorder, adjust sort_order of affected siblings
- Transaction wrapping: All mutations wrapped in DB::transaction()
- Event dispatch: CategoryCreated, CategoryUpdated, CategoryDeleted events

---

### 1.5 Form Request Validation

**File**: `backend/app/Http/Requests/StoreCategoryRequest.php`

```php
public function rules(): array {
    return [
        'name_ar' => 'required|string|min:2|max:100',
        'name_en' => 'required|string|min:2|max:100',
        'parent_id' => 'nullable|integer|exists:categories,id,deleted_at,NULL',
        'icon' => 'nullable|string|max:50',
        'sort_order' => 'nullable|integer|min:0',
        'is_active' => 'nullable|boolean',
    ];
}

public function authorize(): bool {
    return auth()->user()?->isAdmin() ?? false;
}

public function messages(): array {
    return [
        'name_ar.required' => __('validation.required', ['attribute' => __('fields.category_name_ar')]),
        'name_en.required' => __('validation.required', ['attribute' => __('fields.category_name_en')]),
        'parent_id.exists' => __('validation.exists', ['attribute' => __('fields.parent_category')]),
    ];
}
```

**File**: `backend/app/Http/Requests/UpdateCategoryRequest.php`

```php
public function rules(): array {
    $id = $this->route('id');
    return [
        'name_ar' => 'sometimes|string|min:2|max:100',
        'name_en' => 'sometimes|string|min:2|max:100',
        'parent_id' => 'nullable|integer|exists:categories,id,deleted_at,NULL|not_in:' . $id,
        'icon' => 'nullable|string|max:50',
        'is_active' => 'nullable|boolean',
        'version' => 'required|integer',
    ];
}

public function authorize(): bool {
    return auth()->user()?->isAdmin() ?? false;
}
```

---

### 1.6 API Resource

**File**: `backend/app/Http/Resources/CategoryResource.php`

```php
public function toArray(Request $request): array {
    return [
        'id' => $this->id,
        'parent_id' => $this->parent_id,
        'name_ar' => $this->name_ar,
        'name_en' => $this->name_en,
        'slug' => $this->slug,
        'icon' => $this->icon,
        'sort_order' => $this->sort_order,
        'is_active' => $this->is_active,
        'version' => $this->version,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        'deleted_at' => $this->deleted_at,
        'children' => CategoryResource::collection($this->children ?? collect()),
    ];
}
```

---

### 1.7 Controller

**File**: `backend/app/Http/Controllers/CategoryController.php`

```php
class CategoryController extends Controller {
    public function __construct(private readonly CategoryService $service) {}

    public function index(Request $request) {
        // GET /api/v1/categories
        $tree = $this->service->getTree(
            includeDeleted: $request->boolean('include_deleted', false),
            activeOnly: $request->boolean('active_only', true)
        );
        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($tree),
            'error' => null,
        ]);
    }

    public function show(int $id) {
        // GET /api/v1/categories/{id}
        $category = $this->service->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'error' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request) {
        // POST /api/v1/categories
        $category = $this->service->create($request->validated());
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'error' => null,
        ], 201);
    }

    public function update(int $id, UpdateCategoryRequest $request) {
        // PUT /api/v1/categories/{id}
        $category = $this->service->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'error' => null,
        ]);
    }

    public function destroy(int $id) {
        // DELETE /api/v1/categories/{id}
        $this->service->delete($id);
        return response()->json([
            'success' => true,
            'data' => null,
            'error' => null,
        ], 204);
    }

    public function reorder(int $id, Request $request) {
        // PUT /api/v1/categories/{id}/reorder
        $validated = $request->validate([
            'sort_order' => 'required|integer|min:0',
            'version' => 'required|integer',
        ]);
        $category = $this->service->reorder($id, $validated['sort_order'], $validated['version']);
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'error' => null,
        ]);
    }
}
```

---

### 1.8 Routes

**File**: `backend/routes/api/categories.php` (new file)

```php
Route::apiResource('categories', CategoryController::class)->middleware('auth:sanctum');
Route::put('categories/{id}/reorder', [CategoryController::class, 'reorder'])->middleware('auth:sanctum')->name('categories.reorder');
```

**File**: `backend/routes/api.php` (updated)

```php
Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        include 'api/categories.php';
        // Other API routes...
    });
});
```

---

### 1.9 Seeder

**File**: `backend/database/seeders/CategorySeeder.php`

- Creates 10+ default categories (Building Materials, Electrical, Plumbing, Finishing)
- Populates with Arabic & English names
- Nests 2-3 levels deep
- Idempotent (checks for duplicates before insert)

---

### 1.10 Database Indexes Verification

After migration, verify indexes created:

```sql
SHOW INDEX FROM categories;
```

Expected indexes:

- PRIMARY (id)
- FOREIGN (parent_id)
- COMPOSITE (parent_id, sort_order, is_active)
- deleted_at
- is_active
- slug (UNIQUE)

---

## Wave 2: Frontend Components

### 2.1 API Composable Integration

**Update**: `frontend/composables/useApi.ts`

- Add method: `getCategories()` → GET /api/v1/categories
- Add method: `getCategoryTree()` → GET /api/v1/categories with params
- Add method: `createCategory(data)` → POST /api/v1/categories
- Add method: `updateCategory(id, data)` → PUT /api/v1/categories/{id}
- Add method: `deleteCategory(id)` → DELETE /api/v1/categories/{id}
- Add method: `reorderCategory(id, sort_order, version)` → PUT /api/v1/categories/{id}/reorder

---

### 2.2 Pinia Store

**File**: `frontend/stores/category.ts`

```typescript
interface CategoryState {
  categories: Category[];
  loading: boolean;
  error: string | null;
  selectedId: number | null;
}

export const useCategoryStore = defineStore('category', () => {
  const state = reactive<CategoryState>({
    categories: [],
    loading: false,
    error: null,
    selectedId: null,
  });
  const api = useApi();

  const fetchTree = async () => {
    state.loading = true;
    try {
      const { data } = await api.getCategories();
      state.categories = data;
      state.error = null;
    } catch (err) {
      state.error = err.message;
    } finally {
      state.loading = false;
    }
  };

  const createCategory = async (payload) => {
    try {
      const { data } = await api.createCategory(payload);
      state.categories.push(data);
      return data;
    } catch (err) {
      state.error = err.message;
      throw err;
    }
  };

  const updateCategory = async (id, payload) => {
    /* ... */
  };
  const deleteCategory = async (id) => {
    /* ... */
  };
  const reorderCategory = async (id, sort_order, version) => {
    /* ... */
  };

  return { state, fetchTree, createCategory, updateCategory, deleteCategory, reorderCategory };
});
```

---

### 2.3 CategoryTreeComponent

**File**: `frontend/components/category/CategoryTree.vue`

Features:

- Recursive tree rendering with indentation
- Expand/collapse parents (via openNodes state)
- Drag-and-drop to reorder siblings (via @dnd-kit)
- Edit/delete context menu
- Icons displayed for each category
- Search/filter input (optional)
- RTL-aware layout

```vue
<script setup lang="ts">
  import { useCategoryStore } from '~/stores/category';
  const categoryStore = useCategoryStore();
  const openNodes = ref<Set<number>>(new Set());

  const toggleNode = (id: number) => {
    if (openNodes.value.has(id)) {
      openNodes.value.delete(id);
    } else {
      openNodes.value.add(id);
    }
  };
</script>

<template>
  <div class="category-tree">
    <CategoryTreeNode
      v-for="category in categoryStore.state.categories"
      :key="category.id"
      :category
      :open="openNodes.has(category.id)"
      @toggle="toggleNode"
      @edit="handleEdit"
      @delete="handleDelete"
      @reorder="handleReorder"
    />
  </div>
</template>
```

---

### 2.4 CategoryTreeNode (Recursive)

**File**: `frontend/components/category/CategoryTreeNode.vue`

```vue
<script setup lang="ts">
  const props = defineProps<{
    category: Category;
    open: boolean;
    depth?: number;
  }>();

  const emit = defineEmits<{
    toggle: [id: number];
    edit: [category: Category];
    delete: [id: number];
    reorder: [{ id: number; newSort: number }];
  }>();
</script>

<template>
  <div class="tree-node" :style="{ paddingInlineStart: `${(props.depth ?? 0) * 1.5}rem` }">
    <div class="node-header">
      <button v-if="category.children?.length" @click="emit('toggle', category.id)">
        {{ props.open ? '▼' : '▶' }}
      </button>
      <UIcon :name="`i-${category.icon || 'lucide-folder'}`" />
      <span class="node-name">{{ category[`name_${$i18n.locale}`] }}</span>
      <UDropdown
        :items="[
          [
            { label: 'Edit', click: () => emit('edit', category) },
            { label: 'Delete', click: () => emit('delete', category.id) },
          ],
        ]"
      >
        <UButton icon="i-lucide-more-horizontal" color="gray" variant="ghost" size="sm" />
      </UDropdown>
    </div>
    <div v-if="props.open && category.children?.length" class="node-children">
      <CategoryTreeNode
        v-for="child in category.children"
        :key="child.id"
        :category="child"
        :open="openNodes.has(child.id)"
        @toggle="emit('toggle', $event)"
        @edit="emit('edit', $event)"
        @delete="emit('delete', $event)"
        :depth="(props.depth ?? 0) + 1"
      />
    </div>
  </div>
</template>
```

---

### 2.5 CategoryFormModal

**File**: `frontend/components/category/CategoryFormModal.vue`

Features:

- Form inputs: name_ar, name_en, icon, parent_id (selector), is_active
- VeeValidate + Zod for validation
- Create/Edit mode switching
- RTL-aware form layout

```vue
<script setup lang="ts">
  import { useForm } from 'vee-validate';
  import { z } from 'zod';

  const categoryStore = useCategoryStore();

  const schema = z.object({
    name_ar: z.string().min(2).max(100),
    name_en: z.string().min(2).max(100),
    parent_id: z.number().nullable(),
    icon: z.string().max(50).optional(),
    is_active: z.boolean().default(true),
  });

  const { handleSubmit, values, errors } = useForm({
    validationSchema: schema,
    initialValues: { name_ar: '', name_en: '', parent_id: null, is_active: true },
  });

  const onSubmit = handleSubmit(async (data) => {
    try {
      if (props.mode === 'create') {
        await categoryStore.createCategory(data);
      } else {
        await categoryStore.updateCategory(props.categoryId, data);
      }
      emit('close');
    } catch (err) {
      // Handle error
    }
  });
</script>

<template>
  <UDialog>
    <form @submit.prevent="onSubmit">
      <UFormGroup label="Arabic Name" :error="errors.name_ar">
        <UInput v-model="values.name_ar" dir="rtl" />
      </UFormGroup>
      <UFormGroup label="English Name" :error="errors.name_en">
        <UInput v-model="values.name_en" dir="ltr" />
      </UFormGroup>
      <UFormGroup label="Parent Category">
        <CategorySelector v-model="values.parent_id" />
      </UFormGroup>
      <UFormGroup label="Icon" :error="errors.icon">
        <UInput v-model="values.icon" />
      </UFormGroup>
      <UFormGroup>
        <UCheckbox v-model="values.is_active" label="Active" />
      </UFormGroup>
      <div class="flex gap-2">
        <UButton type="submit" label="Save" />
        <UButton @click="emit('close')" color="gray" label="Cancel" />
      </div>
    </form>
  </UDialog>
</template>
```

---

### 2.6 CategorySelector Component

**File**: `frontend/components/category/CategorySelector.vue`

```vue
<script setup lang="ts">
  const modelValue = defineModel<number | null>();
  const search = ref('');

  const filteredCategories = computed(() => {
    if (!search.value) return categoryStore.state.categories;
    return categoryStore.state.categories.filter(
      (cat) =>
        cat.name_ar.includes(search.value) ||
        cat.name_en.toLowerCase().includes(search.value.toLowerCase())
    );
  });

  const flattenTree = (categories: Category[], level = 0) => {
    return categories.flatMap((cat) => [
      { ...cat, level },
      ...(cat.children ? flattenTree(cat.children, level + 1) : []),
    ]);
  };
</script>

<template>
  <div class="category-selector">
    <UInput v-model="search" placeholder="Search categories..." />
    <USelectMenu
      v-model="modelValue"
      :options="flattenTree(filteredCategories)"
      option-attribute="name_en"
    >
      <template #option="{ option }">
        <span :style="{ marginInlineStart: `${option.level * 1.5}rem` }">{{ option.name_en }}</span>
      </template>
    </USelectMenu>
  </div>
</template>
```

---

### 2.7 CategoryBreadcrumb Component

**File**: `frontend/components/category/CategoryBreadcrumb.vue`

```vue
<script setup lang="ts">
  const props = defineProps<{ categoryId: number }>();

  const ancestors = ref<Category[]>([]);
  const loading = ref(true);

  const fetchAncestors = async () => {
    try {
      const { data } = await useApi().getCategory(props.categoryId);
      ancestors.value = [data, ...data.ancestors]; // Assumes API returns ancestors
    } finally {
      loading.value = false;
    }
  };

  onMounted(fetchAncestors);
  watchEffect(() => props.categoryId, fetchAncestors);
</script>

<template>
  <nav v-if="!loading" class="category-breadcrumb flex items-center gap-1">
    <NuxtLink to="/products" class="hover:underline">{{ $t('products') }}</NuxtLink>
    <span class="text-gray-400">/</span>
    <template v-for="(ancestor, idx) in ancestors" :key="ancestor.id">
      <NuxtLink :to="`/products?category=${ancestor.slug}`" class="hover:underline">
        {{ ancestor[`name_${$i18n.locale}`] }}
      </NuxtLink>
      <span v-if="idx < ancestors.length - 1" class="text-gray-400">/</span>
    </template>
  </nav>
</template>
```

---

### 2.8 Admin Category Management Page

**File**: `frontend/pages/admin/categories.vue`

```vue
<script setup lang="ts">
  definePageMeta({ middleware: 'role', roles: ['admin'] });

  const categoryStore = useCategoryStore();
  const showCreateModal = ref(false);
  const editingCategory = ref<Category | null>(null);

  onMounted(() => categoryStore.fetchTree());

  const handleCreate = () => {
    editingCategory.value = null;
    showCreateModal.value = true;
  };

  const handleEdit = (category: Category) => {
    editingCategory.value = category;
    showCreateModal.value = true;
  };

  const handleDelete = async (id: number) => {
    if (confirm('Delete category?')) {
      await categoryStore.deleteCategory(id);
    }
  };
</script>

<template>
  <div class="admin-categories">
    <section class="page-header">
      <h1>{{ $t('categories.management') }}</h1>
      <UButton @click="handleCreate" label="Create Category" icon="i-lucide-plus" />
    </section>

    <CategoryTree @edit="handleEdit" @delete="handleDelete" />

    <CategoryFormModal
      v-if="showCreateModal"
      :category="editingCategory"
      mode="create"
      @close="showCreateModal = false"
    />
  </div>
</template>
```

---

## Wave 3: Testing & Quality Assurance

### 3.1 Unit Tests (Backend)

**File**: `backend/tests/Unit/Services/CategoryServiceTest.php`

Test cases:

```php
test('creates category with auto-generated slug')
test('prevents circular parent references')
test('reorder updates sort_order correctly')
test('soft-delete sets deleted_at timestamp')
test('getAncestors returns correct path')
test('getDescendants returns all nested children')
```

---

### 3.2 Feature Tests (Backend)

**File**: `backend/tests/Feature/Http/CategoryControllerTest.php`

Test cases:

```php
test('GET /api/v1/categories returns tree structure')
test('POST /api/v1/categories requires admin role')
test('POST creates category with valid data')
test('POST rejects invalid parent_id (non-existent)')
test('PUT /api/v1/categories/{id} requires version field for optimistic lock')
test('PUT rejects with 409 on version mismatch')
test('DELETE soft-deletes category')
test('deleted categories hidden from list endpoint')
test('PUT /api/v1/categories/{id}/reorder recalculates siblings')
```

---

### 3.3 E2E Tests (Frontend)

**File**: `frontend/tests/e2e/category-management.spec.ts`

Test scenarios:

```typescript
test('admin can create top-level category', async ({ page }) => {
  // Navigate to /admin/categories
  // Click "Create Category"
  // Fill form (name_ar, name_en)
  // Submit
  // Verify category appears in tree
});

test('admin can create nested sub-category', async ({ page }) => {
  // Create parent
  // Create child with parent_id = parent
  // Verify child indented under parent
});

test('admin can reorder categories via drag-drop', async ({ page }) => {
  // Create 3 categories
  // Drag category 3 to position 1
  // Verify sort_order updated
});

test('arabic names display without truncation in long lists', async ({ page }) => {
  // Create category with 100-char Arabic name
  // Verify no layout breakage
});
```

---

### 3.4 Performance Tests

**Scenarios**:

- Load tree with 1000 categories: must be <500ms
- CategorySelector dropdown with 500 categories: must be <1s
- Concurrent reorder requests: verify optimistic lock prevents conflicts

---

## Risk Mitigations

### 1. Circular Reference Risk

**Mitigation**: Validation in StoreCategoryRequest + Service::checkCircularReference()
**Test**: Feature test with cycle attempt

### 2. N+1 Query Risk

**Mitigation**: Eager loading with `with('children')`, recursive CTE query
**Test**: Query count assertion in feature tests

### 3. Concurrent Reorder Loss

**Mitigation**: Optimistic locking via version field
**Test**: Concurrent request simulation with version mismatch

### 4. Orphaned Records After Delete

**Mitigation**: By design (children remain with parent_id); documented behavior
**Test**: Verify deleted parent's children are hidden but recoverable

### 5. Slug Collision

**Mitigation**: UNIQUE constraint + counter-based fallback
**Test**: Try creating two categories with same name_en

### 6. RTL Text Overflow

**Mitigation**: CSS `truncate` / `line-clamp-2` + wrap testing
**Test**: E2E test with 100-char Arabic names

---

## Dependency Graph

### Upstream (MUST complete first)

✅ STAGE_06_API_FOUNDATION — Provides error contract, middleware, resource pattern

### Parallel (Can work simultaneously)

- Backend: database → models → repository → service → controller → routes
- Frontend: store → composables → components → pages

### Downstream (Blocks)

🚫 STAGE_08_PRODUCTS — Uses Category as foreign key; ProductForm uses CategorySelector

---

## Deployment Checklist

- [ ] Database migration applied (`php artisan migrate`)
- [ ] Seeder executed (`php artisan db:seed --class=CategorySeeder`)
- [ ] Routes registered and tested via postman/curl
- [ ] RBAC middleware enforcing admin-only POST/PUT/DELETE
- [ ] Frontend bundle builds without errors
- [ ] E2E tests pass in staging/QA environment
- [ ] API response times <500ms verified (profiling)
- [ ] Soft-delete scopes hiding deleted categories by default
- [ ] Version field optimistic locking tested with concurrent requests
- [ ] RTL/Arabic rendering verified on browsers (Chrome, Safari, Firefox)
- [ ] Cache invalidation configured (flush on mutations)
- [ ] Production deployment: blue-green or rolling deploy

---

## Success Criteria Verification

| Criteria                                           | Verification Method               |
| -------------------------------------------------- | --------------------------------- |
| Admin creates 10+ hierarchical categories in <10s  | E2E test measure time             |
| Tree API returns <500ms for 1000 categories        | Load test + query profiling       |
| RBAC enforced (non-admin gets 403)                 | Feature test with non-admin token |
| Reorder updates sort_order correctly               | Feature test assertion            |
| Breadcrumb renders without N+1 queries             | Query count assertion             |
| CategorySelector loads 500 categories in <1s       | Load test                         |
| Seeder populates 10+ defaults, no duplicates       | Run seeder twice, verify count    |
| Validation errors return 422 + field details       | Feature test                      |
| Soft-deleted categories excluded from queries      | Feature test                      |
| Concurrent reorder returns 409 on version mismatch | Concurrent feature test           |

---

## Timeline & Estimates

| Phase      | Task                  | Estimate     | Dependencies       |
| ---------- | --------------------- | ------------ | ------------------ |
| **Wave 1** | Migration             | 0.5d         | None               |
|            | Model & Repository    | 1d           | Migration          |
|            | Service & Validation  | 1d           | Model & Repository |
|            | Controller & Routes   | 0.5d         | Service            |
|            | Seeder                | 0.5d         | Migration          |
|            | **Wave 1 Total**      | **3.5 days** |                    |
| **Wave 2** | Composable & Store    | 0.5d         | Wave 1 API tested  |
|            | Tree Component        | 1d           | Store              |
|            | Form Modal & Selector | 1d           | Store              |
|            | Breadcrumb & Page     | 0.5d         | Components         |
|            | **Wave 2 Total**      | **3 days**   |                    |
| **Wave 3** | Unit Tests            | 1d           | Wave 1             |
|            | Feature Tests         | 1.5d         | Wave 1 & Wave 2    |
|            | E2E Tests             | 1d           | Wave 2             |
|            | Performance Testing   | 0.5d         | All waves          |
|            | **Wave 3 Total**      | **4 days**   |                    |
| **TOTAL**  |                       | **~10 days** |                    |

_Note: 10 days is full development. With parallel work (backend + frontend), can compress to 5-7 days._

---

## Post-Implementation: Future Enhancements

- Drag-to-move (change parent via drag-drop)
- Category image/cover upload (currently icon only)
- Bulk import via CSV
- Category analytics (product count per category)
- Category-level permissions (restrict by role)
- Aliases/synonyms for better search

---

## Rollback Plan

If deployment issues occur:

1. **Database**: Rollback migration: `php artisan migrate:rollback` (recreates migration with down())
2. **API**: Revert route registration (remove routes/api/categories.php include)
3. **Frontend**: Deploy previous version without category components
4. **Data**: No data loss; migration is reversible

---

**Status**: Ready for implementation
**Next Step**: Begin Wave 1 (Database & Models)
**Review**: Architecture Guardian + Code Reviewer + Database Engineer
