<?php

namespace App\Actions\Hubspot\Sync;

use App\Actions\Hubspot\Client\CreateClientFromHubspot;
use App\Actions\Hubspot\Client\UpdateClientFromHubspot;
use App\Actions\Hubspot\Http\FetchContactFromDeal;
use App\Actions\Hubspot\Transform\ExtractXanteId;
use App\Actions\Hubspot\Transform\TransformHubspotContact;
use App\Actions\Hubspot\Transform\TransformHubspotDeal;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

/**
 * Action orquestador para procesar un deal individual de HubSpot
 */
class ProcessDealAction
{
    public function __construct(
        private FetchContactFromDeal $fetchContact,
        private ExtractXanteId $extractXanteId,
        private TransformHubspotContact $transformContact,
        private TransformHubspotDeal $transformDeal,
        private CreateClientFromHubspot $createClient,
        private UpdateClientFromHubspot $updateClient,
    ) {}

    /**
     * Procesar un deal individual de HubSpot
     *
     * @param  array  $deal  Datos del deal
     * @return string Resultado del procesamiento (new_clients|updated_clients|skipped|errors)
     */
    public function execute(array $deal): string
    {
        try {
            $dealId = $deal['id'];
            $properties = $deal['properties'] ?? [];

            // Validar estatus
            $estatus = $properties['estatus_de_convenio'] ?? null;
            if ($estatus !== 'Aceptado') {
                return 'skipped';
            }

            // Verificar si tiene contactos asociados
            $numContacts = (int) ($properties['num_associated_contacts'] ?? 0);
            if ($numContacts === 0) {
                Log::info("Deal {$dealId} sin contactos asociados - OMITIDO", [
                    'dealname' => $properties['dealname'] ?? 'N/A',
                ]);

                return 'skipped';
            }

            // Obtener fecha de creación del Deal
            $dealCreatedAt = $properties['createdate'] ?? null;

            // Obtener Contact asociado
            $contact = $this->fetchContact->execute($dealId);
            if (! $contact) {
                return 'skipped';
            }

            $contactProps = $contact['properties'] ?? [];
            $contactId = $contact['id'] ?? null;

            // Extraer xante_id del Contact
            $xanteId = $this->extractXanteId->execute($contactProps);
            if (! $xanteId) {
                Log::info("Contact del Deal {$dealId} sin xante_id válido - OMITIDO", [
                    'contact_id' => $contactId,
                    'email' => $contactProps['email'] ?? 'N/A',
                ]);

                return 'skipped';
            }

            // Transformar datos
            $contactData = $this->transformContact->execute($contactProps);
            $dealData = $this->transformDeal->execute($properties);

            // Verificar si cliente existe - PRIORIZAR xante_id para evitar duplicados
            $existingClient = Client::where('xante_id', $xanteId)->first();

            // Si no existe por xante_id, buscar por hubspot_id
            if (! $existingClient && $contactId) {
                $existingClient = Client::where('hubspot_id', $contactId)->first();

                // Si encontramos por hubspot_id pero tiene diferente xante_id, es un problema
                if ($existingClient && $existingClient->xante_id && $existingClient->xante_id !== $xanteId) {
                    Log::warning('Cliente encontrado por hubspot_id pero con diferente xante_id', [
                        'client_id' => $existingClient->id,
                        'existing_xante_id' => $existingClient->xante_id,
                        'new_xante_id' => $xanteId,
                        'hubspot_id' => $contactId,
                    ]);

                    // Actualizar xante_id al correcto (el de HubSpot es la fuente de verdad)
                    $existingClient->xante_id = $xanteId;
                }
            }

            if ($existingClient) {
                $this->updateClient->execute(
                    $existingClient,
                    $contactData,
                    $dealData,
                    $xanteId,
                    $dealId,
                    $dealCreatedAt,
                    $contactProps
                );

                return 'updated_clients';
            } else {
                $this->createClient->execute(
                    $contactId,
                    $contactData,
                    $dealData,
                    $xanteId,
                    $dealId,
                    $dealCreatedAt
                );

                return 'new_clients';
            }

        } catch (\Exception $e) {
            Log::error('Error procesando Deal', [
                'deal' => $deal,
                'error' => $e->getMessage(),
            ]);

            return 'errors';
        }
    }
}
