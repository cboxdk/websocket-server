<?php

declare(strict_types=1);

use App\Models\ReverbApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The command reads the env at runtime via the `env()` helper. Laravel caches
 * env reads, so tests that mutate it need to set/clear via $_ENV (which env()
 * consults) before invoking artisan. Restore in afterEach so leakage doesn't
 * bleed across tests.
 */
beforeEach(function (): void {
    unset($_ENV['REVERB_SEED_APPS']);
    putenv('REVERB_SEED_APPS');
});

test('no-op when env var is missing', function () {
    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(0);
    expect(ReverbApplication::count())->toBe(0);
});

test('no-op when env var is empty string', function () {
    $_ENV['REVERB_SEED_APPS'] = '';

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(0);
    expect(ReverbApplication::count())->toBe(0);
});

test('fails on invalid JSON', function () {
    $_ENV['REVERB_SEED_APPS'] = '{not-json}';

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(1);
});

test('fails when JSON is not an array', function () {
    $_ENV['REVERB_SEED_APPS'] = '"a string"';

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(1);
});

test('fails when entry is missing required fields', function () {
    $_ENV['REVERB_SEED_APPS'] = json_encode([
        ['id' => 'incomplete'],
    ]);

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(1);
    expect(ReverbApplication::count())->toBe(0);
});

test('creates a new app from env', function () {
    $_ENV['REVERB_SEED_APPS'] = json_encode([
        [
            'id' => 'cbox-id',
            'key' => 'cbox-id-key',
            'secret' => 'cbox-id-secret-min-32-characters-long-x',
            'name' => 'Cbox · ID',
            'allowed_origins' => ['https://id.cbox.systems'],
        ],
    ]);

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(0);

    $row = ReverbApplication::find('cbox-id');
    expect($row)->not->toBeNull();
    expect($row->key)->toBe('cbox-id-key');
    expect($row->name)->toBe('Cbox · ID');
    expect($row->allowed_origins)->toBe(['https://id.cbox.systems']);
});

test('re-running with same env is idempotent', function () {
    $_ENV['REVERB_SEED_APPS'] = json_encode([
        [
            'id' => 'cbox-id',
            'key' => 'cbox-id-key',
            'secret' => 'cbox-id-secret-min-32-characters-long-x',
            'name' => 'Cbox · ID',
        ],
    ]);

    $this->artisan('reverb:seed-from-env')->run();
    $first = ReverbApplication::find('cbox-id');
    $touchedAtFirst = $first->updated_at;

    sleep(1);

    $this->artisan('reverb:seed-from-env')->run();
    $second = ReverbApplication::find('cbox-id')->refresh();

    expect($second->updated_at->equalTo($touchedAtFirst))->toBeTrue();
});

test('updates secret when env changes', function () {
    $_ENV['REVERB_SEED_APPS'] = json_encode([
        [
            'id' => 'cbox-id',
            'key' => 'cbox-id-key',
            'secret' => 'old-secret-min-32-characters-long-zzzz',
            'name' => 'Cbox · ID',
        ],
    ]);
    $this->artisan('reverb:seed-from-env')->run();

    $_ENV['REVERB_SEED_APPS'] = json_encode([
        [
            'id' => 'cbox-id',
            'key' => 'cbox-id-key',
            'secret' => 'new-secret-min-32-characters-long-aaaa',
            'name' => 'Cbox · ID',
        ],
    ]);

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(0);
    expect(ReverbApplication::find('cbox-id')->secret)
        ->toBe('new-secret-min-32-characters-long-aaaa');
});

test('leaves apps absent from env untouched', function () {
    ReverbApplication::factory()->create([
        'id' => 'manual-app',
        'key' => 'manual-key',
        'secret' => 'manual-secret-min-32-characters-long-zz',
    ]);

    $_ENV['REVERB_SEED_APPS'] = json_encode([
        [
            'id' => 'cbox-id',
            'key' => 'cbox-id-key',
            'secret' => 'cbox-id-secret-min-32-characters-long-x',
            'name' => 'Cbox · ID',
        ],
    ]);

    $this->artisan('reverb:seed-from-env')->run();

    expect(ReverbApplication::find('manual-app'))->not->toBeNull();
    expect(ReverbApplication::find('cbox-id'))->not->toBeNull();
    expect(ReverbApplication::count())->toBe(2);
});

test('seeds multiple apps in one run', function () {
    $_ENV['REVERB_SEED_APPS'] = json_encode([
        ['id' => 'a', 'key' => 'a-key', 'secret' => str_repeat('a', 32), 'name' => 'A'],
        ['id' => 'b', 'key' => 'b-key', 'secret' => str_repeat('b', 32), 'name' => 'B'],
        ['id' => 'c', 'key' => 'c-key', 'secret' => str_repeat('c', 32), 'name' => 'C'],
    ]);

    $exit = $this->artisan('reverb:seed-from-env')->run();

    expect($exit)->toBe(0);
    expect(ReverbApplication::count())->toBe(3);
});
