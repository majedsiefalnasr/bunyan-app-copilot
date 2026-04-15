<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers\Api\V1;

use App\Enums\ApiErrorCode;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Unit tests for BaseApiController.
 *
 * Covers: success(), error(), and paginated() response shapes.
 */
class BaseApiControllerTest extends TestCase
{
    private BaseApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate a concrete subclass since BaseApiController is not abstract
        $this->controller = new class extends BaseApiController
        {
            public function callSuccess(mixed $data, mixed $wrappedData = null, int $statusCode = 200): JsonResponse
            {
                return $this->success($data, $wrappedData, $statusCode);
            }

            public function callError(ApiErrorCode $code, ?string $message = null, mixed $details = null, ?int $override = null): JsonResponse
            {
                return $this->error($code, $message, $details, $override);
            }

            public function callPaginated(mixed $collection, LengthAwarePaginator $paginator, int $statusCode = 200): JsonResponse
            {
                return $this->paginated($collection, $paginator, $statusCode);
            }
        };
    }

    // -------------------------------------------------------------------------
    // success()
    // -------------------------------------------------------------------------

    public function test_success_returns_correct_envelope(): void
    {
        $data = ['id' => 1, 'name' => 'Bunyan'];
        $response = $this->controller->callSuccess($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->status());

        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertSame($data, $json['data']);
        $this->assertNull($json['error']);
    }

    public function test_success_respects_custom_status_code(): void
    {
        $response = $this->controller->callSuccess(['created' => true], null, 201);

        $this->assertSame(201, $response->status());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_success_with_null_data(): void
    {
        $response = $this->controller->callSuccess(null, null, 204);

        $this->assertSame(204, $response->status());
        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertNull($json['data']);
        $this->assertNull($json['error']);
    }

    // -------------------------------------------------------------------------
    // error()
    // -------------------------------------------------------------------------

    public function test_error_resource_not_found_returns_404(): void
    {
        $response = $this->controller->callError(ApiErrorCode::RESOURCE_NOT_FOUND);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->status());

        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertSame('RESOURCE_NOT_FOUND', $json['error']['code']);
    }

    public function test_error_validation_returns_422(): void
    {
        $response = $this->controller->callError(
            ApiErrorCode::VALIDATION_ERROR,
            'Validation failed',
            ['name' => ['The name field is required.']]
        );

        $this->assertSame(422, $response->status());
        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertSame('VALIDATION_ERROR', $json['error']['code']);
    }

    public function test_error_message_is_present_in_envelope(): void
    {
        $response = $this->controller->callError(
            ApiErrorCode::RESOURCE_NOT_FOUND,
            'Custom not found message'
        );

        $json = $response->getData(true);
        $this->assertSame('Custom not found message', $json['error']['message']);
    }

    // -------------------------------------------------------------------------
    // paginated()
    // -------------------------------------------------------------------------

    public function test_paginated_returns_data_and_meta(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);

        /** @var LengthAwarePaginator&MockObject $paginator */
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(2);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('total')->willReturn(45);
        $paginator->method('lastPage')->willReturn(5);

        $response = $this->controller->callPaginated($items->all(), $paginator);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->status());

        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertNull($json['error']);

        // data array
        $this->assertIsArray($json['data']);

        // meta
        $this->assertArrayHasKey('meta', $json);
        $this->assertSame(2, $json['meta']['current_page']);
        $this->assertSame(10, $json['meta']['per_page']);
        $this->assertSame(45, $json['meta']['total']);
        $this->assertSame(5, $json['meta']['last_page']);
    }

    public function test_paginated_respects_custom_status_code(): void
    {
        /** @var LengthAwarePaginator&MockObject $paginator */
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(0);
        $paginator->method('lastPage')->willReturn(1);

        $response = $this->controller->callPaginated([], $paginator, 206);

        $this->assertSame(206, $response->status());
    }

    public function test_paginated_propagates_correlation_id_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->attributes->set('correlation_id', 'test-correlation-id-value');
        $this->app->instance('request', $request);

        /** @var LengthAwarePaginator&MockObject $paginator */
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(0);
        $paginator->method('lastPage')->willReturn(1);

        $response = $this->controller->callPaginated([], $paginator);

        $this->assertSame('test-correlation-id-value', $response->headers->get('X-Correlation-ID'));
    }
}
