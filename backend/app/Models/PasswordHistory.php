<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PasswordHistory — Password Change Audit Trail
 *
 * Tracks previous password hashes for each user.
 * Used for password reuse prevention (users cannot reuse recent N passwords).
 * Provides security audit trail for intrusion detection.
 *
 * @property int $id
 * @property int $user_id User who changed password
 * @property string $password_hash Hash of the previous password
 * @property Carbon $changed_at Timestamp of password change
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PasswordHistory extends Model
{
    protected $table = 'password_history';

    protected $fillable = [
        'user_id',
        'password_hash',
        'changed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get recent N password hashes for user (used for reuse prevention).
     */
    public function scopeRecentForUser(Builder $query, int $userId, int $limit = 3): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->orderBy('changed_at', 'desc')
            ->limit($limit);
    }
}
