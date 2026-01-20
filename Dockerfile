# ----------------------------------------------------------------------
# STAGE 1: BUILD DE ASSETS CON NODE.JS
# ----------------------------------------------------------------------
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copiar archivos de dependencias de Node
COPY package.json package-lock.json ./

# Instalar dependencias de Node
RUN npm ci

# Copiar archivos necesarios para el build
COPY . .

# Compilar assets de frontend (Vite)
RUN npm run build

# ----------------------------------------------------------------------
# STAGE 2: IMAGEN DE PRODUCCIÓN CON PHP Y APACHE
# ----------------------------------------------------------------------
FROM php:8.3-apache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Activar módulos necesarios de Apache
RUN a2enmod rewrite headers

# ----------------------------------------------------------------------
# INSTALACIÓN DE HERRAMIENTAS Y LIBRERÍAS (+ SUPERVISOR)
# ----------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    wget \
    curl \
    libcurl4-openssl-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libfreetype6-dev \
    supervisor \
    cron \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------------------
# INSTALACIÓN DE COMPOSER
# ----------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ----------------------------------------------------------------------
# INSTALACIÓN DE EXTENSIONES DE PHP
# ----------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        gd \
        mbstring \
        bcmath \
        curl \
        exif \
        iconv \
        pcntl \
    && rm -rf /tmp/*

# ----------------------------------------------------------------------
# COPIAR ARCHIVOS DE DEPENDENCIAS PRIMERO (optimización de cache)
# ----------------------------------------------------------------------
COPY composer.json composer.lock ./

# Instalar dependencias de PHP (sin autoloader todavía)
RUN composer install --no-dev --no-autoloader --no-scripts \
    && rm -rf /root/.composer/cache

# ----------------------------------------------------------------------
# COPIAR TODO EL CÓDIGO FUENTE
# ----------------------------------------------------------------------
COPY . .

# Copiar assets compilados desde el stage de Node
COPY --from=node-builder /app/public/build ./public/build

# ----------------------------------------------------------------------
# FINALIZAR COMPOSER Y OPTIMIZACIONES
# ----------------------------------------------------------------------
# Generar autoloader optimizado
RUN composer dump-autoload --optimize --classmap-authoritative

# Publicar assets de Filament
RUN php artisan filament:assets --no-interaction

# ----------------------------------------------------------------------
# OPTIMIZACIÓN DE LARAVEL PARA PRODUCCIÓN
# ----------------------------------------------------------------------
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ----------------------------------------------------------------------
# CONFIGURACIÓN DE APACHE
# ----------------------------------------------------------------------
# Apuntar DocumentRoot a /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

# Configurar Apache para permitir .htaccess y forzar HTTPS
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configurar headers para HTTPS
RUN echo 'SetEnvIf X-Forwarded-Proto https HTTPS=on' >> /etc/apache2/apache2.conf

# ----------------------------------------------------------------------
# CONFIGURACIÓN DE SUPERVISOR PARA LARAVEL QUEUE
# ----------------------------------------------------------------------
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ----------------------------------------------------------------------
# PERMISOS
# ----------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ----------------------------------------------------------------------
# SCRIPT DE INICIO
# ----------------------------------------------------------------------
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# ----------------------------------------------------------------------
# EXPONER PUERTO Y COMANDO DE INICIO
# ----------------------------------------------------------------------
# IMPORTANTE: Desactivamos que apache inicie por si solo como servicio background
RUN update-rc.d apache2 disable

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]