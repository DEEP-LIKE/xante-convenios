<?php

namespace App\Actions\Hubspot\Http;

use Illuminate\Support\Facades\Http;

/**
 * Trait para configuración HTTP común de HubSpot con retry logic
 */
trait HubspotHttpClient
{
    /**
     * Realizar request HTTP a HubSpot API con retry logic
     *
     * @param  string  $method  Método HTTP (get, post, put, delete)
     * @param  string  $endpoint  Endpoint relativo (ej: /crm/v3/objects/deals)
     * @param  array  $data  Datos para el request (query params o body)
     * @return array{success: bool, data?: array, error?: string}
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::timeout(config('hubspot.sync.timeout'))
                ->retry(3, 100, function ($exception) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException
                        || $exception instanceof \Illuminate\Http\Client\RequestException;
                }, throw: false)
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('hubspot.token'),
                    'Content-Type' => 'application/json',
                ])
                ->$method(config('hubspot.api_base_url').$endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
