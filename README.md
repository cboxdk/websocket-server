# Cbox WebSocket Server

A production-ready WebSocket server powered by [Laravel Reverb](https://reverb.laravel.com). Designed for multi-tenant applications with dynamic app management, Prometheus metrics, and zero-downtime deployments.

## Features

- **Multi-App Support** - Manage multiple WebSocket applications via REST API
- **Prometheus Metrics** - Built-in `/metrics` endpoint for monitoring
- **Health Checks** - Docker/Kubernetes-ready `/health` endpoint
- **Hot Reload** - Update app configurations without restart
- **File-Based Storage** - Simple JSON storage, no database required

## Quick Start

### Docker (Recommended)

```bash
docker run -d \
  -p 80:80 \
  -p 8080:8080 \
  -e APP_KEY=base64:your-key-here \
  -e API_ADMIN_TOKEN=your-secret-token \
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
      - ./apps.json:/var/www/html/storage/reverb/apps.json
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

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_KEY` | Laravel application key | *required* |
| `API_ADMIN_TOKEN` | Token for API authentication | *required* |
| `REVERB_HOST` | WebSocket server bind address | `0.0.0.0` |
| `REVERB_PORT` | WebSocket server port | `8080` |
| `METRICS_ENABLED` | Enable Prometheus metrics | `true` |
| `METRICS_AUTH_TOKEN` | Token for metrics endpoint | *optional* |

Generate an `APP_KEY`:

```bash
php artisan key:generate --show
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
