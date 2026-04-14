<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * FailedLoginAttempt — Failed Login Tracking
 *
 * Tracks failed login attempts per email + IP address.
 * Used for account lockout enforcement and audit trails.
 *
 * @property int $id
 * @property string $email Email address being authenticated
 * @property string|null $ip_address IP address of the attempt
 * @property string|null $user_agent HTTP User-Agent header
 * @property int $attempt_count Total attempts in current window
 * @property Carbon|null $first_attempt_at Start of attempt window
 * @property Carbon|null $locked_until Account locked until timestamp
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class FailedLoginAttempt extends Model
{
    protected $table = 'failed_login_attempts';

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'attempt_count',
        'first_attempt_at',
        'locked_until',
    ];

    protected $casts = [
        'attempt_count' => 'integer',
        'first_attempt_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    /**
     * Check if this email/IP combination is currently locked.
     */
    public function isLocked(): bool
    {
        return $this->locked_until && Carbon::now()->isBefore($this->locked_until);
    }

    /**
     * Get time remaining until unlock (in seconds).
     */
    public function getTimeUntilUnlock(): int
    {
        if (! $this->locked_until) {
            return 0;
        }

        $remaining = Carbon::now()->diffInSeconds($this->locked_until, false);

        return max(0, (int) $remaining);
    }

    /**
     * Scope: Find by email + IP (for upsert operations).
     */
    public function scopeByEmailAndIp(Builder $query, string $email, ?string $ipAddress): Builder
    {
        return $query->where('email', $email)->where('ip_address', $ipAddress);
    }

    /**
     * Scope: Active locks (not yet expired).
     */
    public function scopeActiveLocks(Builder $query): Builder
    {
        return $query->where('locked_until', '>', Carbon::now());
    }
}
