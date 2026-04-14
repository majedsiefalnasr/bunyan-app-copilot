<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * OtpAuditLog — OTP Verification Audit Trail
 *
 * Tracks OTP verification attempts and success/failure for rate limiting.
 * Used for email verification and password reset flows.
 *
 * @property int $id
 * @property string $email Email address being verified
 * @property string $otp_code_hash Hash of the 6-digit OTP code
 * @property int $attempt_number Current attempt number (1-5)
 * @property bool $success Whether OTP matched
 * @property string|null $ip_address IP address of attempt
 * @property string|null $user_agent HTTP User-Agent header
 * @property Carbon $expires_at OTP expiry timestamp
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OtpAuditLog extends Model
{
    protected $table = 'otp_audit_logs';

    protected $fillable = [
        'email',
        'otp_code_hash',
        'attempt_number',
        'success',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'success' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'otp_code_hash',
    ];

    /**
     * Check if this OTP has expired.
     */
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Scope: Get recent attempts for email within expiry window.
     */
    public function scopeRecentForEmail(Builder $query, string $email): Builder
    {
        return $query
            ->where('email', $email)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Count failed attempts for email.
     */
    public function scopeFailedAttemptsForEmail(Builder $query, string $email): Builder
    {
        return $query
            ->where('email', $email)
            ->where('success', false)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope: Expired entries (for cleanup).
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', Carbon::now());
    }
}
