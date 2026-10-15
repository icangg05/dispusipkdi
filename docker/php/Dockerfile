# Stages:
#   dev         local development: the code is bind-mounted (docker-compose.yml), dependencies install on start
#   production  the app with code, vendor/ and built assets baked in; only storage/ and .env come from the host
#   web         nginx serving the built public/ directory (compose.prod.yml)

FROM php:8.5-fpm-alpine AS base

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

# Run PHP as the host user so files written to bind mounts stay editable.
RUN usermod -u "${UID}" www-data && groupmod -g "${GID}" www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-dispusip.ini
COPY docker/php/entrypoint.sh /usr/local/bin/dispusip-entrypoint
RUN chmod +x /usr/local/bin/dispusip-entrypoint

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

ENTRYPOINT ["dispusip-entrypoint"]
CMD ["php-fpm"]


FROM base AS dev

USER www-data


# Production PHP dependencies, with the optimized autoloader and the package manifest.
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction


# Vite build. Tailwind reads the Blade views and the vendor pagination views, so both are present.
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-fund
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build


FROM base AS production

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=vendor /var/www/html/bootstrap/cache ./bootstrap/cache
COPY --from=assets /app/public/build ./public/build

# Uploads live in storage/ on the host; the link is made here because public/ is not writable at runtime.
RUN ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data


FROM nginx:1.28-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build
RUN ln -sfn ../storage/app/public /var/www/html/public/storage
