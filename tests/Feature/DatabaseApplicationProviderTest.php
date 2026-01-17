<?php

use App\Models\ReverbApplication;
use App\Reverb\DatabaseApplicationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Reverb\Application;
use Laravel\Reverb\Exceptions\InvalidApplication;

uses(RefreshDatabase::class);

test('loads applications from database', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'app-1-key',
        'secret' => 'app-1-secret-min-32-characters-long',
        'name' => 'Test App',
    ]);

    $provider = new DatabaseApplicationProvider;
    $all = $provider->all();

    expect($all)->toHaveCount(1);
    expect($all->first())->toBeInstanceOf(Application::class);
    expect($all->first()->id())->toBe('app-1');
    expect($all->first()->key())->toBe('app-1-key');
});

test('finds application by ID', function () {
    ReverbApplication::factory()->create(['id' => 'app-1', 'key' => 'app-1-key']);
    ReverbApplication::factory()->create(['id' => 'app-2', 'key' => 'app-2-key']);

    $provider = new DatabaseApplicationProvider;
    $app = $provider->findById('app-2');

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->id())->toBe('app-2');
});

test('finds application by key', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'unique-app-key-123',
    ]);

    $provider = new DatabaseApplicationProvider;
    $app = $provider->findByKey('unique-app-key-123');

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->id())->toBe('app-1');
});

test('throws exception for invalid application ID', function () {
    $provider = new DatabaseApplicationProvider;
    $provider->findById('non-existent');
})->throws(InvalidApplication::class);

test('throws exception for invalid application key', function () {
    $provider = new DatabaseApplicationProvider;
    $provider->findByKey('non-existent');
})->throws(InvalidApplication::class);

test('adds new application', function () {
    $provider = new DatabaseApplicationProvider;

    $newApp = [
        'id' => 'new-app',
        'key' => 'new-app-key',
        'secret' => 'new-app-secret-min-32-characters-long',
        'name' => 'New App',
        'allowed_origins' => ['*'],
        'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
    ];

    $provider->addApp($newApp);

    expect($provider->exists('new-app'))->toBeTrue();
    expect($provider->findById('new-app'))->toBeInstanceOf(Application::class);
});

test('updates existing application', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'app-1-key',
        'name' => 'Original Name',
        'allowed_origins' => ['*'],
    ]);

    $provider = new DatabaseApplicationProvider;
    $provider->updateApp('app-1', [
        'name' => 'Updated Name',
        'allowed_origins' => ['example.com'],
    ]);

    $rawApps = $provider->getRawApplications();
    $app = $rawApps->firstWhere('id', 'app-1');

    expect($app['name'])->toBe('Updated Name');
    expect($app['allowed_origins'])->toBe(['example.com']);
});

test('deletes application', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'app-1-key',
    ]);

    $provider = new DatabaseApplicationProvider;
    $provider->deleteApp('app-1');

    expect($provider->exists('app-1'))->toBeFalse();
    expect($provider->all())->toHaveCount(0);
});

test('checks if key exists', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'existing-key',
    ]);

    $provider = new DatabaseApplicationProvider;

    expect($provider->keyExists('existing-key'))->toBeTrue();
    expect($provider->keyExists('non-existing-key'))->toBeFalse();
});

test('checks if key exists with exclusion', function () {
    ReverbApplication::factory()->create([
        'id' => 'app-1',
        'key' => 'shared-key',
    ]);

    $provider = new DatabaseApplicationProvider;

    expect($provider->keyExists('shared-key', 'app-1'))->toBeFalse();
    expect($provider->keyExists('shared-key', 'different-app'))->toBeTrue();
});

test('reload is a no-op for database provider', function () {
    ReverbApplication::factory()->create(['id' => 'app-1', 'key' => 'app-1-key']);

    $provider = new DatabaseApplicationProvider;

    expect($provider->all())->toHaveCount(1);

    // Add another app directly to database
    ReverbApplication::factory()->create(['id' => 'app-2', 'key' => 'app-2-key']);

    // No need to reload - database provider always reads fresh
    expect($provider->all())->toHaveCount(2);
});
