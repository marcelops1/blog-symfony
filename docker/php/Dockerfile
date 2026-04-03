FROM php:8.5-fpm

# PDO, OPcache já vêm embutidos no PHP 8.5
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN pecl install pcov && docker-php-ext-enable pcov

RUN echo "opcache.validate_timestamps=1" \
    > /usr/local/etc/php/conf.d/opcache.ini

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
