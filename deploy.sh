#!/bin/bash

# Detener el script si ocurre algún error
set -e

echo "🚀 Iniciando despliegue de XANTE..."

# 1. Actualizar código
echo "📥 Actualizando código desde Git..."
git pull origin main

# 2. Construir imagen
echo "🛠️ Construyendo imagen de Docker..."
docker build -t xante-app .

# 3. Limpieza de contenedores previos
echo "🔄 Reiniciando contenedor..."
docker stop xante-container || true
docker rm xante-container || true

# 4. Lanzar nuevo contenedor con el .env vinculado
# Usamos $(pwd)/.env para asegurar la ruta absoluta del archivo en el host
docker run -d \
    -p 80:80 \
    --name xante-container \
    --restart unless-stopped \
    -v "$(pwd)/.env:/var/www/html/.env" \
    xante-app

# 5. Espera de cortesía para que el contenedor y servicios internos arranquen
echo "⏳ Esperando a que el contenedor inicie (5s)..."
sleep 5

# 6. Tareas de Laravel
echo "⚙️ Ejecutando tareas de Laravel..."

# Verificamos si el contenedor sigue corriendo antes de ejecutar comandos internos
if [ "$(docker inspect -f '{{.State.Running}}' xante-container)" = "true" ]; then
    docker exec --user www-data xante-container php artisan migrate --force
    docker exec --user www-data xante-container php artisan optimize
    echo "✅ ¡XANTE actualizado con éxito!"
else
    echo "❌ ERROR: El contenedor no inició correctamente. Revisa 'docker logs xante-container'"
    exit 1
fi