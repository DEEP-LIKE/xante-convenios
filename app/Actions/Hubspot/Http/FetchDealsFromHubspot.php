<?php

namespace App\Actions\Hubspot\Http;

/**
 * Action para obtener deals desde HubSpot API
 */
class FetchDealsFromHubspot
{
    use HubspotHttpClient;

    /**
     * Obtener deals con estatus "Aceptado" desde HubSpot
     *
     * @param  string|null  $after  Cursor de paginación
     * @return array{success: bool, data?: array, error?: string}
     */
    public function execute(?string $after = null): array
    {
        $payload = [
            'filterGroups' => config('hubspot.filters.deal_accepted.filterGroups'),
            'properties' => config('hubspot.deal_sync.properties'),
            'limit' => config('hubspot.sync.batch_size'),
            'sorts' => [
                [
                    'propertyName' => 'createdate',
                    'direction' => 'DESCENDING',
                ],
            ],
        ];

        if ($after) {
            $payload['after'] = $after;
        }

        return $this->makeRequest('post', config('hubspot.endpoints.deals_search'), $payload);
    }
}
