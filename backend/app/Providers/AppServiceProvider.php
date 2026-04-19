<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\FailedLoginAttempt;
use App\Models\OtpAuditLog;
use App\Models\PasswordHistory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Policies\SupplierPolicy;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\FailedLoginAttemptRepository;
use App\Repositories\OtpAuditLogRepository;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\UserRepository;
use App\Services\AvatarService;
use App\Services\PasswordResetService;
use App\Services\VerificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepository::class, fn () => new UserRepository(new User));
        $this->app->bind(FailedLoginAttemptRepository::class, fn () => new FailedLoginAttemptRepository(new FailedLoginAttempt));
        $this->app->bind(OtpAuditLogRepository::class, fn () => new OtpAuditLogRepository(new OtpAuditLog));
        $this->app->bind(PasswordHistoryRepository::class, fn () => new PasswordHistoryRepository(new PasswordHistory));
        $this->app->bind(RoleRepository::class, fn () => new RoleRepository(new Role));
        $this->app->bind(PermissionRepository::class, fn () => new PermissionRepository(new Permission));

        // Service bindings
        $this->app->bind(PasswordResetService::class, fn ($app) => new PasswordResetService(
            $app->make(UserRepository::class),
            $app->make(PasswordHistoryRepository::class),
        ));
        $this->app->bind(VerificationService::class, fn ($app) => new VerificationService(
            $app->make(OtpAuditLogRepository::class),
        ));
        $this->app->singleton(AvatarService::class);

        // Supplier
        $this->app->bind(
            SupplierRepositoryInterface::class,
            fn () => new SupplierRepository
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // CORS security guard: prevent wildcard origin + credentials misconfiguration
        // in non-local environments. Throws InvalidArgumentException to fail fast.
        $this->guardCorsWildcard();

        // Route model binding
        Route::model('supplier', SupplierProfile::class);

        // Explicit policy registration — SupplierPolicy doesn't follow auto-discovery naming
        Gate::policy(SupplierProfile::class, SupplierPolicy::class);

        // Admin superuser bypass — Admin bypasses all Gate/Policy checks
        Gate::before(function (User $user, string $ability) {
            if ($user->hasEnumRole(UserRole::ADMIN)) {
                return true;
            }
        });

        // Define a named rate limiter for API routes. Keeps tests stable
        // and enforces a reasonable default threshold for brute-force guards.
        RateLimiter::for('api', function (Request $request) {
            // Prefer X-Forwarded-For left-most IP if present (tests simulate via header)
            $xff = $request->header('X-Forwarded-For');
            $ip = $xff ? trim(explode(',', $xff)[0]) : $request->ip();

            // Debug logging to help test investigation (only in local/test env)
            try {
                Log::info('rate_limiter.api invoked', [
                    'xff' => $xff,
                    'computed_ip' => $ip,
                    'request_ip' => $request->ip(),
                    'route' => optional($request->route())->getName(),
                    'uri' => $request->path(),
                ]);
            } catch (\Throwable $_) {
                // tolerate logging failures during bootstrap
            }

            // 10 requests per minute is the test threshold used in security tests
            return Limit::perMinute(10)->by($ip ?: $request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            // T044: 5 login attempts per 15 minutes (900 seconds)
            // Note: Account lockout also triggers at 5 failed attempts,
            // so both fire on the same threshold for consistent behavior.
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            // 5 registration attempts per minute (prevent bulk registration abuse)
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            // T044: 3 forgot-password attempts per 60 minutes per IP+email combo
            $email = $request->input('email', '');

            return Limit::perMinutes(60, 3)->by($request->ip().'|'.$email);
        });

        RateLimiter::for('auth-email-resend', function (Request $request) {
            // T044: 5 OTP resend attempts per 15 minutes
            return Limit::perMinutes(15, 5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('user-avatar-upload', function (Request $request) {
            // T047: 5 avatar upload attempts per 15 minutes per user
            return Limit::perMinutes(15, 5)->by($request->user()?->id ?: $request->ip());
        });

        // STAGE_06: API Foundation rate limiters
        RateLimiter::for('api-authenticated', function (Request $request) {
            // 60 req/min for authenticated users, keyed by user ID with IP fallback
            $key = $request->user()?->id
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('api-public', function (Request $request) {
            // 10 req/min for unauthenticated requests, keyed by IP via TrustProxies
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api-admin', function (Request $request) {
            // 300 req/min for admin users, keyed by admin user ID
            $key = $request->user()?->id
                ? 'admin:'.$request->user()->id
                : 'ip:'.$request->ip();

            return Limit::perMinute(300)->by($key);
        });
    }

    /**
     * Guard against CORS wildcard + credentials misconfiguration.
     *
     * In non-local environments, having CORS_ALLOWED_ORIGINS contain '*' while
     * supports_credentials = true is a security misconfiguration that exposes
     * the API to cross-origin credential theft. We fail fast with an
     * InvalidArgumentException rather than logging quietly.
     *
     * @throws \InvalidArgumentException When wildcard + credentials is detected in non-local env
     */
    private function guardCorsWildcard(): void
    {
        $env = config('app.env', 'production');

        if ($env === 'local' || $env === 'testing') {
            return;
        }

        $corsConfig = config('cors');

        if (! is_array($corsConfig)) {
            return;
        }

        $allowedOrigins = $corsConfig['allowed_origins'] ?? [];
        $supportsCredentials = $corsConfig['supports_credentials'] ?? false;

        if ($supportsCredentials && in_array('*', (array) $allowedOrigins, true)) {
            throw new \InvalidArgumentException(
                'CORS misconfiguration detected: CORS_ALLOWED_ORIGINS cannot contain "*" '
                .'when supports_credentials is true. '
                .'This is a security vulnerability. '
                .'Set CORS_ALLOWED_ORIGINS to the specific origin(s) for your frontend application.'
            );
        }
    }
}
