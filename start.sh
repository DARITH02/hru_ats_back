#!/usr/bin/env bash

# Use the PORT environment variable provided by Render, or default to 8080
export PORT=${PORT:-8080}

# Replace the ${PORT} variable in the Nginx configuration
envsubst '${PORT}' < /etc/nginx/conf.d/default.conf > /etc/nginx/conf.d/default.conf.tmp
mv /etc/nginx/conf.d/default.conf.tmp /etc/nginx/conf.d/default.conf

# Cache configuration, routes, and views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (force for production to avoid prompts)
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
