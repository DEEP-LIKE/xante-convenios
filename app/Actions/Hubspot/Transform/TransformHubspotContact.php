<?php

namespace App\Actions\Hubspot\Transform;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Action para transformar datos de contacto de HubSpot a formato local
 */
class TransformHubspotContact
{
    /**
     * Transformar propiedades de contacto de HubSpot a datos de cliente
     *
     * @param  array  $properties  Propiedades del contacto
     * @return array Datos transformados para modelo Client
     */
    public function execute(array $properties): array
    {
        $mapping = config('hubspot.mapping.contact_fields');
        $clientData = [];

        foreach ($mapping as $hubspotField => $clientField) {
            if (isset($properties[$hubspotField]) && ! empty($properties[$hubspotField])) {
                $value = $properties[$hubspotField];

                // Procesar fechas
                if (in_array($clientField, ['fecha_registro', 'updated_at']) && is_numeric($value)) {
                    $value = Carbon::createFromTimestampMs($value);
                }

                // Procesar fecha de nacimiento
                if ($clientField === 'birthdate') {
                    $value = $this->parseBirthdate($value);
                }

                $clientData[$clientField] = $value;
            }
        }

        // Mapeo de nombre completo
        if (isset($properties['firstname']) && isset($properties['lastname'])) {
            $clientData['name'] = trim(($properties['firstname'] ?? '').' '.($properties['lastname'] ?? ''));
        } elseif (isset($properties['firstname'])) {
            $clientData['name'] = $properties['firstname'];
        }

        return $clientData;
    }

    /**
     * Parsear fecha de nacimiento
     *
     * @param  mixed  $value  Valor de fecha
     * @return string|null Fecha formateada o null
     */
    private function parseBirthdate($value): ?string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestampMs($value)->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Error parseando fecha de nacimiento: {$value}");

            return null;
        }
    }
}
