<?php

use App\Http\Controllers\Api\AdminRbacController;
use Illuminate\Support\Facades\Route;

/**
 * Admin RBAC Routes — API v1
 *
 * Role and permission management (admin-only).
 * All routes are prefixed with /api/v1/admin by the parent group in api.php.
 */
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('roles', [AdminRbacController::class, 'listRoles'])
            ->name('api.v1.admin.roles.index');
        Route::get('roles/{id}', [AdminRbacController::class, 'showRole'])
            ->name('api.v1.admin.roles.show');
        Route::put('roles/{id}/permissions', [AdminRbacController::class, 'syncPermissions'])
            ->name('api.v1.admin.roles.permissions.sync');
        Route::post('users/{id}/role', [AdminRbacController::class, 'assignRole'])
            ->name('api.v1.admin.users.role.assign');
        Route::get('users', [AdminRbacController::class, 'listUsers'])
            ->name('api.v1.admin.users.index');
        Route::get('permissions', [AdminRbacController::class, 'listPermissions'])
            ->name('api.v1.admin.permissions.index');
    });
