<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

/**
 * API Routes — Bunyan v1
 *
 * Note: These routes are automatically prefixed with /api and versioned (/v1)
 * All routes accessed via: /api/v1/...
 * Authentication via Laravel Sanctum bearer tokens
 *
 * Route sub-files:
 * - api/v1/auth.php   — Registration, login, logout, password reset, email verification, profile
 * - api/v1/users.php  — Avatar upload, profile management
 * - api/v1/admin.php  — Admin RBAC: role and permission management
 */

/**
 * Health Check — Outside v1 prefix, no auth, no throttle.
 * Accessed via: GET /api/health
 */
Route::get('health', [HealthController::class, 'check'])->name('api.health');

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

    // V1 route sub-files — extracted for maintainability
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/users.php';
    require __DIR__.'/api/v1/admin.php';
    require __DIR__.'/api/v1/categories.php';
    require __DIR__.'/api/v1/suppliers.php';
    require __DIR__.'/api/v1/projects.php';
});
