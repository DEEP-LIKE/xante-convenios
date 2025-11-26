# Implementación de Sincronización Bidireccional (Push a HubSpot)

Se ha implementado con éxito la capacidad de enviar actualizaciones desde la plataforma local hacia HubSpot (Paso 5), completando el ciclo de sincronización bidireccional.

## 🚀 Funcionalidades Implementadas

### 1. Método `pushClientToHubspot`
Se agregó este método al servicio `HubspotSyncService`. Permite tomar un cliente local y actualizar sus datos correspondientes en HubSpot.

*   **Actualización de Deal:** Prioritaria. Actualiza nombre del titular, dirección y otros datos del convenio.
*   **Actualización de Contacto:** Secundaria. Actualiza email, teléfono y datos básicos del perfil.

### 2. Corrección de Mapeo de Propiedades
Durante la implementación, descubrimos que los nombres de las propiedades en HubSpot eran diferentes a los configurados. Se ajustó el código para usar los nombres reales del portal:

| Dato Local | Propiedad Configurada (Anterior) | Propiedad Real (Implementada) |
| :--- | :--- | :--- |
| Nombre Titular | `nombre_completo` | `nombre_del_titular` |
| Calle/Dirección | `domicilio_actual` | `calle_o_privada_` |
| Colonia | `colonia` | `colonia` (Sin cambios) |
| Estado | `estado` | `estado` (Sin cambios) |

> ⚠️ **Nota:** Propiedades como CURP, RFC y datos del cónyuge se han deshabilitado temporalmente en el envío porque no existen como campos de texto simple en el objeto Deal de este portal específico.

## 🧪 Verificación

Se realizaron las siguientes pruebas exitosas:

1.  **Sincronización Inicial:** Se ejecutó `php artisan hubspot:suite test --sync` para poblar los `hubspot_deal_id` en la base de datos local (98 clientes actualizados).
2.  **Prueba de Push:** Se ejecutó el script `scripts/test-hubspot-push.php` con el cliente `miguel.alfaro@carbono.mx`.
    *   **Resultado:**
        ```
        Deal Actualizado: ✅ SI
        Contact Actualizado: ✅ SI
        ```

## 📝 Cómo usarlo en el Código

Para actualizar HubSpot desde cualquier parte de la aplicación (ej. al finalizar el Wizard):

```php
use App\Services\HubspotSyncService;

// ... dentro de tu controlador o Livewire component
$service = new HubspotSyncService();
$result = $service->pushClientToHubspot($client);

if ($result['deal_updated']) {
    // Éxito
}
```

## 🔄 Integración en el Wizard (Paso 5)

Se ha actualizado la acción `SyncClientToHubspotAction` que es invocada por el Wizard en el último paso.

**Flujo Final:**
1.  **Usuario:** Clic en "Validar y Generar Documentos".
2.  **Sistema:** Muestra "Sincronizando información con HubSpot...".
3.  **Acción:**
    *   Guarda los datos del formulario en la BD local (`UpdateClientFromWizardAction`).
    *   Ejecuta el Push a HubSpot (`pushClientToHubspot`).
4.  **Sistema:** Muestra "Generando documentos PDF...".
5.  **Sistema:** Redirige al siguiente paso (Wizard 2).

## ⚠️ Manejo de Errores

**Si HubSpot falla durante la sincronización:**
1.  El proceso **se detiene** inmediatamente.
2.  Se muestra una notificación persistente: *"No se pudo sincronizar la información con HubSpot. Por favor, intenta nuevamente en unos momentos. Tus datos han sido guardados."*
3.  **NO se generan PDFs** ni se avanza al siguiente wizard.
4.  Los datos del formulario **ya están guardados** en la base de datos local.
5.  El usuario puede volver a intentar haciendo clic nuevamente en "Validar y Generar Documentos".

> ✅ **Ventaja:** El usuario no pierde su trabajo. Todos los datos capturados están seguros en la BD local y puede reintentar cuando HubSpot esté disponible.

## 🔗 Preselección de Cliente desde Tabla

Se ha implementado la funcionalidad de preselección de clientes desde la tabla de clientes.

**Flujo:**
1.  **Usuario:** Hace clic en "Sin Convenio" en la tabla de clientes.
2.  **Sistema:** Redirige al Wizard (Paso 1) con el parámetro `?client_id={xante_id}`.
3.  **Wizard:** Detecta el parámetro y precarga automáticamente:
    *   **Paso 2 (Cliente):** Datos del titular y cónyuge.
    *   **Paso 3 (Propiedad):** Datos de la vivienda (Lote, Manzana, Prototipo, etc.) sincronizados desde HubSpot.
    *   **Paso 4 (Calculadora):** Datos financieros (Valor convenio, Precios, etc.) sincronizados desde HubSpot.
4.  **Notificación:** Muestra "Cliente Preseleccionado - Los datos de {nombre} han sido precargados."
5.  **Usuario:** Puede revisar los datos y hacer clic en "Siguiente" para continuar.

> ✅ **Beneficio:** Ahorra tiempo al usuario al no tener que buscar y seleccionar manualmente el cliente en el selector. Además, **el 90% del formulario ya viene lleno** desde HubSpot.
