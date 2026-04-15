<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * HealthController — Platform Health Check
 *
 * Provides a public health-check endpoint at GET /api/health (no auth, no throttle).
 * Probes the database and cache layer, returning a structured health status.
 *
 * Response codes:
 * - HTTP 200 + status=healthy  : All probes pass
 * - HTTP 200 + status=degraded : DB ok, cache fails
 * - HTTP 503 + HEALTH_CHECK_FAILED : DB probe fails
 *
 * Performance goal: NFR-001 ≤ 200ms p95
 *
 * @see specs/runtime/006-api-foundation/contracts/api-health-response.json
 */
class HealthController extends BaseController
{
    /**
     * Perform platform health check.
     *
     * Probes database and cache layer. If the database fails, returns HTTP 503
     * with HEALTH_CHECK_FAILED error contract. Cache-only failure returns 200 degraded.
     */
    #[OA\Get(
        path: '/health',
        summary: 'Platform health check',
        description: 'Probes the database and cache layer. Returns healthy/degraded/unhealthy status.',
        tags: ['Health'],
        operationId: 'healthCheck',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Platform is healthy or degraded',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', enum: ['healthy', 'degraded'], example: 'healthy'),
                                new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                                new OA\Property(property: 'environment', type: 'string', example: 'production'),
                                new OA\Property(
                                    property: 'checks',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'database', type: 'string', enum: ['ok', 'fail'], example: 'ok'),
                                        new OA\Property(property: 'cache', type: 'string', enum: ['ok', 'fail'], example: 'ok'),
                                    ],
                                ),
                                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-04-14T12:00:00Z'),
                            ],
                        ),
                        new OA\Property(property: 'error', type: 'null', example: null),
                    ],
                ),
            ),
            new OA\Response(
                response: 503,
                description: 'Platform is unhealthy (database probe failed)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'data', type: 'null', example: null),
                        new OA\Property(
                            property: 'error',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'code', type: 'string', example: 'HEALTH_CHECK_FAILED'),
                                new OA\Property(property: 'message', type: 'string', example: 'Platform health check failed. Please try again later'),
                                new OA\Property(
                                    property: 'details',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'status', type: 'string', example: 'unhealthy'),
                                        new OA\Property(
                                            property: 'checks',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'database', type: 'string', example: 'fail'),
                                                new OA\Property(property: 'cache', type: 'string', example: 'fail'),
                                            ],
                                        ),
                                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                                        new OA\Property(property: 'environment', type: 'string', example: 'production'),
                                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                                    ],
                                ),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function check(): JsonResponse
    {
        $dbStatus = $this->checkDatabase();
        $cacheStatus = $this->checkCache();

        $checks = [
            'database' => $dbStatus,
            'cache' => $cacheStatus,
        ];

        $version = config('app.version', '1.0.0');
        $environment = config('app.env', 'production');
        $timestamp = now()->toISOString();

        // Database failure → HTTP 503, error.code = HEALTH_CHECK_FAILED
        if ($dbStatus === 'fail') {
            return $this->error(
                ApiErrorCode::HEALTH_CHECK_FAILED,
                ApiErrorCode::HEALTH_CHECK_FAILED->defaultMessage(),
                [
                    'status' => 'unhealthy',
                    'checks' => $checks,
                    'version' => $version,
                    'environment' => $environment,
                    'timestamp' => $timestamp,
                ],
                503
            );
        }

        // Cache failure only → HTTP 200 degraded
        $status = $cacheStatus === 'fail' ? 'degraded' : 'healthy';

        return $this->success([
            'status' => $status,
            'version' => $version,
            'environment' => $environment,
            'checks' => $checks,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Probe the database connection.
     *
     * Executes a minimal SELECT 1 query via the PDO connection.
     * Wrapped in try/catch Throwable to prevent a hung probe from
     * exceeding the NFR-001 ≤ 200ms p95 budget.
     *
     * @return string 'ok' on success, 'fail' on any error
     */
    private function checkDatabase(): string
    {
        try {
            // Establish PDO to allow OS-level timeout propagation
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }

    /**
     * Probe the cache layer.
     *
     * Writes a unique probe key, reads it back, then deletes it.
     * Wrapped in try/catch Throwable to prevent a hung probe.
     *
     * @return string 'ok' on success, 'fail' on any error
     */
    private function checkCache(): string
    {
        try {
            $probeKey = 'health_probe_'.microtime(true);
            $probeValue = 'bunyan_health_check';

            Cache::put($probeKey, $probeValue, 10);
            $retrieved = Cache::get($probeKey);
            Cache::forget($probeKey);

            return $retrieved === $probeValue ? 'ok' : 'fail';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
