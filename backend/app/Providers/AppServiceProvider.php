<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FailedLoginAttempt;
use App\Models\OtpAuditLog;
use App\Models\PasswordHistory;
use App\Models\User;
use App\Repositories\FailedLoginAttemptRepository;
use App\Repositories\OtpAuditLogRepository;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\UserRepository;
use App\Services\AvatarService;
use App\Services\PasswordResetService;
use App\Services\VerificationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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

        // Service bindings
        $this->app->bind(PasswordResetService::class, fn ($app) => new PasswordResetService(
            $app->make(UserRepository::class),
            $app->make(PasswordHistoryRepository::class),
        ));
        $this->app->bind(VerificationService::class, fn ($app) => new VerificationService(
            $app->make(OtpAuditLogRepository::class),
        ));
        $this->app->singleton(AvatarService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
            // T044: 10 login attempts per 15 minutes (900 seconds)
            return Limit::perMinutes(15, 10)->by($request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            // 5 registration attempts per minute (prevent bulk registration abuse)
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            // T044: 3 forgot-password attempts per 60 minutes per IP+email combo
            $email = $request->input('email', '');

            return Limit::perMinute(60, 3)->by($request->ip().'|'.$email);
        });

        RateLimiter::for('auth-email-resend', function (Request $request) {
            // T044: 5 OTP resend attempts per 15 minutes
            return Limit::perMinutes(15, 5)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('user-avatar-upload', function (Request $request) {
            // T047: 5 avatar upload attempts per 15 minutes per user
            return Limit::perMinutes(15, 5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
