---
name: security-hardening
description: CSRF, CSP, input sanitization, rate limiting (Laravel)
---

# Security Hardening — Bunyan

## Authentication

### Laravel Sanctum Configuration

- Token-based authentication for SPA and API
- Token expiration: 24 hours for web, configurable for API
- Rate limit login attempts: 5 per minute per IP
- Password requirements: min 8 chars, mixed case, number, symbol

### Session Security

```php
// config/session.php
'secure' => true,
'http_only' => true,
'same_site' => 'lax',
```

## RBAC Authorization

### Policy Pattern (Mandatory)

```php
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Customer => $project->customer_id === $user->id,
            UserRole::Contractor => $project->contractor_id === $user->id,
            UserRole::SupervisingArchitect => $project->supervising_architect_id === $user->id,
            UserRole::FieldEngineer => $project->tasks()->where('assigned_to', $user->id)->exists(),
        };
    }
}
```

### Middleware Stack

```php
Route::middleware(['auth:sanctum', 'verified', 'role:admin,contractor'])->group(fn () => ...);
```

## Input Validation

1. **Always use Form Requests** — never validate in controllers
2. **Sanitize HTML** — use `strip_tags()` or `htmlspecialchars()`
3. **File uploads**: Validate MIME type server-side, max size, allowed extensions
4. **SQL injection**: Use Eloquent/Query Builder — never raw SQL with user input
5. **Mass assignment**: Always define `$fillable` on models

## CSRF Protection

- Enabled by default in Laravel for web routes
- SPA mode: Use Sanctum's CSRF cookie endpoint
- API tokens exempt from CSRF

## File Upload Security

```php
$request->validate([
    'document' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,png', 'max:10240'],
]);

// Store with hashed name
$path = $request->file('document')->store('project-documents', 'private');
```

## Rate Limiting

```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('uploads', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

## Content Security Policy

```php
// Middleware or config
$policy = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;";
```

## Financial Transaction Security

1. All monetary operations in database transactions
2. Decimal precision: `decimal(12, 2)` for amounts
3. Audit trail for all financial changes
4. Idempotency keys for payment operations
5. Never trust client-submitted prices — always verify server-side
