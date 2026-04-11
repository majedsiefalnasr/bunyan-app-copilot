<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * LogAuditEventJob — Asynchronous Audit Logging
 *
 * Queued job for writing audit events to the database.
 * Keeps request/response handling synchronous and fast.
 *
 * Usage:
 *   LogAuditEventJob::dispatch(
 *       userId: auth()->id(),
 *       action: 'project.created',
 *       resourceType: 'Project',
 *       resourceId: $project->id,
 *       newValues: $project->toArray(),
 *       correlationId: request()->correlationId(),
 *   );
 */
class LogAuditEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  int|null  $userId  User who performed the action
     * @param  string  $action  Action type (e.g., 'project.created')
     * @param  string  $resourceType  Resource type (e.g., 'Project')
     * @param  int|null  $resourceId  Resource ID
     * @param  array|null  $oldValues  Previous state
     * @param  array|null  $newValues  New state
     * @param  string|null  $correlationId  Correlation ID for tracing
     * @param  string|null  $ipAddress  Client IP address
     * @param  string|null  $userAgent  Client user agent
     */
    public function __construct(
        public ?int $userId = null,
        public string $action = 'unknown',
        public string $resourceType = 'Unknown',
        public ?int $resourceId = null,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?string $correlationId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AuditLog::create([
                'correlation_id' => $this->correlationId ?? 'N/A',
                'user_id' => $this->userId,
                'action' => $this->action,
                'resource_type' => $this->resourceType,
                'resource_id' => $this->resourceId,
                'old_values' => $this->oldValues,
                'new_values' => $this->newValues,
                'status' => 'success',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'created_at' => now(),
            ]);

            Log::debug('Audit event logged', [
                'action' => $this->action,
                'resource_type' => $this->resourceType,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log audit event', [
                'action' => $this->action,
                'exception' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        }
    }
}
