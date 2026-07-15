---
title: Scaling
weight: 43
description: Run multiple instances with REVERB_SCALING_ENABLED and Redis, and scrape metrics across the cluster.
---

# Scaling

A single instance handles many apps and connections, but for high availability
and horizontal throughput you run multiple instances behind a load balancer.
Reverb coordinates broadcasts across instances through Redis.

## Enable cluster mode

Set `REVERB_SCALING_ENABLED=true` and point the instances at a shared Redis:

```bash
REVERB_SCALING_ENABLED=true
REDIS_HOST=your-redis-host
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password   # if required
```

With scaling enabled, Reverb publishes and subscribes over Redis so an event
broadcast on one instance reaches clients connected to any instance. In the
default standalone mode (`false`) no Redis server is needed.

The `predis/predis` client ships with the server, so no extra install is
required — you only need a reachable Redis instance.

## Shared app state

Apps live in the database, not in per-instance memory, so every instance sees the
same set of apps automatically. Point all instances at the same database
(SQLite is single-node; use MySQL or PostgreSQL for a shared, multi-instance
store — see [Environment](../configuration/environment.md)).

Because the [seed step](seed-apps-from-env.md) is idempotent and upserts by `id`,
every instance can safely run the same `REVERB_SEED_APPS` at boot.

## Metrics in a cluster

When scaling is enabled, `reverb_server_info` reports `scaling_mode="cluster"`,
and the metrics driver's `auto` default uses Redis (see [Metrics](../core-concepts/metrics.md)):

```bash
METRICS_DRIVER=auto            # Redis when scaling is on, memory otherwise
METRICS_REDIS_CONNECTION=default
METRICS_REDIS_PREFIX=reverb:metrics:
```

Each instance computes its metrics live at scrape time and reports its own view.
Configure Prometheus to scrape **every** instance (each is a distinct target
with its own `instance` label) and aggregate across them with PromQL — for
example, `sum(reverb_connections_current)` for the cluster-wide connection total.

## Health checks behind a load balancer

Point your load balancer and orchestrator liveness/readiness probes at
`/health` on each instance. It returns `200` when that instance's Reverb process
is accepting connections and `503` otherwise, so unhealthy instances are taken
out of rotation. The probe is unauthenticated.
