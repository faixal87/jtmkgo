#!/bin/bash
set -e

APP_DIR="/var/www/jtmkgo"

cd "$APP_DIR"

BRANCH=$(git branch --show-current)

echo "Deploying branch: $BRANCH"

sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

git fetch origin
git reset --hard origin/$BRANCH

composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan optimize:clear

npm ci || npm install
npm run build

sudo touch storage/logs/laravel.log
sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

echo ""
echo "================================"
echo "DEPLOY SUCCESS"
git log --oneline -1
echo "================================"
