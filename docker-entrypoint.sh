#!/bin/sh

echo "Waiting for MySQL to be ready..."

# Attendre MySQL
while ! nc -z $DB_HOST $DB_PORT; do
  echo "MySQL is unavailable - sleeping"
  sleep 2
done

echo "MySQL is up!"

# Générer clé si vide
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  echo "Generating application key..."
  php artisan key:generate --force
fi

# Migrations
echo "Running migrations..."
php artisan migrate --force

# Cache Laravel (safe)
echo "Optimizing Laravel..."
php artisan config:clear
php artisan config:cache

echo "Starting application..."
exec "$@"