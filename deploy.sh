#!/bin/bash

set -e

echo "🚀 Iniciando despliegue de XANTE..."

echo "📥 Actualizando código desde Git..."
git pull origin main

echo "🛠️ Construyendo imagen de Docker..."
docker build -t xante-app .

echo "🔄 Reiniciando contenedor..."
docker stop xante-container || true
docker rm xante-container || true

docker run -d \
    -p 80:80 \
    --name xante-container \
    --restart unless-stopped \
    xante-app

echo "⚙️ Ejecutando optimizaciones de Laravel..."

docker exec xante-container php artisan migrate --force

docker exec xante-container php artisan optimize

echo "✅ ¡XANTE actualizado con éxito!"