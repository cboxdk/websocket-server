<?php

namespace App\Providers;

use App\Metrics\Contracts\MetricsStore;
use App\Metrics\InMemoryMetricsStore;
use App\Metrics\PrometheusExporter;
use App\Reverb\FileApplicationProvider;
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
        $this->registerPrometheusExporter();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerFileDriver();
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
     * Register the metrics store.
     * Uses InMemoryMetricsStore since metrics are built on-demand from Reverb's API.
     */
    protected function registerMetricsStore(): void
    {
        $this->app->singleton(MetricsStore::class, function () {
            return new InMemoryMetricsStore;
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
}
