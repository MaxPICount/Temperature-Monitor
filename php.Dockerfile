FROM php:fpm-alpine

# adding Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# need for php composer only
RUN apk update && apk add git zip

# adding php modules
RUN docker-php-ext-install pdo_mysql

# for xdebug only
RUN apk add --update linux-headers
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS


