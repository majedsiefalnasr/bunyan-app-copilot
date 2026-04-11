<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

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
     */
    public function testRbacRoleDenied(): JsonResponse
    {
        return $this->error(
            ApiErrorCode::RBAC_ROLE_DENIED,
            'Your role is not authorized for this action'
        );
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
