---
title: API authentication
weight: 61
description: The API_ADMIN_TOKEN bearer auth for the admin API, and the optional token plus IP allow-list guarding /metrics.
---

# API authentication

The server has two protected HTTP surfaces. Each has its own guard.

## Admin API — `API_ADMIN_TOKEN`

Every `/api/*` endpoint is protected by a bearer token. Set `API_ADMIN_TOKEN` to
a strong random secret and send it on each request:

```
Authorization: Bearer {API_ADMIN_TOKEN}
```

The token check is a constant-time comparison against the configured value.
Behaviour:

| Condition | Status | Body |
|---|---|---|
| `API_ADMIN_TOKEN` not set | `500` | `{"error":"API authentication not configured"}` |
| No `Authorization` header | `401` | `{"error":"Authorization token required"}` |
| Token mismatch | `401` | `{"error":"Invalid authorization token"}` |
| Valid token | request proceeds | — |

Because a missing token fails closed with `500`, the admin API is never
unintentionally left open — you must set the token for it to function at all.

**Operational guidance:**

- Use a long, random token (treat it like a root credential — it can create,
  edit, and delete every app).
- Rotate it by updating the env var and redeploying.
- Never expose the admin port (`80`/`/api`) to the public internet without a
  network boundary in front of it.

## Metrics endpoint — token and IP allow-list

`/metrics` is guarded by a separate, optional layer. A request is authorized if
**either** check passes:

1. **IP allow-list** — if `METRICS_ALLOWED_IPS` is set (comma-separated), a
   request from one of those IPs is allowed.
2. **Bearer token** — if `METRICS_AUTH_TOKEN` is set, a matching bearer token is
   allowed (constant-time comparison).

Resolution order and defaults:

| `METRICS_ALLOWED_IPS` | `METRICS_AUTH_TOKEN` | Result |
|---|---|---|
| unset | unset | `/metrics` is open (no auth). |
| unset | set | Requires the bearer token. |
| set | unset | Allows listed IPs; others rejected. |
| set | set | Allows listed IPs **or** a valid token. |

An unauthorized request receives `401 Unauthorized`. If metrics are disabled
entirely (`METRICS_ENABLED=false`), `/metrics` returns `503` regardless of auth.

```bash
# With a token configured:
curl -H "Authorization: Bearer $METRICS_TOKEN" http://localhost/metrics
```

**Guidance:** in production, restrict `/metrics` to your monitoring network with
`METRICS_ALLOWED_IPS`, or require `METRICS_AUTH_TOKEN`, or both. Leaving it fully
open exposes connection and channel counts to anyone who can reach the port.

## The health endpoint

`/health` is intentionally **unauthenticated** so orchestrators and load
balancers can probe it. It reveals only liveness (`healthy`/`unhealthy`) and a
timestamp — no app data or counts. See
[Architecture](../core-concepts/architecture.md#health-model).
