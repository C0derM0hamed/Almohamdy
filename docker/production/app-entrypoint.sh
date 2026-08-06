#!/bin/sh
set -eu

mkdir -p \
    storage/app \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan view:cache
composer check-platform-reqs --no-dev

exec "$@"

