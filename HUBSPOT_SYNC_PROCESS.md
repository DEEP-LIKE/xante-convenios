# Sincronización con HubSpot - Documentación Técnica

## 📋 Resumen

La sincronización con HubSpot funciona **desde Deals hacia Contacts**, creando o actualizando clientes en la base de datos local basándose en deals que cumplen ciertos criterios.

---

## 🔄 Flujo de Sincronización

### 1. **Punto de Entrada**

La sincronización se ejecuta de las siguientes maneras:

#### A. **Comando Artisan (Manual)**
```bash
php artisan hubspot:suite
```

#### B. **Cron Job (Automático)**
Configurado en `app/Console/Kernel.php`:
```php
$schedule->command('hubspot:suite')->hourly();
```

#### C. **Llamada Programática**
```php
$syncService = app(HubspotSyncService::class);
$stats = $syncService->syncClients();
```

---

## ✅ Condiciones para la Sincronización

Para que un **Deal** de HubSpot se sincronice a la BD local, debe cumplir **TODAS** estas condiciones:

### 1. **Estado del Deal: "Aceptado"**
```php
// Configurado en config/hubspot.php
'deal_sync' => [
    'status_field' => 'estatus_de_convenio',
    'accepted_value' => 'Aceptado',
]
```

**Filtro aplicado:**
```php
'filters' => [
    'deal_accepted' => [
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'estatus_de_convenio',
                        'operator' => 'EQ',
                        'value' => 'Aceptado'
                    ]
                ]
            ]
        ]
    ]
]
```

### 2. **Tiene Contacto Asociado**
```php
$numContacts = (int)($properties['num_associated_contacts'] ?? 0);
if ($numContacts === 0) {
    return 'skipped'; // ❌ Se omite
}
```

### 3. **El Contacto tiene `xante_id` válido**
```php
$xanteId = $this->extractXanteId($contactProps);
if (!$xanteId) {
    return 'skipped'; // ❌ Se omite
}
```

El sistema busca el `xante_id` en estas propiedades del contacto:
- `xante_id`
- `xante_client_id`
- `id_xante`
- `client_xante_id`

---

## 🔍 Proceso Detallado

### Paso 1: Obtener Deals de HubSpot

```php
// HubspotSyncService::fetchDeals()
POST https://api.hubapi.com/crm/v3/objects/deals/search

Payload:
{
    "filterGroups": [
        {
            "filters": [
                {
                    "propertyName": "estatus_de_convenio",
                    "operator": "EQ",
                    "value": "Aceptado"
                }
            ]
        }
    ],
    "properties": [
        "dealname",
        "amount",
        "estatus_de_convenio",
        "num_associated_contacts",
        "createdate",
        ...
    ],
    "limit": 100
}
```

### Paso 2: Procesar Cada Deal

```php
// HubspotSyncService::processDeal()

foreach ($deals as $deal) {
    // 1. Validar estatus
    if ($estatus !== 'Aceptado') {
        continue; // ⏭️ Saltar
    }
    
    // 2. Verificar contactos asociados
    if ($numContacts === 0) {
        continue; // ⏭️ Saltar
    }
    
    // 3. Obtener contacto del deal
    $contact = $this->getContactFromDeal($dealId);
    
    // 4. Validar xante_id
    $xanteId = $this->extractXanteId($contactProps);
    if (!$xanteId) {
        continue; // ⏭️ Saltar
    }
    
    // 5. Crear o actualizar cliente
    $existingClient = Client::where('xante_id', $xanteId)
        ->orWhere('hubspot_id', $contactId)
        ->first();
        
    if ($existingClient) {
        $this->updateExistingClient(...); // ✅ Actualizar
    } else {
        $this->createNewClient(...); // ✅ Crear
    }
}
```

### Paso 3: Obtener Contacto del Deal

```php
// HubspotSyncService::getContactFromDeal()

GET https://api.hubapi.com/crm/v4/objects/deals/{dealId}/associations/contacts

// Obtener el primer contacto asociado
$contactId = $response['results'][0]['toObjectId'];

// Obtener datos completos del contacto
GET https://api.hubapi.com/crm/v3/objects/contacts/{contactId}
```

### Paso 4: Crear o Actualizar Cliente

#### A. **Crear Nuevo Cliente**
```php
Client::create([
    'xante_id' => $xanteId,
    'hubspot_id' => $contactId,
    'hubspot_deal_id' => $dealId,
    'name' => $contactProps['firstname'] ?? 'Sin nombre',
    'email' => $contactProps['email'] ?? null,
    'phone' => $contactProps['phone'] ?? null,
    'fecha_registro' => $dealCreatedAt,
    'last_synced_at' => now(),
]);
```

#### B. **Actualizar Cliente Existente**
```php
$existingClient->update([
    'hubspot_id' => $contactId,
    'hubspot_deal_id' => $dealId,
    'name' => $contactProps['firstname'] ?? $existingClient->name,
    'email' => $contactProps['email'] ?? $existingClient->email,
    'phone' => $contactProps['phone'] ?? $existingClient->phone,
    'last_synced_at' => now(),
]);
```

---

## 📊 Estadísticas de Sincronización

Cada sincronización retorna estadísticas:

```php
[
    'total_deals' => 150,           // Total de deals procesados
    'new_clients' => 10,            // Clientes nuevos creados
    'updated_clients' => 50,        // Clientes actualizados
    'skipped' => 90,                // Deals omitidos (no cumplen criterios)
    'errors' => 0,                  // Errores encontrados
    'processed_pages' => 2,         // Páginas procesadas
    'time_limited' => false,        // ¿Se detuvo por tiempo?
    'max_pages_reached' => false,   // ¿Se alcanzó límite de páginas?
]
```

---

## 🎯 Casos de Uso

### Caso 1: Deal Nuevo con Estado "Aceptado"

```
Deal en HubSpot:
├─ estatus_de_convenio: "Aceptado" ✅
├─ num_associated_contacts: 1 ✅
└─ Contacto asociado:
   └─ xante_id: "XNT-001" ✅

Resultado: ✅ Cliente CREADO en BD local
```

### Caso 2: Deal sin Contacto Asociado

```
Deal en HubSpot:
├─ estatus_de_convenio: "Aceptado" ✅
└─ num_associated_contacts: 0 ❌

Resultado: ⏭️ Deal OMITIDO (skipped)
```

### Caso 3: Contacto sin xante_id

```
Deal en HubSpot:
├─ estatus_de_convenio: "Aceptado" ✅
├─ num_associated_contacts: 1 ✅
└─ Contacto asociado:
   └─ xante_id: null ❌

Resultado: ⏭️ Deal OMITIDO (skipped)
```

### Caso 4: Deal con Estado Diferente

```
Deal en HubSpot:
├─ estatus_de_convenio: "En Proceso" ❌
├─ num_associated_contacts: 1 ✅
└─ Contacto asociado:
   └─ xante_id: "XNT-001" ✅

Resultado: ⏭️ Deal OMITIDO (skipped)
```

---

## 🔧 Configuración

### Archivo: `config/hubspot.php`

```php
'deal_sync' => [
    'status_field' => 'estatus_de_convenio',    // Campo de estado
    'accepted_value' => 'Aceptado',             // Valor requerido
    'properties' => [                            // Propiedades a obtener
        'dealname',
        'amount',
        'estatus_de_convenio',
        'num_associated_contacts',
        'createdate',
        ...
    ],
],

'sync' => [
    'batch_size' => 100,        // Deals por página
    'timeout' => 30,            // Timeout en segundos
    'retry_attempts' => 3,      // Intentos de reintento
    'retry_delay' => 2,         // Delay entre reintentos
],
```

---

## 🚀 Modos de Sincronización

### 1. **Sincronización Completa**
```php
$syncService->syncClients(); // Sin límites
```

### 2. **Sincronización Rápida**
```php
$syncService->syncClientsQuick(); // Max 10 páginas, 30 segundos
```

### 3. **Sincronización por Lotes**
```php
$syncService->syncClientsBatch(5); // Max 5 páginas, 40 segundos
```

### 4. **Sincronización Personalizada**
```php
$syncService->syncClients(
    maxPages: 20,      // Máximo 20 páginas
    timeLimit: 60      // Máximo 60 segundos
);
```

---

## 📝 Logs

Todos los eventos se registran en `storage/logs/laravel.log`:

```
[INFO] Iniciando sincronización de clientes desde Deals HubSpot
[INFO] Deal 12345 sin contactos asociados - OMITIDO
[INFO] Contact del Deal 67890 sin xante_id válido - OMITIDO
[INFO] Cliente creado desde Deal 11111 {"xante_id":"XNT-001"}
[INFO] Cliente actualizado desde Deal 22222 {"xante_id":"XNT-002"}
[INFO] Sincronización completada {"total_deals":150,"new_clients":10,...}
```

---

## ⚠️ Puntos Importantes

1. **El `xante_id` es OBLIGATORIO**: Sin él, el contacto NO se sincroniza
2. **Solo deals "Aceptado"**: Otros estados se ignoran
3. **Debe tener contacto asociado**: Deals sin contacto se omiten
4. **Sincronización unidireccional**: HubSpot → BD Local (no al revés en este proceso)
5. **Rate limiting**: 100ms de delay entre requests para no exceder límites de HubSpot

---

## 🔄 Actualización desde el Wizard

Cuando se usa el wizard para actualizar información:

1. El wizard guarda datos en la BD local
2. Se ejecuta `SyncClientToHubspotAction` (si está configurado)
3. Los datos se envían de vuelta a HubSpot
4. La próxima sincronización traerá los datos actualizados

---

## 📞 Comandos Útiles

```bash
# Sincronización manual
php artisan hubspot:suite

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar caché
php artisan cache:clear
```
