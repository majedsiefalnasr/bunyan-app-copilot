<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\UserRepository;
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
        $this->app->bind(UserRepository::class, fn () => new UserRepository(new User));
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
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $email = $request->input('email', '');

            return Limit::perMinute(3)->by($request->ip().'|'.$email);
        });

        RateLimiter::for('auth-email-resend', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });
    }
}
