<?php

namespace App\Metrics;

use App\Metrics\Contracts\MetricsStore;

class PrometheusExporter
{
    /**
     * Metric type definitions for HELP and TYPE annotations.
     *
     * @var array<string, array{type: string, help: string}>
     */
    protected array $metricDefinitions = [
        'reverb_up' => [
            'type' => 'gauge',
            'help' => 'Whether Reverb server is reachable (1 = up, 0 = down)',
        ],
        'reverb_connections_total' => [
            'type' => 'gauge',
            'help' => 'Current number of active WebSocket connections per app',
        ],
        'reverb_connections_current' => [
            'type' => 'gauge',
            'help' => 'Total current WebSocket connections across all apps',
        ],
        'reverb_channels_active' => [
            'type' => 'gauge',
            'help' => 'Current number of active channels by type',
        ],
        'reverb_channels_current' => [
            'type' => 'gauge',
            'help' => 'Total current active channels across all apps',
        ],
        'reverb_server_info' => [
            'type' => 'gauge',
            'help' => 'Reverb server information',
        ],
        'reverb_apps_configured' => [
            'type' => 'gauge',
            'help' => 'Number of configured WebSocket applications',
        ],
    ];

    public function __construct(
        protected MetricsStore $store
    ) {}

    /**
     * Export all metrics in Prometheus text format.
     */
    public function export(): string
    {
        $output = [];
        $processedMetrics = [];

        $counters = $this->store->getCounters();
        foreach ($counters as $data) {
            $name = $data['name'];
            if (! isset($processedMetrics[$name])) {
                $output[] = $this->formatMetricHeader($name);
                $processedMetrics[$name] = true;
            }
            $output[] = $this->formatMetricLine($name, $data['value'], $data['labels']);
        }

        $gauges = $this->store->getGauges();
        foreach ($gauges as $data) {
            $name = $data['name'];
            if (! isset($processedMetrics[$name])) {
                $output[] = $this->formatMetricHeader($name);
                $processedMetrics[$name] = true;
            }
            $output[] = $this->formatMetricLine($name, $data['value'], $data['labels']);
        }

        if (empty($output)) {
            return "# No metrics collected yet\n";
        }

        return implode("\n", array_filter($output))."\n";
    }

    /**
     * Format the metric header (HELP and TYPE annotations).
     */
    protected function formatMetricHeader(string $name): string
    {
        $definition = $this->metricDefinitions[$name] ?? [
            'type' => 'untyped',
            'help' => 'No description available',
        ];

        return sprintf(
            "# HELP %s %s\n# TYPE %s %s",
            $name,
            $definition['help'],
            $name,
            $definition['type']
        );
    }

    /**
     * Format a single metric line with labels.
     *
     * @param  array<string, string>  $labels
     */
    protected function formatMetricLine(string $name, int|float $value, array $labels): string
    {
        if (empty($labels)) {
            return sprintf('%s %s', $name, $this->formatValue($value));
        }

        $labelParts = [];
        foreach ($labels as $key => $val) {
            $labelParts[] = sprintf('%s="%s"', $key, $this->escapeLabel($val));
        }

        return sprintf('%s{%s} %s', $name, implode(',', $labelParts), $this->formatValue($value));
    }

    /**
     * Format a metric value for output.
     */
    protected function formatValue(int|float $value): string
    {
        if (is_float($value)) {
            if (is_nan($value)) {
                return 'NaN';
            }
            if (is_infinite($value)) {
                return $value > 0 ? '+Inf' : '-Inf';
            }

            return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    /**
     * Escape a label value for Prometheus format.
     */
    protected function escapeLabel(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n"],
            ['\\\\', '\\"', '\\n'],
            $value
        );
    }
}
