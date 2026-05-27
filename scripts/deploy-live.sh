#!/usr/bin/env bash
set -euo pipefail

COMPOSE="docker compose -f docker-compose.live.yml"

echo "==> Pulling latest code..."
git fetch origin main
git checkout main
git pull origin main

echo "==> Building containers..."
$COMPOSE build web scheduler queue

echo "==> Starting containers..."
$COMPOSE up -d

echo "==> Caching config..."
$COMPOSE exec -T web php artisan config:cache
$COMPOSE exec -T web php artisan route:cache
$COMPOSE exec -T web php artisan view:cache

echo "==> Done. Site should be live."
