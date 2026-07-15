---
title: Core concepts
weight: 20
description: How the server is put together — architecture, the multi-app model with hot reload, and the metrics pipeline.
---

# Core concepts

This section explains how Cbox WebSocket Server works underneath the API.

- **[Architecture](architecture.md)** — the Reverb WebSocket process alongside
  PHP-FPM, the database-backed application provider, the metrics model, and the
  health check.
- **[Multi-app management](multi-app.md)** — the `ReverbApplication` model, the
  `DatabaseApplicationProvider`, and hot reload via `POST /api/reload`.
- **[Metrics](metrics.md)** — the Prometheus exporter, every exported metric
  name, and the `auto` / `memory` / `redis` driver setting.
