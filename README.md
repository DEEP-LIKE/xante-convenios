# Portal de Convenios XANTE.MX

Sistema de gestión de convenios de compraventa de propiedades desarrollado con Laravel 12 y FilamentPHP 4.

## Características

- **Panel de Administración**: Interface moderna con FilamentPHP
- **Wizard Multi-paso**: Proceso guiado para crear convenios
- **Generación de PDFs**: Documentos automáticos con plantillas Blade
- **Sistema de Correos**: Envío automático con colas de Laravel
- **Gestión Completa**: CRUD para clientes, propiedades y convenios
- **Dashboard Analítico**: Estadísticas y gráficos de convenios

## Stack Tecnológico

- **Framework**: Laravel 12
- **Panel Admin**: FilamentPHP 4
- **Frontend**: Livewire + Tailwind CSS
- **PDF Generation**: Barryvdh/laravel-dompdf
- **Queue System**: Laravel Queues
- **Database**: MySQL/PostgreSQL/SQLite

## Instalación

### 1. Clonar el repositorio
```bash
git clone <repository-url>
cd xante
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Configurar el entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar la base de datos
Edita el archivo `.env` con tus credenciales de base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xante_convenios
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Configurar el correo electrónico
Configura el servicio de correo en `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@xante.mx
MAIL_FROM_NAME="XANTE.MX"
```

### 6. Ejecutar migraciones y seeders
```bash
php artisan migrate --seed
```

### 7. Crear enlace simbólico para storage
```bash
php artisan storage:link
```

### 8. Compilar assets
```bash
npm run build
```

## Uso

### 1. Iniciar el servidor
```bash
php artisan serve
```

### 2. Iniciar el worker de colas (en otra terminal)
```bash
php artisan queue:work
```

### 3. Acceder al panel
Visita `http://localhost:8000/admin` y usa las credenciales:
- **Email**: admin@carbono.mx
- **Password**: password

## Estructura del Sistema

### Modelos Principales

- **User**: Usuarios del sistema con roles
- **Client**: Clientes con información personal completa
- **Property**: Propiedades con detalles y valuación
- **Agreement**: Convenios que relacionan cliente y propiedad
- **Calculation**: Cálculos financieros del convenio

### Flujo de Trabajo

1. **Crear Convenio**: Wizard de 3 pasos
   - Paso 1: Datos del cliente y propiedad
   - Paso 2: Calculadora financiera
   - Paso 3: Vista previa y envío

2. **Procesamiento Automático**:
   - Generación de PDF con plantilla profesional
   - Envío por correo electrónico al cliente
   - Actualización de estado del convenio

3. **Gestión**:
   - Dashboard con estadísticas
   - CRUD completo para todas las entidades
   - Filtros y búsquedas avanzadas

### Recursos Filament

- **Dashboard**: Vista principal con estadísticas
- **AgreementResource**: Gestión de convenios con wizard
- **ClientResource**: Gestión de clientes
- **PropertyResource**: Gestión de propiedades
- **UserResource**: Gestión de usuarios y roles

## Configuración de Producción

### 1. Optimizaciones
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 2. Supervisor para colas
Crear archivo `/etc/supervisor/conf.d/xante-worker.conf`:
```ini
[program:xante-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/xante/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/xante/storage/logs/worker.log
```

### 3. Configurar cron
```bash
* * * * * cd /path/to/xante && php artisan schedule:run >> /dev/null 2>&1
```

## Personalización

### Plantilla PDF
Edita `resources/views/pdfs/convenio.blade.php` para personalizar el diseño del convenio.

### Email Template
Modifica `resources/views/emails/agreement.blade.php` para cambiar el diseño del correo.

### Dashboard
Personaliza `app/Filament/Pages/Dashboard.php` para agregar más widgets o estadísticas.

## Seguridad

- Validación de CURP y RFC mexicanos
- Autenticación requerida para acceso al panel
- Restricción de acceso por dominio de email (@carbono.mx)
- Almacenamiento seguro de PDFs
- Logs de errores y actividades

## Soporte

Para soporte técnico o consultas:
- Email: info@xante.mx
- Tel: +52 (55) 1234-5678

## Licencia

Este proyecto es propiedad de XANTE.MX. Todos los derechos reservados.
