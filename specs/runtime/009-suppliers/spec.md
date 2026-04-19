# Suppliers — STAGE_09

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Stage File:** `specs/phases/02_CATALOG_AND_INVENTORY/STAGE_09_SUPPLIERS.md`
> **Branch:** `spec/009-suppliers`
> **Created:** 2026-04-15T00:00:00Z

---

## 1. Overview

### Feature Summary

Implement a full supplier management subsystem within the Bunyan marketplace. A supplier is a
User with the `contractor` role who additionally holds a `SupplierProfile` record. Suppliers can
list and sell building-material products to customers; admins control verification status; and the
public can browse the supplier directory and individual supplier product catalogs.

### User Roles Impacted

| Role                     | Impact                                                        |
| ------------------------ | ------------------------------------------------------------- |
| Admin (الإدارة)          | Create suppliers, verify/suspend profiles, manage all entries |
| Contractor (المقاول)     | Self-register supplier profile, update own profile            |
| Customer (العميل)        | Browse supplier directory, view products per supplier         |
| Supervising Architect    | Read-only access to supplier directory                        |
| Field Engineer           | Read-only access to supplier directory                        |
| Public (unauthenticated) | Browse supplier directory, view profiles                      |

### Objectives

1. Allow contractors to register a supplier profile (company info, commercial registration, etc.)
2. Provide an admin verification workflow: `pending → verified → suspended`
3. Expose a public supplier directory and per-supplier product catalog
4. Aggregate ratings on supplier profiles (rating data written by the reviews stage)
5. Enable admin to manage all supplier profiles

---

## 2. User Stories

### US1 — Browse Supplier Directory (Public)

**As a** visitor or authenticated user, **I want** to browse the list of verified suppliers with
filtering and search, **so that** I can find suppliers relevant to my needs.

**Acceptance Criteria:**

- [ ] `GET /api/v1/suppliers` returns a paginated list of supplier profiles
- [ ] Response includes: company names (AR/EN), city, verification_status, rating_avg, logo
- [ ] By default only `verified` suppliers are returned to unauthenticated users
- [ ] Admins can filter by any `verification_status`
- [ ] Filter params: `city`, `district`, `search` (searches company_name_ar/en), `verification_status` (admin only)
- [ ] Pagination uses `per_page` (default 15, max 100) and `page` query params
- [ ] Response follows error contract: `{ success, data, error }`

### US2 — View Supplier Profile (Public)

**As a** visitor, **I want** to view a single supplier's profile and company details, **so that**
I can evaluate them before purchasing.

**Acceptance Criteria:**

- [ ] `GET /api/v1/suppliers/{id}` returns full supplier profile
- [ ] Includes: all company fields, verification_status, rating_avg, total_ratings, website, description (AR/EN)
- [ ] Returns `RESOURCE_NOT_FOUND` for non-existent (or soft-deleted) suppliers
- [ ] Unverified suppliers visible only to Admin and the owning Contractor

### US3 — Self-Register as Supplier (Contractor)

**As a** user with the Contractor role, **I want** to create my supplier profile, **so that** I can
list products on the marketplace.

**Acceptance Criteria:**

- [ ] `POST /api/v1/suppliers` creates a new `SupplierProfile` for the authenticated user
- [ ] User must have `contractor` role; other roles receive `RBAC_ROLE_DENIED` (403)
- [ ] A contractor cannot create a second profile; duplicate attempt returns `CONFLICT_ERROR` (409)
- [ ] Required fields: company_name_ar, company_name_en, commercial_reg, phone, city
- [ ] Optional fields: tax_number, district, address, description_ar, description_en, logo, website
- [ ] Profile is created with `verification_status = pending`
- [ ] Admin can also create profiles on behalf of contractors

### US4 — Update Supplier Profile (Contractor / Admin)

**As a** contractor or admin, **I want** to update a supplier profile, **so that** the information
stays current.

**Acceptance Criteria:**

- [ ] `PUT /api/v1/suppliers/{id}` updates the profile
- [ ] Contractor can only update their own profile (`AUTH_UNAUTHORIZED` otherwise)
- [ ] Admin can update any profile
- [ ] `verification_status` may NOT be changed via this endpoint (use dedicated verify/suspend endpoints)
- [ ] Returns updated resource in response
- [ ] Validation errors follow `VALIDATION_ERROR` contract

### US5 — Verify Supplier (Admin)

**As an** admin, **I want** to mark a supplier as verified, **so that** they become publicly visible
and can sell products.

**Acceptance Criteria:**

- [ ] `PUT /api/v1/suppliers/{id}/verify` transitions status to `verified`
- [ ] Only Admin may call this endpoint; others receive `RBAC_ROLE_DENIED` (403)
- [ ] Sets `verified_at` to current timestamp and `verified_by` to admin user id
- [ ] If already `verified`, the operation is idempotent (returns 200 with current state)
- [ ] Admin can transition `suspended → verified` directly (re-verification without a separate un-suspend step)

### US6 — Suspend Supplier (Admin)

**As an** admin, **I want** to suspend a supplier, **so that** their profile and products are
hidden from the public.

**Acceptance Criteria:**

- [ ] `PUT /api/v1/suppliers/{id}/suspend` transitions status to `suspended`
- [ ] Only Admin may call this endpoint; others receive `RBAC_ROLE_DENIED` (403)
- [ ] Suspended suppliers are hidden from public listing and product catalog
- [ ] If already `suspended`, the operation is idempotent

### US7 — View Supplier's Products (Public)

**As a** visitor, **I want** to see all products listed by a specific supplier, **so that** I can
browse their catalog.

**Acceptance Criteria:**

- [ ] `GET /api/v1/suppliers/{id}/products` returns paginated products for that supplier
- [ ] Only products of `verified` suppliers are returned to unauthenticated users
- [ ] Admin sees products for all verification statuses
- [ ] Returns `RESOURCE_NOT_FOUND` if supplier does not exist
- [ ] Product data shape deferred to STAGE_08_PRODUCTS; this endpoint returns an empty array until products exist

### US8 — Manage Suppliers in Admin Panel (Admin)

**As an** admin, **I want** a dedicated admin UI page to list, filter, verify, and suspend
suppliers, **so that** I can govern the supplier base efficiently.

**Acceptance Criteria:**

- [ ] Admin management page at `/admin/suppliers`
- [ ] Table shows all suppliers with status badges
- [ ] Admin can filter by verification_status, city, search term
- [ ] Verify / Suspend actions available inline
- [ ] Navigates to individual supplier detail/edit

---

## 3. Technical Requirements

### 3.1 Backend

#### SupplierProfile Model

- Eloquent model: `app/Models/SupplierProfile.php`
- Table: `supplier_profiles`
- Uses `SoftDeletes`
- Relationships:
  - `belongsTo(User::class, 'user_id')` → owning contractor
  - `belongsTo(User::class, 'verified_by')` → verifying admin (nullable)
  - `hasMany(Product::class)` ← defined in product stage; stub relationship now
- Scopes:
  - `scopeVerified` — filters `verification_status = verified`
  - `scopeByCity(string $city)`
  - `scopeSearch(string $term)` — LIKE on company_name_ar / company_name_en
- Casts: `verification_status` → `SupplierVerificationStatus` enum, `verified_at` → `datetime`, `rating_avg` → `decimal:2`
- `$fillable`: all profile fields except id, user_id, verified_at, verified_by, rating_avg, total_ratings

#### SupplierVerificationStatus Enum

```php
// app/Enums/SupplierVerificationStatus.php
enum SupplierVerificationStatus: string {
    case Pending   = 'pending';
    case Verified  = 'verified';
    case Suspended = 'suspended';
}
```

#### SupplierRepository

- Interface: `app/Repositories/Contracts/SupplierRepositoryInterface.php`
- Implementation: `app/Repositories/SupplierRepository.php`
- Methods:
  - `paginate(array $filters, int $perPage, ?User $actor = null): LengthAwarePaginator`
  - `findById(int $id): ?SupplierProfile`
  - `findByUserId(int $userId): ?SupplierProfile`
  - `create(array $data): SupplierProfile`
  - `update(SupplierProfile $supplier, array $data): SupplierProfile`
  - `delete(SupplierProfile $supplier): bool`
  - `updateVerificationStatus(SupplierProfile $supplier, SupplierVerificationStatus $status, ?int $verifiedBy): SupplierProfile`

#### SupplierService

- `app/Services/SupplierService.php`
- Constructor-injected: `SupplierRepositoryInterface`
- Methods:
  - `list(array $filters, ?User $actor): LengthAwarePaginator`
  - `show(int $id, ?User $actor): SupplierProfile`
  - `create(array $data, User $actor): SupplierProfile`
  - `update(int $id, array $data, User $actor): SupplierProfile`
  - `verify(int $id, User $admin): SupplierProfile`
  - `suspend(int $id, User $admin): SupplierProfile`
  - `listProducts(int $id, ?User $actor, int $perPage): LengthAwarePaginator`
  - `aggregateRatings(int $supplierId): void` (called by ratings stage)
- Business rules enforced inside service (not controller)

#### SupplierController

- `app/Http/Controllers/Api/V1/SupplierController.php`
- Extends `BaseController`
- Constructor-injected: `SupplierService`
- Thin: delegates to service, returns `SupplierResource`
- Methods map 1:1 with API endpoints

#### Form Requests

| Class                    | File                                                    |
| ------------------------ | ------------------------------------------------------- |
| `StoreSupplierRequest`   | `app/Http/Requests/Supplier/StoreSupplierRequest.php`   |
| `UpdateSupplierRequest`  | `app/Http/Requests/Supplier/UpdateSupplierRequest.php`  |
| `VerifySupplierRequest`  | `app/Http/Requests/Supplier/VerifySupplierRequest.php`  |
| `SuspendSupplierRequest` | `app/Http/Requests/Supplier/SuspendSupplierRequest.php` |

`StoreSupplierRequest` rules:

```php
'company_name_ar'  => ['required', 'string', 'max:255'],
'company_name_en'  => ['required', 'string', 'max:255'],
'commercial_reg'   => ['required', 'string', 'max:100', 'unique:supplier_profiles'],
'phone'            => ['required', 'string', 'regex:/^05\d{8}$/'],
'city'             => ['required', 'string', 'max:100'],
'tax_number'       => ['nullable', 'string', 'max:50'],
'district'         => ['nullable', 'string', 'max:100'],
'address'          => ['nullable', 'string', 'max:500'],
'description_ar'   => ['nullable', 'string', 'max:2000'],
'description_en'   => ['nullable', 'string', 'max:2000'],
'logo'             => ['nullable', 'string', 'url', 'max:500'],
'website'          => ['nullable', 'url', 'max:255'],
```

`UpdateSupplierRequest` — same rules, all fields `sometimes` (partial update).

`VerifySupplierRequest` — no request body; authorization done via Policy.

#### SupplierPolicy

- `app/Policies/SupplierPolicy.php`
- Methods:
  - `viewAny(?User $user): bool` — always true (public)
  - `view(?User $user, SupplierProfile $supplier): bool` — true if verified, or own profile, or admin
  - `create(User $user): bool` — must have contractor role; duplicate detection delegated to SupplierService::create() via CONFLICT_ERROR
  - `update(User $user, SupplierProfile $supplier): bool` — own profile or admin
  - `verify(User $user): bool` — admin only
  - `suspend(User $user): bool` — admin only
  - `delete(User $user): bool` — admin only

#### SupplierResource

- `app/Http/Resources/SupplierResource.php`
- JSON shape: see § 5 (API Contracts)

### 3.2 API Routes

File: `backend/routes/api/v1/suppliers.php`

```php
Route::prefix('suppliers')->name('api.v1.suppliers.')->group(function () {
    // Public routes
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

> **Route model binding:** `{supplier}` binds to `App\Models\SupplierProfile` (not `User`).
> Register in `app/Providers/AppServiceProvider.php` inside `boot()`: `Route::model('supplier', SupplierProfile::class);`

### 3.3 Database

Migration file: `database/migrations/YYYY_MM_DD_HHMMSS_create_supplier_profiles_table.php`

### 3.4 Frontend

Tech stack: Nuxt.js 3 (Vue 3 + TypeScript), Nuxt UI, Pinia, `@vueuse/core`, RTL Arabic-first.

Pages:

| Path                          | Component                              | Access     |
| ----------------------------- | -------------------------------------- | ---------- |
| `/suppliers`                  | `pages/suppliers/index.vue`            | Public     |
| `/suppliers/[id]`             | `pages/suppliers/[id].vue`             | Public     |
| `/dashboard/supplier/profile` | `pages/dashboard/supplier/profile.vue` | Contractor |
| `/admin/suppliers`            | `pages/admin/suppliers/index.vue`      | Admin      |

Key components:

- `components/supplier/SupplierCard.vue` — card for directory listing
- `components/supplier/VerificationStatusBadge.vue` — badge (pending/verified/suspended)
- `components/supplier/SupplierForm.vue` — shared form for create/edit (VeeValidate + Zod)

Stores:

- `stores/useSupplierStore.ts` — list, detail, CRUD actions, verification actions
- Uses `useApiClient` composable for all API calls

---

## 4. Data Model

### Table: `supplier_profiles`

| Column                | Type                                   | Constraints                    | Notes                                                                  |
| --------------------- | -------------------------------------- | ------------------------------ | ---------------------------------------------------------------------- |
| `id`                  | BIGINT UNSIGNED                        | PK, AUTO_INCREMENT             |                                                                        |
| `user_id`             | BIGINT UNSIGNED                        | NOT NULL, FK→users(id), UNIQUE | One profile per contractor                                             |
| `company_name_ar`     | VARCHAR(255)                           | NOT NULL                       | Arabic company name                                                    |
| `company_name_en`     | VARCHAR(255)                           | NOT NULL                       | English company name                                                   |
| `commercial_reg`      | VARCHAR(100)                           | NOT NULL, UNIQUE               | Saudi commercial reg number                                            |
| `tax_number`          | VARCHAR(50)                            | NULLABLE                       | VAT registration number                                                |
| `city`                | VARCHAR(100)                           | NOT NULL                       | City of operation                                                      |
| `district`            | VARCHAR(100)                           | NULLABLE                       | District / neighborhood                                                |
| `address`             | VARCHAR(500)                           | NULLABLE                       | Full address                                                           |
| `phone`               | VARCHAR(20)                            | NOT NULL                       | Business phone (Saudi format)                                          |
| `verification_status` | ENUM('pending','verified','suspended') | NOT NULL, DEFAULT 'pending'    |                                                                        |
| `verified_at`         | TIMESTAMP                              | NULLABLE                       | Time of last verification                                              |
| `verified_by`         | BIGINT UNSIGNED                        | NULLABLE, FK→users(id)         | Admin who verified                                                     |
| `rating_avg`          | DECIMAL(8,2)                           | NOT NULL, DEFAULT 0.00         | Calculated rating average                                              |
| `total_ratings`       | INT UNSIGNED                           | NOT NULL, DEFAULT 0            | Count of ratings received                                              |
| `description_ar`      | TEXT                                   | NULLABLE                       | Arabic company description                                             |
| `description_en`      | TEXT                                   | NULLABLE                       | English company description                                            |
| `logo`                | VARCHAR(500)                           | NULLABLE                       | Logo URL string only; file upload deferred to STAGE_15_FILE_MANAGEMENT |
| `website`             | VARCHAR(255)                           | NULLABLE                       | Company website URL                                                    |
| `created_at`          | TIMESTAMP                              | NULLABLE                       |                                                                        |
| `updated_at`          | TIMESTAMP                              | NULLABLE                       |                                                                        |
| `deleted_at`          | TIMESTAMP                              | NULLABLE                       | Soft delete                                                            |

**Indexes:**

- `user_id` (unique)
- `commercial_reg` (unique)
- `verification_status`
- `city`

**Relationships:**

- `supplier_profiles.user_id` → `users.id` (owning contractor)
- `supplier_profiles.verified_by` → `users.id` (verifying admin, nullable)
- `products.supplier_profile_id` → `supplier_profiles.id` (STAGE_08)

---

## 5. API Contracts

All requests use `Accept: application/json` and `Content-Type: application/json`.
Sanctum token passed as `Authorization: Bearer {token}` where required.

### 5.1 GET /api/v1/suppliers

**Query Parameters:**

| Param                 | Type   | Description                            |
| --------------------- | ------ | -------------------------------------- |
| `page`                | int    | Page number (default: 1)               |
| `per_page`            | int    | Items per page (default: 15, max: 100) |
| `city`                | string | Filter by city                         |
| `district`            | string | Filter by district                     |
| `search`              | string | Search company name (AR or EN)         |
| `verification_status` | string | Admin only: filter by status           |

**Success Response 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 42,
      "company_name_ar": "شركة البناء المتقدم",
      "company_name_en": "Advanced Construction Co.",
      "city": "الرياض",
      "district": "العليا",
      "phone": "0512345678",
      "verification_status": "verified",
      "rating_avg": "4.50",
      "total_ratings": 28,
      "logo": "https://cdn.example.com/logos/1.png",
      "created_at": "2026-04-15T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8
  },
  "error": null
}
```

### 5.2 GET /api/v1/suppliers/{id}

**Success Response 200:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 42,
    "company_name_ar": "شركة البناء المتقدم",
    "company_name_en": "Advanced Construction Co.",
    "commercial_reg": "1010123456",
    "tax_number": "300000000000003",
    "city": "الرياض",
    "district": "العليا",
    "address": "طريق الملك فهد",
    "phone": "0512345678",
    "verification_status": "verified",
    "verified_at": "2026-04-15T10:00:00Z",
    "verified_by": 1,
    "rating_avg": "4.50",
    "total_ratings": 28,
    "description_ar": "نحن شركة ...",
    "description_en": "We are a company ...",
    "logo": "https://cdn.example.com/logos/1.png",
    "website": "https://advancedconstruction.sa",
    "created_at": "2026-04-15T00:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  },
  "error": null
}
```

**Error 404:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "المورّد غير موجود",
    "details": null
  }
}
```

### 5.3 POST /api/v1/suppliers

**Request Body:**

```json
{
  "company_name_ar": "شركة البناء المتقدم",
  "company_name_en": "Advanced Construction Co.",
  "commercial_reg": "1010123456",
  "tax_number": "300000000000003",
  "phone": "0512345678",
  "city": "الرياض",
  "district": "العليا",
  "address": "طريق الملك فهد",
  "description_ar": "وصف الشركة",
  "description_en": "Company description",
  "logo": "https://cdn.example.com/logos/1.png",
  "website": "https://example.sa"
}
```

**Success Response 201:** full `SupplierResource`

**Error 409 (duplicate):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "CONFLICT_ERROR",
    "message": "يمتلك هذا المقاول ملف شركة مسجّل مسبقاً",
    "details": null
  }
}
```

**Error 403 (wrong role):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RBAC_ROLE_DENIED",
    "message": "يجب أن تكون مقاولاً لإنشاء ملف مورّد",
    "details": null
  }
}
```

### 5.4 PUT /api/v1/suppliers/{id}

**Request Body:** same as POST, all fields optional (partial update).

**Success Response 200:** full `SupplierResource`

### 5.5 PUT /api/v1/suppliers/{id}/verify

**Request Body:** empty

**Success Response 200:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "verification_status": "verified",
    "verified_at": "2026-04-15T10:00:00Z",
    "verified_by": 1
  },
  "error": null
}
```

### 5.6 PUT /api/v1/suppliers/{id}/suspend

**Request Body:** empty

**Success Response 200:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "verification_status": "suspended"
  },
  "error": null
}
```

### 5.7 GET /api/v1/suppliers/{id}/products

**Query Parameters:** `page`, `per_page`

**Success Response 200:**

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 0,
    "last_page": 1
  },
  "error": null
}
```

> Note: Product shape is defined in STAGE_08_PRODUCTS. This endpoint returns an empty collection until products exist.

---

## 6. Business Rules

### 6.1 Verification Workflow

```
pending ──► verified ──► suspended
pending ◄────────────── suspended
                verified ◄── suspended  (re-verify after suspension)
```

| Transition | From      | To        | Actor                     |
| ---------- | --------- | --------- | ------------------------- |
| Verify     | pending   | verified  | Admin                     |
| Suspend    | verified  | suspended | Admin                     |
| Re-verify  | suspended | verified  | Admin                     |
| Re-submit  | suspended | pending   | Contractor (future stage) |

- Direct `suspended → verified` is allowed (admin judgment)
- Direct `verified → pending` is NOT allowed
- `pending → suspended` IS allowed (admin may reject without verifying first)

### 6.2 RBAC Rules

- Unauthenticated requests can only see `verified` suppliers
- Contractor can only create one profile (UNIQUE constraint on `user_id`)
- Contractor can only view/update their own profile; a contractor cannot view another contractor's unverified or suspended profile
- Only Admin and the owning Contractor can see non-verified (pending/suspended) profiles; all other actors receive `RESOURCE_NOT_FOUND`
- Admin can create, read, update, verify, suspend, and delete any profile
- `commercial_reg` must be globally unique across all supplier profiles
- Soft-deleted profiles are **completely invisible to all actors including Admin**; `SoftDeletes` excludes them from all queries without exception — Admin uses `suspend` to hide a profile, not delete

### 6.3 Validation Rules (server-side)

| Field             | Rule                                                |
| ----------------- | --------------------------------------------------- |
| `company_name_ar` | required, string, max 255                           |
| `company_name_en` | required, string, max 255                           |
| `commercial_reg`  | required, string, max 100, unique:supplier_profiles |
| `phone`           | required, string, matches `/^05\d{8}$/`             |
| `city`            | required, string, max 100                           |
| `tax_number`      | nullable, string, max 50                            |
| `district`        | nullable, string, max 100                           |
| `address`         | nullable, string, max 500                           |
| `description_ar`  | nullable, string, max 2000                          |
| `description_en`  | nullable, string, max 2000                          |
| `logo`            | nullable, URL, max 500                              |
| `website`         | nullable, URL, max 255                              |

### 6.4 Rating Aggregation

- `rating_avg` and `total_ratings` are computed columns (not user-submitted)
- `SupplierService::aggregateRatings(int $supplierId): void` is a **stub-only** method in this stage — implemented as a no-op; no aggregation logic is written here
- Rating updates will be triggered **externally** by the future reviews/ratings stage; no rating writes occur in STAGE_09
- Never directly writeable via API

---

## 7. RBAC Matrix

| Endpoint                     | Public            | Customer          | Contractor (own) | Contractor (other) | Architect         | Field Eng         | Admin |
| ---------------------------- | ----------------- | ----------------- | ---------------- | ------------------ | ----------------- | ----------------- | ----- |
| GET /suppliers               | verified only     | verified only     | all statuses     | verified only      | verified only     | verified only     | all   |
| GET /suppliers/{id}          | verified only     | verified only     | own (any status) | verified only      | verified only     | verified only     | all   |
| POST /suppliers              | ✗                 | ✗                 | ✓ (no existing)  | ✗                  | ✗                 | ✗                 | ✓     |
| PUT /suppliers/{id}          | ✗                 | ✗                 | ✓ own            | ✗                  | ✗                 | ✗                 | ✓ any |
| PUT /suppliers/{id}/verify   | ✗                 | ✗                 | ✗                | ✗                  | ✗                 | ✗                 | ✓     |
| PUT /suppliers/{id}/suspend  | ✗                 | ✗                 | ✗                | ✗                  | ✗                 | ✗                 | ✓     |
| GET /suppliers/{id}/products | verified supplier | verified supplier | own              | verified supplier  | verified supplier | verified supplier | all   |

---

## 8. i18n Requirements

### Translation Keys (Arabic — `lang/ar/suppliers.php`)

```php
return [
    'profile_created'          => 'تم إنشاء ملف المورّد بنجاح',
    'profile_updated'          => 'تم تحديث ملف المورّد بنجاح',
    'profile_verified'         => 'تم التحقق من المورّد بنجاح',
    'profile_suspended'        => 'تم تعليق حساب المورّد',
    'not_found'                => 'المورّد غير موجود',
    'already_exists'           => 'يمتلك هذا المقاول ملف شركة مسجّل مسبقاً',
    'role_required'            => 'يجب أن تكون مقاولاً لإنشاء ملف مورّد',
    'unauthorized_update'      => 'ليس لديك صلاحية تعديل هذا الملف',
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

### Translation Keys (English — `lang/en/suppliers.php`)

```php
return [
    'profile_created'          => 'Supplier profile created successfully',
    'profile_updated'          => 'Supplier profile updated successfully',
    'profile_verified'         => 'Supplier verified successfully',
    'profile_suspended'        => 'Supplier account suspended',
    'not_found'                => 'Supplier not found',
    'already_exists'           => 'This contractor already has a registered supplier profile',
    'role_required'            => 'You must be a contractor to create a supplier profile',
    'unauthorized_update'      => 'You are not authorized to update this profile',
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

### Frontend i18n Keys (`frontend/locales/ar.json` / `en.json`)

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
      "rating": "التقييم"
    },
    "admin": {
      "manage": "إدارة الموردين",
      "verify_action": "توثيق",
      "suspend_action": "تعليق"
    }
  }
}
```

### RTL/Layout Rules

- All pages use `dir="rtl"` inherited from `<html>` element (Nuxt config)
- Company name displayed Arabic-first with English as secondary
- Nuxt UI components support RTL natively; no overrides needed

---

## 9. Testing Requirements

### 9.1 Unit Tests (PHPUnit)

**File:** `tests/Unit/Services/SupplierServiceTest.php`

| Test                                         | Description                                                  |
| -------------------------------------------- | ------------------------------------------------------------ |
| `test_list_returns_only_verified_for_public` | Unauthenticated actor receives only verified results         |
| `test_list_returns_all_for_admin`            | Admin actor receives all verification statuses               |
| `test_create_succeeds_for_contractor`        | Contractor can create a profile                              |
| `test_create_fails_if_duplicate_profile`     | Second create for same user throws ConflictException         |
| `test_create_fails_for_non_contractor`       | Non-contractor actor gets authorization exception            |
| `test_update_succeeds_for_owner`             | Contractor updates own profile                               |
| `test_update_fails_for_non_owner_contractor` | Contractor cannot update another's profile                   |
| `test_verify_transitions_to_verified`        | Status becomes verified, verified_at is set                  |
| `test_suspended_to_verified_is_allowed`      | Suspended→verify is allowed; service returns verified status |
| `test_suspend_transitions_to_suspended`      | Status becomes suspended                                     |
| `test_suspend_is_idempotent`                 | Calling suspend twice is safe                                |
| `test_aggregate_ratings_is_noop`             | aggregateRatings returns void with no repository calls       |

### 9.2 Feature Tests (RBAC Matrix)

**File:** `tests/Feature/Api/V1/SupplierControllerTest.php`

For each endpoint test the following actor × response combinations:

| Endpoint                        | Guest               | Customer            | Contractor (own)  | Contractor (other)  | Admin     |
| ------------------------------- | ------------------- | ------------------- | ----------------- | ------------------- | --------- |
| GET /suppliers                  | 200 (verified only) | 200 (verified only) | 200 (own visible) | 200 (verified only) | 200 (all) |
| GET /suppliers/{id} (verified)  | 200                 | 200                 | 200               | 200                 | 200       |
| GET /suppliers/{id} (pending)   | 404                 | 404                 | 200               | 404                 | 200       |
| GET /suppliers/{id} (suspended) | 404                 | 404                 | 200               | 404                 | 200       |
| POST /suppliers                 | 401                 | 403                 | 201               | —                   | 201       |
| POST /suppliers (duplicate)     | —                   | —                   | 409               | —                   | —         |
| PUT /suppliers/{id}             | 401                 | 403                 | 200               | 403                 | 200       |
| PUT /suppliers/{id}/verify      | 401                 | 403                 | 403               | 403                 | 200       |
| PUT /suppliers/{id}/suspend     | 401                 | 403                 | 403               | 403                 | 200       |
| GET /suppliers/{id}/products    | 200                 | 200                 | 200               | 200                 | 200       |

### 9.3 Workflow Transition Tests

**File:** `tests/Feature/Api/V1/SupplierVerificationWorkflowTest.php`

| Test                         | Initial State | Action  | Expected      |
| ---------------------------- | ------------- | ------- | ------------- |
| `test_pending_to_verified`   | pending       | verify  | 200 verified  |
| `test_verified_to_suspended` | verified      | suspend | 200 suspended |
| `test_suspended_to_verified` | suspended     | verify  | 200 verified  |
| `test_pending_to_suspended`  | pending       | suspend | 200 suspended |

| `test_verify_already_verified_idempotent` | verified | verify | 200 verified |
| `test_suspend_already_suspended_idempotent` | suspended | suspend | 200 suspended |

---

## 10. Out of Scope

The following are explicitly **not** included in this stage:

- **Product management** — products belong to STAGE_08; this stage only stubs the relationship
- **Review / Rating submission** — writing reviews and rating calculation belong to a ratings stage
- **File/logo upload** — logo is stored as a URL string; actual file upload is a separate file-handling stage
- **Supplier re-submit after suspension** — contractors requesting re-review is a future workflow
- **Payment/escrow linking** — financial operations belong to the transactions stage
- **Supplier analytics dashboard** — reporting and statistics belong to a reporting stage
- **Multi-branch suppliers** — single profile per contractor
- **Supplier notifications** — email/SMS on verification status changes (future notifications stage)
- **Public search beyond name/city/district** — full-text or geolocation search is a future enhancement

---

## 11. Dependencies

### Upstream (must be complete)

| Stage                    | Reason                                                                 |
| ------------------------ | ---------------------------------------------------------------------- |
| STAGE_04_RBAC_SYSTEM     | Role enum (`contractor`, `admin`) must exist; middleware must be wired |
| STAGE_06_API_FOUNDATION  | `BaseController`, `ApiErrorCode` enum, response helpers must exist     |
| STAGE_03_AUTHENTICATION  | `auth:sanctum` middleware; authenticated User model                    |
| STAGE_02_DATABASE_SCHEMA | Users table with `role` enum column must exist                         |

### Downstream (blocked until this stage completes)

| Stage                 | Reason                                                         |
| --------------------- | -------------------------------------------------------------- |
| STAGE_08_PRODUCTS     | Products reference `supplier_profiles.id` as FK                |
| STAGE_18_QUOTATIONS   | Quotations reference supplier profiles                         |
| Reviews/Ratings stage | Rating aggregation calls `SupplierService::aggregateRatings()` |

---

## 12. Clarifications

### Session 2026-04-15

All open questions resolved. No `[NEEDS CLARIFICATION]` markers remain.

1. **Logo field type** — `logo` is a URL string (`VARCHAR(500)`, `url` validation rule) in this stage. Platform file-upload integration is deferred to STAGE_15_FILE_MANAGEMENT or equivalent. Column note updated in §4.

2. **Admin notifications on new supplier submissions** — Deferred to the notifications stage. No notifications subsystem exists in STAGE_09. Documented in §10 Out of Scope.

3. **Can a Contractor view another contractor's unverified profile?** — **No.** Only Admin and the owning Contractor can see non-verified (pending/suspended) profiles. All other actors — including other Contractors — receive `RESOURCE_NOT_FOUND`. Explicit rule added to §6.2; already encoded in §7 RBAC Matrix.

4. **Phone format `^05\d{8}$`** — **Confirmed correct for Saudi Arabia.** Saudi mobile numbers are 10 digits: prefix `05` + 8 digits. `^05\d{8}$` matches this exactly. No change needed.

5. **`rating_avg` update mechanism** — `SupplierService::aggregateRatings()` is a **stub-only no-op** in STAGE_09. No rating logic is implemented. The future reviews/ratings stage will call this method to trigger aggregation. §6.4 updated accordingly.

6. **Pagination response shape** — The `data.items` + `data.pagination` draft shape **does not match** the codebase. The authoritative pattern (`BaseApiController::paginated()`) is: `data` as a top-level array + `meta` object at root with keys `current_page`, `per_page`, `total`, `last_page`. §5.1 and §5.7 updated to match. Non-standard `from`/`to` fields removed.

7. **Route model binding for `{supplier}`** — `{supplier}` binds to `App\Models\SupplierProfile` (not `User`). Must register `Route::model('supplier', SupplierProfile::class)` in `AppServiceProvider::boot()`. Note added to §3.2.

8. **Soft-deleted profiles visibility** — Soft-deleted profiles are **invisible to all actors including Admin**. Laravel's `SoftDeletes` trait enforces this globally. Admin uses `suspend` to hide active profiles. Hard delete is an admin-only emergency operation with permanent effect. §6.2 updated.
