#!/bin/bash
set -e

php artisan migrate --force --isolated
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve --host=0.0.0.0 --port=$PORT
