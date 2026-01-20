<?php

namespace App\Actions\Hubspot\Transform;

use Illuminate\Support\Facades\Log;

/**
 * Action para extraer y validar xante_id de propiedades de HubSpot
 */
class ExtractXanteId
{
    /**
     * Extraer y validar xante_id de propiedades de HubSpot
     *
     * @param  array  $properties  Propiedades del contacto/deal
     * @return string|null xante_id válido o null
     */
    public function execute(array $properties): ?string
    {
        $possibleFields = config('hubspot.mapping.custom_properties');

        foreach ($possibleFields as $field) {
            if (isset($properties[$field]) && ! empty(trim($properties[$field]))) {
                $xanteId = trim($properties[$field]);

                // Validación: debe ser numérico y mayor a 0
                if (is_numeric($xanteId) && (int) $xanteId > 0) {
                    return $xanteId;
                }

                Log::warning('xante_id inválido encontrado', [
                    'field' => $field,
                    'value' => $xanteId,
                ]);
            }
        }

        return null;
    }
}
