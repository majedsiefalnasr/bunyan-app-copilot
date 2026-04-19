# API Contracts: Category System

**Stage**: STAGE_07_CATEGORIES
**Version**: v1
**Base Path**: `/api/v1/categories`
**Authentication**: Bearer Token (Sanctum)
**Authorization**: Admin-only for mutations (POST/PUT/DELETE)

---

## 1. List Categories (Tree Format)

### GET /api/v1/categories

**Description**: Retrieve full nested hierarchy of categories. Returns tree structure with recursive children arrays.

**Authentication**: Required (any authenticated user)
**Authorization**: Public read; any authenticated user can view

**Query Parameters**:

| Parameter         | Type    | Required | Default | Description                                                       |
| ----------------- | ------- | -------- | ------- | ----------------------------------------------------------------- |
| `parent_id`       | integer | No       | null    | Filter by parent ID (if provided, returns children + descendants) |
| `include_deleted` | boolean | No       | false   | Include soft-deleted categories (admin only)                      |
| `active_only`     | boolean | No       | true    | Filter by is_active = true                                        |

**Request**:

```http
GET /api/v1/categories HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "name_ar": "مواد بناء",
      "name_en": "Building Materials",
      "slug": "building-materials",
      "icon": "lucide-box",
      "sort_order": 1,
      "is_active": true,
      "version": 0,
      "created_at": "2026-04-15T10:00:00Z",
      "updated_at": "2026-04-15T10:00:00Z",
      "deleted_at": null,
      "children": [
        {
          "id": 2,
          "parent_id": 1,
          "name_ar": "أسمنت",
          "name_en": "Cement",
          "slug": "cement",
          "icon": "lucide-package",
          "sort_order": 0,
          "is_active": true,
          "version": 0,
          "created_at": "2026-04-15T10:00:01Z",
          "updated_at": "2026-04-15T10:00:01Z",
          "deleted_at": null,
          "children": []
        }
      ]
    }
  ],
  "error": null
}
```

**Error Responses**:

- `400 Bad Request`: Invalid query parameters
- `401 AUTH_TOKEN_EXPIRED`: Token expired or invalid
- `403 AUTH_UNAUTHORIZED`: User not authenticated

---

## 2. Get Single Category

### GET /api/v1/categories/{id}

**Description**: Retrieve a single category with nested children.

**Authentication**: Required
**Authorization**: Public read

**Path Parameters**:

- `id` (integer, required): Category ID

**Request**:

```http
GET /api/v1/categories/1 HTTP/1.1
Authorization: Bearer {token}
Accept: application/json
```

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "parent_id": null,
    "name_ar": "مواد بناء",
    "name_en": "Building Materials",
    "slug": "building-materials",
    "icon": "lucide-box",
    "sort_order": 1,
    "is_active": true,
    "version": 0,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z",
    "deleted_at": null,
    "children": [
      {
        "id": 2,
        "parent_id": 1,
        "name_ar": "أسمنت",
        "name_en": "Cement",
        "slug": "cement",
        "icon": "lucide-package",
        "sort_order": 0,
        "is_active": true,
        "version": 0,
        "children": []
      }
    ]
  },
  "error": null
}
```

**Error Responses**:

- `404 RESOURCE_NOT_FOUND`: Category not found
- `401 AUTH_TOKEN_EXPIRED`: Token expired

---

## 3. Create Category

### POST /api/v1/categories

**Description**: Create a new category (admin only).

**Authentication**: Required
**Authorization**: Admin role required

**Request Body**:

```json
{
  "name_ar": "مواد بناء",
  "name_en": "Building Materials",
  "parent_id": null,
  "icon": "lucide-box",
  "sort_order": 1,
  "is_active": true
}
```

**Request Parameters**:

| Field        | Type    | Required | Validations                  | Description                             |
| ------------ | ------- | -------- | ---------------------------- | --------------------------------------- |
| `name_ar`    | string  | Yes      | min:2, max:100               | Arabic category name                    |
| `name_en`    | string  | Yes      | min:2, max:100               | English category name                   |
| `parent_id`  | integer | No       | exists:categories,id OR null | Parent category ID (null for top-level) |
| `icon`       | string  | No       | max:50                       | CSS class name or icon identifier       |
| `sort_order` | integer | No       | >= 0                         | Display order (default: max + 1)        |
| `is_active`  | boolean | No       | default: true                | Visibility flag                         |

**Request**:

```http
POST /api/v1/categories HTTP/1.1
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "name_ar": "أسمنت",
  "name_en": "Cement",
  "parent_id": 1,
  "icon": "lucide-package",
  "sort_order": 0
}
```

**Success Response** (201 Created):

```json
{
  "success": true,
  "data": {
    "id": 2,
    "parent_id": 1,
    "name_ar": "أسمنت",
    "name_en": "Cement",
    "slug": "cement",
    "icon": "lucide-package",
    "sort_order": 0,
    "is_active": true,
    "version": 0,
    "created_at": "2026-04-15T10:00:01Z",
    "updated_at": "2026-04-15T10:00:01Z",
    "deleted_at": null,
    "children": []
  },
  "error": null
}
```

**Error Responses**:

| Error Code                    | Status | Description                                |
| ----------------------------- | ------ | ------------------------------------------ |
| `VALIDATION_ERROR`            | 422    | Input validation failed                    |
| `RBAC_ROLE_DENIED`            | 403    | User is not admin                          |
| `CONFLICT_ERROR`              | 409    | Slug collision (name already exists)       |
| `WORKFLOW_INVALID_TRANSITION` | 422    | parent_id creates circular reference       |
| `RESOURCE_NOT_FOUND`          | 404    | parent_id references non-existent category |

**Validation Error Example**:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "name_ar": ["The name_ar field is required"],
      "name_en": ["The name_en must be at least 2 characters"],
      "parent_id": ["The selected parent_id is invalid"]
    }
  }
}
```

---

## 4. Update Category

### PUT /api/v1/categories/{id}

**Description**: Update category metadata (admin only). **Slug is immutable and cannot be changed.**

**Authentication**: Required
**Authorization**: Admin role required

**Path Parameters**:

- `id` (integer, required): Category ID

**Request Body** (all fields optional):

```json
{
  "name_ar": "مواد بناء الممتازة",
  "name_en": "Premium Building Materials",
  "parent_id": null,
  "icon": "lucide-box-2",
  "is_active": true,
  "version": 0
}
```

**Request Parameters**:

| Field       | Type    | Required | Validations                  | Description                                         |
| ----------- | ------- | -------- | ---------------------------- | --------------------------------------------------- |
| `name_ar`   | string  | No       | min:2, max:100               | Update Arabic name                                  |
| `name_en`   | string  | No       | min:2, max:100               | Update English name                                 |
| `parent_id` | integer | No       | exists:categories,id OR null | Change parent (validated for cycles)                |
| `icon`      | string  | No       | max:50                       | Update icon                                         |
| `is_active` | boolean | No       | —                            | Toggle visibility                                   |
| `version`   | integer | No       | —                            | Optimistic locking version (for conflict detection) |

**Request**:

```http
PUT /api/v1/categories/1 HTTP/1.1
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "name_ar": "مواد بناء محدثة",
  "version": 0
}
```

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "parent_id": null,
    "name_ar": "مواد بناء محدثة",
    "name_en": "Building Materials",
    "slug": "building-materials",
    "icon": "lucide-box",
    "sort_order": 1,
    "is_active": true,
    "version": 1,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:05:00Z",
    "deleted_at": null,
    "children": []
  },
  "error": null
}
```

**Error Responses**:

| Error Code                    | Status | Description                                                     |
| ----------------------------- | ------ | --------------------------------------------------------------- |
| `VALIDATION_ERROR`            | 422    | Input validation failed                                         |
| `CONFLICT_ERROR`              | 409    | Version mismatch (concurrent update) OR parent_id creates cycle |
| `RBAC_ROLE_DENIED`            | 403    | User is not admin                                               |
| `RESOURCE_NOT_FOUND`          | 404    | Category not found                                              |
| `WORKFLOW_INVALID_TRANSITION` | 422    | parent_id creates circular reference                            |

**Optimistic Locking Example** (version conflict):

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "CONFLICT_ERROR",
    "message": "Category was modified concurrently. Please refresh and try again.",
    "details": {
      "current_version": 1,
      "expected_version": 0
    }
  }
}
```

---

## 5. Delete Category (Soft Delete)

### DELETE /api/v1/categories/{id}

**Description**: Soft-delete a category (set deleted_at). Children remain with parent_id intact.

**Authentication**: Required
**Authorization**: Admin role required

**Path Parameters**:

- `id` (integer, required): Category ID

**Request**:

```http
DELETE /api/v1/categories/2 HTTP/1.1
Authorization: Bearer {admin-token}
```

**Success Response** (200 OK or 204 No Content):

```json
{
  "success": true,
  "data": null,
  "error": null
}
```

**Error Responses**:

| Error Code           | Status | Description                           |
| -------------------- | ------ | ------------------------------------- |
| `RESOURCE_NOT_FOUND` | 404    | Category not found or already deleted |
| `RBAC_ROLE_DENIED`   | 403    | User is not admin                     |

---

## 6. Reorder Categories (Optimistic Locking)

### PUT /api/v1/categories/{id}/reorder

**Description**: Update sort_order and recalculate siblings using optimistic locking (version).

**Authentication**: Required
**Authorization**: Admin role required

**Path Parameters**:

- `id` (integer, required): Category ID

**Request Body**:

```json
{
  "sort_order": 2,
  "version": 0
}
```

**Request Parameters**:

| Field        | Type    | Required | Validations           | Description             |
| ------------ | ------- | -------- | --------------------- | ----------------------- |
| `sort_order` | integer | Yes      | >= 0, < sibling_count | New display order       |
| `version`    | integer | Yes      | current version       | Optimistic lock version |

**Request**:

```http
PUT /api/v1/categories/1/reorder HTTP/1.1
Authorization: Bearer {admin-token}
Content-Type: application/json

{
  "sort_order": 2,
  "version": 0
}
```

**Success Response** (200 OK):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "parent_id": null,
    "name_ar": "مواد بناء",
    "name_en": "Building Materials",
    "slug": "building-materials",
    "sort_order": 2,
    "is_active": true,
    "version": 1,
    "updated_at": "2026-04-15T10:05:30Z",
    "children": []
  },
  "error": null
}
```

**Reorder Algorithm**:

1. Lock category by version check: `WHERE id = {id} AND version = {old_version}`
2. Increment version: `version = version + 1`
3. Update target sort_order: `sort_order = {new_sort_order}`
4. Recalculate siblings:
   - If new_sort_order > old_sort_order: decrement sort_order for items between old and new
   - If new_sort_order < old_sort_order: increment sort_order for items between new and old
5. Return updated category

**Error Responses**:

| Error Code           | Status | Description                                   |
| -------------------- | ------ | --------------------------------------------- |
| `CONFLICT_ERROR`     | 409    | Version mismatch (concurrent modification)    |
| `VALIDATION_ERROR`   | 422    | Invalid sort_order or missing required fields |
| `RESOURCE_NOT_FOUND` | 404    | Category not found                            |
| `RBAC_ROLE_DENIED`   | 403    | User is not admin                             |

**Concurrent Conflict Example**:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "CONFLICT_ERROR",
    "message": "Reorder failed: category was modified by another user.",
    "details": {
      "current_version": 1,
      "expected_version": 0
    }
  }
}
```

---

## Error Code Registry (Category-Specific)

All error responses use standardized Bunyan error format:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly message",
    "details": { ... }
  }
}
```

### Error Codes Used

| Code                          | HTTP | Scenario                                                                   |
| ----------------------------- | ---- | -------------------------------------------------------------------------- |
| `VALIDATION_ERROR`            | 422  | name_ar/name_en invalid, parent_id missing, sort_order < 0                 |
| `RESOURCE_NOT_FOUND`          | 404  | Category ID doesn't exist                                                  |
| `RBAC_ROLE_DENIED`            | 403  | Non-admin attempts POST/PUT/DELETE                                         |
| `CONFLICT_ERROR`              | 409  | Slug collision OR optimistic lock version mismatch OR parent creates cycle |
| `WORKFLOW_INVALID_TRANSITION` | 422  | parent_id creates circular reference                                       |
| `AUTH_UNAUTHORIZED`           | 403  | User not authenticated                                                     |
| `AUTH_TOKEN_EXPIRED`          | 401  | Bearer token invalid/expired                                               |
| `SERVER_ERROR`                | 500  | Unhandled exception                                                        |

---

## Request & Response Headers

### Request Headers (Required)

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
Accept-Language: ar | en
```

### Response Headers (Standard)

```http
Content-Type: application/json; charset=utf-8
Cache-Control: private, max-age=300  (for GET, varies)
ETag: "hash"
X-Request-ID: correlation-id
```

---

## Examples by Workflow

### Workflow 1: Create Top-Level Category

```http
POST /api/v1/categories HTTP/1.1
Authorization: Bearer {token}
Content-Type: application/json

{
  "name_ar": "مواد بناء",
  "name_en": "Building Materials",
  "icon": "lucide-box"
}
```

Response (201):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "parent_id": null,
    "name_ar": "مواد بناء",
    "name_en": "Building Materials",
    "slug": "building-materials",
    "icon": "lucide-box",
    "sort_order": 0,
    "is_active": true,
    "version": 0
  }
}
```

### Workflow 2: Create Nested Sub-Category

```http
POST /api/v1/categories HTTP/1.1

{
  "name_ar": "أسمنت",
  "name_en": "Cement",
  "parent_id": 1,
  "sort_order": 0
}
```

Response (201):

```json
{
  "success": true,
  "data": {
    "id": 2,
    "parent_id": 1,
    "name_ar": "أسمنت",
    "name_en": "Cement",
    "slug": "cement",
    "sort_order": 0,
    "version": 0
  }
}
```

### Workflow 3: Move Category to Different Parent

```http
PUT /api/v1/categories/2 HTTP/1.1

{
  "parent_id": 3,
  "version": 0
}
```

Response (200):

```json
{
  "success": true,
  "data": {
    "id": 2,
    "parent_id": 3,
    "name_ar": "أسمنت",
    "name_en": "Cement",
    "version": 1
  }
}
```

### Workflow 4: Reorder Categories

```http
PUT /api/v1/categories/1/reorder HTTP/1.1

{
  "sort_order": 3,
  "version": 0
}
```

Response (200):

```json
{
  "success": true,
  "data": {
    "id": 1,
    "sort_order": 3,
    "version": 1
  }
}
```

---

## Performance & Caching

### Response Time SLAs

| Endpoint                       | Typical | P99    |
| ------------------------------ | ------- | ------ |
| GET /api/v1/categories         | <500ms  | <1s    |
| GET /api/v1/categories/{id}    | <100ms  | <300ms |
| POST /api/v1/categories        | <200ms  | <500ms |
| PUT /api/v1/categories/{id}    | <200ms  | <500ms |
| DELETE /api/v1/categories/{id} | <100ms  | <300ms |

### Caching Strategy

- **GET /api/v1/categories** (tree): Cache 5-15 minutes, invalidate on mutations
- **GET /api/v1/categories/{id}**: Cache 1 hour
- **Cache key**: `category:tree:v1` or `category:{id}`
- **Invalidation**: On any POST/PUT/DELETE, flush relevant keys

---

**Next**: Service layer and frontend component specifications.
