<?php

namespace App\Actions\Agreements;

use App\Models\Client;
use Filament\Notifications\Notification;

class UpdateClientFromWizardAction
{
    public function execute(int $clientId, array $wizardData): void
    {
        $client = Client::find($clientId);

        if (! $client) {
            Notification::make()
                ->title('Error')
                ->body('Cliente no encontrado.')
                ->danger()
                ->send();

            return;
        }

        $clientUpdateData = [];
        $spouseUpdateData = [];

        // Mapear campos del wizard a campos del cliente
        // NOTA: Solo campos que existen en la tabla 'clients'
        // Los datos de AC/Privada y régimen se guardan en wizard_data del convenio
        $clientFieldMapping = [
            'delivery_file' => 'holder_delivery_file',
            'curp' => 'holder_curp',
            'rfc' => 'holder_rfc',
            'email' => 'holder_email',
            'phone' => 'holder_phone',
            'office_phone' => 'holder_office_phone',
            'additional_contact_phone' => 'holder_additional_contact_phone',
            'current_address' => 'current_address',
            'neighborhood' => 'neighborhood',
            'postal_code' => 'postal_code',
            'municipality' => 'municipality',
            'state' => 'state',
            'civil_status' => 'holder_civil_status',
            'occupation' => 'holder_occupation',
            // Campos removidos (no existen en tabla clients):
            // - regime_type (se guarda en wizard_data)
            // - ac_name, ac_phone, ac_quota (datos de propiedad, en wizard_data)
            // - private_president_* (datos de propiedad, en wizard_data)
        ];

        // Mapear campos del wizard a campos del cónyuge
        $spouseFieldMapping = [
            'name' => 'spouse_name',
            'birthdate' => 'spouse_birthdate',
            'curp' => 'spouse_curp',
            'rfc' => 'spouse_rfc',
            'email' => 'spouse_email',
            'phone' => 'spouse_phone',
            'delivery_file' => 'spouse_delivery_file',
            'civil_status' => 'spouse_civil_status',
            // 'regime_type' => 'spouse_regime_type', // Removido - no existe en tabla spouses
            'occupation' => 'spouse_occupation',
            'office_phone' => 'spouse_office_phone',
            'additional_contact_phone' => 'spouse_additional_contact_phone',
            'current_address' => 'spouse_current_address',
            'neighborhood' => 'spouse_neighborhood',
            'postal_code' => 'spouse_postal_code',
            'municipality' => 'spouse_municipality',
            'state' => 'spouse_state',
        ];

        // Preparar datos del cliente
        foreach ($clientFieldMapping as $clientField => $wizardField) {
            if (isset($wizardData[$wizardField]) && $wizardData[$wizardField] !== null) {
                $clientUpdateData[$clientField] = $wizardData[$wizardField];
            }
        }

        // Preparar datos del cónyuge
        foreach ($spouseFieldMapping as $spouseField => $wizardField) {
            if (isset($wizardData[$wizardField]) && $wizardData[$wizardField] !== null) {
                $spouseUpdateData[$spouseField] = $wizardData[$wizardField];
            }
        }

        // Actualizar cliente
        if (! empty($clientUpdateData)) {
            $client->update($clientUpdateData);
        }

        // Actualizar o crear cónyuge
        // Solo si hay al menos un dato relevante (ej: nombre)
        if (! empty($spouseUpdateData) && ! empty($spouseUpdateData['name'])) {
            $client->spouse()->updateOrCreate(
                ['client_id' => $client->id],
                $spouseUpdateData
            );
        }

        Notification::make()
            ->title('Datos actualizados')
            ->body('La información del cliente y cónyuge ha sido actualizada.')
            ->success()
            ->send();
    }
}
