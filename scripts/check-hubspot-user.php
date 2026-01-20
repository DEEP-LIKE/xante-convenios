<?php

/**
 * Script para comparar información entre BD Local y HubSpot
 *
 * Uso:
 * php scripts/check-hubspot-user.php
 *
 * Este script compara:
 * 1. Datos del cliente en BD local
 * 2. Datos del deal en HubSpot
 * 3. Muestra si están sincronizados
 */

require __DIR__.'/../vendor/autoload.php';

use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Support\Facades\Http;

// Cargar la aplicación Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Email del usuario a consultar
$userEmail = 'miguel.alfaro@carbono.mx';

// Configuración de HubSpot
$token = config('hubspot.token');
$baseUrl = config('hubspot.api_base_url');

echo "\n";
echo "===============================================================\n";
echo "  COMPARACION: BD LOCAL vs HUBSPOT\n";
echo "===============================================================\n";
echo "Usuario: {$userEmail}\n";
echo 'Fecha: '.now()->format('Y-m-d H:i:s')."\n";
echo "===============================================================\n\n";

try {
    // 1. Obtener datos de BD Local
    echo "[PASO 1/3] Consultando BD Local...\n";
    $client = Client::with('spouse')->where('email', $userEmail)->first();

    // 2. Obtener datos de HubSpot
    echo "[PASO 2/3] Consultando HubSpot...\n";

    $hubspotDeal = null;
    $hubspotProps = [];

    // Si el cliente tiene hubspot_deal_id, buscar directamente ese deal
    if ($client && $client->hubspot_deal_id) {
        echo "[DEBUG] Cliente tiene hubspot_deal_id: {$client->hubspot_deal_id}\n";
        echo "[DEBUG] Consultando deal directamente...\n";

        $dealUrl = $baseUrl."/crm/v3/objects/deals/{$client->hubspot_deal_id}";
        $dealResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->get($dealUrl, [
            'properties' => implode(',', config('hubspot.deal_sync.properties')),
        ]);

        if ($dealResponse->successful()) {
            $hubspotDeal = $dealResponse->json();
            $hubspotProps = $hubspotDeal['properties'];
            echo "[DEBUG] Deal encontrado directamente\n";
        } else {
            echo '[DEBUG] Error al obtener deal: '.$dealResponse->status()."\n";
        }
    } else {
        // Si no tiene deal_id, buscar como lo hace HubspotSyncService
        echo "[DEBUG] Cliente NO tiene hubspot_deal_id, buscando deals 'Aceptado'...\n";

        $searchUrl = $baseUrl.config('hubspot.endpoints.deals_search');
        $searchPayload = [
            'filterGroups' => config('hubspot.filters.deal_accepted.filterGroups'),
            'properties' => config('hubspot.deal_sync.properties'),
            'limit' => 100, // Buscar más deals para encontrar el correcto
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->post($searchUrl, $searchPayload);

        echo '[DEBUG] Response Status: '.$response->status()."\n";
        echo "[DEBUG] Total Deals 'Aceptado': ".($response->json('total') ?? 0)."\n";

        if ($response->successful() && $response->json('total') > 0) {
            $deals = $response->json('results');
            echo "[DEBUG] Buscando deal que corresponda al email: {$userEmail}\n";

            // Buscar el deal que tenga el email correcto en sus propiedades
            foreach ($deals as $deal) {
                $props = $deal['properties'];

                // Verificar si el email del deal coincide
                if (isset($props['email']) && $props['email'] === $userEmail) {
                    $hubspotDeal = $deal;
                    $hubspotProps = $props;
                    echo "[DEBUG] Deal encontrado por email en propiedades\n";
                    echo "[DEBUG] Deal ID: {$deal['id']}\n";
                    break;
                }
            }

            // Si no se encontró por email en propiedades, obtener contacts asociados
            if (! $hubspotDeal) {
                echo "[DEBUG] No se encontró por email en propiedades, consultando contacts asociados...\n";

                foreach ($deals as $deal) {
                    $dealId = $deal['id'];

                    // Obtener asociaciones del deal
                    $assocUrl = $baseUrl."/crm/v4/objects/deals/{$dealId}/associations/contacts";
                    $assocResponse = Http::withHeaders([
                        'Authorization' => "Bearer {$token}",
                    ])->get($assocUrl);

                    if ($assocResponse->successful() && count($assocResponse->json('results', [])) > 0) {
                        $contactId = $assocResponse->json('results')[0]['toObjectId'];

                        // Obtener email del contact
                        $contactUrl = $baseUrl."/crm/v3/objects/contacts/{$contactId}";
                        $contactResponse = Http::withHeaders([
                            'Authorization' => "Bearer {$token}",
                        ])->get($contactUrl, [
                            'properties' => 'email',
                        ]);

                        if ($contactResponse->successful()) {
                            $contactEmail = $contactResponse->json('properties.email');

                            if ($contactEmail === $userEmail) {
                                $hubspotDeal = $deal;
                                $hubspotProps = $deal['properties'];
                                echo "[DEBUG] Deal encontrado por email del contact asociado\n";
                                echo "[DEBUG] Deal ID: {$dealId}\n";
                                echo "[DEBUG] Contact ID: {$contactId}\n";
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    if ($hubspotDeal) {
        echo '[DEBUG] Deal ID: '.$hubspotDeal['id']."\n";
        echo '[DEBUG] Propiedades recibidas: '.count($hubspotProps)." campos\n";
        echo "[DEBUG] Propiedades clave:\n";
        echo '        - dealname: '.($hubspotProps['dealname'] ?? '[NO EXISTE]')."\n";
        echo '        - email: '.($hubspotProps['email'] ?? '[NO EXISTE]')."\n";
        echo '        - nombre_completo: '.($hubspotProps['nombre_completo'] ?? '[NO EXISTE]')."\n";
        echo '        - curp: '.($hubspotProps['curp'] ?? '[NO EXISTE]')."\n";
        echo '        - rfc: '.($hubspotProps['rfc'] ?? '[NO EXISTE]')."\n";
    } else {
        echo "[DEBUG] No se encontró deal que corresponda al usuario\n";
    }
    echo "\n";

    // 3. Comparar y mostrar resultados
    echo "[PASO 3/3] Comparando datos...\n\n";
    echo "===============================================================\n";
    echo "  RESULTADOS DE LA COMPARACION\n";
    echo "===============================================================\n\n";

    // Función helper para comparar valores
    $compare = function ($localValue, $hubspotValue, $label) {
        $local = $localValue ?: '[VACIO]';
        $hubspot = $hubspotValue ?: '[VACIO]';
        $synced = ($localValue == $hubspotValue) ? 'SI' : 'NO';

        printf("%-30s | %-30s | %-30s | %s\n", $label, substr($local, 0, 30), substr($hubspot, 0, 30), $synced);

        return $localValue == $hubspotValue;
    };

    // Encabezado de tabla
    echo str_repeat('=', 125)."\n";
    printf("%-30s | %-30s | %-30s | %s\n", 'CAMPO', 'BD LOCAL', 'HUBSPOT', 'SYNC');
    echo str_repeat('=', 125)."\n";

    $allSynced = true;

    // IDs y Metadata
    echo "\n[IDs Y METADATA]\n";
    echo str_repeat('-', 125)."\n";
    if ($client) {
        $allSynced &= $compare($client->hubspot_deal_id, $hubspotDeal['id'] ?? null, 'HubSpot Deal ID');
        $allSynced &= $compare($client->hubspot_synced_at?->format('Y-m-d H:i:s'),
            isset($hubspotProps['hs_lastmodifieddate']) ? date('Y-m-d H:i:s', $hubspotProps['hs_lastmodifieddate'] / 1000) : null,
            'Ultima Modificacion');
    } else {
        echo "Cliente NO existe en BD local\n";
    }

    // Datos del Titular
    echo "\n[DATOS DEL TITULAR]\n";
    echo str_repeat('-', 125)."\n";
    if ($client) {
        $allSynced &= $compare($client->name, $hubspotProps['nombre_completo'] ?? null, 'Nombre Completo');
        $allSynced &= $compare($client->email, $hubspotProps['email'] ?? null, 'Email');
        $allSynced &= $compare($client->phone, $hubspotProps['mobilephone'] ?? $hubspotProps['phone'] ?? null, 'Telefono Movil');
        $allSynced &= $compare($client->office_phone, $hubspotProps['telefono_oficina'] ?? null, 'Telefono Oficina');
        $allSynced &= $compare($client->curp, $hubspotProps['curp'] ?? null, 'CURP');
        $allSynced &= $compare($client->rfc, $hubspotProps['rfc'] ?? null, 'RFC');
        $allSynced &= $compare($client->civil_status, $hubspotProps['estado_civil'] ?? null, 'Estado Civil');
        $allSynced &= $compare($client->occupation, $hubspotProps['ocupacion'] ?? null, 'Ocupacion');
    } else {
        echo "N/A - Cliente no existe en BD local\n";
    }

    // Domicilio del Titular
    echo "\n[DOMICILIO DEL TITULAR]\n";
    echo str_repeat('-', 125)."\n";
    if ($client) {
        $hubspotAddress = ($hubspotProps['domicilio_actual'] ?? '').(! empty($hubspotProps['numero_casa']) ? ' #'.$hubspotProps['numero_casa'] : '');
        $allSynced &= $compare($client->current_address, $hubspotAddress ?: null, 'Direccion');
        $allSynced &= $compare($client->neighborhood, $hubspotProps['colonia'] ?? null, 'Colonia');
        $allSynced &= $compare($client->postal_code, $hubspotProps['codigo_postal'] ?? null, 'Codigo Postal');
        $allSynced &= $compare($client->municipality, $hubspotProps['municipio'] ?? null, 'Municipio');
        $allSynced &= $compare($client->state, $hubspotProps['estado'] ?? null, 'Estado');
    } else {
        echo "N/A - Cliente no existe en BD local\n";
    }

    // Datos del Cónyuge
    echo "\n[DATOS DEL CONYUGE]\n";
    echo str_repeat('-', 125)."\n";
    if ($client && $client->spouse) {
        $spouse = $client->spouse;
        $allSynced &= $compare($spouse->name, $hubspotProps['nombre_completo_conyuge'] ?? null, 'Nombre Completo');
        $allSynced &= $compare($spouse->email, $hubspotProps['email_conyuge'] ?? null, 'Email');
        $allSynced &= $compare($spouse->phone, $hubspotProps['telefono_movil_conyuge'] ?? null, 'Telefono Movil');
        $allSynced &= $compare($spouse->curp, $hubspotProps['curp_conyuge'] ?? null, 'CURP');
    } elseif (! empty($hubspotProps['nombre_completo_conyuge'])) {
        echo "Conyuge existe en HubSpot pero NO en BD local\n";
        $allSynced = false;
    } else {
        echo "N/A - No hay conyuge en ninguno de los dos sistemas\n";
    }

    // Domicilio del Cónyuge
    if ($client && $client->spouse && $client->spouse->current_address) {
        echo "\n[DOMICILIO DEL CONYUGE]\n";
        echo str_repeat('-', 125)."\n";
        $spouse = $client->spouse;
        $hubspotSpouseAddress = ($hubspotProps['domicilio_actual_conyuge'] ?? '').(! empty($hubspotProps['numero_casa_conyuge']) ? ' #'.$hubspotProps['numero_casa_conyuge'] : '');
        $allSynced &= $compare($spouse->current_address, $hubspotSpouseAddress ?: null, 'Direccion');
        $allSynced &= $compare($spouse->neighborhood, $hubspotProps['colonia_conyuge'] ?? null, 'Colonia');
        $allSynced &= $compare($spouse->postal_code, $hubspotProps['codigo_postal_conyuge'] ?? null, 'Codigo Postal');
        $allSynced &= $compare($spouse->municipality, $hubspotProps['municipio_conyuge'] ?? null, 'Municipio');
        $allSynced &= $compare($spouse->state, $hubspotProps['estado_conyuge'] ?? null, 'Estado');
    }

    // Agreements
    if ($client) {
        $agreements = Agreement::where('client_id', $client->id)->get();
        echo "\n[AGREEMENTS EN BD LOCAL]\n";
        echo str_repeat('-', 125)."\n";
        if ($agreements->count() > 0) {
            foreach ($agreements as $agreement) {
                echo "   ID: {$agreement->id} | Estado: {$agreement->status} | Creado: {$agreement->created_at->format('Y-m-d H:i:s')}\n";
            }
        } else {
            echo "   No hay agreements asociados\n";
        }
    }

    // Resumen final
    echo "\n";
    echo str_repeat('=', 125)."\n";
    echo "  ESTADO DE SINCRONIZACION\n";
    echo str_repeat('=', 125)."\n";

    if (! $client) {
        echo "\n[X] CLIENTE NO EXISTE EN BD LOCAL\n";
        echo "    Ejecuta: php artisan hubspot:suite\n\n";
    } elseif (! $hubspotDeal) {
        echo "\n[X] DEAL NO ENCONTRADO EN HUBSPOT\n";
        echo "    Verifica que el deal tenga estado 'Aceptado'\n\n";
    } elseif ($allSynced) {
        echo "\n[OK] DATOS COMPLETAMENTE SINCRONIZADOS\n";
        echo "     BD Local y HubSpot tienen la misma informacion\n\n";
    } else {
        echo "\n[!] DATOS DESINCRONIZADOS\n";
        echo "    Hay diferencias entre BD Local y HubSpot\n";
        echo "    Ejecuta: php artisan hubspot:suite (para actualizar desde HubSpot)\n\n";
    }

    echo "===============================================================\n";
    echo "  FIN DE LA COMPARACION\n";
    echo "===============================================================\n\n";

} catch (\Exception $e) {
    echo "\n[ERROR] ".$e->getMessage()."\n";
    echo '   Archivo: '.$e->getFile()."\n";
    echo '   Linea: '.$e->getLine()."\n\n";
}
