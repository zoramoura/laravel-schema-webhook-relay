#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure composer dependencies are installed (handles bind-mount where vendor/ may be absent on host).
if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

# Generate application key if missing.
if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "base64:CHANGE_ME_GENERATE_WITH_ARTISAN_KEY_GENERATE" ]; then
    php artisan key:generate --force --no-interaction || true
fi

# Run migrations & seeders (idempotent; safe to re-run on every container start).
php artisan migrate --force --no-interaction || true
php artisan db:seed --force --no-interaction || true

# Cache config & routes for production performance (skip in local debug to allow live edits).
if [ "${APP_DEBUG:-false}" != "true" ]; then
    php artisan config:cache --no-interaction || true
    php artisan route:cache --no-interaction || true
    php artisan view:cache --no-interaction || true
fi

exec "$@"