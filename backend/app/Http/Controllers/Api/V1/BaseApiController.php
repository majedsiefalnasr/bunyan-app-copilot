<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * BaseApiController — V1 API Base Controller
 *
 * Extends the platform BaseController to add pagination support for
 * versioned V1 API endpoints. All V1 controllers should extend this class.
 *
 * Provides a standardised `paginated()` method that extracts pagination
 * metadata from a LengthAwarePaginator and returns a unified response shape:
 * { success, data, meta, error }
 *
 * @see App\Http\Controllers\Api\BaseController
 * @see App\Traits\ApiResponseTrait
 */
class BaseApiController extends BaseController
{
    /**
     * Return a paginated API response with standardised metadata.
     *
     * Response format:
     * {
     *   "success": true,
     *   "data": [...],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 15,
     *     "total": 100,
     *     "last_page": 7
     *   },
     *   "error": null
     * }
     *
     * @param  mixed  $collection  The collection of items (array or ResourceCollection)
     * @param  LengthAwarePaginator  $paginator  The paginator instance for metadata
     * @param  int  $statusCode  HTTP status code (default: 200)
     * @return JsonResponse JSON response with data and pagination meta
     */
    protected function paginated(mixed $collection, LengthAwarePaginator $paginator, int $statusCode = 200): JsonResponse
    {
        $correlationId = request()->attributes->get('correlation_id');

        $response = response()->json([
            'success' => true,
            'data' => $collection,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'error' => null,
        ], $statusCode);

        if ($correlationId) {
            $response->headers->set('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
