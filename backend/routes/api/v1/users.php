<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/**
 * User Routes — API v1
 *
 * Avatar upload, profile management.
 * All routes are prefixed with /api/v1/user by the parent group in api.php.
 */
Route::prefix('user')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('avatar', [UserController::class, 'uploadAvatar'])
            ->middleware('throttle:user-avatar-upload')
            ->name('api.v1.users.avatar');
    });
