<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectPhaseController;
use Illuminate\Support\Facades\Route;

/**
 * Project Routes — API v1
 *
 * Full project CRUD with phases and timeline.
 * All routes prefixed with /api/v1 by parent group in api.php.
 */
Route::prefix('projects')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])
        ->name('api.v1.projects.index');

    Route::post('/', [ProjectController::class, 'store'])
        ->middleware('role:admin')
        ->name('api.v1.projects.store');

    Route::get('{project}', [ProjectController::class, 'show'])
        ->name('api.v1.projects.show');

    Route::put('{project}', [ProjectController::class, 'update'])
        ->name('api.v1.projects.update');

    Route::delete('{project}', [ProjectController::class, 'destroy'])
        ->middleware('role:admin')
        ->name('api.v1.projects.destroy');

    Route::put('{project}/status', [ProjectController::class, 'transitionStatus'])
        ->middleware('role:admin')
        ->name('api.v1.projects.transition-status');

    Route::get('{project}/timeline', [ProjectController::class, 'timeline'])
        ->name('api.v1.projects.timeline');

    // Phases (nested resource)
    Route::prefix('{project}/phases')->group(function () {
        Route::get('/', [ProjectPhaseController::class, 'index'])
            ->name('api.v1.projects.phases.index');

        Route::post('/', [ProjectPhaseController::class, 'store'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.store');

        Route::put('{phase}', [ProjectPhaseController::class, 'update'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.update');

        Route::delete('{phase}', [ProjectPhaseController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.destroy');
    });
});
