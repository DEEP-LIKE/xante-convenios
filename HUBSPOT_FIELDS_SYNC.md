# Campos Sincronizados desde HubSpot

Este documento lista **todos los campos** que se sincronizan desde HubSpot hacia la base de datos local cuando se importa un Deal con estado "Aceptado".

## 📋 Datos Generales

| Campo en HubSpot | Campo en BD Local | Descripción |
|------------------|-------------------|-------------|
| `createdate` | `fecha_registro` | Fecha de creación del Deal en HubSpot |
| `xante_id` (del Contact) | `xante_id` | ID único del cliente en Xante |
| `hubspot_id` (del Contact) | `hubspot_id` | ID del contacto en HubSpot |
| `hubspot_deal_id` | `hubspot_deal_id` | ID del Deal en HubSpot |

---

## 👤 Datos del Titular

### Información Personal

| Campo en HubSpot | Campo en BD Local | Descripción |
|------------------|-------------------|-------------|
| `nombre_completo` | `name` | Nombre completo del titular |
| `email` | `email` | Correo electrónico |
| `phone` / `mobilephone` | `phone` | Teléfono móvil |
| `telefono_oficina` | `office_phone` | Teléfono de oficina |
| `curp` | `curp` | CURP del titular |
| `rfc` | `rfc` | RFC del titular |
| `estado_civil` | `civil_status` | Estado civil |
| `ocupacion` | `occupation` | Ocupación/Profesión |

### Domicilio del Titular

| Campo en HubSpot | Campo en BD Local | Descripción |
|------------------|-------------------|-------------|
| `domicilio_actual` + `numero_casa` | `current_address` | Calle y número |
| `colonia` | `neighborhood` | Colonia |
| `codigo_postal` | `postal_code` | Código postal |
| `municipio` | `municipality` | Municipio |
| `estado` | `state` | Estado |

---

## 💑 Datos del Cónyuge

### Información Personal

| Campo en HubSpot | Campo en BD Local (Spouse) | Descripción |
|------------------|----------------------------|-------------|
| `nombre_completo_conyuge` | `name` | Nombre completo del cónyuge |
| `email_conyuge` | `email` | Correo electrónico |
| `telefono_movil_conyuge` | `phone` | Teléfono móvil |
| `curp_conyuge` | `curp` | CURP del cónyuge |

### Domicilio del Cónyuge

| Campo en HubSpot | Campo en BD Local (Spouse) | Descripción |
|------------------|----------------------------|-------------|
| `domicilio_actual_conyuge` + `numero_casa_conyuge` | `current_address` | Calle y número |
| `colonia_conyuge` | `neighborhood` | Colonia |
| `codigo_postal_conyuge` | `postal_code` | Código postal |
| `municipio_conyuge` | `municipality` | Municipio |
| `estado_conyuge` | `state` | Estado |

---

## 🏠 Datos de la Propiedad

> **Nota:** Estos campos se solicitan a HubSpot pero actualmente **NO se guardan** en la tabla `clients`. Se guardarían en la tabla `agreements` cuando se crea el convenio.

| Campo en HubSpot | Descripción |
|------------------|-------------|
| `domicilio_convenio` | Dirección de la vivienda |
| `comunidad` | Nombre de la comunidad/fraccionamiento |
| `tipo_vivienda` | Tipo de vivienda |
| `prototipo` | Prototipo de la casa |
| `lote` | Número de lote |
| `manzana` | Número de manzana |
| `etapa` | Etapa del desarrollo |
| `municipio_propiedad` | Municipio de la propiedad |
| `estado_propiedad` | Estado de la propiedad |

---

## 💰 Datos Financieros

> **Nota:** Estos campos se solicitan a HubSpot pero actualmente **NO se guardan** en la tabla `clients`. Se guardarían en la tabla `agreements` cuando se crea el convenio.

| Campo en HubSpot | Descripción |
|------------------|-------------|
| `valor_convenio` | Valor total del convenio |
| `precio_promocion` | Precio con promoción |
| `comision_total_pagar` | Comisión total a pagar |
| `ganancia_final` | Ganancia final estimada |

---

## ⚙️ Reglas de Sincronización

1. **Solo si el valor NO está vacío:** Si un campo viene vacío desde HubSpot, no se actualiza en la BD local.
2. **Prioridad Deal > Contact:** Si un dato existe tanto en el Deal como en el Contact, se usa el del Deal.
3. **Cónyuge condicional:** Si `nombre_completo_conyuge` está vacío, se elimina el registro del cónyuge (si existía).
4. **Fecha automática:** `fecha_registro` se convierte automáticamente de timestamp de HubSpot a formato fecha.

---

## 📊 Resumen

- **Total de campos del Titular:** 13 campos
- **Total de campos del Cónyuge:** 9 campos
- **Total de campos de Propiedad:** 9 campos (no se guardan en `clients`)
- **Total de campos Financieros:** 4 campos (no se guardan en `clients`)

**Total sincronizado a tabla `clients`:** 22 campos + 9 campos de cónyuge = **31 campos**
