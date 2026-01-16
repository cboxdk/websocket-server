<?php

namespace App\Metrics;

use App\Metrics\Contracts\MetricsStore;

class InMemoryMetricsStore implements MetricsStore
{
    /**
     * Counter metrics storage.
     *
     * @var array<string, array{value: int, labels: array<string, string>}>
     */
    protected array $counters = [];

    /**
     * Gauge metrics storage.
     *
     * @var array<string, array{value: float, labels: array<string, string>}>
     */
    protected array $gauges = [];

    /**
     * Increment a counter metric.
     *
     * @param  array<string, string>  $labels
     */
    public function increment(string $name, array $labels = [], int $value = 1): void
    {
        $key = $this->buildKey($name, $labels);

        if (! isset($this->counters[$key])) {
            $this->counters[$key] = ['value' => 0, 'labels' => $labels, 'name' => $name];
        }

        $this->counters[$key]['value'] += $value;
    }

    /**
     * Set a gauge metric value.
     *
     * @param  array<string, string>  $labels
     */
    public function gauge(string $name, float $value, array $labels = []): void
    {
        $key = $this->buildKey($name, $labels);
        $this->gauges[$key] = ['value' => $value, 'labels' => $labels, 'name' => $name];
    }

    /**
     * Increment a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function incrementGauge(string $name, array $labels = [], float $value = 1): void
    {
        $key = $this->buildKey($name, $labels);

        if (! isset($this->gauges[$key])) {
            $this->gauges[$key] = ['value' => 0.0, 'labels' => $labels, 'name' => $name];
        }

        $this->gauges[$key]['value'] += $value;
    }

    /**
     * Decrement a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function decrementGauge(string $name, array $labels = [], float $value = 1): void
    {
        $key = $this->buildKey($name, $labels);

        if (! isset($this->gauges[$key])) {
            $this->gauges[$key] = ['value' => 0.0, 'labels' => $labels, 'name' => $name];
        }

        $this->gauges[$key]['value'] -= $value;

        if ($this->gauges[$key]['value'] < 0) {
            $this->gauges[$key]['value'] = 0.0;
        }
    }

    /**
     * Get all counter metrics.
     *
     * @return array<string, array{value: int, labels: array<string, string>, name: string}>
     */
    public function getCounters(): array
    {
        return $this->counters;
    }

    /**
     * Get all gauge metrics.
     *
     * @return array<string, array{value: float, labels: array<string, string>, name: string}>
     */
    public function getGauges(): array
    {
        return $this->gauges;
    }

    /**
     * Clear all metrics.
     */
    public function clear(): void
    {
        $this->counters = [];
        $this->gauges = [];
    }

    /**
     * Build a unique key for a metric with labels.
     *
     * @param  array<string, string>  $labels
     */
    protected function buildKey(string $name, array $labels): string
    {
        if (empty($labels)) {
            return $name;
        }

        ksort($labels);

        return $name.':'.md5(json_encode($labels));
    }
}
