---
title: Requirements
weight: 3
description: The runtime versions the server's composer.json enforces — PHP, Laravel, Reverb, and its direct dependencies.
---

# Requirements

These are the constraints declared in the server's `composer.json`. Nothing here
is invented — it is exactly what the dependency resolver enforces.

## Runtime

| Requirement | Constraint | Notes |
|---|---|---|
| PHP | `^8.4` | 8.4 or newer; the Docker image runs 8.5. |
| `laravel/framework` | `^13.43` | The application framework. |
| `laravel/reverb` | `^1.14` | The WebSocket engine this server is built on. |
| `cboxdk/laravel-telemetry` | `^3.9` | First-party observability — traces, metrics and events (auto-discovered). |
| `laravel/tinker` | `^3.0` | REPL for maintenance and debugging. |
| `predis/predis` | `^3.3` | Redis client, used for cluster-mode scaling. |

## Observability

The server ships [`cboxdk/laravel-telemetry`](https://cbox.dk/packages/laravel-telemetry),
auto-discovered on install. It adds collector-free traces, metrics and events on
top of the built-in Prometheus `/metrics` endpoint. Its maintenance commands
(`telemetry:doctor`, `telemetry:flush`, `telemetry:monitor`) are available via
`artisan`; point it at an exporter through its own configuration when you want to
ship signals to a backend.

Its push metrics need a store shared by the PHP processes. The package defaults
to Redis; the Docker image sets `TELEMETRY_STORE=sqlite` (a file under
`storage/framework`), so a standalone server needs no Redis for telemetry either.
Set `TELEMETRY_STORE=redis` when you run with Redis anyway. The package's own
scrape endpoint, `/telemetry/metrics`, answers 403 outside `local` until you set
`TELEMETRY_ALLOWED_IPS` or `TELEMETRY_PROMETHEUS_TOKEN`; the server's
`/metrics` endpoint is unaffected.

## Redis

`predis/predis` is always installed, but Redis is only *needed* at runtime when
you enable horizontal scaling (`REVERB_SCALING_ENABLED=true`). In the default
single-instance (standalone) mode the server runs without a Redis server. See
[Scaling](cookbook/scaling.md).

## Docker image

The published image (`ghcr.io/cboxdk/websocket-server`, `linux/amd64` and
`linux/arm64`) is built on
[cboxdk/php-baseimages](https://github.com/cboxdk/php-baseimages) v2: Debian 13
(trixie), PHP 8.5, Cbox Init as PID 1. It is **rootless**: nginx, PHP-FPM and
Reverb all run as `www-data` (uid 33), and nginx still listens on port 80
(Docker and Kubernetes let an unprivileged process bind it). See
[Installation](getting-started/installation.md).

## Database

Apps and other state persist to a database. SQLite is the default and requires no
external service; MySQL and PostgreSQL are supported by setting the `DB_*`
environment variables. See [Environment](configuration/environment.md).

## Development tooling

For local development the server also pulls in dev dependencies including Pest 5
(`pestphp/pest`, `pestphp/pest-plugin-laravel`), Laravel Pint, Pail, Sail,
Boost, Mockery, Collision, and Faker. These are not required to run the shipped
Docker image.
