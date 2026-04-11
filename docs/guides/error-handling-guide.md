# Error Handling Implementation Guide — Bunyan

**Comprehensive guide for implementing error handling across backend and frontend.**

## Table of Contents

1. [Backend Patterns](#backend-patterns)
2. [Frontend Patterns](#frontend-patterns)
3. [Testing Strategies](#testing-strategies)
4. [Localization](#localization)
5. [Migration Guide](#migration-guide)
6. [Best Practices](#best-practices)

---

## Backend Patterns

### 1. API Response Contract

All API responses must follow the unified contract:

```php
{
  "success": true|false,
  "data": {...} | null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly message",
    "details": {...}
  } | null
}
```

### 2. Using ApiResponseTrait

**Step 1: Extend BaseController**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ProjectController extends BaseController
{
    // Automatically gets success() and error() methods from BaseController
}
```

**Step 2: Return Success Response**

```php
public function store(): JsonResponse
{
    $validated = request()->validate([
        'name' => 'required|string',
        'budget' => 'required|numeric|min:0',
    ]);

    $project = Project::create($validated);

    // Success response (defaults to 200 status)
    return $this->success($project, 'Project created', 201);
}
```

**Step 3: Return Error Response**

```php
public function update(Project $project): JsonResponse
{
    try {
        // Your logic here
    } catch (InvalidStateException $e) {
        // Error response
        return $this->error(
            ApiErrorCode::WORKFLOW_INVALID_TRANSITION,
            'Cannot transition to this status'
        );
    }
}
```

### 3. Exception Handler

The global exception handler (`app/Exceptions/Handler.php`) automatically catches exceptions and returns proper error responses.

**Supported Exception Types:**

```php
// ValidationException → 422 VALIDATION_ERROR (with field details)
if ($e instanceof ValidationException) {
    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'details' => $e->errors(),
        ],
    ], 422);
}

// AuthenticationException → 401 AUTH_UNAUTHORIZED or AUTH_TOKEN_EXPIRED
if ($e instanceof AuthenticationException) {
    $code = $e->getMessage() === 'token_expired' 
        ? ApiErrorCode::AUTH_TOKEN_EXPIRED 
        : ApiErrorCode::AUTH_INVALID_CREDENTIALS;
    
    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => $code->value,
            'message' => $code->defaultMessage(),
        ],
    ], $code->httpStatus());
}

// AuthorizationException → 403 AUTH_UNAUTHORIZED (generic, not role-specific)
if ($e instanceof AuthorizationException) {
    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'AUTH_UNAUTHORIZED',
            'message' => 'You do not have permission',
        ],
    ], 403);
}

// RoleNotAllowedException → 403 RBAC_ROLE_DENIED (role-specific)
if ($e instanceof RoleNotAllowedException) {
    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'RBAC_ROLE_DENIED',
            'message' => 'Your role cannot perform this action',
        ],
    ], 403);
}

// ModelNotFoundException → 404 RESOURCE_NOT_FOUND
if ($e instanceof ModelNotFoundException) {
    return response()->json([
        'success' => false,
        'data' => null,
        'error' => [
            'code' => 'RESOURCE_NOT_FOUND',
            'message' => 'The requested resource was not found',
        ],
    ], 404);
}

// Unhandled exception → 500 SERVER_ERROR (NO stack trace to client)
return response()->json([
    'success' => false,
    'data' => null,
    'error' => [
        'code' => 'SERVER_ERROR',
        'message' => 'Something went wrong. Please try again.',
    ],
], 500);
```

### 4. Validation Error Handling

Use Form Requests for consistent validation:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0|max:999999999',
            'location' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required',
            'budget.numeric' => 'Budget must be a number',
        ];
    }
}
```

In your controller:

```php
public function store(StoreProjectRequest $request): JsonResponse
{
    // Validation happens automatically; 422 response if invalid
    $validated = $request->validated();
    
    $project = Project::create($validated);
    return $this->success($project, 'Project created', 201);
}
```

### 5. RBAC Error Handling

Implement role-based access control with policies:

```php
<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Enums\UserRole;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return match ($user->role) {
            UserRole::ADMIN => true,
            UserRole::CUSTOMER => $project->customer_id === $user->id,
            UserRole::CONTRACTOR => $project->contractor_id === $user->id,
            UserRole::SUPERVISING_ARCHITECT => $project->supervising_architect_id === $user->id,
            default => false,
        };
    }

    public function update(User $user, Project $project): bool
    {
        // Only admins and customers can update
        return $user->hasAnyRole(UserRole::ADMIN, UserRole::CUSTOMER);
    }
}
```

In your controller:

```php
public function show(Project $project): JsonResponse
{
    // Throws AuthorizationException if unauthorized
    $this->authorize('view', $project);
    
    return $this->success($project);
}

public function update(Project $project): JsonResponse
{
    $this->authorize('update', $project);
    
    $validated = request()->validate([...]);
    $project->update($validated);
    
    return $this->success($project);
}
```

### 6. Workflow Error Handling

Manage workflow state transitions safely:

```php
<?php

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Project;

class ProjectWorkflowService
{
    public function transitionTo(Project $project, string $newStatus): Project
    {
        // Check if transition is allowed
        if (!$this->isTransitionAllowed($project->status, $newStatus)) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$project->status} to {$newStatus}",
                $project->status,
                $newStatus
            );
        }

        // Check prerequisites
        if (!$this->checkPrerequisites($project, $newStatus)) {
            throw (new InvalidStateTransitionException(
                "Prerequisites not met for {$newStatus}"
            ))->setCode(ApiErrorCode::WORKFLOW_PREREQUISITES_UNMET);
        }

        $project->update(['status' => $newStatus]);
        return $project->refresh();
    }

    private function isTransitionAllowed(string $from, string $to): bool
    {
        $allowed = [
            'draft' => ['active', 'cancelled'],
            'active' => ['completed', 'paused', 'cancelled'],
            'paused' => ['active', 'completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        return in_array($to, $allowed[$from] ?? []);
    }

    private function checkPrerequisites(Project $project, string $newStatus): bool
    {
        return match ($newStatus) {
            'completed' => $project->allTasksCompleted(),
            'active' => $project->hasApprovedBudget(),
            default => true,
        };
    }
}
```

In your controller:

```php
public function updateStatus(Project $project, UpdateProjectStatusRequest $request): JsonResponse
{
    try {
        $project = app(ProjectWorkflowService::class)
            ->transitionTo($project, $request->status);

        return $this->success($project);
    } catch (InvalidStateTransitionException $e) {
        return $this->error(
            $e->getCode() ?? ApiErrorCode::WORKFLOW_INVALID_TRANSITION,
            $e->getMessage()
        );
    }
}
```

### 7. Logging with Correlation IDs

Request/response logging is automatic, but you can add custom logging:

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;

class ProjectController extends BaseController
{
    public function store(): JsonResponse
    {
        $correlationId = request()->correlationId();
        
        try {
            Log::info('Creating project', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
                'endpoint' => 'POST /api/v1/projects',
            ]);

            // ... create project
            
            Log::info('Project created', [
                'correlation_id' => $correlationId,
                'project_id' => $project->id,
            ]);

            return $this->success($project);
        } catch (\Exception $e) {
            Log::error('Failed to create project', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                ApiErrorCode::SERVER_ERROR,
                'Failed to create project'
            );
        }
    }
}
```

---

## Frontend Patterns

### 1. useApi Composable with Error Interception

```typescript
// composables/useApi.ts
import { useRouter } from 'vue-router'
import { useToast } from './useToast'
import { useErrorHandler } from './useErrorHandler'

export const useApi = () => {
  const router = useRouter()
  const { showToast } = useToast()
  const { handleError } = useErrorHandler()

  // Setup axios/fetch interceptors
  const $fetch = $fetch.create({
    onResponse({ response }) {
      if (response.status >= 400) {
        const error = response._data

        // Handle specific error codes
        if (error.error?.code === 'AUTH_TOKEN_EXPIRED') {
          router.push('/login')
        } else if (error.error?.code === 'RBAC_ROLE_DENIED') {
          showToast('Access denied', 'error')
        } else if (error.error?.code === 'RATE_LIMIT_EXCEEDED') {
          showToast('Too many requests. Please wait a moment.', 'warning')
        }
      }
    },
    onError({ error }) {
      handleError(error)
    },
  })

  return { $fetch }
}
```

### 2. Error Boundary Component

```vue
<!-- components/GlobalErrorBoundary.vue -->
<script setup lang="ts">
import { onErrorCaptured, ref } from 'vue'

const error = ref(null)
const showError = ref(false)

onErrorCaptured((err) => {
  error.value = err
  showError.value = true

  // Log error with correlation ID
  console.error('Component error:', {
    correlation_id: window.__CORRELATION_ID__,
    error: err.message,
  })

  return false // Prevent propagation
})

const reload = () => {
  window.location.reload()
}

const goBack = () => {
  window.history.back()
}
</script>

<template>
  <div>
    <slot v-if="!showError" />

    <div v-else class="error-boundary">
      <UCard>
        <h1>Something went wrong</h1>
        <p>{{ error?.message || 'An unexpected error occurred' }}</p>
        <div class="actions">
          <UButton @click="reload">Reload Page</UButton>
          <UButton variant="secondary" @click="goBack">Go Back</UButton>
        </div>
      </UCard>
    </div>
  </div>
</template>
```

### 3. useErrorHandler Composable

```typescript
// composables/useErrorHandler.ts
import { useRouter } from 'vue-router'
import { useToast } from './useToast'

export const useErrorHandler = () => {
  const router = useRouter()
  const { showToast } = useToast()
  const { $t } = useI18n()

  const handleError = (error: any, context?: any) => {
    const errorCode = error?.error?.code || error?.status

    switch (errorCode) {
      case 'AUTH_INVALID_CREDENTIALS':
        showToast($t('errors.auth_invalid_credentials'), 'error')
        router.push('/login')
        break

      case 'AUTH_TOKEN_EXPIRED':
        showToast($t('errors.auth_token_expired'), 'error')
        router.push('/login')
        break

      case 'RBAC_ROLE_DENIED':
        showToast($t('errors.access_denied'), 'error')
        router.push('/error-403')
        break

      case 'RESOURCE_NOT_FOUND':
      case 404:
        showToast($t('errors.not_found'), 'error')
        router.push('/error-404')
        break

      case 'VALIDATION_ERROR':
        if (error.error?.details) {
          // Display field-level errors
          displayFieldErrors(error.error.details)
        } else {
          showToast(error.error?.message || $t('errors.validation_error'), 'error')
        }
        break

      case 'RATE_LIMIT_EXCEEDED':
      case 429:
        showToast($t('errors.rate_limit_exceeded'), 'warning')
        break

      case 'SERVER_ERROR':
      case 500:
        showToast($t('errors.server_error'), 'error')
        break

      default:
        showToast(
          error?.error?.message || context?.fallback || $t('errors.unknown'),
          'error'
        )
    }
  }

  return { handleError }
}
```

---

## Testing Strategies

### Backend Testing

**Feature Test Example:**

```php
class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_project(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->postJson('/api/v1/projects', [
            'name' => 'Test Project',
            'budget' => 100000,
            'location' => 'Riyadh',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'error' => null,
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'status'],
            ]);
    }

    public function test_contractor_cannot_create_project(): void
    {
        $contractor = User::factory()->contractor()->create();

        $response = $this->actingAs($contractor)->postJson('/api/v1/projects', [
            'name' => 'Test',
            'budget' => 100000,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RBAC_ROLE_DENIED',
                ],
            ]);
    }
}
```

### Frontend Testing

**Vitest Example:**

```typescript
import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { useErrorHandler } from '~/composables/useErrorHandler'

describe('useErrorHandler', () => {
  it('handles RBAC_ROLE_DENIED error', () => {
    const { handleError } = useErrorHandler()

    const error = {
      error: {
        code: 'RBAC_ROLE_DENIED',
        message: 'Access denied',
      },
    }

    expect(() => {
      handleError(error)
    }).not.toThrow()
  })

  it('handles validation errors with field details', () => {
    const { handleError } = useErrorHandler()

    const error = {
      error: {
        code: 'VALIDATION_ERROR',
        details: {
          name: ['Name is required'],
          budget: ['Budget must be positive'],
        },
      },
    }

    handleError(error)
    // Verify field errors displayed
  })
})
```

---

## Localization

### i18n Setup

**ar.json:**
```json
{
  "errors": {
    "validation_error": "بيانات غير صحيحة",
    "auth_invalid_credentials": "بيانات دخول غير صحيحة",
    "auth_token_expired": "انتهت جلستك",
    "access_denied": "دورك الحالي لا يسمح بهذا الإجراء",
    "not_found": "المورد غير موجود",
    "rate_limit_exceeded": "عدد طلبات كبير جداً",
    "server_error": "حدث خطأ غير متوقع"
  }
}
```

**en.json:**
```json
{
  "errors": {
    "validation_error": "Invalid input data",
    "auth_invalid_credentials": "Invalid credentials",
    "auth_token_expired": "Your session has expired",
    "access_denied": "Access denied",
    "not_found": "Not found",
    "rate_limit_exceeded": "Too many requests",
    "server_error": "Something went wrong"
  }
}
```

---

## Migration Guide

### Migrating Existing Endpoints

**Before (Old Pattern):**
```php
public function show($id)
{
    try {
        $project = Project::findOrFail($id);
        return response()->json($project);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Not found'], 404);
    }
}
```

**After (New Pattern):**
```php
public function show(Project $project): JsonResponse
{
    $this->authorize('view', $project);
    return $this->success($project);
}
```

### Key Changes:

1. ✅ Use type-hinted model injection (Larvel handles 404 automatically)
2. ✅ Use `$this->success()` and `$this->error()` methods
3. ✅ Add authorization checks with `$this->authorize()`
4. ✅ Return JsonResponse explicitly
5. ✅ Remove manual error handling (exception handler catches it)

---

## Best Practices

### 1. Error Codes Are Semantic and Stable

```php
// ✅ DO: Use existing error codes
if ($failed) {
    return $this->error(ApiErrorCode::WORKFLOW_INVALID_TRANSITION, '...');
}

// ❌ DON'T: Create new error codes dynamically
return response()->json(['error' => 'MY_CUSTOM_ERROR'], 422);
```

### 2. Don't Expose Internal Details

```php
// ✅ DO: Generic message for client
return $this->error(
    ApiErrorCode::PAYMENT_FAILED,
    'Payment processing failed'
);

// ❌ DON'T: Expose payment provider details
return $this->error(
    ApiErrorCode::PAYMENT_FAILED,
    'Stripe error: card_declined - Your card was declined'
);
```

### 3. Always Include Correlation ID in Logs

```php
// ✅ DO
Log::error('Payment failed', [
    'correlation_id' => request()->correlationId(),
    'user_id' => auth()->id(),
]);

// ❌ DON'T
Log::error('Payment failed'); // No traceability
```

### 4. Distinguish RBAC_ROLE_DENIED from AUTH_UNAUTHORIZED

```php
// ✅ DO: RBAC_ROLE_DENIED for role-based checks
if (!$user->hasEnumRole(UserRole::ADMIN)) {
    throw new RoleNotAllowedException();
}

// ✅ DO: AUTH_UNAUTHORIZED for general permission checks
if (!$user->can('edit-report')) {
    throw new AuthorizationException();
}
```

### 5. Validate Before Processing

```php
// ✅ DO: Validate early
$validated = request()->validate([...]);
$project = Project::create($validated);

// ❌ DON'T: Validate during processing
$project = Project::create(request()->all());
```

---

## References

- [Error Codes Reference](../api/error-codes.md)
- [Quickstart Guide](../runtime/005-error-handling/quickstart.md)
- [API ResponseTrait Code](../backend/app/Traits/ApiResponseTrait.php)
- [Exception Handler Code](../backend/app/Exceptions/Handler.php)
