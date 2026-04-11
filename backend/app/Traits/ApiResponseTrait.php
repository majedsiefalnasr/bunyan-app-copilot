<?php

namespace App\Traits;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

/**
 * ApiResponseTrait — Standard API Response Helper
 *
 * Provides consistent success() and error() methods for all API responses.
 * Enforces the unified error contract across the platform.
 *
 * @see specs/runtime/005-error-handling/contracts/error-response.json
 */
trait ApiResponseTrait
{
    /**
     * Return a successful API response.
     *
     * Response format:
     * {
     *   "success": true,
     *   "data": { ... },
     *   "error": null
     * }
     *
     * @param mixed $data The response data (array, object, collection, null)
     * @param string|null $message Optional success message (typically not used)
     * @param int $statusCode HTTP status code (default: 200)
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
        ], $statusCode);
    }

    /**
     * Return an error API response.
     *
     * Response format:
     * {
     *   "success": false,
     *   "data": null,
     *   "error": {
     *     "code": "ERROR_CODE",
     *     "message": "Human-readable message",
     *     "details": { ... }  // Optional, for validation errors
     *   }
     * }
     *
     * @param ApiErrorCode $code The error code
     * @param string|null $message Human-readable error message
     * @param array<string, mixed>|null $details Field-level error details (for validation errors)
     * @param int|null $statusCode HTTP status code (defaults to code's httpStatus())
     * @return JsonResponse
     */
    protected function error(
        ApiErrorCode $code,
        ?string $message = null,
        ?array $details = null,
        ?int $statusCode = null,
    ): JsonResponse {
        $statusCode = $statusCode ?? $code->httpStatus();
        $message = $message ?? $code->defaultMessage();

        $error = [
            'code' => $code->value,
            'message' => $message,
        ];

        // Include details only for validation errors (422) or when explicitly provided
        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $error,
        ], $statusCode);
    }
}
