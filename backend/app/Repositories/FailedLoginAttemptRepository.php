<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FailedLoginAttempt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class FailedLoginAttemptRepository extends BaseRepository
{
    public function __construct(FailedLoginAttempt $model)
    {
        parent::__construct($model);
    }

    /**
     * Find or create failed login attempt record for email + IP.
     */
    public function findOrCreateByEmailAndIp(string $email, ?string $ipAddress): FailedLoginAttempt
    {
        /** @var FailedLoginAttempt */
        return $this->model
            ->query()
            ->firstOrCreate(
                ['email' => $email, 'ip_address' => $ipAddress],
                ['attempt_count' => 0, 'first_attempt_at' => Carbon::now()]
            );
    }

    /**
     * Get current failed attempts for email + IP.
     */
    public function getAttemptCount(string $email, ?string $ipAddress): int
    {
        /** @var FailedLoginAttempt|null */
        $record = $this->model
            ->query()
            ->where('email', $email)
            ->where('ip_address', $ipAddress)
            ->first();

        return $record ? $record->attempt_count : 0;
    }

    /**
     * Check if email + IP is currently locked.
     */
    public function isLocked(string $email, ?string $ipAddress): bool
    {
        /** @var FailedLoginAttempt|null */
        $record = $this->model
            ->query()
            ->where('email', $email)
            ->where('ip_address', $ipAddress)
            ->first();

        return $record?->isLocked() ?? false;
    }

    /**
     * Increment attempt count for email + IP.
     */
    public function incrementAttempts(string $email, ?string $ipAddress): void
    {
        $record = $this->findOrCreateByEmailAndIp($email, $ipAddress);
        $record->increment('attempt_count');

        if ($record->attempt_count >= 5) {
            $record->update(['locked_until' => Carbon::now()->addMinutes(15)]);
        }
    }

    /**
     * Reset attempt count for email + IP (after successful login).
     */
    public function resetAttempts(string $email, ?string $ipAddress): void
    {
        $this->model
            ->query()
            ->where('email', $email)
            ->where('ip_address', $ipAddress)
            ->update([
                'attempt_count' => 0,
                'first_attempt_at' => null,
                'locked_until' => null,
            ]);
    }

    /**
     * Clean up old records (older than 48 hours).
     */
    public function cleanup(): int
    {
        return $this->model
            ->query()
            ->where('created_at', '<', Carbon::now()->subHours(48))
            ->delete();
    }

    /**
     * Get all active locks.
     */
    public function getActiveLocks(): Collection
    {
        /** @var Collection */
        return $this->model->query()
            ->where('locked_until', '>', Carbon::now())
            ->get();
    }
}
