<?php

use App\Reverb\FileApplicationProvider;

beforeEach(function () {
    $this->tempDir = storage_path('reverb/test');
    if (! is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0755, true);
    }
    $this->configPath = $this->tempDir.'/apps.json';

    // Create initial apps.json
    $apps = [
        'apps' => [
            [
                'id' => 'test-app-1',
                'key' => 'test-app-1-key-1234',
                'secret' => 'test-app-1-secret-min-32-characters-long',
                'name' => 'Test App 1',
                'allowed_origins' => ['*'],
                'max_connections' => null,
                'max_message_size' => 10000,
                'options' => [
                    'host' => 'localhost',
                    'port' => 8080,
                    'scheme' => 'http',
                    'useTLS' => false,
                    'ping_interval' => 60,
                    'activity_timeout' => 30,
                ],
            ],
        ],
    ];
    file_put_contents($this->configPath, json_encode($apps, JSON_PRETTY_PRINT));

    // Configure the provider to use our test file
    config(['reverb.apps.file.path' => $this->configPath]);
    config(['services.api.admin_token' => 'test-admin-token']);

    // Rebind the provider to use the test config
    $this->app->singleton(FileApplicationProvider::class, function ($app) {
        return new FileApplicationProvider($this->configPath, 0);
    });

    $this->validToken = 'test-admin-token';
});

afterEach(function () {
    if (file_exists($this->configPath)) {
        unlink($this->configPath);
    }
    if (is_dir($this->tempDir)) {
        @rmdir($this->tempDir);
    }
});

test('list apps returns all applications', function () {
    $response = $this->getJson('/api/apps', [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'key', 'name', 'allowed_origins', 'max_connections', 'max_message_size', 'options'],
            ],
        ])
        ->assertJsonCount(1, 'data');
});

test('list apps requires authentication', function () {
    $response = $this->getJson('/api/apps');

    $response->assertUnauthorized();
});

test('list apps rejects invalid token', function () {
    $response = $this->getJson('/api/apps', [
        'Authorization' => 'Bearer invalid-token',
    ]);

    $response->assertUnauthorized();
});

test('show app returns specific application', function () {
    $response = $this->getJson('/api/apps/test-app-1', [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', 'test-app-1')
        ->assertJsonPath('data.name', 'Test App 1');
});

test('show app returns 404 for non-existent app', function () {
    $response = $this->getJson('/api/apps/non-existent', [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertNotFound();
});

test('store app creates new application', function () {
    $response = $this->postJson('/api/apps', [
        'name' => 'New Test App',
        'allowed_origins' => ['example.com'],
    ], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'New Test App')
        ->assertJsonPath('data.allowed_origins', ['example.com'])
        ->assertJsonPath('message', 'Application created successfully');

    // Verify app was created
    expect($response->json('data.id'))->not->toBeEmpty();
    expect($response->json('data.key'))->not->toBeEmpty();
});

test('store app validates required name', function () {
    $response = $this->postJson('/api/apps', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('store app with custom id and key', function () {
    $response = $this->postJson('/api/apps', [
        'id' => 'custom-app-id',
        'key' => 'custom-app-key-1234',
        'secret' => 'custom-app-secret-min-32-characters-long-here',
        'name' => 'Custom App',
    ], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.id', 'custom-app-id')
        ->assertJsonPath('data.key', 'custom-app-key-1234');
});

test('update app modifies existing application', function () {
    $response = $this->putJson('/api/apps/test-app-1', [
        'name' => 'Updated App Name',
        'allowed_origins' => ['updated.com'],
    ], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated App Name')
        ->assertJsonPath('data.allowed_origins', ['updated.com'])
        ->assertJsonPath('message', 'Application updated successfully');
});

test('update app returns 404 for non-existent app', function () {
    $response = $this->putJson('/api/apps/non-existent', [
        'name' => 'Updated Name',
    ], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertNotFound();
});

test('delete app removes application', function () {
    $response = $this->deleteJson('/api/apps/test-app-1', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Application deleted successfully');

    // Verify app was deleted
    $this->getJson('/api/apps/test-app-1', [
        'Authorization' => 'Bearer '.$this->validToken,
    ])->assertNotFound();
});

test('delete app returns 404 for non-existent app', function () {
    $response = $this->deleteJson('/api/apps/non-existent', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertNotFound();
});

test('regenerate secret creates new secret', function () {
    // Get original app to compare
    $originalResponse = $this->getJson('/api/apps/test-app-1', [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response = $this->postJson('/api/apps/test-app-1/regenerate-secret', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', 'test-app-1')
        ->assertJsonStructure(['data' => ['id', 'secret']])
        ->assertJsonPath('message', 'Secret regenerated successfully');

    // Verify secret changed
    expect($response->json('data.secret'))->not->toBeEmpty();
});

test('regenerate secret returns 404 for non-existent app', function () {
    $response = $this->postJson('/api/apps/non-existent/regenerate-secret', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertNotFound();
});

test('reload endpoint reloads configuration', function () {
    $response = $this->postJson('/api/reload', [], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Configuration reloaded successfully');
});

test('store app rejects duplicate key', function () {
    $response = $this->postJson('/api/apps', [
        'name' => 'Duplicate Key App',
        'key' => 'test-app-1-key-1234', // Same as existing app
    ], [
        'Authorization' => 'Bearer '.$this->validToken,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['key']);
});
