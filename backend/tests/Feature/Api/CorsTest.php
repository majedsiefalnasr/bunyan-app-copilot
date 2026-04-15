<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Feature tests for CORS configuration (T032).
 *
 * Covers AC-03 and AC-04:
 * - AC-03: Allowed origin gets Access-Control-Allow-Origin + credentials
 * - AC-04: Non-allowed origin gets no Access-Control-Allow-Origin
 * - X-Correlation-ID is in Access-Control-Expose-Headers
 * - Preflight for X-Correlation-ID in request headers is allowed
 *
 * Test configuration:
 * - Allowed origin: http://localhost:3000 (from cors.php default / CORS_ALLOWED_ORIGINS env default)
 * - Non-allowed origin: http://evil.example.com
 *
 * @see backend/config/cors.php
 * @see specs/runtime/006-api-foundation/spec.md FR-013 FR-014
 */
class CorsTest extends TestCase
{
    private const ALLOWED_ORIGIN = 'http://localhost:3000';

    private const DISALLOWED_ORIGIN = 'http://evil.example.com';

    private const CORS_ENDPOINT = '/api/health';

    // -------------------------------------------------------------------------
    // AC-03: Preflight OPTIONS from allowed origin
    // -------------------------------------------------------------------------

    public function test_preflight_from_allowed_origin_returns_access_control_allow_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
        ])->options(self::CORS_ENDPOINT);

        $response->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    public function test_preflight_from_allowed_origin_includes_credentials_header(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
        ])->options(self::CORS_ENDPOINT);

        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_preflight_allows_x_correlation_id_in_request_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'X-Correlation-ID',
        ])->options(self::CORS_ENDPOINT);

        $allowedHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers', ''));
        $this->assertStringContainsString(
            'x-correlation-id',
            $allowedHeaders,
            'Access-Control-Allow-Headers must include x-correlation-id'
        );
    }

    public function test_preflight_response_includes_allowed_methods(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/auth/login');

        // Should not return 405 — OPTIONS must be allowed
        $this->assertNotEquals(405, $response->getStatusCode());
        $response->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    // -------------------------------------------------------------------------
    // Exposed headers: X-Correlation-ID
    // -------------------------------------------------------------------------

    public function test_actual_request_from_allowed_origin_exposes_x_correlation_id_header(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
        ])->getJson(self::CORS_ENDPOINT);

        $exposed = strtolower((string) $response->headers->get('Access-Control-Expose-Headers', ''));
        $this->assertStringContainsString(
            'x-correlation-id',
            $exposed,
            'Access-Control-Expose-Headers must include x-correlation-id'
        );
    }

    public function test_actual_request_from_allowed_origin_returns_access_control_allow_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::ALLOWED_ORIGIN,
        ])->getJson(self::CORS_ENDPOINT);

        $response->assertHeader('Access-Control-Allow-Origin', self::ALLOWED_ORIGIN);
    }

    // -------------------------------------------------------------------------
    // AC-04: Non-allowed origin gets no CORS headers
    // -------------------------------------------------------------------------

    public function test_request_from_non_allowed_origin_receives_no_access_control_allow_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::DISALLOWED_ORIGIN,
        ])->getJson(self::CORS_ENDPOINT);

        // The CORS middleware must NOT echo back a disallowed origin
        $allowOriginHeader = $response->headers->get('Access-Control-Allow-Origin');

        $this->assertNotEquals(
            self::DISALLOWED_ORIGIN,
            $allowOriginHeader,
            'Non-allowed origin must not be echoed in Access-Control-Allow-Origin'
        );

        // It also must not be a wildcard '*' with credentials
        if ($allowOriginHeader === '*') {
            $credentials = $response->headers->get('Access-Control-Allow-Credentials', 'false');
            $this->assertNotEquals(
                'true',
                strtolower($credentials),
                'Wildcard CORS with credentials=true is a security misconfiguration'
            );
        }
    }

    public function test_preflight_from_non_allowed_origin_does_not_grant_access(): void
    {
        $response = $this->withHeaders([
            'Origin' => self::DISALLOWED_ORIGIN,
            'Access-Control-Request-Method' => 'GET',
        ])->options(self::CORS_ENDPOINT);

        $allowOriginHeader = $response->headers->get('Access-Control-Allow-Origin');

        $this->assertNotEquals(
            self::DISALLOWED_ORIGIN,
            $allowOriginHeader,
            'Non-allowed origin must not be echoed in preflight Access-Control-Allow-Origin'
        );
    }

    // -------------------------------------------------------------------------
    // Security: no wildcard + credentials
    // -------------------------------------------------------------------------

    public function test_cors_config_does_not_use_wildcard_with_credentials(): void
    {
        $corsConfig = config('cors');

        $allowedOrigins = $corsConfig['allowed_origins'] ?? [];
        $supportsCredentials = $corsConfig['supports_credentials'] ?? false;

        if ($supportsCredentials) {
            $this->assertNotContains(
                '*',
                $allowedOrigins,
                'CORS wildcard (*) with supports_credentials=true is a security misconfiguration'
            );
        }
    }
}
