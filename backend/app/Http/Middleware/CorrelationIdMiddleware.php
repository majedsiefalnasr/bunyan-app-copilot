<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CorrelationIdMiddleware — Request Correlation ID Management
 *
 * Generates or propagates a UUID v4 correlation ID for each request.
 * Makes it available via `request()->correlationId()` throughout the request.
 * Returns the correlation ID in the response header `X-Correlation-ID`.
 *
 * Usage in code:
 *   Log::info($message, ['correlation_id' => request()->correlationId()]);
 */
class CorrelationIdMiddleware
{
    /**
     * UUID v4 regex pattern validation.
     * Ensures correlation ID is a valid UUID v4 format.
     * Prevents header injection attacks.
     */
    private const UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve or generate correlation ID
        $correlationId = $this->getOrGenerateCorrelationId($request);

        // Make correlation ID available via request bag
        $request->attributes->set('correlation_id', $correlationId);

        // Bind correlation ID to Log context for all subsequent logs
        Log::withContext(['correlation_id' => $correlationId]);

        // Process the request
        $response = $next($request);

        // Add correlation ID to response header
        $response->header('X-Correlation-ID', $correlationId);

        return $response;
    }

    /**
     * Get existing correlation ID or generate a new one.
     *
     * @param  Request  $request  The HTTP request
     * @return string The correlation ID (UUID v4 format)
     *
     * @throws \InvalidArgumentException If incoming correlation ID is invalid
     */
    private function getOrGenerateCorrelationId(Request $request): string
    {
        // Check for incoming correlation ID
        $incomingId = $request->header('X-Correlation-ID') ?? $request->header('x-correlation-id');

        if ($incomingId) {
            // Validate incoming correlation ID
            if (! $this->isValidUuidV4($incomingId)) {
                // Log the rejection for security audit
                Log::warning('Invalid correlation ID rejected', [
                    'attempted_id' => $incomingId,
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                ]);

                // Generate a new one instead of throwing
                return $this->generateCorrelationId();
            }

            return $incomingId;
        }

        // Generate a new correlation ID if not provided
        return $this->generateCorrelationId();
    }

    /**
     * Generate a new UUID v4 correlation ID.
     *
     * @return string UUID v4 string
     */
    private function generateCorrelationId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Validate that a correlation ID is a valid UUID v4.
     *
     * @param  string  $id  The correlation ID to validate
     * @return bool True if valid UUID v4, false otherwise
     */
    private function isValidUuidV4(string $id): bool
    {
        return preg_match(self::UUID_V4_PATTERN, $id) === 1;
    }
}
