<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\RoleNotAllowedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage: middleware('role:admin,contractor')
     *
     * @param  string  ...$roles  Role slugs (variadic — Laravel splits comma-separated params)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $rolesString = implode(',', $roles);

        if (! $user) {
            throw new RoleNotAllowedException(
                'Authentication required',
                null,
                $rolesString,
            );
        }

        // Check if user is active
        if (! $user->is_active) {
            throw new RoleNotAllowedException(
                'Your account is not active',
                $user->role->value ?? null,
                $rolesString,
            );
        }

        // Map role slugs to UserRole enum
        $allowedRoles = array_map(
            fn (string $role) => UserRole::from(trim($role)),
            $roles,
        );

        // Check if user has any of the allowed roles
        if (! $user->hasAnyRole(...$allowedRoles)) {
            throw new RoleNotAllowedException(
                'Your current role does not allow this action',
                $user->role->value ?? null,
                $rolesString,
            );
        }

        return $next($request);
    }
}
