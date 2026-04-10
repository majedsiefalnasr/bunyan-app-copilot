---
name: observability-standards
description: Structured logging, correlation IDs, monitoring
---

# Observability Standards — Bunyan

## Structured Logging

### Backend (Laravel)
```php
use Illuminate\Support\Facades\Log;

// Always use structured context
Log::info('Project created', [
    'project_id' => $project->id,
    'user_id' => $user->id,
    'action' => 'project.created',
]);

Log::error('Payment failed', [
    'order_id' => $order->id,
    'user_id' => $user->id,
    'error' => $exception->getMessage(),
    'action' => 'payment.failed',
]);
```

### Log Channels
```php
// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'days' => 14,
    ],
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'days' => 90,
    ],
],
```

## Request Logging Middleware

```php
class RequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::channel('requests')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'duration_ms' => round((microtime(true) - LARAVEL_START) * 1000),
        ]);

        return $response;
    }
}
```

## Monitoring Checklist

- [ ] All API endpoints log request/response status
- [ ] Authentication events logged (login, logout, failed)
- [ ] Workflow state transitions logged
- [ ] Payment/financial operations logged to audit channel
- [ ] Error responses include correlation context
- [ ] Queue job failures logged with full context
