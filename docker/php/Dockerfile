FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
        bash \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libwebp \
        libzip \
        freetype \
        shadow \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl pcntl pdo_mysql zip \
    && (php -m | grep -qi '^Zend OPcache$' || docker-php-ext-install opcache) \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run PHP as the host user so files written to the bind mount stay editable.
RUN usermod -u "${UID}" www-data && groupmod -g "${GID}" www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-dispusip.ini
COPY docker/php/entrypoint.sh /usr/local/bin/dispusip-entrypoint
RUN chmod +x /usr/local/bin/dispusip-entrypoint

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

USER www-data

ENTRYPOINT ["dispusip-entrypoint"]
CMD ["php-fpm"]
