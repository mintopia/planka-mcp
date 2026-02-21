#!/bin/sh
set -e

if [ ! -f /app/vendor/autoload.php ]; then
    echo "vendor/autoload.php not found — running composer install..."
    composer install --no-interaction --prefer-dist
fi

exec "$@"
