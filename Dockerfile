# ----------------------------------------------------------------------
# 1. IMAGEN BASE: PHP 8.3 con Apache (Ubuntu/Debian)
# ----------------------------------------------------------------------
FROM php:8.3-apache

# Establecer el directorio de trabajo (donde estará el código)
WORKDIR /var/www/html

# Activar el módulo de reescritura de Apache, necesario para Laravel (htaccess)
RUN a2enmod rewrite

# ----------------------------------------------------------------------
# 2. INSTALACIÓN DE HERRAMIENTAS Y LIBRERÍAS DE DESARROLLO
# ----------------------------------------------------------------------
# Actualizar el gestor de paquetes (apt), instalar herramientas y librerías de desarrollo
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    wget \
    curl \
    # Librerías para Extensiones de PHP
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libfreetype6-dev \
    # Instalar Node.js y npm (se usa la versión disponible en los repos de Debian)
    nodejs \
    npm \
    # Limpiar caché
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ----------------------------------------------------------------------
# 3. INSTALACIÓN DE COMPOSER (Soluciona "composer: not found")
# ----------------------------------------------------------------------
# Descargar e instalar Composer de forma global
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ----------------------------------------------------------------------
# 4. INSTALACIÓN DE EXTENSIONES DE PHP
# ----------------------------------------------------------------------
# Instalar todas las extensiones requeridas por tu proyecto (Xante)
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
        # Extensión que faltaba en el buildpack para el token de HubSpot
        iconv \
    && rm -rf /tmp/*

# ----------------------------------------------------------------------
# 5. COPIAR CÓDIGO Y DEPENDENCIAS
# ----------------------------------------------------------------------
# Copiar archivos de Composer para aprovechar el cacheo de capas
COPY composer.json composer.lock ./
# Ejecutar la instalación de dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

# Copiar todo el código fuente del proyecto
COPY . .

# ----------------------------------------------------------------------
# 6. COMPILACIÓN DE ASSETS Y OPTIMIZACIÓN DE LARAVEL
# ----------------------------------------------------------------------
# Instalar dependencias de Node.js (Frontend) y compilar assets (npm run build)
RUN npm install \
    && npm run build

# Optimización de Laravel (debe hacerse después de copiar el código)
RUN php artisan config:cache \
    && php artisan route:cache

# ----------------------------------------------------------------------
# 7. CONFIGURACIÓN DE APACHE Y PERMISOS
# ----------------------------------------------------------------------
# Modificar la configuración de Apache para apuntar al directorio 'public' de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

# Ajustar permisos (Apache corre como www-data)
RUN chown -R www-data:www-data storage bootstrap/cache

# ----------------------------------------------------------------------
# 8. EJECUCIÓN DEL CONTENEDOR
# ----------------------------------------------------------------------
EXPOSE 80

# Comando para mantener Apache corriendo en primer plano
CMD ["apache2-foreground"]