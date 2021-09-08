FROM php:8-fpm

RUN apt-get update && apt-get install -y curl wget git zip unzip libpq-dev libicu-dev libxslt-dev libpng-dev openssl libsodium-dev\
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-install xsl \
    && docker-php-ext-install gd \
    && pecl install -f libsodium \
    && echo "extension = sodium.so" >> /$PHP_INI_DIR/conf.d/sodium.ini \
    && pecl install redis \
    && docker-php-ext-enable redis

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer


ENV COMPOSER_ALLOW_SUPERUSER 1
