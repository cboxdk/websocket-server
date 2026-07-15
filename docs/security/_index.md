---
title: Security
weight: 60
description: Authenticating the admin API and protecting the metrics endpoint.
---

# Security

The server exposes two privileged HTTP surfaces — the admin API and the metrics
endpoint — plus the unauthenticated `/health` probe. This section covers how to
protect the first two.

- **[API authentication](api-authentication.md)** — the `API_ADMIN_TOKEN`
  bearer token for `/api/*`, and the optional token + IP allow-list for
  `/metrics`.

Beyond endpoint auth, the usual practices apply: restrict `allowed_origins` per
app, keep app secrets out of client code, and terminate TLS in front of the
server for `wss://` traffic.
