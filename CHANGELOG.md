# Changelog

All notable changes to Cbox WebSocket Server are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-11-18

### Security

- **Dependencies updated, `composer audit` clean.** 31 advisories in the shipped
  dependencies are closed: guzzlehttp/guzzle (5), laravel/framework (4),
  league/commonmark (12), league/flysystem, symfony/http-foundation (5),
  symfony/http-kernel, symfony/mime and symfony/routing. `laravel/framework`
  13.20 → 13.43 and `laravel/reverb` 1.10 → 1.14 (the constraints now require at
  least these). CI runs `composer audit --no-dev` on every push.
- **Rootless image.** nginx, PHP-FPM and Reverb run as `www-data` (uid 33); the
  container never runs as root. It starts with `--cap-drop ALL` and
  `no-new-privileges`, and meets the Kubernetes `restricted` Pod Security level.
  The ports are unchanged: nginx still listens on 80 (`NGINX_HTTP_PORT=80`; an
  unprivileged process may bind it in Docker and Kubernetes) and Reverb on 8080.
  The start-up step that ran `chown` as root is gone: the image's files are owned
  by `www-data` at build time. Earlier images already handed the database
  directory to `www-data` at start, so existing volumes keep working; a host
  directory created by hand must be writable by uid 33.

### Changed

- **Base image: cboxdk/php-baseimages v2** — Debian 13 (trixie), PHP 8.5 (was
  8.4 on bookworm), Cbox Init 3.10 as PID 1, both stages pinned by digest.
  `composer.json` still accepts PHP 8.4; CI tests 8.4 and 8.5.
- **Image published for `linux/amd64` and `linux/arm64`** (was amd64 only), and
  only after a smoke test of the built image: rootless start, `/health`, and a
  WebSocket upgrade against a seeded app.
- **`cboxdk/laravel-telemetry` 1.0 → 3.9.** The image sets
  `TELEMETRY_STORE=sqlite`: the package defaults to Redis, so a standalone server
  logged `RedisException: Connection refused` on every request. Telemetry's own
  `/telemetry/metrics` answers 403 outside `local` until
  `TELEMETRY_ALLOWED_IPS` or `TELEMETRY_PROMETHEUS_TOKEN` is set.
- Development: Pest 4 → 5 (PHPUnit 13), Laravel Pint 1.32, Boost 2.10.

### Fixed

- Reverb's database driver is resolved through the manager's container, not
  `$this->app`.
- Code style (Pint) across nine files; CI's lint job is green again.

### Added

- `LICENSE` (MIT, as `composer.json` already declared).

## [1.2.0] - 2026-07-15

### Added

- **First-party observability via `cboxdk/laravel-telemetry` (`^1.0`).** Collector-free
  traces, metrics and events, auto-discovered on install, on top of the existing
  Prometheus `/metrics` endpoint. Ships the `telemetry:doctor` / `telemetry:flush`
  / `telemetry:monitor` maintenance commands; point it at an exporter through its
  own config to ship signals to a backend.

### Changed

- **Upgraded to Laravel 13 and raised the platform floor.** `laravel/framework`
  `^12` → `^13`, **PHP `^8.2` → `^8.4`**, `laravel/reverb` `^1.7` → `^1.10`,
  `laravel/tinker` `^2.10` → `^3.0` (and Symfony 7 → 8 transitively). Build or
  deploy the server on **PHP 8.4+**; the published Docker image bundles a
  compatible runtime. Test suite green (61 tests) on the new stack.

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
