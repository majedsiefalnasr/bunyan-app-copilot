<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * BaseApiResource — Abstract API Resource Base Class
 *
 * All API resources MUST extend this abstract class to enforce consistent
 * response enveloping under the `data` key and attach the `X-Correlation-ID`
 * header on every resource response.
 *
 * IMPORTANT: This class MUST remain `abstract` because it declares
 * `abstract toArray()`. PHP will not compile a non-abstract class with
 * abstract methods.
 *
 * @see Illuminate\Http\Resources\Json\JsonResource
 * @see App\Traits\ApiResponseTrait
 */
abstract class BaseApiResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * Setting this to 'data' ensures all resource responses wrap the
     * primary payload under a `data` key when returned directly.
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Transform the resource into an array.
     *
     * Subclasses MUST override this method with the resource-specific fields.
     * The parent JsonResource provides a default implementation that returns
     * the resource attributes directly, but all concrete resources should
     * declare an explicit field list for security and stability.
     *
     * @param  Request  $request  The current HTTP request
     * @return array<string, mixed> Transformed resource data
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Extract pagination metadata from a LengthAwarePaginator.
     *
     * Produces a standardised `meta` array compatible with the Bunyan
     * API pagination contract: { current_page, per_page, total, last_page }.
     *
     * Usage (from a controller):
     *   $meta = SomeResource::paginatedCollection($paginator);
     *   return response()->json(['success' => true, ...$meta, 'error' => null]);
     *
     * @param  LengthAwarePaginator  $paginator  The paginator instance
     * @return array{ data: array<int, mixed>, meta: array<string, int> }
     */
    public static function paginatedCollection(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => static::collection($paginator)->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * Propagates the `X-Correlation-ID` header from the request attributes
     * to the outgoing response for end-to-end distributed tracing.
     *
     * @param  Request  $request  The current HTTP request
     * @param  Response  $response  The outgoing HTTP response
     */
    public function withResponse(Request $request, Response $response): void
    {
        $correlationId = $request->attributes->get('correlation_id');

        if (! $correlationId) {
            $incoming = $request->header('X-Correlation-ID') ?? $request->header('x-correlation-id');
            if ($incoming && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $incoming)) {
                $correlationId = $incoming;
            }
        }

        if ($correlationId) {
            $response->headers->set('X-Correlation-ID', $correlationId);
        }
    }
}
