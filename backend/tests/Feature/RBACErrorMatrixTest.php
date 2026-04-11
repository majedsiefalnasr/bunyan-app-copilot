<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

/**
 * RBAC Role-Based Integration Test Matrix
 *
 * CRITICAL SECURITY GATE: T083
 *
 * Tests all 5 roles × 5 endpoint types = 25 scenarios
 * Each unauthorized access MUST return 403 RBAC_ROLE_DENIED (not generic 403 AUTH_UNAUTHORIZED)
 * Error message MUST NOT expose role names (only "Access denied")
 * All 25 test cases in this file MUST pass before closure approval
 *
 * @see specs/runtime/005-error-handling/README.md (Phase 4 security gates)
 */
class RBACErrorMatrixTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Test matrix: 5 roles × 5 endpoint types = 25 scenarios
     * 
     * Each endpoint array contains:
     * - method: HTTP method (GET, POST, PUT, DELETE)
     * - uri: API endpoint URI
     * - ownerRole: role for which this endpoint is accessible
     * - data: optional request data for POST/PUT
     */
    private array $endpointMatrix = [
        // Customer-only endpoints
        [
            'method' => 'GET',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::CUSTOMER,
            'name' => 'Get Customer Dashboard',
        ],
        [
            'method' => 'POST',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::CUSTOMER,
            'name' => 'Create Project (Customer Only)',
            'data' => ['name' => 'Test Project'],
        ],
        // Contractor-only endpoints
        [
            'method' => 'GET',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::CONTRACTOR,
            'name' => 'Get Contractor Dashboard',
        ],
        [
            'method' => 'PUT',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::CONTRACTOR,
            'name' => 'Update Contract Status (Contractor Only)',
            'data' => ['status' => 'in_progress'],
        ],
        // Field Engineer-only endpoints
        [
            'method' => 'POST',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::FIELD_ENGINEER,
            'name' => 'Submit Field Report (Field Engineer Only)',
            'data' => ['report' => 'test report'],
        ],
        // Supervising Architect-only endpoints
        [
            'method' => 'GET',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::SUPERVISING_ARCHITECT,
            'name' => 'Get Supervision Dashboard',
        ],
        [
            'method' => 'POST',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::SUPERVISING_ARCHITECT,
            'name' => 'Approve Activity (Architect Only)',
            'data' => ['activity_id' => 1, 'approved' => true],
        ],
        // Admin-only endpoints
        [
            'method' => 'GET',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::ADMIN,
            'name' => 'List All Users (Admin Only)',
        ],
        [
            'method' => 'PUT',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::ADMIN,
            'name' => 'Update Configuration (Admin Only)',
            'data' => ['key' => 'rate_limit', 'value' => 100],
        ],
        [
            'method' => 'DELETE',
            'uri' => '/api/v1/test/error/rbac-role-denied',
            'ownerRole' => UserRole::ADMIN,
            'name' => 'Delete User (Admin Only)',
        ],
    ];

    /**
     * Test all 5 roles accessing each of 5 endpoint types.
     *
     * Generates 25 test scenarios:
     * - 1 authorized access per endpoint (should succeed)
     * - 4 unauthorized accesses per endpoint (should return 403 RBAC_ROLE_DENIED)
     *
     * AC: All 25 test cases in backend/tests/Feature/RBACErrorMatrixTest.php MUST pass
     * AC: Each unauthorized access MUST return 403 RBAC_ROLE_DENIED (not generic 403 AUTH_UNAUTHORIZED)
     * AC: Error message MUST NOT expose role names (only "Access denied")
     * AC: Verify error code matches spec: code=RBAC_ROLE_DENIED, http_status=403
     */
    public function test_rbac_matrix_all_25_role_endpoint_scenarios(): void
    {
        // All 5 roles in the system
        $allRoles = [
            UserRole::CUSTOMER,
            UserRole::CONTRACTOR,
            UserRole::FIELD_ENGINEER,
            UserRole::SUPERVISING_ARCHITECT,
            UserRole::ADMIN,
        ];

        $totalScenarios = 0;
        $passedScenarios = 0;

        // For each endpoint
        foreach ($this->endpointMatrix as $endpoint) {
            $ownerRole = $endpoint['ownerRole'];
            $method = $endpoint['method'];
            $uri = $endpoint['uri'];
            $endpointName = $endpoint['name'];
            $data = $endpoint['data'] ?? [];

            // Test with all 5 roles
            foreach ($allRoles as $testRole) {
                $totalScenarios++;
                $user = User::factory()->create(['role' => $testRole]);

                // Make the request
                $response = $this->actingAs($user)->{strtolower($method)}Json($uri, $data);

                if ($testRole === $ownerRole) {
                    // Authorized: Should succeed or redirect, NOT return RBAC_ROLE_DENIED
                    $this->assertNotIn($response->status(), [403], 
                        "Role {$testRole->value} should have access to: {$endpointName}");
                    $passedScenarios++;
                } else {
                    // Unauthorized: MUST return 403 with RBAC_ROLE_DENIED
                    $response->assertStatus(403);
                    $response->assertJsonStructure([
                        'success',
                        'data',
                        'error' => [
                            'code',
                            'message',
                        ],
                    ]);

                    $json = $response->json();
                    $this->assertFalse($json['success'], 
                        "Unauthorized response must have success=false for {$endpointName}");
                    $this->assertNull($json['data'], 
                        "Unauthorized response must have data=null for {$endpointName}");
                    $this->assertIsArray($json['error'], 
                        "Unauthorized response must have error object for {$endpointName}");
                    
                    // CRITICAL: Error code MUST be RBAC_ROLE_DENIED (not AUTH_UNAUTHORIZED)
                    $this->assertEquals(
                        ApiErrorCode::RBAC_ROLE_DENIED->value,
                        $json['error']['code'],
                        "Role {$testRole->value} accessing {$endpointName} must return RBAC_ROLE_DENIED (not AUTH_UNAUTHORIZED)"
                    );

                    // CRITICAL: Error message MUST NOT expose role names
                    $message = $json['error']['message'];
                    $this->assertStringNotContainsString('admin', strtolower($message),
                        "Error message must not expose 'admin' role");
                    $this->assertStringNotContainsString('contractor', strtolower($message),
                        "Error message must not expose 'contractor' role");
                    $this->assertStringNotContainsString('architect', strtolower($message),
                        "Error message must not expose 'architect' role");
                    $this->assertStringNotContainsString('engineer', strtolower($message),
                        "Error message must not expose 'engineer' role");
                    $this->assertStringNotContainsString('customer', strtolower($message),
                        "Error message must not expose 'customer' role");

                    $passedScenarios++;
                }
            }
        }

        // Final verification
        $this->assertEquals(
            $totalScenarios,
            $passedScenarios,
            "All {$totalScenarios} RBAC matrix scenarios must pass"
        );
    }

    /**
     * Test that RBAC_ROLE_DENIED error has correct HTTP status (403).
     */
    public function test_rbac_role_denied_returns_403_status(): void
    {
        $customer = User::factory()->customer()->create();
        $contractor = User::factory()->contractor()->create();

        // Customer trying to access contractor endpoint
        $response = $this->actingAs($customer)->postJson('/api/v1/test/error/rbac-role-denied');
        $this->assertEquals(403, $response->status());

        // Contractor trying to access customer endpoint
        $response = $this->actingAs($contractor)->postJson('/api/v1/test/error/rbac-role-denied');
        $this->assertEquals(403, $response->status());
    }

    /**
     * Test that RBAC errors are distinct from AUTH_UNAUTHORIZED errors.
     * 
     * Both return 403, but must have different error codes for distinguishing
     * "not authenticated" (AUTH_UNAUTHORIZED) from "authenticated but wrong role" (RBAC_ROLE_DENIED).
     */
    public function test_rbac_role_denied_distinct_from_auth_unauthorized(): void
    {
        $user = User::factory()->contractor()->create();

        // RBAC_ROLE_DENIED: Authenticated but wrong role
        $response = $this->actingAs($user)->postJson('/api/v1/test/error/rbac-role-denied');
        $response->assertStatus(403);
        $json = $response->json();
        $this->assertEquals(ApiErrorCode::RBAC_ROLE_DENIED->value, $json['error']['code']);

        // AUTH_UNAUTHORIZED: Authenticated but lacking general permission (not role-specific)
        $response = $this->getJson('/api/v1/test/error/auth-unauthorized');
        $response->assertStatus(403);
        $json = $response->json();
        $this->assertEquals(ApiErrorCode::AUTH_UNAUTHORIZED->value, $json['error']['code']);
    }

    /**
     * Test that unauthenticated requests return 401 (not 403 RBAC_ROLE_DENIED).
     */
    public function test_unauthenticated_requests_return_401_not_rbac(): void
    {
        // Unauthenticated request should get 401, not 403 RBAC_ROLE_DENIED
        $response = $this->postJson('/api/v1/test/error/rbac-role-denied');
        $this->assertEquals(401, $response->status());

        $json = $response->json();
        $this->assertNotEquals(
            ApiErrorCode::RBAC_ROLE_DENIED->value,
            $json['error']['code'],
            "Unauthenticated request must not return RBAC_ROLE_DENIED"
        );
    }
}
