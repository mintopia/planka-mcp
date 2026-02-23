# -- node-builder stage --------------------------------------------------------
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources/css resources/css
COPY resources/js resources/js
COPY vite.config.js ./

RUN npm run build

# -- base stage ----------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.3 AS base

# Install system dependencies and required PHP extensions.
# BuildKit cache mounts keep apt lists and IPE downloads between builds,
# so only the first build pays the full download/compile cost.
# pcntl is required by Laravel Octane.
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,target=/tmp/ipe-cache,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && IPE_CACHE_DIR=/tmp/ipe-cache install-php-extensions \
    opcache \
    pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# -- deps stage ----------------------------------------------------------------
FROM base AS deps

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist

# -- app stage -----------------------------------------------------------------
FROM base AS app

ENV APP_ENV=production
ENV APP_DEBUG=0

WORKDIR /app

COPY --from=deps /app/vendor vendor/
COPY . .
COPY --from=node-builder /app/public/build public/build/

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache

COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

RUN addgroup --system --gid 1001 laravel && \
    adduser --system --uid 1001 --ingroup laravel laravel && \
    chown -R laravel:laravel /app/storage /app/bootstrap/cache /data /config

USER laravel

EXPOSE 80 443

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=80", "--workers=4", "--max-requests=500"]
