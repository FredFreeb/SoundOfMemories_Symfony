FROM node:22-alpine AS app_assets

WORKDIR /app

COPY package*.json vite.config.js ./
COPY assets ./assets
COPY public ./public

RUN npm ci \
    && npm run build

FROM php:8.4-fpm-alpine AS app_php

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_SECRET=build-time-secret

RUN apk add --no-cache \
        bash \
        freetype-dev \
        git \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        gd \
        intl \
        opcache \
        pdo_mysql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=app_assets /app/public/build /var/www/html/public/build

RUN composer install --prefer-dist --no-dev --no-interaction --no-progress --optimize-autoloader \
    && php bin/console cache:clear --env=prod --no-debug \
    && chown -R www-data:www-data var public/uploads

FROM nginx:1.27-alpine AS app_nginx

WORKDIR /var/www/html

COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app_php /var/www/html/public /var/www/html/public

FROM app_php AS app_runtime

EXPOSE 9000
