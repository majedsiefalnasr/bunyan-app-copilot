<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Feature tests for OpenAPI documentation endpoints (T033, AC-13).
 *
 * Covers:
 * - AC-13a: GET /api/documentation returns HTTP 200 with Swagger UI HTML
 * - AC-13b: GET /api/documentation.json returns JSON with an `openapi` key
 * - Both routes are accessible without authentication
 *
 * Configuration:
 * - L5_SWAGGER_GENERATE_ALWAYS is forced to true in setUp() via config() override
 *   so the documentation JSON is regenerated on each test request, ensuring the
 *   spec file exists even in a clean test environment.
 *
 * @see backend/config/l5-swagger.php
 * @see backend/app/Http/Controllers/Api/OpenApiAnnotations.php
 * @see backend/app/Http/Controllers/Api/HealthController.php (annotated endpoint)
 */
class SwaggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force swagger doc generation so the JSON spec file is created
        // before we assert on /api/documentation.json
        config(['l5-swagger.defaults.generate_always' => true]);
    }

    // -------------------------------------------------------------------------
    // AC-13a: GET /api/documentation — Swagger UI HTML
    // -------------------------------------------------------------------------

    public function test_documentation_ui_route_returns_200(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertStatus(200);
    }

    public function test_documentation_ui_route_returns_html_content(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertStatus(200);
        $contentType = $response->headers->get('Content-Type', '');
        $this->assertStringContainsString('html', strtolower($contentType));
    }

    public function test_documentation_ui_contains_swagger_ui_markers(): void
    {
        $response = $this->get('/api/documentation');
        $response->assertStatus(200);

        $content = $response->getContent();
        // Swagger UI HTML includes one of these identifiers
        $this->assertTrue(
            str_contains($content, 'swagger') || str_contains($content, 'Swagger') || str_contains($content, 'openapi'),
            'Documentation UI HTML must reference swagger or openapi'
        );
    }

    // -------------------------------------------------------------------------
    // AC-13b: GET /api/documentation.json — OpenAPI JSON spec
    // -------------------------------------------------------------------------

    public function test_documentation_json_route_returns_200(): void
    {
        // Trigger generation first via UI route (generates the spec file)
        $this->get('/api/documentation');

        $response = $this->get('/api/documentation.json');

        $response->assertStatus(200);
    }

    public function test_documentation_json_contains_openapi_key(): void
    {
        // Trigger generation first via UI route
        $this->get('/api/documentation');

        $response = $this->get('/api/documentation.json');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayHasKey(
            'openapi',
            $json,
            'OpenAPI JSON spec must contain an "openapi" version key'
        );
        $this->assertNotEmpty($json['openapi'], '"openapi" key must not be empty');
    }

    public function test_documentation_json_contains_info_key(): void
    {
        $this->get('/api/documentation');

        $response = $this->get('/api/documentation.json');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayHasKey('info', $json);
        $this->assertSame('Bunyan API', $json['info']['title'] ?? '');
    }

    // -------------------------------------------------------------------------
    // Auth: both routes accessible without authentication
    // -------------------------------------------------------------------------

    public function test_documentation_ui_is_accessible_without_authentication(): void
    {
        // No actingAs() call — unauthenticated request
        $response = $this->get('/api/documentation');

        // Must not require authentication
        $this->assertNotEquals(
            401,
            $response->status(),
            'Documentation UI must be accessible without authentication'
        );
    }

    public function test_documentation_json_is_accessible_without_authentication(): void
    {
        // Generate first
        $this->get('/api/documentation');

        // No actingAs() call — unauthenticated request
        $response = $this->get('/api/documentation.json');

        $this->assertNotEquals(
            401,
            $response->status(),
            'Documentation JSON must be accessible without authentication'
        );
    }
}
