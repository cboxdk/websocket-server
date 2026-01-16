<?php

use App\Http\Controllers\Api\AppController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Protected API routes for app management
Route::middleware(ApiTokenAuth::class)->prefix('apps')->group(function () {
    Route::get('/', [AppController::class, 'index'])->name('api.apps.index');
    Route::post('/', [AppController::class, 'store'])->name('api.apps.store');
    Route::get('{app}', [AppController::class, 'show'])->name('api.apps.show');
    Route::put('{app}', [AppController::class, 'update'])->name('api.apps.update');
    Route::delete('{app}', [AppController::class, 'destroy'])->name('api.apps.destroy');
    Route::post('{app}/regenerate-secret', [AppController::class, 'regenerateSecret'])->name('api.apps.regenerate-secret');
});

// Reload endpoint
Route::middleware(ApiTokenAuth::class)->post('/reload', [AppController::class, 'reload'])->name('api.reload');
