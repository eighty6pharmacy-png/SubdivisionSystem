#!/bin/bash

echo "🚀 Starting Laravel Queue Worker..."
# Start the queue worker in the background
php artisan queue:work --sleep=3 --tries=3 &

echo "🌐 Starting Web Server..."
# Execute the default Nixpacks start script to run NGINX and PHP-FPM
/assets/scripts/start.sh
