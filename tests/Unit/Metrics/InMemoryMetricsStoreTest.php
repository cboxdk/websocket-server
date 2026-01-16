<?php

use App\Metrics\InMemoryMetricsStore;

beforeEach(function () {
    $this->store = new InMemoryMetricsStore;
});

test('increments counter without labels', function () {
    $this->store->increment('test_counter');
    $this->store->increment('test_counter');
    $this->store->increment('test_counter', [], 5);

    $counters = $this->store->getCounters();

    expect($counters)->toHaveKey('test_counter');
    expect($counters['test_counter']['value'])->toBe(7);
    expect($counters['test_counter']['labels'])->toBe([]);
});

test('increments counter with labels', function () {
    $this->store->increment('test_counter', ['app_id' => 'app-1']);
    $this->store->increment('test_counter', ['app_id' => 'app-2']);
    $this->store->increment('test_counter', ['app_id' => 'app-1'], 3);

    $counters = $this->store->getCounters();

    expect($counters)->toHaveCount(2);

    $app1Counter = collect($counters)->first(fn ($c) => ($c['labels']['app_id'] ?? null) === 'app-1');
    $app2Counter = collect($counters)->first(fn ($c) => ($c['labels']['app_id'] ?? null) === 'app-2');

    expect($app1Counter['value'])->toBe(4);
    expect($app2Counter['value'])->toBe(1);
});

test('sets gauge value', function () {
    $this->store->gauge('test_gauge', 42.5);

    $gauges = $this->store->getGauges();

    expect($gauges)->toHaveKey('test_gauge');
    expect($gauges['test_gauge']['value'])->toBe(42.5);
});

test('sets gauge with labels', function () {
    $this->store->gauge('test_gauge', 10, ['type' => 'public']);
    $this->store->gauge('test_gauge', 20, ['type' => 'private']);

    $gauges = $this->store->getGauges();

    expect($gauges)->toHaveCount(2);

    $publicGauge = collect($gauges)->first(fn ($g) => ($g['labels']['type'] ?? null) === 'public');
    $privateGauge = collect($gauges)->first(fn ($g) => ($g['labels']['type'] ?? null) === 'private');

    expect($publicGauge['value'])->toBe(10.0);
    expect($privateGauge['value'])->toBe(20.0);
});

test('increments gauge', function () {
    $this->store->incrementGauge('test_gauge');
    $this->store->incrementGauge('test_gauge', [], 5);

    $gauges = $this->store->getGauges();

    expect($gauges['test_gauge']['value'])->toBe(6.0);
});

test('decrements gauge', function () {
    $this->store->gauge('test_gauge', 10);
    $this->store->decrementGauge('test_gauge');
    $this->store->decrementGauge('test_gauge', [], 3);

    $gauges = $this->store->getGauges();

    expect($gauges['test_gauge']['value'])->toBe(6.0);
});

test('decrement gauge does not go below zero', function () {
    $this->store->gauge('test_gauge', 5);
    $this->store->decrementGauge('test_gauge', [], 10);

    $gauges = $this->store->getGauges();

    expect($gauges['test_gauge']['value'])->toBe(0.0);
});

test('clears all metrics', function () {
    $this->store->increment('test_counter');
    $this->store->gauge('test_gauge', 10);

    expect($this->store->getCounters())->not->toBeEmpty();
    expect($this->store->getGauges())->not->toBeEmpty();

    $this->store->clear();

    expect($this->store->getCounters())->toBeEmpty();
    expect($this->store->getGauges())->toBeEmpty();
});

test('stores metric name in data', function () {
    $this->store->increment('my_counter', ['label' => 'value']);
    $this->store->gauge('my_gauge', 42, ['label' => 'value']);

    $counters = $this->store->getCounters();
    $gauges = $this->store->getGauges();

    $counter = collect($counters)->first();
    $gauge = collect($gauges)->first();

    expect($counter['name'])->toBe('my_counter');
    expect($gauge['name'])->toBe('my_gauge');
});

test('handles multiple labels', function () {
    $this->store->increment('test_counter', [
        'app_id' => 'app-1',
        'channel_type' => 'public',
        'status' => 'success',
    ]);

    $counters = $this->store->getCounters();
    $counter = collect($counters)->first();

    expect($counter['labels'])->toBe([
        'app_id' => 'app-1',
        'channel_type' => 'public',
        'status' => 'success',
    ]);
});

test('distinguishes metrics with different label combinations', function () {
    $this->store->increment('test_counter', ['a' => '1', 'b' => '2']);
    $this->store->increment('test_counter', ['a' => '2', 'b' => '1']);

    $counters = $this->store->getCounters();

    expect($counters)->toHaveCount(2);
});
