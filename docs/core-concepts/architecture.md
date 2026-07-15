---
title: Architecture
weight: 21
description: The Reverb WebSocket process alongside PHP-FPM, the database-backed app provider, the on-demand metrics model, and the health check.
---

# Architecture

Cbox WebSocket Server is a single deployable that runs two long-lived processes
and a small set of HTTP surfaces on top of Laravel Reverb.

## Two processes, one image

- **Reverb WebSocket server** — the realtime engine. It accepts client
  connections and broadcasts events over the Pusher protocol, listening on
  `REVERB_PORT` (`8080` by default) bound to `REVERB_HOST` (`0.0.0.0` by
  default).
- **PHP-FPM HTTP app** — a standard Laravel application serving the admin API
  (`/api/*`), the health check (`/health`), and the metrics endpoint
  (`/metrics`) on port `80`.

The Docker image runs both, plus a one-shot seed step at startup (see
[Seed apps from env](../cookbook/seed-apps-from-env.md)). The seed runs after
database migration and before the WebSocket and FPM processes come up, so the
instance boots with its apps already provisioned.

## Database-backed application provider

Reverb normally reads its applications from static config. This server replaces
that with a **database provider**: `config('reverb.apps.provider')` defaults to
`database`, and `App\Reverb\DatabaseApplicationProvider` registers itself as
Reverb's `database` application driver.

The provider reads apps from the `reverb_applications` table (the
`App\Models\ReverbApplication` model). Because Reverb resolves apps through this
provider on each lookup, changes to the table take effect for new connections
without restarting the process. See [Multi-app management](multi-app.md).

The default database is SQLite, stored under `/var/www/html/database`. MySQL and
PostgreSQL are supported by setting the `DB_*` environment variables.

## Metrics model

Metrics are **computed on demand**, not accumulated in the background. Each time
`/metrics` is scraped, the `MetricsController`:

1. Clears any transient in-memory state.
2. Records server-info gauges (framework version, PHP version, scaling mode) and
   the configured-app count.
3. Iterates every configured app, opens a Pusher client against Reverb's HTTP
   API, and reads live connection, channel, and subscription counts.
4. Renders everything as Prometheus text via `PrometheusExporter`.

Because the values are read live at scrape time, the metric store is
process-local and holds only the current snapshot. The `METRICS_DRIVER` setting
(`auto` / `memory` / `redis`) selects the storage backend for that snapshot; see
[Metrics](metrics.md). If an app can't be reached, it is skipped; if no app can
be reached, `reverb_up` is reported as `0`.

## Health model

`GET /health` is a lightweight liveness/readiness probe suitable for Docker and
Kubernetes. It opens a TCP socket to the Reverb host and port (with a 2-second
timeout) and reports:

- `200` with `{"status":"healthy","checks":{"reverb":"up"}}` when Reverb is
  accepting connections.
- `503` with `{"status":"unhealthy","checks":{"reverb":"down"}}` when it is not.

The response also carries an ISO-8601 `timestamp`. The check is unauthenticated
so orchestrators can probe it freely.

## Request surfaces at a glance

| Surface | Port | Auth | Purpose |
|---|---|---|---|
| WebSocket (Reverb) | `8080` | Pusher app key/secret | Client connections and broadcasts. |
| `/api/*` | `80` | `API_ADMIN_TOKEN` bearer | App management + reload. |
| `/metrics` | `80` | Optional token / IP allow-list | Prometheus scrape. |
| `/health` | `80` | None | Liveness/readiness probe. |
