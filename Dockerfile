# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

#
# Build stage: Install Composer dependencies
#
FROM ghcr.io/gophpeek/baseimages/php-cli:${PHP_VERSION}-bookworm AS build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

# Create storage directories needed for Laravel (excluded from build context via .dockerignore)
RUN mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

RUN composer dump-autoload --optimize --no-dev

#
# Production image
#
FROM ghcr.io/gophpeek/baseimages/php-fpm-nginx:${PHP_VERSION}-bookworm

LABEL org.opencontainers.image.source="https://github.com/cboxdk/websocket-server"
LABEL org.opencontainers.image.description="Cbox WebSocket Server powered by Laravel Reverb"

WORKDIR /var/www/html

# Copy application (excluding storage from build)
COPY --from=build --chown=www-data:www-data /app .

# Remove storage from build stage and create fresh directories
RUN rm -rf storage bootstrap/cache && \
    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database

# Copy custom PHPeek PM config with fix-permissions process
COPY docker/phpeek-pm.yaml /etc/phpeek-pm/phpeek-pm.yaml

# Enable Reverb via PHPeek PM
ENV LARAVEL_REVERB=true

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost/health || exit 1
