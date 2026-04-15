# API Contracts — 009-suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Stage:** STAGE_09
> **Generated:** 2026-04-15
> **API Base:** `/api/v1/`

---

## General Notes

- All requests must include `Accept: application/json`
- Authenticated requests must include `Authorization: Bearer {token}`
- All responses follow the unified error contract: `{ success, data, error }`
- Paginated responses include `meta: { current_page, per_page, total, last_page }`
- **Route model binding:** `{supplier}` in URL resolves to `App\Models\SupplierProfile` (registered in `AppServiceProvider::boot()`)
- `{supplier}` resolves by the `id` column of `supplier_profiles` table (default key)

---

## Shared Shapes

### SupplierResource (Full)

Used in: `GET /suppliers/{id}`, `POST /suppliers` (201), `PUT /suppliers/{id}` (200)

```json
{
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
  "description_ar": "نحن شركة متخصصة في مواد البناء",
  "description_en": "We specialize in building materials",
  "logo": "https://cdn.example.com/logos/1.png",
  "website": "https://advancedconstruction.sa",
  "created_at": "2026-04-15T00:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

### SupplierResource (List Item)

Used in: `GET /suppliers` data array. Contains a subset of fields (no descriptions, no verified_by).

```json
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
```

> **Implementation note:** Use a single `SupplierResource` for both list and detail. The `toArray()` method returns all fields. The slightly smaller list payload is acceptable; the spec calls for a full `SupplierResource` in all cases.

---

## Endpoint Contracts

### 1. GET /api/v1/suppliers

**Purpose:** Browse the supplier directory (public).

**Auth:** Optional (unauthenticated allowed)

**Query Parameters:**

| Param                 | Type    | Default | Constraints                      | Notes                                         |
| --------------------- | ------- | ------- | -------------------------------- | --------------------------------------------- |
| `page`                | integer | 1       | min: 1                           | Pagination                                    |
| `per_page`            | integer | 15      | min: 1, max: 100                 | Items per page                                |
| `city`                | string  | —       | max: 100                         | Filter by city                                |
| `district`            | string  | —       | max: 100                         | Filter by district                            |
| `search`              | string  | —       | max: 100                         | Search in company_name_ar and company_name_en |
| `verification_status` | string  | —       | enum: pending/verified/suspended | **Admin only** — ignored for all other roles  |

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

**RBAC Visibility Rules (enforced in SupplierService::list()):**

- Unauthenticated / Customer / Architect / Field Engineer: only `verified` suppliers returned
- Contractor: sees `verified` + their own profile (any status)
- Admin: all statuses; `verification_status` filter param respected

---

### 2. GET /api/v1/suppliers/{supplier}

**Purpose:** View a single supplier's full profile.

**Auth:** Optional

**Path Parameters:** `{supplier}` — SupplierProfile ID (route model binding → `SupplierProfile`)

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
    "description_ar": "نحن شركة متخصصة في مواد البناء",
    "description_en": "We specialize in building materials",
    "logo": "https://cdn.example.com/logos/1.png",
    "website": "https://advancedconstruction.sa",
    "created_at": "2026-04-15T00:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  },
  "error": null
}
```

**Error 404 — Supplier not found or visibility denied (pending/suspended for non-owner/non-admin):**

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

> **Security note:** Non-verified suppliers return 404 (not 403) to non-owner/non-admin actors to avoid information leakage about the existence of pending/suspended profiles. Enforced in `SupplierService::show()`.

---

### 3. POST /api/v1/suppliers

**Purpose:** Contractor self-registers a supplier profile. Admin can also create profiles on behalf of contractors.

**Auth:** Required (`auth:sanctum`)

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
  "description_ar": "وصف الشركة بالعربية",
  "description_en": "Company description in English",
  "logo": "https://cdn.example.com/logos/1.png",
  "website": "https://example.sa",
  "user_id": 42
}
```

> **(Admin only: `user_id` is the ID of the contractor to create the profile for. Required when the authenticated actor is Admin; ignored for Contractor actor.)**

**Validation Rules:**

| Field             | Rules                                                                                                                                                          |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `company_name_ar` | required, string, max:255                                                                                                                                      |
| `company_name_en` | required, string, max:255                                                                                                                                      |
| `commercial_reg`  | required, string, max:100, unique:supplier_profiles,commercial_reg                                                                                             |
| `phone`           | required, string, regex:/^05\d{8}$/                                                                                                                            |
| `city`            | required, string, max:100                                                                                                                                      |
| `tax_number`      | nullable, string, max:50                                                                                                                                       |
| `district`        | nullable, string, max:100                                                                                                                                      |
| `address`         | nullable, string, max:500                                                                                                                                      |
| `description_ar`  | nullable, string, max:2000                                                                                                                                     |
| `description_en`  | nullable, string, max:2000                                                                                                                                     |
| `logo`            | nullable, string, url, max:500                                                                                                                                 |
| `website`         | nullable, url, max:255                                                                                                                                         |
| `user_id`         | nullable, integer, exists:users,id — **Admin-only:** required when actor is Admin (target contractor ID); if Admin actor and not provided → `VALIDATION_ERROR` |

**Success Response 201:** full `SupplierResource` wrapped in success envelope

```json
{
  "success": true,
  "data": { ...SupplierResource (full) },
  "error": null
}
```

**Error 403 — Wrong role (not contractor, not admin):**

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

**Error 409 — Contractor already has a profile:**

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

**Error 422 — Validation failure:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "البيانات المُدخلة غير صالحة",
    "details": {
      "commercial_reg": ["رقم السجل التجاري مسجّل مسبقاً"],
      "phone": ["رقم الهاتف يجب أن يبدأ بـ 05 ويتكون من 10 أرقام"]
    }
  }
}
```

**Error 422 — Admin did not provide `user_id`:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "البيانات المُدخلة غير صالحة",
    "details": {
      "user_id": ["يجب تحديد المقاول المستهدف عند إنشاء ملف من قِبَل الإدارة"]
    }
  }
}
```

---

### 4. PUT /api/v1/suppliers/{supplier}

**Purpose:** Contractor updates their own profile. Admin updates any profile.

**Auth:** Required (`auth:sanctum`)

**Path Parameters:** `{supplier}` — SupplierProfile ID

**Request Body:** Same fields as POST, all `sometimes` (partial update allowed).

```json
{
  "city": "جدة",
  "website": "https://new-website.sa"
}
```

**Note:** `verification_status`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings`, `user_id` are **not accepted** in this payload — they are silently ignored if provided (excluded from `$fillable` and not in validation rules).

**Success Response 200:** full updated `SupplierResource`

```json
{
  "success": true,
  "data": { ...SupplierResource (full, updated) },
  "error": null
}
```

**Error 403 — Not own profile (and not admin):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_UNAUTHORIZED",
    "message": "ليس لديك صلاحية تعديل هذا الملف",
    "details": null
  }
}
```

**Error 404 — Supplier not found:**

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

---

### 5. PUT /api/v1/suppliers/{supplier}/verify

**Purpose:** Admin transitions supplier status to `verified`.

**Auth:** Required (`auth:sanctum`), Admin only

**Path Parameters:** `{supplier}` — SupplierProfile ID

**Request Body:** Empty (`{}` or no body)

**Allowed Transitions:**

- `pending → verified` ✓
- `suspended → verified` ✓
- `verified → verified`: idempotent — returns 200 with current state, no error

**Forbidden Transitions:**

- None (all → verified is allowed, except suspended requires going through pending first per spec — actually spec says `suspended → verified` IS allowed directly; only `verified → pending` is disallowed)

**Success Response 200:** full `SupplierResource` wrapped in success envelope

```json
{
  "success": true,
  "data": { "...SupplierResource (full, with updated verification_status, verified_at, verified_by)" },
  "error": null
}
```

**Error 422 — Cannot verify suspended supplier (spec: suspended → verified IS allowed, so this only applies if a future business rule blocks it; for STAGE_09 this should NOT throw — verify from suspended is permitted):**

> Per spec §6.1 and §5.5: `suspended → verified` IS allowed. The 422 in the contract spec example was for a different scenario. The service will NOT throw `WORKFLOW_INVALID_TRANSITION` for `suspended → verified`.
>
> The only scenario for 422 is if a future rule disallows a transition. For now, all `→ verified` transitions are permitted.

**Error 403 — Not admin:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RBAC_ROLE_DENIED",
    "message": "لا تمتلك صلاحية إجراء هذه العملية",
    "details": null
  }
}
```

---

### 6. PUT /api/v1/suppliers/{supplier}/suspend

**Purpose:** Admin transitions supplier status to `suspended`.

**Auth:** Required (`auth:sanctum`), Admin only

**Path Parameters:** `{supplier}` — SupplierProfile ID

**Request Body:** Empty

**Allowed Transitions:**

- `pending → suspended` ✓
- `verified → suspended` ✓
- `suspended → suspended`: idempotent — returns 200

**Success Response 200:** full `SupplierResource` wrapped in success envelope

```json
{
  "success": true,
  "data": { "...SupplierResource (full, with updated verification_status)" },
  "error": null
}
```

**Error 403 — Not admin:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RBAC_ROLE_DENIED",
    "message": "لا تمتلك صلاحية إجراء هذه العملية",
    "details": null
  }
}
```

---

### 7. GET /api/v1/suppliers/{supplier}/products

**Purpose:** Browse paginated products listing for a specific supplier.

**Auth:** Optional

**Path Parameters:** `{supplier}` — SupplierProfile ID

**Query Parameters:** `page` (default: 1), `per_page` (default: 15, max: 100)

**Visibility Rules:**

- Unauthenticated / non-admin: only products of `verified` suppliers accessible; pending/suspended returns 404
- Admin: products accessible for all statuses

**Success Response 200 (stub — no Product model in STAGE_09):**

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

**Error 404 — Supplier not found or not accessible:**

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

---

## Error Matrix

| Scenario                                                               | Code                 | HTTP | Message (AR)                                   |
| ---------------------------------------------------------------------- | -------------------- | ---- | ---------------------------------------------- |
| Supplier not found (or not visible to actor)                           | `RESOURCE_NOT_FOUND` | 404  | المورّد غير موجود                              |
| Duplicate profile for same contractor                                  | `CONFLICT_ERROR`     | 409  | يمتلك هذا المقاول ملف شركة مسجّل مسبقاً        |
| Wrong role (not contractor / not admin)                                | `RBAC_ROLE_DENIED`   | 403  | يجب أن تكون مقاولاً لإنشاء ملف مورّد           |
| Not own profile (update by other contractor)                           | `AUTH_UNAUTHORIZED`  | 403  | ليس لديك صلاحية تعديل هذا الملف                |
| Non-admin tries verify/suspend                                         | `RBAC_ROLE_DENIED`   | 403  | لا تمتلك صلاحية إجراء هذه العملية              |
| Validation failure                                                     | `VALIDATION_ERROR`   | 422  | البيانات المُدخلة غير صالحة                    |
| Admin missing `user_id` when creating supplier on behalf of contractor | `VALIDATION_ERROR`   | 422  | البيانات المُدخلة غير صالحة (details: user_id) |
| Unauthenticated on auth-required endpoint                              | `AUTH_TOKEN_EXPIRED` | 401  | (Laravel Sanctum default)                      |
