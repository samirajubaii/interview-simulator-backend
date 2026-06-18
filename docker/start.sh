#!/bin/bash
set -e

cd /var/www

# Generate app key if not set
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
