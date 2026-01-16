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
RUN composer dump-autoload --optimize --no-dev

#
# Production image
#
FROM ghcr.io/gophpeek/baseimages/php-fpm-nginx:${PHP_VERSION}-bookworm

LABEL org.opencontainers.image.source="https://github.com/cboxdk/websocket-server"
LABEL org.opencontainers.image.description="Cbox WebSocket Server powered by Laravel Reverb"

WORKDIR /var/www/html

COPY --from=build --chown=www-data:www-data /app .

# Create required directories
RUN mkdir -p storage/reverb storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && echo '{"apps":[]}' > storage/reverb/apps.json

# Enable Reverb via PHPeek PM
ENV LARAVEL_REVERB=true

EXPOSE 80 8080
