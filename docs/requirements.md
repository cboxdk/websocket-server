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
| PHP | `^8.2` | 8.2 or newer in the 8.x line. |
| `laravel/framework` | `^12.0` | The application framework. |
| `laravel/reverb` | `^1.7` | The WebSocket engine this server is built on. |
| `laravel/tinker` | `^2.10.1` | REPL for maintenance and debugging. |
| `predis/predis` | `^3.3` | Redis client, used for cluster-mode scaling. |

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
