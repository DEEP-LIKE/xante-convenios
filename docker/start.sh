#!/bin/bash

# Crear directorios de logs si no existen
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs

# Asegurar permisos correctos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar caché de Laravel (opcional, útil para deployments)
php /var/www/html/artisan config:clear
php /var/www/html/artisan cache:clear

# Iniciar Supervisor (que a su vez inicia Apache y el Worker)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf