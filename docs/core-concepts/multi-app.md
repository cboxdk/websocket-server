---
title: Multi-app management
weight: 22
description: The ReverbApplication model, the DatabaseApplicationProvider, app fields, and hot reload via POST /api/reload.
---

# Multi-app management

A single server instance hosts many independent WebSocket applications. Each app
has its own credentials, allowed origins, and limits, and clients are isolated by
app. Apps are stored as rows, not static config, so they can be managed at
runtime.

## The ReverbApplication model

Apps are persisted through `App\Models\ReverbApplication`, backed by the
`reverb_applications` table. The primary key is a non-incrementing string `id`.

| Field | Type | Default | Meaning |
|---|---|---|---|
| `id` | string | generated UUID | Primary key / app identifier. |
| `key` | string | random (20 chars) | Public app key clients connect with. Unique across apps. |
| `secret` | string | random (40 chars) | Server-side secret for signing. |
| `name` | string | — | Human-readable label (required on create). |
| `allowed_origins` | string[] | `["*"]` | Origins permitted to connect. |
| `enable_client_messages` | bool | `false` | Whether clients may send client events. |
| `max_connections` | int \| null | `null` | Connection cap (null = unlimited). |
| `max_message_size` | int | `10000` | Max message size in bytes. |
| `options` | object | see below | Connection/runtime options. |

`allowed_origins`, `enable_client_messages`, `max_connections`,
`max_message_size`, and `options` are cast to their native types by the model.

### The `options` object

On create, `options` is merged over sensible defaults:

| Option | Default | Notes |
|---|---|---|
| `host` | Reverb hostname | Advertised host for the app. |
| `port` | Reverb port (`8080`) | Advertised port. |
| `scheme` | `http` | `http` or `https`. |
| `useTLS` | `false` | Whether clients should use TLS. |
| `ping_interval` | `60` | Seconds between pings. |
| `activity_timeout` | `30` | Idle timeout in seconds. |

Two advanced keys are honoured when present:

- `options.accept_client_events_from` — one of `all` | `members` | `none`, the
  native Reverb shape for who may send client events. When omitted, it is derived
  from `enable_client_messages` (`true` → `all`, `false` → `none`).
- `options.rate_limiting` — passed through to Reverb's rate-limiting config.

## The DatabaseApplicationProvider

`App\Reverb\DatabaseApplicationProvider` implements Reverb's
`ApplicationProvider` contract and is registered as Reverb's `database`
application driver. It resolves apps by `id` and by `key`, and translates each
row into a Reverb `Application` instance. It also backs the admin API's CRUD
operations (`addApp`, `updateApp`, `deleteApp`, `keyExists`, and so on).

Because the provider always reads fresh from the database, a newly created or
edited app is visible to **new** connections immediately, with no restart.

## Hot reload

`POST /api/reload` exists so operators have an explicit, uniform "apply config"
call. For the database provider, `reload()` is a no-op — it already reads fresh
from the table on every lookup — so the endpoint returns success without
disrupting active connections:

```bash
curl -X POST http://localhost/api/reload \
  -H "Authorization: Bearer $TOKEN"
# {"message":"Configuration reloaded successfully"}
```

You do not need to call reload after creating, updating, or deleting an app via
the API — those changes are already live. The endpoint is a stable hook for
tooling and for provider implementations that do cache.

See the [Apps API reference](../api-reference/apps.md) for the full set of
management endpoints.
