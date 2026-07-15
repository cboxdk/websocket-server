---
title: Cbox WebSocket Server
weight: 1
description: A production-ready, multi-tenant WebSocket server powered by Laravel Reverb — dynamic app management, Prometheus metrics, and zero-downtime config reloads.
---

# Cbox WebSocket Server

Cbox WebSocket Server is a deployable, production-ready WebSocket server powered
by [Laravel Reverb](https://reverb.laravel.com). It is built for multi-tenant
realtime applications: manage many WebSocket apps over a REST API, expose
first-class Prometheus metrics, run Docker/Kubernetes health checks, and change
app configuration without restarting the process.

It speaks the Pusher protocol, so any Pusher-compatible client (for example
`laravel-echo` + `pusher-js`) connects to it unchanged.

## Mental model

Think of the server as three cooperating surfaces on top of Reverb:

- **The Reverb WebSocket process** — the realtime engine that accepts client
  connections and broadcasts events. It listens on port `8080` by default.
- **The HTTP admin/observability plane** — a small Laravel app (served by
  PHP-FPM on port `80`) that manages apps over `/api/apps`, answers `/health`,
  and exports `/metrics`.
- **A database-backed application provider** — apps live in a database table
  (`ReverbApplication`) rather than static config, so they can be created,
  updated, and deleted at runtime. A `POST /api/reload` picks up changes with no
  restart.

Because apps are data, you provision them two ways: declaratively at boot via
the `REVERB_SEED_APPS` env var (idempotent, GitOps-friendly), or imperatively at
runtime via the admin REST API. Both write to the same table.

Metrics are computed on demand: each time `/metrics` is scraped, the server
queries Reverb's HTTP API for live connection, channel, and subscription counts
and renders them in Prometheus text format.

## Sections

- **[Getting started](getting-started/_index.md)** — install and run the server
  with Docker, Docker Compose, or locally.
- **[Core concepts](core-concepts/_index.md)** — the architecture, the
  multi-app model and hot reload, and how metrics work.
- **[API reference](api-reference/_index.md)** — the full admin REST API for
  managing apps.
- **[Cookbook](cookbook/_index.md)** — task recipes: seeding apps from env,
  client integration, and scaling.
- **[Configuration](configuration/_index.md)** — the complete environment
  variable reference.
- **[Security](security/_index.md)** — authenticating the admin API and the
  metrics endpoint.

New here? Start with the **[Quickstart](quickstart.md)** — Docker run to a
connected client in a single read.
