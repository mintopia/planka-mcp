FROM dunglas/frankenphp:1-php8.3 AS base

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
    intl \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── deps stage ─────────────────────────────────────────────────────────────────
FROM base AS deps

COPY composer.json composer.lock* ./

RUN composer install \
    --no-dev \
    --no-autoloader \
    --no-scripts \
    --no-interaction \
    --prefer-dist

# ── app stage ───────────────────────────────────────────────────────────────────
FROM base AS app

ENV APP_ENV=prod
ENV APP_DEBUG=0

WORKDIR /app

COPY --from=deps /app/vendor vendor/

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

RUN APP_ENV=prod php bin/console cache:warmup --no-debug

COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile

RUN addgroup --system --gid 1001 symfony && \
    adduser --system --uid 1001 --ingroup symfony symfony && \
    chown -R symfony:symfony /app var/

USER symfony

EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
