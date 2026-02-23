#!/bin/sh
set -e

if [ ! -f /app/vendor/autoload.php ]; then
    echo "vendor/autoload.php not found — running composer install..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -d /app/public/build ]; then
    echo "public/build not found — running npm install and build..."
    npm install --prefix /app
    npm run build --prefix /app
fi

exec "$@"
