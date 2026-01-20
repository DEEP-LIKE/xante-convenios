#!/bin/bash

# Detener si hay errores durante la preparación
set -e

echo "🔧 Preparando entorno interno..."

# 1. Crear directorios necesarios
mkdir -p /var/log/supervisor \
         /var/www/html/storage/logs \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache

# 2. Permisos (Asegura que Laravel pueda escribir)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Validar archivo de entorno (.env)
if [ ! -f /var/www/html/.env ]; then
    echo "❌ ERROR: Archivo .env no encontrado en /var/www/html/"
    exit 1
fi

# 4. Verificar conexión a DB (Informativo)
if php /var/www/html/artisan db:show --quiet > /dev/null 2>&1; then
    echo "✅ Conexión a la base de datos establecida."
else
    echo "⚠️  ADVERTENCIA: No se pudo conectar a la base de datos."
fi

# 5. Cachear configuración si no existe (Acelera el primer arranque)
if [ ! -f /var/www/html/bootstrap/cache/config.php ]; then
    echo "📦 Generando caché de configuración..."
    php /var/www/html/artisan optimize
fi

# 6. Ejecutar Supervisor en primer plano
# NOTA: Supervisor debe manejar Apache y los Workers de Laravel
echo "🚀 Iniciando Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf