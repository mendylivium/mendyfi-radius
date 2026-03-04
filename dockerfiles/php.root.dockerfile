FROM php:8.3.7-fpm-alpine3.18

RUN mkdir -p /var/www/html /var/log/php \
    && chown www-data:www-data /var/log/php

WORKDIR /var/www/html

COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

# Switch PHP-FPM pool to www-data (never root in production)
RUN sed -i "s/user = www-data/user = www-data/g" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s/group = www-data/group = www-data/g" /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[error_log] = /var/log/php/fpm-error.log" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[disable_functions] = passthru,shell_exec,system,proc_open,popen,pcntl_exec" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[open_basedir] = /var/www/html:/tmp:/var/log/php" >> /usr/local/etc/php-fpm.d/www.conf

RUN apk add --no-cache libpq postgresql-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql bcmath opcache \
    && apk del --no-cache postgresql-dev

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

RUN apk add --no-cache freeradius-utils mariadb-client curl \
    && apk add --no-cache --repository=https://dl-cdn.alpinelinux.org/alpine/v3.19/main postgresql16-client

RUN apk add --no-cache icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

# Production php.ini
RUN { \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "display_startup_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/php/error.log"; \
    echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
    echo ""; \
    echo "[opcache]"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.interned_strings_buffer = 16"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.revalidate_freq = 60"; \
    echo "opcache.validate_timestamps = 0"; \
    echo "opcache.fast_shutdown = 1"; \
} > /usr/local/etc/php/conf.d/zzz-production.ini

USER www-data

CMD ["php-fpm"]
