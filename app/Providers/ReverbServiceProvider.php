<?php

namespace App\Providers;

use App\Listeners\ReverbMetricsListener;
use App\Metrics\Contracts\MetricsStore;
use App\Metrics\InMemoryMetricsStore;
use App\Metrics\PrometheusExporter;
use App\Metrics\RedisMetricsStore;
use App\Metrics\ReverbMetricsCollector;
use App\Reverb\FileApplicationProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ApplicationManager;

class ReverbServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerFileApplicationProvider();
        $this->registerMetricsStore();
        $this->registerMetricsCollector();
        $this->registerPrometheusExporter();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerFileDriver();
        $this->registerMetricsListener();
    }

    /**
     * Register the FileApplicationProvider singleton.
     */
    protected function registerFileApplicationProvider(): void
    {
        $this->app->singleton(FileApplicationProvider::class, function ($app) {
            $config = $app['config']->get('reverb.apps.file', []);

            return new FileApplicationProvider(
                $config['path'] ?? storage_path('reverb/apps.json'),
                $config['cache_ttl'] ?? 5
            );
        });
    }

    /**
     * Register the metrics store based on configuration.
     */
    protected function registerMetricsStore(): void
    {
        $this->app->singleton(MetricsStore::class, function ($app) {
            $driver = $app['config']->get('metrics.driver', 'auto');

            if ($driver === 'auto') {
                $driver = $app['config']->get('reverb.servers.reverb.scaling.enabled', false)
                    ? 'redis'
                    : 'memory';
            }

            return match ($driver) {
                'redis' => new RedisMetricsStore(
                    $app['config']->get('metrics.redis.prefix'),
                    $app['config']->get('metrics.redis.connection')
                ),
                default => new InMemoryMetricsStore,
            };
        });
    }

    /**
     * Register the metrics collector.
     */
    protected function registerMetricsCollector(): void
    {
        $this->app->singleton(ReverbMetricsCollector::class, function ($app) {
            return new ReverbMetricsCollector($app->make(MetricsStore::class));
        });
    }

    /**
     * Register the Prometheus exporter.
     */
    protected function registerPrometheusExporter(): void
    {
        $this->app->singleton(PrometheusExporter::class, function ($app) {
            return new PrometheusExporter($app->make(MetricsStore::class));
        });
    }

    /**
     * Register the file driver for Reverb applications.
     */
    protected function registerFileDriver(): void
    {
        $this->app->resolving(ApplicationManager::class, function (ApplicationManager $manager) {
            $manager->extend('file', function () {
                return $this->app->make(FileApplicationProvider::class);
            });
        });
    }

    /**
     * Register the metrics event listener.
     */
    protected function registerMetricsListener(): void
    {
        if (! $this->app['config']->get('metrics.enabled', true)) {
            return;
        }

        $this->app->make(Dispatcher::class)->subscribe(
            $this->app->make(ReverbMetricsListener::class)
        );
    }
}
