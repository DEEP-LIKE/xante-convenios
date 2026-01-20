<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use App\Models\Client;
use Illuminate\Console\Command;

class SyncClientAgreementRelations extends Command
{
    protected $signature = 'sync:client-agreements';

    protected $description = 'Sincroniza las relaciones entre clientes y convenios basándose en los datos del wizard';

    public function handle()
    {
        $this->info('Iniciando sincronización de relaciones Cliente-Convenio...');

        // Obtener todos los convenios que tienen datos de wizard pero no tienen client_xante_id
        $agreementsWithoutClient = Agreement::whereNull('client_xante_id')
            ->whereNotNull('wizard_data')
            ->get();

        $this->info("Encontrados {$agreementsWithoutClient->count()} convenios sin relación de cliente.");

        $synced = 0;
        $created = 0;

        foreach ($agreementsWithoutClient as $agreement) {
            $wizardData = $agreement->wizard_data;

            if (! $wizardData || ! isset($wizardData['xante_id'])) {
                continue;
            }

            $xanteId = $wizardData['xante_id'];

            // Buscar cliente existente
            $client = Client::where('xante_id', $xanteId)->first();

            if (! $client) {
                // Crear cliente si no existe
                $client = $this->createClientFromWizardData($wizardData, $agreement);
                if ($client) {
                    $created++;
                    $this->line("✓ Cliente creado: {$xanteId}");
                }
            }

            if ($client) {
                // Actualizar la relación
                $agreement->update(['client_xante_id' => $client->xante_id]);
                $synced++;
                $this->line("✓ Relación sincronizada: Convenio {$agreement->id} -> Cliente {$client->xante_id}");
            }
        }

        // También verificar convenios que tienen client_xante_id pero el cliente no existe
        $orphanedAgreements = Agreement::whereNotNull('client_xante_id')
            ->whereDoesntHave('client')
            ->get();

        $this->info("Encontrados {$orphanedAgreements->count()} convenios con clientes inexistentes.");

        foreach ($orphanedAgreements as $agreement) {
            $wizardData = $agreement->wizard_data;

            if ($wizardData) {
                $client = $this->createClientFromWizardData($wizardData, $agreement);
                if ($client) {
                    $created++;
                    $this->line("✓ Cliente recreado: {$client->xante_id}");
                }
            }
        }

        $this->info('Sincronización completada:');
        $this->info("- Relaciones sincronizadas: {$synced}");
        $this->info("- Clientes creados: {$created}");

        return Command::SUCCESS;
    }

    private function createClientFromWizardData(array $wizardData, Agreement $agreement): ?Client
    {
        try {
            $clientData = [
                'xante_id' => $wizardData['xante_id'] ?? null,
                'name' => $wizardData['holder_name'] ?? $agreement->holder_name ?? 'Cliente Importado',
                'email' => $wizardData['holder_email'] ?? $agreement->holder_email ?? 'sin-email@xante.com',
                'phone' => $wizardData['holder_phone'] ?? $agreement->holder_phone ?? '0000000000',
                'curp' => $wizardData['holder_curp'] ?? $agreement->holder_curp,
                'rfc' => $wizardData['holder_rfc'] ?? $agreement->holder_rfc,
                'birthdate' => $wizardData['holder_birthdate'] ?? $agreement->holder_birthdate ?? now()->subYears(30),
                'current_address' => $wizardData['current_address'] ?? $agreement->current_address,
                'civil_status' => $wizardData['holder_civil_status'] ?? $agreement->holder_civil_status,
                'occupation' => $wizardData['holder_occupation'] ?? $agreement->holder_occupation,
                'municipality' => $wizardData['municipality'] ?? $agreement->municipality,
                'state' => $wizardData['state'] ?? $agreement->state,
                'postal_code' => $wizardData['postal_code'] ?? $agreement->postal_code,
                'neighborhood' => $wizardData['neighborhood'] ?? $agreement->neighborhood,
            ];

            // Filtrar valores nulos
            $clientData = array_filter($clientData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Asegurar campos requeridos
            if (! isset($clientData['xante_id'])) {
                return null;
            }

            return Client::create($clientData);

        } catch (\Exception $e) {
            $this->error('Error creando cliente: '.$e->getMessage());

            return null;
        }
    }
}
