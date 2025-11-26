# Portal de Convenios XANTE.MX

Sistema de gestión de convenios de compraventa de propiedades integrado con HubSpot CRM, desarrollado con Laravel 12 y FilamentPHP 4.

## 🎯 Características Principales

### Integración HubSpot
- **Sincronización Bidireccional**: Pull (HubSpot → Local) y Push (Local → HubSpot)
- **Protección contra Race Conditions**: Validación de fechas de modificación
- **Mapeo Automático**: Contactos y Deals sincronizados con campos personalizados
- **Visualización en Tiempo Real**: Consulta de estado y monto desde HubSpot sin guardar localmente

### Sistema de Wizards
- **Wizard 1 - Captura de Datos**: 4 pasos para datos del cliente, cónyuge, propiedad y cálculos financieros
- **Wizard 2 - Gestión de Documentos**: 3 pasos para envío, recepción y cierre exitoso
- **Generación Automática de PDFs**: 6 documentos profesionales generados al finalizar Wizard 1
- **Envío por Email**: Notificaciones automáticas a cliente y asesor

### Panel de Administración
- **Dashboard Analítico**: Estadísticas de convenios y sincronización
- **Gestión de Usuarios**: Roles (Administrador/Asesor) con permisos diferenciados
- **Tabla de Clientes**: Visualización de estado HubSpot en tiempo real
- **Restricción de Eliminación**: Solo administradores pueden eliminar registros

## 🛠 Stack Tecnológico

- **Framework**: Laravel 12
- **Panel Admin**: FilamentPHP 4
- **Frontend**: Livewire + Tailwind CSS
- **PDF Generation**: Barryvdh/laravel-dompdf
- **Queue System**: Laravel Queues
- **CRM Integration**: HubSpot API v3
- **Database**: MySQL/PostgreSQL

## 📦 Instalación

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
Edita el archivo `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=xante_convenios
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Configurar HubSpot
Agrega tu token de HubSpot en `.env`:
```env
HUBSPOT_API_TOKEN=tu_token_aqui
HUBSPOT_API_BASE_URL=https://api.hubapi.com
```

### 6. Configurar el correo electrónico
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@xante.mx
MAIL_FROM_NAME="XANTE.MX"
```

### 7. Ejecutar migraciones y seeders
```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
```

### 8. Crear enlace simbólico para storage
```bash
php artisan storage:link
```

### 9. Compilar assets
```bash
npm run build
```

## 🚀 Uso

### 1. Iniciar el servidor
```bash
php artisan serve
```

### 2. Iniciar el worker de colas (en otra terminal)
```bash
php artisan queue:work
```

### 3. Acceder al panel
Visita `http://localhost:8000/admin` y usa las credenciales por defecto:

| Rol | Email | Contraseña |
|-----|-------|------------|
| **Administrador** | admin@xante.com | admin123 |
| **Asesor** | asesor@xante.com | asesor123 |

## 🔄 Flujo de Trabajo

### 1. Sincronización desde HubSpot (Pull)
1. En `/admin/clients`, clic en **"Sincronizar HubSpot"**
2. El sistema trae Deals con `estatus_de_convenio = "Aceptado"`
3. Crea/actualiza clientes locales con `xante_id` válido
4. **Protección**: No sobrescribe convenios en proceso o completados

### 2. Creación de Convenio (Wizard 1)
1. Seleccionar cliente sincronizado desde HubSpot
2. **Paso 1**: Datos personales del titular
3. **Paso 2**: Datos del cónyuge (si aplica)
4. **Paso 3**: Datos de la propiedad (AC/Privada)
5. **Paso 4**: Calculadora financiera automática
6. Al finalizar:
   - Genera 6 PDFs profesionales
   - Envía email al cliente y asesor
   - **Actualiza HubSpot**: `estatus_de_convenio = "En Proceso"`

### 3. Gestión de Documentos (Wizard 2)
1. **Paso 1 - Envío**: Enviar documentos generados al cliente
2. **Paso 2 - Recepción**: Subir documentos firmados/validados del cliente
   - Al avanzar: **Actualiza HubSpot**: `estatus_de_convenio = "Aceptado"`
3. **Paso 3 - Cierre**: Capturar valor final de propuesta
   - Al guardar: **Actualiza HubSpot**: `amount = valor_propuesta`

## 📊 Estructura del Sistema

### Modelos Principales

- **User**: Usuarios con roles (admin/asesor)
- **Client**: Clientes sincronizados desde HubSpot
- **Agreement**: Convenios con wizard_data completo
- **GeneratedDocument**: PDFs generados automáticamente
- **ClientDocument**: Documentos subidos por el cliente

### Sincronización HubSpot

#### Mapeo de Campos (Pull: HubSpot → Local)

**Contacto HubSpot → Cliente Local:**
- `email` → `email`
- `phone` → `phone`
- `firstname + lastname` → `name`
- `address` → `current_address`
- `city` → `municipality`
- `state` → `state`
- `zip` → `postal_code`

**Deal HubSpot → Agreement Local:**
- `estatus_de_convenio` → Filtro de importación (solo "Aceptado")
- `amount` → `proposal_value`
- `createdate` → `fecha_registro`

#### Mapeo de Campos (Push: Local → HubSpot)

**Cliente Local → Deal HubSpot:**
- `name` → `nombre_del_titular`
- `current_address` → `calle_o_privada_`
- `neighborhood` → `colonia`
- `state` → `estado`

**Agreement Local → Deal HubSpot:**
- `status: draft/in_progress` → `estatus_de_convenio: "En Proceso"`
- `status: completed` → `estatus_de_convenio: "Aceptado"`
- `proposal_value` → `amount`

### Documentos Generados (Wizard 1)

1. **Datos Generales** - Información completa del convenio
2. **Acuerdo de Promoción** - Términos y condiciones
3. **Condiciones de Comercialización** - Detalles de la venta
4. **Checklist de Expediente** - Lista de documentos requeridos
5. **Checklist de Expediente (Actualizado)** - Con documentos marcados
6. **ZIP con todos los documentos**

## 🔐 Seguridad y Permisos

### Roles de Usuario

| Permiso | Administrador | Asesor |
|---------|---------------|--------|
| Ver clientes | ✅ | ✅ |
| Crear convenios | ✅ | ✅ |
| Ver monto HubSpot | ✅ | ❌ |
| Eliminar registros | ✅ | ❌ |
| Gestionar usuarios | ✅ | ❌ |
| Sincronizar HubSpot | ✅ | ✅ |

### Protecciones Implementadas

1. **Race Conditions**: Compara `updated_at` local vs `lastmodifieddate` de HubSpot
2. **Convenios en Proceso**: No se sobrescriben desde HubSpot si están activos
3. **Validación de Email**: Solo dominios `@xante.com` y `@carbono.mx`
4. **Campos Vacíos**: HubSpot no borra datos locales si envía campos vacíos

## 🧪 Scripts de Utilidad

### Comparar Datos HubSpot vs Local
```bash
php scripts/compare-hubspot-contact.php
```

### Auditoría Profunda de Sincronización
```bash
php scripts/deep-audit-sync.php
```

### Forzar Sincronización de un Convenio
```bash
php scripts/force-sync-106.php
```

## 🚀 Configuración de Producción

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

### 3. Configurar cron para sincronización automática
```bash
# Sincronizar HubSpot cada hora
0 * * * * cd /path/to/xante && php artisan hubspot:sync >> /dev/null 2>&1
```

## 📝 Notas Importantes

- **HubSpot como Fuente de Verdad**: Los clientes se importan desde HubSpot, no se crean manualmente
- **No hay Seeders de Clientes**: Los clientes vienen exclusivamente de la sincronización con HubSpot
- **Convenios Locales**: Se crean en la plataforma y sincronizan su estado a HubSpot
- **Documentos**: Se generan y almacenan localmente, no en HubSpot

## 🐛 Troubleshooting

### Error: "Cliente no tiene HubSpot ID"
**Solución**: Ejecutar sincronización desde `/admin/clients` → "Sincronizar HubSpot"

### Datos desactualizados en tabla
**Solución**: Las columnas de HubSpot consultan en tiempo real. Refrescar la página.

### Convenio sobrescrito por sincronización
**Solución**: El sistema protege convenios `in_progress` y `completed`. Verificar el estado del convenio.

## 📞 Soporte

Para soporte técnico o consultas:
- Email: info@xante.mx
- Tel: +52 (55) 1234-5678

## 📄 Licencia

Este proyecto es propiedad de XANTE.MX. Todos los derechos reservados.
