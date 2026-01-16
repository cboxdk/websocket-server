<?php

namespace App\Metrics\Contracts;

interface MetricsStore
{
    /**
     * Increment a counter metric.
     *
     * @param  array<string, string>  $labels
     */
    public function increment(string $name, array $labels = [], int $value = 1): void;

    /**
     * Set a gauge metric value.
     *
     * @param  array<string, string>  $labels
     */
    public function gauge(string $name, float $value, array $labels = []): void;

    /**
     * Increment a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function incrementGauge(string $name, array $labels = [], float $value = 1): void;

    /**
     * Decrement a gauge metric.
     *
     * @param  array<string, string>  $labels
     */
    public function decrementGauge(string $name, array $labels = [], float $value = 1): void;

    /**
     * Get all counter metrics.
     *
     * @return array<string, array{value: int, labels: array<string, string>}>
     */
    public function getCounters(): array;

    /**
     * Get all gauge metrics.
     *
     * @return array<string, array{value: float, labels: array<string, string>}>
     */
    public function getGauges(): array;

    /**
     * Clear all metrics.
     */
    public function clear(): void;
}
