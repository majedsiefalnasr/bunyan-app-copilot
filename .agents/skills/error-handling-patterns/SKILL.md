---
name: error-handling-patterns
description: Standard error format, error code registry
---

# Error Handling Patterns — Bunyan

## API Response Contract

All API responses MUST follow this format:

```json
{
  "success": true|false,
  "data": { ... } | null,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message"
  } | null
}
```

## Error Code Registry

| Code | HTTP | Description |
|------|------|-------------|
| `AUTH_INVALID_CREDENTIALS` | 401 | Invalid login credentials |
| `AUTH_TOKEN_EXPIRED` | 401 | Authentication token expired |
| `AUTH_UNAUTHORIZED` | 403 | Insufficient permissions |
| `RBAC_ROLE_DENIED` | 403 | Role not allowed for this action |
| `RESOURCE_NOT_FOUND` | 404 | Requested resource not found |
| `VALIDATION_ERROR` | 422 | Input validation failed |
| `WORKFLOW_INVALID_TRANSITION` | 422 | Invalid state transition |
| `WORKFLOW_PREREQUISITES_UNMET` | 422 | Prerequisites not satisfied |
| `PAYMENT_FAILED` | 422 | Payment processing failed |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |

## Laravel Exception Handler

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): JsonResponse
{
    if ($e instanceof ValidationException) {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
                'details' => $e->errors(),
            ],
        ], 422);
    }

    if ($e instanceof ModelNotFoundException) {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => 'المورد المطلوب غير موجود',
            ],
        ], 404);
    }

    if ($e instanceof AuthorizationException) {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'AUTH_UNAUTHORIZED',
                'message' => 'غير مصرح لك بهذا الإجراء',
            ],
        ], 403);
    }

    // Log unexpected errors
    Log::error($e->getMessage(), ['exception' => $e]);

    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'حدث خطأ غير متوقع',
        ],
    ], 500);
}
```

## Custom Exception Pattern

```php
class InvalidStateTransitionException extends DomainException
{
    public function __construct(
        string $message,
        public readonly string $fromState,
        public readonly string $toState,
    ) {
        parent::__construct($message);
    }
}
```
