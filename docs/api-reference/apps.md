---
title: Apps API
weight: 31
description: Full admin REST API for managing apps — auth, every endpoint, request fields, curl examples, and JSON responses.
---

# Apps API

The admin API lives under `/api` on port `80`. It manages the WebSocket
applications the server hosts.

## Authentication

Every endpoint requires a bearer token matching `API_ADMIN_TOKEN`:

```
Authorization: Bearer {API_ADMIN_TOKEN}
```

Failure modes:

| Condition | Status | Body |
|---|---|---|
| No token configured on the server | `500` | `{"error":"API authentication not configured"}` |
| No token supplied | `401` | `{"error":"Authorization token required"}` |
| Wrong token | `401` | `{"error":"Invalid authorization token"}` |

See [API authentication](../security/api-authentication.md) for setup detail.

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/apps` | List all apps. |
| `POST` | `/api/apps` | Create an app. |
| `GET` | `/api/apps/{app}` | Show one app. |
| `PUT` | `/api/apps/{app}` | Update an app. |
| `DELETE` | `/api/apps/{app}` | Delete an app. |
| `POST` | `/api/apps/{app}/regenerate-secret` | Rotate an app's secret. |
| `POST` | `/api/reload` | Reload configuration. |

`{app}` is the app `id`. Secrets are never returned in list/show/create/update
responses — only the `regenerate-secret` endpoint returns a secret, at the moment
it is created.

## Request fields

`POST` and `PUT` accept these fields (all optional on create except `name`; on
update, send only what changes):

| Field | Rules | Notes |
|---|---|---|
| `name` | required on create, string, ≤255 | Human-readable label. |
| `id` | string, ≤255 | Create only; generated UUID if omitted. |
| `key` | string, 16–255, unique | Generated if omitted. |
| `secret` | string, 32–255 | Generated if omitted. |
| `allowed_origins` | array of string | Defaults to `["*"]`. |
| `enable_client_messages` | boolean | Defaults to `false`. |
| `max_connections` | integer ≥1, nullable | Defaults to `null` (unlimited). |
| `max_message_size` | integer 1–10000000 | Defaults to `10000`. |
| `options` | object | Merged over defaults; see below. |

`options` sub-fields are validated: `options.host` (string),
`options.port` (1–65535), `options.scheme` (`http`|`https`),
`options.useTLS` (boolean), `options.ping_interval` (1–3600),
`options.activity_timeout` (1–3600).

Validation failures return `422` with Laravel's standard error envelope. A
duplicate `key` returns `422` with `"The key has already been taken."`

## List apps

```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/apps
```

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "key": "app-key-here",
      "name": "my-app",
      "allowed_origins": ["*"],
      "enable_client_messages": false,
      "max_connections": null,
      "max_message_size": 10000,
      "options": { "host": "localhost", "port": 8080, "scheme": "http", "useTLS": false }
    }
  ]
}
```

## Create app

```bash
curl -X POST http://localhost/api/apps \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "my-app"}'
```

Returns `201`:

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "key": "app-key-here",
    "name": "my-app",
    "allowed_origins": ["*"],
    "enable_client_messages": false,
    "max_connections": null,
    "max_message_size": 10000,
    "options": { "host": "localhost", "port": 8080, "scheme": "http", "useTLS": false }
  },
  "message": "Application created successfully"
}
```

## Show app

```bash
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/api/apps/550e8400-e29b-41d4-a716-446655440000
```

Returns the app under `data`, or `404` `{"error":"Application not found"}`.

## Update app

Send only the fields you want to change. `options` is merged into the existing
options rather than replacing them.

```bash
curl -X PUT http://localhost/api/apps/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"allowed_origins": ["https://example.com"]}'
```

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "key": "app-key-here",
    "name": "my-app",
    "allowed_origins": ["https://example.com"],
    "enable_client_messages": false,
    "max_connections": null,
    "max_message_size": 10000,
    "options": { "host": "localhost", "port": 8080, "scheme": "http", "useTLS": false }
  },
  "message": "Application updated successfully"
}
```

Unknown `{app}` returns `404`.

## Delete app

```bash
curl -X DELETE http://localhost/api/apps/550e8400-e29b-41d4-a716-446655440000 \
  -H "Authorization: Bearer $TOKEN"
```

```json
{ "message": "Application deleted successfully" }
```

Unknown `{app}` returns `404`.

## Regenerate secret

Generates a new 40-character secret and returns it once. This is the only
response that includes a secret.

```bash
curl -X POST http://localhost/api/apps/550e8400-e29b-41d4-a716-446655440000/regenerate-secret \
  -H "Authorization: Bearer $TOKEN"
```

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "secret": "new-40-character-secret-value-here"
  },
  "message": "Secret regenerated successfully"
}
```

Clients using the old secret must be reconfigured with the new one.

## Reload

```bash
curl -X POST http://localhost/api/reload \
  -H "Authorization: Bearer $TOKEN"
```

```json
{ "message": "Configuration reloaded successfully" }
```

With the database provider this is a no-op success — app changes made through
this API are already live. See [Hot reload](../core-concepts/multi-app.md#hot-reload).
