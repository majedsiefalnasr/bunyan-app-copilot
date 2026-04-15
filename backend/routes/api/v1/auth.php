<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/**
 * Auth Routes — API v1
 *
 * Registration, login, logout, password reset, email verification, profile.
 * All routes are prefixed with /api/v1/auth by the parent group in api.php.
 */
Route::prefix('auth')->group(function () {
    // Public auth routes (with rate limiting)
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:auth-register')
        ->name('api.v1.auth.register');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware(['throttle:auth-login', 'check-account-lockout'])
        ->name('api.v1.auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:auth-forgot-password')
        ->name('api.v1.auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
        ->name('api.v1.auth.reset-password');

    // Email verification (signed URL — no auth required)
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');
        Route::post('refresh', [AuthController::class, 'refreshToken'])
            ->name('api.v1.auth.refresh');
        Route::get('user', [AuthController::class, 'user'])
            ->name('api.v1.auth.user');
        Route::put('user', [AuthController::class, 'updateProfile'])
            ->name('api.v1.auth.update-profile');
        Route::post('email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:auth-email-resend')
            ->name('api.v1.auth.email-resend');
    });
});
