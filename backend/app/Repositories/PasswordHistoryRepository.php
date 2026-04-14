<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PasswordHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PasswordHistoryRepository extends BaseRepository
{
    public function __construct(PasswordHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * Record a password change for user.
     */
    public function recordPasswordChange(int $userId, string $passwordHash): PasswordHistory
    {
        /** @var PasswordHistory */
        return $this->model->create([
            'user_id' => $userId,
            'password_hash' => $passwordHash,
            'changed_at' => Carbon::now(),
        ]);
    }

    /**
     * Get recent password hashes for user (for reuse prevention).
     * By default returns last 3 passwords.
     */
    public function getRecentHashes(int $userId, int $limit = 3): Collection
    {
        /** @var Collection */
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->orderBy('changed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if password has been used recently (within last N attempts).
     */
    public function isPasswordReused(int $userId, string $newPasswordHash, int $maxRecent = 3): bool
    {
        $recentHashes = $this->getRecentHashes($userId, $maxRecent);

        /** @var PasswordHistory $history */
        foreach ($recentHashes as $history) {
            if (password_verify($newPasswordHash, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get last password change time for user.
     */
    public function getLastChangeTime(int $userId): ?Carbon
    {
        /** @var PasswordHistory|null */
        $record = $this->model
            ->query()
            ->where('user_id', $userId)
            ->orderBy('changed_at', 'desc')
            ->first();

        return $record ? $record->changed_at : null;
    }

    /**
     * Count password changes in last N days for user.
     */
    public function countChangesInDays(int $userId, int $days): int
    {
        return $this->model
            ->query()
            ->where('user_id', $userId)
            ->where('changed_at', '>', Carbon::now()->subDays($days))
            ->count();
    }

    /**
     * Clean up old password history (keep only last 12 months).
     */
    public function cleanup(): int
    {
        return $this->model
            ->query()
            ->where('changed_at', '<', Carbon::now()->subYear())
            ->delete();
    }
}
