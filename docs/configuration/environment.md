---
title: Environment
weight: 51
description: Full environment variable reference — required secrets, WebSocket server, metrics, database, and Redis.
---

# Environment

Every setting is an environment variable. This page is the complete reference,
grouped by concern. Defaults are what the server uses when the variable is unset.

## Required

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel encryption key. Generate with `php artisan key:generate --show`. |
| `API_ADMIN_TOKEN` | Bearer token for the `/api/*` admin endpoints. Without it, the API returns `500`. |

## WebSocket server

| Variable | Default | Description |
|---|---|---|
| `REVERB_HOST` | `0.0.0.0` | Bind address for the Reverb WebSocket server. |
| `REVERB_PORT` | `8080` | Port the WebSocket server listens on. |
| `REVERB_SCALING_ENABLED` | `false` | Enable Redis-based horizontal scaling across instances. |

See [Scaling](../cookbook/scaling.md) for cluster mode.

## Metrics

| Variable | Default | Description |
|---|---|---|
| `METRICS_ENABLED` | `true` | Enable the `/metrics` endpoint. When `false`, `/metrics` returns `503`. |
| `METRICS_DRIVER` | `auto` | Snapshot store: `auto`, `memory`, or `redis`. `auto` uses Redis when scaling is enabled. |
| `METRICS_AUTH_TOKEN` | *unset* | Optional bearer token to require on `/metrics`. |
| `METRICS_ALLOWED_IPS` | *unset* | Optional comma-separated IP allow-list for `/metrics`. |
| `METRICS_REDIS_CONNECTION` | `default` | Redis connection used when the driver is `redis`. |
| `METRICS_REDIS_PREFIX` | `reverb:metrics:` | Key prefix for Redis-backed metrics. |
| `METRICS_INSTANCE_ID` | machine hostname | Instance identity for server info. |

See [Metrics](../core-concepts/metrics.md) and
[API authentication](../security/api-authentication.md).

## Database

Apps and other state persist to a database. SQLite is the default and needs no
external service; the database file lives under `/var/www/html/database` (mount
a volume there — see [Installation](../getting-started/installation.md)).

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `sqlite` | Database driver: `sqlite`, `mysql`, or `pgsql`. |
| `DB_HOST` | `127.0.0.1` | Database host (MySQL/PostgreSQL). |
| `DB_PORT` | `3306` | Database port. |
| `DB_DATABASE` | `laravel` | Database name. |
| `DB_USERNAME` | `root` | Database username. |
| `DB_PASSWORD` | *empty* | Database password. |

Use MySQL or PostgreSQL when running multiple instances against a shared store.

## Redis

Redis is used for cluster-mode scaling (and, with `METRICS_DRIVER=redis`, for
the metrics snapshot). It is only needed at runtime when scaling is enabled.

| Variable | Default | Description |
|---|---|---|
| `REDIS_HOST` | `127.0.0.1` | Redis host. |
| `REDIS_PORT` | `6379` | Redis port. |
| `REDIS_PASSWORD` | *null* | Redis password. |
