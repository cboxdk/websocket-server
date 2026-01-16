<?php

use App\Reverb\FileApplicationProvider;
use Laravel\Reverb\Application;
use Laravel\Reverb\Exceptions\InvalidApplication;

beforeEach(function () {
    $this->tempDir = storage_path('reverb/test');
    if (! is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0755, true);
    }
    $this->configPath = $this->tempDir.'/apps.json';
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

test('loads applications from JSON file', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'app-1-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'name' => 'Test App',
                'allowed_origins' => ['*'],
                'options' => [
                    'ping_interval' => 60,
                    'activity_timeout' => 30,
                ],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $all = $provider->all();

    expect($all)->toHaveCount(1);
    expect($all->first())->toBeInstanceOf(Application::class);
    expect($all->first()->id())->toBe('app-1');
    expect($all->first()->key())->toBe('app-1-key');
});

test('finds application by ID', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'app-1-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
            [
                'id' => 'app-2',
                'key' => 'app-2-key',
                'secret' => 'app-2-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $app = $provider->findById('app-2');

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->id())->toBe('app-2');
});

test('finds application by key', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'unique-app-key-123',
                'secret' => 'app-1-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $app = $provider->findByKey('unique-app-key-123');

    expect($app)->toBeInstanceOf(Application::class);
    expect($app->id())->toBe('app-1');
});

test('throws exception for invalid application ID', function () {
    $apps = ['apps' => []];
    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $provider->findById('non-existent');
})->throws(InvalidApplication::class);

test('throws exception for invalid application key', function () {
    $apps = ['apps' => []];
    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $provider->findByKey('non-existent');
})->throws(InvalidApplication::class);

test('adds new application', function () {
    $apps = ['apps' => []];
    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);

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
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'app-1-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'name' => 'Original Name',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $provider->updateApp('app-1', [
        'key' => 'app-1-key',
        'secret' => 'app-1-secret-min-32-characters-long',
        'name' => 'Updated Name',
        'allowed_origins' => ['example.com'],
        'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
    ]);

    $rawApps = $provider->getRawApplications();
    $app = $rawApps->firstWhere('id', 'app-1');

    expect($app['name'])->toBe('Updated Name');
    expect($app['allowed_origins'])->toBe(['example.com']);
});

test('deletes application', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'app-1-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);
    $provider->deleteApp('app-1');

    expect($provider->exists('app-1'))->toBeFalse();
    expect($provider->all())->toHaveCount(0);
});

test('checks if key exists', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'existing-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);

    expect($provider->keyExists('existing-key'))->toBeTrue();
    expect($provider->keyExists('non-existing-key'))->toBeFalse();
});

test('checks if key exists with exclusion', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'shared-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath);

    expect($provider->keyExists('shared-key', 'app-1'))->toBeFalse();
    expect($provider->keyExists('shared-key', 'different-app'))->toBeTrue();
});

test('reloads configuration on file change', function () {
    $apps = [
        'apps' => [
            [
                'id' => 'app-1',
                'key' => 'app-1-key',
                'secret' => 'app-1-secret-min-32-characters-long',
                'name' => 'Original',
                'allowed_origins' => ['*'],
                'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
            ],
        ],
    ];

    file_put_contents($this->configPath, json_encode($apps));

    $provider = new FileApplicationProvider($this->configPath, cacheTtl: 0);

    expect($provider->all())->toHaveCount(1);

    // Update file
    sleep(1); // Ensure different timestamp
    $apps['apps'][] = [
        'id' => 'app-2',
        'key' => 'app-2-key',
        'secret' => 'app-2-secret-min-32-characters-long',
        'allowed_origins' => ['*'],
        'options' => ['ping_interval' => 60, 'activity_timeout' => 30],
    ];
    file_put_contents($this->configPath, json_encode($apps));

    // Force reload
    $provider->reload();

    expect($provider->all())->toHaveCount(2);
});
