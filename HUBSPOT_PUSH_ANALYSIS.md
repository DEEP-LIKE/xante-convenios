# Análisis de Capacidad: Envío de Datos a HubSpot (Push)

Este documento analiza la factibilidad técnica de implementar el "Paso 5": enviar datos desde la plataforma local hacia HubSpot para actualizar Deals y Contactos.

## 1. Infraestructura Existente (Lo que ya tenemos)

✅ **Métodos de Conexión:**
El servicio `HubspotSyncService` ya cuenta con los métodos necesarios para escribir en HubSpot:
*   `updateHubspotContact($hubspotId, $properties)`: Para actualizar contactos.
*   `updateHubspotDeal($dealId, $properties)`: Para actualizar deals.

✅ **Mapa de Propiedades:**
El archivo de configuración `config/hubspot.php` ya contiene la lista completa de propiedades internas de HubSpot (`dealname`, `nombre_completo`, `curp`, etc.) que necesitamos enviar.

## 2. Piezas Faltantes (Lo que necesitamos construir)

Aunque tenemos el "transporte" (los métodos de update), nos falta el "traductor" (el mapeo inverso).

❌ **Mapeo Inverso (Reverse Mapping):**
Actualmente el sistema solo sabe traducir de *HubSpot -> Local*. Necesitamos crear la lógica para traducir de *Local -> HubSpot*.

**Falta implementar:**
1.  `mapClientToHubspotDeal(Client $client)`: Que tome un cliente y su cónyuge y genere el array de propiedades para el Deal.
2.  `mapClientToHubspotContact(Client $client)`: Que tome un cliente y genere el array para el Contacto.

## 3. Estrategia de Implementación Recomendada

Para realizar el "Paso 5" de manera segura, se recomienda:

1.  **Crear Métodos de Mapeo Inverso:**
    Agregar al `HubspotSyncService` funciones que conviertan los modelos `Client` y `Spouse` en el formato JSON que espera HubSpot.

2.  **Validación de Integridad:**
    Antes de enviar, verificar que el `hubspot_deal_id` exista en el cliente local.

3.  **Flujo de Actualización:**
    *   **Paso 5a:** Recopilar datos finales del Wizard.
    *   **Paso 5b:** Construir payload para el Deal (incluyendo datos del cónyuge y financieros).
    *   **Paso 5c:** Llamar a `updateHubspotDeal`.
    *   **Paso 5d:** (Opcional) Actualizar datos básicos del contacto con `updateHubspotContact`.

## 4. Conclusión

**SÍ es posible** realizar la actualización desde la plataforma hacia HubSpot con lo que tenemos actualmente. Solo se requiere agregar la lógica de transformación de datos (mapeo inverso) en el servicio existente. No se requieren nuevas librerías ni cambios en la API.
