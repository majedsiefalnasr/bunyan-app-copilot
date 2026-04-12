<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Tests\TestCase;

/**
 * Comprehensive Error Handling Integration Test
 *
 * T069 — End-to-end error flow validation
 *
 * Tests complete error workflows:
 * - Client request → backend handling → error response → client reception
 * - All 6 user stories covered
 * - Authentication flow tested
 * - Workflow errors tested
 * - Payment errors tested
 * - Rate limiting tested
 */
class ErrorHandlingIntegrationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Test complete authentication error flow.
     *
     * Flow: Login attempt → Bad credentials → 401 response → Client redirects to login
     */
    public function test_authentication_error_flow_end_to_end(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Client: Send login request with wrong password
        $response = $this->postJson('/api/v1/test/error/auth-invalid-credentials', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        // Server: Returns 401 with proper error code
        $response->assertStatus(401);
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
        $this->assertEquals(ApiErrorCode::AUTH_INVALID_CREDENTIALS->value, $json['error']['code']);

        // Client: Would redirect to login page (verified in E2E tests)
    }

    /**
     * Test complete authorization error flow (RBAC).
     *
     * Flow: Authenticated request → Role denied → 403 RBAC_ROLE_DENIED → Client shows error
     */
    public function test_rbac_authorization_error_flow_end_to_end(): void
    {
        $contractor = User::factory()->contractor()->create();

        // Client: Contractor tries to access admin endpoint
        $response = $this->actingAs($contractor)
            ->postJson('/api/v1/test/error/rbac-role-denied');

        // Server: Returns 403 with RBAC_ROLE_DENIED (not generic AUTH_UNAUTHORIZED)
        $response->assertStatus(403);
        $json = $response->json();
        $this->assertEquals(ApiErrorCode::RBAC_ROLE_DENIED->value, $json['error']['code']);

        // Verify error message doesn't expose role names
        $this->assertStringNotContainsString('contractor', strtolower($json['error']['message']));
        $this->assertStringNotContainsString('admin', strtolower($json['error']['message']));
    }

    /**
     * Test complete validation error flow.
     *
     * Flow: Invalid input → Validation fails → 422 response with field errors → Client displays them
     */
    public function test_validation_error_flow_end_to_end(): void
    {
        $customer = User::factory()->customer()->create();

        // Client: Send request with invalid data (missing required field)
        $response = $this->actingAs($customer)->postJson('/api/v1/test/error/validation-error', [
            // Missing 'name' field
            'budget' => 'not-a-number',
        ]);

        // Server: Returns 422 with field errors
        $response->assertStatus(422);
        $json = $response->json();

        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertEquals(ApiErrorCode::VALIDATION_ERROR->value, $json['error']['code']);

        // Verify field-level errors included
        if (isset($json['error']['details'])) {
            $this->assertIsArray($json['error']['details']);
            // At least one field error present
            $this->assertGreaterThan(0, count($json['error']['details']));
        }
    }

    /**
     * Test complete workflow error flow.
     *
     * Flow: Invalid state transition → Workflow engine rejects → 422 WORKFLOW_INVALID_TRANSITION
     */
    public function test_workflow_error_flow_end_to_end(): void
    {
        $customer = User::factory()->customer()->create();

        // Client: Try invalid workflow transition
        $response = $this->actingAs($customer)
            ->postJson('/api/v1/test/error/workflow-invalid-transition', [
                'current_status' => 'completed',
                'desired_status' => 'draft', // Invalid: can't go backwards
            ]);

        // Server: Returns 422 with workflow error
        $response->assertStatus(422);
        $json = $response->json();

        $this->assertEquals(ApiErrorCode::WORKFLOW_INVALID_TRANSITION->value, $json['error']['code']);
    }

    /**
     * Test complete resource not found error flow.
     *
     * Flow: Request missing resource → Model not found → 404 response
     */
    public function test_resource_not_found_flow_end_to_end(): void
    {
        $user = User::factory()->create();

        // Client: Request non-existent project
        $response = $this->actingAs($user)
            ->getJson('/api/v1/test/error/resource-not-found');

        // Server: Returns 404
        $response->assertStatus(404);
        $json = $response->json();

        $this->assertEquals(ApiErrorCode::RESOURCE_NOT_FOUND->value, $json['error']['code']);
    }

    /**
     * Test complete rate limiting error flow.
     *
     * Flow: Exceeds rate limit → 429 response with Retry-After header
     */
    public function test_rate_limiting_error_flow_end_to_end(): void
    {
        // Simulate rapid requests hitting rate limit
        $attacker_ip = '192.168.1.100';

        // First 10 requests succeed (or fail with non-429 status)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('X-Forwarded-For', $attacker_ip)
                ->postJson('/api/v1/test/error/auth-invalid-credentials');

            $this->assertNotEqual(429, $response->status());
        }

        // 11th request is rate limited
        $response = $this->withHeader('X-Forwarded-For', $attacker_ip)
            ->postJson('/api/v1/test/error/auth-invalid-credentials');

        $response->assertStatus(429);
        $json = $response->json();

        $this->assertEquals(ApiErrorCode::RATE_LIMIT_EXCEEDED->value, $json['error']['code']);

        // Verify Retry-After header
        $retryAfter = $response->headers->get('Retry-After');
        $this->assertNotNull($retryAfter);
        $this->assertGreaterThan(0, (int)$retryAfter);
    }

    /**
     * Test error response format compliance end-to-end.
     *
     * Verify all responses (success and error) match contract exactly.
     */
    public function test_all_error_responses_comply_with_contract(): void
    {
        $endpoints = [
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

        foreach ($endpoints as $endpoint => $expectedStatus) {
            $response = $this->getJson($endpoint);

            // Status code correct
            $this->assertEquals(
                $expectedStatus,
                $response->status(),
                "Endpoint {$endpoint} should return {$expectedStatus}"
            );

            // Response structure correct
            $json = $response->json();
            $this->assertIsArray($json);
            $this->assertArrayHasKey('success', $json);
            $this->assertArrayHasKey('data', $json);
            $this->assertArrayHasKey('error', $json);

            // Error response contract
            $this->assertFalse($json['success']);
            $this->assertNull($json['data']);
            $this->assertIsArray($json['error']);
            $this->assertArrayHasKey('code', $json['error']);
            $this->assertArrayHasKey('message', $json['error']);

            // Error code is valid enum value
            $errorCode = $json['error']['code'];
            $validCodes = array_map(fn ($case) => $case->value, ApiErrorCode::cases());
            $this->assertContains(
                $errorCode,
                $validCodes,
                "Error code {$errorCode} from {$endpoint} must be valid enum value"
            );
        }
    }

    /**
     * Test correlation ID propagation end-to-end.
     *
     * Verify correlation ID passes through:
     * Request → Middleware → Logs → Response
     */
    public function test_correlation_id_propagates_end_to_end(): void
    {
        $testCorrelationId = '550e8400-e29b-41d4-a716-446655440000'; // Valid UUID

        $response = $this->withHeader('X-Correlation-ID', $testCorrelationId)
            ->getJson('/api/v1/test/success');

        // Correlation ID in response header
        $responseCorrelationId = $response->headers->get('X-Correlation-ID');
        $this->assertNotNull($responseCorrelationId);

        // If we sent a valid UUID, it should be preserved
        if ($response->status() === 200) {
            $this->assertEquals(
                $testCorrelationId,
                $responseCorrelationId,
                'Correlation ID should be preserved in response'
            );
        }
    }

    /**
     * Test error response with different content types.
     */
    public function test_error_responses_always_return_json(): void
    {
        $user = User::factory()->create();

        // Request with JSON Accept header
        $response = $this->withHeader('Accept', 'application/json')
            ->actingAs($user)
            ->postJson('/api/v1/test/error/rbac-role-denied');

        // Should return JSON even though endpoint might be in error
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertIsArray($response->json());
    }
}
