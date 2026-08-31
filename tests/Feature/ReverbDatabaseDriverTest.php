<?php

declare(strict_types=1);

use App\Reverb\DatabaseApplicationProvider;
use Laravel\Reverb\ApplicationManager;

/**
 * The database driver must resolve off the ApplicationManager without touching a
 * property that only exists on the service provider.
 *
 * Reverb 1.10.2 invokes a custom driver creator bound to the ApplicationManager, so
 * a creator closure that reached for $this->app read an undefined property on the
 * manager and the whole server refused to start ("Undefined property
 * Laravel\Reverb\ApplicationManager::$app"). reverb:start builds the pusher router,
 * which resolves this exact driver — so this is the boot path, exercised without
 * standing a socket up.
 */
it('resolves the database driver off the application manager', function (): void {
    $provider = app(ApplicationManager::class)->driver('database');

    expect($provider)->toBeInstanceOf(DatabaseApplicationProvider::class);
});
