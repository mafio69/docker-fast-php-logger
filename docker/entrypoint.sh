#!/bin/sh
set -e
chown -R www-data:www-data /var/www/html/logs /var/www/html/app
exec apache2-foreground
