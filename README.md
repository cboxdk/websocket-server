# Cbox WebSocket Server

A production-ready WebSocket server powered by [Laravel Reverb](https://reverb.laravel.com). Designed for multi-tenant applications with dynamic app management, Prometheus metrics, and zero-downtime deployments.

## Features

- **Multi-App Support** - Manage multiple WebSocket applications via REST API
- **Prometheus Metrics** - Built-in `/metrics` endpoint for monitoring
- **Health Checks** - Docker/Kubernetes-ready `/health` endpoint
- **Hot Reload** - Update app configurations without restart
- **Database Storage** - SQLite by default, supports MySQL/PostgreSQL

## Quick Start

### Docker (Recommended)

```bash
docker run -d \
  -p 80:80 \
  -p 8080:8080 \
  -e APP_KEY=base64:your-key-here \
  -e API_ADMIN_TOKEN=your-secret-token \
  -v websocket-data:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

### Docker Compose

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

### Local Development

```bash
# Clone and install
git clone https://github.com/cboxdk/websocket-server.git
cd websocket-server
composer setup

# Start development server
composer dev
```

## Configuration

### Persistence (Volume Mounts)

For data to survive container restarts, mount the following:

| Path | Description | Required |
|------|-------------|----------|
| `/var/www/html/database` | SQLite database (apps, sessions, cache) | **Yes** |

**Example with bind mount:**

```bash
docker run -d \
  -v /path/on/host/database:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

**Example with named volume:**

```bash
docker run -d \
  -v websocket-data:/var/www/html/database \
  ghcr.io/cboxdk/websocket-server:latest
```

### Environment Variables

#### Required

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel encryption key (generate with `php artisan key:generate --show`) |
| `API_ADMIN_TOKEN` | Bearer token for API authentication |

#### WebSocket Server

| Variable | Description | Default |
|----------|-------------|---------|
| `REVERB_HOST` | WebSocket server bind address | `0.0.0.0` |
| `REVERB_PORT` | WebSocket server port | `8080` |
| `REVERB_SCALING_ENABLED` | Enable Redis-based horizontal scaling | `false` |

#### Metrics

| Variable | Description | Default |
|----------|-------------|---------|
| `METRICS_ENABLED` | Enable Prometheus metrics endpoint | `true` |
| `METRICS_AUTH_TOKEN` | Bearer token for `/metrics` endpoint | *optional* |

#### Database (Optional - for MySQL/PostgreSQL)

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_CONNECTION` | Database driver (`sqlite`, `mysql`, `pgsql`) | `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `laravel` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | *empty* |

#### Redis (for cluster mode)

| Variable | Description | Default |
|----------|-------------|---------|
| `REDIS_HOST` | Redis host | `127.0.0.1` |
| `REDIS_PORT` | Redis port | `6379` |
| `REDIS_PASSWORD` | Redis password | *null* |

### Generate APP_KEY

```bash
php artisan key:generate --show
# Or using Docker:
docker run --rm ghcr.io/cboxdk/websocket-server:latest php artisan key:generate --show
```

## API Reference

All API endpoints require the `Authorization: Bearer {API_ADMIN_TOKEN}` header.

### List Apps

```bash
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/apps
```

### Create App

```bash
curl -X POST http://localhost/api/apps \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "my-app"}'
```

**Response:**

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "key": "app-key-here",
    "name": "my-app",
    "allowed_origins": ["*"],
    "max_connections": null
  }
}
```

### Update App

```bash
curl -X PUT http://localhost/api/apps/{id} \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"allowed_origins": ["https://example.com"]}'
```

### Delete App

```bash
curl -X DELETE http://localhost/api/apps/{id} \
  -H "Authorization: Bearer $TOKEN"
```

### Regenerate Secret

```bash
curl -X POST http://localhost/api/apps/{id}/regenerate-secret \
  -H "Authorization: Bearer $TOKEN"
```

### Reload Configuration

```bash
curl -X POST http://localhost/api/reload \
  -H "Authorization: Bearer $TOKEN"
```

## Monitoring

### Health Check

```bash
curl http://localhost/health
```

```json
{
  "status": "healthy",
  "timestamp": "2025-01-17T12:00:00+00:00",
  "checks": {
    "reverb": "up"
  }
}
```

### Prometheus Metrics

```bash
curl http://localhost/metrics
# Or with authentication:
curl -H "Authorization: Bearer $METRICS_TOKEN" http://localhost/metrics
```

## Client Integration

Connect using any Pusher-compatible client:

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

Echo.channel('my-channel')
    .listen('MyEvent', (e) => {
        console.log(e);
    });
```

## Development

```bash
# Run tests
composer test

# Format code
vendor/bin/pint

# Start dev server with logs
composer dev
```

## License

MIT
