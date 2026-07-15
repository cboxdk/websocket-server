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
| PHP | `^8.4` | 8.4 or newer. |
| `laravel/framework` | `^13.0` | The application framework. |
| `laravel/reverb` | `^1.10` | The WebSocket engine this server is built on. |
| `cboxdk/laravel-telemetry` | `^1.0` | First-party observability — traces, metrics and events (auto-discovered). |
| `laravel/tinker` | `^3.0` | REPL for maintenance and debugging. |
| `predis/predis` | `^3.3` | Redis client, used for cluster-mode scaling. |

## Observability

The server ships [`cboxdk/laravel-telemetry`](https://cbox.dk/packages/laravel-telemetry),
auto-discovered on install. It adds collector-free traces, metrics and events on
top of the built-in Prometheus `/metrics` endpoint. Its maintenance commands
(`telemetry:doctor`, `telemetry:flush`, `telemetry:monitor`) are available via
`artisan`; point it at an exporter through its own configuration when you want to
ship signals to a backend.

## Redis

`predis/predis` is always installed, but Redis is only *needed* at runtime when
you enable horizontal scaling (`REVERB_SCALING_ENABLED=true`). In the default
single-instance (standalone) mode the server runs without a Redis server. See
[Scaling](cookbook/scaling.md).

## Database

Apps and other state persist to a database. SQLite is the default and requires no
external service; MySQL and PostgreSQL are supported by setting the `DB_*`
environment variables. See [Environment](configuration/environment.md).

## Development tooling

For local development the server also pulls in dev dependencies including Pest 4
(`pestphp/pest`, `pestphp/pest-plugin-laravel`), Laravel Pint, Pail, Sail,
Boost, Mockery, Collision, and Faker. These are not required to run the shipped
Docker image.
