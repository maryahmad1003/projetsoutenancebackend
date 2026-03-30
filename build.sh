#!/bin/bash

echo "Building Docker image..."
docker build -t docsecur .

echo "Running migrations..."

docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=$DB_HOST \
  -e DB_PORT=3306 \
  -e DB_DATABASE=$DB_DATABASE \
  -e DB_USERNAME=$DB_USERNAME \
  -e DB_PASSWORD=$DB_PASSWORD \
  docsecur \
  php artisan migrate --force

echo "Installing Passport keys..."

docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=$DB_HOST \
  -e DB_PORT=3306 \
  -e DB_DATABASE=$DB_DATABASE \
  -e DB_USERNAME=$DB_USERNAME \
  -e DB_PASSWORD=$DB_PASSWORD \
  docsecur \
  php artisan passport:install --force

echo "Build completed successfully!"