<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\RoleNotAllowedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: middleware('permission:projects.create')
     *
     * @param  string  $permission  The required permission name
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new RoleNotAllowedException(
                'Authentication required',
                null,
                $permission,
            );
        }

        // Check if user is active
        if (! $user->is_active) {
            throw new RoleNotAllowedException(
                'Your account is not active',
                $user->role->value ?? null,
                $permission,
            );
        }

        // Admin superuser bypass
        if ($user->hasEnumRole(UserRole::ADMIN)) {
            return $next($request);
        }

        // Eager-load roles.permissions if not already loaded
        if (! $user->relationLoaded('roles')) {
            $user->load('roles.permissions');
        } else {
            $user->roles->each(function ($role) {
                if (! $role->relationLoaded('permissions')) {
                    $role->load('permissions');
                }
            });
        }

        // Check if user has the required permission
        if (! $user->hasPermission($permission)) {
            throw new RoleNotAllowedException(
                'Your current role does not allow this action',
                $user->role->value ?? null,
                $permission,
            );
        }

        return $next($request);
    }
}
