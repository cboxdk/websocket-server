<?php

use App\Metrics\InMemoryMetricsStore;
use App\Metrics\PrometheusExporter;

beforeEach(function () {
    $this->store = new InMemoryMetricsStore;
    $this->exporter = new PrometheusExporter($this->store);
});

test('exports empty metrics', function () {
    $output = $this->exporter->export();

    expect($output)->toContain('# No metrics collected yet');
});

test('exports gauge metric', function () {
    $this->store->gauge('reverb_connections_total', 42, ['app_id' => 'app-1']);

    $output = $this->exporter->export();

    expect($output)->toContain('# HELP reverb_connections_total');
    expect($output)->toContain('# TYPE reverb_connections_total gauge');
    expect($output)->toContain('reverb_connections_total{app_id="app-1"} 42');
});

test('exports counter metric', function () {
    $this->store->increment('custom_counter', ['app_id' => 'app-1']);

    $output = $this->exporter->export();

    expect($output)->toContain('custom_counter{app_id="app-1"} 1');
});

test('exports metric without labels', function () {
    $this->store->gauge('reverb_up', 1);

    $output = $this->exporter->export();

    expect($output)->toContain('reverb_up 1');
});

test('exports multiple metrics with same name and different labels', function () {
    $this->store->gauge('reverb_channels_active', 5, ['app_id' => 'app-1', 'type' => 'public']);
    $this->store->gauge('reverb_channels_active', 3, ['app_id' => 'app-1', 'type' => 'private']);
    $this->store->gauge('reverb_channels_active', 2, ['app_id' => 'app-2', 'type' => 'public']);

    $output = $this->exporter->export();

    expect($output)->toContain('reverb_channels_active{app_id="app-1",type="public"} 5');
    expect($output)->toContain('reverb_channels_active{app_id="app-1",type="private"} 3');
    expect($output)->toContain('reverb_channels_active{app_id="app-2",type="public"} 2');

    // Should only have one HELP and TYPE annotation
    expect(substr_count($output, '# HELP reverb_channels_active'))->toBe(1);
    expect(substr_count($output, '# TYPE reverb_channels_active'))->toBe(1);
});

test('escapes label values', function () {
    $this->store->increment('test_metric', ['label' => 'value with "quotes"']);

    $output = $this->exporter->export();

    expect($output)->toContain('label="value with \\"quotes\\""');
});

test('escapes newlines in label values', function () {
    $this->store->increment('test_metric', ['label' => "value with\nnewline"]);

    $output = $this->exporter->export();

    expect($output)->toContain('label="value with\\nnewline"');
});

test('formats float values correctly', function () {
    $this->store->gauge('test_gauge', 42.5);

    $output = $this->exporter->export();

    expect($output)->toContain('42.5');
});

test('formats integer values correctly', function () {
    $this->store->increment('test_counter', [], 100);

    $output = $this->exporter->export();

    expect($output)->toContain('test_counter 100');
});

test('provides help text for known metrics', function () {
    $this->store->gauge('reverb_up', 1);
    $this->store->gauge('reverb_connections_total', 10, ['app_id' => 'app-1']);
    $this->store->gauge('reverb_connections_current', 10);
    $this->store->gauge('reverb_channels_active', 5, ['app_id' => 'app-1', 'type' => 'public']);
    $this->store->gauge('reverb_channels_current', 5);
    $this->store->gauge('reverb_server_info', 1, ['version' => '12.0.0']);
    $this->store->gauge('reverb_apps_configured', 2);

    $output = $this->exporter->export();

    expect($output)->toContain('# HELP reverb_up Whether Reverb server is reachable (1 = up, 0 = down)');
    expect($output)->toContain('# HELP reverb_connections_total Current number of active WebSocket connections per app');
    expect($output)->toContain('# HELP reverb_connections_current Total current WebSocket connections across all apps');
    expect($output)->toContain('# HELP reverb_channels_active Current number of active channels by type');
    expect($output)->toContain('# HELP reverb_channels_current Total current active channels across all apps');
    expect($output)->toContain('# HELP reverb_server_info Reverb server information');
    expect($output)->toContain('# HELP reverb_apps_configured Number of configured WebSocket applications');
});

test('uses correct metric types', function () {
    $this->store->gauge('reverb_connections_total', 1, ['app_id' => 'app-1']);
    $this->store->gauge('reverb_up', 1);

    $output = $this->exporter->export();

    expect($output)->toContain('# TYPE reverb_connections_total gauge');
    expect($output)->toContain('# TYPE reverb_up gauge');
});
