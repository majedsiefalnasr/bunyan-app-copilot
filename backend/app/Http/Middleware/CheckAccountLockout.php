<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Repositories\FailedLoginAttemptRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckAccountLockout Middleware
 *
 * Verifies that the user's account is not locked due to failed login attempts.
 * Applied to POST /api/v1/auth/login to enforce account lockout rules.
 *
 * Rules:
 * - Max 5 failed login attempts per email → 15-minute lockout
 * - IP-based rate limiting: max 10 attempts per 15 minutes (via throttle middleware)
 * - Per-email lockout persists across IPs (extra security)
 *
 * @see T045 — Account Lockout Implementation
 */
class CheckAccountLockout
{
    public function __construct(
        private readonly FailedLoginAttemptRepository $failedLoginRepository,
    ) {}

    /**
     * Handle the incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');

        if (! $email) {
            return $next($request);
        }

        // Check if account is locked
        if ($this->failedLoginRepository->isLocked($email, $request->ip())) {
            throw ApiException::make(
                ApiErrorCode::AUTH_UNAUTHORIZED,
                'Account locked due to multiple failed login attempts. Try again after 15 minutes.'
            );
        }

        return $next($request);
    }
}
