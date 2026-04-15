<?php

use App\Enums\ApiErrorCode;

/**
 * English error translation messages — Bunyan API
 *
 * Keys correspond to ApiErrorCode enum values (snake_cased).
 * Used by ApiErrorCode::defaultMessage() when locale is 'en'.
 *
 * @see ApiErrorCode
 */
return [
    'health_check_failed' => 'Platform health check failed. Please try again later.',
    'server_error' => 'An unexpected error occurred. Please try again later.',
    'rate_limit_exceeded' => 'Too many requests. Please wait before trying again.',
    'resource_not_found' => 'The requested resource was not found.',
    'validation_error' => 'Invalid data. Please check the required fields.',
    'auth_invalid_credentials' => 'Invalid login credentials.',
    'auth_token_expired' => 'Your session has expired. Please log in again.',
    'auth_unauthorized' => 'You are not authorized to perform this action.',
    'rbac_role_denied' => 'Your current role does not allow this action.',
    'conflict_error' => 'There is a conflict with the data. A duplicate may exist or there was a concurrent update.',
    'workflow_invalid_transition' => 'Cannot transition to this status from the current status.',
    'workflow_prerequisites_unmet' => 'The prerequisites for this action have not been met.',
    'payment_failed' => 'Payment processing failed. Please try again.',
];
