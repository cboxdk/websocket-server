# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.4

#
# Stage 1: Install Composer dependencies
#
FROM ghcr.io/gophpeek/baseimages/php-cli:${PHP_VERSION}-bookworm AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev

#
# Stage 2: Production image
#
FROM ghcr.io/gophpeek/baseimages/php-fpm-nginx:${PHP_VERSION}-bookworm

LABEL org.opencontainers.image.source="https://github.com/cboxdk/websocket-server"
LABEL org.opencontainers.image.description="Cbox WebSocket Server powered by Laravel Reverb"

# Install supervisor for running multiple processes
RUN apt-get update && apt-get install -y --no-install-recommends \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application from composer stage
COPY --from=composer /app .

# Copy Docker configuration files
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create required directories
RUN mkdir -p storage/reverb \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && chown -R www-data:www-data storage

# Create default apps.json if not exists
RUN echo '{"apps":[]}' > storage/reverb/apps.json \
    && chown www-data:www-data storage/reverb/apps.json

# Expose ports: 80 for HTTP API, 8080 for WebSocket
EXPOSE 80 8080

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/api/metrics || exit 1

# Start supervisor (manages nginx, php-fpm, and reverb)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
