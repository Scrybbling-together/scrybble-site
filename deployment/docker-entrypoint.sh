#!/usr/bin/env bash
set -e

php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize
php /var/www/html/artisan migrate --force

if [ "${SCRYBBLE_AUTO_MIGRATE:-true}" = "true" ] && [[ "$*" == *apache2-foreground* ]]; then
    php /var/www/html/artisan migrate --force
fi

exec "$@"
