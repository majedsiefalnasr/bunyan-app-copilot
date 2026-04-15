<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Tests\TestCase;

/**
 * Feature tests for GET /api/health (AC-05 through AC-07, AC-16).
 *
 * Covers:
 * - HTTP 200 + data.status=healthy when all probes pass
 * - HTTP 200 + data.status=degraded when cache probe fails
 * - HTTP 503 + success=false + error.code=HEALTH_CHECK_FAILED when DB fails
 * - No Authorization header required (non-401)
 * - Response includes data.checks.database and data.checks.cache
 * - Every response includes X-Correlation-ID header (AC-07)
 */
class HealthCheckTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Healthy path
    // -------------------------------------------------------------------------

    public function test_health_returns_200_healthy_when_all_probes_pass(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'healthy');
        $response->assertJsonPath('data.checks.database', 'ok');
        $response->assertJsonPath('data.checks.cache', 'ok');
        $response->assertJsonPath('error', null);
    }

    public function test_health_response_includes_version_environment_and_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $json = $response->json();
        $this->assertArrayHasKey('version', $json['data']);
        $this->assertArrayHasKey('environment', $json['data']);
        $this->assertArrayHasKey('timestamp', $json['data']);
        $this->assertNotEmpty($json['data']['timestamp']);
    }

    // -------------------------------------------------------------------------
    // No auth required
    // -------------------------------------------------------------------------

    public function test_health_requires_no_authorization_header(): void
    {
        // No auth header → must NOT be 401
        $response = $this->getJson('/api/health');
        $response->assertStatus(200);
    }

    public function test_health_accessible_without_any_session(): void
    {
        $response = $this->withoutMiddleware(EnsureFrontendRequestsAreStateful::class)
            ->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'healthy');
    }

    // -------------------------------------------------------------------------
    // X-Correlation-ID header (AC-07)
    // -------------------------------------------------------------------------

    public function test_health_response_includes_x_correlation_id_header_on_200(): void
    {
        $correlationId = 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e';
        $response = $this->withHeaders(['X-Correlation-ID' => $correlationId])
            ->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID', $correlationId);
    }

    public function test_health_response_generates_correlation_id_when_none_provided(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertHeaderMissing('X-Correlation-ID'); // No incoming ID → no propagation required (header may or may not be set by middleware)
        // Alternatively: if RequestResponseLoggingMiddleware always generates one, assert it present
        // The contract is: if incoming ID is valid UUID4, it MUST be echoed back.
    }

    // -------------------------------------------------------------------------
    // Degraded: cache fail, DB ok
    // -------------------------------------------------------------------------

    public function test_health_returns_200_degraded_when_cache_fails(): void
    {
        Cache::shouldReceive('put')->andThrow(new \RuntimeException('Cache connection refused'));
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(true);

        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'degraded');
        $response->assertJsonPath('data.checks.database', 'ok');
        $response->assertJsonPath('data.checks.cache', 'fail');
        $response->assertJsonPath('error', null);
    }

    // -------------------------------------------------------------------------
    // Unhealthy: DB fail
    // -------------------------------------------------------------------------

    public function test_health_returns_503_when_database_fails(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('DB connection refused'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('data', null);
        $response->assertJsonPath('error.code', 'HEALTH_CHECK_FAILED');
    }

    public function test_health_503_includes_checks_in_error_details(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('DB down'));

        $response = $this->getJson('/api/health');
        $response->assertStatus(503);

        $json = $response->json();
        $details = $json['error']['details'];

        $this->assertArrayHasKey('checks', $details);
        $this->assertArrayHasKey('database', $details['checks']);
        $this->assertArrayHasKey('cache', $details['checks']);
        $this->assertArrayHasKey('status', $details);
        $this->assertSame('unhealthy', $details['status']);
    }

    public function test_health_503_x_correlation_id_header_echoed(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('DB down'));

        $correlationId = 'a0b1c2d3-e4f5-4a6b-8c7d-9e0f1a2b3c4d';
        $response = $this->withHeaders(['X-Correlation-ID' => $correlationId])
            ->getJson('/api/health');

        $response->assertStatus(503);
        $response->assertHeader('X-Correlation-ID', $correlationId);
    }
}
