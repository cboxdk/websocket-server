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

test('exports counter metric', function () {
    $this->store->increment('reverb_connections_created_total', ['app_id' => 'app-1']);

    $output = $this->exporter->export();

    expect($output)->toContain('# HELP reverb_connections_created_total');
    expect($output)->toContain('# TYPE reverb_connections_created_total counter');
    expect($output)->toContain('reverb_connections_created_total{app_id="app-1"} 1');
});

test('exports gauge metric', function () {
    $this->store->gauge('reverb_connections_total', 42, ['app_id' => 'app-1']);

    $output = $this->exporter->export();

    expect($output)->toContain('# HELP reverb_connections_total');
    expect($output)->toContain('# TYPE reverb_connections_total gauge');
    expect($output)->toContain('reverb_connections_total{app_id="app-1"} 42');
});

test('exports metric without labels', function () {
    $this->store->increment('reverb_subscriptions_total');

    $output = $this->exporter->export();

    expect($output)->toContain('reverb_subscriptions_total 1');
});

test('exports multiple metrics with same name and different labels', function () {
    $this->store->increment('reverb_messages_received_total', ['app_id' => 'app-1', 'channel_type' => 'public']);
    $this->store->increment('reverb_messages_received_total', ['app_id' => 'app-1', 'channel_type' => 'private']);
    $this->store->increment('reverb_messages_received_total', ['app_id' => 'app-2', 'channel_type' => 'public']);

    $output = $this->exporter->export();

    expect($output)->toContain('reverb_messages_received_total{app_id="app-1",channel_type="public"} 1');
    expect($output)->toContain('reverb_messages_received_total{app_id="app-1",channel_type="private"} 1');
    expect($output)->toContain('reverb_messages_received_total{app_id="app-2",channel_type="public"} 1');

    // Should only have one HELP and TYPE annotation
    expect(substr_count($output, '# HELP reverb_messages_received_total'))->toBe(1);
    expect(substr_count($output, '# TYPE reverb_messages_received_total'))->toBe(1);
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
    $this->store->gauge('reverb_connections_total', 1);
    $this->store->increment('reverb_connections_created_total');
    $this->store->increment('reverb_connections_closed_total', ['reason' => 'normal']);
    $this->store->increment('reverb_messages_received_total');
    $this->store->increment('reverb_messages_sent_total');
    $this->store->gauge('reverb_channels_active', 1);
    $this->store->increment('reverb_subscriptions_total');
    $this->store->gauge('reverb_server_info', 1, ['instance' => 'test']);

    $output = $this->exporter->export();

    expect($output)->toContain('# HELP reverb_connections_total Current number of active WebSocket connections');
    expect($output)->toContain('# HELP reverb_connections_created_total Total number of WebSocket connections created');
    expect($output)->toContain('# HELP reverb_connections_closed_total Total number of WebSocket connections closed');
    expect($output)->toContain('# HELP reverb_messages_received_total Total number of messages received');
    expect($output)->toContain('# HELP reverb_messages_sent_total Total number of messages sent');
    expect($output)->toContain('# HELP reverb_channels_active Current number of active channels');
    expect($output)->toContain('# HELP reverb_subscriptions_total Total number of channel subscriptions');
    expect($output)->toContain('# HELP reverb_server_info Reverb server information');
});

test('uses correct metric types', function () {
    $this->store->gauge('reverb_connections_total', 1);
    $this->store->increment('reverb_connections_created_total');

    $output = $this->exporter->export();

    expect($output)->toContain('# TYPE reverb_connections_total gauge');
    expect($output)->toContain('# TYPE reverb_connections_created_total counter');
});
