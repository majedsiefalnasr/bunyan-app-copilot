<?php

use App\Enums\ApiErrorCode;

/**
 * Arabic error translation messages — Bunyan API (بنيان)
 *
 * Keys correspond to ApiErrorCode enum values (snake_cased).
 * Used by ApiErrorCode::defaultMessage() when locale starts with 'ar'.
 *
 * @see ApiErrorCode
 */
return [
    'health_check_failed' => 'فحص صحة المنصة فشل. يرجى المحاولة لاحقاً.',
    'server_error' => 'حدث خطأ غير متوقع. يرجى محاولة القيام بذلك لاحقاً.',
    'rate_limit_exceeded' => 'عدد كبير جداً من الطلبات. يرجى الانتظار قبل المحاولة مرة أخرى.',
    'resource_not_found' => 'المورد المطلوب غير موجود.',
    'validation_error' => 'بيانات غير صحيحة. يرجى التحقق من الحقول المطلوبة.',
    'auth_invalid_credentials' => 'بيانات دخول غير صحيحة.',
    'auth_token_expired' => 'انتهت جلستك. يرجى تسجيل الدخول مرة أخرى.',
    'auth_unauthorized' => 'غير مصرح لك بهذا الإجراء.',
    'rbac_role_denied' => 'دورك الحالي لا يسمح بهذا الإجراء.',
    'conflict_error' => 'تعارض في البيانات. قد يكون هناك نسخة مكررة أو تحديث متزامن.',
    'workflow_invalid_transition' => 'لا يمكن الانتقال إلى هذه الحالة من الحالة الحالية.',
    'workflow_prerequisites_unmet' => 'لم تتحقق متطلبات هذا الإجراء.',
    'payment_failed' => 'فشلت عملية الدفع. يرجى المحاولة مرة أخرى.',
];
