#!/bin/bash

# Générer Swagger
php artisan l5-swagger:generate

# Créer le fichier .env si nécessaire
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=\${DB_HOST:-127.0.0.1}
DB_PORT=\${DB_PORT:-3306}
DB_DATABASE=\${DB_DATABASE:-PROJET_DOCSECUR}
DB_USERNAME=\${DB_USERNAME:-mon_user}
DB_PASSWORD=\${DB_PASSWORD:-mot_de_passe}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
EOF
fi

# Debug DB
echo "Database configuration:"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"

# Générer APP_KEY si nécessaire
if [ -z "$APP_KEY" ] || [[ "$APP_KEY" != base64:* ]]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Attendre MySQL
echo "Waiting for MySQL..."
until php -r "
try {
    new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo 'MySQL connected!';
} catch (Exception \$e) {
    exit(1);
}
"; do
    echo "MySQL not ready... waiting"
    sleep 2
done

echo "MySQL is ready!"

# Migration
echo "Running migrations..."
php artisan migrate --force

# OAuth (si utilisé)
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "Generating OAuth keys..."
    php artisan passport:keys --force
fi

# Optimisation
echo "Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:cache

# Démarrage
echo "Starting Laravel..."
exec "$@"