#!/bin/bash

# Railway deployment script for Laravel
echo "🚀 Starting Laravel deployment..."

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate application key if needed
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Install and build frontend assets
npm ci
npm run build

echo "✅ Deployment completed successfully!"
