# Technical Plan — 009-suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Based on:** `specs/runtime/009-suppliers/spec.md`
> **Branch:** `spec/009-suppliers`
> **Created:** 2026-04-15

---

## Architecture Overview

**Pattern:** Request → Sanctum → Policy/Gate → Controller (thin) → Service (business logic) → Repository (DB) → Model

- `SupplierProfile` is a separate model (not a User subclass); contractors have a 1-to-1 relationship with it
- Verification is a finite state machine (`pending → verified ↔ suspended`), enforced in `SupplierService`
- Route model binding `{supplier}` resolves to `SupplierProfile`; visibility (404 vs 200) is enforced inside the service, not the middleware
- Admin superuser bypass in `Gate::before` means `SupplierPolicy` only handles non-admin cases
- `aggregateRatings()` is a no-op stub; products endpoint returns empty collection stub

---

## Phase 0 — Database Layer

### P0.1 — Enum: SupplierVerificationStatus

**File:** `backend/app/Enums/SupplierVerificationStatus.php`
**Class:** `App\Enums\SupplierVerificationStatus`

```php
enum SupplierVerificationStatus: string
{
    case Pending   = 'pending';
    case Verified  = 'verified';
    case Suspended = 'suspended';
}
```

**Dependencies:** None.

---

### P0.2 — Migration: create_supplier_profiles_table

**File:** `backend/database/migrations/2026_04_15_000001_create_supplier_profiles_table.php`

**Key Columns:**

- `id` — BIGINT PK auto-increment
- `user_id` — BIGINT UNSIGNED UNIQUE FK → `users.id` CASCADE DELETE
- `company_name_ar`, `company_name_en` — VARCHAR(255) NOT NULL
- `commercial_reg` — VARCHAR(100) UNIQUE NOT NULL
- `tax_number` — VARCHAR(50) NULLABLE
- `city` — VARCHAR(100) NOT NULL
- `district`, `address` — NULLABLE
- `phone` — VARCHAR(20) NOT NULL
- `verification_status` — ENUM('pending','verified','suspended') DEFAULT 'pending'
- `verified_at` — TIMESTAMP NULLABLE
- `verified_by` — BIGINT UNSIGNED NULLABLE FK → `users.id` NULL ON DELETE
- `rating_avg` — DECIMAL(8,2) DEFAULT 0.00
- `total_ratings` — INT UNSIGNED DEFAULT 0
- `description_ar`, `description_en` — TEXT NULLABLE
- `logo` — VARCHAR(500) NULLABLE
- `website` — VARCHAR(255) NULLABLE
- `timestamps()` + `softDeletes()`

**Indexes:** `user_id` (unique), `commercial_reg` (unique), `verification_status` (index), `city` (index)

**Dependencies:** `users` table (existing), `SupplierVerificationStatus` enum (P0.1)

---

### P0.3 — Model: SupplierProfile

**File:** `backend/app/Models/SupplierProfile.php`
**Class:** `App\Models\SupplierProfile`
**Extends:** `App\Models\BaseModel` (inherits `HasFactory`, `SoftDeletes`, `$guarded = []`)

**`$fillable`:** `company_name_ar`, `company_name_en`, `commercial_reg`, `tax_number`, `city`, `district`, `address`, `phone`, `description_ar`, `description_en`, `logo`, `website`

> ⚠ **SEC-FINDING-A:** `verification_status`, `user_id`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings` are NOT in `$fillable` (mass-assignment privilege escalation prevention).

**`casts()`:**

```php
[
    'verification_status' => SupplierVerificationStatus::class,
    'verified_at'         => 'datetime',
    'rating_avg'          => 'decimal:2',
]
```

**Relationships:**

- `user(): BelongsTo<User, $this>` — owning contractor via `user_id`
- `verifier(): BelongsTo<User, $this>` — admin who verified via `verified_by`
- Products relationship commented-out stub until STAGE_08

**Scopes:**

- `scopeVerified(Builder $query): Builder` — `verification_status = 'verified'`
- `scopeByCity(Builder $query, string $city): Builder`
- `scopeSearch(Builder $query, string $term): Builder` — LIKE on both name columns
- `scopeVisibleTo(Builder $query, ?User $actor): Builder` — Admin: all statuses (including pending/suspended); Contractor: verified + own profile (`user_id = $actor->id`); Guest/others: verified only

**Also update:** `backend/app/Models/User.php` — add `supplierProfile(): HasOne` relationship.

**Dependencies:** `SupplierVerificationStatus` (P0.1), migration (P0.2), `User` model (existing)

---

### P0.4 — Factory: SupplierProfileFactory

**File:** `backend/database/factories/SupplierProfileFactory.php`
**Class:** `Database\Factories\SupplierProfileFactory` extends `Factory<SupplierProfile>`

**States:**

- `definition()` — default pending supplier, generates contractor user via `User::factory()->contractor()`
- `verified()` — sets `verification_status = 'verified'`, `verified_at = now()`
- `pending()` — explicit pending state
- `suspended()` — sets `verification_status = 'suspended'`

**Dependencies:** `SupplierProfile` model (P0.3), `UserFactory::contractor()` (existing)

---

## Phase 1 — Backend Core

### P1.1 — Repository Interface: SupplierRepositoryInterface

**File:** `backend/app/Repositories/Contracts/SupplierRepositoryInterface.php`
**Interface:** `App\Repositories\Contracts\SupplierRepositoryInterface`

**Methods:**

```php
public function paginate(array $filters, int $perPage, ?User $actor = null): LengthAwarePaginator;
public function findById(int $id): ?SupplierProfile;
public function findByUserId(int $userId): ?SupplierProfile;
public function create(array $data): SupplierProfile;
public function update(SupplierProfile $supplier, array $data): SupplierProfile;
public function delete(SupplierProfile $supplier): bool;
public function updateVerificationStatus(
    SupplierProfile $supplier,
    SupplierVerificationStatus $status,
    ?int $verifiedBy
): SupplierProfile;
```

> **Design note:** Interface does NOT extend `RepositoryInterface` — the method signatures differ (typed `SupplierProfile` return types, custom `$filters` param). PHPStan level 8 requires exact typed returns.

**Dependencies:** `SupplierProfile` model (P0.3), `SupplierVerificationStatus` enum (P0.1)

---

### P1.2 — Repository: SupplierRepository

**File:** `backend/app/Repositories/SupplierRepository.php`
**Class:** `App\Repositories\SupplierRepository`
**Extends:** `BaseRepository`
**Implements:** `SupplierRepositoryInterface`

**Method implementations:**

- `paginate(array $filters, int $perPage, ?User $actor = null)` — applies `scopeVisibleTo($actor)` first for role-based visibility, then additional filter scopes from `$filters` keys (`city`, `district`, `search`) using `when()` builder; the `verification_status` filter param is only honoured for admin actors (enforced in service)
- `findById(int $id): ?SupplierProfile` — `SupplierProfile::find($id)`
- `findByUserId(int $userId): ?SupplierProfile` — `where('user_id', $userId)->first()`
- `create(array $data): SupplierProfile` — merges `user_id`; calls `SupplierProfile::create($data)`
- `update(SupplierProfile $supplier, array $data): SupplierProfile` — `$supplier->update($data); return $supplier->fresh()`
- `delete(SupplierProfile $supplier): bool` — `$supplier->delete()`
- `updateVerificationStatus(...)` — explicitly sets columns and calls `save()`

**Dependencies:** `SupplierProfile` (P0.3), `SupplierRepositoryInterface` (P1.1), `SupplierVerificationStatus` (P0.1), `BaseRepository` (existing)

---

### P1.3 — Service: SupplierService

**File:** `backend/app/Services/SupplierService.php`
**Class:** `App\Services\SupplierService`

**Constructor:** `private readonly SupplierRepositoryInterface $supplierRepository`

**Methods:**

| Method             | Signature                                                                 | Business Rules                                                                                                                                                                                                                                                                                                                                                          |
| ------------------ | ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `list`             | `list(array $filters, ?User $actor): LengthAwarePaginator`                | Passes `$actor` to `repository->paginate()`; repository applies `scopeVisibleTo($actor)` for role-based visibility; contractor sees verified + own; admin sees all                                                                                                                                                                                                      |
| `show`             | `show(int $id, ?User $actor): SupplierProfile`                            | Returns 404-equivalent (`RESOURCE_NOT_FOUND`) for non-visible non-verified profiles                                                                                                                                                                                                                                                                                     |
| `create`           | `create(array $data, User $actor): SupplierProfile`                       | If actor is Admin AND `$data['user_id']` provided: verify target user has `contractor` role, use `$data['user_id']` as profile owner. If actor is Admin AND no `user_id`: throw `VALIDATION_ERROR` (admin must specify which contractor). If actor is Contractor: always use `$actor->id`, ignore any `user_id` in data. Duplicate check; creates with `pending` status |
| `update`           | `update(int $id, array $data, User $actor): SupplierProfile`              | Ownership check unless admin; delegates to repository                                                                                                                                                                                                                                                                                                                   |
| `verify`           | `verify(int $id, User $admin): SupplierProfile`                           | Already-verified is idempotent; all transitions to verified permitted                                                                                                                                                                                                                                                                                                   |
| `suspend`          | `suspend(int $id, User $admin): SupplierProfile`                          | Already-suspended is idempotent; updates status                                                                                                                                                                                                                                                                                                                         |
| `listProducts`     | `listProducts(int $id, ?User $actor, int $perPage): LengthAwarePaginator` | Visibility check; returns empty paginator stub                                                                                                                                                                                                                                                                                                                          |
| `aggregateRatings` | `aggregateRatings(int $supplierId): void`                                 | **No-op stub** — implemented as empty method                                                                                                                                                                                                                                                                                                                            |

**Error throwing (via `ApiException::make`):**

- Role mismatch on create → `RBAC_ROLE_DENIED`
- Duplicate profile → `CONFLICT_ERROR`
- Not found / not visible → `RESOURCE_NOT_FOUND`
- Not own profile (update) → `AUTH_UNAUTHORIZED`

**Dependencies:** `SupplierRepositoryInterface` (P1.1), `ApiException`, `ApiErrorCode`, `UserRole` (existing), `SupplierVerificationStatus` (P0.1)

---

### P1.4 — AppServiceProvider: Bindings & Route Model Binding

**File:** `backend/app/Providers/AppServiceProvider.php` — **MODIFY EXISTING**

**Changes to `register()`:** Add:

```php
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\SupplierRepository;
use App\Models\SupplierProfile;

$this->app->bind(SupplierRepositoryInterface::class,
    fn () => new SupplierRepository(new SupplierProfile)
);
```

**Changes to `boot()`:** Add route model binding:

```php
use Illuminate\Support\Facades\Route;  // Already imported? Check.

Route::model('supplier', SupplierProfile::class);
```

> **Check:** Verify `use Illuminate\Support\Facades\Route` is already present. If not, add the import.

**Dependencies:** `SupplierRepository` (P1.2), `SupplierRepositoryInterface` (P1.1), `SupplierProfile` (P0.3)

---

## Phase 2 — HTTP Layer

### P2.1 — Form Request: StoreSupplierRequest

**File:** `backend/app/Http/Requests/Supplier/StoreSupplierRequest.php`
**Class:** `App\Http\Requests\Supplier\StoreSupplierRequest` extends `FormRequest`

**`authorize()`:** `return true;` — RBAC enforced via Policy in controller

**`rules()`:**

```php
[
    'company_name_ar' => ['required', 'string', 'max:255'],
    'company_name_en' => ['required', 'string', 'max:255'],
    'commercial_reg'  => ['required', 'string', 'max:100', 'unique:supplier_profiles,commercial_reg'],
    'phone'           => ['required', 'string', 'regex:/^05\d{8}$/'],
    'city'            => ['required', 'string', 'max:100'],
    'tax_number'      => ['nullable', 'string', 'max:50'],
    'district'        => ['nullable', 'string', 'max:100'],
    'address'         => ['nullable', 'string', 'max:500'],
    'description_ar'  => ['nullable', 'string', 'max:2000'],
    'description_en'  => ['nullable', 'string', 'max:2000'],
    'logo'            => ['nullable', 'string', 'url', 'max:500'],
    'website'         => ['nullable', 'url', 'max:255'],
    'user_id'         => ['nullable', 'integer', 'exists:users,id'],
]
```

> **Note:** `user_id` is only consumed by Admin create-on-behalf logic in `SupplierService::create()`. For Contractor actors, `user_id` is always overridden with `$actor->id` regardless of what is submitted.

**Dependencies:** `FormRequest` (Laravel)

---

### P2.2 — Form Request: UpdateSupplierRequest

**File:** `backend/app/Http/Requests/Supplier/UpdateSupplierRequest.php`
**Class:** `App\Http\Requests\Supplier\UpdateSupplierRequest` extends `FormRequest`

Same rules as `StoreSupplierRequest`, all wrapped in `sometimes`:

```php
'company_name_ar' => ['sometimes', 'string', 'max:255'],
```

and `commercial_reg` uniqueness check uses `ignore` for the current record:

```php
'commercial_reg' => [
    'sometimes', 'string', 'max:100',
    Rule::unique('supplier_profiles', 'commercial_reg')->ignore($this->route('supplier')?->id),
],
```

**Dependencies:** `FormRequest`, `Illuminate\Validation\Rule`

---

### P2.3 — Form Request: VerifySupplierRequest

**File:** `backend/app/Http/Requests/Supplier/VerifySupplierRequest.php`
**Class:** `App\Http\Requests\Supplier\VerifySupplierRequest` extends `FormRequest`

No request body validation needed; auth is handled by middleware and Policy.

```php
public function authorize(): bool { return true; }
public function rules(): array { return []; }
```

**Dependencies:** `FormRequest`

> **ADR Reference:** See `ADR-009-01-supplier-show-404-over-403.md` — `SupplierController::show()` returns 404 instead of 403 for unverified profiles to prevent existence enumeration. This is the rationale for `VerifySupplierRequest::authorize()` returning `true` (the controller delegates visibility enforcement to the service, not the policy).

---

### P2.3a — Form Request: SuspendSupplierRequest

**File:** `backend/app/Http/Requests/Supplier/SuspendSupplierRequest.php`
**Class:** `App\Http\Requests\Supplier\SuspendSupplierRequest` extends `FormRequest`

Mirrors `VerifySupplierRequest` — no request body; authorization via Policy (admin-only, enforced by `Gate::before`).

```php
public function authorize(): bool { return true; }
public function rules(): array { return []; }
```

**Dependencies:** `FormRequest`

---

### P2.4 — Policy: SupplierPolicy

**File:** `backend/app/Policies/SupplierPolicy.php`
**Class:** `App\Policies\SupplierPolicy`

> **Note:** `app/Policies/` directory does not yet exist. Create it.
> **Note:** Admin cases are NOT handled inside policy methods — `Gate::before` bypasses them.
> **ADR Reference:** [`ADR-009-01`](../../../docs/architecture/ADR/ADR-009-01-supplier-show-404-over-403.md) — `SupplierController::show()` skips `Policy::view()` and instead defers to `SupplierService::show()` which throws `RESOURCE_NOT_FOUND` (404) for non-visible profiles. `Policy::view()` is only called via `index()` per-item filtering.

**Methods:**

| Method    | Signature                                             | Logic                                                                                                                            |
| --------- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `viewAny` | `viewAny(?User $user): bool`                          | Always `true` (public access)                                                                                                    |
| `view`    | `view(?User $user, SupplierProfile $supplier): bool`  | `true` if supplier is `verified` OR ($user exists AND ($user->id === $supplier->user_id OR $user->hasEnumRole(UserRole::ADMIN))) |
| `create`  | `create(User $user): bool`                            | `$user->hasEnumRole(UserRole::CONTRACTOR)` — duplicate check done in service                                                     |
| `update`  | `update(User $user, SupplierProfile $supplier): bool` | `$user->id === $supplier->user_id`                                                                                               |
| `verify`  | `verify(User $user): bool`                            | `false` for all non-admin (admin bypassed by Gate::before)                                                                       |
| `suspend` | `suspend(User $user): bool`                           | `false` for all non-admin                                                                                                        |
| `delete`  | `delete(User $user): bool`                            | `false` for all non-admin                                                                                                        |

**Dependencies:** `User` model, `SupplierProfile` model (P0.3), `UserRole` enum (existing)

---

### P2.5 — Resource: SupplierResource

**File:** `backend/app/Http/Resources/SupplierResource.php`
**Class:** `App\Http\Resources\SupplierResource` extends `BaseApiResource`

**`toArray(Request $request)` output:**

```php
[
    'id'                  => $this->id,
    'user_id'             => $this->user_id,
    'company_name_ar'     => $this->company_name_ar,
    'company_name_en'     => $this->company_name_en,
    'commercial_reg'      => $this->commercial_reg,
    'tax_number'          => $this->tax_number,
    'city'                => $this->city,
    'district'            => $this->district,
    'address'             => $this->address,
    'phone'               => $this->phone,
    'verification_status' => $this->verification_status instanceof SupplierVerificationStatus
                                 ? $this->verification_status->value
                                 : $this->verification_status,
    'verified_at'         => $this->verified_at?->toISOString(),
    'verified_by'         => $this->verified_by,
    'rating_avg'          => $this->rating_avg,
    'total_ratings'       => $this->total_ratings,
    'description_ar'      => $this->description_ar,
    'description_en'      => $this->description_en,
    'logo'                => $this->logo,
    'website'             => $this->website,
    'created_at'          => $this->created_at?->toISOString(),
    'updated_at'          => $this->updated_at?->toISOString(),
]
```

**Dependencies:** `BaseApiResource` (existing), `SupplierProfile` (P0.3), `SupplierVerificationStatus` (P0.1)

---

### P2.6 — Controller: SupplierController

**File:** `backend/app/Http/Controllers/Api/V1/SupplierController.php`
**Class:** `App\Http\Controllers\Api\V1\SupplierController`
**Extends:** `BaseApiController` (has `paginated()` method)

**Constructor:** `private readonly SupplierService $supplierService`

**Methods:**

| Method     | Route                                | Auth     | Key Logic                                                                                                                                   |
| ---------- | ------------------------------------ | -------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `index`    | `GET /suppliers`                     | optional | `authorize('viewAny', SupplierProfile::class)`; get perPage (min 1, max 100); call `service->list()`; return `paginated()`                  |
| `show`     | `GET /suppliers/{supplier}`          | optional | `authorize('view', $supplier)`; call `service->show()`; return `success(new SupplierResource($supplier))`                                   |
| `store`    | `POST /suppliers`                    | required | `authorize('create', SupplierProfile::class)`; call `service->create($request->validated(), $request->user())`; return `success()` with 201 |
| `update`   | `PUT /suppliers/{supplier}`          | required | `authorize('update', $supplier)`; call `service->update()`; return `success()`                                                              |
| `verify`   | `PUT /suppliers/{supplier}/verify`   | required | `authorize('verify', SupplierProfile::class)`; call `service->verify()`; return `success()`                                                 |
| `suspend`  | `PUT /suppliers/{supplier}/suspend`  | required | Type-hint `SuspendSupplierRequest $request`; `authorize('suspend', SupplierProfile::class)`; call `service->suspend()`; return `success()`  |
| `products` | `GET /suppliers/{supplier}/products` | optional | `authorize('view', $supplier)`; call `service->listProducts()`; return `paginated()`                                                        |

> **Note on `show()`:** The Policy `view()` method will return false for non-visible suppliers, but the service also throws `RESOURCE_NOT_FOUND`. The controller should catch `AuthorizationException` and re-throw as 404 to avoid information leakage. Alternatively, the service enforces visibility directly and the controller calls service (not policy) first.
>
> **DECISION:** The controller calls `$this->authorize()` using the Policy, which will throw `AuthorizationException` → 403, but for `view` we want 404. Therefore: in `show()` the controller skips `authorize()` and instead the service enforces visibility (returning `RESOURCE_NOT_FOUND` which maps to 404 via `ApiException`). The `Gate::before` admin bypass is still active for the admin use case.

**Dependencies:** `SupplierService` (P1.3), `SupplierResource` (P2.5), `SupplierProfile` (P0.3), `StoreSupplierRequest` (P2.1), `UpdateSupplierRequest` (P2.2), `VerifySupplierRequest` (P2.3), `SuspendSupplierRequest` (P2.3a), `BaseApiController` (existing)

---

### P2.7 — Routes: suppliers.php

**File:** `backend/routes/api/v1/suppliers.php`

```php
<?php

use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

/**
 * Supplier Routes — API v1
 *
 * Provides the supplier directory, profile management, and verification workflow.
 * Route model binding: {supplier} → App\Models\SupplierProfile
 * Registered in AppServiceProvider::boot() via Route::model().
 */
Route::prefix('suppliers')->name('api.v1.suppliers.')->group(function () {
    // Public routes (no auth required)
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/{supplier}', [SupplierController::class, 'show'])->name('show');
    Route::get('/{supplier}/products', [SupplierController::class, 'products'])->name('products');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::put('/{supplier}/verify', [SupplierController::class, 'verify'])->name('verify');
        Route::put('/{supplier}/suspend', [SupplierController::class, 'suspend'])->name('suspend');
    });
});
```

**Also modify:** `backend/routes/api.php` — add `require __DIR__.'/api/v1/suppliers.php';` inside the `v1` group.

---

## Phase 3 — Frontend

### P3.1 — Composable: useSupplier

**File:** `frontend/composables/useSupplier.ts`

**Exports:** `export function useSupplier()`

**Methods:**

```typescript
function listSuppliers(params?: SupplierListParams): Promise<PaginatedResponse<Supplier>>;
function getSupplier(id: number): Promise<Supplier>;
function createSupplier(data: CreateSupplierData): Promise<Supplier>;
function updateSupplier(id: number, data: UpdateSupplierData): Promise<Supplier>;
function verifySupplier(id: number): Promise<VerificationResult>;
function suspendSupplier(id: number): Promise<VerificationResult>;
function getSupplierProducts(
  id: number,
  params?: PaginationParams
): Promise<PaginatedResponse<unknown>>;
```

**Internals:** Uses `useApi()` composable for all HTTP calls.

**Types (define inline or in `types/supplier.ts`):**

```typescript
interface Supplier {
  id: number;
  user_id: number;
  company_name_ar: string;
  company_name_en: string;
  commercial_reg: string;
  tax_number: string | null;
  city: string;
  district: string | null;
  address: string | null;
  phone: string;
  verification_status: 'pending' | 'verified' | 'suspended';
  verified_at: string | null;
  verified_by: number | null;
  rating_avg: string;
  total_ratings: number;
  description_ar: string | null;
  description_en: string | null;
  logo: string | null;
  website: string | null;
  created_at: string;
  updated_at: string;
}
```

**Dependencies:** `useApi` composable (existing)

---

### P3.2 — Store: useSupplierStore

**File:** `frontend/stores/useSupplierStore.ts`

**Pattern:** Composition API style (`defineStore('suppliers', () => { ... })`)

**State:**

```typescript
const suppliers = ref<Supplier[]>([]);
const currentSupplier = ref<Supplier | null>(null);
const meta = ref<PaginationMeta | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
```

**Actions:**

- `fetchSuppliers(params?)` — calls `useSupplier().listSuppliers()`
- `fetchSupplier(id)` — calls `useSupplier().getSupplier()`
- `createSupplier(data)` — calls composable, pushes to store
- `updateSupplier(id, data)` — updates in-store after success
- `verifySupplier(id)` — calls composable, updates local state
- `suspendSupplier(id)` — calls composable, updates local state

**Getters:**

- `verifiedSuppliers` — computed filtered list

**Dependencies:** `useSupplier` composable (P3.1), Pinia

---

### P3.3 — Component: SupplierCard.vue

**File:** `frontend/components/supplier/SupplierCard.vue`

**Props:**

```typescript
interface Props {
  supplier: Supplier;
}
```

**Display:** Logo, Arabic company name (primary), English name (secondary), city, verification status badge, rating (if avg > 0).

**Uses:** `VerificationStatusBadge` component, `UCard` (Nuxt UI), `NuxtLink` for profile URL.

**RTL:** Inherits from `<html dir="rtl">`.

---

### P3.4 — Component: VerificationStatusBadge.vue

**File:** `frontend/components/supplier/VerificationStatusBadge.vue`

**Props:**

```typescript
interface Props {
  status: 'pending' | 'verified' | 'suspended';
}
```

**Display:** Color-coded `UBadge` (Nuxt UI):

- `pending` → yellow/warning
- `verified` → green/success
- `suspended` → red/error

**Uses i18n:** `t('suppliers.status.pending')`, etc.

---

### P3.5 — Component: SupplierForm.vue

**File:** `frontend/components/supplier/SupplierForm.vue`

**Props:**

```typescript
interface Props {
  initialData?: Partial<Supplier>;
  isEdit?: boolean;
}

interface Emits {
  (e: 'submit', data: CreateSupplierData | UpdateSupplierData): void;
}
```

**Validation:** VeeValidate + Zod schema matching backend rules:

```typescript
const schema = z.object({
  company_name_ar: z.string().min(1).max(255),
  company_name_en: z.string().min(1).max(255),
  commercial_reg: z.string().min(1).max(100),
  phone: z.string().regex(/^05\d{8}$/),
  city: z.string().min(1).max(100),
  // ... all optional fields
});
```

**Fields:** All profile fields with RTL-appropriate labels. Uses `UForm`, `UFormField`, `UInput`, `UTextarea`, `UButton` (Nuxt UI).

---

### P3.6 — Pages

#### `/suppliers` — Directory

**File:** `frontend/pages/suppliers/index.vue`
**Access:** Public
**Layout:** `default`

Features:

- Supplier directory grid using `SupplierCard.vue`
- Filter/search bar (city, search term)
- Pagination (`UPagination` Nuxt UI)
- Loading skeleton states
- Empty state message (Arabic/English)

---

#### `/suppliers/[id]` — Profile

**File:** `frontend/pages/suppliers/[id].vue`
**Access:** Public
**Layout:** `default`

Features:

- Full supplier profile display
- All company details (bilingual)
- Ratings display (if total_ratings > 0)
- Products section (empty stub with "Coming Soon" message)
- 404 redirect for not found

---

#### `/dashboard/supplier/profile` — Contractor Self-Management

**File:** `frontend/pages/dashboard/supplier/profile.vue`
**Access:** Contractor role required
**Layout:** `dashboard`
**Middleware:** `auth` + role guard (contractor)

Features:

- Shows existing profile (if exists) or registration form
- Uses `SupplierForm.vue` for create/edit
- Status badge showing current verification_status
- Save/update via store actions

---

#### `/admin/suppliers` — Admin Management

**File:** `frontend/pages/admin/suppliers/index.vue`
**Access:** Admin role required
**Layout:** `admin`
**Middleware:** `auth` + role guard (admin)

Features:

- `UTable` with all suppliers (all statuses)
- Filter bar: `verification_status`, `city`, `search`
- Inline verify/suspend action buttons
- Status badge column
- Link to individual supplier edit
- `UPagination` for pagination

---

## Phase 4 — Tests

### P4.1 — Factory Usage

Primary test factory: `SupplierProfile::factory()` with states `.verified()`, `.pending()`, `.suspended()`.

For feature tests, create users via `User::factory()->contractor()->create()` then attach supplier profile: `SupplierProfile::factory()->create(['user_id' => $user->id])`.

---

### P4.2 — Seeder: SupplierSeeder

**File:** `backend/database/seeders/SupplierSeeder.php`
**Class:** `Database\Seeders\SupplierSeeder`

Creates:

- 5 verified suppliers (with contractor users)
- 3 pending suppliers
- 2 suspended suppliers

Used by database seeder for dev/staging environments.

---

### P4.3 — Feature Tests: SupplierControllerTest

**File:** `backend/tests/Feature/Api/V1/SupplierControllerTest.php`

**Full RBAC matrix per spec §9.2:**

| Test name                                          | Actors tested                    | Assertions                                  |
| -------------------------------------------------- | -------------------------------- | ------------------------------------------- |
| `test_public_sees_only_verified_suppliers`         | Guest                            | status 200, all items have status=verified  |
| `test_admin_sees_all_suppliers`                    | Admin                            | status 200, items include pending/suspended |
| `test_contractor_sees_own_pending_in_list`         | Contractor with pending profile  | 200, own profile included                   |
| `test_show_verified_supplier_public`               | Guest                            | 200, full resource                          |
| `test_show_pending_supplier_returns_404_for_guest` | Guest                            | 404                                         |
| `test_show_pending_supplier_visible_to_owner`      | Contractor (owner)               | 200                                         |
| `test_show_suspended_visible_to_admin`             | Admin                            | 200                                         |
| `test_store_requires_auth`                         | Guest                            | 401                                         |
| `test_store_fails_for_customer`                    | Customer                         | 403                                         |
| `test_store_succeeds_for_contractor`               | Contractor                       | 201, profile created                        |
| `test_store_duplicate_profile_returns_conflict`    | Contractor (already has profile) | 409                                         |
| `test_store_admin_can_create_for_any_contractor`   | Admin                            | 201                                         |
| `test_update_succeeds_for_owner_contractor`        | Contractor (own)                 | 200                                         |
| `test_update_fails_for_other_contractor`           | Contractor (other)               | 403                                         |
| `test_update_admin_can_update_any`                 | Admin                            | 200                                         |
| `test_verify_requires_admin`                       | Contractor                       | 403                                         |
| `test_verify_succeeds_for_admin`                   | Admin                            | 200, status=verified                        |
| `test_suspend_requires_admin`                      | Contractor                       | 403                                         |
| `test_suspend_succeeds_for_admin`                  | Admin                            | 200, status=suspended                       |
| `test_products_endpoint_returns_empty_array`       | Guest                            | 200, data=[]                                |

---

### P4.4 — Feature Tests: SupplierVerificationWorkflowTest

**File:** `backend/tests/Feature/Api/V1/SupplierVerificationWorkflowTest.php`

| Test                         | Initial   | Action  | Expected                  |
| ---------------------------- | --------- | ------- | ------------------------- |
| `test_pending_to_verified`   | pending   | verify  | 200, verified             |
| `test_verified_to_suspended` | verified  | suspend | 200, suspended            |
| `test_suspended_to_verified` | suspended | verify  | 200, verified             |
| `test_pending_to_suspended`  | pending   | suspend | 200, suspended            |
| `test_verify_idempotent`     | verified  | verify  | 200, verified (no error)  |
| `test_suspend_idempotent`    | suspended | suspend | 200, suspended (no error) |

---

### P4.5 — Unit Tests: SupplierServiceTest

**File:** `backend/tests/Unit/Services/SupplierServiceTest.php`

Uses `Mockery::mock(SupplierRepositoryInterface::class)`.

| Test                                             | What it tests                 |
| ------------------------------------------------ | ----------------------------- |
| `test_list_returns_only_verified_for_null_actor` | Public filtering              |
| `test_list_returns_all_for_admin`                | Admin bypass                  |
| `test_create_succeeds_for_contractor`            | Happy path create             |
| `test_create_fails_if_duplicate_profile`         | CONFLICT_ERROR thrown         |
| `test_create_fails_for_non_contractor`           | RBAC_ROLE_DENIED thrown       |
| `test_update_succeeds_for_owner`                 | Owner can update              |
| `test_update_fails_for_non_owner_contractor`     | AUTH_UNAUTHORIZED thrown      |
| `test_verify_transitions_to_verified`            | verified_at set               |
| `test_suspend_transitions_to_suspended`          | status updated                |
| `test_suspend_is_idempotent`                     | No error on double suspend    |
| `test_aggregate_ratings_is_noop`                 | Returns void, no side effects |

---

## Phase 5 — i18n

### P5.1 — Backend: Arabic Translation File

**File:** `backend/lang/ar/suppliers.php`

```php
return [
    'profile_created'         => 'تم إنشاء ملف المورّد بنجاح',
    'profile_updated'         => 'تم تحديث ملف المورّد بنجاح',
    'profile_verified'        => 'تم التحقق من المورّد بنجاح',
    'profile_suspended'       => 'تم تعليق حساب المورّد',
    'not_found'               => 'المورّد غير موجود',
    'already_exists'          => 'يمتلك هذا المقاول ملف شركة مسجّل مسبقاً',
    'role_required'           => 'يجب أن تكون مقاولاً لإنشاء ملف مورّد',
    'unauthorized_update'     => 'ليس لديك صلاحية تعديل هذا الملف',
    'validation' => [
        'company_name_ar.required' => 'اسم الشركة بالعربية مطلوب',
        'company_name_en.required' => 'اسم الشركة بالإنجليزية مطلوب',
        'commercial_reg.required'  => 'رقم السجل التجاري مطلوب',
        'commercial_reg.unique'    => 'رقم السجل التجاري مسجّل مسبقاً',
        'phone.required'           => 'رقم الهاتف مطلوب',
        'phone.regex'              => 'رقم الهاتف يجب أن يبدأ بـ 05 ويتكون من 10 أرقام',
        'city.required'            => 'المدينة مطلوبة',
    ],
];
```

---

### P5.2 — Backend: English Translation File

**File:** `backend/lang/en/suppliers.php`

```php
return [
    'profile_created'         => 'Supplier profile created successfully',
    'profile_updated'         => 'Supplier profile updated successfully',
    'profile_verified'        => 'Supplier verified successfully',
    'profile_suspended'       => 'Supplier account suspended',
    'not_found'               => 'Supplier not found',
    'already_exists'          => 'This contractor already has a registered supplier profile',
    'role_required'           => 'You must be a contractor to create a supplier profile',
    'unauthorized_update'     => 'You are not authorized to update this profile',
    'validation' => [
        'company_name_ar.required' => 'Arabic company name is required',
        'company_name_en.required' => 'English company name is required',
        'commercial_reg.required'  => 'Commercial registration number is required',
        'commercial_reg.unique'    => 'Commercial registration number is already registered',
        'phone.required'           => 'Phone number is required',
        'phone.regex'              => 'Phone number must start with 05 and be 10 digits',
        'city.required'            => 'City is required',
    ],
];
```

---

### P5.3 — Frontend: Locale Keys

**Files:** Update `frontend/locales/ar.json` and `frontend/locales/en.json` by adding a `"suppliers"` key:

**Arabic additions:**

```json
{
  "suppliers": {
    "title": "الموردون",
    "directory": "دليل الموردين",
    "profile": "ملف المورّد",
    "register": "تسجيل كمورّد",
    "edit": "تعديل الملف",
    "status": {
      "pending": "في انتظار التحقق",
      "verified": "موثّق",
      "suspended": "موقوف"
    },
    "fields": {
      "company_name_ar": "اسم الشركة (عربي)",
      "company_name_en": "اسم الشركة (إنجليزي)",
      "commercial_reg": "رقم السجل التجاري",
      "city": "المدينة",
      "phone": "رقم الهاتف",
      "rating": "التقييم",
      "description": "وصف الشركة",
      "logo": "الشعار",
      "website": "الموقع الإلكتروني"
    },
    "admin": {
      "manage": "إدارة الموردين",
      "verify_action": "توثيق",
      "suspend_action": "تعليق"
    },
    "empty": "لا يوجد موردون مسجّلون",
    "products_coming_soon": "منتجات هذا المورّد ستكون متاحة قريباً"
  }
}
```

---

## File Inventory

| #   | File Path                                                                          | Type            | Action                           |
| --- | ---------------------------------------------------------------------------------- | --------------- | -------------------------------- |
| 1   | `backend/app/Enums/SupplierVerificationStatus.php`                                 | Enum            | CREATE                           |
| 2   | `backend/database/migrations/2026_04_15_000001_create_supplier_profiles_table.php` | Migration       | CREATE                           |
| 3   | `backend/app/Models/SupplierProfile.php`                                           | Model           | CREATE                           |
| 4   | `backend/app/Models/User.php`                                                      | Model           | MODIFY (add `supplierProfile()`) |
| 5   | `backend/database/factories/SupplierProfileFactory.php`                            | Factory         | CREATE                           |
| 6   | `backend/app/Repositories/Contracts/SupplierRepositoryInterface.php`               | Interface       | CREATE                           |
| 7   | `backend/app/Repositories/SupplierRepository.php`                                  | Repository      | CREATE                           |
| 8   | `backend/app/Services/SupplierService.php`                                         | Service         | CREATE                           |
| 9   | `backend/app/Providers/AppServiceProvider.php`                                     | Provider        | MODIFY                           |
| 10  | `backend/app/Http/Requests/Supplier/StoreSupplierRequest.php`                      | Request         | CREATE                           |
| 11  | `backend/app/Http/Requests/Supplier/UpdateSupplierRequest.php`                     | Request         | CREATE                           |
| 12  | `backend/app/Http/Requests/Supplier/VerifySupplierRequest.php`                     | Request         | CREATE                           |
| 12a | `backend/app/Http/Requests/Supplier/SuspendSupplierRequest.php`                    | Request         | CREATE                           |
| 13  | `backend/app/Policies/SupplierPolicy.php`                                          | Policy          | CREATE (new dir)                 |
| 14  | `backend/app/Http/Resources/SupplierResource.php`                                  | Resource        | CREATE                           |
| 15  | `backend/app/Http/Controllers/Api/V1/SupplierController.php`                       | Controller      | CREATE                           |
| 16  | `backend/routes/api/v1/suppliers.php`                                              | Routes          | CREATE                           |
| 17  | `backend/routes/api.php`                                                           | Routes          | MODIFY                           |
| 18  | `backend/lang/ar/suppliers.php`                                                    | i18n            | CREATE                           |
| 19  | `backend/lang/en/suppliers.php`                                                    | i18n            | CREATE                           |
| 20  | `backend/database/seeders/SupplierSeeder.php`                                      | Seeder          | CREATE                           |
| 21  | `backend/tests/Feature/Api/V1/SupplierControllerTest.php`                          | Test            | CREATE (new dir)                 |
| 22  | `backend/tests/Feature/Api/V1/SupplierVerificationWorkflowTest.php`                | Test            | CREATE                           |
| 23  | `backend/tests/Unit/Services/SupplierServiceTest.php`                              | Test            | CREATE (new dir)                 |
| 24  | `frontend/types/supplier.ts`                                                       | TypeScript type | CREATE                           |
| 25  | `frontend/composables/useSupplier.ts`                                              | Composable      | CREATE                           |
| 26  | `frontend/stores/useSupplierStore.ts`                                              | Store           | CREATE                           |
| 27  | `frontend/components/supplier/SupplierCard.vue`                                    | Component       | CREATE                           |
| 28  | `frontend/components/supplier/VerificationStatusBadge.vue`                         | Component       | CREATE                           |
| 29  | `frontend/components/supplier/SupplierForm.vue`                                    | Component       | CREATE                           |
| 30  | `frontend/pages/suppliers/index.vue`                                               | Page            | CREATE                           |
| 31  | `frontend/pages/suppliers/[id].vue`                                                | Page            | CREATE                           |
| 32  | `frontend/pages/dashboard/supplier/profile.vue`                                    | Page            | CREATE                           |
| 33  | `frontend/pages/admin/suppliers/index.vue`                                         | Page            | CREATE                           |
| 34  | `frontend/locales/ar.json`                                                         | i18n            | MODIFY                           |
| 35  | `frontend/locales/en.json`                                                         | i18n            | MODIFY                           |

**Total:** 33 CREATE, 4 MODIFY = **37 file operations**

---

## Architectural Decisions

### ADR-SUPPLIER-01: `verification_status` Excluded from `$fillable`

**Decision:** `verification_status` is NOT in `SupplierProfile::$fillable`.

**Rationale:** Follows `SEC-FINDING-A` (User.role precedent). Mass-assignment of `verification_status` via a POST/PUT payload would allow privilege escalation (e.g., a contractor self-verifying). The field is set only via explicit `$model->verification_status = $value; $model->save()` inside the service.

**Alternative rejected:** Including it in `$fillable` and relying on form request to omit it — insufficient, because model can be used in other contexts.

---

### ADR-SUPPLIER-02: Service Enforces 404 Visibility (Not Policy)

**Decision:** `SupplierService::show()` throws `RESOURCE_NOT_FOUND` (404) when a non-actor is requesting a non-verified profile, rather than using `Policy::view()` which would return a 403.

**Rationale:** Spec §6.2 states: "Only Admin and the owning Contractor can see non-verified profiles; all other actors receive `RESOURCE_NOT_FOUND`." A 403 would leak the existence of the profile. The service enforces visibility and returns a 404-equivalent `ApiException`, while Policy only governs explicit management operations (update, verify, suspend).

---

### ADR-SUPPLIER-03: `SupplierRepositoryInterface` Does NOT Extend `RepositoryInterface`

**Decision:** `SupplierRepositoryInterface` is a standalone interface, not extending the base `RepositoryInterface`.

**Rationale:** PHPStan level 8 requires covariant return types. The base interface returns `Model`, but supplier methods return `SupplierProfile`. Extending and overriding with a narrower type satisfies PHP covariance rules for return types but requires careful phrasing. A standalone interface avoids this complexity and is cleaner for PHPStan compliance.

---

### ADR-SUPPLIER-04: Products Endpoint Returns Empty Stub

**Decision:** `GET /suppliers/{id}/products` returns an empty paginator stub in STAGE_09.

**Rationale:** The `Product` model and table are defined in STAGE_08 (which may or may not be complete before this stage is implemented). To avoid a hard dependency, the service method returns `new LengthAwarePaginator([], 0, $perPage)`. When STAGE_08 is deployed, the service is updated to delegate to `ProductRepository`.

---

## Constitution Check

| Principle                                 | Status | Notes                                           |
| ----------------------------------------- | ------ | ----------------------------------------------- |
| RBAC enforced server-side                 | ✅     | Policy + Gate::before; no client-only guards    |
| Frontend ↔ Backend via REST API only      | ✅     | No direct DB access from frontend               |
| Controllers are thin                      | ✅     | Delegated to service; no business logic         |
| Business logic in Services                | ✅     | SupplierService owns all rules                  |
| DB access only in Repositories            | ✅     | Service never touches Eloquent directly         |
| Forward-only migrations with down()       | ✅     | Migration includes rollback                     |
| Error contract `{ success, data, error }` | ✅     | ApiResponseTrait + ApiException                 |
| Arabic-first RTL                          | ✅     | All text fields are AR/EN pairs; RTL via Nuxt   |
| PHPStan level 8 compliance                | ✅     | Strict types, typed returns, no mixed           |
| PSR-12 coding style                       | ✅     | `declare(strict_types=1)`, consistent namespace |

---

## Validation Pipeline

Run before any PR:

```bash
# Backend
cd backend
rtk composer run lint
rtk php artisan test --filter=Supplier

# Frontend
cd frontend
rtk npm run lint
rtk npm run typecheck
rtk npm run test -- --reporter=verbose
```

| Unit (JS) | Vitest | 80% |
| Integration | PHPUnit | Key flows |
| E2E | Playwright | Critical paths |

## Security Considerations

- [ ] Input validation via Form Requests
- [ ] RBAC middleware on all protected routes
- [ ] SQL injection prevention (Eloquent parameterized queries)
- [ ] XSS prevention (Blade escaping / Nuxt auto-escaping)
- [ ] CSRF protection
- [ ] Rate limiting on sensitive endpoints

## i18n / RTL Considerations

- [ ] All user-facing strings use translation keys
- [ ] RTL layout verified for Arabic
- [ ] Date/number formatting uses locale-aware helpers

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
| ---- | ---------- | ------ | ---------- |
|      |            |        |            |
