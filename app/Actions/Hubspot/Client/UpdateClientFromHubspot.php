<?php

namespace App\Actions\Hubspot\Client;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Action para actualizar cliente existente desde datos de HubSpot
 */
class UpdateClientFromHubspot
{
    /**
     * Actualizar cliente existente desde datos de HubSpot
     *
     * @param  Client  $client  Cliente a actualizar
     * @param  array  $contactData  Datos transformados del contacto
     * @param  array  $dealData  Datos transformados del deal
     * @param  string  $xanteId  ID de Xante
     * @param  string  $dealId  ID del deal
     * @param  string|null  $dealCreatedAt  Fecha de creación del deal
     * @param  array  $contactProps  Propiedades originales del contacto (para validar fecha)
     * @return Client Cliente actualizado
     */
    public function execute(
        Client $client,
        array $contactData,
        array $dealData,
        string $xanteId,
        string $dealId,
        ?string $dealCreatedAt = null,
        array $contactProps = []
    ): Client {
        $hubspotId = $contactProps['hs_object_id'] ?? null;

        // Verificar si debemos actualizar datos personales (protección contra race conditions)
        if (! $this->shouldUpdate($client, $contactProps)) {
            // AUNQUE no actualicemos datos personales, SIEMPRE actualizamos los campos que son EXCLUSIVOS de HubSpot
            // o que están vacíos en local pero vienen con datos en HubSpot
            $updates = [];
            
            // IDs
            if (! $client->hubspot_id && $hubspotId) $updates['hubspot_id'] = $hubspotId;
            if (! $client->hubspot_deal_id) $updates['hubspot_deal_id'] = $dealId;
            
            // Estatus y Monto (Nuevos mapeos corregidos)
            if (($client->hubspot_status === 'N/A' || empty($client->hubspot_status)) && !empty($dealData['hubspot_status'])) {
                $updates['hubspot_status'] = $dealData['hubspot_status'];
            }
            if (empty($client->hubspot_amount) && !empty($dealData['hubspot_amount'])) {
                $updates['hubspot_amount'] = $dealData['hubspot_amount'];
            }

            // Datos de propiedad (si están vacíos en local pero vienen en el Deal)
            foreach (['domicilio_convenio', 'comunidad', 'tipo_vivienda', 'municipio_propiedad', 'estado_propiedad'] as $field) {
                if (empty($client->$field) && !empty($dealData[$field])) {
                    $updates[$field] = $dealData[$field];
                }
            }

            if (!empty($updates)) {
                $updates['hubspot_synced_at'] = now();
                $client->update($updates);
                Log::info("Campos de HubSpot actualizados forzosamente para cliente {$client->id} (aunque datos locales eran más recientes)");
            }

            return $client;
        }

        // Combinar datos del contacto y del deal
        $clientData = array_merge($contactData, $dealData, [
            'xante_id' => $xanteId,
            'hubspot_deal_id' => $dealId,
            'hubspot_synced_at' => now(),
        ]);

        // Actualizar fecha de registro si viene del Deal
        if ($dealCreatedAt) {
            $clientData['fecha_registro'] = $this->parseDate($dealCreatedAt);
        }

        // Si el cliente no tenía hubspot_id, asignarlo ahora
        if (empty($client->hubspot_id) && isset($contactProps['hs_object_id'])) {
            $clientData['hubspot_id'] = $contactProps['hs_object_id'];
        }

        $client->update($clientData);

        Log::info('Cliente actualizado desde HubSpot', [
            'client_id' => $client->id,
            'xante_id' => $xanteId,
            'hubspot_id' => $client->hubspot_id,
            'deal_id' => $dealId,
        ]);

        return $client;
    }

    /**
     * Verificar si debemos actualizar el cliente (protección contra race conditions)
     *
     * @param  Client  $client  Cliente local
     * @param  array  $contactProps  Propiedades del contacto de HubSpot
     * @return bool True si debemos actualizar
     */
    private function shouldUpdate(Client $client, array $contactProps): bool
    {
        $hsLastModified = $contactProps['lastmodifieddate'] ?? null;

        if (! $hsLastModified || ! $client->updated_at) {
            return true;
        }

        try {
            $hsDate = is_numeric($hsLastModified)
                ? Carbon::createFromTimestampMs($hsLastModified)
                : Carbon::parse($hsLastModified);

            // Si la modificación local es más reciente que la de HubSpot (con margen de 2 min)
            if ($client->updated_at->gt($hsDate->addMinutes(2))) {
                Log::info("Ignorando actualización de cliente {$client->id}: Datos locales más recientes", [
                    'local_updated_at' => $client->updated_at->toIso8601String(),
                    'hs_lastmodifieddate' => $hsDate->toIso8601String(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::warning('Error comparando fechas de modificación: '.$e->getMessage());
        }

        return true;
    }

    /**
     * Actualizar solo IDs críticos
     *
     * @param  Client  $client  Cliente a actualizar
     * @param  string|null  $hubspotId  ID de HubSpot
     * @param  string  $dealId  ID del deal
     */
    private function updateCriticalIds(Client $client, ?string $hubspotId, string $dealId): void
    {
        $updates = [];

        if (! $client->hubspot_id && $hubspotId) {
            $updates['hubspot_id'] = $hubspotId;
        }

        if (! $client->hubspot_deal_id) {
            $updates['hubspot_deal_id'] = $dealId;
        }

        if (! empty($updates)) {
            $client->update($updates);
        }
    }

    /**
     * Parsear fecha desde timestamp o string
     *
     * @param  string  $date  Fecha en timestamp o formato ISO
     * @return Carbon|null Fecha parseada o null
     */
    private function parseDate(string $date): ?Carbon
    {
        try {
            return is_numeric($date)
                ? Carbon::createFromTimestampMs($date)
                : Carbon::parse($date);
        } catch (\Exception $e) {
            Log::warning("Error parseando fecha: {$date}");

            return null;
        }
    }
}
