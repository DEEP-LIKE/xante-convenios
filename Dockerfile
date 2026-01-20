# ----------------------------------------------------------------------
# STAGE 1: COMPOSER DEPENDENCIES (PHP)
# ----------------------------------------------------------------------
FROM php:8.3-cli-alpine AS vendor-builder

WORKDIR /app

# Instalar dependencias necesarias para Composer
RUN apk add --no-cache git unzip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos de dependencias de PHP
COPY composer.json composer.lock ./

# Instalar dependencias de PHP (sin scripts para evitar errores de autoloader)
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

# ----------------------------------------------------------------------
# STAGE 2: ASSET BUILD (NODE.JS)
# ----------------------------------------------------------------------
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copiar archivos de dependencias de Node
COPY package.json package-lock.json ./

# Instalar dependencias de Node
RUN npm ci

# RECOPILAR TODO PARA EL BUILD:
# 1. Copiamos el código fuente
COPY . .
# 2. COPIAMOS EL VENDOR (Crucial para Tailwind/Flux CSS)
COPY --from=vendor-builder /app/vendor ./vendor

# Compilar assets de frontend (Vite)
RUN npm run build

# ----------------------------------------------------------------------
# STAGE 3: PRODUCTION IMAGE (PHP + APACHE)
# ----------------------------------------------------------------------
FROM php:8.3-apache

WORKDIR /var/www/html

# Activar módulos necesarios de Apache
RUN a2enmod rewrite headers

# INSTALACIÓN DE HERRAMIENTAS Y LIBRERÍAS
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libcurl4-openssl-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libfreetype6-dev \
    supervisor \
    cron \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# INSTALACIÓN DE EXTENSIONES DE PHP
RUN docker-php-ext-configure gd --with-freetype \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        gd \
        mbstring \
        bcmath \
        pcntl \
    && rm -rf /tmp/*

# INSTALACIÓN DE COMPOSER (para el dump-autoload final)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 1. COPIAMOS EL CÓDIGO FUENTE
COPY . .

# 2. COPIAMOS LAS DEPENDENCIAS DE PHP (desde stage 1)
COPY --from=vendor-builder /app/vendor ./vendor

# 3. COPIAMOS LOS ASSETS COMPILADOS (desde stage 2)
COPY --from=node-builder /app/public/build ./public/build

# FINALIZAR CONFIGURACIÓN DE PHP/LARAVEL
# Agregamos excepción de git para evitar error de ownership
# Y nos aseguramos de que no haya caches previos antes del dump
RUN git config --global --add safe.directory /var/www/html \
    && rm -f bootstrap/cache/*.php \
    && composer dump-autoload --optimize --classmap-authoritative \
    && php artisan filament:assets --no-interaction \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# CONFIGURACIÓN DE APACHE
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf \
    && echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on' >> /etc/apache2/apache2.conf

# CONFIGURACIÓN DE SUPERVISOR
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# PERMISOS
# Es importante que storage y bootstrap/cache tengan permisos de escritura para www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# SCRIPT DE INICIO
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# FINALIZACIÓN
RUN update-rc.d apache2 disable
EXPOSE 80
CMD ["/usr/local/bin/start.sh"]
