<?php

use App\Http\Controllers\MetricsController;
use App\Http\Middleware\MetricsAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('docs');
});

// Prometheus metrics endpoint (standard path)
Route::middleware(MetricsAuth::class)->get('/metrics', MetricsController::class)->name('metrics');

// Health check endpoint for Docker/Kubernetes
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');
