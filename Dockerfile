
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
FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libssl-dev \
    libsasl2-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Installer l'extension MongoDB (version compatible)
RUN pecl install mongodb-1.20.0 && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurer Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le reste du code
COPY . .

# Copier les fichiers de configuration
COPY composer.json composer.lock ./

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Copier les scripts et les rendre exécutables
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configurer Apache pour Laravel
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Exposer le port 80
EXPOSE 80

# Utiliser le script de démarrage
CMD ["/usr/local/bin/start.sh"]