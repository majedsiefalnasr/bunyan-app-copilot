<?php

use App\Enums\ApiErrorCode;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes — Bunyan v1
 * 
 * Note: These routes are automatically prefixed with /api and versioned (/v1)
 * All routes accessed via: /api/v1/...
 * Authentication via Laravel Sanctum bearer tokens
 */

Route::prefix('v1')->group(function () {
    /**
     * Health & Test Endpoints
     * Used for validation, monitoring, and error contract testing
     */
    Route::prefix('test')->group(function () {
        // Success response test endpoint
        Route::get('success', [TestController::class, 'testSuccess']);

        // Error response test endpoints for each error code
        Route::get('error/auth-invalid-credentials', [TestController::class, 'testAuthInvalidCredentials']);
        Route::get('error/auth-token-expired', [TestController::class, 'testAuthTokenExpired']);
        Route::get('error/auth-unauthorized', [TestController::class, 'testAuthUnauthorized']);
        Route::get('error/rbac-role-denied', [TestController::class, 'testRbacRoleDenied']);
        Route::get('error/resource-not-found', [TestController::class, 'testResourceNotFound']);
        Route::get('error/validation-error', [TestController::class, 'testValidationError']);
        Route::get('error/conflict-error', [TestController::class, 'testConflictError']);
        Route::get('error/workflow-invalid-transition', [TestController::class, 'testWorkflowInvalidTransition']);
        Route::get('error/workflow-prerequisites-unmet', [TestController::class, 'testWorkflowPrerequisitesUnmet']);
        Route::get('error/payment-failed', [TestController::class, 'testPaymentFailed']);
        Route::get('error/rate-limit-exceeded', [TestController::class, 'testRateLimitExceeded']);
        Route::get('error/server-error', [TestController::class, 'testServerError']);
    });

    // Additional API routes will be added by other stages
});
