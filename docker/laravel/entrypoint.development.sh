#!/bin/bash
set -e

composer install
wait $!
npm install
wait $!
php artisan key:generate
wait $!
php artisan migrate
wait $!
php artisan storage:link
wait $!
exec supervisord -c /etc/supervisor/conf.d/supervisord.development.conf
