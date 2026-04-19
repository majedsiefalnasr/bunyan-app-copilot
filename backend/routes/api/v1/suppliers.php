<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────────────────
// Public routes (no authentication required)
// ──────────────────────────────────────────────────────────────────────────────
Route::prefix('suppliers')->name('api.v1.suppliers.')->group(function () {
    Route::get('/', [SupplierController::class, 'index'])->name('index');
    Route::get('/{id}', [SupplierController::class, 'show'])->name('show');
    Route::get('/{supplier}/products', [SupplierController::class, 'products'])->name('products');

    // ─────────────────────────────────────────────────────────────────────────
    // Authenticated routes
    // ─────────────────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        Route::put('/{supplier}/verify', [SupplierController::class, 'verify'])->name('verify');
        Route::put('/{supplier}/suspend', [SupplierController::class, 'suspend'])->name('suspend');
    });
});
