---
title: Seed apps from env
weight: 41
description: Provision apps declaratively at boot with REVERB_SEED_APPS — the JSON schema, idempotent upsert-by-id semantics, and the manual command.
---

# Seed apps from env

For declarative, GitOps-friendly provisioning, pass `REVERB_SEED_APPS` as a JSON
array of app definitions. The server seeds these at boot — as a one-shot step
that runs after database migration and before the WebSocket and FPM processes
start — so an instance comes up with its apps already provisioned, with no race
against the admin API.

This is the recommended way to declare a known-ahead-of-time set of apps.
Ad-hoc apps are still best created through the [admin API](../api-reference/apps.md).

## Usage

```bash
REVERB_SEED_APPS='[
  {
    "id": "app-1",
    "key": "app-1-key",
    "secret": "app-1-secret-min-32-characters-long",
    "name": "App One",
    "allowed_origins": ["https://app-one.example.com"]
  }
]'
```

## Per-entry schema

| Field | Type | Required | Default |
|---|---|---|---|
| `id` | string | yes | — |
| `key` | string (unique) | yes | — |
| `secret` | string | yes | — |
| `name` | string | yes | — |
| `allowed_origins` | string[] | no | `["*"]` |
| `enable_client_messages` | bool | no | `false` |
| `max_connections` | int \| null | no | `null` |
| `max_message_size` | int | no | `10000` |
| `options` | object | no | `null` |

If the JSON is malformed or an entry fails the schema, the seed command fails
(exit code `1`) and prints the offending errors, rather than provisioning a
partial set.

## Idempotency

The seed is **idempotent, upserting by `id`**:

- An entry whose `id` does not exist is **created**.
- An entry whose `id` exists is **updated** only if a field actually changed.
- An entry that already matches is left untouched.

This makes rotation a config change: to rotate a secret or edit allowed origins,
change the value in `REVERB_SEED_APPS` and redeploy.

Rows in the database that are **absent** from `REVERB_SEED_APPS` are left alone —
the seed never deletes. Apps created out-of-band through the admin API are not
touched by seeding.

## Running it manually

The seed runs automatically at container start. To run it by hand (for example
after changing the env in a running shell):

```bash
php artisan reverb:seed-from-env
```

The command reports how many apps it created and updated. An empty or unset
`REVERB_SEED_APPS` is a success — it simply seeds nothing.
