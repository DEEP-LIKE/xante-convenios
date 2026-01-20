<?php

namespace App\Actions\Hubspot\Client;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Action para crear nuevo cliente desde datos de HubSpot
 */
class CreateClientFromHubspot
{
    /**
     * Crear nuevo cliente desde datos de HubSpot
     *
     * @param  string  $hubspotId  ID del contacto en HubSpot
     * @param  array  $contactData  Datos transformados del contacto
     * @param  array  $dealData  Datos transformados del deal
     * @param  string  $xanteId  ID de Xante
     * @param  string  $dealId  ID del deal
     * @param  string|null  $dealCreatedAt  Fecha de creación del deal
     * @return Client Cliente creado
     */
    public function execute(
        string $hubspotId,
        array $contactData,
        array $dealData,
        string $xanteId,
        string $dealId,
        ?string $dealCreatedAt = null
    ): Client {
        // Combinar datos del contacto y del deal
        $clientData = array_merge($contactData, $dealData, [
            'hubspot_id' => $hubspotId,
            'hubspot_deal_id' => $dealId,
            'xante_id' => $xanteId,
            'hubspot_synced_at' => now(),
        ]);

        // Asignar fecha de registro desde el Deal
        if ($dealCreatedAt) {
            $clientData['fecha_registro'] = $this->parseDate($dealCreatedAt);
        }

        $client = Client::create($clientData);

        Log::info('Cliente creado desde HubSpot', [
            'xante_id' => $xanteId,
            'hubspot_id' => $hubspotId,
            'deal_id' => $dealId,
            'client_id' => $client->id,
        ]);

        return $client;
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
