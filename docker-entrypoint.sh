#!/bin/sh

echo "⏳ Waiting for PostgreSQL..."

while ! nc -z $DB_HOST $DB_PORT; do
  echo "PostgreSQL is unavailable - sleeping"
  sleep 2
done

echo "✅ PostgreSQL is up!"

# Générer clé si nécessaire
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  echo "Generating application key..."
  php artisan key:generate --force
fi

# Migration
echo "Running migrations..."
php artisan migrate --force

# Cache
echo "Optimizing Laravel..."
php artisan config:clear
php artisan config:cache

echo "Starting application..."
exec "$@"