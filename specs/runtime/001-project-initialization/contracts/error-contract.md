# API Contract: Error Response Format

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Purpose:** Define standardized error response format for all API responses  
> **Created:** 2026-04-10T00:00:00Z
> **Binding:** All API responses (success AND error) must follow this format

---

## Standard Response Envelope

Every API response (whether successful or failed) must follow this exact JSON structure:

```json
{
  "success": boolean,
  "data": null | object | array,
  "message": string,
  "errors": object
}
```

**Fields:**

| Field     | Type                | Description                                      | Notes                     |
| --------- | ------------------- | ------------------------------------------------ | ------------------------- |
| `success` | boolean             | `true` if operation succeeded, `false` if failed | Always present            |
| `data`    | null\|object\|array | Operation result (null on error)                 | Populated on success only |
| `message` | string              | Human-readable message (success or error)        | Always present            |
| `errors`  | object              | Detailed error information (empty on success)    | Empty `{}` on success     |

---

## Error Scenarios & Examples

### 1. Validation Error (422 Unprocessable Entity)

**Scenario:** Form request validation fails (e.g., missing email, invalid password format)

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address."
    ],
    "password": [
      "The password must be at least 8 characters.",
      "The password must contain at least one uppercase letter."
    ],
    "name": [
      "The name field is required."
    ]
  }
}
```

**Error Object Structure:**

- Keys: field names (from request body)
- Values: arrays of error messages (one or more)
- Multiple messages possible if multiple validations fail

**HTTP Status:** `422 Unprocessable Entity`

---

### 2. Authentication Error (401 Unauthorized)

**Scenario 1: Invalid credentials**

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Invalid credentials",
  "errors": {
    "auth": ["Email or password is incorrect."]
  }
}
```

**Scenario 2: Missing token**

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Unauthenticated",
  "errors": {
    "auth": ["Authorization header missing."]
  }
}
```

**Scenario 3: Invalid token**

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Unauthenticated",
  "errors": {
    "auth": ["Token invalid or expired."]
  }
}
```

**HTTP Status:** `401 Unauthorized`

---

### 3. Authorization Error (403 Forbidden)

**Scenario:** User authenticated but lacks required role/permission

```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Forbidden",
  "errors": {
    "rbac": ["This action requires 'admin' role. Your role is 'customer'."]
  }
}
```

**HTTP Status:** `403 Forbidden`

**Note:** This error occurs AFTER authentication succeeds but authorization fails.

---

### 4. Resource Not Found (404 Not Found)

**Scenario:** Requested resource does not exist

```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Not found",
  "errors": {
    "resource": ["User with ID 999 not found."]
  }
}
```

**HTTP Status:** `404 Not Found`

---

### 5. Business Logic Error (409 Conflict)

**Scenario:** Action violates business rules (e.g., duplicate email)

```http
HTTP/1.1 409 Conflict
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Conflict",
  "errors": {
    "email": ["This email is already registered."]
  }
}
```

**HTTP Status:** `409 Conflict`

---

### 6. Server Error (500 Internal Server Error)

**Scenario:** Unhandled exception or database failure

```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "An unexpected error occurred. Please try again later.",
  "errors": {
    "server": ["Database connection timeout."]
  }
}
```

**HTTP Status:** `500 Internal Server Error`

**Note:** DO NOT expose internal exception details to clients (security). Log full stack trace server-side.

---

### 7. Rate Limited (429 Too Many Requests)

**Scenario:** Client exceeds rate limit

```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 60

{
  "success": false,
  "data": null,
  "message": "Too many requests",
  "errors": {
    "rate_limit": ["You have made too many requests. Please retry after 60 seconds."]
  }
}
```

**HTTP Status:** `429 Too Many Requests`  
**Header:** `Retry-After: 60` (seconds to wait)

---

### 8. Bad Request (400 Bad Request)

**Scenario:** Malformed request (e.g., invalid JSON)

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Bad request",
  "errors": {
    "request": ["Invalid JSON format."]
  }
}
```

**HTTP Status:** `400 Bad Request`

---

## Success Response Examples

### Success with Data (200 OK)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "id": 1,
    "name": "محمد علي",
    "email": "user@example.com",
    "role": "customer"
  },
  "message": "User retrieved successfully",
  "errors": {}
}
```

### Success with Array (200 OK)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "محمد علي",
      "email": "user@example.com"
    },
    {
      "id": 2,
      "name": "فاطمة أحمد",
      "email": "fatima@example.com"
    }
  ],
  "message": "Users retrieved successfully",
  "errors": {}
}
```

### Success with Null Data (201 Created)

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "success": true,
  "data": {
    "id": 123,
    "created_at": "2026-04-10T10:30:00Z"
  },
  "message": "Project created successfully",
  "errors": {}
}
```

### Success with Pagination (200 OK)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "items": [
      { "id": 1, "name": "Item 1" },
      { "id": 2, "name": "Item 2" }
    ],
    "pagination": {
      "total": 100,
      "per_page": 2,
      "current_page": 1,
      "last_page": 50
    }
  },
  "message": "Items retrieved successfully",
  "errors": {}
}
```

---

## HTTP Status Code Reference

| Status | Meaning               | When to Use                         | `success` | Data      |
| ------ | --------------------- | ----------------------------------- | --------- | --------- |
| `200`  | OK                    | Successful GET/PATCH/PUT            | `true`    | populated |
| `201`  | Created               | Successful POST (resource created)  | `true`    | populated |
| `204`  | No Content            | Success with no response body       | `true`    | null      |
| `400`  | Bad Request           | Malformed request (invalid JSON)    | `false`   | null      |
| `401`  | Unauthorized          | Missing/invalid authentication      | `false`   | null      |
| `403`  | Forbidden             | Authenticated but insufficient role | `false`   | null      |
| `404`  | Not Found             | Resource doesn't exist              | `false`   | null      |
| `409`  | Conflict              | Business rule violated              | `false`   | null      |
| `422`  | Unprocessable Entity  | Validation error                    | `false`   | null      |
| `429`  | Too Many Requests     | Rate limit exceeded                 | `false`   | null      |
| `500`  | Internal Server Error | Unhandled exception                 | `false`   | null      |

---

## Laravel Implementation

### Global Exception Handler

**Location:** `backend/app/Exceptions/Handler.php`

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception): Response
    {
        // Validation errors
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        // Authentication errors
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthenticated',
                'errors' => ['auth' => ['Token invalid or expired.']],
            ], 401);
        }

        // Authorization errors (not enough permission)
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Forbidden',
                'errors' => ['rbac' => [$exception->getMessage()]],
            ], 403);
        }

        // Model not found
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Not found',
                'errors' => ['resource' => ['Resource not found.']],
            ], 404);
        }

        // Generic server error
        if ($this->isHttpException($exception)) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'message' => match($statusCode) {
                500 => 'An unexpected error occurred. Please try again later.',
                default => $exception->getMessage(),
            },
            'errors' => ['server' => [$exception->getMessage()]],
        ], $statusCode);
    }
}
```

### Base Response Trait

**Location:** `backend/app/Traits/ApiResponse.php`

```php
<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(
        $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'errors' => [],
        ], $statusCode);
    }

    protected function error(
        string $message,
        array $errors = [],
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
```

**Usage in Controller:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error(
                'User not found',
                ['resource' => ['User with ID ' . $id . ' not found.']],
                404
            );
        }

        return $this->success($user);
    }
}
```

---

## Frontend Error Handling

### Composable for API Errors

**Location:** `frontend/composables/useApiError.ts`

```typescript
import type { ApiResponse } from "~/types/api";

export const useApiError = () => {
  const handleError = (error: any): string => {
    if (error.data?.errors) {
      // Collect all error messages
      const messages = Object.values(error.data.errors).flat().join("; ");
      return messages || error.data.message || "An error occurred";
    }

    return error.message || "An unexpected error occurred";
  };

  const getFieldError = (error: any, field: string): string[] => {
    return error?.data?.errors?.[field] || [];
  };

  return {
    handleError,
    getFieldError,
  };
};
```

**Usage in Component:**

```vue
<template>
  <form @submit.prevent="submitForm">
    <UInput
      v-model="form.email"
      label="Email"
      :error="fieldErrors.email?.[0]"
    />
    <UButton type="submit">Submit</UButton>
    <UAlert v-if="globalError" :title="globalError" color="red" />
  </form>
</template>

<script setup lang="ts">
import { useApiError } from "~/composables/useApiError";

const { handleError, getFieldError } = useApiError();
const form = ref({ email: "" });
const globalError = ref("");
const fieldErrors = ref({});

const submitForm = async () => {
  try {
    await $fetch("/api/v1/submit", {
      method: "POST",
      body: form.value,
    });
  } catch (error) {
    globalError.value = handleError(error);
    fieldErrors.value = error?.data?.errors || {};
  }
};
</script>
```

---

## Error Documentation Template

For each new endpoint, document:

1. **Success Response** — What does success look like?
2. **Validation Error** — What fields can fail validation?
3. **Auth Error** — What authentication issues can occur?
4. **Business Error** — What business rules can fail?
5. **Server Error** — What can go wrong internally?

---

## Checklist for Error Contract Compliance

- [ ] All 404s return resource not found
- [ ] All 401s include auth error reason
- [ ] All 403s include specific permission denied reason
- [ ] All 422s include field-level errors
- [ ] All 500s do NOT expose internal stack traces to client
- [ ] All responses have `success`, `data`, `message`, `errors` fields
- [ ] Validation errors specify field names (with array of messages)
- [ ] HTTP status codes match error type (not all errors are 400)
- [ ] Error messages are user-friendly (no SQL, no internal paths)
- [ ] Arabic messages supported (no hardcoded English-only)
