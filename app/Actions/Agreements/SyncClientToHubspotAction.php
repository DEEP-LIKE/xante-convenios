<?php

namespace App\Actions\Agreements;

use App\Models\Agreement;
use App\Services\HubspotSyncService;
use Illuminate\Support\Facades\Log;

class SyncClientToHubspotAction
{
    public function __construct(
        protected HubspotSyncService $hubspotService,
        protected UpdateClientFromWizardAction $updateClientAction
    ) {}

    /**
     * Ejecuta la sincronización hacia HubSpot
     * Retorna un array con los campos no sincronizados (o errores)
     */
    public function execute(Agreement $agreement, array $wizardData): array
    {
        $client = $agreement->client;
        if (! $client || ! $client->hubspot_id) {
            Log::warning('No se puede sincronizar: Cliente no tiene HubSpot ID', ['agreement_id' => $agreement->id]);

            return ['error' => 'Cliente sin conexión a HubSpot'];
        }

        // 1. Asegurar que el cliente local tenga los datos más recientes del Wizard
        // Esto es CRÍTICO porque pushClientToHubspot lee de la BD, no del array wizardData
        $this->updateClientAction->execute($client->id, $wizardData);

        // Recargar cliente para obtener los datos recién guardados
        $client->refresh();

        // 2. Ejecutar Push a HubSpot usando el servicio centralizado
        Log::info('Iniciando Push a HubSpot desde Wizard', ['client_id' => $client->id]);

        $result = $this->hubspotService->pushClientToHubspot($client, $agreement);

        // 3. Procesar resultados
        $errors = $result['errors'] ?? [];

        if (! empty($errors)) {
            Log::error('Errores en Push a HubSpot', ['errors' => $errors]);

            return $errors;
        }

        if ($result['deal_updated']) {
            Log::info('Deal actualizado correctamente desde Wizard');
        }

        return [];
    }
}
