FROM php:7.4.1-fpm-alpine3.10

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.0 \
    && docker-php-ext-enable xdebug

RUN addgroup -S sputnik && adduser -S sputnik -G sputnik -u 1000

USER sputnik
