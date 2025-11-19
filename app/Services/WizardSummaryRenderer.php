<?php

namespace App\Services;

/**
 * Servicio para renderizar resúmenes HTML del wizard
 * 
 * Responsabilidades:
 * - Generar HTML para el resumen del titular
 * - Generar HTML para el resumen del cónyuge
 * - Generar HTML para el resumen de la propiedad
 * - Generar HTML para el resumen financiero
 */
class WizardSummaryRenderer
{
    /**
     * Renderiza el resumen de datos del TITULAR
     */
    public function renderHolderSummary(array $data): string
    {
        $html = '<div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">';

        if (!empty($data['holder_name'])) {
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">';
            $html .= '<div><strong class="text-blue-700">Nombre:</strong> ' . ($data['holder_name'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Email:</strong> ' . ($data['holder_email'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Teléfono:</strong> ' . ($data['holder_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Tel. Oficina:</strong> ' . ($data['holder_office_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">CURP:</strong> ' . ($data['holder_curp'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">RFC:</strong> ' . ($data['holder_rfc'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Estado Civil:</strong> ' . ($data['holder_civil_status'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-blue-700">Ocupación:</strong> ' . ($data['holder_occupation'] ?? 'N/A') . '</div>';

            // Domicilio del titular
            if (!empty($data['current_address'])) {
                $html .= '<div class="col-span-2 mt-3 pt-3 border-t border-blue-200">';
                $html .= '<h5 class="font-semibold text-blue-700 mb-2 flex items-center"><span class="mr-1">📍</span> Domicilio</h5>';

                $address = $data['current_address'];
                if (!empty($data['holder_house_number'])) {
                    $address .= ' #' . $data['holder_house_number'];
                }
                $html .= '<div class="mb-2"><strong class="text-blue-700">Dirección:</strong> ' . $address . '</div>';

                $html .= '<div class="grid grid-cols-2 gap-2">';
                if (!empty($data['neighborhood'])) {
                    $html .= '<div><strong class="text-blue-700">Colonia:</strong> ' . $data['neighborhood'] . '</div>';
                }
                if (!empty($data['postal_code'])) {
                    $html .= '<div><strong class="text-blue-700">C.P.:</strong> ' . $data['postal_code'] . '</div>';
                }
                if (!empty($data['municipality'])) {
                    $html .= '<div><strong class="text-blue-700">Municipio:</strong> ' . $data['municipality'] . '</div>';
                }
                if (!empty($data['state'])) {
                    $html .= '<div><strong class="text-blue-700">Estado:</strong> ' . $data['state'] . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="text-center text-gray-500 py-4">📝 No se capturó información del titular</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen de datos del CÓNYUGE/COACREDITADO
     */
    public function renderSpouseSummary(array $data): string
    {
        $html = '<div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">';

        if (!empty($data['spouse_name'])) {
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">';
            $html .= '<div><strong class="text-green-700">Nombre:</strong> ' . ($data['spouse_name'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Email:</strong> ' . ($data['spouse_email'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Teléfono:</strong> ' . ($data['spouse_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Tel. Oficina:</strong> ' . ($data['spouse_office_phone'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">CURP:</strong> ' . ($data['spouse_curp'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">RFC:</strong> ' . ($data['spouse_rfc'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Estado Civil:</strong> ' . ($data['spouse_civil_status'] ?? 'N/A') . '</div>';
            $html .= '<div><strong class="text-green-700">Ocupación:</strong> ' . ($data['spouse_occupation'] ?? 'N/A') . '</div>';

            // Domicilio del cónyuge
            if (!empty($data['spouse_current_address'])) {
                $html .= '<div class="col-span-2 mt-3 pt-3 border-t border-green-200">';
                $html .= '<h5 class="font-semibold text-green-700 mb-2 flex items-center"><span class="mr-1">📍</span> Domicilio</h5>';

                $address = $data['spouse_current_address'];
                if (!empty($data['spouse_house_number'])) {
                    $address .= ' #' . $data['spouse_house_number'];
                }
                $html .= '<div class="mb-2"><strong class="text-green-700">Dirección:</strong> ' . $address . '</div>';

                $html .= '<div class="grid grid-cols-2 gap-2">';
                if (!empty($data['spouse_neighborhood'])) {
                    $html .= '<div><strong class="text-green-700">Colonia:</strong> ' . $data['spouse_neighborhood'] . '</div>';
                }
                if (!empty($data['spouse_postal_code'])) {
                    $html .= '<div><strong class="text-green-700">C.P.:</strong> ' . $data['spouse_postal_code'] . '</div>';
                }
                if (!empty($data['spouse_municipality'])) {
                    $html .= '<div><strong class="text-green-700">Municipio:</strong> ' . $data['spouse_municipality'] . '</div>';
                }
                if (!empty($data['spouse_state'])) {
                    $html .= '<div><strong class="text-green-700">Estado:</strong> ' . $data['spouse_state'] . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="text-center text-gray-500 py-4 italic">';
            $html .= '📝 No se capturó información del cónyuge / coacreditado';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen de datos de la propiedad
     */
    public function renderPropertySummary(array $data): string
    {
        $html = '<div class="space-y-3">';

        $html .= '<div><strong>Domicilio:</strong> ' . ($data['domicilio_convenio'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Comunidad:</strong> ' . ($data['comunidad'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Tipo de Vivienda:</strong> ' . ($data['tipo_vivienda'] ?? 'N/A') . '</div>';
        $html .= '<div><strong>Prototipo:</strong> ' . ($data['prototipo'] ?? 'N/A') . '</div>';

        if (!empty($data['lote']) || !empty($data['manzana']) || !empty($data['etapa'])) {
            $html .= '<div><strong>Ubicación:</strong> ';
            if (!empty($data['lote'])) $html .= 'Lote ' . $data['lote'] . ' ';
            if (!empty($data['manzana'])) $html .= 'Manzana ' . $data['manzana'] . ' ';
            if (!empty($data['etapa'])) $html .= 'Etapa ' . $data['etapa'];
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Renderiza el resumen financiero
     */
    public function renderFinancialSummary(array $data): string
    {
        $valorConvenio = floatval(str_replace(',', '', $data['valor_convenio'] ?? 0));
        $precioPromocion = floatval(str_replace(',', '', $data['precio_promocion'] ?? 0));
        $comisionTotal = floatval(str_replace(',', '', $data['comision_total_pagar'] ?? 0));
        $gananciaFinal = floatval(str_replace(',', '', $data['ganancia_final'] ?? 0));

        $html = '<div class="bg-gray-50 p-4 rounded-lg space-y-2">';
        $html .= '<div class="flex justify-between"><span><strong>Valor del Convenio:</strong></span><span class="font-bold text-blue-600">$' . number_format($valorConvenio, 2) . '</span></div>';
        $html .= '<div class="flex justify-between"><span><strong>Precio de Promoción:</strong></span><span class="font-bold text-green-600">$' . number_format($precioPromocion, 2) . '</span></div>';
        $html .= '<div class="flex justify-between"><span><strong>Comisión Total:</strong></span><span class="font-bold text-orange-600">$' . number_format($comisionTotal, 2) . '</span></div>';
        $html .= '<hr class="my-2">';
        $html .= '<div class="flex justify-between text-lg"><span><strong>Ganancia Final:</strong></span><span class="font-bold text-green-700">$' . number_format($gananciaFinal, 2) . '</span></div>';
        $html .= '</div>';

        return $html;
    }
}
