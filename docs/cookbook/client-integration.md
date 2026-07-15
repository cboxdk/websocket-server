---
title: Client integration
weight: 42
description: Connect a Pusher-compatible client with laravel-echo and pusher-js, and subscribe to public, private, and presence channels.
---

# Client integration

The server speaks the Pusher protocol, so any Pusher-compatible client connects
to it unchanged. The most common setup is `laravel-echo` with `pusher-js`.

You need the app's **`key`** (from the [Apps API](../api-reference/apps.md) or
your seed config), the WebSocket host, and the port — `8080` by default.

## Connect with Laravel Echo

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-app-key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    disableStats: true,
    cluster: 'mt1',
});
```

Notes:

- `key` is the app's public key — never the secret.
- `wsPort` is the Reverb port (`8080` unless you changed `REVERB_PORT`).
- Set `forceTLS: true` and point `wsHost` at your TLS-terminating hostname when
  serving WebSockets over `wss://` in production.
- `cluster` is required by `pusher-js` but is not otherwise meaningful for a
  self-hosted server; any value works.

## Subscribe to channels

Public channel:

```javascript
Echo.channel('my-channel')
    .listen('MyEvent', (e) => {
        console.log(e);
    });
```

Private and presence channels work as in any Pusher/Echo setup — they require
your Laravel application to expose the standard broadcasting auth endpoint so the
client can be authorized for `private-` and `presence-` channels.

## Client events

If you want clients to broadcast events directly to each other (client events),
enable it per app with `enable_client_messages: true` (or the finer-grained
`options.accept_client_events_from`). See
[Multi-app management](../core-concepts/multi-app.md#the-options-object). Client
events are disabled by default.

## Origins

By default an app allows all origins (`allowed_origins: ["*"]`). In production,
restrict this to the domains that should be able to connect — set
`allowed_origins` when creating the app or via an update.
