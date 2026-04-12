# Data Model: Error Handling & Logging Database Schema

**Branch**: `spec/005-error-handling` | **Date**: 2026-04-11

This document specifies the optional database schema for enhanced error tracking and audit logging in Bunyan.

---

## Overview

While error handling primarily operates at the application layer (exceptions, middleware, logging), the ERROR_HANDLING stage optionally introduces structured database tables for:

1. **Audit Logs** — Financial transactions, workflow state changes, sensitive actions
2. **Request Logs** — HTTP request/response metadata for performance analysis and debugging
3. **Error Tracking** — Detailed error records linked to requests

**Note**: These tables are **optional** for Phase 1. The core error handling works without database tables. Database logging is recommended in Phase 2+ if audit requirements demand persistent, queryable error records.

---

## Table 1: audit_logs

Purpose: Permanent audit trail for financial and workflow-sensitive events

```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- Tracing
    correlation_id CHAR(36) NOT NULL COMMENT 'UUID for request tracing',
    request_id CHAR(36) NOT NULL COMMENT 'Request identifier',

    -- User Context
    user_id BIGINT UNSIGNED COMMENT 'ID of user who initiated action',
    user_role VARCHAR(50) COMMENT 'Role of user (e.g., customer, contractor, admin)',

    -- Action Details
    action VARCHAR(255) NOT NULL COMMENT 'Action performed (e.g., payment_processed, phase_approved)',
    resource_type VARCHAR(100) NOT NULL COMMENT 'Type of resource (e.g., Project, Payment, Phase)',
    resource_id BIGINT UNSIGNED COMMENT 'ID of resource affected',

    -- State Change (JSON)
    old_values JSON COMMENT 'Previous state of resource',
    new_values JSON COMMENT 'New state of resource',

    -- Result
    status VARCHAR(50) NOT NULL COMMENT 'success, failed, pending',
    error_code VARCHAR(100) COMMENT 'Error code if failed (e.g., PAYMENT_FAILED)',
    error_message TEXT COMMENT 'Human-readable error message',

    -- Request Metadata
    method VARCHAR(10) COMMENT 'HTTP method (GET, POST, etc.)',
    uri VARCHAR(500) COMMENT 'Request URI',
    ip_address VARCHAR(45) COMMENT 'IPv4 or IPv6 address',
    user_agent TEXT COMMENT 'Browser/client user agent',

    -- Performance
    duration_ms INT COMMENT 'Request duration in milliseconds',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    KEY idx_user_id_created_at (user_id, created_at),
    KEY idx_correlation_id (correlation_id),
    KEY idx_resource_type_resource_id (resource_type, resource_id),
    KEY idx_action (action),
    KEY idx_status (status),
    KEY idx_error_code (error_code),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Records**:

```sql
-- Payment processed successfully
INSERT INTO audit_logs VALUES (
    NULL,
    '550e8400-e29b-41d4-a716-446655440000',
    '550e8400-e29b-41d4-a716-446655440001',
    5,
    'customer',
    'payment_processed',
    'Payment',
    101,
    JSON_OBJECT('status', 'pending'),
    JSON_OBJECT('status', 'completed', 'reference_id', 'PAY-12345'),
    'success',
    NULL,
    NULL,
    'POST',
    '/api/v1/payments',
    '192.168.1.1',
    'Mozilla/5.0...',
    45,
    NOW()
);

-- Payment failed
INSERT INTO audit_logs VALUES (
    NULL,
    '550e8400-e29b-41d4-a716-446655440002',
    '550e8400-e29b-41d4-a716-446655440003',
    5,
    'customer',
    'payment_processed',
    'Payment',
    102,
    JSON_OBJECT('status', 'pending'),
    NULL,
    'failed',
    'PAYMENT_FAILED',
    'Card declined — insufficient funds',
    'POST',
    '/api/v1/payments',
    '192.168.1.2',
    'Mozilla/5.0...',
    235,
    NOW()
);
```

### Audit Log Lifecycle

1. Service performs action (e.g., `PaymentService::process()`)
2. Before committing, service logs to `audit_logs` table
3. Transaction commits or rolls back
4. If rolled back, audit record should also be rolled back (within transaction)

```php
// app/Services/PaymentService.php

public function process(Payment $payment): void
{
    DB::transaction(function () use ($payment) {
        // Process payment
        $payment->status = 'completed';
        $payment->save();

        // Log to audit table
        AuditLog::create([
            'correlation_id' => request()->correlationId(),
            'user_id' => auth()->id(),
            'action' => 'payment_processed',
            'resource_type' => 'Payment',
            'resource_id' => $payment->id,
            'old_values' => $payment->getOriginal(),
            'new_values' => $payment->getChanges(),
            'status' => 'success',
        ]);
    });
}
```

---

## Table 2: request_logs

Purpose: HTTP request/response metadata for performance analysis, debugging, and security monitoring

```sql
CREATE TABLE request_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- Tracing
    correlation_id CHAR(36) NOT NULL COMMENT 'UUID for request tracing',
    request_id CHAR(36) NOT NULL COMMENT 'Unique request identifier',

    -- Request Details
    method VARCHAR(10) NOT NULL COMMENT 'HTTP method (GET, POST, PUT, PATCH, DELETE)',
    uri VARCHAR(500) NOT NULL COMMENT 'Request path (e.g., /api/v1/projects)',
    query_string TEXT COMMENT 'Query parameters (e.g., page=1&limit=10)',

    -- Response Details
    status_code INT NOT NULL COMMENT 'HTTP status code (200, 401, 404, 500, etc.)',
    response_time_ms INT COMMENT 'Request duration in milliseconds',
    response_size_bytes INT COMMENT 'Response body size',

    -- User Context
    user_id BIGINT UNSIGNED COMMENT 'ID of authenticated user',
    user_role VARCHAR(50) COMMENT 'Role of user',

    -- Client Context
    ip_address VARCHAR(45) COMMENT 'IPv4 or IPv6 address',
    user_agent TEXT COMMENT 'Browser/client user agent',
    referer VARCHAR(500) COMMENT 'HTTP Referer header',

    -- Error Context
    error_code VARCHAR(100) COMMENT 'Error code if request failed (e.g., AUTH_UNAUTHORIZED)',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    KEY idx_user_id_created_at (user_id, created_at),
    KEY idx_correlation_id (correlation_id),
    KEY idx_status_code (status_code),
    KEY idx_method_uri (method, uri),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Records**:

```sql
-- Successful GET request
INSERT INTO request_logs VALUES (
    NULL,
    '550e8400-e29b-41d4-a716-446655440000',
    '550e8400-e29b-41d4-a716-446655440001',
    'GET',
    '/api/v1/projects',
    'page=1&limit=10',
    200,
    45,
    2048,
    5,
    'customer',
    '192.168.1.1',
    'Mozilla/5.0...',
    'https://bunyan.example.com/dashboard',
    NULL,
    NOW()
);

-- Failed POST request (validation error)
INSERT INTO request_logs VALUES (
    NULL,
    '550e8400-e29b-41d4-a716-446655440002',
    '550e8400-e29b-41d4-a716-446655440003',
    'POST',
    '/api/v1/projects',
    NULL,
    422,
    120,
    512,
    5,
    'customer',
    '192.168.1.2',
    'Mozilla/5.0...',
    'https://bunyan.example.com/projects/create',
    'VALIDATION_ERROR',
    NOW()
);

-- Unauthorized request (401)
INSERT INTO request_logs VALUES (
    NULL,
    '550e8400-e29b-41d4-a716-446655440004',
    '550e8400-e29b-41d4-a716-446655440005',
    'GET',
    '/api/v1/admin/users',
    NULL,
    401,
    15,
    256,
    NULL,
    NULL,
    '192.168.1.3',
    'curl/7.68.0',
    NULL,
    'AUTH_INVALID_CREDENTIALS',
    NOW()
);
```

### Request Logging Middleware Integration

```php
// app/Http/Middleware/RequestLoggingMiddleware.php

public function handle(Request $request, Closure $next)
{
    $startTime = microtime(true);
    $response = $next($request);
    $duration = round((microtime(true) - $startTime) * 1000, 2);

    // Only log to database in production (optional)
    if (config('app.debug') === false) {
        RequestLog::create([
            'correlation_id' => $request->correlationId(),
            'request_id' => $request->id(),
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'query_string' => $request->getQueryString(),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => $duration,
            'response_size_bytes' => strlen($response->getContent()),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role->value,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->referer(),
            'error_code' => $this->extractErrorCode($response),
        ]);
    }

    return $response;
}

private function extractErrorCode($response): ?string
{
    if ($response->status() < 400) {
        return null;
    }

    $data = json_decode($response->getContent(), true);
    return $data['error']['code'] ?? null;
}
```

---

## Table 3: error_events (Optional)

Purpose: Detailed error tracking for analysis and alerting

```sql
CREATE TABLE error_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- Tracing
    correlation_id CHAR(36) NOT NULL,

    -- Error Details
    error_code VARCHAR(100) NOT NULL,
    exception_class VARCHAR(255) COMMENT 'Exception PHP class name',
    exception_message TEXT,
    stack_trace LONGTEXT COMMENT 'Full stack trace',

    -- Context
    user_id BIGINT UNSIGNED,
    endpoint VARCHAR(500),
    method VARCHAR(10),

    -- Analysis
    is_resolved BOOLEAN DEFAULT FALSE,
    resolution_notes TEXT,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,

    -- Indexes
    KEY idx_error_code_created_at (error_code, created_at),
    KEY idx_user_id (user_id),
    KEY idx_is_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Indexes & Query Patterns

### Common Queries

**1. Find all requests from a user in the last hour**:

```sql
SELECT * FROM request_logs
WHERE user_id = 5 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC
LIMIT 100;
```

**2. Find all errors in the last day**:

```sql
SELECT * FROM request_logs
WHERE status_code >= 400 AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY error_code
ORDER BY COUNT(*) DESC;
```

**3. Find all 429 rate limit errors**:

```sql
SELECT * FROM request_logs
WHERE error_code = 'RATE_LIMIT_EXCEEDED'
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;
```

**4. Audit trail for a specific resource**:

```sql
SELECT * FROM audit_logs
WHERE resource_type = 'Payment' AND resource_id = 101
ORDER BY created_at DESC;
```

**5. Find slow requests (>1000ms)**:

```sql
SELECT * FROM request_logs
WHERE response_time_ms > 1000
AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY response_time_ms DESC;
```

---

## Migration Files

### Migration 1: Create audit_logs Table

```php
// database/migrations/[timestamp]_create_audit_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->uuid('request_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_role', 50)->nullable();
            $table->string('action', 255);
            $table->string('resource_type', 100)->index();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('status', 50)->index();
            $table->string('error_code', 100)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('uri', 500)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

### Migration 2: Create request_logs Table

```php
// database/migrations/[timestamp]_create_request_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->uuid('request_id');
            $table->string('method', 10);
            $table->string('uri', 500)->index();
            $table->text('query_string')->nullable();
            $table->unsignedSmallInteger('status_code')->index();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedBigInteger('response_size_bytes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_role', 50)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer', 500)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['method', 'uri']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
```

---

## Eloquent Models (Optional)

If database logging is implemented, create corresponding models:

```php
// app/Models/AuditLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'correlation_id',
        'request_id',
        'user_id',
        'user_role',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'status',
        'error_code',
        'error_message',
        'method',
        'uri',
        'ip_address',
        'user_agent',
        'duration_ms',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// app/Models/RequestLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    protected $fillable = [
        'correlation_id',
        'request_id',
        'method',
        'uri',
        'query_string',
        'status_code',
        'response_time_ms',
        'response_size_bytes',
        'user_id',
        'user_role',
        'ip_address',
        'user_agent',
        'referer',
        'error_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Retention & Cleanup

### Scheduled Job for Log Cleanup

```php
// app/Console/Commands/CleanupLogs.php

namespace App\Console\Commands;

use App\Models\RequestLog;
use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanupLogs extends Command
{
    protected $signature = 'logs:cleanup';
    protected $description = 'Cleanup old log records';

    public function handle(): void
    {
        // Delete request logs older than 30 days
        RequestLog::where('created_at', '<', now()->subDays(30))->delete();

        // Delete audit logs older than 90 days
        AuditLog::where('created_at', '<', now()->subDays(90))->delete();

        $this->info('Log cleanup completed');
    }
}
```

Schedule in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logs:cleanup')->daily()->at('02:00');
}
```

---

## Summary

This data model provides optional database tables for:

1. **audit_logs** — Permanent audit trail for financial/workflow events (90-day retention)
2. **request_logs** — HTTP request/response metadata (30-day retention)
3. **error_events** — Detailed error tracking for analysis

These tables enable:

- Regulatory compliance (audit trail)
- Performance analysis (slow query detection)
- Security monitoring (rate limit violations, failed auth)
- Debugging (request/response history)

All tables are optional for Phase 1. Implement when required by compliance or operational needs.
