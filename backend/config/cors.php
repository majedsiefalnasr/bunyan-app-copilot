<?php

use App\Providers\AppServiceProvider;

/**
 * CORS Configuration — Bunyan API
 *
 * Controls Cross-Origin Resource Sharing (CORS) for all API routes.
 * Origins are env-driven via CORS_ALLOWED_ORIGINS (comma-separated).
 *
 * Security note: Never use wildcard (*) with supports_credentials = true.
 * The AppServiceProvider::boot() CORS guard enforces this at startup.
 *
 * @see AppServiceProvider::boot()
 * @see specs/runtime/006-api-foundation/spec.md FR-013 FR-014 FR-015
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * Paths to apply CORS to.
     */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
     * Allowed HTTP methods for cross-origin requests.
     */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Allowed origins. Parsed from CORS_ALLOWED_ORIGINS env variable.
     * Format: comma-separated list of origins.
     * Example: http://localhost:3000,https://app.bunyan.sa
     *
     * WARNING: Do not set to ['*'] when supports_credentials = true.
     * The AppServiceProvider CORS guard will throw InvalidArgumentException
     * if this misconfiguration is detected in non-local environments.
     */
    'allowed_origins' => array_filter(
        array_map(
            'trim',
            explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))
        )
    ),

    /*
     * Allowed origins patterns (regex).
     */
    'allowed_origins_patterns' => [],

    /*
     * Allowed headers in cross-origin requests.
     * X-Correlation-ID is required for distributed tracing.
     */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-Correlation-ID',
        'Accept',
        'Accept-Language',
    ],

    /*
     * Headers exposed to the browser.
     * X-Correlation-ID must be exposed so the frontend can log it.
     */
    'exposed_headers' => ['X-Correlation-ID'],

    /*
     * Maximum time in seconds the browser can cache preflight responses.
     * 86400 = 24 hours.
     */
    'max_age' => 86400,

    /*
     * Whether the request can be sent with credentials (cookies, auth headers).
     * MUST be true for Sanctum cookie-based authentication.
     *
     * SECURITY: When true, allowed_origins MUST NOT contain '*'.
     */
    'supports_credentials' => true,

];
