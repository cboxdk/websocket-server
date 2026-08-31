<?php

namespace App\Providers;

use App\Metrics\Contracts\MetricsStore;
use App\Metrics\InMemoryMetricsStore;
use App\Metrics\PrometheusExporter;
use App\Reverb\DatabaseApplicationProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ApplicationManager;

class ReverbServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerDatabaseApplicationProvider();
        $this->registerMetricsStore();
        $this->registerPrometheusExporter();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerDatabaseDriver();
    }

    /**
     * Register the DatabaseApplicationProvider singleton.
     */
    protected function registerDatabaseApplicationProvider(): void
    {
        $this->app->singleton(DatabaseApplicationProvider::class, function () {
            return new DatabaseApplicationProvider;
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
     * Register the database driver for Reverb applications.
     */
    protected function registerDatabaseDriver(): void
    {
        $this->app->resolving(ApplicationManager::class, function (ApplicationManager $manager) {
            // Resolve through the container the Manager hands the creator, not through
            // $this->app. Reverb 1.10.2 invokes a custom driver creator bound to the
            // ApplicationManager instance, so inside this closure $this is the manager,
            // and $this->app reads an undefined property there — the server refused to
            // start with "Undefined property Laravel\Reverb\ApplicationManager::$app".
            // The manager passes the container as the argument, which is what the
            // config-provider creators already rely on.
            $manager->extend('database', function ($app) {
                return $app->make(DatabaseApplicationProvider::class);
            });
        });
    }
}
