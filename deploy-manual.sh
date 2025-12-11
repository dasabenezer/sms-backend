#!/bin/bash

# Manual deployment script for sms-backend
# Run this on your EC2 server after SSH

echo "🚀 Starting backend deployment..."

# Navigate to backend directory
cd /var/www/schoolsms/backend || exit

# Put application in maintenance mode
php artisan down || true

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Create production .env if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.production..."
    cp .env.production .env
    echo "⚠️  Please edit .env and fill in your actual credentials!"
    nano .env
fi

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

# Clear and rebuild cache
echo "🔄 Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Set proper permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Restart services
echo "♻️  Restarting services..."
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart schoolsms-worker:*

# Bring application back up
php artisan up

echo "✅ Backend deployment completed successfully!"
