# Xante - Convenios 

Sistema de gestión de convenios inmobiliarios con integración a HubSpot.

## 🚀 Características Principales

### Flujo de Trabajo (Two-Wizard Flow)

El sistema utiliza un proceso de dos fases para la gestión de convenios:

1.  **Wizard 1 (Creación y Cotización)**: Captura de datos del titular, cónyuge, propiedad y cálculos financieros.
2.  **Wizard 2 (Documentación y Cierre)**: Gestión de documentos generados, carga de archivos del cliente y seguimiento hasta el cierre.

### Sistema de Usuarios y Roles

El sistema cuenta con 3 roles principales con permisos específicos:

- **Ejecutivo**: Realización de cotizaciones y gestión de convenios.
- **Coordinador FI**: Validación de calculadoras, aprobación de cambios de precio y gestión de estados.
- **Gerencia**: Autorización de cambios de comisión y gestión completa del sistema.

### Calculadora Financiera Avanzada

- Cálculo automático de comisiones e IVA en tiempo real.
- **Porcentajes de Gastos de Escrituración (GE)** por estado configurables (Edomex: 9.5%, Qro: 10%, Pue: 7.5%, Hgo/Q.Roo: 8%).
- Sistema de **Validaciones y Autorizaciones** para cambios de precio o comisión que se salgan de los parámetros establecidos.

### Generación Automatizada de Documentos (PDF)

El sistema genera automáticamente 6 documentos clave al finalizar la primera fase:
- Acuerdo de Promoción Inmobiliaria
- Datos Generales - Fase I
- Checklist de Expediente Básico
- Condiciones para Comercialización
- Aviso de Privacidad
- EUC Venta Convenio

### Integración con HubSpot

- **Sincronización Bidireccional**: Los clientes (Contacts) y tratos (Deals) se mantienen sincronizados.
- **Validación CRÍTICA**: Los contactos deben tener un `xante_id` válido para ser importados.
- Mapeo automático de propiedades personalizadas entre Xante y HubSpot.

### Gestión de Cuentas Bancarias

Matriz de cuentas bancarias por estado y municipio. El sistema permite seleccionar la cuenta específica durante el proceso del convenio.

| Estado | Municipio | Banco | Cuenta | CLABE |
|--------|-----------|-------|--------|-------|
| Estado de México | Tecámac | BBVA | 0154352572 | 012180001543525726 |
| Hidalgo | Tula | BBVA | 0183189163 | 012180001831891638 |
| Hidalgo | Pachuca | BBVA | 0154870212 | 012180001548702120 |
| Querétaro | - | BBVA | 0177112955 | 012180001771129554 |
| Puebla | - | BBVA | 0108111332 | 012180001081113328 |
| Quintana Roo | Cancún | BBVA | 0183189759 | 012180001831897593 |

## 📦 Stack Tecnológico

- **Framework**: Laravel 12.0
- **Frontend**: Filament 4.0 (TALL Stack: Tailwind, Alpine.js, Laravel, Livewire)
- **Base de Datos**: MySQL 8.0+
- **PDF**: Barryvdh/laravel-dompdf
- **Integración**: HubSpot API

## 🔧 Instalación y Despliegue

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

# Ejecutar migraciones y seeders
php artisan migrate:fresh --seed
```

### Script de Despliegue (Docker)

El proyecto incluye un script `deploy.sh` para despliegues automatizados en servidores con Docker.

```bash
./deploy.sh
```

## 🔐 Usuarios de Prueba

| Usuario | Email | Contraseña | Rol |
|---------|-------|------------|-----|
| Gerencia Xante | gerencia@xante.com | `Xante2025!` | gerencia |
| Coordinador FI | coordinador@xante.com | `Xante2025!` | coordinador_fi |
| Ejecutivo Demo | ejecutivo@xante.com | `Xante2025!` | ejecutivo |

## 🔄 Estado del Proyecto

**Última actualización**: 29/12/2025

- ✅ Sistema de roles y permisos completo.
- ✅ Flujo de Dos Wizards (Calculadora + Documentación).
- ✅ Generación de 6 documentos PDF automáticos.
- ✅ Integración HubSpot funcional (Sync de Deals/Contacts).
- ✅ Sistema de Autorizaciones de Precio y Comisión.
- ✅ Gestión de multicuenta bancaria por estado.

---
Propietario: Xante & VI, SAPI de CV
