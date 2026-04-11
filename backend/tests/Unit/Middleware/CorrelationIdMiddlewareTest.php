<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CorrelationIdMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CorrelationIdMiddlewareTest — Unit Tests
 *
 * Test correlation ID generation, validation, propagation, and lifecycle.
 */
class CorrelationIdMiddlewareTest extends TestCase
{
    protected CorrelationIdMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CorrelationIdMiddleware;
    }

    #[Test]
    public function it_generates_uuid_v4_if_not_provided(): void
    {
        $request = Request::create('/api/test', 'GET');
        $called = false;

        $response = $this->middleware->handle($request, function ($req) use (&$called) {
            $called = true;
            $this->assertNotNull($req->attributes->get('correlation_id'));
            $this->assertTrue($this->isValidUuidV4($req->attributes->get('correlation_id')));

            return new Response;
        });

        $this->assertTrue($called);
    }

    #[Test]
    public function it_preserves_valid_correlation_id_from_header(): void
    {
        $uuidV4 = (string) Str::uuid();
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Correlation-ID', $uuidV4);

        $middleware = $this->middleware;
        $response = $middleware->handle($request, function ($req) use ($uuidV4) {
            $this->assertEquals($uuidV4, $req->attributes->get('correlation_id'));

            return new Response;
        });
    }

    #[Test]
    public function it_rejects_invalid_correlation_id_format(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Correlation-ID', 'invalid-format-123');

        $this->middleware->handle($request, function ($req) {
            // Invalid ID should be rejected and new one generated
            $correlationId = $req->attributes->get('correlation_id');
            $this->assertNotEquals('invalid-format-123', $correlationId);
            $this->assertTrue($this->isValidUuidV4($correlationId));

            return new Response;
        });
    }

    #[Test]
    public function it_rejects_malicious_correlation_ids(): void
    {
        $maliciousIds = [
            "<script>alert('xss')</script>",
            "'; DROP TABLE users; --",
            '../../../etc/passwd',
            '../../config/database.php',
        ];

        foreach ($maliciousIds as $maliciousId) {
            $request = Request::create('/api/test', 'GET');
            $request->headers->set('X-Correlation-ID', $maliciousId);

            $this->middleware->handle($request, function ($req) use ($maliciousId) {
                $correlationId = $req->attributes->get('correlation_id');
                $this->assertNotEquals($maliciousId, $correlationId);
                $this->assertTrue($this->isValidUuidV4($correlationId));

                return new Response;
            });
        }
    }

    #[Test]
    public function it_adds_correlation_id_to_response_header(): void
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function () {
            return new Response;
        });

        $this->assertTrue($response->headers->has('X-Correlation-ID'));
        $correlationId = $response->headers->get('X-Correlation-ID');
        $this->assertTrue($this->isValidUuidV4($correlationId));
    }

    #[Test]
    public function it_respects_lowercase_header_variant(): void
    {
        $uuidV4 = (string) Str::uuid();
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('x-correlation-id', $uuidV4);

        $this->middleware->handle($request, function ($req) use ($uuidV4) {
            $this->assertEquals($uuidV4, $req->attributes->get('correlation_id'));

            return new Response;
        });
    }

    /**
     * Helper: Check if a string is a valid UUID v4.
     */
    private function isValidUuidV4(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $uuid) === 1;
    }
}
