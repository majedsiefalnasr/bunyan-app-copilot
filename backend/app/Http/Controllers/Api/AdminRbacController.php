<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\SyncPermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserRoleResource;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRbacController extends BaseController
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly PermissionService $permissionService,
    ) {}

    public function listRoles(): JsonResponse
    {
        $roles = $this->roleService->listRoles();
        $roles->each(fn ($role) => $role->loadCount('permissions'));

        return $this->success(RoleResource::collection($roles));
    }

    public function showRole(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->error(ApiErrorCode::RESOURCE_NOT_FOUND, 'Role not found');
        }

        return $this->success(new RoleResource($role));
    }

    public function syncPermissions(SyncPermissionsRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->getRoleWithPermissions($id);

        if (! $role) {
            return $this->error(ApiErrorCode::RESOURCE_NOT_FOUND, 'Role not found');
        }

        $updatedRole = $this->permissionService->syncPermissionsToRole($role, $request->validated('permission_ids'));

        return $this->success(new RoleResource($updatedRole));
    }

    public function assignRole(AssignRoleRequest $request, int $id): JsonResponse
    {
        $targetUser = User::find($id);

        if (! $targetUser) {
            return $this->error(ApiErrorCode::RESOURCE_NOT_FOUND, 'User not found');
        }

        $newRole = UserRole::from($request->validated('role'));
        $result = $this->roleService->assignRoleToUser($targetUser, $newRole, $request->user());

        return $this->success(new UserRoleResource($result));
    }

    public function listUsers(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->success([
            'data' => UserRoleResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function listPermissions(): JsonResponse
    {
        $grouped = $this->permissionService->listPermissionsGrouped();

        // Transform each group to use PermissionResource format
        $result = [];
        foreach ($grouped as $group => $permissions) {
            $result[$group] = array_map(fn ($p) => [
                'id' => $p['id'],
                'name' => $p['name'],
                'display_name' => $p['display_name'],
                'group' => $p['group'],
                'description' => $p['description'],
            ], $permissions);
        }

        return $this->success($result);
    }
}
