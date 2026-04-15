<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * OpenApiAnnotations — OpenAPI 3.0 Base Annotations
 *
 * This class is non-routable and contains only OpenAPI annotations.
 * It is not instantiated or used at runtime — it exists solely as a
 * namespace for the OA\Info, OA\Server, and OA\SecurityScheme annotations
 * that zircote/swagger-php scans when generating the API specification.
 */
#[OA\Info(
    version: '1.0.0',
    description: 'Bunyan (بنيان) — Full-stack Arabic construction services and building materials marketplace API. Provides endpoints for project management, user authentication, role-based access control, and building materials catalog.',
    title: 'Bunyan API',
    contact: new OA\Contact(name: 'Bunyan API Team', email: 'api@bunyan.sa'),
    license: new OA\License(name: 'Proprietary', url: 'https://bunyan.sa/terms'),
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Bunyan API v1 — Production',
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Laravel Sanctum bearer token. Obtain via POST /api/v1/auth/login.',
)]
class OpenApiAnnotations
{
    // This class is intentionally empty.
    // It exists only to anchor the OA\Info, OA\Server, and OA\SecurityScheme
    // attributes for swagger-php to discover during doc generation.
}
