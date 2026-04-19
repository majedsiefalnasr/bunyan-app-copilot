<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/**
 * Category Routes — API v1
 *
 * Product category hierarchy with full CRUD, reordering, and movement.
 * All routes are prefixed with /api/v1/categories by the parent group in api.php.
 *
 * Public (no auth required):
 * - GET /api/v1/categories — Tree structure (active only)
 * - GET /api/v1/categories/{id} — Single category with children
 *
 * Protected (admin only):
 * - POST /api/v1/categories — Create category
 * - PUT /api/v1/categories/{id} — Update category
 * - PUT /api/v1/categories/{id}/reorder — Reorder category within siblings
 * - PUT /api/v1/categories/{id}/move — Move category to different parent
 * - DELETE /api/v1/categories/{id} — Soft-delete category
 */
Route::prefix('categories')->group(function () {
    // All category routes require authentication per API contract
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])
            ->name('api.v1.categories.index');

        Route::get('{category}', [CategoryController::class, 'show'])
            ->name('api.v1.categories.show');

        Route::post('/', [CategoryController::class, 'store'])
            ->name('api.v1.categories.store');

        Route::put('{id}', [CategoryController::class, 'update'])
            ->name('api.v1.categories.update');

        Route::put('{id}/reorder', [CategoryController::class, 'reorder'])
            ->name('api.v1.categories.reorder');

        Route::put('{id}/move', [CategoryController::class, 'move'])
            ->name('api.v1.categories.move');

        Route::delete('{id}', [CategoryController::class, 'destroy'])
            ->name('api.v1.categories.destroy');
    });
});
