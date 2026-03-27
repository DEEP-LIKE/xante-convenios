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
echo "🔑 Ajustando permisos..."
# Si existe laravel.log y no es de www-data o no es escribible, lo borramos para que Laravel lo cree fresco
if [ -f /var/www/html/storage/logs/laravel.log ]; then
    rm -f /var/www/html/storage/logs/laravel.log
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
# Forzamos 777 solo en logs y framework para evitar CUALQUIER bloqueo
chmod -R 777 /var/www/html/storage/logs /var/www/html/storage/framework

# 3. Validar archivo de entorno (.env)
if [ ! -f /var/www/html/.env ]; then
    echo "❌ ERROR: Archivo .env no encontrado en /var/www/html/"
    exit 1
fi

# 4. Verificar conexión a DB (Informativo)
if su -s /bin/bash www-data -c "php /var/www/html/artisan db:show --quiet" > /dev/null 2>&1; then
    echo "✅ Conexión a la base de datos establecida."
else
    echo "⚠️  ADVERTENCIA: No se pudo conectar a la base de datos."
fi

# 5. Forzar la generación de caché de configuración al arrancar
# Esto es vital para que Laravel reconozca el .env que montamos en el deploy
echo "📦 Generando caché de configuración fresca..."
su -s /bin/bash www-data -c "php /var/www/html/artisan config:clear"
su -s /bin/bash www-data -c "php /var/www/html/artisan optimize"


# 6. Ejecutar Supervisor en primer plano
# NOTA: Supervisor debe manejar Apache y los Workers de Laravel
echo "🚀 Iniciando Supervisor..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf