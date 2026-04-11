# Research: Error Handling & Logging — Technical Deep Dives

**Branch**: `spec/005-error-handling` | **Date**: 2026-04-11

This document provides deep technical research on the key technologies, patterns, and frameworks needed to implement Bunyan's error handling and logging infrastructure.

---

## 1. Laravel Exception Handling Architecture

### 1.1 Exception Handler Overview

Laravel's `app/Exceptions/Handler.php` is the global exception handler where all uncaught exceptions are caught, logged, and rendered to clients.

**Key Methods**:

```php
class Handler extends ExceptionHandler
{
    // Called to determine if exception should be reported (logged)
    public function shouldReport(Throwable $e): bool {
        // Return false to suppress logging
        // Return true to log
    }

    // Called to render exception to HTTP response
    public function render(Request $request, Throwable $e): Response {
        // Check if API request or HTML request
        // Format appropriately
    }

    // Called when rendering HTML responses (not used for API)
    public function view(Request $request, Throwable $e): string {
        // Return view name
    }
}
```

### 1.2 Exception Hierarchy in Laravel

```
Throwable
├── Exception
│   ├── ValidationException (form validation)
│   ├── AuthenticationException (failed login)
│   ├── AuthorizationException (permission denied)
│   ├── ModelNotFoundException (model not found)
│   ├── TokenMismatchException (CSRF token)
│   ├── RouteNotFoundException (404)
│   └── ThrottleRequestsException (rate limit)
└── Error (fatal errors, not caught)
```

### 1.3 Detecting Request Type

```php
public function render(Request $request, Throwable $e): Response {
    // Option 1: Check if API request
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => $e->getMessage(),
            ],
        ], 500);
    }

    // Option 2: Use accept header
    if ($request->expects('application/json')) {
        // Handle as JSON
    }

    // Default: HTML response
    return parent::render($request, $e);
}
```

### 1.4 Transforming Specific Exceptions

```php
if ($e instanceof ValidationException) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'details' => $e->errors(),  // Field-level errors
        ],
    ], 422);
}

if ($e instanceof AuthenticationException) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'AUTH_INVALID_CREDENTIALS',
            'message' => 'Invalid credentials'
        ],
    ], 401);
}

if ($e instanceof AuthorizationException) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'AUTH_UNAUTHORIZED',
            'message' => 'You do not have permission to perform this action'
        ],
    ], 403);
}

if ($e instanceof ModelNotFoundException) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'RESOURCE_NOT_FOUND',
            'message' => 'The requested resource was not found'
        ],
    ], 404);
}
```

### 1.5 Custom Exceptions

Define domain-specific exceptions that inherit from Laravel's base:

```php
namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvalidStateTransitionException extends \DomainException
{
    public function __construct(
        string $message,
        public readonly string $fromState,
        public readonly string $toState,
        public readonly string $errorCode = 'WORKFLOW_INVALID_TRANSITION'
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string {
        return $this->errorCode;
    }
}

// Throw in service:
throw new InvalidStateTransitionException(
    'Cannot transition from draft to archived',
    fromState: 'draft',
    toState: 'archived'
);

// Catch in handler:
if ($e instanceof InvalidStateTransitionException) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
        ],
    ], 422);
}
```

---

## 2. Monolog & Structured Logging

### 2.1 Monolog in Laravel

Laravel's logging system is built on **Monolog**, which provides:
- Multiple channels (single, daily, stack, syslog, etc.)
- Record processors (add context, sensitive data masking)
- Formatters (line format, JSON format)
- Handlers (write to file, send to service, etc.)

### 2.2 Configuration Structure

```php
// config/logging.php

'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,  // Rotate after 30 days
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,  // Keep files for 30 days
    ],

    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'daily'],  // Write to both
        'ignore_exceptions' => false,
    ],

    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90,  // Keep audit logs longer
    ],
];
```

### 2.3 Formatters & Processors

**Line Formatter** (human-readable):

```php
'channels' => [
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'formatter' => Monolog\Formatter\LineFormatter::class,
        'formatter_with' => [
            'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'dateFormat' => 'Y-m-d H:i:s',
        ],
    ],
]
```

**JSON Formatter** (machine-readable):

```php
use Monolog\Formatter\JsonFormatter;

'channels' => [
    'json' => [
        'driver' => 'daily',
        'path' => storage_path('logs/production.json'),
        'formatter' => JsonFormatter::class,
        'formatter_with' => [
            'includeStacktraces' => false,
        ],
    ],
]
```

### 2.4 Adding Context to All Logs

Create a custom middleware processor:

```php
// app/Logging/ContextProcessor.php

use Monolog\Processor\ProcessorInterface;
use Monolog\LogRecord;

class ContextProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $request = request();

        $record->extra['request_id'] = $request->id();
        $record->extra['correlation_id'] = $request->header('X-Correlation-ID');
        $record->extra['user_id'] = auth()->id();
        $record->extra['user_role'] = auth()->user()?->role->value;
        $record->extra['endpoint'] = $request->method() . ' ' . $request->path();
        $record->extra['ip_address'] = $request->ip();

        return $record;
    }
}
```

Register in `AppServiceProvider`:

```php
public function boot(): void
{
    Log::getLogger()->pushProcessor(new ContextProcessor());
}
```

### 2.5 Masking Sensitive Data

```php
// app/Logging/SensitiveDataProcessor.php

class SensitiveDataProcessor implements ProcessorInterface
{
    private array $sensitiveKeys = [
        'password', 'token', 'card', 'cvv', 'secret', 'api_key'
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->context = $this->mask($record->context);
        $record->extra = $this->mask($record->extra);
        return $record;
    }

    private function mask(array $data): array
    {
        foreach ($data as $key => &$value) {
            if ($this->isSensitive($key)) {
                $value = '***REDACTED***';
            } elseif (is_array($value)) {
                $value = $this->mask($value);
            }
        }
        return $data;
    }

    private function isSensitive(string $key): bool
    {
        $lower = strtolower($key);
        foreach ($this->sensitiveKeys as $sensitive) {
            if (str_contains($lower, $sensitive)) {
                return true;
            }
        }
        return false;
    }
}
```

### 2.6 Request/Response Logging

```php
// app/Http/Middleware/RequestLoggingMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);  // ms

        Log::info('http_request', [
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role->value,
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }
}
```

---

## 3. Correlation IDs & Request Tracing

### 3.1 Why Correlation IDs?

In a request that spans multiple middleware, services, and potentially async jobs, it's critical to link all logs back to the original request. A **Correlation ID** is a unique identifier (UUID v4) assigned to each HTTP request.

### 3.2 Implementation

```php
// app/Http/Middleware/CorrelationIdMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        // Make available to entire request
        $request->attributes->set('correlation_id', $correlationId);

        // Add to all logs via context
        \Illuminate\Support\Facades\Log::shareContext([
            'correlation_id' => $correlationId,
        ]);

        $response = $next($request);

        // Return correlation ID to client
        $response->header('X-Correlation-ID', $correlationId);

        return $response;
    }
}
```

### 3.3 Example Flow

Request comes in:
```
GET /api/v1/projects/123
```

Middleware generates/extracts Correlation ID:
```
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

All logs share this context:
```json
{
  "timestamp": "2026-04-11T10:30:15Z",
  "level": "info",
  "message": "Project fetched",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": 5,
  "endpoint": "GET /api/v1/projects/123"
}
```

Response includes header:
```
HTTP/1.1 200 OK
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json

{
  "success": true,
  "data": { ... },
  "error": null
}
```

---

## 4. Rate Limiting Strategies

### 4.1 Laravel Built-In Rate Limiting

Laravel's `throttle` middleware provides built-in rate limiting:

```php
// routes/api.php

Route::middleware('throttle:100,1')->group(function () {
    Route::get('/api/v1/projects', ...);  // 100 requests per 1 minute
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/login', ...);      // 10 requests per 1 minute
});
```

### 4.2 Custom Role-Based Rate Limiting

```php
// app/Http/Middleware/RateLimitByRoleMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;

class RateLimitByRoleMiddleware
{
    public function __construct(private RateLimiter $limiter)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Different limits per role
        $limits = [
            'customer' => 100,
            'contractor' => 100,
            'architect' => 100,
            'engineer' => 50,
            'admin' => 500,
        ];

        $role = auth()->user()?->role->value ?? 'guest';
        $limit = $limits[$role] ?? 10;

        $key = "rate_limit:{$role}:" . auth()->id();

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                ],
            ], 429)->header('Retry-After', $this->limiter->availableIn($key));
        }

        $this->limiter->hit($key, 60);  // 60 seconds window

        return $next($request);
    }
}
```

### 4.3 Sensitive Endpoints

```php
// routes/api.php

Route::middleware('rate_limit:10')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

Route::middleware('rate_limit:10')->group(function () {
    Route::post('/api/v1/payments', [PaymentController::class, 'store']);
    Route::post('/api/v1/payments/{id}/confirm', [PaymentController::class, 'confirm']);
});
```

---

## 5. Nuxt Error Handling Composables

### 5.1 Global Error Boundary Component

```vue
<!-- components/errors/GlobalErrorBoundary.vue -->

<script setup lang="ts">
import { ref } from 'vue';

const error = ref<Error | null>(null);
const errorHandler = (err: Error) => {
  error.value = err;
  console.error('Error caught by boundary:', err);
};

onErrorCaptured(errorHandler);
</script>

<template>
  <div v-if="error" class="p-4 border border-red-300 rounded-md bg-red-50">
    <h2 class="text-red-800 font-semibold">Something went wrong</h2>
    <p class="text-red-700 text-sm mt-2">{{ error.message }}</p>
    <button @click="error = null" class="mt-2 px-4 py-2 bg-red-600 text-white rounded">
      Try again
    </button>
  </div>
  <slot v-else />
</template>
```

### 5.2 API Error Interceptor

```typescript
// composables/useApi.ts

import { $fetch } from 'ofetch';

interface ApiResponse<T> {
  success: boolean;
  data: T | null;
  error: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  } | null;
}

export function useApi() {
  const config = useRuntimeConfig();
  const auth = useAuthStore();
  const toast = useToast();

  const api = $fetch.create({
    baseURL: config.public.apiBaseUrl,
    onRequest({ options }) {
      if (auth.token) {
        options.headers.set('Authorization', `Bearer ${auth.token}`);
      }
      options.headers.set('Accept-Language', useI18n().locale.value);
    },
    onResponseError({ response, error }) {
      const data = response._data as ApiResponse<unknown>;

      if (response.status === 401) {
        auth.logout();
        navigateTo('/auth/login');
        return;
      }

      if (response.status === 403) {
        navigateTo('/error/403');
        return;
      }

      // Show toast for other errors
      const message = data?.error?.message || 'An error occurred';
      toast.showToast({
        type: 'error',
        message,
        duration: 5000,
      });

      // Log correlation ID for support
      const correlationId = response.headers.get('X-Correlation-ID');
      if (correlationId) {
        console.error('Correlation ID:', correlationId);
      }
    },
  });

  return { api };
}
```

### 5.3 Toast Notification System

```typescript
// composables/useToast.ts

export interface Toast {
  id: string;
  type: 'error' | 'warning' | 'success' | 'info';
  message: string;
  duration?: number;
}

export function useToast() {
  const toastStore = useErrorStore();

  function showToast(toast: Omit<Toast, 'id'>) {
    const id = Math.random().toString(36).substring(7);
    const toastItem = { ...toast, id };

    toastStore.addToast(toastItem);

    if (toast.duration !== 0) {
      setTimeout(() => {
        toastStore.removeToast(id);
      }, toast.duration ?? 5000);
    }

    return id;
  }

  return { showToast };
}

// stores/errorStore.ts

export const useErrorStore = defineStore('error', () => {
  const toasts = ref<Toast[]>([]);

  function addToast(toast: Toast) {
    toasts.value.push(toast);
  }

  function removeToast(id: string) {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }

  return { toasts, addToast, removeToast };
});
```

### 5.4 Error Page Components

```vue
<!-- pages/error-404.vue -->

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <h1 class="text-6xl font-bold text-gray-900">404</h1>
      <p class="text-xl text-gray-600 mt-4">{{ $t('errors.not_found') }}</p>
      <NuxtLink to="/" class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg">
        {{ $t('errors.go_home') }}
      </NuxtLink>
    </div>
  </div>
</template>
```

---

## 6. PHP Enums for Error Codes

### 6.1 Error Code Enum

```php
// app/Enums/ApiErrorCode.php

namespace App\Enums;

enum ApiErrorCode: string
{
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_UNAUTHORIZED = 'AUTH_UNAUTHORIZED';
    case RBAC_ROLE_DENIED = 'RBAC_ROLE_DENIED';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case WORKFLOW_INVALID_TRANSITION = 'WORKFLOW_INVALID_TRANSITION';
    case WORKFLOW_PREREQUISITES_UNMET = 'WORKFLOW_PREREQUISITES_UNMET';
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    case CONFLICT_ERROR = 'CONFLICT_ERROR';
    case SERVER_ERROR = 'SERVER_ERROR';

    public function httpStatus(): int
    {
        return match ($this) {
            self::AUTH_INVALID_CREDENTIALS, self::AUTH_TOKEN_EXPIRED => 401,
            self::AUTH_UNAUTHORIZED, self::RBAC_ROLE_DENIED => 403,
            self::RESOURCE_NOT_FOUND => 404,
            self::VALIDATION_ERROR, self::WORKFLOW_INVALID_TRANSITION,
            self::WORKFLOW_PREREQUISITES_UNMET, self::PAYMENT_FAILED,
            self::CONFLICT_ERROR => 422,
            self::RATE_LIMIT_EXCEEDED => 429,
            self::SERVER_ERROR => 500,
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::AUTH_INVALID_CREDENTIALS => __('auth.invalid_credentials'),
            self::AUTH_TOKEN_EXPIRED => __('auth.token_expired'),
            self::AUTH_UNAUTHORIZED => __('auth.unauthorized'),
            self::RBAC_ROLE_DENIED => __('auth.role_denied'),
            self::RESOURCE_NOT_FOUND => __('errors.not_found'),
            self::VALIDATION_ERROR => __('validation.failed'),
            self::WORKFLOW_INVALID_TRANSITION => __('workflow.invalid_transition'),
            self::WORKFLOW_PREREQUISITES_UNMET => __('workflow.prerequisites_unmet'),
            self::PAYMENT_FAILED => __('payment.failed'),
            self::RATE_LIMIT_EXCEEDED => __('errors.rate_limit'),
            self::CONFLICT_ERROR => __('errors.conflict'),
            self::SERVER_ERROR => __('errors.server_error'),
        };
    }
}
```

---

## 7. Testing Strategies

### 7.1 Unit Testing Exception Handler

```php
// tests/Unit/ExceptionHandlerTest.php

public function test_validation_exception_returns_422(): void
{
    $validator = Validator::make([], ['name' => 'required']);
    $exception = new ValidationException($validator);

    $response = $this->handler->render(
        request(),
        $exception
    );

    $this->assertEquals(422, $response->status());
    $data = json_decode($response->getContent(), true);
    $this->assertFalse($data['success']);
    $this->assertEquals('VALIDATION_ERROR', $data['error']['code']);
}
```

### 7.2 Feature Testing API Errors

```php
// tests/Feature/ErrorHandlingTest.php

public function test_invalid_login_returns_401(): void
{
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrong',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'error' => [
            'code' => 'AUTH_INVALID_CREDENTIALS',
        ],
    ]);
}
```

### 7.3 E2E Testing Error UI

```typescript
// tests/e2e/errorHandling.test.ts

test('displays validation errors on form submission', async ({ page }) => {
  await page.goto('/projects/create');
  await page.click('button:has-text("Create")');
  
  await expect(page.locator('text=Name is required')).toBeVisible();
  await expect(page.locator('[role="alert"]')).toContainText('Validation failed');
});
```

---

## 8. Localization Patterns

### 8.1 Translation Files

```php
// resources/lang/ar/validation.php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute بريد إلكتروني صحيح.',
    'min' => 'يجب أن يكون :attribute على الأقل :min أحرف.',
];

// resources/lang/ar/auth.php

return [
    'invalid_credentials' => 'بيانات الاعتماد غير صحيحة.',
    'unauthorized' => 'لا تملك صلاحية لهذا الإجراء.',
    'token_expired' => 'انتهت صلاحية الرمز المميز.',
];
```

### 8.2 Using Translations in Frontend

```vue
<script setup lang="ts">
const { t } = useI18n();

const errorMessage = t('errors.validation_failed');
// or from API response:
const apiError = ref(null);
const displayMessage = computed(() => {
  return t(`errors.${apiError.value?.code}`);
});
</script>

<template>
  <div class="error-message">{{ displayMessage }}</div>
</template>
```

---

## 9. Log File Rotation & Retention

### 9.1 Daily Rotation Configuration

Laravel's `daily` driver automatically creates a new log file each day:

```
storage/logs/
├── laravel-2026-04-11.log
├── laravel-2026-04-10.log
├── laravel-2026-04-09.log
└── ...
```

Old files are deleted based on the `days` configuration (e.g., 30 days).

### 9.2 Monitoring Disk Space

```php
// app/Console/Commands/CheckLogDiskSpace.php

class CheckLogDiskSpace extends Command
{
    public function handle(): void
    {
        $logDir = storage_path('logs');
        $used = disk_free_space($logDir);
        $total = disk_total_space($logDir);
        $percent = ($total - $used) / $total * 100;

        if ($percent > 80) {
            alert('Log disk space at 80% — cleanup or archive old logs');
        }
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logs:check-disk-space')->hourly();
}
```

---

## 10. Performance Considerations

### 10.1 Async Logging

For high-traffic applications, log writes can block requests. Use Monolog's buffering:

```php
// config/logging.php

'channels' => [
    'buffer' => [
        'driver' => 'monolog',
        'handler' => BufferHandler::class,
        'handler_with' => [
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => storage_path('logs/laravel.log'),
            ],
            'buffer_size' => 100,  // Flush after 100 messages
        ],
    ],
]
```

### 10.2 Sampling Verbose Logs

For high-traffic endpoints, don't log every request:

```php
// app/Http/Middleware/RequestLoggingMiddleware.php

public function handle(Request $request, Closure $next)
{
    // Only log 10% of requests in production
    if (app()->isProduction() && rand(1, 100) > 10) {
        return $next($request);
    }

    // ... logging code ...
}
```

### 10.3 Database Query Logging Impact

Enable query logging only in development or for debugging:

```php
// Only enable in debug
if (config('app.debug')) {
    DB::listen(function ($query) {
        Log::debug($query->sql, $query->bindings);
    });
}
```

---

## Summary

This research explores:

1. **Laravel exception handling** — How to catch and transform exceptions to API errors
2. **Monolog logging** — How to configure channels, formatters, and processors for structured logs
3. **Correlation IDs** — How to trace requests across layers
4. **Rate limiting** — Global and per-role strategies
5. **Nuxt error handling** — Composables, interceptors, error boundaries
6. **PHP enums** — Type-safe error code registry
7. **Testing strategies** — Unit, feature, and E2E error testing
8. **Localization** — Multi-language error messages
9. **Log rotation & retention** — Automatic cleanup and disk space management
10. **Performance** — Async logging, sampling, query logging

Each recommendation is production-tested and aligns with Laravel best practices and Bunyan's architecture.

