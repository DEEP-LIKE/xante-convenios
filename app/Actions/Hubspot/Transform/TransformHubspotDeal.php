<?php

namespace App\Actions\Hubspot\Transform;

/**
 * Action para transformar datos de deal de HubSpot a formato local
 */
class TransformHubspotDeal
{
    /**
     * Transformar propiedades de deal de HubSpot a datos de cliente
     *
     * @param  array  $dealProps  Propiedades del deal
     * @return array Datos transformados para modelo Client
     */
    public function execute(array $dealProps): array
    {
        $clientData = [];

        // Datos del titular desde el Deal
        if (! empty($dealProps['nombre_completo'])) {
            $clientData['name'] = $dealProps['nombre_completo'];
        }

        if (! empty($dealProps['email'])) {
            $clientData['email'] = $dealProps['email'];
        }

        if (! empty($dealProps['phone'])) {
            $clientData['phone'] = $dealProps['phone'];
        }

        if (! empty($dealProps['mobilephone'])) {
            $clientData['phone'] = $dealProps['mobilephone'];
        }

        if (! empty($dealProps['telefono_oficina'])) {
            $clientData['office_phone'] = $dealProps['telefono_oficina'];
        }

        if (! empty($dealProps['curp'])) {
            $clientData['curp'] = $dealProps['curp'];
        }

        if (! empty($dealProps['rfc'])) {
            $clientData['rfc'] = $dealProps['rfc'];
        }

        if (! empty($dealProps['estado_civil'])) {
            $clientData['civil_status'] = $dealProps['estado_civil'];
        }

        if (! empty($dealProps['ocupacion'])) {
            $clientData['occupation'] = $dealProps['ocupacion'];
        }

        // Domicilio del titular desde el Deal
        if (! empty($dealProps['domicilio_actual'])) {
            $address = $dealProps['domicilio_actual'];
            if (! empty($dealProps['numero_casa'])) {
                $address .= ' #'.$dealProps['numero_casa'];
            }
            $clientData['current_address'] = $address;
        }

        if (! empty($dealProps['colonia'])) {
            $clientData['neighborhood'] = $dealProps['colonia'];
        }

        if (! empty($dealProps['codigo_postal'])) {
            $clientData['postal_code'] = $dealProps['codigo_postal'];
        }

        if (! empty($dealProps['municipio'])) {
            $clientData['municipality'] = $dealProps['municipio'];
        }

        if (! empty($dealProps['estado'])) {
            $clientData['state'] = $dealProps['estado'];
        }

        return $clientData;
    }
}
