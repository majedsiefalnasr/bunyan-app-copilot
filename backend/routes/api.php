<?php

use App\Http\Controllers\Api\AdminRbacController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes — Bunyan v1
 *
 * Note: These routes are automatically prefixed with /api and versioned (/v1)
 * All routes accessed via: /api/v1/...
 * Authentication via Laravel Sanctum bearer tokens
 */
// Ensure API middleware group is applied so throttle, bindings, and other
// api-scoped middleware run for these test endpoints.
Route::middleware('api')->prefix('v1')->group(function () {
    /**
     * Health & Test Endpoints
     * Used for validation, monitoring, and error contract testing
     */
    Route::prefix('test')->group(function () {
        // Success response test endpoint
        Route::get('success', [TestController::class, 'testSuccess']);

        // Error response test endpoints for each error code
        // Accept GET and POST to support both contract checks and POST-based flow tests
        Route::match(['get', 'post'], 'error/auth-invalid-credentials', [TestController::class, 'testAuthInvalidCredentials']);
        Route::match(['get', 'post'], 'error/auth-token-expired', [TestController::class, 'testAuthTokenExpired']);
        Route::match(['get', 'post'], 'error/auth-unauthorized', [TestController::class, 'testAuthUnauthorized']);
        // RBAC endpoint — Supports all HTTP methods (GET, POST, PUT, DELETE) for the test matrix.
        // Tests call each method for the same URI with different datasets to simulate
        // distinct endpoints with role-specific ownership.
        Route::match(['get', 'post', 'put', 'delete'], 'error/rbac-role-denied', [TestController::class, 'testRbacRoleDenied']);
        Route::match(['get', 'post'], 'error/resource-not-found', [TestController::class, 'testResourceNotFound']);
        Route::match(['get', 'post'], 'error/validation-error', [TestController::class, 'testValidationError']);
        Route::match(['get', 'post'], 'error/conflict-error', [TestController::class, 'testConflictError']);
        Route::match(['get', 'post'], 'error/workflow-invalid-transition', [TestController::class, 'testWorkflowInvalidTransition']);
        Route::match(['get', 'post'], 'error/workflow-prerequisites-unmet', [TestController::class, 'testWorkflowPrerequisitesUnmet']);
        Route::match(['get', 'post'], 'error/payment-failed', [TestController::class, 'testPaymentFailed']);
        Route::match(['get', 'post'], 'error/rate-limit-exceeded', [TestController::class, 'testRateLimitExceeded']);
        Route::match(['get', 'post'], 'error/server-error', [TestController::class, 'testServerError']);
    });

    // Additional API routes will be added by other stages

    /**
     * Authentication Endpoints
     * Registration, login, logout, password reset, email verification, profile
     */
    Route::prefix('auth')->group(function () {
        // Public auth routes (with rate limiting)
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware(['throttle:auth-login', 'check-account-lockout']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth-forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        // Email verification (signed URL — no auth required)
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('signed')
            ->name('verification.verify');

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refreshToken']);
            Route::get('user', [AuthController::class, 'user']);
            Route::put('user', [AuthController::class, 'updateProfile']);
            Route::post('email/resend', [AuthController::class, 'resendVerification'])
                ->middleware('throttle:auth-email-resend');
        });
    });

    /**
    /**
     * User Endpoints
     * Avatar upload, profile management
     */
    Route::prefix('user')->middleware('auth:sanctum')->group(function () {
        Route::post('avatar', [UserController::class, 'uploadAvatar'])
            ->middleware('throttle:user-avatar-upload');
    });

    /**
     * Admin RBAC Endpoints
     * Role and permission management (admin-only)
     */
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('roles', [AdminRbacController::class, 'listRoles']);
        Route::get('roles/{id}', [AdminRbacController::class, 'showRole']);
        Route::put('roles/{id}/permissions', [AdminRbacController::class, 'syncPermissions']);
        Route::post('users/{id}/role', [AdminRbacController::class, 'assignRole']);
        Route::get('users', [AdminRbacController::class, 'listUsers']);
        Route::get('permissions', [AdminRbacController::class, 'listPermissions']);
    });
});
