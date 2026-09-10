FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
