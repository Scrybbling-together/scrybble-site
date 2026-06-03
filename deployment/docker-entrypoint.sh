#!/usr/bin/env bash
set -e

php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize
php /var/www/html/artisan migrate --force

exec "$@"
