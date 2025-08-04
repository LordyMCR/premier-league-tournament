#!/usr/bin/env bash
# exit on error
set -o errexit

composer install --no-dev --working-dir=/opt/render/project/src

echo "Generate application key..."
php artisan key:generate --force

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force
