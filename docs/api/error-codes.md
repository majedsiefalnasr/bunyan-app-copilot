# Error Codes Reference — Bunyan API

**Complete registry of all API error codes with status codes, messages, and handling strategies.**

## Error Code Registry

### Authentication & Authorization (4xx)

#### AUTH_INVALID_CREDENTIALS (401)

| Field                     | Value                                                                          |
| ------------------------- | ------------------------------------------------------------------------------ |
| **Code**                  | `AUTH_INVALID_CREDENTIALS`                                                     |
| **HTTP Status**           | 401 Unauthorized                                                               |
| **Arabic Message**        | بيانات دخول غير صحيحة                                                          |
| **English Message**       | Invalid email or password                                                      |
| **Technical Description** | User provided invalid login credentials (wrong email, wrong password, or both) |
| **When to Use**           | Login attempt with incorrect email or password                                 |
| **Client Action**         | Show login form again with error message; suggest password reset               |

**Example Request:**

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "wrongpassword"
}
```

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "Invalid email or password",
    "details": null
  }
}
```

---

#### AUTH_TOKEN_EXPIRED (401)

| Field                     | Value                                                    |
| ------------------------- | -------------------------------------------------------- |
| **Code**                  | `AUTH_TOKEN_EXPIRED`                                     |
| **HTTP Status**           | 401 Unauthorized                                         |
| **Arabic Message**        | انتهت جلستك. يرجى تسجيل الدخول مرة أخرى                  |
| **English Message**       | Your session has expired. Please log in again.           |
| **Technical Description** | User's authentication token has expired or been revoked  |
| **When to Use**           | API request with expired bearer token or session token   |
| **Client Action**         | Redirect to login page; request new authentication token |

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_TOKEN_EXPIRED",
    "message": "Your session has expired",
    "details": null
  }
}
```

---

#### AUTH_UNAUTHORIZED (403)

| Field                     | Value                                                                                  |
| ------------------------- | -------------------------------------------------------------------------------------- |
| **Code**                  | `AUTH_UNAUTHORIZED`                                                                    |
| **HTTP Status**           | 403 Forbidden                                                                          |
| **Arabic Message**        | غير مصرح لك بهذا الإجراء                                                               |
| **English Message**       | You do not have permission to access this resource                                     |
| **Technical Description** | User is authenticated but lacks general permission for this action (not role-specific) |
| **When to Use**           | General permission check fails; user lacks specific capability                         |
| **Client Action**         | Show "Access Denied" message; suggest contacting admin                                 |

---

#### RBAC_ROLE_DENIED (403)

| Field                     | Value                                                                                    |
| ------------------------- | ---------------------------------------------------------------------------------------- |
| **Code**                  | `RBAC_ROLE_DENIED`                                                                       |
| **HTTP Status**           | 403 Forbidden                                                                            |
| **Arabic Message**        | دورك الحالي لا يسمح بهذا الإجراء                                                         |
| **English Message**       | Your role is not authorized for this action.                                             |
| **Technical Description** | User's role is not allowed to perform this action (role-specific check)                  |
| **When to Use**           | Role-based access control check fails (e.g., Contractor trying to access Admin endpoint) |
| **Client Action**         | Show "Access Denied" message; do NOT expose role names                                   |
| **Security Note**         | Error message MUST NOT include user's role or endpoint's allowed roles                   |

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RBAC_ROLE_DENIED",
    "message": "Access denied",
    "details": null
  }
}
```

---

### Resource & Input Errors (4xx)

#### RESOURCE_NOT_FOUND (404)

| Field                     | Value                                                           |
| ------------------------- | --------------------------------------------------------------- |
| **Code**                  | `RESOURCE_NOT_FOUND`                                            |
| **HTTP Status**           | 404 Not Found                                                   |
| **Arabic Message**        | المورد المطلوب غير موجود                                        |
| **English Message**       | The requested resource was not found                            |
| **Technical Description** | Requested resource (project, user, task, etc.) does not exist   |
| **When to Use**           | Model not found (via `findOrFail()`, soft-deleted models, etc.) |
| **Client Action**         | Show 404 page; offer link to list or home page                  |

---

#### VALIDATION_ERROR (422)

| Field                     | Value                                                                      |
| ------------------------- | -------------------------------------------------------------------------- |
| **Code**                  | `VALIDATION_ERROR`                                                         |
| **HTTP Status**           | 422 Unprocessable Entity                                                   |
| **Arabic Message**        | بيانات غير صحيحة. يرجى التحقق من الحقول المطلوبة                           |
| **English Message**       | Validation failed. Please check the highlighted fields.                    |
| **Technical Description** | Request body failed validation (bad format, missing required fields, etc.) |
| **When to Use**           | Form validation fails in Form Request class                                |
| **Details**               | Includes field-level validation messages                                   |
| **Client Action**         | Display field-level error messages; highlight invalid fields               |

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "name": ["The name field is required"],
      "budget": ["The budget must be a number"]
    }
  }
}
```

---

#### CONFLICT_ERROR (409)

| Field                     | Value                                                                                         |
| ------------------------- | --------------------------------------------------------------------------------------------- |
| **Code**                  | `CONFLICT_ERROR`                                                                              |
| **HTTP Status**           | 409 Conflict                                                                                  |
| **Arabic Message**        | تعارض في البيانات                                                                             |
| **English Message**       | Resource conflict. It may already exist or have been modified.                                |
| **Technical Description** | Resource conflict: duplicate unique constraint, race condition, optimistic lock failure, etc. |
| **When to Use**           | Unique constraint violation, OptimisticLockException, concurrent update detected              |
| **Client Action**         | Refresh data and retry; inform user of potential concurrent edits                             |

---

### Workflow & Business Logic Errors (4xx)

#### WORKFLOW_INVALID_TRANSITION (422)

| Field                     | Value                                                                                 |
| ------------------------- | ------------------------------------------------------------------------------------- |
| **Code**                  | `WORKFLOW_INVALID_TRANSITION`                                                         |
| **HTTP Status**           | 422 Unprocessable Entity                                                              |
| **Arabic Message**        | لا يمكن الانتقال إلى هذه الحالة من الحالة الحالية                                     |
| **English Message**       | Cannot transition to this status from the current state                               |
| **Technical Description** | Project/task status transition is not allowed by workflow rules                       |
| **When to Use**           | Workflow engine rejects state transition (e.g., can't go from Complete back to Draft) |
| **Client Action**         | Show explanation; offer valid next states                                             |

---

#### WORKFLOW_PREREQUISITES_UNMET (422)

| Field                     | Value                                                                             |
| ------------------------- | --------------------------------------------------------------------------------- |
| **Code**                  | `WORKFLOW_PREREQUISITES_UNMET`                                                    |
| **HTTP Status**           | 422 Unprocessable Entity                                                          |
| **Arabic Message**        | لم تتحقق متطلبات هذا الإجراء                                                      |
| **English Message**       | Prerequisites for this action are not yet met                                     |
| **Technical Description** | Action cannot be performed; dependencies/prerequisites not satisfied              |
| **When to Use**           | Workflow prerequisites not met (e.g., can't approve task if report not submitted) |
| **Client Action**         | Show missing prerequisites; block action UI until requirements met                |

---

#### PAYMENT_FAILED (422)

| Field                     | Value                                                                                              |
| ------------------------- | -------------------------------------------------------------------------------------------------- |
| **Code**                  | `PAYMENT_FAILED`                                                                                   |
| **HTTP Status**           | 422 Unprocessable Entity                                                                           |
| **Arabic Message**        | فشلت عملية الدفع                                                                                   |
| **English Message**       | Payment processing failed                                                                          |
| **Technical Description** | Payment gateway declined transaction (insufficient funds, card expired, fraud detection, etc.)     |
| **When to Use**           | Payment processor returns error code                                                               |
| **Client Action**         | Show specific reason (card declined, insufficient funds, etc.); retry or offer alternative payment |

---

### Rate Limiting (4xx)

#### RATE_LIMIT_EXCEEDED (429)

| Field                     | Value                                                                          |
| ------------------------- | ------------------------------------------------------------------------------ |
| **Code**                  | `RATE_LIMIT_EXCEEDED`                                                          |
| **HTTP Status**           | 429 Too Many Requests                                                          |
| **Arabic Message**        | عدد كبير جداً من الطلبات. يرجى الانتظار قبل المحاولة مرة أخرى                  |
| **English Message**       | Too many requests. Please wait before trying again.                            |
| **Technical Description** | Client has exceeded rate limit (global: 100 req/min; auth/payment: 10 req/min) |
| **When to Use**           | Rate limiter middleware detects threshold exceeded                             |
| **Response Headers**      | `Retry-After: <seconds>` with time to wait                                     |
| **Client Action**         | Show message with retry time; wait before next request                         |

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests",
    "details": null
  }
}
```

**Response Headers:**

```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
```

---

### Server Errors (5xx)

#### SERVER_ERROR (500)

| Field                     | Value                                                                      |
| ------------------------- | -------------------------------------------------------------------------- |
| **Code**                  | `SERVER_ERROR`                                                             |
| **HTTP Status**           | 500 Internal Server Error                                                  |
| **Arabic Message**        | حدث خطأ غير متوقع                                                          |
| **English Message**       | Something went wrong. Please try again later.                              |
| **Technical Description** | Unhandled exception on server (never exposed to client)                    |
| **When to Use**           | Uncaught exception in request handler                                      |
| **Security**              | Stack trace NOT included in response; logged server-side only              |
| **Client Action**         | Show error message; offer retry button; include correlation ID for support |

**Example Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "SERVER_ERROR",
    "message": "Something went wrong. Please try again.",
    "details": null
  }
}
```

---

## Client Handling Strategy

### By HTTP Status Code

| Status  | Error Codes                                        | Action                                              |
| ------- | -------------------------------------------------- | --------------------------------------------------- |
| **401** | `AUTH_INVALID_CREDENTIALS`, `AUTH_TOKEN_EXPIRED`   | Redirect to login; request new token                |
| **403** | `AUTH_UNAUTHORIZED`, `RBAC_ROLE_DENIED`            | Show 403 error page; offer contact form             |
| **404** | `RESOURCE_NOT_FOUND`                               | Show 404 error page; offer navigation               |
| **409** | `CONFLICT_ERROR`                                   | Inform user of conflict; offer refresh/retry        |
| **422** | `VALIDATION_ERROR`, `WORKFLOW_*`, `PAYMENT_FAILED` | Show field errors; suggest corrections              |
| **429** | `RATE_LIMIT_EXCEEDED`                              | Show wait time; retry after `Retry-After` seconds   |
| **500** | `SERVER_ERROR`                                     | Show error message with correlation ID; retry later |

### Frontend Implementation

```typescript
// composables/useErrorHandler.ts
export const handleError = (error: any) => {
  const code = error.error?.code;

  switch (code) {
    case 'AUTH_TOKEN_EXPIRED':
      navigateTo('/login');
      break;
    case 'RBAC_ROLE_DENIED':
      navigateTo('/error-403');
      break;
    case 'RESOURCE_NOT_FOUND':
      navigateTo('/error-404');
      break;
    case 'RATE_LIMIT_EXCEEDED':
      const retryAfter = error.response.headers['retry-after'];
      showToast(`Please wait ${retryAfter} seconds`, 'warning');
      break;
    case 'VALIDATION_ERROR':
      displayFieldErrors(error.error.details);
      break;
    default:
      showToast(error.error?.message || 'Something went wrong', 'error');
  }
};
```

## Monitoring & Alerting

### Severity Levels

| Code                          | Severity | Monitor                                  |
| ----------------------------- | -------- | ---------------------------------------- |
| `RATE_LIMIT_EXCEEDED`         | INFO     | Track per user; alert on spike           |
| `VALIDATION_ERROR`            | DEBUG    | Log field patterns for UX improvements   |
| `WORKFLOW_INVALID_TRANSITION` | WARNING  | Alert if misconfigured state machine     |
| `PAYMENT_FAILED`              | WARNING  | Alert on success rate drop below 95%     |
| `SERVER_ERROR`                | CRITICAL | Alert immediately; page on-call engineer |

### Log Query Examples

```bash
# Find 429 errors
grep "RATE_LIMIT_EXCEEDED" storage/logs/laravel.log | tail -100

# Find RBAC denials by role
grep "RBAC_ROLE_DENIED" storage/logs/laravel.log | grep "contractor"

# Find payment failures
grep "PAYMENT_FAILED" storage/logs/laravel.log | wc -l

# Track correlation ID flow
grep "correlation_id: abc-123" storage/logs/laravel.log
```

---

## References

- [Error Handling Implementation Guide](../../docs/guides/error-handling-guide.md)
- [Quickstart Guide](quickstart.md)
- [Specifications](spec.md)
