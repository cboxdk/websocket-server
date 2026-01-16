<?php

use App\Http\Controllers\MetricsController;
use App\Http\Middleware\MetricsAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// /docs route is handled by Scalar Laravel package (config/scalar.php)

// Prometheus metrics endpoint (standard path)
Route::middleware(MetricsAuth::class)->get('/metrics', MetricsController::class)->name('metrics');

// Health check endpoint for Docker/Kubernetes
Route::get('/health', function () {
    $reverbHost = config('reverb.servers.reverb.host', '0.0.0.0');
    $reverbPort = config('reverb.servers.reverb.port', 8080);

    // Check if Reverb is listening
    $reverbHealthy = false;
    $connection = @fsockopen($reverbHost === '0.0.0.0' ? '127.0.0.1' : $reverbHost, $reverbPort, $errno, $errstr, 2);

    if ($connection) {
        fclose($connection);
        $reverbHealthy = true;
    }

    $status = $reverbHealthy ? 'healthy' : 'unhealthy';
    $statusCode = $reverbHealthy ? 200 : 503;

    return response()->json([
        'status' => $status,
        'timestamp' => now()->toIso8601String(),
        'checks' => [
            'reverb' => $reverbHealthy ? 'up' : 'down',
        ],
    ], $statusCode);
})->name('health');
