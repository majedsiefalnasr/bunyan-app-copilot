# Error Handling Quickstart — Developer Reference

**Branch**: `spec/005-error-handling` | **Date**: 2026-04-11

A quick reference guide for implementing proper error handling in Bunyan features.

---

## 1. Quick Reference: Error Codes

| Code                           | HTTP | Use When                       | Category   |
| ------------------------------ | ---- | ------------------------------ | ---------- |
| `AUTH_INVALID_CREDENTIALS`     | 401  | Wrong email/password           | Auth       |
| `AUTH_TOKEN_EXPIRED`           | 401  | Token > 24 hours old           | Auth       |
| `AUTH_UNAUTHORIZED`            | 403  | User lacks permission          | Auth       |
| `RBAC_ROLE_DENIED`             | 403  | Role not allowed for endpoint  | Auth       |
| `RESOURCE_NOT_FOUND`           | 404  | Resource does not exist        | Data       |
| `VALIDATION_ERROR`             | 422  | Input validation failed        | Input      |
| `WORKFLOW_INVALID_TRANSITION`  | 422  | Invalid state transition       | Workflow   |
| `WORKFLOW_PREREQUISITES_UNMET` | 422  | Prerequisites not met          | Workflow   |
| `PAYMENT_FAILED`               | 422  | Payment processing error       | Payment    |
| `RATE_LIMIT_EXCEEDED`          | 429  | Too many requests              | Rate Limit |
| `CONFLICT_ERROR`               | 409  | Duplicate/uniqueness violation | Data       |
| `SERVER_ERROR`                 | 500  | Unexpected exception           | System     |

---

## 2. Backend: Throwing Custom Errors

### Custom Exception

```php
// Define in app/Exceptions/

class InvalidStateTransitionException extends \DomainException
{
    public function __construct(
        string $message,
        public readonly string $fromState,
        public readonly string $toState,
    ) {
        parent::__construct($message);
    }
}

// Throw in service
throw new InvalidStateTransitionException(
    'Cannot transition from draft to archived',
    fromState: 'draft',
    toState: 'archived',
);
```

### Using Error Code Enum

```php
// app/Services/ProjectService.php

use App\Enums\ApiErrorCode;

public function create(CreateProjectData $data): Project
{
    if ($data->budget < 0) {
        throw new \InvalidArgumentException('Budget must be positive');
    }

    return $this->store()->create([...]);
}

public function transition(Project $project, string $newStatus): void
{
    if (!$this->isValidTransition($project->status, $newStatus)) {
        throw new InvalidStateTransitionException(
            sprintf('Cannot transition from %s to %s', $project->status, $newStatus),
            fromState: $project->status,
            toState: $newStatus,
        );
    }

    $project->update(['status' => $newStatus]);
    Log::info('Project status changed', [
        'project_id' => $project->id,
        'from' => $project->status,
        'to' => $newStatus,
    ]);
}
```

---

## 3. Backend: Exception Handler (Handler.php)

```php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Enums\ApiErrorCode;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // API requests only
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return parent::render($request, $e);
        }

        // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => ApiErrorCode::VALIDATION_ERROR->value,
                    'message' => __('validation.failed'),
                    'details' => $e->errors(),
                ],
            ], 422);
        }

        // Auth errors
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => ApiErrorCode::AUTH_INVALID_CREDENTIALS->value,
                    'message' => __('auth.invalid_credentials'),
                ],
            ], 401);
        }

        // Authorization errors
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => ApiErrorCode::AUTH_UNAUTHORIZED->value,
                    'message' => __('auth.unauthorized'),
                ],
            ], 403);
        }

        // Model not found
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => ApiErrorCode::RESOURCE_NOT_FOUND->value,
                    'message' => __('errors.not_found'),
                ],
            ], 404);
        }

        // Custom domain exceptions
        if ($e instanceof InvalidStateTransitionException) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => ApiErrorCode::WORKFLOW_INVALID_TRANSITION->value,
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // Fallback: 500 server error
        Log::error('Unhandled exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::SERVER_ERROR->value,
                'message' => __('errors.server_error'),
            ],
        ], 500);
    }
}
```

---

## 4. Backend: API Response Helper Trait

```php
// app/Traits/ApiResponseTrait.php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use App\Enums\ApiErrorCode;

trait ApiResponseTrait
{
    protected function success($data = null, $message = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], $statusCode);
    }

    protected function error(
        $message,
        $details = [],
        $statusCode = 500,
        $code = 'SERVER_ERROR'
    ): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => !empty($details) ? $details : null,
            ],
        ], $statusCode);
    }
}

// Usage in controller
class ProjectController extends Controller
{
    use ApiResponseTrait;

    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->service->create($request->validated());
        return $this->success($project, null, 201);
    }

    public function show($id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);
            return $this->success($project);
        } catch (ModelNotFoundException $e) {
            return $this->error(
                __('errors.not_found'),
                [],
                404,
                ApiErrorCode::RESOURCE_NOT_FOUND->value
            );
        }
    }
}
```

---

## 5. Backend: Logging Context

```php
// Automatic context via CorrelationIdMiddleware + ContextProcessor
Log::info('Payment processed', [
    'payment_id' => 101,
    'amount' => 10000,
    'currency' => 'SAR',
]);

// Output in logs
// [2026-04-11 10:30:15] production.INFO: Payment processed
// correlation_id: 550e8400-e29b-41d4-a716-446655440000
// user_id: 5
// payment_id: 101
// amount: 10000
```

---

## 6. Backend: Rate Limiting

```php
// routes/api.php

Route::middleware('throttle:100,1')->group(function () {
    // Global endpoints: 100 req/min
    Route::get('/api/v1/projects', [ProjectController::class, 'index']);
});

Route::middleware('throttle:10,1')->group(function () {
    // Auth endpoints: 10 req/min
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// Custom middleware
Route::middleware([RateLimitByRoleMiddleware::class])->group(function () {
    // Per-role rate limiting
    Route::post('/api/v1/payments', [PaymentController::class, 'store']);
});
```

---

## 7. Frontend: useApi Composable

```typescript
// composables/useApi.ts

export function useApi() {
  const config = useRuntimeConfig();
  const auth = useAuthStore();
  const toast = useToast();

  const api = $fetch.create({
    baseURL: config.public.apiBaseUrl,

    onRequest({ options }) {
      if (auth.token) {
        options.headers.set("Authorization", `Bearer ${auth.token}`);
      }
      options.headers.set("Accept-Language", useI18n().locale.value);
    },

    onResponseError({ response }) {
      const data = response._data as ApiResponse<unknown>;

      // Handle specific status codes
      if (response.status === 401) {
        auth.logout();
        navigateTo("/auth/login");
        return;
      }

      if (response.status === 403) {
        navigateTo("/error/403");
        return;
      }

      // Show error toast
      const message = data?.error?.message || "An error occurred";
      toast.showToast({
        type: "error",
        message,
        duration: 5000,
      });

      // Log correlation ID for support
      const correlationId = response.headers.get("X-Correlation-ID");
      if (correlationId) {
        console.error("Error ID:", correlationId);
      }
    },
  });

  return { api };
}

// Usage
const { api } = useApi();
const { data: projects } = await api<ProjectsResponse>("/api/v1/projects");
```

---

## 8. Frontend: Error Handler Composable

```typescript
// composables/useErrorHandler.ts

export function useErrorHandler() {
  const { t } = useI18n();
  const toast = useToast();

  function handleError(error: ApiError) {
    const message = t(`errors.${error.code}`) || error.message;

    if (error.code === "VALIDATION_ERROR") {
      // Handle field-level errors separately
      return {
        message: t("errors.validation_failed"),
        details: error.details, // Pass to form
      };
    }

    if (error.code === "RATE_LIMIT_EXCEEDED") {
      toast.showToast({
        type: "warning",
        message,
        duration: 10000,
      });
      return;
    }

    toast.showToast({
      type: "error",
      message,
      duration: 5000,
    });
  }

  return { handleError };
}
```

---

## 9. Frontend: Toast System

```typescript
// composables/useToast.ts

export function useToast() {
  const error = useErrorStore();

  function showToast(toast: Omit<Toast, "id">) {
    const id = Math.random().toString(36).substring(7);
    error.addToast({ ...toast, id });

    if (toast.duration !== 0) {
      setTimeout(() => {
        error.removeToast(id);
      }, toast.duration ?? 5000);
    }

    return id;
  }

  return { showToast };
}

// Usage
const { showToast } = useToast();
showToast({
  type: "error",
  message: "Failed to create project",
  duration: 5000,
});
```

---

## 10. Frontend: Error Pages

```vue
<!-- pages/error-404.vue -->

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100"
  >
    <div class="text-center max-w-md">
      <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
      <p class="text-xl text-gray-600 mb-6">{{ $t("errors.not_found") }}</p>
      <NuxtLink
        to="/"
        class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        {{ $t("errors.go_home") }}
      </NuxtLink>
    </div>
  </div>
</template>

<!-- pages/error-403.vue -->

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-red-100"
  >
    <div class="text-center max-w-md">
      <h1 class="text-6xl font-bold text-red-900 mb-2">403</h1>
      <p class="text-xl text-red-700 mb-6">{{ $t("errors.access_denied") }}</p>
      <a
        href="mailto:support@bunyan.example"
        class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg"
      >
        {{ $t("errors.contact_support") }}
      </a>
    </div>
  </div>
</template>
```

---

## 11. Translations

```json
{
  "ar": {
    "errors": {
      "validation_failed": "فشل التحقق من صحة البيانات",
      "not_found": "المورد المطلوب غير موجود",
      "access_denied": "لا تملك صلاحية الوصول",
      "server_error": "حدث خطأ غير متوقع",
      "rate_limit": "عدد كبير جداً من الطلبات. يرجى المحاولة لاحقاً",
      "go_home": "العودة للرئيسية",
      "contact_support": "التواصل مع الدعم"
    },
    "validation": {
      "required": "حقل :attribute مطلوب",
      "email": ":attribute يجب أن يكون بريد إلكتروني صحيح"
    },
    "auth": {
      "invalid_credentials": "بيانات الاعتماد غير صحيحة",
      "unauthorized": "لا تملك صلاحية لهذا الإجراء"
    }
  },
  "en": {
    "errors": {
      "validation_failed": "Validation failed",
      "not_found": "The requested resource was not found",
      "access_denied": "You do not have access to this resource",
      "server_error": "An unexpected error occurred",
      "rate_limit": "Too many requests. Please try again later",
      "go_home": "Go to home",
      "contact_support": "Contact support"
    }
  }
}
```

---

## 12. Testing Errors

### Backend Unit Test

```php
// tests/Unit/ExceptionHandlerTest.php

class ExceptionHandlerTest extends TestCase
{
    public function test_validation_exception_returns_422_with_details()
    {
        $validator = Validator::make([], ['email' => 'required|email']);
        $exception = new ValidationException($validator);

        $response = $this->handler->render(request(), $exception);

        $this->assertEquals(422, $response->status());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('VALIDATION_ERROR', $data['error']['code']);
        $this->assertIsArray($data['error']['details']);
    }
}
```

### Frontend E2E Test

```typescript
// tests/e2e/errorHandling.test.ts

test("displays validation errors on form submission", async ({ page }) => {
  await page.goto("/projects/create");

  // Submit empty form
  await page.click('button:has-text("Create")');

  // Check for error messages
  await expect(page.locator("text=required")).toBeVisible();
  await expect(page.locator('[role="alert"]')).toContainText("validation");
});

test("displays correlation ID on server error", async ({ page }) => {
  // Force 500 error (mock API)
  await page.route("**/api/**", (route) => {
    route.abort("failed");
  });

  await page.goto("/projects");

  // Check error message includes ID
  const errorText = await page.locator('[role="alert"]').textContent();
  expect(errorText).toContain("Error ID:");
});
```

---

## 13. Common Patterns

### Pattern 1: Validate Then Process

```php
public function transfer(TransferRequest $request): JsonResponse
{
    // $request->validated() only returns valid data
    $data = $request->validated();

    // Process in service
    try {
        $transfer = $this->service->process($data);
        return $this->success(new TransferResource($transfer), null, 201);
    } catch (InsufficientFundsException $e) {
        return $this->error(
            $e->getMessage(),
            [],
            422,
            ApiErrorCode::PAYMENT_FAILED->value
        );
    }
}
```

### Pattern 2: Check Permissions

```php
public function update(Project $project, UpdateProjectRequest $request): JsonResponse
{
    // Authorization check (throws AuthorizationException → 403)
    $this->authorize('update', $project);

    $project->update($request->validated());
    return $this->success(new ProjectResource($project));
}
```

### Pattern 3: Handle Optional Resources

```php
public function show($id): JsonResponse
{
    $project = Project::find($id);

    if (!$project) {
        return $this->error(
            __('errors.not_found'),
            [],
            404,
            ApiErrorCode::RESOURCE_NOT_FOUND->value
        );
    }

    return $this->success(new ProjectResource($project));
}

// OR use findOrFail (throws ModelNotFoundException → 404 automatically)
public function show($id): JsonResponse
{
    $project = Project::findOrFail($id);  // Exception handler converts to 404
    return $this->success(new ProjectResource($project));
}
```

---

## 14. Debugging Checklist

- [ ] Error response includes `X-Correlation-ID` header
- [ ] Correlation ID matches logs when debugging
- [ ] Sensitive data (passwords, tokens, cards) NOT in logs
- [ ] Arabic and English error messages both present
- [ ] Error codes match registry
- [ ] HTTP status codes correct (401, 403, 404, 422, 429, 500)
- [ ] Validation errors include field-level details
- [ ] Frontend shows user-friendly messages (not stack traces)
- [ ] Rate limits enforced on sensitive endpoints
- [ ] Tests cover error paths (not just success)

---

## 15. Support Workflow

**Customer reports error:**

> "I can't submit the form. Getting an error."

**Support asks:**

> "What's the error ID at the bottom right?"

**Customer provides:**

> "Error ID: 550e8400-e29b-41d4-a716-446655440000"

**Support queries logs:**

```bash
# In Laravel Pail or log files
tail -f storage/logs/laravel.log | grep 550e8400-e29b-41d4-a716-446655440000
```

**Support finds:**

- All request logs for that correlation ID
- Validation errors, auth errors, rate limits
- Exact timestamp and user context
- Can confidently resolve issue

---

## Additional Resources

- [plan.md](plan.md) — Overall implementation strategy
- [research.md](research.md) — Technical deep dives
- [data-model.md](data-model.md) — Database schema
- [contracts/error-response.json](contracts/error-response.json) — API contract
- [contracts/error-codes-registry.json](contracts/error-codes-registry.json) — Error code reference
- [contracts/correlation-id-flow.json](contracts/correlation-id-flow.json) — Request tracing
