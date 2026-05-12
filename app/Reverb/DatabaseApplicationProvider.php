<?php

namespace App\Reverb;

use App\Models\ReverbApplication;
use Illuminate\Support\Collection;
use Laravel\Reverb\Application;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Exceptions\InvalidApplication;
use RuntimeException;

class DatabaseApplicationProvider implements ApplicationProvider
{
    /**
     * Get all of the configured applications as Application instances.
     *
     * @return Collection<int, Application>
     */
    public function all(): Collection
    {
        return ReverbApplication::all()->map(fn (ReverbApplication $app) => $this->createApplication($app));
    }

    /**
     * Find an application instance by ID.
     *
     * @throws InvalidApplication
     */
    public function findById(string $id): Application
    {
        $app = ReverbApplication::find($id);

        if (! $app) {
            throw new InvalidApplication;
        }

        return $this->createApplication($app);
    }

    /**
     * Find an application instance by key.
     *
     * @throws InvalidApplication
     */
    public function findByKey(string $key): Application
    {
        $app = ReverbApplication::query()->where('key', $key)->first();

        if (! $app) {
            throw new InvalidApplication;
        }

        return $this->createApplication($app);
    }

    /**
     * Create an Application instance from model.
     *
     * Reverb 1.x's Application constructor accepts:
     *   id, key, secret, pingInterval, activityTimeout, allowedOrigins,
     *   maxMessageSize, ?maxConnections, acceptClientEventsFrom,
     *   ?rateLimiting, options
     *
     * We honour the legacy `enable_client_messages` boolean by mapping
     * true → "all", false → "none". Override per-app by setting
     * `options.accept_client_events_from` (one of "all" | "members" | "none")
     * which matches Reverb's native config shape.
     */
    protected function createApplication(ReverbApplication $app): Application
    {
        $options = $app->options ?? [];

        $acceptClientEventsFrom = $options['accept_client_events_from']
            ?? ($app->enable_client_messages ? 'all' : 'none');

        return new Application(
            $app->id,
            $app->key,
            $app->secret,
            $options['ping_interval'] ?? 60,
            $options['activity_timeout'] ?? 30,
            $app->allowed_origins ?? ['*'],
            $app->max_message_size ?? 10_000,
            $app->max_connections,
            $acceptClientEventsFrom,
            $options['rate_limiting'] ?? null,
            $options,
        );
    }

    /**
     * Get the raw applications data.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRawApplications(): Collection
    {
        return ReverbApplication::all()->map(fn (ReverbApplication $app) => $app->toArray());
    }

    /**
     * Add a new application.
     *
     * @param  array<string, mixed>  $app
     */
    public function addApp(array $app): ReverbApplication
    {
        return ReverbApplication::create($app);
    }

    /**
     * Update an existing application.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws RuntimeException
     */
    public function updateApp(string $id, array $data): ReverbApplication
    {
        $app = ReverbApplication::find($id);

        if (! $app) {
            throw new RuntimeException("Application not found: {$id}");
        }

        $app->update($data);

        return $app->fresh();
    }

    /**
     * Delete an application.
     *
     * @throws RuntimeException
     */
    public function deleteApp(string $id): void
    {
        $app = ReverbApplication::find($id);

        if (! $app) {
            throw new RuntimeException("Application not found: {$id}");
        }

        $app->delete();
    }

    /**
     * Check if an application exists by ID.
     */
    public function exists(string $id): bool
    {
        return ReverbApplication::query()->where('id', $id)->exists();
    }

    /**
     * Check if an application key exists.
     */
    public function keyExists(string $key, ?string $excludeId = null): bool
    {
        $query = ReverbApplication::query()->where('key', $key);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Force reload the applications (no-op for database provider).
     */
    public function reload(): void
    {
        // No-op for database provider - always reads fresh from DB
    }
}
