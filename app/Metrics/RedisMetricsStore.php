<?php

namespace App\Metrics;

use App\Metrics\Contracts\MetricsStore;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;

class RedisMetricsStore implements MetricsStore
{
    protected string $prefix;

    protected string $connection;

    public function __construct(?string $prefix = null, ?string $connection = null)
    {
        $this->prefix = $prefix ?? config('metrics.redis.prefix', 'reverb:metrics:');
        $this->connection = $connection ?? config('metrics.redis.connection', 'default');
    }

    /**
     * Increment a counter metric.
     *
     * @param  array<string, string>  $labels
     */
    public function increment(string $name, array $labels = [], int $value = 1): void
    {
        $key = $this->buildKey('counter', $name, $labels);
        $labelsKey = $this->buildLabelsKey('counter', $name, $labels);

        $this->redis()->incrby($key, $value);
        $this->redis()->set($labelsKey, json_encode(['labels' => $labels, 'name' => $name]));
        $this->redis()->sadd($this->prefix.'counter_keys', $key);
    }

    /**
     * Set a gauge metric value.
     *
     * @param  array<string, string>  $labels
     */
    public function gauge(string $name, float $value, array $labels = []): void
    {
        $key = $this->buildKey('gauge', $name, $labels);
        $labelsKey = $this->buildLabelsKey('gauge', $name, $labels);

        $this->redis()->set($key, $value);
        $this->redis()->set($labelsKey, json_encode(['labels' => $labels, 'name' => $name]));
        $this->redis()->sadd($this->prefix.'gauge_keys', $key);
    }

    /**
     * Increment a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function incrementGauge(string $name, array $labels = [], float $value = 1): void
    {
        $key = $this->buildKey('gauge', $name, $labels);
        $labelsKey = $this->buildLabelsKey('gauge', $name, $labels);

        $this->redis()->incrbyfloat($key, $value);
        $this->redis()->set($labelsKey, json_encode(['labels' => $labels, 'name' => $name]));
        $this->redis()->sadd($this->prefix.'gauge_keys', $key);
    }

    /**
     * Decrement a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function decrementGauge(string $name, array $labels = [], float $value = 1): void
    {
        $key = $this->buildKey('gauge', $name, $labels);
        $labelsKey = $this->buildLabelsKey('gauge', $name, $labels);

        $current = (float) ($this->redis()->get($key) ?? 0);
        $newValue = max(0, $current - $value);

        $this->redis()->set($key, $newValue);
        $this->redis()->set($labelsKey, json_encode(['labels' => $labels, 'name' => $name]));
        $this->redis()->sadd($this->prefix.'gauge_keys', $key);
    }

    /**
     * Get all counter metrics.
     *
     * @return array<string, array{value: int, labels: array<string, string>, name: string}>
     */
    public function getCounters(): array
    {
        return $this->getMetrics('counter');
    }

    /**
     * Get all gauge metrics.
     *
     * @return array<string, array{value: float, labels: array<string, string>, name: string}>
     */
    public function getGauges(): array
    {
        return $this->getMetrics('gauge');
    }

    /**
     * Clear all metrics.
     */
    public function clear(): void
    {
        $counterKeys = $this->redis()->smembers($this->prefix.'counter_keys') ?? [];
        $gaugeKeys = $this->redis()->smembers($this->prefix.'gauge_keys') ?? [];

        foreach ($counterKeys as $key) {
            $this->redis()->del($key);
            $this->redis()->del($key.':labels');
        }

        foreach ($gaugeKeys as $key) {
            $this->redis()->del($key);
            $this->redis()->del($key.':labels');
        }

        $this->redis()->del($this->prefix.'counter_keys');
        $this->redis()->del($this->prefix.'gauge_keys');
    }

    /**
     * Get metrics of a specific type.
     *
     * @return array<string, array{value: int|float, labels: array<string, string>, name: string}>
     */
    protected function getMetrics(string $type): array
    {
        $keys = $this->redis()->smembers($this->prefix.$type.'_keys') ?? [];
        $metrics = [];

        foreach ($keys as $key) {
            $value = $this->redis()->get($key);
            $labelsData = $this->redis()->get($key.':labels');

            if ($value === null) {
                continue;
            }

            $data = $labelsData ? json_decode($labelsData, true) : ['labels' => [], 'name' => ''];

            $metrics[$key] = [
                'value' => $type === 'counter' ? (int) $value : (float) $value,
                'labels' => $data['labels'] ?? [],
                'name' => $data['name'] ?? '',
            ];
        }

        return $metrics;
    }

    /**
     * Build a Redis key for a metric.
     *
     * @param  array<string, string>  $labels
     */
    protected function buildKey(string $type, string $name, array $labels): string
    {
        $hash = $this->buildLabelHash($labels);

        return $this->prefix.$type.':'.$name.':'.$hash;
    }

    /**
     * Build a Redis key for storing metric labels.
     *
     * @param  array<string, string>  $labels
     */
    protected function buildLabelsKey(string $type, string $name, array $labels): string
    {
        return $this->buildKey($type, $name, $labels).':labels';
    }

    /**
     * Build a hash from labels for unique identification.
     *
     * @param  array<string, string>  $labels
     */
    protected function buildLabelHash(array $labels): string
    {
        if (empty($labels)) {
            return 'default';
        }

        ksort($labels);

        return md5(json_encode($labels));
    }

    /**
     * Get the Redis connection.
     */
    protected function redis(): Connection
    {
        return Redis::connection($this->connection);
    }
}
