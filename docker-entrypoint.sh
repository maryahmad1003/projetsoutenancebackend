#!/bin/sh

echo "Waiting for database..."

while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME"; do
  sleep 1
done

echo "Database ready"

# Nettoyer cache Laravel
php artisan config:clear
php artisan cache:clear

# Générer clé si absente
php artisan key:generate --force

# Migration
php artisan migrate --force

echo "Starting app..."

exec "$@"