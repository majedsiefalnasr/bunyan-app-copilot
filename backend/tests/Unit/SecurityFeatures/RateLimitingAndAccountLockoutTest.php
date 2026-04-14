<?php

namespace Tests\Unit\SecurityFeatures;

use App\Models\FailedLoginAttempt;
use App\Repositories\FailedLoginAttemptRepository;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * T058 + T045: Unit Tests for Rate Limiting and Account Lockout
 *
 * Validates:
 * - Max 5 failed login attempts trigger 15-minute lockout
 * - Attempt counter increments on authentication failure
 * - Attempt counter resets on successful login
 * - Locked accounts cannot login until timeout expires
 *
 * @see T044 — Rate Limiting Middleware
 * @see T045 — Account Lockout Logic
 */
class RateLimitingAndAccountLockoutTest extends TestCase
{
    /**
     * Test account lockout after 5 failed attempts.
     */
    public function test_account_locked_after_5_failed_login_attempts(): void
    {
        $email = 'user@example.com';
        $ip = '127.0.0.1';
        $repo = new FailedLoginAttemptRepository(new FailedLoginAttempt);

        // First 4 attempts
        for ($i = 1; $i <= 4; $i++) {
            $repo->incrementAttempts($email, $ip);
            $this->assertFalse($repo->isLocked($email, $ip));
        }

        // 5th attempt triggers lockout
        $repo->incrementAttempts($email, $ip);
        $this->assertTrue($repo->isLocked($email, $ip));

        // Verify attempt count
        $this->assertEquals(5, $repo->getAttemptCount($email, $ip));
    }

    /**
     * Test attempt counter resets on successful login.
     */
    public function test_attempt_count_resets_on_successful_login(): void
    {
        $email = 'user@example.com';
        $ip = '127.0.0.1';
        $repo = new FailedLoginAttemptRepository(new FailedLoginAttempt);

        // Increment 3 times
        for ($i = 0; $i < 3; $i++) {
            $repo->incrementAttempts($email, $ip);
        }

        $this->assertEquals(3, $repo->getAttemptCount($email, $ip));

        // Reset on successful login
        $repo->resetAttempts($email, $ip);
        $this->assertEquals(0, $repo->getAttemptCount($email, $ip));
    }

    /**
     * Test lockout time is 15 minutes.
     */
    public function test_lockout_duration_is_15_minutes(): void
    {
        $email = 'user@example.com';
        $ip = '127.0.0.1';
        $repo = new FailedLoginAttemptRepository(new FailedLoginAttempt);

        // Trigger lockout
        for ($i = 0; $i < 5; $i++) {
            $repo->incrementAttempts($email, $ip);
        }

        $record = FailedLoginAttempt::where('email', $email)->where('ip_address', $ip)->first();
        $this->assertNotNull($record->locked_until);

        // Verify lockout is roughly 15 minutes
        $minutesUntilUnlock = Carbon::now()->diffInMinutes($record->locked_until);
        $this->assertGreaterThanOrEqual(14, $minutesUntilUnlock);
        $this->assertLessThanOrEqual(15, $minutesUntilUnlock);
    }
}
