# =============================================================
# Stage 1 : Build Composer (dépendances PHP)
# =============================================================
FROM php:8.4-cli-alpine AS composer-build

RUN apk add --no-cache \
        git unzip curl \
        libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# =============================================================
# Stage 2 : Image finale (PostgreSQL)
# =============================================================
FROM php:8.4-fpm-alpine

# Extensions PostgreSQL + utilitaires (plus MySQL !)
RUN apk add --no-cache \
        postgresql-client \
        freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev \
        netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd zip

# Utilisateur non-root
RUN addgroup -g 1000 laravel \
    && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

# Copier vendor du stage 1 + sources
COPY --from=composer-build /app/vendor ./vendor
COPY . .

# Supprimer tout .env éventuellement copié (les secrets viennent des env vars Render)
RUN rm -f .env

# Créer les répertoires Laravel nécessaires et permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
        storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && rm -rf bootstrap/cache/*.php

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER laravel

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
