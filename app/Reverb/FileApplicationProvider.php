<?php

namespace App\Reverb;

use Illuminate\Support\Collection;
use Laravel\Reverb\Application;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Exceptions\InvalidApplication;
use RuntimeException;

class FileApplicationProvider implements ApplicationProvider
{
    protected Collection $applications;

    protected int $lastModified = 0;

    protected int $lastChecked = 0;

    public function __construct(
        protected string $configPath,
        protected int $cacheTtl = 5
    ) {
        $this->applications = collect();
        $this->loadApps();
    }

    /**
     * Get all of the configured applications as Application instances.
     *
     * @return Collection<int, Application>
     */
    public function all(): Collection
    {
        $this->refreshIfNeeded();

        return $this->applications->map(fn (array $app) => $this->createApplication($app));
    }

    /**
     * Find an application instance by ID.
     *
     * @throws InvalidApplication
     */
    public function findById(string $id): Application
    {
        return $this->find('id', $id);
    }

    /**
     * Find an application instance by key.
     *
     * @throws InvalidApplication
     */
    public function findByKey(string $key): Application
    {
        return $this->find('key', $key);
    }

    /**
     * Find an application instance.
     *
     * @throws InvalidApplication
     */
    protected function find(string $key, mixed $value): Application
    {
        $this->refreshIfNeeded();

        $app = $this->applications->firstWhere($key, $value);

        if (! $app) {
            throw new InvalidApplication;
        }

        return $this->createApplication($app);
    }

    /**
     * Create an Application instance from array data.
     */
    protected function createApplication(array $app): Application
    {
        return new Application(
            $app['id'],
            $app['key'],
            $app['secret'],
            $app['options']['ping_interval'] ?? $app['ping_interval'] ?? 60,
            $app['options']['activity_timeout'] ?? $app['activity_timeout'] ?? 30,
            $app['allowed_origins'] ?? ['*'],
            $app['max_message_size'] ?? 10_000,
            $app['max_connections'] ?? null,
            $app['options'] ?? [],
        );
    }

    /**
     * Load applications from JSON file.
     *
     * @throws RuntimeException
     */
    protected function loadApps(): void
    {
        if (! file_exists($this->configPath)) {
            throw new RuntimeException("Reverb apps configuration file not found: {$this->configPath}");
        }

        $content = file_get_contents($this->configPath);

        if ($content === false) {
            throw new RuntimeException("Unable to read Reverb apps configuration file: {$this->configPath}");
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! isset($data['apps']) || ! is_array($data['apps'])) {
            throw new RuntimeException("Invalid Reverb apps configuration: 'apps' key must be an array");
        }

        $this->applications = collect($data['apps']);
        $this->lastModified = (int) filemtime($this->configPath);
        $this->lastChecked = time();
    }

    /**
     * Refresh applications if the config file has changed.
     */
    protected function refreshIfNeeded(): void
    {
        $now = time();

        if (($now - $this->lastChecked) < $this->cacheTtl) {
            return;
        }

        $this->lastChecked = $now;

        if (! file_exists($this->configPath)) {
            return;
        }

        $currentMtime = (int) filemtime($this->configPath);

        if ($currentMtime > $this->lastModified) {
            $this->loadApps();
        }
    }

    /**
     * Force reload the applications from the file.
     */
    public function reload(): void
    {
        clearstatcache(true, $this->configPath);
        $this->loadApps();
    }

    /**
     * Get the raw applications data.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRawApplications(): Collection
    {
        $this->refreshIfNeeded();

        return $this->applications;
    }

    /**
     * Save applications to the JSON file.
     *
     * @param  Collection<int, array<string, mixed>>  $apps
     *
     * @throws RuntimeException
     */
    public function saveApps(Collection $apps): void
    {
        $directory = dirname($this->configPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = json_encode(
            ['apps' => $apps->values()->all()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if ($content === false) {
            throw new RuntimeException('Unable to encode apps configuration to JSON');
        }

        $tempPath = $this->configPath.'.tmp';

        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write to temporary file: {$tempPath}");
        }

        if (! rename($tempPath, $this->configPath)) {
            unlink($tempPath);
            throw new RuntimeException("Unable to save apps configuration to: {$this->configPath}");
        }

        $this->applications = $apps;
        $this->lastModified = (int) filemtime($this->configPath);
        $this->lastChecked = time();
    }

    /**
     * Add a new application.
     *
     * @param  array<string, mixed>  $app
     */
    public function addApp(array $app): void
    {
        $this->refreshIfNeeded();

        $apps = $this->applications->push($app);
        $this->saveApps($apps);
    }

    /**
     * Update an existing application.
     *
     * @param  array<string, mixed>  $app
     *
     * @throws RuntimeException
     */
    public function updateApp(string $id, array $app): void
    {
        $this->refreshIfNeeded();

        $index = $this->applications->search(fn (array $a) => $a['id'] === $id);

        if ($index === false) {
            throw new RuntimeException("Application not found: {$id}");
        }

        $apps = $this->applications->replace([$index => array_merge(['id' => $id], $app)]);
        $this->saveApps($apps);
    }

    /**
     * Delete an application.
     *
     * @throws RuntimeException
     */
    public function deleteApp(string $id): void
    {
        $this->refreshIfNeeded();

        $apps = $this->applications->reject(fn (array $a) => $a['id'] === $id);

        if ($apps->count() === $this->applications->count()) {
            throw new RuntimeException("Application not found: {$id}");
        }

        $this->saveApps($apps);
    }

    /**
     * Check if an application exists by ID.
     */
    public function exists(string $id): bool
    {
        $this->refreshIfNeeded();

        return $this->applications->contains(fn (array $a) => $a['id'] === $id);
    }

    /**
     * Check if an application key exists.
     */
    public function keyExists(string $key, ?string $excludeId = null): bool
    {
        $this->refreshIfNeeded();

        return $this->applications->contains(function (array $a) use ($key, $excludeId) {
            if ($excludeId !== null && $a['id'] === $excludeId) {
                return false;
            }

            return $a['key'] === $key;
        });
    }
}
