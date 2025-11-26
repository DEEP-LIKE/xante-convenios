# Análisis: Datos del Wizard 1 vs Sincronización HubSpot

## 📊 Resumen Ejecutivo

El **Wizard 1** solo captura el `client_id` (selección del cliente). **TODOS los demás datos** se obtienen de la sincronización con HubSpot cuando se selecciona el cliente.

---

## 🔍 Datos que SE OBTIENEN de la Sincronización HubSpot

### ✅ Información Básica del Cliente (Contact)

Estos datos vienen del **Contact** en HubSpot y se almacenan en la tabla `clients`:

| Campo en BD Local | Campo en HubSpot | Fuente | Notas |
|-------------------|------------------|--------|-------|
| `xante_id` | `xante_id` / `xante_client_id` / `id_xante` / `client_xante_id` | Contact | **OBLIGATORIO** para sincronizar |
| `hubspot_id` | `hs_object_id` | Contact | ID del contacto en HubSpot |
| `name` | `firstname` + `lastname` | Contact | Nombre completo concatenado |
| `email` | `email` | Contact | Email del contacto |
| `phone` | `phone` | Contact | Teléfono principal |
| `current_address` | `address` | Contact | Dirección actual |
| `municipality` | `city` | Contact | Ciudad/Municipio |
| `state` | `state` | Contact | Estado |
| `postal_code` | `zip` | Contact | Código postal |
| `neighborhood` | `colonia` | Contact | Colonia |
| `birthdate` | `date_of_birth` | Contact | Fecha de nacimiento |
| `occupation` | `jobtitle` | Contact | Ocupación/Puesto |
| `fecha_registro` | `createdate` (del Deal) | Deal | Fecha de creación del deal |
| `hubspot_synced_at` | - | Sistema | Timestamp de última sincronización |

### ⚠️ Limitaciones de la Sincronización Actual

**La sincronización SOLO trae datos básicos del Contact**, NO trae:
- Datos del titular completos (RFC, CURP, estado civil, etc.)
- Datos del cónyuge
- Datos de la propiedad
- Datos financieros

---

## ❌ Datos que NO se obtienen de la Sincronización

Estos datos **NO están** en la sincronización actual y deben venir del **Deal** en HubSpot:

### 1. **Datos Completos del Titular**
- `holder_name` → `nombre_completo` (Deal)
- `holder_email` → `email` (Deal)
- `holder_phone` → `phone` (Deal)
- `holder_office_phone` → `telefono_oficina` (Deal)
- `holder_curp` → `curp` (Deal)
- `holder_rfc` → `rfc` (Deal)
- `holder_civil_status` → `estado_civil` (Deal)
- `holder_occupation` → `ocupacion` (Deal)

### 2. **Domicilio del Titular**
- `current_address` → `domicilio_actual` (Deal)
- `holder_house_number` → `numero_casa` (Deal)
- `neighborhood` → `colonia` (Deal)
- `postal_code` → `codigo_postal` (Deal)
- `municipality` → `municipio` (Deal)
- `state` → `estado` (Deal)

### 3. **Datos del Cónyuge**
- `spouse_name` → `nombre_completo_conyuge` (Deal)
- `spouse_email` → `email_conyuge` (Deal)
- `spouse_phone` → `telefono_movil_conyuge` (Deal)
- `spouse_curp` → `curp_conyuge` (Deal)

### 4. **Domicilio del Cónyuge**
- `spouse_current_address` → `domicilio_actual_conyuge` (Deal)
- `spouse_house_number` → `numero_casa_conyuge` (Deal)
- `spouse_neighborhood` → `colonia_conyuge` (Deal)
- `spouse_postal_code` → `codigo_postal_conyuge` (Deal)
- `spouse_municipality` → `municipio_conyuge` (Deal)
- `spouse_state` → `estado_conyuge` (Deal)

### 5. **Datos de la Propiedad**
- `domicilio_convenio` → `domicilio_convenio` (Deal)
- `comunidad` → `comunidad` (Deal)
- `tipo_vivienda` → `tipo_vivienda` (Deal)
- `prototipo` → `prototipo` (Deal)
- `lote` → `lote` (Deal)
- `manzana` → `manzana` (Deal)
- `etapa` → `etapa` (Deal)
- `municipio_propiedad` → `municipio_propiedad` (Deal)
- `estado_propiedad` → `estado_propiedad` (Deal)

### 6. **Datos Financieros**
- `valor_convenio` → `valor_convenio` (Deal)
- `precio_promocion` → `precio_promocion` (Deal)
- `comision_total_pagar` → `comision_total_pagar` (Deal)
- `ganancia_final` → `ganancia_final` (Deal)

---

## 🔄 Flujo Actual del Wizard 1

```
1. Usuario selecciona cliente (client_id)
   ↓
2. Se ejecuta preloadClientData()
   ↓
3. PreloadClientDataAction consulta:
   - Tabla `clients` (datos del titular)
   - Tabla `spouses` (datos del cónyuge)
   ↓
4. Se cargan datos en el wizard
   ↓
5. Usuario puede ver/editar los datos
```

**IMPORTANTE:** `PreloadClientDataAction` NO consulta HubSpot directamente. Solo carga datos que YA están en la BD local (sincronizados previamente).

---

## 📋 Código: PreloadClientDataAction

```php
// app/Actions/Agreements/PreloadClientDataAction.php

public function execute(int $clientId, callable $set): void
{
    // 1. Obtener cliente de BD local (con relación spouse)
    $client = Client::with('spouse')->find($clientId);
    
    // 2. Cargar datos del titular (de tabla clients)
    $set('holder_name', $client->name);
    $set('holder_email', $client->email);
    $set('holder_phone', $client->phone);
    $set('holder_curp', $client->curp);
    $set('holder_rfc', $client->rfc);
    $set('holder_civil_status', $client->civil_status);
    $set('holder_occupation', $client->occupation);
    $set('holder_current_address', $client->current_address);
    // ... más campos
    
    // 3. Cargar datos del cónyuge (de tabla spouses)
    $spouse = $client->spouse;
    $set('spouse_name', $spouse?->name);
    $set('spouse_email', $spouse?->email);
    $set('spouse_phone', $spouse?->phone);
    $set('spouse_curp', $spouse?->curp);
    // ... más campos
}
```

---

## 💡 Conclusión Actualizada

### ✅ Lo que SÍ viene de la sincronización HubSpot → BD Local:

**Actualmente, la sincronización SOLO trae:**
- `xante_id` (OBLIGATORIO)
- `hubspot_id`
- `name` (firstname + lastname)
- `email`
- `phone`
- `current_address`
- `municipality`
- `state`
- `postal_code`
- `neighborhood`
- `birthdate`
- `occupation`
- `fecha_registro`

### ❌ Lo que NO viene de la sincronización actual:

**Estos campos están en la tabla `clients` pero NO se sincronizan desde HubSpot:**
- `curp`
- `rfc`
- `civil_status`
- `regime_type`
- `office_phone`
- `additional_contact_phone`
- `delivery_file`
- Y TODOS los datos del cónyuge (tabla `spouses`)
- Y TODOS los datos de la propiedad
- Y TODOS los datos financieros

### 🔍 Dónde están estos datos entonces:

1. **Opción 1:** Se capturan manualmente en el wizard
2. **Opción 2:** Ya existen en la BD de una captura anterior
3. **Opción 3:** Deberían venir de HubSpot pero no están configurados en la sincronización

---

## 🎯 Problema Identificado

**La sincronización actual es MUY LIMITADA:**

```php
// HubspotSyncService::getContactFromDeal()
// Solo obtiene estos campos del Contact:
$contactResponse = Http::get($contactUrl, [
    'properties' => implode(',', array_merge(
        ['firstname', 'lastname', 'email', 'phone'],  // ← Solo 4 campos básicos
        $this->config['mapping']['custom_properties']  // ← xante_id
    ))
]);
```

**NO se están consultando:**
- Propiedades del Deal (donde están TODOS los datos completos)
- Campos adicionales del Contact (CURP, RFC, etc.)

---

## 🚀 Recomendación

Para tener datos completos en el wizard sin capturarlos manualmente:

### 1. **Expandir la sincronización** para incluir propiedades del Deal:

```php
// En HubspotSyncService::processDeal()
// Después de obtener el Contact, también guardar datos del Deal:

$dealData = [
    'holder_curp' => $properties['curp'] ?? null,
    'holder_rfc' => $properties['rfc'] ?? null,
    'holder_civil_status' => $properties['estado_civil'] ?? null,
    'spouse_name' => $properties['nombre_completo_conyuge'] ?? null,
    'spouse_curp' => $properties['curp_conyuge'] ?? null,
    // ... todos los campos del deal
];

// Guardar en tabla clients o en una nueva tabla deal_data
```

### 2. **Crear tabla `deal_data`** para almacenar información del Deal:

```php
Schema::create('deal_data', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained();
    $table->string('hubspot_deal_id')->unique();
    $table->json('titular_data');      // Datos completos del titular
    $table->json('spouse_data');       // Datos del cónyuge
    $table->json('property_data');     // Datos de la propiedad
    $table->json('financial_data');    // Datos financieros
    $table->timestamp('synced_at');
});
```

### 3. **Actualizar PreloadClientDataAction** para usar deal_data:

```php
public function execute(int $clientId, callable $set): void
{
    $client = Client::with(['spouse', 'dealData'])->find($clientId);
    
    // Cargar datos del deal si existen
    if ($client->dealData) {
        $titular = $client->dealData->titular_data;
        $set('holder_curp', $titular['curp']);
        $set('holder_rfc', $titular['rfc']);
        // ... etc
    }
}
```

---

## 📊 Resumen Visual

```
┌─────────────────────────────────────────────────────────────┐
│                    HUBSPOT (Deal)                            │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ • Titular completo (CURP, RFC, estado civil, etc.)   │   │
│  │ • Cónyuge completo                                   │   │
│  │ • Propiedad                                          │   │
│  │ • Datos financieros                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                          ↓                                   │
│                   SINCRONIZACIÓN                             │
│                    (LIMITADA)                                │
│                          ↓                                   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Solo: name, email, phone, address básica             │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                  BD LOCAL (clients)                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ✅ xante_id, name, email, phone                      │   │
│  │ ❌ curp, rfc, civil_status (VACÍOS)                  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              WIZARD 1 (PreloadClientDataAction)              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Carga datos de BD local                              │   │
│  │ ⚠️  Muchos campos vienen VACÍOS                      │   │
│  │ → Usuario debe capturarlos manualmente               │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

