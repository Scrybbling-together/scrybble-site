ARG RELEASE_HASH
FROM laauurraaa/composer-8.3.21 AS build-php
RUN test -n "$RELEASE_HASH" || echo "RELEASE_HASH must be set for a build"
LABEL authors="lb"

RUN docker-php-ext-install exif && docker-php-ext-enable exif
COPY . /app/
WORKDIR /app/
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

# Clear any stale bootstrap cache before testing artisan
RUN rm -f bootstrap/cache/*.php

# This checks whether artisan is usable, it's a test.
RUN php artisan about

FROM node:lts-alpine3.15 AS build-js
ARG RELEASE_HASH
COPY --from=build-php /app/ /app/
WORKDIR /app/
ENV RELEASE_HASH=$RELEASE_HASH
RUN npm ci
RUN npm install -g cross-env
RUN npm run build
RUN rm -rf node_modules

FROM php:8.3.21-apache-bookworm AS production

RUN apt-get update
RUN apt-get -yqq install libbz2-dev libzip-dev libicu-dev

RUN docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install  \
    bz2 \
    zip \
    intl \
    pcntl \
    pdo \
    pdo_mysql \
    exif \
    && \
    pecl install redis && \
    docker-php-ext-enable redis exif

COPY --chown=www-data:www-data --from=build-js /app/ /var/www/html/
RUN rm -rf /var/www/html/public/hot

COPY ./deployment/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY deployment/php/conf.d/php-overrides.ini /usr/local/etc/php/conf.d/php-overrides.ini
COPY ./deployment/000-default.conf /etc/apache2/sites-available/000-default.conf
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN a2enmod rewrite
RUN a2enmod expires
RUN a2enmod headers

# Clear any stale bootstrap cache before optimizing
RUN rm -f bootstrap/cache/*.php
RUN php artisan optimize
