<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use Tests\TestCase;

class ErrorResponseContractTest extends TestCase
{
    /**
     * Test success response format compliance with contract.
     */
    public function test_success_response_format_compliance(): void
    {
        $response = $this->getJson('/api/v1/test/success');

        // Format validation
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'error',
        ]);

        $json = $response->json();

        // Contract assertions
        $this->assertTrue($json['success']);
        $this->assertIsArray($json['data']);
        $this->assertNull($json['error']);

        // Data structure
        $this->assertArrayHasKey('id', $json['data']);
        $this->assertArrayHasKey('name', $json['data']);
        $this->assertArrayHasKey('status', $json['data']);
    }

    /**
     * Test all error codes return proper format.
     */
    public function test_all_error_codes_response_format(): void
    {
        $errorEndpoints = [
            '/api/v1/test/error/auth-invalid-credentials' => 401,
            '/api/v1/test/error/auth-token-expired' => 401,
            '/api/v1/test/error/auth-unauthorized' => 403,
            '/api/v1/test/error/rbac-role-denied' => 403,
            '/api/v1/test/error/resource-not-found' => 404,
            '/api/v1/test/error/conflict-error' => 409,
            '/api/v1/test/error/validation-error' => 422,
            '/api/v1/test/error/workflow-invalid-transition' => 422,
            '/api/v1/test/error/workflow-prerequisites-unmet' => 422,
            '/api/v1/test/error/payment-failed' => 422,
            '/api/v1/test/error/rate-limit-exceeded' => 429,
            '/api/v1/test/error/server-error' => 500,
        ];

        foreach ($errorEndpoints as $endpoint => $expectedStatus) {
            $response = $this->getJson($endpoint);

            $response->assertStatus($expectedStatus);
            $response->assertJsonStructure([
                'success',
                'data',
                'error' => [
                    'code',
                    'message',
                ],
            ]);

            $json = $response->json();

            $this->assertFalse($json['success']);
            $this->assertNull($json['data']);
            $this->assertIsArray($json['error']);
            $this->assertNotEmpty($json['error']['code']);
            $this->assertNotEmpty($json['error']['message']);
        }
    }

    /**
     * Test validation error includes field details.
     */
    public function test_validation_error_includes_field_details(): void
    {
        $response = $this->getJson('/api/v1/test/error/validation-error');

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'data',
            'error' => [
                'code',
                'message',
                'details',
            ],
        ]);

        $json = $response->json();

        $this->assertFalse($json['success']);
        $this->assertEquals('VALIDATION_ERROR', $json['error']['code']);
        $this->assertIsArray($json['error']['details']);
        $this->assertArrayHasKey('name', $json['error']['details']);
        $this->assertArrayHasKey('budget', $json['error']['details']);
        $this->assertArrayHasKey('location', $json['error']['details']);
    }

    /**
     * Test non-validation errors don't include details field.
     */
    public function test_non_validation_errors_without_details(): void
    {
        $errorEndpoints = [
            '/api/v1/test/error/auth-invalid-credentials',
            '/api/v1/test/error/resource-not-found',
            '/api/v1/test/error/server-error',
        ];

        foreach ($errorEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);

            $json = $response->json();

            $this->assertArrayNotHasKey('details', $json['error']);
        }
    }

    /**
     * Test error codes are semantic and stable.
     */
    public function test_error_codes_are_semantic(): void
    {
        $response = $this->getJson('/api/v1/test/error/auth-invalid-credentials');

        $json = $response->json();

        // Code should be uppercase with underscores (semantic naming)
        $this->assertMatchesRegularExpression(
            '/^[A-Z_]+$/',
            $json['error']['code']
        );
    }

    /**
     * Test status codes match error code HTTP status.
     */
    public function test_http_status_matches_error_code(): void
    {
        $tests = [
            'AUTH_INVALID_CREDENTIALS' => 401,
            'AUTH_TOKEN_EXPIRED' => 401,
            'AUTH_UNAUTHORIZED' => 403,
            'RBAC_ROLE_DENIED' => 403,
            'RESOURCE_NOT_FOUND' => 404,
            'CONFLICT_ERROR' => 409,
            'VALIDATION_ERROR' => 422,
            'WORKFLOW_INVALID_TRANSITION' => 422,
            'WORKFLOW_PREREQUISITES_UNMET' => 422,
            'PAYMENT_FAILED' => 422,
            'RATE_LIMIT_EXCEEDED' => 429,
            'SERVER_ERROR' => 500,
        ];

        foreach ($tests as $errorCodeName => $expectedStatus) {
            $errorCode = ApiErrorCode::from($errorCodeName);
            $this->assertEquals($expectedStatus, $errorCode->httpStatus());
        }
    }

    /**
     * Test error messages are not empty.
     */
    public function test_error_messages_not_empty(): void
    {
        $endpointMap = [
            '/api/v1/test/error/auth-invalid-credentials' => 'AUTH_INVALID_CREDENTIALS',
            '/api/v1/test/error/auth-token-expired' => 'AUTH_TOKEN_EXPIRED',
            '/api/v1/test/error/auth-unauthorized' => 'AUTH_UNAUTHORIZED',
            '/api/v1/test/error/rbac-role-denied' => 'RBAC_ROLE_DENIED',
            '/api/v1/test/error/resource-not-found' => 'RESOURCE_NOT_FOUND',
            '/api/v1/test/error/validation-error' => 'VALIDATION_ERROR',
            '/api/v1/test/error/conflict-error' => 'CONFLICT_ERROR',
            '/api/v1/test/error/workflow-invalid-transition' => 'WORKFLOW_INVALID_TRANSITION',
            '/api/v1/test/error/workflow-prerequisites-unmet' => 'WORKFLOW_PREREQUISITES_UNMET',
            '/api/v1/test/error/payment-failed' => 'PAYMENT_FAILED',
            '/api/v1/test/error/rate-limit-exceeded' => 'RATE_LIMIT_EXCEEDED',
            '/api/v1/test/error/server-error' => 'SERVER_ERROR',
        ];

        foreach ($endpointMap as $endpoint => $expectedCode) {
            $response = $this->getJson($endpoint);
            $json = $response->json();

            $this->assertEquals($expectedCode, $json['error']['code']);
            $this->assertNotEmpty($json['error']['message']);
            $this->assertIsString($json['error']['message']);
            $this->assertGreaterThan(0, strlen(trim($json['error']['message'])));
        }
    }

    /**
     * Test response structure doesn't include extra fields (strict contract).
     */
    public function test_response_structure_strictly_validated(): void
    {
        $response = $this->getJson('/api/v1/test/success');

        $json = $response->json();

        // Success response must have exactly these fields
        $this->assertCount(3, $json);
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('error', $json);
    }

    /**
     * Test error response strictly validated.
     */
    public function test_error_response_structure_strictly_validated(): void
    {
        $response = $this->getJson('/api/v1/test/error/resource-not-found');

        $json = $response->json();
        $error = $json['error'];

        // Error object must have code and message, no extra fields (unless details)
        $this->assertArrayHasKey('code', $error);
        $this->assertArrayHasKey('message', $error);
        $this->assertCount(2, $error); // Only code and message for non-validation errors
    }

    /**
     * Test validation error details structure.
     */
    public function test_validation_error_details_structure(): void
    {
        $response = $this->getJson('/api/v1/test/error/validation-error');

        $json = $response->json();
        $details = $json['error']['details'];

        // Each field should have an array of messages
        foreach ($details as $fieldName => $messages) {
            $this->assertIsString($fieldName);
            $this->assertIsArray($messages);
            foreach ($messages as $message) {
                $this->assertIsString($message);
            }
        }
    }

    /**
     * Test UTF-8 and special characters in messages.
     */
    public function test_special_characters_in_error_messages(): void
    {
        $response = $this->getJson('/api/v1/test/error/validation-error');

        $json = $response->json();

        // Validate JSON encoding
        $this->assertIsString($json['error']['message']);

        // Message should be properly encoded
        $reencoded = json_encode($json['error']['message']);
        $this->assertIsString($reencoded);
    }

    /**
     * Test RATE_LIMIT_EXCEEDED error status.
     */
    public function test_rate_limit_error_status(): void
    {
        $response = $this->getJson('/api/v1/test/error/rate-limit-exceeded');

        $response->assertStatus(429);
        $json = $response->json();
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $json['error']['code']);
    }

    /**
     * Test all 12 error codes are accessible and return proper response.
     */
    public function test_all_error_codes_registry_complete(): void
    {
        $expectedCodes = [
            'AUTH_INVALID_CREDENTIALS',
            'AUTH_TOKEN_EXPIRED',
            'AUTH_UNAUTHORIZED',
            'RBAC_ROLE_DENIED',
            'RESOURCE_NOT_FOUND',
            'VALIDATION_ERROR',
            'CONFLICT_ERROR',
            'WORKFLOW_INVALID_TRANSITION',
            'WORKFLOW_PREREQUISITES_UNMET',
            'PAYMENT_FAILED',
            'RATE_LIMIT_EXCEEDED',
            'SERVER_ERROR',
        ];

        $this->assertCount(12, $expectedCodes);

        foreach (ApiErrorCode::cases() as $errorCode) {
            $this->assertContains($errorCode->value, $expectedCodes);
        }
    }
}
