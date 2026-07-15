---
title: API reference
weight: 30
description: The admin REST API for managing WebSocket applications at runtime.
---

# API reference

The admin REST API manages WebSocket applications at runtime — list, create,
inspect, update, delete, rotate secrets, and reload. Every endpoint is guarded
by the `API_ADMIN_TOKEN` bearer token.

- **[Apps API](apps.md)** — the complete endpoint reference with `curl`
  examples and JSON responses.

For how the token is configured and verified, see
[API authentication](../security/api-authentication.md).
