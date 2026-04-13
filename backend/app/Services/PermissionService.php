<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    /**
     * @param  array<int, int>  $permissionIds
     */
    public function syncPermissionsToRole(Role $role, array $permissionIds): Role
    {
        $role->permissions()->sync($permissionIds);

        return $role->fresh(['permissions']);
    }

    public function listPermissions(): Collection
    {
        return $this->permissionRepository->findAll();
    }

    /**
     * @return array<string, mixed>
     */
    public function listPermissionsGrouped(): array
    {
        return $this->permissionRepository->findAll()
            ->groupBy('group')
            ->toArray();
    }

    public function userHasPermission(User $user, string $permissionName): bool
    {
        return $user->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permissionName))
            ->exists();
    }
}
