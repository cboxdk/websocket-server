<?php

namespace App\Http\Controllers;

use App\Metrics\Contracts\MetricsStore;
use App\Metrics\PrometheusExporter;
use App\Reverb\FileApplicationProvider;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    public function __construct(
        protected PrometheusExporter $exporter,
        protected MetricsStore $store,
        protected FileApplicationProvider $appProvider
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        if (! config('metrics.enabled', true)) {
            return response('Metrics disabled', 503);
        }

        // Add server info metrics that are always available
        $this->addServerInfoMetrics();

        $content = $this->exporter->export();

        return response($content, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Add server info metrics that don't require Reverb process.
     */
    protected function addServerInfoMetrics(): void
    {
        $scalingEnabled = config('reverb.servers.reverb.scaling.enabled', false);
        $appCount = $this->appProvider->all()->count();

        // Server info gauge
        $this->store->gauge('reverb_server_info', 1, [
            'version' => app()->version(),
            'php_version' => PHP_VERSION,
            'scaling_mode' => $scalingEnabled ? 'cluster' : 'standalone',
        ]);

        // Number of configured apps
        $this->store->gauge('reverb_apps_configured', $appCount);

        // Add note about scaling mode for standalone
        if (! $scalingEnabled) {
            $this->store->gauge('reverb_standalone_mode', 1, [
                'note' => 'WebSocket metrics require REVERB_SCALING_ENABLED=true with Redis',
            ]);
        }
    }
}
