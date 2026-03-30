# =========================
# Étape 1: Composer build
# =========================
FROM php:8.4-cli-alpine AS composer-build

RUN apk add --no-cache \
    git unzip curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install gd zip

# Installer Composer
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


# =========================
# Étape 2: App finale
# =========================
FROM php:8.4-fpm-alpine

# ✅ MYSQL (corrigé)
RUN apk add --no-cache \
    mysql-client \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip

# Créer user
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

COPY --from=composer-build /app/vendor ./vendor
COPY . .

# Permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Nettoyer cache
RUN rm -rf bootstrap/cache/*.php

# =========================
# 🔥 TON .ENV ICI
# =========================
RUN echo "APP_NAME=Laravel" > .env && \
    echo "APP_ENV=local" >> .env && \
    echo "APP_KEY=base64:QwdqG3bEA3WiJ1LAkeYbmlvoi4Nw6Mz4WtVPMnzNsDA=" >> .env && \
    echo "APP_DEBUG=true" >> .env && \
    echo "APP_URL=http://localhost" >> .env && \
    echo "" >> .env && \
    echo "LOG_CHANNEL=stack" >> .env && \
    echo "LOG_LEVEL=debug" >> .env && \
    echo "" >> .env && \
    echo "DB_CONNECTION=mysql" >> .env && \
    echo "DB_HOST=\${DB_HOST}" >> .env && \
    echo "DB_PORT=3306" >> .env && \
    echo "DB_DATABASE=PROJET_DOCSECUR" >> .env && \
    echo "DB_USERNAME=mon_user" >> .env && \
    echo "DB_PASSWORD=mot_de_passe" >> .env && \
    echo "" >> .env && \
    echo "CACHE_DRIVER=file" >> .env && \
    echo "SESSION_DRIVER=file" >> .env && \
    echo "QUEUE_CONNECTION=sync" >> .env && \
    echo "" >> .env && \
    echo "MAIL_MAILER=smtp" >> .env && \
    echo "MAIL_HOST=mailpit" >> .env && \
    echo "MAIL_PORT=1025" >> .env && \
    echo "" >> .env && \
    echo "TWILIO_SID=ton_twilio_sid" >> .env && \
    echo "TWILIO_TOKEN=ton_twilio_token" >> .env && \
    echo "TWILIO_FROM=+221xxxxxxxx" >> .env

RUN chown laravel:laravel .env

# Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER laravel

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]