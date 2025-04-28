ARG RELEASE_HASH
FROM laauurraaa/composer-8.1.7 AS build-php
RUN test -n "$RELEASE_HASH" || echo "RELEASE_HASH must be set for a build"
LABEL authors="lb"

COPY . /app/
WORKDIR /app/
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

FROM node:lts-alpine3.15 AS build-js
ARG RELEASE_HASH
COPY --from=build-php /app/ /app/
COPY ../.env.azure-prod /app/.env
WORKDIR /app/
ENV RELEASE_HASH=$RELEASE_HASH
RUN npm ci
RUN npm install -g cross-env
RUN npm run build
RUN rm -rf node_modules

FROM laauurraaa/smg-app-base-image:1.1 AS production

COPY --chown=www-data:www-data --from=build-js /app/ /var/www/html/
RUN rm -rf /var/www/html/public/hot

COPY ./deployment/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY deployment/php/conf.d/php-overrides.ini /usr/local/etc/php/conf.d/php-overrides.ini
COPY ./deployment/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ../.env.azure-prod /var/www/html/.env

RUN a2enmod rewrite

RUN php artisan optimize
