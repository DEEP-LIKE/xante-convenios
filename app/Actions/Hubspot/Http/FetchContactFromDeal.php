<?php

namespace App\Actions\Hubspot\Http;

use Illuminate\Support\Facades\Log;

/**
 * Action para obtener contacto asociado a un deal
 */
class FetchContactFromDeal
{
    use HubspotHttpClient;

    /**
     * Obtener contacto asociado a un deal desde HubSpot
     *
     * @param  string  $dealId  ID del deal
     * @return array|null Datos del contacto o null si no se encuentra
     */
    public function execute(string $dealId): ?array
    {
        // 1. Obtener asociaciones
        $associationsResponse = $this->makeRequest(
            'get',
            "/crm/v3/objects/deals/{$dealId}/associations/contacts"
        );

        if (! $associationsResponse['success']) {
            Log::warning("No se pudieron obtener asociaciones del Deal {$dealId}");

            return null;
        }

        $associations = $associationsResponse['data']['results'] ?? [];

        if (empty($associations)) {
            Log::info("Deal {$dealId} sin Contact asociado en API");

            return null;
        }

        // 2. Obtener ID del primer Contact asociado
        $contactId = $associations[0]['id'] ?? $associations[0]['toObjectId'] ?? null;

        if (! $contactId) {
            Log::warning("Deal {$dealId} tiene asociación pero sin Contact ID válido");

            return null;
        }

        // 3. Obtener datos del Contact
        $contactResponse = $this->makeRequest(
            'get',
            "/crm/v3/objects/contacts/{$contactId}",
            [
                'properties' => implode(',', array_merge(
                    ['firstname', 'lastname', 'email', 'phone'],
                    config('hubspot.mapping.custom_properties')
                )),
            ]
        );

        if ($contactResponse['success']) {
            return $contactResponse['data'];
        }

        Log::error("Error obteniendo Contact {$contactId} del Deal {$dealId}");

        return null;
    }
}
