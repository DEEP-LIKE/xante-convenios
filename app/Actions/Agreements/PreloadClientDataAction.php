<?php

namespace App\Actions\Agreements;

use App\Models\Client;
use Filament\Notifications\Notification;

class PreloadClientDataAction
{
    public function execute(int $clientId, callable $set): void
    {
        $client = Client::with('spouse')->find($clientId);

        if (! $client) {
            return;
        }

        // Datos generales
        $set('xante_id', $client->xante_id);
        $set('fecha_registro', $client->fecha_registro?->format('Y-m-d'));

        // Datos personales titular
        $set('holder_name', $client->name);
        $set('holder_birthdate', $client->birthdate);
        $set('holder_curp', $client->curp);
        $set('holder_rfc', $client->rfc);
        $set('holder_email', $client->email);
        $set('holder_phone', $client->phone);
        $set('holder_delivery_file', $client->delivery_file);
        $set('holder_civil_status', $client->civil_status);
        $set('holder_regime_type', $client->regime_type);
        $set('holder_occupation', $client->occupation);
        $set('holder_office_phone', $client->office_phone);
        $set('holder_additional_contact_phone', $client->additional_contact_phone);
        $set('holder_current_address', $client->current_address);
        $set('holder_neighborhood', $client->neighborhood);
        $set('holder_postal_code', $client->postal_code);
        $set('holder_municipality', $client->municipality);
        $set('holder_state', $client->state);

        // Datos cónyuge
        $spouse = $client->spouse;
        $set('spouse_name', $spouse?->name);
        $set('spouse_birthdate', $spouse?->birthdate);
        $set('spouse_curp', $spouse?->curp);
        $set('spouse_rfc', $spouse?->rfc);
        $set('spouse_email', $spouse?->email);
        $set('spouse_phone', $spouse?->phone);
        $set('spouse_delivery_file', $spouse?->delivery_file);
        $set('spouse_civil_status', $spouse?->civil_status);
        $set('spouse_regime_type', $spouse?->regime_type);
        $set('spouse_occupation', $spouse?->occupation);
        $set('spouse_office_phone', $spouse?->office_phone);
        $set('spouse_additional_contact_phone', $spouse?->additional_contact_phone);
        $set('spouse_current_address', $spouse?->current_address);
        $set('spouse_neighborhood', $spouse?->neighborhood);
        $set('spouse_postal_code', $spouse?->postal_code);
        $set('spouse_municipality', $spouse?->municipality);
        $set('spouse_state', $spouse?->state);

        // Contactos AC/Presidente
        $set('ac_name', $client->ac_name);
        $set('ac_phone', $client->ac_phone);
        $set('ac_quota', $client->ac_quota);
        $set('private_president_name', $client->private_president_name);
        $set('private_president_phone', $client->private_president_phone);
        $set('private_president_quota', $client->private_president_quota);
        
        // Datos de la propiedad (Paso 3)
        $set('domicilio_convenio', $client->domicilio_convenio);
        $set('comunidad', $client->comunidad);
        $set('tipo_vivienda', $client->tipo_vivienda);
        $set('prototipo', $client->prototipo);
        $set('lote', $client->lote);
        $set('manzana', $client->manzana);
        $set('etapa', $client->etapa);
        $set('municipio_propiedad', $client->municipio_propiedad);
        $set('estado_propiedad', $client->estado_propiedad);

        // Precargar propuesta financiera si existe (Paso 4)
        $proposalService = app(\App\Services\ProposalPreloadService::class);
        $proposalData = $proposalService->preloadProposalData($clientId);

        if ($proposalData) {
            foreach ($proposalData as $key => $value) {
                // Solo precargar campos financieros si no están vacíos en la propuesta
                if (! empty($value) || $value === 0 || $value === '0') {
                    $set($key, $value);
                }
            }

            Notification::make()
                ->title('Pre-cálculo Cargado')
                ->body('Se han precargado los valores de la cotización previa del cliente.')
                ->info()
                ->send();
        }

        Notification::make()
            ->title('Datos precargados')
            ->body('La información del cliente ha sido precargada. Puede editarla en el paso 2 si es necesario.')
            ->success()
            ->send();
    }
}
