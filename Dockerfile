# syntax=docker/dockerfile:1
#
# Cbox WebSocket Server: Laravel Reverb (the Pusher protocol) on cboxdk/php-baseimages v2
# (Debian 13 trixie, PHP 8.5), rootless: every process runs as www-data (uid 33).
#
#   nginx   :80    the HTTP API, /health and /metrics (NGINX_HTTP_PORT=80; an unprivileged
#                  process may bind it in Docker and Kubernetes, whose pods start with
#                  net.ipv4.ip_unprivileged_port_start=0)
#   Reverb  :8080  WebSocket connections
#
# Both base images are pinned by digest; bump them together (docs/getting-started/docker.md).

#
# Build stage: Composer dependencies
#
FROM ghcr.io/cboxdk/php-baseimages/php-cli:8.5-trixie-v2@sha256:237aeea35765ba52ce8058ed8a3e2399e07811c4325206873b87239920488f14 AS build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

# Storage directories Laravel needs (storage/ is excluded from the build context)
RUN mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

RUN composer dump-autoload --optimize --no-dev

#
# Production image
#
FROM ghcr.io/cboxdk/php-baseimages/php-fpm-nginx:8.5-trixie-rootless-v2@sha256:e0f9e71685ac9a7949172b288dba3ad0497394d7ac66e2170f57320717c7d2b9

LABEL org.opencontainers.image.source="https://github.com/cboxdk/websocket-server"
LABEL org.opencontainers.image.description="Cbox WebSocket Server powered by Laravel Reverb"
LABEL org.opencontainers.image.licenses="MIT"

WORKDIR /var/www/html

# The application, owned by the user every process runs as
COPY --from=build --chown=www-data:www-data /app .

# Fresh, writable storage (the build stage's is discarded)
USER root
RUN rm -rf storage bootstrap/cache \
 && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/reverb bootstrap/cache database \
 && chown -R www-data:www-data storage bootstrap/cache database
USER www-data

# cbox-init: the base image's rootless configuration plus migrate and seed-apps
COPY --chown=www-data:www-data docker/cbox-init.yaml /etc/cbox-init/cbox-init.yaml

ENV LARAVEL_REVERB=true \
    NGINX_HTTP_PORT=80 \
    TELEMETRY_STORE=sqlite

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost/health || exit 1
