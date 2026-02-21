FROM dunglas/frankenphp:1-php8.3 AS base

# Install system dependencies and required PHP extensions.
# BuildKit cache mounts keep apt lists and IPE downloads between builds,
# so only the first build pays the full download/compile cost.
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,target=/tmp/ipe-cache,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    && IPE_CACHE_DIR=/tmp/ipe-cache install-php-extensions \
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
