# ----------------------------------------------------------------------
# 1. IMAGEN BASE: PHP 8.3 con Apache (Ubuntu/Debian)
# ----------------------------------------------------------------------
FROM php:8.3-apache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Activar módulos necesarios de Apache
RUN a2enmod rewrite headers

# ----------------------------------------------------------------------
# 2. INSTALACIÓN DE HERRAMIENTAS Y LIBRERÍAS
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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------------------
# 3. INSTALAR NODE.JS (Versión actualizada desde NodeSource)
# ----------------------------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------------------
# 4. INSTALACIÓN DE COMPOSER
# ----------------------------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ----------------------------------------------------------------------
# 5. INSTALACIÓN DE EXTENSIONES DE PHP
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
    && rm -rf /tmp/*

# ----------------------------------------------------------------------
# 6. COPIAR ARCHIVOS DE DEPENDENCIAS PRIMERO (optimización de cache)
# ----------------------------------------------------------------------
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Instalar dependencias de PHP (sin autoloader todavía)
RUN composer install --no-dev --no-autoloader --no-scripts

# Instalar dependencias de Node.js
RUN npm ci

# ----------------------------------------------------------------------
# 7. COPIAR TODO EL CÓDIGO FUENTE
# ----------------------------------------------------------------------
COPY . .

# ----------------------------------------------------------------------
# 8. FINALIZAR COMPOSER Y COMPILAR ASSETS
# ----------------------------------------------------------------------
# Generar autoloader optimizado
RUN composer dump-autoload --optimize

# Publicar assets de Filament ANTES de compilar Vite
RUN php artisan filament:assets --no-interaction

# Compilar assets de frontend (Vite)
RUN npm run build

# Limpiar archivos innecesarios
RUN rm -rf node_modules

# ----------------------------------------------------------------------
# 9. OPTIMIZACIÓN DE LARAVEL PARA PRODUCCIÓN
# ----------------------------------------------------------------------
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ----------------------------------------------------------------------
# 10. CONFIGURACIÓN DE APACHE
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
# 11. PERMISOS
# ----------------------------------------------------------------------
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ----------------------------------------------------------------------
# 12. EXPONER PUERTO Y COMANDO DE INICIO
# ----------------------------------------------------------------------
EXPOSE 80

CMD ["apache2-foreground"]