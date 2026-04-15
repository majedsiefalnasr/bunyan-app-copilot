# Security Checklist — STAGE_07_CATEGORIES

**Purpose**: Unit test for requirement quality and completeness of security controls.  
**Generated**: 2026-04-15  
**Spec Reference**: specs/runtime/007-categories/spec.md

---

## RBAC Enforcement (FR-008)

- [ ] **CHK-SEC-001**: Admin middleware applies to ALL write endpoints (POST, PUT, DELETE)
  - **Requirement**: FR-008, AGENTS.md RBAC Hard Rule
  - **Validation**: Test non-admin user receives `403 RBAC_ROLE_DENIED` for POST /api/v1/categories
  - **Example**:
    ```php
    Route::middleware(['auth:sanctum', 'verified', 'role:admin'])
        ->post('/categories', [CategoryController::class, 'store']);
    ```

- [ ] **CHK-SEC-002**: GET endpoints allow any authenticated user (or public read)
  - **Requirement**: FR-008 states "Any authenticated user can GET categories"
  - **Validation**: Test authenticated user can call GET /api/v1/categories; test unauthenticated user behavior (decide: allow or require auth)
  - **Example**: No role: middleware on GET, or public endpoint with optional auth

- [ ] **CHK-SEC-003**: RBAC check in Policy class before any business logic
  - **Requirement**: security-hardening skill, Laravel Policy pattern
  - **Validation**: CategoryPolicy::create() returns false for non-admin; returned via authorize() in controller
  - **Example**:
    ```php
    if ($this->authorize('create', Category::class)) {
        return $this->service->create($validated);
    }
    ```

- [ ] **CHK-SEC-004**: Authorization failures log and return generic error (no role info leakage)
  - **Requirement**: security-hardening skill, error contract AGENTS.md
  - **Validation**: Non-admin user receives `{ "success": false, "error": { "code": "AUTH_UNAUTHORIZED", "message": "You are not authorized to perform this action" } }` without revealing admin role
  - **Example**: No log message like "User is not admin role"

---

## Input Validation (FR-005, FR-006)

- [ ] **CHK-SEC-005**: Form Request class created with all validations for create/update
  - **Requirement**: FR-006: "System MUST create a Form Request for category creation/update"
  - **Validation**: StoreCategoryRequest and UpdateCategoryRequest exist; validations are server-side only
  - **Example**:
    ```php
    class StoreCategoryRequest extends FormRequest {
        public function rules(): array {
            return [
                'name_ar' => 'required|string|min:2|max:100',
                'name_en' => 'required|string|min:2|max:100',
                'icon' => 'nullable|string|max:50',
                'parent_id' => ['nullable', 'exists:categories,id', 'different:id'],
                'sort_order' => 'nullable|integer|min:0',
            ];
        }
        public function messages(): array {
            return [
                'name_ar.required' => 'اسم الفئة بالعربية مطلوب',
                'name_en.required' => 'Category name in English is required',
            ];
        }
    }
    ```

- [ ] **CHK-SEC-006**: Validation rules enforce min/max lengths and data types
  - **Requirement**: FR-005 specifies min:2, max:100 for names
  - **Validation**: Test POST with name_ar="a" returns 422 VALIDATION_ERROR; test name_ar="" returns 422
  - **Example**: Message: "The name_ar field must be at least 2 characters."

- [ ] **CHK-SEC-007**: parent_id validates against existing category (prevent orphaning)
  - **Requirement**: FR-005 "parent_id must reference existing category if provided"
  - **Validation**: Test POST with invalid parent_id returns 422 with field error
  - **Example**: `'parent_id' => 'nullable|exists:categories,id'`

- [ ] **CHK-SEC-008**: parent_id cannot equal category's own id (no self-parenting)
  - **Requirement**: FR-005 "parent_id cannot be the category's own id"
  - **Validation**: Test PUT /api/v1/categories/1 with parent_id=1 returns 422
  - **Example**: In Form Request: `'parent_id' => ['nullable', 'different:id', 'exists:categories,id']` (update request has id in context)

- [ ] **CHK-SEC-009**: Circular parent references prevented via validation logic
  - **Requirement**: Edge Cases section, FR-013 mentions WORKFLOW_INVALID_TRANSITION for cycles
  - **Validation**: Test move category to grandchild: PUT /api/v1/categories/1 with parent_id=[grandchild_id] returns 422 or 409
  - **Example**:
    ```php
    // In Service or Repository
    if ($this->hasAncestor($categoryId, $newParentId)) {
        throw ValidationException::withMessages([
            'parent_id' => 'Cannot set a descendant as parent (circular reference)'
        ]);
    }
    ```

- [ ] **CHK-SEC-010**: Icon value sanitized (no script injection via icon class name)
  - **Requirement**: FR-001 "icon (nullable, max 50 chars)"
  - **Validation**: Test icon="<script>" returns 422 or sanitized; test icon="lucide-box" succeeds
  - **Example**: `'icon' => 'nullable|string|max:50|regex:/^[a-z0-9\-_]+$/'` (whitelist alphanumeric + hyphens/underscores)

---

## SQL Injection Prevention

- [ ] **CHK-SEC-011**: All category queries use Eloquent ORM, never raw SQL with user input
  - **Requirement**: security-hardening skill "always use Eloquent/Query Builder"
  - **Validation**: Grep backend/app for raw DB::raw() — should be zero occurrences in Category code
  - **Example**: ✓ `Category::where('name_ar', $name)->first()` vs ✗ `DB::select("SELECT * FROM categories WHERE name_ar = '$name'")`

- [ ] **CHK-SEC-012**: Slug generation uses Laravel Str::slug() (never user-supplied)
  - **Requirement**: FR-001 "slug (unique, auto-generated from name_en, immutable)"
  - **Validation**: Test POST /api/v1/categories does not accept slug in request body; slug is auto-generated server-side
  - **Example**:
    ```php
    $category->slug = Str::slug($request->name_en);
    // Never: $category->slug = $request->slug;
    ```

---

## Soft Delete Data Integrity (FR-002, Clarification 4)

- [ ] **CHK-SEC-013**: Soft-deleted categories excluded from all default queries
  - **Requirement**: Clarification 4 "Soft-deleted categories globally invisible"
  - **Validation**: DELETE /api/v1/categories/1 succeeds; GET /api/v1/categories no longer includes deleted category; GET /api/v1/categories/1 returns 404
  - **Example**: Global scope in Category model: `protected static function booted() { static::addGlobalScope(new SoftDeletingScope()); }`

- [ ] **CHK-SEC-014**: Soft-deleted category with deleted_at timestamp verified in database
  - **Requirement**: FR-002 "soft-delete handling MUST NOT cascade"
  - **Validation**: SELECT \* FROM categories WHERE id=1 shows deleted_at is NOT NULL
  - **Example**: `deleted_at` = '2026-04-15 10:30:00'

- [ ] **CHK-SEC-015**: Children of soft-deleted parent remain in database with parent_id intact
  - **Requirement**: Clarification 2 "Children remain orphaned (parent_id stays intact)"
  - **Validation**: DELETE parent; fetch with `withTrashed()` to verify child.parent_id = parent_id (not nullified)
  - **Example**:
    ```php
    Category::withTrashed()->where('id', $childId)->first()->parent_id; // Still points to deleted parent
    ```

- [ ] **CHK-SEC-016**: Admin audit queries can retrieve soft-deleted categories via withTrashed()
  - **Requirement**: Clarification 4 "Admin audit queries see soft-deleted categoria"
  - **Validation**: Only admin can access /api/v1/admin/categories?include=trashed endpoint; returns deleted categories
  - **Example**: `Category::withTrashed()->get()`

---

## Conflict & Uniqueness Handling (FR-013)

- [ ] **CHK-SEC-017**: Duplicate slug detection returns 409 CONFLICT_ERROR
  - **Requirement**: FR-011 "Error codes: CONFLICT_ERROR (409 for slug)"
  - **Validation**: Create category with slug "building-materials"; attempt create another with name_en="Building Materials" → receive 409 CONFLICT_ERROR
  - **Example**:
    ```php
    catch (QueryException $e) {
        if (str_contains($e->getMessage(), 'unique constraint')) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONFLICT_ERROR',
                    'message' => 'Category slug already exists'
                ]
            ], 409);
        }
    }
    ```

- [ ] **CHK-SEC-018**: Optimistic locking prevents concurrent update conflicts (version mismatch → 409)
  - **Requirement**: Clarification 5 "Optimistic locking with version field"
  - **Validation**: Two admins call PUT /api/v1/categories/1 with different versions simultaneously; second request receives 409 CONFLICT_ERROR
  - **Example**:
    ```php
    $category = Category::findOrFail($id);
    if ($category->version !== $request->version) {
        throw new ConflictException('Category was modified by another user');
    }
    $category->update($validated);
    $category->increment('version');
    ```

---

## Error Response Compliance (NFR-001)

- [ ] **CHK-SEC-019**: All error responses follow error contract with code, message, details
  - **Requirement**: NFR-001 "API responses must comply with standardized error contract"
  - **Validation**: Test any error endpoint returns `{ "success": false, "data": null, "error": { "code": "...", "message": "...", "details": {...} } }`
  - **Example**: POST invalid data returns:
    ```json
    {
      "success": false,
      "data": null,
      "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
          "name_ar": ["The name_ar field must be at least 2 characters."],
          "parent_id": ["The selected parent_id is invalid."]
        }
      }
    }
    ```

- [ ] **CHK-SEC-020**: RBAC_ROLE_DENIED error returned instead of generic AUTH_UNAUTHORIZED
  - **Requirement**: AGENTS.md error codes, security-hardening pattern
  - **Validation**: Non-admin receives `{ "error": { "code": "RBAC_ROLE_DENIED", "message": "Only administrators can manage categories" } }` with 403
  - **Example**: Middleware or Policy returns 403 with specific code

- [ ] **CHK-SEC-021**: 404 RESOURCE_NOT_FOUND returned for non-existent or deleted categories
  - **Requirement**: FR-012 "Error: RESOURCE_NOT_FOUND (404) if deleted"
  - **Validation**: GET /api/v1/categories/999 or GET deleted category returns 404 with code RESOURCE_NOT_FOUND
  - **Example**: Controller: `return response()->json(['success' => false, 'error' => ['code' => 'RESOURCE_NOT_FOUND']], 404)`

---

## Mass Assignment Protection (security-hardening skill)

- [ ] **CHK-SEC-022**: Category model defines $fillable (prevent mass assignment vulnerabilities)
  - **Requirement**: security-hardening "always define $fillable on models"
  - **Validation**: Category::all()->first()->$guarded is empty; $fillable includes only intended fields
  - **Example**:
    ```php
    protected $fillable = ['name_ar', 'name_en', 'icon', 'parent_id', 'sort_order', 'is_active', 'version'];
    // Never: id, slug, created_at, updated_at, deleted_at should NOT be fillable
    ```

- [ ] **CHK-SEC-023**: Immutable fields (id, slug, timestamps) not in $fillable or guarded via custom logic
  - **Requirement**: FR-001 "slug immutable after creation", FR-013 "Slug is immutable"
  - **Validation**: Attempt PUT /api/v1/categories/1 with {slug: "new-slug"} — slug remains unchanged
  - **Example**: Model override:
    ```php
    protected function setSlugAttribute($value) {
        // Prevent slug updates
        if ($this->exists) {
            return;
        }
        $this->attributes['slug'] = $value;
    }
    ```

---

## Slug Immutability Validation (Clarification 1)

- [ ] **CHK-SEC-024**: Slug is not regenerated when name_en updates
  - **Requirement**: Clarification 1 "Slug is read-only after creation"
  - **Validation**: Create category with name_en="Building Materials" → slug="building-materials"; PUT update name_en="Construction Materials" → slug remains "building-materials"
  - **Example**: Service never calls Str::slug() in update method

- [ ] **CHK-SEC-025**: Slug uniqueness constraint at database level
  - **Requirement**: FR-001 "slug (unique, auto-generated)"
  - **Validation**: Migration includes `$table->string('slug')->unique()`; duplicate slug creation fails at database level with integrity error
  - **Example**: Migration:
    ```php
    $table->string('slug')->unique();
    ```

---

## CSRF Protection (security-hardening skill)

- [ ] **CHK-SEC-026**: CSRF middleware applies to web routes (if any Category endpoints are web-based)
  - **Requirement**: security-hardening "CSRF enabled by default in Laravel for web routes"
  - **Validation**: POST from web form includes X-CSRF-TOKEN header; missing/invalid token returns 419
  - **Note**: API routes use Sanctum tokens (exempt from CSRF), so this applies only if web routes exist

---

## Audit Logging (observability best practice)

- [ ] **CHK-SEC-027**: All category mutations (create, update, delete) logged with user context
  - **Requirement**: Security best practice, audit trail
  - **Validation**: Check app/Logging directory for category-specific logging; verify logs include user_id, action (created/updated/deleted), timestamp
  - **Example**:
    ```php
    Log::info('Category created', [
        'category_id' => $category->id,
        'user_id' => auth()->id(),
        'name_ar' => $category->name_ar,
        'action' => 'created',
    ]);
    ```

---

## API Resource Security (FR-007)

- [ ] **CHK-SEC-028**: CategoryResource excludes sensitive fields (if any)
  - **Requirement**: FR-007 "API Resource serializing all fields"
  - **Validation**: Check app/Http/Resources/CategoryResource.php; no private administrator data exposed
  - **Example**: public toArray includes: id, parent_id, name_ar, name_en, slug, icon, sort_order, is_active, created_at, updated_at (not deleted_at unless admin context)

- [ ] **CHK-SEC-029**: Nested children in CategoryResource loaded via recursive transformation
  - **Requirement**: Clarification 3 "Nested tree structure", FR-007
  - **Validation**: GET /api/v1/categories returns nested children arrays; verify no missing children
  - **Example**:
    ```php
    public function toArray($request) {
        return [
            'id' => $this->id,
            'children' => CategoryResource::collection($this->children),
        ];
    }
    ```
