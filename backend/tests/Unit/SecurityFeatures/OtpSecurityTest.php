<?php

namespace Tests\Unit\SecurityFeatures;

use App\Exceptions\ApiException;
use App\Models\OtpAuditLog;
use App\Repositories\OtpAuditLogRepository;
use App\Services\VerificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * T061: Unit Tests for OTP Security
 *
 * Validates:
 * - 5 failed OTP attempts trigger rate limit
 * - 10-minute OTP expiry
 * - OTP attempt counter tracking
 * - Rate limit error on 5+ failed attempts
 *
 * @see T049 — OTP Security
 */
class OtpSecurityTest extends TestCase
{
    /**
     * Test OTP rate limiting (5 failed attempts).
     */
    public function test_otp_rate_limited_after_5_failed_attempts(): void
    {
        $email = 'user@example.com';
        $repo = new OtpAuditLogRepository(new OtpAuditLog);

        // Simulate 5 failed attempts
        $expiresAt = Carbon::now()->addMinutes(10);
        for ($i = 1; $i <= 5; $i++) {
            $repo->logAttempt(
                email: $email,
                otpCodeHash: Hash::make('123456'),
                attemptNumber: $i,
                success: false,
                ipAddress: '127.0.0.1',
                userAgent: 'Test',
                expiresAt: $expiresAt
            );
        }

        // Verify rate limit is triggered
        $this->assertTrue($repo->isRateLimited($email));
    }

    /**
     * Test OTP rate limit error is thrown.
     */
    public function test_otp_rate_limit_exception_on_exceed(): void
    {
        $this->expectException(ApiException::class);

        $email = 'user@example.com';
        $service = new VerificationService(new OtpAuditLogRepository(new OtpAuditLog));

        // Trigger 5 failed attempts
        $repo = new OtpAuditLogRepository(new OtpAuditLog);
        $expiresAt = Carbon::now()->addMinutes(10);
        for ($i = 1; $i <= 5; $i++) {
            $repo->logAttempt(
                email: $email,
                otpCodeHash: Hash::make('123456'),
                attemptNumber: $i,
                success: false,
                ipAddress: '127.0.0.1',
                userAgent: 'Test',
                expiresAt: $expiresAt
            );
        }

        // Next verification attempt should throw rate limit exception
        $otp = $service->generateOtp($email);
        $service->verifyOtp($email, '000000', $otp['hash'], '127.0.0.1');
    }

    /**
     * Test OTP generation (format: 6 digits).
     */
    public function test_otp_generated_is_6_digit_format(): void
    {
        $service = new VerificationService(new OtpAuditLogRepository(new OtpAuditLog));

        $otp = $service->generateOtp('user@example.com');

        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp['otp']);
        $this->assertTrue(Hash::check($otp['otp'], $otp['hash']));
    }

    /**
     * Test OTP expiry is 10 minutes.
     */
    public function test_otp_expiry_is_10_minutes(): void
    {
        $service = new VerificationService(new OtpAuditLogRepository(new OtpAuditLog));

        $before = Carbon::now();
        $otp = $service->generateOtp('user@example.com');
        $after = Carbon::now();

        $expiryMinutes = $before->diffInMinutes($otp['expires_at']);
        $this->assertGreaterThanOrEqual(9, $expiryMinutes);
        $this->assertLessThanOrEqual(10, $expiryMinutes);
    }

    /**
     * Test OTP attempt counter increments.
     */
    public function test_otp_attempt_counter_increments(): void
    {
        $email = 'user@example.com';
        $repo = new OtpAuditLogRepository(new OtpAuditLog);

        $expiresAt = Carbon::now()->addMinutes(10);
        for ($i = 1; $i <= 3; $i++) {
            $repo->logAttempt(
                email: $email,
                otpCodeHash: Hash::make('123456'),
                attemptNumber: $i,
                success: false,
                ipAddress: '127.0.0.1',
                userAgent: 'Test',
                expiresAt: $expiresAt
            );
        }

        $failedCount = $repo->countFailedAttempts($email);
        $this->assertEquals(3, $failedCount);
    }
}
