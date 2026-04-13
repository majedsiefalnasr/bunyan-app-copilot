<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
    ) {}

    /**
     * Assign role to user — atomic enum + pivot sync.
     *
     * @throws ValidationException if admin self-lockout
     */
    public function assignRoleToUser(User $targetUser, UserRole $newRole, User $performingAdmin): User
    {
        // Admin self-lockout prevention
        if ($targetUser->id === $performingAdmin->id && $newRole !== UserRole::ADMIN) {
            throw ValidationException::withMessages([
                'role' => ['Cannot remove Admin role from your own account'],
            ]);
        }

        return DB::transaction(function () use ($targetUser, $newRole) {
            // 1. Update enum column
            $targetUser->role = $newRole;
            $targetUser->save();

            // 2. Sync pivot table
            $role = $this->roleRepository->findByName($newRole->value);
            if ($role) {
                $targetUser->roles()->sync([$role->id]);
            }

            return $targetUser->fresh(['roles']);
        });
    }

    public function listRoles(): Collection
    {
        return $this->roleRepository->findAll();
    }

    public function getRoleWithPermissions(int $id): ?Role
    {
        return $this->roleRepository->findWithPermissions($id);
    }

    public function getUserRole(User $user): UserRole
    {
        return $user->role;
    }
}
