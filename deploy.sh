#!/bin/bash
git pull origin main
docker build -t xante-app .
docker rm -f xante-container
docker run -d -p 80:80 --name xante-container --restart unless-stopped xante-app
docker exec -it xante-container php artisan migrate --force
docker exec -it xante-container php artisan optimize:clear
echo "¡Sitio actualizado con éxito!"