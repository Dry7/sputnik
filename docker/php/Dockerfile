FROM php:7.3.3-fpm-alpine3.9

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.7.0 \
    && docker-php-ext-enable xdebug

RUN addgroup -S sputnik && adduser -S sputnik -G sputnik -u 1000

USER sputnik
