# Integración HubSpot - Portal de Convenios XANTE.MX

## 📋 Descripción General

Esta integración permite sincronizar clientes desde HubSpot hacia el Portal de Convenios XANTE.MX de forma unidireccional. Solo se importan contactos que tengan un `xante_id` válido, garantizando que únicamente los clientes relevantes para el sistema sean sincronizados.

## 🏗️ Arquitectura de la Integración

### Componentes Principales

1. **HubspotSyncService** - Servicio principal de sincronización
2. **SyncHubspotClientsJob** - Job asíncrono para procesamiento en segundo plano
3. **Comandos de Artisan** - Herramientas de exploración y pruebas
4. **Interfaz Filament** - Botones de sincronización en la tabla de clientes

### Flujo de Sincronización

```
HubSpot API → HubspotSyncService → Validaciones → Base de Datos Laravel
                     ↓
              SyncHubspotClientsJob (Asíncrono)
                     ↓
              Notificaciones Filament
```

## ⚙️ Configuración

### 1. Variables de Entorno

Agregar al archivo `.env`:

```env
HUBSPOT_TOKEN=tu_token_de_acceso_aqui
```

### 2. Configuración de Cola de Jobs

Asegurar que la cola de Laravel esté configurada en `.env`:

```env
QUEUE_CONNECTION=database
```

### 3. Ejecutar Migraciones

```bash
php artisan migrate
```

Los nuevos campos agregados a la tabla `clients`:
- `hubspot_id` - ID único de HubSpot (VID)
- `hubspot_synced_at` - Timestamp de última sincronización

## 🚀 Uso de la Integración

### Desde la Interfaz Filament

1. **Navegar a Clientes**: Ve a `/admin/clients`
2. **Sincronizar**: Haz clic en "Sincronizar HubSpot"
3. **Ver Estadísticas**: Usa "Estadísticas HubSpot" para ver métricas

### Comandos de Artisan

#### Explorar API de HubSpot
```bash
# Explorar estructura de datos
php artisan hubspot:explore

# Limitar resultados
php artisan hubspot:explore --limit=10
```

#### Probar la Integración
```bash
# Pruebas básicas
php artisan hubspot:test

# Incluir sincronización completa
php artisan hubspot:test --sync

# Probar job asíncrono
php artisan hubspot:test --job
```

#### Procesar Jobs en Cola
```bash
# Procesar jobs pendientes
php artisan queue:work

# Procesar solo jobs de HubSpot
php artisan queue:work --queue=hubspot-sync
```

## 📊 Validaciones y Reglas de Negocio

### Criterios de Importación

✅ **SE IMPORTA** si el contacto tiene:
- `hubspot_id` (hs_object_id) válido
- `xante_id` en propiedades personalizadas
- Datos básicos (nombre, email, etc.)

❌ **NO SE IMPORTA** si:
- No tiene `xante_id` definido
- Ya existe en la base de datos (se actualiza en su lugar)
- Faltan datos críticos

### Campos Mapeados

| Campo HubSpot | Campo Laravel | Descripción |
|---------------|---------------|-------------|
| `hs_object_id` | `hubspot_id` | ID único de HubSpot |
| `firstname` + `lastname` | `name` | Nombre completo |
| `email` | `email` | Correo electrónico |
| `phone` | `phone` | Teléfono |
| `xante_id` | `xante_id` | ID crítico del sistema |

### Propiedades Personalizadas Buscadas

El sistema busca el `xante_id` en estas propiedades:
- `xante_id`
- `xante_client_id`
- `id_xante`
- `client_xante_id`

## 🔧 Configuración Avanzada

### Rate Limiting

La integración incluye limitación de velocidad:
- **10 requests/segundo** máximo
- **Delay de 100ms** entre requests
- **3 reintentos** en caso de error

### Timeouts y Reintentos

```php
// Job Configuration
public int $timeout = 300; // 5 minutos
public int $tries = 3;     // 3 intentos
public int $maxExceptions = 3;
```

### Configuración de Caché

Las estadísticas se almacenan en caché:
- **Estadísticas de sync**: 1 hora
- **Estado de progreso**: 10 minutos
- **Última sincronización**: 1 hora

## 📈 Monitoreo y Logs

### Logs de Laravel

Todos los eventos se registran en `storage/logs/laravel.log`:

```php
// Ejemplos de logs
Log::info('Sincronización HubSpot completada', $stats);
Log::error('Error en contacto HubSpot', ['contact' => $contact]);
Log::warning('Contacto sin xante_id omitido', ['hubspot_id' => $id]);
```

### Notificaciones Filament

Los usuarios reciben notificaciones automáticas:
- ✅ **Éxito**: Resumen de clientes sincronizados
- ⚠️ **Advertencias**: Sincronización en progreso
- ❌ **Errores**: Fallos de conexión o procesamiento

### Métricas Disponibles

```php
$stats = [
    'total_hubspot' => 150,      // Total en HubSpot
    'new_clients' => 25,         // Nuevos importados
    'updated_clients' => 10,     // Actualizados
    'skipped' => 115,            // Omitidos (sin xante_id)
    'errors' => 0,               // Errores de procesamiento
    'processed_pages' => 3,      // Páginas de API procesadas
];
```

## 🛠️ Solución de Problemas

### Errores Comunes

#### 1. Token No Configurado
```
❌ HUBSPOT_TOKEN no está configurado en el archivo .env
```
**Solución**: Agregar `HUBSPOT_TOKEN=tu_token` al archivo `.env`

#### 2. Error de Conexión
```
❌ Error de conexión con HubSpot: HTTP 401
```
**Solución**: Verificar que el token sea válido y tenga permisos

#### 3. Job No Se Procesa
```
⚠️ Job despachado pero no se ejecuta
```
**Solución**: Ejecutar `php artisan queue:work`

#### 4. Sincronización Bloqueada
```
⚠️ Ya hay una sincronización en progreso
```
**Solución**: Esperar o limpiar caché: `php artisan cache:clear`

### Comandos de Diagnóstico

```bash
# Verificar configuración
php artisan config:show hubspot

# Ver jobs en cola
php artisan queue:status

# Limpiar caché
php artisan cache:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## 🔒 Seguridad y Mejores Prácticas

### Protección de Token

- ✅ Token almacenado en `.env` (no versionado)
- ✅ Configuración centralizada en `config/hubspot.php`
- ✅ Validación de token antes de cada operación

### Rate Limiting

- ✅ Respeto a límites de API de HubSpot
- ✅ Delays automáticos entre requests
- ✅ Manejo de errores 429 (Too Many Requests)

### Validación de Datos

- ✅ Validación de `xante_id` obligatorio
- ✅ Verificación de duplicados por `hubspot_id`
- ✅ Sanitización de datos antes de insertar

## 📚 API de HubSpot Utilizada

### Endpoints Principales

```
GET /crm/v3/objects/contacts
GET /crm/v3/properties/contacts
GET /crm/v3/objects/deals (futuro)
```

### Propiedades Solicitadas

```
firstname,lastname,email,phone,hs_object_id,createdate,lastmodifieddate,xante_id
```

## 🔄 Futuras Expansiones

### Fase 2: Actualización Bidireccional

- Actualizar contactos EN HubSpot desde Laravel
- Sincronización de deals/negocios
- Webhooks para sincronización en tiempo real

### Mejoras Planificadas

- Dashboard de métricas de sincronización
- Configuración de mapeo de campos desde UI
- Sincronización selectiva por filtros
- Historial de sincronizaciones

## 📞 Soporte

Para soporte técnico o preguntas sobre la integración:

1. **Logs**: Revisar `storage/logs/laravel.log`
2. **Comandos de prueba**: `php artisan hubspot:test`
3. **Documentación HubSpot**: [HubSpot API Docs](https://developers.hubspot.com/docs/api/overview)

---

**Versión**: 1.0.0  
**Última actualización**: Noviembre 2025  
**Compatibilidad**: Laravel 12, FilamentPHP 4, HubSpot API v3
