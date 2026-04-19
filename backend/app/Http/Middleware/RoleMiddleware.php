<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\RoleNotAllowedException;
use Closure;
use Illuminate\Auth\AuthenticationException;
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
     *
     * @throws AuthenticationException When no user is authenticated
     * @throws RoleNotAllowedException When user lacks required role
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $rolesString = implode(',', $roles);

        // No user authenticated - this is an authentication error (401), not RBAC (403)
        if (! $user) {
            throw new AuthenticationException('Authentication required');
        }

        // Check if user is active - this is an auth issue, not RBAC
        if (! $user->is_active) {
            throw new AuthenticationException('Your account is not active');
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
