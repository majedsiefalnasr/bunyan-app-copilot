# Research — 009-suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Stage:** STAGE_09
> **Generated:** 2026-04-15

---

## 1. Authentication & RBAC Patterns

### Role System

- `User.role` is a first-class column (not only a pivot) cast to `App\Enums\UserRole` backed string enum.
- Available cases: `CUSTOMER`, `CONTRACTOR`, `SUPERVISING_ARCHITECT`, `FIELD_ENGINEER`, `ADMIN`.
- Role helpers on User model:
  - `hasEnumRole(UserRole $role): bool` — checks the `role` column
  - `hasAnyRole(UserRole ...$roles): bool` — checks the `role` column against multiple values
  - `hasRole(string $slug): bool` — checks via `roles` pivot relationship
- `UserFactory` already has states: `admin()`, `customer()`, **`contractor()`**, `supervisingArchitect()`, `fieldEngineer()`. No new factory states needed.

### Policy & Gate Integration

- Laravel Gate with `appServiceProvider::boot()` registers an **admin superuser bypass**:
  ```php
  Gate::before(function (User $user, string $ability) {
      if ($user->hasEnumRole(UserRole::ADMIN)) { return true; }
  });
  ```
  **Implication:** `SupplierPolicy` methods do NOT handle the admin case — the Gate short-circuits before reaching the policy for admin users.
- No existing Policies directory (`app/Policies/` does not exist yet). It must be created.
- Policy auto-discovery: Laravel discovers policies in `app/Policies/` and maps them by model name.
- Guest-compatible policy methods (`viewAny`, `view`) must declare `?User $user` (nullable type).
- Controller usage: `$this->authorize('action', $supplierOrClass)` — throws `AuthorizationException` which maps to 403.

### AppServiceProvider Boot Registration

Current `boot()` already registers:

- Rate limiters (`api`, `auth-login`, `auth-register`, etc.)
- CORS wildcard guard
- `Gate::before` admin bypass
- **Route::model binding is NOT yet used** — must be added in `boot()` using `use Illuminate\Support\Facades\Route;`

---

## 2. Pagination Pattern

### `BaseApiController::paginated()` Response Shape

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8
  },
  "error": null
}
```

- Method signature: `protected function paginated(mixed $collection, LengthAwarePaginator $paginator, int $statusCode = 200): JsonResponse`
- `$collection` should be `SupplierResource::collection($paginator)` (resolved by the resource class)
- Attaches `X-Correlation-ID` header when available

### `BaseApiResource::paginatedCollection()` Helper

- Returns `['data' => [...], 'meta' => [...]]` array for manual response building.
- **DECISION**: Use `$this->paginated(SupplierResource::collection($paginator), $paginator)` in controller for consistency with the established V1 pattern.

---

## 3. Service & Repository Patterns

### BaseRepository

| Method     | Signature                                                                    |
| ---------- | ---------------------------------------------------------------------------- |
| `find`     | `find(int $id): ?Model`                                                      |
| `findAll`  | `findAll(): Collection`                                                      |
| `findBy`   | `findBy(array $criteria): Collection`                                        |
| `create`   | `create(array $data): Model`                                                 |
| `update`   | `update(int $id, array $data): Model` — takes **int id**, not model instance |
| `delete`   | `delete(int $id): bool`                                                      |
| `paginate` | `paginate(int $perPage = 15): LengthAwarePaginator`                          |

- `BaseRepository::update()` throws `ModelNotFoundException` if record not found.
- `SupplierRepository` must **override** `update()` to accept a `SupplierProfile` instance (avoids double-fetch, matches service contract). The interface defines the override.

### Repository Binding Pattern (AppServiceProvider::register)

```php
$this->app->bind(SupplierRepositoryInterface::class,
    fn () => new SupplierRepository(new SupplierProfile)
);
```

### Service Pattern

- Constructor receives injected interface(s): `private readonly SupplierRepositoryInterface $supplierRepository`
- Business exceptions: `ApiException::make(ApiErrorCode::X, trans('suppliers.message_key'))`
- Returns typed domain objects — never `JsonResponse`
- Validates business rules before delegating to repository

### ApiException Usage

```php
// 409 conflict:
throw ApiException::make(ApiErrorCode::CONFLICT_ERROR, trans('suppliers.already_exists'));

// 403 role denied:
throw ApiException::make(ApiErrorCode::RBAC_ROLE_DENIED, trans('suppliers.role_required'));

// 404:
throw ApiException::make(ApiErrorCode::RESOURCE_NOT_FOUND, trans('suppliers.not_found'));

// 422 workflow:
throw ApiException::make(ApiErrorCode::WORKFLOW_INVALID_TRANSITION, trans('suppliers.cannot_verify_suspended'));
```

---

## 4. Migration Conventions

- **Pattern**: `return new class extends Migration { public function up(): void {...} public function down(): void {...} };`
- **Naming**: `YYYY_MM_DD_HHMMSS_create_{table}_table.php`
- **FK columns**: `$table->foreignId('user_id')->constrained()->cascadeOnDelete()` or `$table->unsignedBigInteger('verified_by')->nullable()` with manual `foreign()` for nullable FKs
- **Enum columns**: `$table->enum('verification_status', ['pending','verified','suspended'])->default('pending')`
- **Soft deletes**: `$table->softDeletes()` — adds `deleted_at`
- **Timestamps**: `$table->timestamps()` — adds `created_at`, `updated_at`
- **Decimal**: `$table->decimal('rating_avg', 8, 2)->default(0.00)`
- **Unsigned int**: `$table->unsignedInteger('total_ratings')->default(0)`
- **Rollback**: `down()` method calls `Schema::dropIfExists('supplier_profiles')`

---

## 5. Model Conventions

### BaseModel vs User

- Domain models extend `App\Models\BaseModel` (not `Authenticatable`).
- `BaseModel` includes: `HasFactory`, `SoftDeletes`, `$guarded = []`, `$dateFormat = 'Y-m-d H:i:s'`.
- Setting `$fillable` on child class overrides the `$guarded = []` (fillable takes precedence).

### Casts (PHP 8.3+ method-based casts)

```php
protected function casts(): array
{
    return [
        'verification_status' => SupplierVerificationStatus::class,
        'verified_at'         => 'datetime',
        'rating_avg'          => 'decimal:2',
    ];
}
```

### Scopes

```php
public function scopeVerified(Builder $query): Builder
public function scopeByCity(Builder $query, string $city): Builder
public function scopeSearch(Builder $query, string $term): Builder
```

### Security Decision — `$fillable` Exclusions

Following `SEC-FINDING-A` pattern (same as `User.role`):

- `verification_status` is **NOT** in `$fillable` — must be set explicitly (`$model->verification_status = $value; $model->save()`) to prevent privilege escalation via forged mass assignment.
- Also excluded per spec: `id`, `user_id`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings`.

---

## 6. HTTP Layer Conventions

### Form Requests

```php
final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Auth handled by middleware + Policy
    public function rules(): array { return [...]; }
}
```

### SupplierResource extends BaseApiResource

- Must call `parent::toArray($request)` pattern (or override fully).
- `BaseApiResource::$wrap = 'data'` — resource responses auto-wrapped.
- For list response via controller `paginated()`, collection is extracted without wrapper.

### Controller Structure

- Extends `BaseApiController` (V1 base — has `paginated()` method)
- Constructor injects service: `private readonly SupplierService $supplierService`
- Thin methods: validate → authorize → delegate to service → return resource

---

## 7. Test Organization

### Feature Tests

- Location: `tests/Feature/Api/V1/` — new sub-directory for supplier tests
- Pattern: `use RefreshDatabase; extends Tests\TestCase`
- Factory patterns:
  - `User::factory()->contractor()->create()` ✓ (contractor state confirmed in UserFactory)
  - `User::factory()->admin()->create()` ✓ (admin state confirmed)
  - `User::factory()->customer()->create()` ✓ (customer state confirmed)
  - `SupplierProfile::factory()->create(['user_id' => $user->id])` — new factory needed

### Unit Tests

- Location: `tests/Unit/Services/SupplierServiceTest.php` (new directory `Unit/Services/`)
- Mock repository with `Mockery::mock(SupplierRepositoryInterface::class)`

### DataProvider Tests

```php
#[DataProvider('rbacProvider')]
public function test_rbac_matrix(string $role, int $expected): void { ... }
```

---

## 8. Route Structure

### api.php Includes Pattern

```php
// Existing pattern:
require __DIR__.'/api/v1/auth.php';
require __DIR__.'/api/v1/users.php';
require __DIR__.'/api/v1/admin.php';

// New line to add:
require __DIR__.'/api/v1/suppliers.php';
```

### Route File Location

`backend/routes/api/v1/suppliers.php`

---

## 9. Frontend Conventions

### Composables

- `useApi()` composable returns `$fetch`-based API client with token auth, refresh, error handling
- Composable pattern: named export function `export function useSupplier() { ... }`
- API base URL from `useRuntimeConfig().public.apiBaseUrl`

### Pinia Stores

- Use Composition API style (`defineStore('name', () => { ... })`)
- `ref()` for reactive state, `computed()` for derived state
- Import `useApi` composable for HTTP calls

### Pages

- RTL via `<html dir="rtl">` in Nuxt config (inherited — no per-page override needed)
- Auth + role guards via Nuxt middleware
- `<script setup lang="ts">` + TypeScript throughout

---

## 10. Frontend i18n

- Locale files: `frontend/locales/ar.json` and `frontend/locales/en.json`
- Nested key format: `suppliers.title`, `suppliers.status.verified`, etc.
- Existing files must be **updated** (not replaced) with supplier keys nested under `"suppliers": {...}`

---

## 11. Resolved Unknowns

| Unknown                                                    | Resolution                                                            |
| ---------------------------------------------------------- | --------------------------------------------------------------------- |
| Does `UserFactory` have a `contractor()` state?            | **Yes** — confirmed in `UserFactory.php`                              |
| Does `app/Policies/` exist?                                | **No** — must create directory and first policy                       |
| Does `Route::model()` already exist in AppServiceProvider? | **No** — must add with `use Illuminate\Support\Facades\Route;` import |
| Does V1 test directory exist?                              | `tests/Feature/Api/V1/` does not exist yet — must create              |
| Does `tests/Unit/Services/` exist?                         | Not confirmed — must create                                           |
| Frontend pages structure for admin?                        | `pages/admin/` exists but only has `roles/` subdir                    |
| Frontend pages for suppliers area?                         | `pages/suppliers/` does not exist yet                                 |
