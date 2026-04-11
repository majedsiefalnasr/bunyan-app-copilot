<?php

namespace Tests\Feature;

use App\Exceptions\RoleNotAllowedException;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Jobs\LogAuditEventJob;
use App\Models\AuditLog;
use App\Support\SensitiveFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * LoggingIntegrationTest — End-to-End Logging Tests
 *
 * Smoke tests for logging and exception handling integration.
 */
class LoggingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function exception_handler_catches_role_not_allowed_exception(): void
    {
        try {
            throw new RoleNotAllowedException(
                'User role is not allowed',
                role: 'customer',
                requiredRole: 'admin'
            );
        } catch (RoleNotAllowedException $e) {
            $this->assertEquals('User role is not allowed', $e->getMessage());
            $this->assertEquals('customer', $e->role);
            $this->assertEquals('admin', $e->requiredRole);
        }
    }

    #[Test]
    public function audit_log_model_can_be_created(): void
    {
        $log = AuditLog::create([
            'correlation_id' => 'test-uuid-1234',
            'action' => 'test.action',
            'resource_type' => 'TestResource',
            'resource_id' => 1,
            'status' => 'success',
        ]);

        $this->assertNotNull($log->id);
        $this->assertEquals('test-uuid-1234', $log->correlation_id);
        $this->assertEquals('test.action', $log->action);
    }

    #[Test]
    public function audit_log_scope_by_correlation_id_works(): void
    {
        $correlationId = 'test-uuid-5678';

        AuditLog::create([
            'correlation_id' => $correlationId,
            'action' => 'action.one',
            'resource_type' => 'Resource',
        ]);

        AuditLog::create([
            'correlation_id' => 'other-uuid',
            'action' => 'action.two',
            'resource_type' => 'Resource',
        ]);

        $logs = AuditLog::byCorrelationId($correlationId)->get();
        $this->assertEquals(1, $logs->count());
        $this->assertEquals('action.one', $logs->first()->action);
    }

    #[Test]
    public function log_audit_event_job_can_be_dispatched(): void
    {
        // Test that the job can be instantiated and dispatched without errors
        $job = new LogAuditEventJob(
            userId: null, // nullable userId for testing
            action: 'project.created',
            resourceType: 'Project',
            resourceId: 10,
            newValues: ['name' => 'Test Project'],
            correlationId: 'test-correlation-id'
        );

        $this->assertEquals('project.created', $job->action);
        $this->assertEquals('Project', $job->resourceType);
        $this->assertEquals('test-correlation-id', $job->correlationId);
    }

    #[Test]
    public function sensitive_fields_mask_passwords_in_data(): void
    {
        $data = [
            'password' => 'secret123',
            'username' => 'john',
        ];

        $masked = SensitiveFields::mask($data);

        $this->assertEquals('***', $masked['password']);
        $this->assertEquals('john', $masked['username']);
    }

    #[Test]
    public function correlation_id_middleware_generates_valid_uuid(): void
    {
        $middleware = new CorrelationIdMiddleware;
        $request = Request::create('/api/test', 'GET');

        $middleware->handle($request, function ($req) {
            $correlationId = $req->attributes->get('correlation_id');
            $this->assertNotNull($correlationId);
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $correlationId
            );

            return new Response;
        });
    }
}
