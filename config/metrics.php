<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Metrics Enabled
    |--------------------------------------------------------------------------
    |
    | This option determines if metrics collection and exposure is enabled.
    | When disabled, no metrics will be collected or exposed via the endpoint.
    |
    */

    'enabled' => env('METRICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Metrics Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option determines where metrics are stored. In standalone mode,
    | metrics are stored in-memory (process-local). In cluster mode with
    | REVERB_SCALING_ENABLED=true, Redis is used for shared metrics.
    |
    | Supported: "memory", "redis", "auto"
    |
    */

    'driver' => env('METRICS_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Redis-based metrics storage. Only used when driver
    | is "redis" or when "auto" and REVERB_SCALING_ENABLED is true.
    |
    */

    'redis' => [
        'connection' => env('METRICS_REDIS_CONNECTION', 'default'),
        'prefix' => env('METRICS_REDIS_PREFIX', 'reverb:metrics:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Authentication
    |--------------------------------------------------------------------------
    |
    | Optional authentication for the /metrics endpoint. Set METRICS_AUTH_TOKEN
    | to require Bearer token authentication.
    |
    */

    'auth' => [
        'token' => env('METRICS_AUTH_TOKEN'),
        'allowed_ips' => env('METRICS_ALLOWED_IPS') ? explode(',', env('METRICS_ALLOWED_IPS')) : [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server Information
    |--------------------------------------------------------------------------
    |
    | Information included in the reverb_server_info metric.
    |
    */

    'server' => [
        'instance' => env('METRICS_INSTANCE_ID', gethostname()),
    ],

];
