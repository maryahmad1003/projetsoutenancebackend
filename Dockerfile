
# # Étape 1: Composer build
# # =========================
# FROM php:8.4-cli-alpine AS composer-build

# # Installer outils + extensions nécessaires
# RUN apk add --no-cache \
#     git unzip curl \
#     libpng-dev \
#     libjpeg-turbo-dev \
#     freetype-dev \
#     libzip-dev \
#     && docker-php-ext-configure gd \
#         --with-freetype \
#         --with-jpeg \
#     && docker-php-ext-install gd zip

# # Installer Composer
# RUN curl -sS https://getcomposer.org/installer | php \
#     && mv composer.phar /usr/local/bin/composer

# WORKDIR /app

# # Copier fichiers composer
# COPY composer.json composer.lock ./

# # Autoriser root pour composer
# ENV COMPOSER_ALLOW_SUPERUSER=1

# # Installer dépendances
# RUN composer install \
#     --no-dev \
#     --optimize-autoloader \
#     --no-interaction \
#     --prefer-dist \
#     --no-scripts


# # =========================
# # Étape 2: App finale
# # =========================
# FROM php:8.4-fpm-alpine

# # Installer extensions PHP nécessaires
# RUN apk add --no-cache \
#     postgresql-dev \
#     postgresql-client \
#     freetype-dev \
#     libjpeg-turbo-dev \
#     libpng-dev \
#     libzip-dev \
#     && docker-php-ext-configure gd \
#         --with-freetype \
#         --with-jpeg \
#     && docker-php-ext-install pdo pdo_pgsql gd zip

# # Créer user
# RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# WORKDIR /var/www/html

# # Copier vendor
# COPY --from=composer-build /app/vendor ./vendor

# # Copier projet
# COPY . .

# # Permissions
# RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
#     && mkdir -p storage/logs \
#     && mkdir -p bootstrap/cache \
#     && chown -R laravel:laravel /var/www/html \
#     && chmod -R 775 storage bootstrap/cache

# # Supprimer cache Laravel (TRÈS IMPORTANT)
# RUN rm -rf bootstrap/cache/*.php

# # .env minimal
# RUN echo "APP_NAME=Laravel" > .env && \
#     echo "APP_ENV=production" >> .env && \
#     echo "APP_KEY=" >> .env && \
#     echo "APP_DEBUG=false" >> .env && \
#     echo "APP_URL=http://localhost" >> .env && \
#     echo "" >> .env && \
#     echo "LOG_CHANNEL=stack" >> .env && \
#     echo "LOG_LEVEL=error" >> .env && \
#     echo "" >> .env && \
#     echo "DB_CONNECTION=pgsql" >> .env && \
#     echo "DB_HOST=\${DB_HOST}" >> .env && \
#     echo "DB_PORT=\${DB_PORT}" >> .env && \
#     echo "DB_DATABASE=\${DB_DATABASE}" >> .env && \
#     echo "DB_USERNAME=\${DB_USERNAME}" >> .env && \
#     echo "DB_PASSWORD=\${DB_PASSWORD}" >> .env && \
#     echo "" >> .env && \
#     echo "CACHE_DRIVER=file" >> .env && \
#     echo "SESSION_DRIVER=file" >> .env && \
#     echo "QUEUE_CONNECTION=sync" >> .env

# RUN chown laravel:laravel .env

# # Entrypoint
# COPY docker-entrypoint.sh /usr/local/bin/
# RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# USER laravel

# EXPOSE 8000

# ENTRYPOINT ["docker-entrypoint.sh"]

# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]


# Utiliser l'image PHP officielle avec Apache
# Image PHP + Apache
FROM php:8.3-apache

# Installer dépendances système (MYSQL au lieu de PG + Mongo)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer Apache rewrite
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer d'abord (optimisation cache Docker)
COPY composer.json composer.lock ./

# Installer dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Copier le reste du projet
COPY . .

# Copier script start
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Permissions Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Config Apache pour Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Port
EXPOSE 80

# Démarrage
CMD ["/usr/local/bin/start.sh"]