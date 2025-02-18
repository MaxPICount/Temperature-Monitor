FROM php

# adding Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# need for php composer only
RUN apt-get update && apt-get install -y git zip

# adding php modules
RUN docker-php-ext-install pdo_mysql

RUN pecl channel-update pecl.php.net
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

