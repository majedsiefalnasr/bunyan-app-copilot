<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OtpAuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OtpAuditLogRepository extends BaseRepository
{
    public function __construct(OtpAuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * Create an OTP audit log entry.
     */
    public function logAttempt(
        string $email,
        string $otpCodeHash,
        int $attemptNumber,
        bool $success,
        ?string $ipAddress,
        ?string $userAgent,
        Carbon $expiresAt
    ): OtpAuditLog {
        /** @var OtpAuditLog */
        return $this->model->create([
            'email' => $email,
            'otp_code_hash' => $otpCodeHash,
            'attempt_number' => $attemptNumber,
            'success' => $success,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Count failed attempts for email (within expiry window).
     */
    public function countFailedAttempts(string $email): int
    {
        return $this->model
            ->query()
            ->where('email', $email)
            ->where('success', false)
            ->where('expires_at', '>', Carbon::now())
            ->count();
    }

    /**
     * Check if email is rate-limited (5+ failures).
     */
    public function isRateLimited(string $email): bool
    {
        return $this->countFailedAttempts($email) >= 5;
    }

    /**
     * Get recent attempts for email.
     */
    public function getRecentAttempts(string $email): Collection
    {
        /** @var Collection */
        return $this->model
            ->query()
            ->where('email', $email)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the latest OTP hash for email (for verification).
     */
    public function getLatestOtpHash(string $email): ?string
    {
        /** @var OtpAuditLog|null */
        $log = $this->model
            ->query()
            ->where('email', $email)
            ->where('expires_at', '>', Carbon::now())
            ->where('success', false)
            ->orderBy('created_at', 'desc')
            ->first();

        return $log ? $log->otp_code_hash : null;
    }

    /**
     * Mark OTP as successful and get record.
     */
    public function markAsSuccessful(string $email, int $attemptNumber): ?OtpAuditLog
    {
        /** @var OtpAuditLog|null */
        $record = $this->model
            ->query()
            ->where('email', $email)
            ->where('attempt_number', $attemptNumber)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($record) {
            $record->update(['success' => true]);
        }

        return $record;
    }

    /**
     * Clean up expired OTP records (older than 1 day).
     */
    public function cleanup(): int
    {
        return $this->model
            ->query()
            ->where('expires_at', '<', Carbon::now())
            ->delete();
    }
}
