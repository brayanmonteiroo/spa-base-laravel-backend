#!/bin/sh
set -e

if [ -f artisan ] && [ -d vendor ]; then
    echo "Clearing configurations..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

exec "$@"
