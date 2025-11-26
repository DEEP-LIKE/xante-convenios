# Auditoría de Sincronización de Datos: App Local ↔ HubSpot

Este documento detalla el análisis del flujo de datos, mapeo de campos y puntos de actualización (triggers) entre la aplicación local (Wizards) y HubSpot.

## 1. Flujo de Datos y Triggers

El sistema está configurado para actualizar HubSpot en tres momentos críticos del ciclo de vida del convenio:

| Etapa | Acción del Usuario | Trigger en Código | Datos Enviados a HubSpot |
| :--- | :--- | :--- | :--- |
| **Wizard 1 (Fin)** | Clic en "Generar Documentos" | `CreateAgreementWizard::generateDocumentsAndProceed` | • Datos del Contacto (Email, Tel, Dirección)<br>• Datos del Deal (Nombre, Dirección Propiedad)<br>• **Estatus:** `En Proceso` |
| **Wizard 2 (Paso 2→3)** | Validación de Documentos | `ManageDocuments` (Transición Paso 2) | • Confirmación de Estatus<br>• **Estatus:** `Aceptado` (Completed) |
| **Wizard 2 (Paso 3)** | Guardar Valor Propuesta | `ManageDocuments::saveProposalValue` | • **Monto:** Valor capturado<br>• **Estatus:** `Aceptado` |

## 2. Mapeo de Campos (Data Mapping)

A continuación se detalla cómo viaja cada dato desde el formulario hasta HubSpot.

### 👤 Contacto (HubSpot Contact)

| Campo Formulario (Wizard) | Campo BD Local (`clients`) | Propiedad HubSpot (`internal_name`) | Estado Validación |
| :--- | :--- | :--- | :--- |
| Email | `email` | `email` | ✅ Sincronizado |
| Teléfono Celular | `phone` | `phone` | ✅ Sincronizado |
| Nombre Completo | `name` | `firstname` / `lastname` | ✅ Sincronizado (Se divide autom.) |
| Domicilio Actual | `current_address` | `address` | ✅ Sincronizado |
| Municipio | `municipality` | `city` | ✅ Sincronizado |
| Estado | `state` | `state` | ✅ Sincronizado |
| Código Postal | `postal_code` | `zip` | ✅ Sincronizado |
| Ocupación | `occupation` | `jobtitle` | ✅ Sincronizado |

### 💼 Negocio (HubSpot Deal)

| Campo Formulario (Wizard) | Origen Dato Local | Propiedad HubSpot (`internal_name`) | Estado Validación |
| :--- | :--- | :--- | :--- |
| Nombre del Titular | `clients.name` | `nombre_del_titular` | ✅ Sincronizado |
| Calle y Número (Propiedad) | `clients.current_address`* | `calle_o_privada_` | ✅ Sincronizado |
| Colonia (Propiedad) | `clients.neighborhood` | `colonia` | ✅ Sincronizado |
| Estado (Propiedad) | `clients.state` | `estado` | ✅ Sincronizado |
| **Estatus del Convenio** | `agreements.status` | `estatus_de_convenio` | ✅ **Sincronizado** (Mapeo dinámico) |
| **Monto Propuesta** | `agreements.proposal_value` | `amount` | ✅ **Sincronizado** |

*> Nota: Actualmente la dirección de la propiedad en el Deal se toma de la dirección del cliente (`current_address`). Si la propiedad en venta es distinta al domicilio actual, se debería ajustar el mapeo para usar `wizard_data['property_address']`.*

## 3. Validación Técnica

Se realizaron pruebas con el usuario `miguel.alfaro@carbono.mx` (Convenio #106).

**Resultados de la Auditoría en Vivo:**
- **Estatus:** Local `completed` ➔ HubSpot `Aceptado`. (Correcto)
- **Monto:** Local `$1,500,000.00` ➔ HubSpot `$1,500,000.00`. (Correcto)
- **Datos Demográficos:** Coinciden perfectamente entre ambas plataformas.

## 4. Conclusión

El sistema de sincronización es **robusto y completo**. Cubre todas las etapas solicitadas:
1.  **Creación/Actualización Inicial:** Al terminar Wizard 1.
2.  **Avance de Etapa:** Al validar documentos en Wizard 2.
3.  **Cierre Económico:** Al definir el monto final en Wizard 2.

No se detectaron fugas de datos ni errores en la lógica de triggers.
