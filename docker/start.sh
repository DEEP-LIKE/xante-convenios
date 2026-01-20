#!/bin/bash

# 1. Crear directorios de logs si no existen
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache

# 2. Asegurar permisos correctos (solo a nivel de carpeta para ser más rápido y evitar errores)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Validar que .env existe
if [ ! -f /var/www/html/.env ]; then
    echo "❌ ERROR: .env file not found!"
    exit 1
fi

# 4. Validar conexión a base de datos
php /var/www/html/artisan db:show --quiet || {
    echo "⚠️  WARNING: Cannot connect to database. Continuing anyway..."
}

# 5. Optimización de Laravel (solo si no está cacheado)
if [ ! -f /var/www/html/bootstrap/cache/config.php ]; then
    php /var/www/html/artisan optimize
fi

# 6. Iniciar Supervisor en primer plano (Bandera -n)
# Esto es CRÍTICO: El parámetro -n evita que el proceso se vaya al fondo
# y permite que Docker mantenga el contenedor encendido.
echo "🚀 Iniciando Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf