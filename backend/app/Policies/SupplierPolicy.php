<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SupplierVerificationStatus;
use App\Enums\UserRole;
use App\Models\SupplierProfile;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Admin cases are NOT handled here — Gate::before bypasses all checks for admin.
     * These methods only handle non-admin actors.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Public access
    }

    public function view(?User $user, SupplierProfile $supplier): bool
    {
        if ($supplier->verification_status === SupplierVerificationStatus::Verified) {
            return true;
        }

        if ($user !== null && $user->id === $supplier->user_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasEnumRole(UserRole::CONTRACTOR);
    }

    public function update(User $user, SupplierProfile $supplier): bool
    {
        return $user->id === $supplier->user_id;
    }

    /**
     * Admin-only — always false for non-admin (Gate::before handles admin bypass).
     */
    public function verify(User $user): bool
    {
        return false;
    }

    /**
     * Admin-only — always false for non-admin (Gate::before handles admin bypass).
     */
    public function suspend(User $user): bool
    {
        return false;
    }

    /**
     * Admin-only — always false for non-admin (Gate::before handles admin bypass).
     */
    public function delete(User $user): bool
    {
        return false;
    }
}
