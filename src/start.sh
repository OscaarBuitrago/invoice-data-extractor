#!/bin/bash
set -e

php artisan migrate --force --isolated
php artisan db:seed --class=SuperAdminSeeder --force
php artisan db:seed --class=MaturanaPLCSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve --host=0.0.0.0 --port=$PORT
