<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * RoleNotAllowedException — Custom RBAC Exception
 *
 * Thrown when a user's role is not allowed to perform a specific action.
 * Distinct from `AuthorizationException` to enable role-based monitoring.
 *
 * Maps to error code: RBAC_ROLE_DENIED
 * HTTP Status: 403 Forbidden
 */
class RoleNotAllowedException extends AuthorizationException
{
    /**
     * Create a new RoleNotAllowedException instance.
     *
     * @param  string  $message  The error message
     * @param  string|null  $role  The user's current role
     * @param  string|null  $requiredRole  The required role(s) for this action
     */
    public function __construct(
        string $message = 'Your role does not have permission to perform this action',
        public readonly ?string $role = null,
        public readonly ?string $requiredRole = null,
    ) {
        parent::__construct($message);
    }
}
