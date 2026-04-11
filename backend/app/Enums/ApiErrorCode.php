<?php

namespace App\Enums;

/**
 * API Error Code Registry — Bunyan
 *
 * Semantic error codes for consistent API error responses.
 * Error codes are stable (never modified after deployment).
 * New error scenarios use new codes (no code overwrites).
 *
 * @see specs/runtime/005-error-handling/contracts/error-codes-registry.json
 */
enum ApiErrorCode: string
{
    // Authentication & Authorization (4xx)
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_UNAUTHORIZED = 'AUTH_UNAUTHORIZED';
    case RBAC_ROLE_DENIED = 'RBAC_ROLE_DENIED';

    // Resource & Input Errors (4xx)
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case CONFLICT_ERROR = 'CONFLICT_ERROR';

    // Workflow Errors (4xx)
    case WORKFLOW_INVALID_TRANSITION = 'WORKFLOW_INVALID_TRANSITION';
    case WORKFLOW_PREREQUISITES_UNMET = 'WORKFLOW_PREREQUISITES_UNMET';

    // Business Logic Errors (4xx)
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';

    // Server Error (5xx)
    case SERVER_ERROR = 'SERVER_ERROR';

    /**
     * Get HTTP status code for this error code.
     *
     * @return int HTTP status code (401, 403, 404, 409, 422, 429, 500)
     */
    public function httpStatus(): int
    {
        return match ($this) {
            // 401 Unauthorized
            self::AUTH_INVALID_CREDENTIALS,
            self::AUTH_TOKEN_EXPIRED => 401,

            // 403 Forbidden
            self::AUTH_UNAUTHORIZED,
            self::RBAC_ROLE_DENIED => 403,

            // 404 Not Found
            self::RESOURCE_NOT_FOUND => 404,

            // 409 Conflict
            self::CONFLICT_ERROR => 409,

            // 422 Unprocessable Entity
            self::VALIDATION_ERROR,
            self::WORKFLOW_INVALID_TRANSITION,
            self::WORKFLOW_PREREQUISITES_UNMET,
            self::PAYMENT_FAILED => 422,

            // 429 Too Many Requests
            self::RATE_LIMIT_EXCEEDED => 429,

            // 500 Internal Server Error
            self::SERVER_ERROR => 500,
        };
    }

    /**
     * Get default user-friendly message for this error code.
     *
     * Supports Arabic (ar_SA) and English (en_US) localization.
     * Falls back to English if locale not supported.
     *
     * @param  string|null  $locale  Locale code (ar_SA, en_US, etc.)
     * @return string User-friendly error message
     */
    public function defaultMessage(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';

        // Arabic messages (ar_SA, ar, etc.)
        if (str_starts_with($locale, 'ar')) {
            return match ($this) {
                self::AUTH_INVALID_CREDENTIALS => 'بيانات دخول غير صحيحة',
                self::AUTH_TOKEN_EXPIRED => 'انتهت جلستك. يرجى تسجيل الدخول مرة أخرى',
                self::AUTH_UNAUTHORIZED => 'غير مصرح لك بهذا الإجراء',
                self::RBAC_ROLE_DENIED => 'دورك الحالي لا يسمح بهذا الإجراء',
                self::RESOURCE_NOT_FOUND => 'المورد المطلوب غير موجود',
                self::VALIDATION_ERROR => 'بيانات غير صحيحة. يرجى التحقق من الحقول المطلوبة',
                self::CONFLICT_ERROR => 'تعارض في البيانات. قد يكون هناك نسخة مكررة أو تحديث متزامن',
                self::WORKFLOW_INVALID_TRANSITION => 'لا يمكن الانتقال إلى هذه الحالة من الحالة الحالية',
                self::WORKFLOW_PREREQUISITES_UNMET => 'لم تتحقق متطلبات هذا الإجراء',
                self::PAYMENT_FAILED => 'فشلت عملية الدفع. يرجى المحاولة مرة أخرى',
                self::RATE_LIMIT_EXCEEDED => 'عدد كبير جداً من الطلبات. يرجى الانتظار قبل المحاولة مرة أخرى',
                self::SERVER_ERROR => 'حدث خطأ غير متوقع. يرجى محاولة القيام بذلك لاحقاً',
            };
        }

        // English messages (default)
        return match ($this) {
            self::AUTH_INVALID_CREDENTIALS => 'Invalid login credentials',
            self::AUTH_TOKEN_EXPIRED => 'Your session has expired. Please log in again',
            self::AUTH_UNAUTHORIZED => 'You are not authorized to perform this action',
            self::RBAC_ROLE_DENIED => 'Your current role does not allow this action',
            self::RESOURCE_NOT_FOUND => 'The requested resource was not found',
            self::VALIDATION_ERROR => 'Invalid data. Please check the required fields',
            self::CONFLICT_ERROR => 'There is a conflict with the data. A duplicate may exist or there was a concurrent update',
            self::WORKFLOW_INVALID_TRANSITION => 'Cannot transition to this status from the current status',
            self::WORKFLOW_PREREQUISITES_UNMET => 'The prerequisites for this action have not been met',
            self::PAYMENT_FAILED => 'Payment processing failed. Please try again',
            self::RATE_LIMIT_EXCEEDED => 'Too many requests. Please wait before trying again',
            self::SERVER_ERROR => 'An unexpected error occurred. Please try again later',
        };
    }
}
