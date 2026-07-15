---
title: Metrics
weight: 23
description: The Prometheus exporter, every exported metric name and its labels, and the auto / memory / redis driver setting.
---

# Metrics

The server exposes a Prometheus-compatible `/metrics` endpoint. Metrics are
built on demand at scrape time by querying Reverb's HTTP API for live counts, so
each scrape reflects the current state of that instance.

Metrics collection can be turned off entirely with `METRICS_ENABLED=false`, in
which case `/metrics` returns `503 Metrics disabled`. Access to the endpoint is
guarded by [metrics auth](../security/api-authentication.md).

## Scraping

```bash
curl http://localhost/metrics
# or, when a token is configured:
curl -H "Authorization: Bearer $METRICS_TOKEN" http://localhost/metrics
```

The response is served as `text/plain; version=0.0.4` with cache disabled, the
standard Prometheus exposition format.

## Exported metrics

All metrics are gauges. Per-app series carry an `app_id` label; per-channel-type
series also carry a `type` label (`public`, `private`, `presence`, or
`encrypted`).

| Metric | Labels | Meaning |
|---|---|---|
| `reverb_up` | — | `1` if any app's Reverb API was reachable this scrape, else `0`. |
| `reverb_server_info` | `version`, `php_version`, `scaling_mode` | Always `1`; carries build info as labels. `scaling_mode` is `cluster` or `standalone`. |
| `reverb_apps_configured` | — | Number of configured applications. |
| `reverb_connections_current` | — | Total current connections across all apps. |
| `reverb_connections_total` | `app_id` | Current connections for one app. |
| `reverb_channels_current` | — | Total active channels across all apps. |
| `reverb_channels_active` | `app_id`, `type` | Active channels of a type, for one app. |
| `reverb_subscriptions_current` | — | Total current subscriptions across all apps. |
| `reverb_subscriptions_total` | `app_id`, `type` | Current subscriptions of a type, for one app. |

The `*_current` totals are only emitted when `reverb_up` is `1`. Each metric is
prefixed by its `# HELP` and `# TYPE` annotations in the output.

### Example output

```text
# HELP reverb_up Whether Reverb server is reachable (1 = up, 0 = down)
# TYPE reverb_up gauge
reverb_up 1
# HELP reverb_apps_configured Number of configured WebSocket applications
# TYPE reverb_apps_configured gauge
reverb_apps_configured 2
# HELP reverb_connections_total Current number of active WebSocket connections per app
# TYPE reverb_connections_total gauge
reverb_connections_total{app_id="my-app"} 12
# HELP reverb_channels_active Current number of active channels by type
# TYPE reverb_channels_active gauge
reverb_channels_active{app_id="my-app",type="presence"} 3
```

## The metrics driver

`config/metrics.php` selects where the per-scrape snapshot is stored via
`METRICS_DRIVER`:

| Value | Behaviour |
|---|---|
| `auto` (default) | Uses Redis when `REVERB_SCALING_ENABLED=true`, otherwise in-memory (process-local). |
| `memory` | In-memory, process-local. |
| `redis` | Redis-backed, sharing the connection/prefix from `config/metrics.php`. |

The Redis connection and key prefix are configurable with
`METRICS_REDIS_CONNECTION` (default `default`) and `METRICS_REDIS_PREFIX`
(default `reverb:metrics:`).

Because metric values are read live from Reverb's API on each scrape, the store
holds only the current snapshot rather than a running history. In a multi-instance
deployment, point Prometheus at every instance and aggregate there — see
[Scaling](../cookbook/scaling.md).

## Instance identity

`config/metrics.php` also reads `METRICS_INSTANCE_ID` (defaulting to the machine
hostname) as the server's instance identity. Prometheus itself already
distinguishes scrape targets by their `instance` label, which is the primary way
to tell instances apart in a cluster.
