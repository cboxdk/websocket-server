---
title: Installation
weight: 11
description: Run the server via Docker, Docker Compose, or a local dev setup, and generate the required APP_KEY.
---

# Installation

The recommended way to run Cbox WebSocket Server in production is the published
Docker image. For contributing or local experimentation, run it as a standard
Laravel project.

## Required configuration

Two environment variables are required in every deployment:

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel's encryption key. |
| `API_ADMIN_TOKEN` | Bearer token guarding the `/api/*` admin endpoints. |

See [Environment](../configuration/environment.md) for the full reference.

## Generate an APP_KEY

```bash
php artisan key:generate --show
```

Or, using the Docker image without a running container:

```bash
docker run --rm ghcr.io/cboxdk/websocket-server:latest php artisan key:generate --show
```

Copy the `base64:...` value into `APP_KEY`.

## Docker

The image exposes two ports — `80` for the HTTP API and `8080` for WebSocket
traffic — and stores its SQLite database under `/var/www/html/database`. Mount a
volume there so apps and other state survive restarts.

```bash
docker run -d \
  -p 80:80 \
  -p 8080:8080 \
  -e APP_KEY=base64:your-key-here \
  -e API_ADMIN_TOKEN=your-secret-token \
  -v websocket-data:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

### Persistence

For data to survive container restarts, mount the database directory:

| Path | Contents | Required |
|---|---|---|
| `/var/www/html/database` | SQLite database (apps, sessions, cache) | Yes |

A bind mount works the same way:

```bash
docker run -d \
  -v /path/on/host/database:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

## Docker Compose

```yaml
services:
  websocket-server:
    image: ghcr.io/cboxdk/websocket-server:latest
    ports:
      - "80:80"      # HTTP API
      - "8080:8080"  # WebSocket
    environment:
      APP_KEY: "${APP_KEY}"
      API_ADMIN_TOKEN: "${API_ADMIN_TOKEN}"
    volumes:
      - websocket-data:/var/www/html/database

volumes:
  websocket-data:
```

## Local development

Clone the repository and run the setup script, which installs dependencies,
copies `.env`, generates the key, migrates the database, and builds front-end
assets:

```bash
git clone https://github.com/cboxdk/websocket-server.git
cd websocket-server
composer setup
```

Start the development stack (HTTP server, queue listener, log tailing, and the
asset watcher, all concurrently):

```bash
composer dev
```

Run the test suite:

```bash
composer test
```

Format code with Pint:

```bash
vendor/bin/pint
```

## Verify the install

Regardless of how you started the server, confirm it is up:

```bash
curl http://localhost/health
# {"status":"healthy","timestamp":"...","checks":{"reverb":"up"}}
```

A `200` with `"reverb":"up"` means the Reverb WebSocket process is listening.
A `503` with `"reverb":"down"` means it is not reachable yet. See
[Architecture](../core-concepts/architecture.md) for the health model.
