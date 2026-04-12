<?php

namespace Tests\Feature;

use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\RequestResponseLoggingMiddleware;
use App\Models\AuditLog;
use App\Support\SensitiveFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * LoggingPerformanceTest — Performance Validation
 *
 * Verify that logging and masking operations are performant.
 */
class LoggingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function correlation_id_generation_is_performant(): void
    {
        $times = [];

        for ($i = 0; $i < 50; $i++) {
            $start = microtime(true);

            $middleware = new CorrelationIdMiddleware;
            $request = Request::create('/test', 'GET');
            $middleware->handle($request, function ($req) {
                return new Response;
            });

            $times[] = (microtime(true) - $start) * 1000;
        }

        $maxTime = max($times);
        $avgTime = array_sum($times) / count($times);

        $this->assertLessThan(10, $avgTime, "Correlation ID generation average time too high: {$avgTime}ms");
        $this->assertLessThan(50, $maxTime, "Correlation ID generation max time too high: {$maxTime}ms");
    }

    #[Test]
    public function sensitive_field_masking_is_performant(): void
    {
        $data = [
            'password' => 'secret123',
            'api_token' => 'tok_test_key_12345',
            'credit_card' => '4532123456789010',
            'fields' => array_fill(0, 100, 'value'),
        ];

        $times = [];

        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            SensitiveFields::mask($data);
            $times[] = (microtime(true) - $start) * 1000000; // microseconds
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);

        // Should complete in microseconds, not milliseconds
        $this->assertLessThan(1000, $avgTime, "Masking average time too high: {$avgTime}µs");
        $this->assertLessThan(5000, $maxTime, "Masking max time too high: {$maxTime}µs");
    }

    #[Test]
    public function audit_log_creation_is_performant(): void
    {
        $times = [];

        for ($i = 0; $i < 10; $i++) {
            $start = microtime(true);

            AuditLog::create([
                'correlation_id' => 'test-uuid-'.$i,
                'action' => 'test.action',
                'resource_type' => 'Resource',
                'status' => 'success',
            ]);

            $times[] = (microtime(true) - $start) * 1000;
        }

        $avgTime = array_sum($times) / count($times);

        // Should complete in reasonable time (DB inserts aren't instant)
        $this->assertLessThan(100, $avgTime, "Audit log creation average time too high: {$avgTime}ms");
    }

    #[Test]
    public function request_logging_middleware_does_not_block(): void
    {
        $start = microtime(true);

        $middleware = new RequestResponseLoggingMiddleware;
        $request = Request::create('/test', 'GET');
        $request->attributes->set('correlation_id', 'test-id');

        $middleware->handle($request, function ($req) {
            return new Response('test response');
        });

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(50, $duration, "Request logging middleware time too high: {$duration}ms");
    }
}
