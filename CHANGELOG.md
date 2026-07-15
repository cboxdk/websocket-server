# Changelog

All notable changes to Cbox WebSocket Server are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-07-15

### Added

- **Declarative app seeding from the environment.** `REVERB_SEED_APPS` (a JSON
  array) provisions Reverb applications at boot — idempotent upsert by `id`, run
  as a oneshot after `migrate` and before `php-fpm`/`reverb`, so an instance comes
  up with its apps already provisioned. Also invokable via
  `php artisan reverb:seed-from-env`.
- **`/docs` documentation tree** in the cboxdk topic-folder layout — getting
  started, core concepts (architecture, multi-app, metrics), API reference,
  cookbook, configuration, and security.

### Changed

- **Package identity.** `composer.json` is now `cboxdk/websocket-server` (was the
  untouched `laravel/laravel` skeleton), with a real description, keywords,
  homepage, authors and support links.
- Adapted the database application provider to the Laravel Reverb 1.x
  `Application` signature; refreshed the dependency lock.

### Fixed

- Moved the app-options JSON column default from the migration to the model, so a
  freshly created app row carries the correct default.

## [1.0.0] - 2026-07-10

Initial production release: a WebSocket server powered by Laravel Reverb, with
database-backed multi-app management over a token-authenticated REST API,
Prometheus metrics and a `/health` endpoint, and a Docker-first deployment.
