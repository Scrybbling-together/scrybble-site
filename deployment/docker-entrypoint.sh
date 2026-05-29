#!/usr/bin/env bash
set -e

# The image bakes a Laravel config/route/view cache at build time (see App.Dockerfile,
# "php artisan optimize"). Those cached files capture the build-time placeholder
# environment and, once present, SHADOW the operator's real runtime environment
# (DB_HOST, DB_USERNAME, REDIS_HOST, REMARKS_HOST, ...). Self-hosters would otherwise
# see the app connect to the wrong database host/credentials and Redis.
#
# Rebuild the cache from the *live* environment on every container start so the
# environment is always honored. config:cache / route:cache / view:cache / event:cache
# only read config files and env — they do NOT connect to the database or Redis — so
# this is safe to run before those services are reachable.
php /var/www/html/artisan optimize:clear
php /var/www/html/artisan optimize

exec "$@"
