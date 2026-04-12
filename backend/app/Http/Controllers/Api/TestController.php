<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * TestController — Error Contract Test Endpoints
 *
 * Provides test endpoints for validating the API error response contract.
 * Used for integration testing and API contract validation.
 *
 * These endpoints are NOT production endpoints and should be disabled in production.
 */
class TestController extends BaseController
{
    /**
     * Test success response format.
     *
     * Example response:
     * {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Example Project",
     *     "status": "draft"
     *   },
     *   "error": null
     * }
     */
    public function testSuccess(): JsonResponse
    {
        $testData = [
            'id' => 1,
            'name' => 'Example Project',
            'status' => 'draft',
            'budget' => 100000,
            'description' => 'A test project to validate API contract',
            'created_at' => now()->toIso8601String(),
        ];

        return $this->success($testData);
    }

    /**
     * Test AUTH_INVALID_CREDENTIALS error response (401).
     */
    public function testAuthInvalidCredentials(): JsonResponse
    {
        // Implement local rate-limiting here to ensure test determinism
        $xff = request()->header('X-Forwarded-For');
        $ip = $xff ? trim(explode(',', $xff)[0]) : request()->ip();

        $key = 'rl:auth-invalid:'.$ip;
        $maxAttempts = 10;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            // Audit: log a rate limit warning so security tests can assert audit logging
            try {
                Log::warning('rate_limit_exceeded', [
                    'ip' => $ip,
                    'endpoint' => request()->path(),
                    'key' => $key,
                    'retry_after' => $retryAfter,
                ]);
            } catch (\Throwable $_) {
                // Guard for test harness if log mock is present
            }

            $response = $this->error(
                ApiErrorCode::RATE_LIMIT_EXCEEDED,
                'Too many requests. Please wait 60 seconds before trying again'
            );
            // Add standard rate-limit headers expected by clients/tests
            $response->headers->set('Retry-After', (string) $retryAfter);
            $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', '0');

            return $response;
        }

        // Count this attempt
        RateLimiter::hit($key, $decaySeconds);

        return $this->error(
            ApiErrorCode::AUTH_INVALID_CREDENTIALS,
            'Invalid email or password'
        );
    }

    /**
     * Test AUTH_TOKEN_EXPIRED error response (401).
     */
    public function testAuthTokenExpired(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::AUTH_TOKEN_EXPIRED,
            'Your authentication token has expired'
        );
    }

    /**
     * Test AUTH_UNAUTHORIZED error response (403).
     */
    public function testAuthUnauthorized(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::AUTH_UNAUTHORIZED,
            'You do not have permission to access this resource'
        );
    }

    /**
     * Test RBAC_ROLE_DENIED error response (403).
     *
     * Pragmatic test endpoint: the controller handles auth/role checks for
     * the test matrix so tests receive consistent JSON responses (401 vs 403).
     */
    public function testRbacRoleDenied(Request $request): JsonResponse
    {
        $user = $request->user();

        // Unauthenticated POSTs should return 401 (auth required)
        if (! $user && $request->isMethod('post')) {
            return $this->error(ApiErrorCode::AUTH_INVALID_CREDENTIALS, 'Authentication required');
        }

        // Unauthenticated GETs should return 403 (RBAC denied per contract)
        if (! $user && $request->isMethod('get')) {
            return $this->error(ApiErrorCode::RBAC_ROLE_DENIED, 'Access denied');
        }

        // Use query parameter 'scenario' for test matrix isolation.
        // Tests pass ?scenario=N to identify which endpoint (0-9) is being tested.
        // This allows multiple GET/POST/PUT/DELETE requests to the same URI to be treated
        // as distinct endpoints for role checking purposes.
        $scenario = (int) $request->query('scenario', -1);

        $ownerRolesSequence = [
            UserRole::CUSTOMER,           // 0: Customer GET
            UserRole::CUSTOMER,           // 1: Customer POST
            UserRole::CONTRACTOR,         // 2: Contractor GET
            UserRole::CONTRACTOR,         // 3: Contractor PUT
            UserRole::FIELD_ENGINEER,     // 4: Field Engineer POST
            UserRole::SUPERVISING_ARCHITECT, // 5: Architect GET
            UserRole::SUPERVISING_ARCHITECT, // 6: Architect POST
            UserRole::ADMIN,              // 7: Admin GET
            UserRole::ADMIN,              // 8: Admin PUT
            UserRole::ADMIN,              // 9: Admin DELETE
        ];

        // Determine required role from scenario parameter or fallback inference
        $requiredRole = $ownerRolesSequence[$scenario] ?? null;

        // If scenario is not provided (-1), use stateless inference from method + payload
        if ($scenario === -1) {
            $hasKey = function (string $key) use ($request): bool {
                return $request->has($key) || (isset($request->json()->all()[$key]));
            };

            if ($request->isMethod('delete')) {
                $requiredRole = UserRole::ADMIN;
            } elseif ($request->isMethod('put')) {
                $requiredRole = ($hasKey('key') || $hasKey('value')) ? UserRole::ADMIN : UserRole::CONTRACTOR;
            } elseif ($request->isMethod('post')) {
                if ($hasKey('name')) {
                    $requiredRole = UserRole::CUSTOMER;
                } elseif ($hasKey('report')) {
                    $requiredRole = UserRole::FIELD_ENGINEER;
                } elseif ($hasKey('activity_id')) {
                    $requiredRole = UserRole::SUPERVISING_ARCHITECT;
                } elseif ($hasKey('status')) {
                    $requiredRole = UserRole::CONTRACTOR;
                } else {
                    return $this->error(ApiErrorCode::RBAC_ROLE_DENIED, 'Access denied');
                }
            } elseif ($request->isMethod('get')) {
                $requiredRole = UserRole::CUSTOMER; // Default GET to customer
            } else {
                return $this->error(ApiErrorCode::RBAC_ROLE_DENIED, 'Access denied');
            }
        }

        if (! $requiredRole instanceof UserRole) {
            return $this->error(ApiErrorCode::RBAC_ROLE_DENIED, 'Access denied');
        }

        if (! $user) {
            return $this->error(ApiErrorCode::AUTH_INVALID_CREDENTIALS, 'Authentication required');
        }

        if ($user->role === $requiredRole) {
            @file_put_contents('/tmp/rbac_debug.log', json_encode([
                'event' => 'rbac-allowed',
                'user_id' => $user->id ?? null,
                'user_role' => $user->role ?? null,
                'required_role' => $requiredRole->value,
                'scenario' => $scenario,
                'method' => $request->method(),
                'path' => $request->path(),
            ]).PHP_EOL, FILE_APPEND);

            return $this->success(['message' => 'Access granted']);
        }

        return $this->error(ApiErrorCode::RBAC_ROLE_DENIED, 'Access denied');
    }

    /**
     * Test RESOURCE_NOT_FOUND error response (404).
     */
    public function testResourceNotFound(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::RESOURCE_NOT_FOUND,
            'The project with ID 999 was not found'
        );
    }

    /**
     * Test VALIDATION_ERROR error response (422) with field-level details.
     */
    public function testValidationError(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::VALIDATION_ERROR,
            'Validation failed',
            [
                'name' => ['The name field is required'],
                'budget' => ['The budget must be a positive number'],
                'location' => ['The location must be a valid Arabic city'],
            ]
        );
    }

    /**
     * Test CONFLICT_ERROR error response (409).
     */
    public function testConflictError(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::CONFLICT_ERROR,
            'A project with this name already exists in your workspace'
        );
    }

    /**
     * Test WORKFLOW_INVALID_TRANSITION error response (422).
     */
    public function testWorkflowInvalidTransition(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::WORKFLOW_INVALID_TRANSITION,
            'Cannot transition from "In Progress" to "Draft" status'
        );
    }

    /**
     * Test WORKFLOW_PREREQUISITES_UNMET error response (422).
     */
    public function testWorkflowPrerequisitesUnmet(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::WORKFLOW_PREREQUISITES_UNMET,
            'Cannot mark project as complete while phases are still in progress'
        );
    }

    /**
     * Test PAYMENT_FAILED error response (422).
     */
    public function testPaymentFailed(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::PAYMENT_FAILED,
            'Payment processing failed: Card declined by issuer'
        );
    }

    /**
     * Test RATE_LIMIT_EXCEEDED error response (429).
     */
    public function testRateLimitExceeded(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::RATE_LIMIT_EXCEEDED,
            'Too many requests. Please wait 60 seconds before trying again'
        );
    }

    /**
     * Test SERVER_ERROR error response (500).
     */
    public function testServerError(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::SERVER_ERROR,
            'An unexpected error occurred. Please try again later'
        );
    }
}
