<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

/**
 * ProjectPolicy — Authorization for Project resource.
 *
 * Admin cases are NOT handled here — Gate::before bypasses all checks for Admin.
 * These methods only handle non-Admin actors.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can list (role-scoped via query)
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->role === UserRole::CUSTOMER) {
            return $user->id === $project->owner_id;
        }

        // Stub: Contractor/Architect/Engineer → false until STAGE_15
        return false;
    }

    public function create(User $user): bool
    {
        // Admin-only — Gate::before handles admin bypass
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->role === UserRole::CUSTOMER) {
            return $user->id === $project->owner_id && $project->isEditable();
        }

        return false;
    }

    public function transitionStatus(User $user, Project $project): bool
    {
        // Admin-only — Gate::before handles admin bypass
        return false;
    }

    public function delete(User $user, Project $project): bool
    {
        // Admin-only — Gate::before handles admin bypass
        return false;
    }

    public function addPhase(User $user, Project $project): bool
    {
        // Admin-only — Gate::before handles admin bypass
        return false;
    }
}
