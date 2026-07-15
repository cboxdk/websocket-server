---
title: Quickstart
weight: 2
description: From docker run to a connected client in one read — start the server, create your first app, and subscribe to a channel.
---

# Quickstart

This gets you from nothing to a live WebSocket connection in three steps:
start the server, create an app, connect a client.

## 1. Run the server

The server needs two secrets: an `APP_KEY` (Laravel's encryption key) and an
`API_ADMIN_TOKEN` (the bearer token that guards the admin API).

Generate an `APP_KEY`:

```bash
docker run --rm ghcr.io/cboxdk/websocket-server:latest php artisan key:generate --show
```

Start the container. Port `80` serves the HTTP API and `8080` serves
WebSocket traffic. The volume keeps the SQLite database (and your apps) across
restarts.

```bash
docker run -d \
  -p 80:80 \
  -p 8080:8080 \
  -e APP_KEY=base64:your-key-here \
  -e API_ADMIN_TOKEN=your-secret-token \
  -v websocket-data:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

Confirm it is healthy:

```bash
curl http://localhost/health
# {"status":"healthy","timestamp":"...","checks":{"reverb":"up"}}
```

## 2. Create your first app

Every request to the admin API carries `Authorization: Bearer $API_ADMIN_TOKEN`.
The only required field is `name`; a `key` and `secret` are generated for you if
you omit them.

```bash
export TOKEN=your-secret-token

curl -X POST http://localhost/api/apps \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "my-app"}'
```

The response returns the app, including the `key` you connect clients with:

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

The app is live immediately — the database-backed provider reads fresh on each
connection, so there is no restart or reload needed after creating an app.

## 3. Connect a client

The server speaks the Pusher protocol, so `laravel-echo` + `pusher-js` connect
with no adapter. Use the `key` from the previous step.

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'app-key-here',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
    cluster: 'mt1',
});

Echo.channel('my-channel')
    .listen('MyEvent', (e) => {
        console.log(e);
    });
```

That is the full loop. From here:

- Provision apps declaratively instead — see
  [Seed apps from env](cookbook/seed-apps-from-env.md).
- Wire up Prometheus scraping — see [Metrics](core-concepts/metrics.md).
- Lock down the admin and metrics endpoints — see
  [API authentication](security/api-authentication.md).
