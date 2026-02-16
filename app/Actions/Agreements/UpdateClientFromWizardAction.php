<?php

namespace App\Actions\Agreements;

use App\Models\Client;
use Filament\Notifications\Notification;

class UpdateClientFromWizardAction
{
    public function execute(int $clientId, array $wizardData): array
    {
        $client = Client::find($clientId);

        if (! $client) {
            Notification::make()
                ->title('Error')
                ->body('Cliente no encontrado.')
                ->danger()
                ->send();

            return [];
        }

        $clientUpdateData = [];
        $spouseUpdateData = [];

        // Mapear campos del wizard a campos del cliente
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
            // Datos de la propiedad (añadidos recientemente)
            'domicilio_convenio' => 'domicilio_convenio',
            'comunidad' => 'comunidad',
            'tipo_vivienda' => 'tipo_vivienda',
            'prototipo' => 'prototipo',
            'lote' => 'lote',
            'manzana' => 'manzana',
            'etapa' => 'etapa',
            'municipio_propiedad' => 'municipio_propiedad',
            'estado_propiedad' => 'estado_propiedad',
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
            if (isset($wizardData[$wizardField])) {
                $clientUpdateData[$clientField] = $wizardData[$wizardField];
            }
        }

        // Preparar datos del cónyuge
        foreach ($spouseFieldMapping as $spouseField => $wizardField) {
            if (isset($wizardData[$wizardField])) {
                $spouseUpdateData[$spouseField] = $wizardData[$wizardField];
            }
        }

        $dirtyFields = [];

        // Actualizar cliente - Comparar contra valores originales para detectar cambios reales
        if (! empty($clientUpdateData)) {
            // Capturar valores originales ANTES de fill()
            $originalValues = [];
            foreach (array_keys($clientUpdateData) as $field) {
                $originalValues[$field] = $client->getOriginal($field);
            }

            $client->fill($clientUpdateData);
            
            // Comparar manualmente para detectar solo cambios REALES
            $realChanges = [];
            foreach ($clientUpdateData as $field => $newValue) {
                $oldValue = $originalValues[$field] ?? null;
                
                // Comparación que maneja null, strings vacíos, y tipos diferentes
                if ($this->hasRealChange($oldValue, $newValue)) {
                    $realChanges[$field] = $newValue;
                }
            }

            if (! empty($realChanges)) {
                $dirtyFields['client'] = $realChanges;
                $client->save();
            }
        }

        // Actualizar o crear cónyuge - Misma lógica de comparación inteligente
        if (! empty($spouseUpdateData) && ! empty($spouseUpdateData['name'])) {
            $spouse = $client->spouse;
            $isNewSpouse = ! $spouse;
            
            if ($isNewSpouse) {
                $spouse = new \App\Models\Spouse(['client_id' => $client->id]);
            }

            // Capturar valores originales
            $originalSpouseValues = [];
            if (! $isNewSpouse) {
                foreach (array_keys($spouseUpdateData) as $field) {
                    $originalSpouseValues[$field] = $spouse->getOriginal($field);
                }
            }

            $spouse->fill($spouseUpdateData);
            
            // Comparar manualmente
            $realSpouseChanges = [];
            foreach ($spouseUpdateData as $field => $newValue) {
                $oldValue = $originalSpouseValues[$field] ?? null;
                
                if ($this->hasRealChange($oldValue, $newValue) || $isNewSpouse) {
                    $realSpouseChanges[$field] = $newValue;
                }
            }

            if (! empty($realSpouseChanges)) {
                $dirtyFields['spouse'] = $realSpouseChanges;
                $spouse->save();
            }
        }

        if (! empty($dirtyFields)) {
            Notification::make()
                ->title('Datos actualizados')
                ->body('La información ha sido actualizada y los cambios se sincronizarán.')
                ->success()
                ->send();
        }

        return $dirtyFields;
    }

    /**
     * Determina si hay un cambio real entre dos valores
     * Maneja correctamente null, strings vacíos, y conversiones de tipo
     */
    private function hasRealChange($oldValue, $newValue): bool
    {
        // Si ambos son null o vacíos, no hay cambio
        if (empty($oldValue) && empty($newValue)) {
            return false;
        }

        // Si uno es null/vacío y el otro no, hay cambio
        if (empty($oldValue) !== empty($newValue)) {
            return true;
        }

        // Normalizar para comparación (trim strings, convertir a string para comparar)
        $normalizedOld = is_string($oldValue) ? trim($oldValue) : (string) $oldValue;
        $normalizedNew = is_string($newValue) ? trim($newValue) : (string) $newValue;

        return $normalizedOld !== $normalizedNew;
    }
}
