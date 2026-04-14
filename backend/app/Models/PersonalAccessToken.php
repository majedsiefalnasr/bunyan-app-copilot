<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * PersonalAccessToken — Session Management with Concurrency Limits
 *
 * Extends Laravel Sanctum's PersonalAccessToken to add:
 * - Session concurrency limiting (max 2 active tokens per user)
 * - Device fingerprinting (track device + IP)
 * - Device name tracking (optional: browser, device family)
 *
 * @property int $id
 * @property int $user_id User who owns this token
 * @property string $name Token name (usually "api")
 * @property string $token Hashed token value
 * @property string|null $abilities Token abilities (JSON)
 * @property Carbon|null $last_used_at Last usage timestamp
 * @property Carbon|null $expires_at Token expiry timestamp
 * @property string|null $device_fingerprint Device fingerprint hash
 * @property string|null $device_name Device name (e.g., "Chrome on macOS")
 * @property string|null $ip_address IP address when token was created
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @see T046 — Session Concurrency Limits
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'device_fingerprint',
        'device_name',
        'ip_address',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get active (non-expired) tokens for user.
     */
    public function scopeActiveForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope: Get tokens by device fingerprint.
     */
    public function scopeByDeviceFingerprint(Builder $query, string $fingerprint): Builder
    {
        return $query->where('device_fingerprint', $fingerprint);
    }

    /**
     * Get count of active tokens for user (for concurrency limiting).
     */
    public static function countActiveForUser(int $userId): int
    {
        return self::activeForUser($userId)->count();
    }

    /**
     * Enforce session concurrency limit (max 2 active tokens per user).
     *
     * When user creates a new token and already has 2 active tokens,
     * delete the oldest one to maintain concurrency limit.
     *
     * This allows:
     * - Primary device (desktop or phone)
     * - Secondary device (mobile or laptop)
     * - New login triggers oldest token revocation
     *
     * @see T046 — Session Concurrency Limits (max 2 sessions)
     */
    public static function enforceMaxConcurrencyForUser(int $userId, int $maxActiveTokens = 2): void
    {
        $activeCount = self::countActiveForUser($userId);

        if ($activeCount > $maxActiveTokens) {
            // Delete oldest token(s) to enforce limit
            $toDelete = $activeCount - $maxActiveTokens;
            self::activeForUser($userId)
                ->orderBy('created_at', 'asc')
                ->limit($toDelete)
                ->delete();
        }
    }

    /**
     * Generate device fingerprint from user agent + IP.
     *
     * Used to detect if same user is logging in from different device/location.
     * Fingerprint = hash(user_agent + ip_address)
     */
    public static function generateDeviceFingerprint(string $userAgent, string $ipAddress): string
    {
        return hash('sha256', $userAgent.'::'.$ipAddress);
    }
}
