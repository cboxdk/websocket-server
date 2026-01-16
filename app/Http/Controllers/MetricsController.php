<?php

namespace App\Http\Controllers;

use App\Metrics\Contracts\MetricsStore;
use App\Metrics\PrometheusExporter;
use App\Reverb\FileApplicationProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\Response;
use Laravel\Reverb\Application;
use Throwable;

class MetricsController extends Controller
{
    public function __construct(
        protected PrometheusExporter $exporter,
        protected MetricsStore $store,
        protected FileApplicationProvider $appProvider,
        protected BroadcastManager $broadcast
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        if (! config('metrics.enabled', true)) {
            return response('Metrics disabled', 503);
        }

        // Clear any stale in-memory metrics
        $this->store->clear();

        // Add server info metrics
        $this->addServerInfoMetrics();

        // Add real-time WebSocket metrics from Reverb
        $this->addReverbMetrics();

        $content = $this->exporter->export();

        return response($content, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Add server info metrics.
     */
    protected function addServerInfoMetrics(): void
    {
        $scalingEnabled = config('reverb.servers.reverb.scaling.enabled', false);
        $appCount = $this->appProvider->all()->count();

        $this->store->gauge('reverb_server_info', 1, [
            'version' => app()->version(),
            'php_version' => PHP_VERSION,
            'scaling_mode' => $scalingEnabled ? 'cluster' : 'standalone',
        ]);

        $this->store->gauge('reverb_apps_configured', $appCount);
    }

    /**
     * Add real-time WebSocket metrics by querying Reverb's API.
     */
    protected function addReverbMetrics(): void
    {
        $reverbUp = false;
        $totalConnections = 0;
        $totalChannels = 0;

        foreach ($this->appProvider->all() as $app) {
            try {
                $metrics = $this->fetchAppMetrics($app);

                if ($metrics !== null) {
                    $reverbUp = true;
                    $appId = $app->id();

                    // Connection count per app
                    $this->store->gauge('reverb_connections_total', $metrics['connections'], [
                        'app_id' => $appId,
                    ]);
                    $totalConnections += $metrics['connections'];

                    // Channel counts per app
                    foreach ($metrics['channels'] as $type => $count) {
                        $this->store->gauge('reverb_channels_active', $count, [
                            'app_id' => $appId,
                            'type' => $type,
                        ]);
                        $totalChannels += $count;
                    }
                }
            } catch (Throwable) {
                // Skip this app if we can't reach it
            }
        }

        // Server up/down status
        $this->store->gauge('reverb_up', $reverbUp ? 1 : 0);

        // Add totals if Reverb is up
        if ($reverbUp) {
            $this->store->gauge('reverb_connections_current', $totalConnections);
            $this->store->gauge('reverb_channels_current', $totalChannels);
        }
    }

    /**
     * Fetch metrics for a specific app from Reverb's API.
     *
     * @return array{connections: int, channels: array<string, int>}|null
     */
    protected function fetchAppMetrics(Application $app): ?array
    {
        $pusher = $this->createPusherClient($app);

        // Get connection count
        $connectionsResult = $pusher->get('/connections');
        if (! $connectionsResult) {
            return null;
        }

        $connections = $connectionsResult->connections ?? 0;

        // Get channel info
        $channelsResult = $pusher->get('/channels', [
            'info' => 'subscription_count',
        ]);

        $channels = [
            'public' => 0,
            'private' => 0,
            'presence' => 0,
            'encrypted' => 0,
        ];

        if ($channelsResult && isset($channelsResult->channels)) {
            foreach ($channelsResult->channels as $name => $info) {
                $type = $this->determineChannelType($name);
                $channels[$type]++;
            }
        }

        return [
            'connections' => $connections,
            'channels' => $channels,
        ];
    }

    /**
     * Create a Pusher client configured to talk to Reverb.
     */
    protected function createPusherClient(Application $app): \Pusher\Pusher
    {
        $options = $app->options();

        return $this->broadcast->pusher([
            'key' => $app->key(),
            'secret' => $app->secret(),
            'app_id' => $app->id(),
            'options' => [
                'host' => $options['host'] ?? config('reverb.servers.reverb.host', 'localhost'),
                'port' => $options['port'] ?? config('reverb.servers.reverb.port', 8080),
                'scheme' => $options['scheme'] ?? 'http',
                'useTLS' => $options['useTLS'] ?? false,
            ],
        ]);
    }

    /**
     * Determine the channel type from its name.
     */
    protected function determineChannelType(string $name): string
    {
        if (str_starts_with($name, 'private-encrypted-')) {
            return 'encrypted';
        }

        if (str_starts_with($name, 'private-')) {
            return 'private';
        }

        if (str_starts_with($name, 'presence-')) {
            return 'presence';
        }

        return 'public';
    }
}
