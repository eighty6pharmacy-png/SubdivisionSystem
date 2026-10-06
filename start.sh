#!/bin/bash

echo "Running optimizations and migrations..."
php artisan optimize:clear
php artisan migrate --force

echo "🚀 Starting Laravel Queue Worker..."
php artisan queue:work --sleep=3 --tries=3 &

echo "🌐 Starting Web Server..."
# Check if Nixpacks generated the production Nginx config
if [ -f /assets/nginx.conf ]; then
    echo "Detected Nginx config. Starting PHP-FPM and Nginx..."
    php-fpm -y /assets/php-fpm.conf & 
    nginx -c /assets/nginx.conf
else
    echo "No Nginx config found. Falling back to Artisan Serve..."
    php artisan serve --host=0.0.0.0 --port=$PORT
fi
