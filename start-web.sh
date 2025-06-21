#!/bin/sh
cd /var/www/html

php artisan key:generate --force || true
php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm &
nginx -g 'daemon off;'