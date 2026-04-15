# Tasks — STAGE_09_SUPPLIERS

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Based on:** `specs/runtime/009-suppliers/plan.md`
> **Branch:** `spec/009-suppliers`
> **Created:** 2026-04-15T00:00:00Z
> **Total Tasks:** 39

---

## Legend

- `T001` — Sequential task ID in dependency/execution order
- `[P]` — Parallelizable with other `[P]` tasks in the same phase group
- `[US1]` — User story reference (see US index below)
- `- [ ]` — Incomplete | `- [x]` — Complete
- _Sub-notes_ — Key constraints, security notes, or acceptance criteria reminders

### User Story Index

| Label | Story                                                      |
| ----- | ---------------------------------------------------------- |
| US1   | Contractor creates supplier profile for own business       |
| US2   | Admin creates profile on behalf of any contractor          |
| US3   | Browse verified supplier directory (public)                |
| US4   | View supplier profile detail                               |
| US5   | Admin verifies/re-verifies supplier (incl. from suspended) |
| US6   | Admin suspends supplier                                    |
| US7   | Contractor/Admin updates supplier profile                  |
| US8   | Admin soft-deletes supplier profile                        |

---

## Phase 0 — Database

> All P0 tasks must complete before P1. T003 and T004 can parallel when T002 is done.

- [ ] T001 Create `SupplierVerificationStatus` PHP 8.1 backed enum — `backend/app/Enums/SupplierVerificationStatus.php`
  - Cases: `Pending = 'pending'`, `Verified = 'verified'`, `Suspended = 'suspended'`
  - Declare `strict_types=1`; namespace `App\Enums`

- [ ] T002 Create migration `create_supplier_profiles_table` (20 columns) — `backend/database/migrations/2026_04_15_000001_create_supplier_profiles_table.php`
  - `user_id` BIGINT UNSIGNED UNIQUE FK → `users.id` CASCADE DELETE
  - `commercial_reg` VARCHAR(100) NOT NULL UNIQUE
  - `verified_by` BIGINT UNSIGNED NULLABLE FK → `users.id` SET NULL (manual `foreign()`)
  - `verification_status` ENUM('pending','verified','suspended') DEFAULT 'pending'
  - `rating_avg` DECIMAL(8,2) DEFAULT 0.00; `total_ratings` INT UNSIGNED DEFAULT 0
  - `timestamps()` + `softDeletes()`
  - Indexes: `unique(user_id)`, `unique(commercial_reg)`, `index(verification_status)`, `index(city)`
  - `down()` calls `Schema::dropIfExists('supplier_profiles')`

- [ ] T003 Create `SupplierProfile` Eloquent model — `backend/app/Models/SupplierProfile.php`
  - Extends `App\Models\BaseModel` (inherits HasFactory, SoftDeletes, `$guarded = []`)
  - **SEC-FINDING-A:** `$fillable` MUST exclude `verification_status`, `user_id`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings` (mass-assignment privilege escalation prevention)
  - `casts()`: `verification_status → SupplierVerificationStatus::class`, `verified_at → 'datetime'`, `rating_avg → 'decimal:2'`
  - Relationships: `user(): BelongsTo<User>` via `user_id`, `verifier(): BelongsTo<User>` via `verified_by` (nullable)
  - Scopes: `scopeVerified`, `scopeByCity(string $city)`, `scopeSearch(string $term)` (LIKE on both name columns)
  - Scope: `scopeVisibleTo(Builder $query, ?User $actor): Builder` — Admin: all; Contractor: verified + own `user_id`; Guest/others: verified only
  - Products stub relationship commented out until STAGE_08

- [ ] T004 Add `supplierProfile()` HasOne relationship to `User` model — `backend/app/Models/User.php` _(MODIFY)_
  - Add `public function supplierProfile(): HasOne` returning `HasOne<SupplierProfile>`
  - Import `App\Models\SupplierProfile` at top of file

- [ ] T005 Create `SupplierProfileFactory` with verification states — `backend/database/factories/SupplierProfileFactory.php`
  - `definition()`: pending supplier; generates owning user via `User::factory()->contractor()`
  - States: `verified()` → sets `verification_status = Verified`, `verified_at = now()`; `pending()` → explicit pending; `suspended()` → sets `verification_status = Suspended`
  - Depends on T003 and existing `UserFactory::contractor()` state

---

## Phase 1 — Backend Core

> P1 tasks are strictly sequential (each depends on the prior). Begin after T003.

- [ ] T006 Create `SupplierRepositoryInterface` — `backend/app/Repositories/Contracts/SupplierRepositoryInterface.php`
  - **Does NOT extend base `RepositoryInterface`** (PHPStan level 8: typed `SupplierProfile` returns would conflict)
  - 8 method signatures: `paginate(array $filters, int $perPage, ?User $actor = null): LengthAwarePaginator`, `findById(int $id): ?SupplierProfile`, `findByUserId(int $userId): ?SupplierProfile`, `create(array $data): SupplierProfile`, `update(SupplierProfile $supplier, array $data): SupplierProfile`, `delete(SupplierProfile $supplier): bool`, `updateVerificationStatus(SupplierProfile $supplier, SupplierVerificationStatus $status, ?int $verifiedBy): SupplierProfile`
  - All return types strictly typed; declare `strict_types=1`

- [ ] T007 Create `SupplierRepository` — `backend/app/Repositories/SupplierRepository.php`
  - Extends `BaseRepository`, implements `SupplierRepositoryInterface`
  - `paginate()`: applies `scopeVisibleTo($actor)` first, then `when()` builder for `city`, `district`, `search` filters; honours `verification_status` filter only for admin actors
  - `update()`: accepts `SupplierProfile $supplier` instance (overrides BaseRepository's `update(int $id)` — avoids double-fetch)
  - `updateVerificationStatus()`: explicitly sets `$supplier->verification_status`, `$supplier->verified_at`, `$supplier->verified_by`, then calls `$supplier->save()`
  - `delete()`: calls `$supplier->delete()` (soft delete via SoftDeletes)

- [ ] T008 Create `SupplierService` — `backend/app/Services/SupplierService.php`
  - Constructor: `private readonly SupplierRepositoryInterface $supplierRepository`
  - `create(array $data, User $actor)` — **3-branch logic:**
    1. Actor is Admin + `$data['user_id']` provided → verify target user has contractor role; use `$data['user_id']` as owner
    2. Actor is Admin + no `user_id` → throw `ApiException::make(ApiErrorCode::VALIDATION_ERROR, ...)`
    3. Actor is Contractor → always use `$actor->id`; ignore any `user_id` in data
    - Duplicate check via `findByUserId()` → throw `CONFLICT_ERROR`; creates with `pending` status
  - `show(int $id, ?User $actor)` — visibility check via `scopeVisibleTo`; throws `RESOURCE_NOT_FOUND` (404) for non-visible profiles (**ADR-009-01**: 404, not 403, to prevent existence enumeration)
  - `verify()` / `suspend()` — idempotent; all status transitions to verified/suspended are permitted; sets `verified_at` + `verified_by` on verify; `suspended → verified` direct transition allowed
  - `update()` — ownership check unless admin; delegates to repository
  - `listProducts()` — returns empty `LengthAwarePaginator([], 0, $perPage)` stub (**ADR-SUPPLIER-04**)
  - `aggregateRatings()` — **no-op stub** (empty method, returns void)
  - Business exceptions via `ApiException::make(ApiErrorCode::X, trans('suppliers.key'))`

- [ ] T009 Register `SupplierRepository` binding and route model binding in `AppServiceProvider` — `backend/app/Providers/AppServiceProvider.php` _(MODIFY)_
  - In `register()`: `$this->app->bind(SupplierRepositoryInterface::class, fn () => new SupplierRepository(new SupplierProfile))`
  - In `boot()`: `Route::model('supplier', SupplierProfile::class)`
  - Verify `use Illuminate\Support\Facades\Route` import exists; add if missing

---

## Phase 2 — HTTP Layer

> T010–T013 can run in parallel (all are simple Request classes with no internal dependencies).
> T014–T015 can run in parallel. T016 requires T010–T015. T017–T018 are sequential after T016.

- [ ] T010 [P] [US1,US2] Create `StoreSupplierRequest` — `backend/app/Http/Requests/Supplier/StoreSupplierRequest.php`
  - Create `app/Http/Requests/Supplier/` subdirectory
  - `authorize()` returns `true` (RBAC enforced via Policy in controller)
  - Required fields: `company_name_ar`, `company_name_en`, `commercial_reg`, `phone` (regex `/^05\d{8}$/`), `city`
  - Optional fields: `tax_number`, `district`, `address`, `description_ar`, `description_en`, `logo` (url), `website` (url)
  - `user_id`: nullable, integer, `exists:users,id` — admin-only create-on-behalf field

- [ ] T011 [P] [US7] Create `UpdateSupplierRequest` — `backend/app/Http/Requests/Supplier/UpdateSupplierRequest.php`
  - Same fields as `StoreSupplierRequest`, all wrapped in `sometimes`
  - `commercial_reg` uses `Rule::unique('supplier_profiles', 'commercial_reg')->ignore($this->route('supplier')?->id)`
  - `verification_status`, `user_id`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings` must **NOT** be in validation rules

- [ ] T012 [P] [US5] Create `VerifySupplierRequest` — `backend/app/Http/Requests/Supplier/VerifySupplierRequest.php`
  - No request body; `authorize()` returns `true`; `rules()` returns `[]`
  - **ADR-009-01 note:** Visibility enforcement is in service (`RESOURCE_NOT_FOUND`), not in this request

- [ ] T013 [P] [US6] Create `SuspendSupplierRequest` — `backend/app/Http/Requests/Supplier/SuspendSupplierRequest.php`
  - Mirrors `VerifySupplierRequest` exactly; no body; `authorize()` → `true`; `rules()` → `[]`

- [ ] T014 [P] Create `SupplierPolicy` — `backend/app/Policies/SupplierPolicy.php`
  - **Create `app/Policies/` directory** (does not exist yet)
  - Admin cases NOT handled inside policy methods — `Gate::before` bypasses for admin
  - `viewAny(?User $user): bool` → always `true` (public access)
  - `view(?User $user, SupplierProfile $supplier): bool` → `true` if `verified` OR (user exists AND own profile OR admin)
  - `create(User $user): bool` → `$user->hasEnumRole(UserRole::CONTRACTOR)`
  - `update(User $user, SupplierProfile $supplier): bool` → `$user->id === $supplier->user_id`
  - `verify(User $user): bool` → `false` (admin bypass via Gate::before)
  - `suspend(User $user): bool` → `false` (admin bypass via Gate::before)
  - `delete(User $user): bool` → `false` (admin bypass via Gate::before)

- [ ] T015 [P] Create `SupplierResource` — `backend/app/Http/Resources/SupplierResource.php`
  - Extends `BaseApiResource`
  - Returns all 20 fields per API contracts shape
  - `verification_status` cast: `$this->verification_status instanceof SupplierVerificationStatus ? $this->verification_status->value : $this->verification_status`
  - `verified_at`, `created_at`, `updated_at` serialized via `->toISOString()` (nullable-safe `?->`)

- [ ] T016 [US1,US2,US3,US4,US5,US6,US7,US8] Create `SupplierController` (7 actions + products stub) — `backend/app/Http/Controllers/Api/V1/SupplierController.php`
  - Extends `BaseApiController`; constructor injects `SupplierService`
  - `index()`: `authorize('viewAny', SupplierProfile::class)`; get `per_page` (clamp 1–100, default 15); call `service->list()`; return `paginated(SupplierResource::collection($paginator), $paginator)`
  - **`show()`: SKIP `authorize()` — service enforces visibility and throws `RESOURCE_NOT_FOUND` (ADR-009-01)**; return `success(new SupplierResource($supplier))`
  - `store()`: `authorize('create', SupplierProfile::class)`; call `service->create()`; return `success(new SupplierResource($result), 201)`
  - `update()`: `authorize('update', $supplier)`; call `service->update()`; return `success(new SupplierResource($result))`
  - `verify()`: `authorize('verify', SupplierProfile::class)`; call `service->verify()`; return `success(new SupplierResource($result))`
  - `suspend()`: type-hint `SuspendSupplierRequest $request`; `authorize('suspend', SupplierProfile::class)`; call `service->suspend()`; return `success(new SupplierResource($result))`
  - `products()`: `authorize('view', $supplier)`; call `service->listProducts()`; return `paginated()` (empty stub)

- [ ] T017 Create supplier route definitions — `backend/routes/api/v1/suppliers.php`
  - Public (no auth): `GET /` (index), `GET /{supplier}` (show), `GET /{supplier}/products` (products)
  - Authenticated (`auth:sanctum`): `POST /` (store), `PUT /{supplier}` (update), `PUT /{supplier}/verify` (verify), `PUT /{supplier}/suspend` (suspend)
  - Route names prefix: `api.v1.suppliers.*`
  - No `DELETE` route in this stage; admin soft-delete exposed via separate admin endpoint if required

- [ ] T018 Register supplier route file in main API router — `backend/routes/api.php` _(MODIFY)_
  - Add `require __DIR__.'/api/v1/suppliers.php';` inside the existing `v1` prefix group

---

## Phase 3 — Frontend

> T019–T021 must be sequential (types → composable → store).
> T022 and T023 [P] can run in parallel once T019 is done.
> T024 depends on T022/T023 being done. T025–T028 each depend on T021 + their required components.

- [ ] T019 Create Supplier TypeScript type definitions — `frontend/types/supplier.ts`
  - Interface `Supplier` with all 20 fields (matching API contract response shape)
  - Type `VerificationStatus = 'pending' | 'verified' | 'suspended'`
  - Interfaces: `SupplierListParams`, `CreateSupplierData`, `UpdateSupplierData`, `VerificationResult`, `PaginationMeta`, `PaginatedResponse<T>`
  - Export all types; no business logic

- [ ] T020 Create `useSupplier` API composable — `frontend/composables/useSupplier.ts`
  - Wraps `useApi()` composable for all HTTP calls
  - Exports: `listSuppliers(params?)`, `getSupplier(id)`, `createSupplier(data)`, `updateSupplier(id, data)`, `verifySupplier(id)`, `suspendSupplier(id)`, `getSupplierProducts(id, params?)`
  - All functions return typed `Promise<>` using types from T019
  - Depends on T019 and existing `useApi()` composable

- [ ] T021 Create `useSupplierStore` Pinia store — `frontend/stores/useSupplierStore.ts`
  - Composition API style: `defineStore('suppliers', () => { ... })`
  - State: `suppliers: ref<Supplier[]>([])`, `currentSupplier: ref<Supplier | null>(null)`, `meta: ref<PaginationMeta | null>(null)`, `isLoading: ref(false)`, `error: ref<string | null>(null)`
  - Actions: `fetchSuppliers(params?)`, `fetchSupplier(id)`, `createSupplier(data)`, `updateSupplier(id, data)`, `verifySupplier(id)`, `suspendSupplier(id)` — each delegates to `useSupplier()` and updates local state
  - Getter: `verifiedSuppliers` computed filtered list

- [ ] T022 [P] [US3,US4] Create `SupplierCard.vue` component — `frontend/components/supplier/SupplierCard.vue`
  - Props: `supplier: Supplier`
  - Uses `UCard` (Nuxt UI) + `NuxtLink` for profile URL
  - Displays: logo, `company_name_ar` (primary), `company_name_en` (secondary), city, `VerificationStatusBadge`, `rating_avg` (if > 0)
  - RTL inherits from `<html dir="rtl">` (no inline `dir` needed)
  - `<script setup lang="ts">`

- [ ] T023 [P] [US3,US4,US5,US6,US8] Create `VerificationStatusBadge.vue` component — `frontend/components/supplier/VerificationStatusBadge.vue`
  - Props: `status: VerificationStatus`
  - Uses `UBadge` (Nuxt UI) with color map: `pending → warning/yellow`, `verified → success/green`, `suspended → error/red`
  - Uses i18n: `t('suppliers.status.pending')`, `t('suppliers.status.verified')`, `t('suppliers.status.suspended')`

- [ ] T024 [US1,US7] Create `SupplierForm.vue` component — `frontend/components/supplier/SupplierForm.vue`
  - Props: `initialData?: Partial<Supplier>`, `isEdit?: boolean`; Emits: `submit(data: CreateSupplierData | UpdateSupplierData)`
  - VeeValidate + Zod schema matching backend rules (required: `company_name_ar`, `company_name_en`, `commercial_reg`, `phone` regex `/^05\d{8}$/`, `city`)
  - Uses `UForm`, `UFormField`, `UInput`, `UTextarea`, `UButton` (Nuxt UI)
  - All labels use i18n keys from `suppliers.fields.*`

- [ ] T025 [US3] Create supplier directory (public) page — `frontend/pages/suppliers/index.vue`
  - Layout: `default`; access: public
  - Supplier directory grid using `SupplierCard.vue` components
  - Filter/search bar: `city`, `search` query params; only admin sees `verification_status` filter
  - `UPagination` component for navigation
  - Loading skeleton states and empty state message (Arabic + English via i18n)
  - Calls `useSupplierStore().fetchSuppliers()` on mount

- [ ] T026 [US4] Create supplier profile detail page — `frontend/pages/suppliers/[id].vue`
  - Layout: `default`; access: public
  - Displays full profile with all bilingual company fields
  - Ratings section: show `rating_avg` + `total_ratings` if `total_ratings > 0`
  - Products section: **stub — shows "coming soon" message** (per ADR-SUPPLIER-04)
  - On 404 / `RESOURCE_NOT_FOUND` → `navigateTo('/suppliers')` or show 404 page

- [ ] T027 [US1,US7] Create contractor supplier profile management page — `frontend/pages/dashboard/supplier/profile.vue`
  - Layout: `dashboard`; middleware: `auth` + contractor role guard
  - If no profile: shows `SupplierForm` registration form (`create` mode)
  - If profile exists: shows profile details + `SupplierForm` in edit mode
  - `VerificationStatusBadge` showing current `verification_status`
  - On submit: calls `useSupplierStore().createSupplier()` or `updateSupplier()`

- [ ] T028 [US2,US5,US6,US8] Create admin supplier management page — `frontend/pages/admin/suppliers/index.vue`
  - Layout: `admin`; middleware: `auth` + admin role guard
  - `UTable` showing all suppliers (all statuses visible to admin)
  - Filter bar: `verification_status` dropdown, `city` input, `search` input
  - Columns: company name (AR), city, `VerificationStatusBadge`, verify/suspend action buttons, link to edit
  - Inline Verify / Suspend actions with confirmation; calls store actions
  - `UPagination` for navigation

---

## Phase 4 — Tests + i18n

> T029 depends on T005. i18n tasks T030–T033 are all independent [P].
> Feature tests T034–T035 depend on T017 + T018 + T005 + T009 (routes + factory + bindings registered).
> Unit test T036 depends on T008 only (service is mocked at interface).

- [ ] T029 Create `SupplierSeeder` — `backend/database/seeders/SupplierSeeder.php`
  - Creates 5 verified + 3 pending + 2 suspended supplier profiles with contractor users
  - Uses `SupplierProfileFactory` states from T005
  - Register in `DatabaseSeeder` for dev/staging environments

- [ ] T030 [P] Create Arabic backend translation file — `backend/lang/ar/suppliers.php`
  - Keys: `profile_created`, `profile_updated`, `profile_verified`, `profile_suspended`, `not_found`, `already_exists`, `role_required`, `unauthorized_update`
  - Nested `validation` array with field-level messages (company_name_ar, company_name_en, commercial_reg, phone, city)

- [ ] T031 [P] Create English backend translation file — `backend/lang/en/suppliers.php`
  - Mirror of T030 with English values
  - Key/structure must be identical to `ar/suppliers.php`

- [ ] T032 [P] Add supplier locale keys to Arabic frontend locale — `frontend/locales/ar.json` _(MODIFY)_
  - Add `"suppliers"` top-level key with: `title`, `directory`, `profile`, `register`, `edit`, nested `status` (pending/verified/suspended), nested `fields` (company_name_ar, company_name_en, commercial_reg, city, phone, rating, description, logo, website), nested `admin` (manage, verify_action, suspend_action), `empty`, `products_coming_soon`

- [ ] T033 [P] Add supplier locale keys to English frontend locale — `frontend/locales/en.json` _(MODIFY)_
  - Mirror of T032 additions in English; key structure must be identical

- [ ] T034 [US1,US2,US3,US4,US7,US8] Create `SupplierControllerTest` feature test suite — `backend/tests/Feature/Api/V1/SupplierControllerTest.php`
  - Create `tests/Feature/Api/V1/` directory
  - 20 test cases covering full RBAC matrix (see plan §P4.3):
    - Index visibility: guest sees only verified; admin sees all; contractor sees own pending
    - Show: 200 for verified (guest); 404 for pending (guest); 200 for owner (contractor); 200 for admin (suspended)
    - Store: 401 guest; 403 customer; 201 contractor; 409 duplicate; 201 admin on-behalf
    - Update: 200 owner; 403 other contractor; 200 admin
    - Verify: 403 contractor; 200 admin
    - Suspend: 403 contractor; 200 admin
    - Products: 200 with empty `data: []`

- [ ] T035 [US5,US6] Create `SupplierVerificationWorkflowTest` feature test suite — `backend/tests/Feature/Api/V1/SupplierVerificationWorkflowTest.php`
  - 6 test cases: `pending → verified`, `verified → suspended`, `suspended → verified` (direct, ADR-009-01), `pending → suspended`, verify idempotent, suspend idempotent
  - All transitions invoked as admin actor

- [ ] T036 [US1,US2,US5,US6,US7,US8] Create `SupplierServiceTest` unit test suite — `backend/tests/Unit/Services/SupplierServiceTest.php`
  - Create `tests/Unit/Services/` directory
  - Uses `Mockery::mock(SupplierRepositoryInterface::class)` — no DB required
  - 11 test cases (see plan §P4.5): list visibility (null actor = verified only); list all for admin; create succeeds for contractor; create fails on duplicate → CONFLICT_ERROR; create fails for non-contractor → RBAC_ROLE_DENIED; update succeeds for owner; update fails for non-owner → AUTH_UNAUTHORIZED; verify sets verified_at; suspend sets status; suspend idempotent; aggregateRatings no-op

---

## Validation Pipeline

> Run after all Phase 4 tasks are done. Fix any issues before marking stage complete.

- [ ] T037 Run backend lint and static analysis — `backend/`
  - `cd backend && composer run lint` (PHP_CodeSniffer PSR-12)
  - `cd backend && ./vendor/bin/phpstan analyse` (level 8)
  - All `SupplierProfile`, `SupplierService`, `SupplierRepository`, `SupplierController` classes must pass with zero errors

- [ ] T038 Run backend PHPUnit tests scoped to Supplier — `backend/`
  - `cd backend && php artisan test --filter=Supplier`
  - All 37+ test assertions (controller + workflow + unit) must pass
  - Migration must run cleanly on SQLite in-memory (CI environment)

- [ ] T039 Run frontend lint, TypeScript typecheck, and Vitest tests — `frontend/`
  - `cd frontend && npm run lint`
  - `cd frontend && npm run typecheck`
  - `cd frontend && npm run test` — covers supplier composable, store, and component tests
  - Zero TypeScript errors in `types/supplier.ts`, `composables/useSupplier.ts`, `stores/useSupplierStore.ts`, and all `components/supplier/` files

---

## Dependency Graph

```
T001 → T002 → T003 ──→ T004
                │  └──→ T005
                ↓
               T006 → T007 → T008 → T009
                                      │
              ┌────────────────────────┤
              ↓                        ↓
        T010 T011 T012 T013    T009 (routes ready)
         └───────┬──────────→ T014 T015
                 └─────────────────────→ T016 → T017 → T018
                                                          │
              ┌───────────────────────────────────────────┘
              ↓
        T019 → T020 → T021
        T023[P] T022[P] T024
                          ↓
                T025 T026 T027 T028
                          │
              ┌───────────┘
              ↓
        T029[P] T030[P] T031[P] T032[P] T033[P]
        T034    T035     T036
                          │
                    T037 → T038 → T039
```

---

## Task Summary

| Phase               | Tasks     | Count  |
| ------------------- | --------- | ------ |
| P0 — Database       | T001–T005 | 5      |
| P1 — Backend Core   | T006–T009 | 4      |
| P2 — HTTP Layer     | T010–T018 | 9      |
| P3 — Frontend       | T019–T028 | 10     |
| P4 — Tests + i18n   | T029–T036 | 8      |
| Validation Pipeline | T037–T039 | 3      |
| **TOTAL**           |           | **39** |

## Testing

- [ ] T016 [P] [Unit tests: tests/Unit/Services/XxxServiceTest.php]
- [ ] T017 [P] [Feature tests: tests/Feature/Api/XxxControllerTest.php]
- [ ] T018 [P] [Frontend tests: tests/components/XxxCard.test.ts]

## Documentation & Cleanup

- [ ] T019 [Update API documentation]
- [ ] T020 [Update README if needed]
