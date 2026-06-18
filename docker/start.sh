#!/bin/bash
set -e

cd /var/www

# Generate .env from environment variables
cp .env.example .env

# Override with actual environment values
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Cache config for performance
php artisan config:cache
php artisan route:cache

# Create supervisor log directory
mkdir -p /var/log/supervisor

# Start all services via supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf