<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Unit tests for BaseApiResource.
 *
 * Covers: $wrap='data' key wrapping and paginatedCollection() meta structure.
 */
class BaseApiResourceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Concrete test double
    // -------------------------------------------------------------------------

    private function makeResource(mixed $resource): BaseApiResource
    {
        return new class($resource) extends BaseApiResource
        {
            public function toArray(Request $request): array
            {
                return ['id' => $this->resource['id'], 'name' => $this->resource['name']];
            }
        };
    }

    // -------------------------------------------------------------------------
    // $wrap = 'data'
    // -------------------------------------------------------------------------

    public function test_single_resource_wrapped_under_data_key(): void
    {
        $resource = $this->makeResource(['id' => 1, 'name' => 'Al-Mansouri']);

        $request = Request::create('/test', 'GET');
        $responseArray = $resource->response($request)->getData(true);

        $this->assertArrayHasKey('data', $responseArray);
        $this->assertSame(1, $responseArray['data']['id']);
        $this->assertSame('Al-Mansouri', $responseArray['data']['name']);
    }

    public function test_wrap_property_is_data(): void
    {
        $this->assertSame('data', BaseApiResource::$wrap);
    }

    // -------------------------------------------------------------------------
    // paginatedCollection()
    // -------------------------------------------------------------------------

    public function test_paginated_collection_returns_data_and_meta_array(): void
    {
        // Build a concrete resource class that can be used as collection
        $resourceClass = new class(null) extends BaseApiResource
        {
            public function toArray(Request $request): array
            {
                return ['id' => $this->resource['id']];
            }
        };

        // Use the anonymous class's FQN via get_class
        $resourceClassName = get_class($resourceClass);

        /** @var LengthAwarePaginator&MockObject $paginator */
        $paginator = new LengthAwarePaginator(
            [['id' => 1], ['id' => 2]], // items
            2,   // total
            15,  // perPage
            1,   // currentPage
        );

        // Call paginatedCollection via the concrete subclass
        $result = $resourceClassName::paginatedCollection($paginator);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);

        $meta = $result['meta'];
        $this->assertArrayHasKey('current_page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('last_page', $meta);

        $this->assertSame(1, $meta['current_page']);
        $this->assertSame(15, $meta['per_page']);
        $this->assertSame(2, $meta['total']);
        $this->assertSame(1, $meta['last_page']);
    }

    public function test_paginated_collection_meta_has_correct_types(): void
    {
        $resourceClass = new class(null) extends BaseApiResource
        {
            public function toArray(Request $request): array
            {
                return [];
            }
        };

        $resourceClassName = get_class($resourceClass);

        /** @var LengthAwarePaginator&MockObject $paginator */
        $paginator = new LengthAwarePaginator(
            [],   // items (empty)
            57,   // total
            20,   // perPage
            3,    // currentPage
        );

        $result = $resourceClassName::paginatedCollection($paginator);

        $this->assertIsInt($result['meta']['current_page']);
        $this->assertIsInt($result['meta']['per_page']);
        $this->assertIsInt($result['meta']['total']);
        $this->assertIsInt($result['meta']['last_page']);

        $this->assertSame(3, $result['meta']['current_page']);
        $this->assertSame(20, $result['meta']['per_page']);
        $this->assertSame(57, $result['meta']['total']);
        $this->assertSame(3, $result['meta']['last_page']);
    }
}
