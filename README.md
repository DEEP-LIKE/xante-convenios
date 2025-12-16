# Xante - Convenios

Sistema de gestión de convenios inmobiliarios con integración a HubSpot.

## 🚀 Características Principales

### Sistema de Usuarios y Roles

El sistema cuenta con 3 roles principales con permisos específicos:

- **Ejecutivo**: Realización de calculadoras y gestión de convenios
- **Coordinador FI**: Validación de calculadoras, aprobación de cambios de precio, gestión de estados
- **Gerencia**: Autorización de cambios de comisión, gestión completa del sistema

### Calculadora de Cotizaciones

- Cálculo automático de comisiones según estado
- Porcentajes de Gastos de Escrituración (GE) por estado:
  - Estado de México: 9.5%
  - Querétaro: 10%
  - Puebla: 7.5%
  - Hidalgo: 8%
  - Quintana Roo: 8%
- Integración con clientes y propuestas
- Sistema de autorizaciones para cambios

### Gestión de Cuentas Bancarias

Matriz de cuentas bancarias por estado con soporte para múltiples cuentas:

| Estado | Municipio | Banco | Cuenta | CLABE |
|--------|-----------|-------|--------|-------|
| Estado de México | Tecámac | BBVA | 0154352572 | 012180001543525726 |
| Hidalgo | Tula | BBVA | 0183189163 | 012180001831891638 |
| Hidalgo | Pachuca | BBVA | 0154870212 | 012180001548702120 |
| Querétaro | - | BBVA | 0177112955 | 012180001771129554 |
| Puebla | - | BBVA | 0108111332 | 012180001081113328 |
| Quintana Roo | Cancún | BBVA | 0183189759 | 012180001831897593 |

### Integración con HubSpot

- Sincronización bidireccional de clientes
- Gestión de Deals y Contacts
- Campo para nombre de inmueble (supervisor)
- Documentación completa en `HUBSPOT_INTEGRATION.md`

### Sistema de Autorizaciones

- Solicitudes de cambio de comisión (requiere aprobación de Gerencia)
- Solicitudes de cambio de precio (requiere aprobación de Coordinador FI o Gerencia)
- Tracking completo de autorizaciones con motivos y montos
- Políticas de acceso por rol

## 📦 Instalación

### Requisitos

- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM

### Configuración Inicial

```bash
# Clonar repositorio
git clone [repository-url]
cd xante

# Instalar dependencias
composer install
npm install

# Configurar environment
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
DB_DATABASE=xante
DB_USERNAME=root
DB_PASSWORD=

# Ejecutar migraciones y seeders
php artisan migrate --seed

# iniciar servidor
composer run dev

# iniciar worker
php artisan queue:work

# Compilar assets
npm run build
```

### Seeders Importantes

```bash
# Usuarios de prueba
php artisan db:seed --class=UserSeeder

# Porcentajes de GE por estado
php artisan db:seed --class=StateCommissionRateSeeder

# Cuentas bancarias
php artisan db:seed --class=StateBankAccountSeeder
```

## 🔐 Usuarios de Prueba

| Usuario | Email | Contraseña | Rol |
|---------|-------|------------|-----|
| Gerencia Xante | gerencia@xante.com | `Xante2025!` | gerencia |
| Coordinador FI | coordinador@xante.com | `Xante2025!` | coordinador_fi |
| Ejecutivo Demo | ejecutivo@xante.com | `Xante2025!` | ejecutivo |

### Dominios Permitidos

- @xante.com

## 🗄️ Estructura de Base de Datos

### Tablas Principales

- `users` - Usuarios del sistema con roles
- `clients` - Clientes sincronizados con HubSpot
- `agreements` - Convenios inmobiliarios
- `proposals` - Propuestas de cotización
- `state_commission_rates` - Porcentajes de GE por estado
- `state_bank_accounts` - Cuentas bancarias por estado/municipio
- `quote_authorizations` - Sistema de autorizaciones

### Migraciones Recientes

```bash
2025_12_04_210357_update_user_roles_to_new_structure
2025_12_04_210357_add_municipality_to_state_bank_accounts
2025_12_04_211813_create_quote_authorizations_table
2025_12_04_212153_add_bank_account_id_to_agreements_table
2025_12_04_213234_add_nombre_inmueble_to_agreements_table
2025_12_04_213235_add_tipo_credito_conyugal_to_agreements_table
```

## 🔧 Configuración de HubSpot

### Variables de Entorno

```env
HUBSPOT_ACCESS_TOKEN=your_access_token
HUBSPOT_PORTAL_ID=your_portal_id
```

### Comandos Disponibles

```bash
# Sincronizar clientes desde HubSpot
php artisan hubspot:sync

# Explorar propiedades de HubSpot
php artisan hubspot:explore

# Probar conexión
php artisan hubspot:test
```

Ver documentación completa en `HUBSPOT_INTEGRATION.md`

## 📋 Permisos por Rol

### Ejecutivo
- ✅ Crear y editar convenios
- ✅ Usar calculadora de cotizaciones
- ✅ Solicitar cambios de comisión/precio
- ✅ Ver sus propias autorizaciones
- ❌ Aprobar autorizaciones
- ❌ Editar configuraciones del sistema

### Coordinador FI
- ✅ Todo lo de Ejecutivo
- ✅ Aprobar cambios de precio
- ✅ Crear/editar estados y % GE
- ✅ Ver todas las autorizaciones
- ❌ Aprobar cambios de comisión
- ❌ Eliminar estados

### Gerencia
- ✅ Acceso completo al sistema
- ✅ Aprobar cambios de comisión
- ✅ Aprobar cambios de precio
- ✅ Eliminar estados
- ✅ Gestión completa de usuarios

## 🎯 Flujos de Trabajo

### Creación de Convenio

1. Ejecutivo crea convenio usando wizard
2. Sistema calcula automáticamente comisiones según estado
3. Selecciona cuenta bancaria (si hay múltiples opciones)
4. Captura datos del cliente y cónyuge (si aplica)
5. Genera PDFs automáticamente
6. Sincroniza con HubSpot

### Solicitud de Cambio de Precio

1. Ejecutivo solicita cambio desde calculadora
2. Captura motivo y monto de descuento
3. Sistema crea registro en `quote_authorizations`
4. Coordinador FI o Gerencia revisa solicitud
5. Aprueba o rechaza con motivo
6. Ejecutivo recibe notificación

### Solicitud de Cambio de Comisión

1. Ejecutivo solicita cambio
2. Sistema crea registro en `quote_authorizations`
3. Solo Gerencia puede aprobar
4. Aprueba o rechaza con motivo
5. Ejecutivo recibe notificación

## 📊 Validaciones Importantes

### Créditos Conyugales

El sistema valida automáticamente:

- Si estado civil es "casado" → régimen es obligatorio
- Si régimen es "bienes mancomunados" → datos del cónyuge obligatorios
- Si tipo de crédito es "coacreditado" o "conyugal" → datos del cónyuge obligatorios

Tipos de crédito:
- `individual` - Crédito individual
- `coacreditado` - Crédito coacreditado
- `conyugal` - Crédito conyugal

## 🛠️ Desarrollo

### Comandos Útiles

```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Recrear base de datos
php artisan migrate:fresh --seed
```

### Testing

```bash
# Ejecutar tests
php artisan test

# Con coverage
php artisan test --coverage
```

## 📝 Documentación Adicional

- `HUBSPOT_INTEGRATION.md` - Documentación completa de integración con HubSpot
- `gap_analysis.md` - Análisis de cumplimiento de requerimientos
- `implementation_plan.md` - Plan de implementación detallado
- `walkthrough.md` - Guía de funcionalidades implementadas

## 🔄 Estado del Proyecto

**Última actualización**: 04/12/2025

### Implementado (80%)
- ✅ Sistema de roles y permisos
- ✅ Calculadora con % GE correctos
- ✅ Cuentas bancarias por estado
- ✅ Sistema de autorizaciones (backend)
- ✅ Integración HubSpot (parcial)
- ✅ Validaciones de cónyuge (estructura)

### Pendiente (20%)
- ⏳ UI de autorizaciones (QuoteAuthorizationResource)
- ⏳ Selector de cuenta bancaria en wizard
- ⏳ Sincronización completa con HubSpot
- ⏳ Validaciones obligatorias de cónyuge en UI
- ⏳ Integración de PDFs con cuenta seleccionada

## 📞 Soporte

Para dudas o problemas, contactar al equipo de desarrollo.

## 📄 Licencia

Propietario: Xante & VI, SAPI de CV
