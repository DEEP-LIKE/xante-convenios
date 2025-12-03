<?php

namespace App\Services;

use App\Models\ConfigurationCalculator;

/**
 * Servicio compartido para cálculos financieros de convenios y cotizaciones
 * 
 * Este servicio contiene toda la lógica de cálculo extraída del CreateAgreementWizard
 * para ser reutilizada tanto en el wizard como en la calculadora de cotizaciones.
 */
class AgreementCalculatorService
{
    /**
     * Obtiene los valores por defecto de configuración
     */
    public function getDefaultConfiguration(): array
    {
        $configValues = ConfigurationCalculator::whereIn('key', [
            'comision_sin_iva_default',
            'comision_iva_incluido_default', // This now stores the IVA percentage (16%)
            'precio_promocion_multiplicador_default',
            'isr_default',
            'cancelacion_hipoteca_default',
            'monto_credito_default',
        ])->pluck('value', 'key');

        return [
            'porcentaje_comision_sin_iva' => $configValues['comision_sin_iva_default'] ?? 6.50,
            'iva_percentage' => $configValues['comision_iva_incluido_default'] ?? 16.00, // Using this as IVA %
            'precio_promocion_multiplicador' => $configValues['precio_promocion_multiplicador_default'] ?? 1.09,
            'isr' => $configValues['isr_default'] ?? 0,
            'cancelacion_hipoteca' => $configValues['cancelacion_hipoteca_default'] ?? 20000,
            'monto_credito' => $configValues['monto_credito_default'] ?? 800000,
        ];
    }

    /**
     * Calcula todos los valores financieros basados en el valor del convenio
     * 
     * @param float $valorConvenio Valor principal del convenio
     * @param array $parameters Parámetros de cálculo (opcional, usa defaults si no se proporciona)
     * @return array Array con todos los valores calculados
     */
    public function calculateAllFinancials(float $valorConvenio, array $parameters = []): array
    {
        if ($valorConvenio <= 0) {
            return $this->getEmptyCalculation();
        }

        // Obtener parámetros con valores por defecto
        $defaults = $this->getDefaultConfiguration();
        $params = array_merge($defaults, $parameters);

        $porcentajeComision = (float) ($params['porcentaje_comision_sin_iva'] ?? 6.50);
        $ivaPercentage = (float) ($params['iva_percentage'] ?? 16.00);
        $multiplicadorPrecioPromocion = (float) ($params['precio_promocion_multiplicador'] ?? 1.09);
        $isr = (float) ($params['isr'] ?? 0);
        $cancelacion = (float) ($params['cancelacion_hipoteca'] ?? 20000);
        $montoCredito = (float) ($params['monto_credito'] ?? 800000);

        // Calcular porcentaje de comisión con IVA incluido dinámicamente
        $porcentajeComisionIvaIncluido = round($porcentajeComision * (1 + ($ivaPercentage / 100)), 2);

        // Realizar cálculos
        $calculations = [];
        
        // 0. Valor Convenio (valor base)
        $calculations['valor_convenio'] = $valorConvenio;

        // 1. Precio Promoción = Valor Convenio × Multiplicador Precio Promoción
        $calculations['precio_promocion'] = round($valorConvenio * $multiplicadorPrecioPromocion, 0);

        // 2. Valor CompraVenta = Valor Convenio (espejo)
        $calculations['valor_compraventa'] = $valorConvenio;

        // 3. Monto Comisión (Sin IVA) = Valor Convenio × % Comisión ÷ 100
        $calculations['monto_comision_sin_iva'] = round(($valorConvenio * $porcentajeComision) / 100, 2);

        // 4. Comisión Total Pagar = Valor Convenio × % Comisión IVA Incluido ÷ 100
        $calculations['comision_total_pagar'] = round(($valorConvenio * $porcentajeComisionIvaIncluido) / 100, 2);

        // 5. Total Gastos FI (Venta) = ISR + Cancelación de Hipoteca
        $calculations['total_gastos_fi_venta'] = round($isr + $cancelacion, 2);

        // 6. Ganancia Final = Valor CompraVenta - ISR - Cancelación Hipoteca - Comisión Total - Monto de Crédito
        $calculations['ganancia_final'] = round($valorConvenio - $isr - $cancelacion - $calculations['comision_total_pagar'] - $montoCredito, 2);

        // Agregar parámetros utilizados para referencia
        $calculations['parametros_utilizados'] = [
            'valor_convenio' => $valorConvenio,
            'porcentaje_comision_sin_iva' => $porcentajeComision,
            'iva_percentage' => $ivaPercentage,
            'porcentaje_comision_iva_incluido' => $porcentajeComisionIvaIncluido,
            'precio_promocion_multiplicador' => $multiplicadorPrecioPromocion,
            'isr' => $isr,
            'cancelacion_hipoteca' => $cancelacion,
            'monto_credito' => $montoCredito,
        ];

        return $calculations;
    }

    /**
     * Formatea los valores calculados para mostrar en la UI
     * 
     * @param array $calculations Resultado de calculateAllFinancials()
     * @return array Array con valores formateados para mostrar
     */
    public function formatCalculationsForUI(array $calculations): array
    {
        if (empty($calculations)) {
            return [];
        }

        return [
            'precio_promocion' => number_format($calculations['precio_promocion'], 0, '.', ','),
            'valor_compraventa' => number_format($calculations['valor_compraventa'], 2, '.', ','),
            'monto_comision_sin_iva' => number_format($calculations['monto_comision_sin_iva'], 2, '.', ','),
            'comision_total_pagar' => number_format($calculations['comision_total_pagar'], 2, '.', ','),
            'total_gastos_fi_venta' => number_format($calculations['total_gastos_fi_venta'], 2, '.', ','),
            'ganancia_final' => number_format($calculations['ganancia_final'], 2, '.', ','),
        ];
    }

    /**
     * Retorna un cálculo vacío (todos los valores en cero)
     */
    public function getEmptyCalculation(): array
    {
        return [
            'valor_convenio' => 0,
            'precio_promocion' => 0,
            'valor_compraventa' => 0,
            'monto_comision_sin_iva' => 0,
            'comision_total_pagar' => 0,
            'total_gastos_fi_venta' => 0,
            'ganancia_final' => 0,
            'parametros_utilizados' => [],
        ];
    }

    /**
     * Valida que los parámetros de entrada sean correctos
     * 
     * @param float $valorConvenio
     * @param array $parameters
     * @return array Array con errores de validación (vacío si no hay errores)
     */
    public function validateParameters(float $valorConvenio, array $parameters = []): array
    {
        $errors = [];

        if ($valorConvenio < 0) {
            $errors[] = 'El valor del convenio no puede ser negativo';
        }

        if ($valorConvenio > 999999999) {
            $errors[] = 'El valor del convenio es demasiado alto';
        }

        // Validar parámetros opcionales
        if (isset($parameters['porcentaje_comision_sin_iva'])) {
            $porcentaje = (float) $parameters['porcentaje_comision_sin_iva'];
            if ($porcentaje < 0 || $porcentaje > 100) {
                $errors[] = 'El porcentaje de comisión sin IVA debe estar entre 0 y 100';
            }
        }

        if (isset($parameters['porcentaje_comision_iva_incluido'])) {
            $porcentaje = (float) $parameters['porcentaje_comision_iva_incluido'];
            if ($porcentaje < 0 || $porcentaje > 100) {
                $errors[] = 'El porcentaje de comisión con IVA debe estar entre 0 y 100';
            }
        }

        if (isset($parameters['precio_promocion_multiplicador'])) {
            $multiplicador = (float) $parameters['precio_promocion_multiplicador'];
            if ($multiplicador <= 0 || $multiplicador > 10) {
                $errors[] = 'El multiplicador de precio promoción debe estar entre 0.01 y 10';
            }
        }

        if (isset($parameters['isr'])) {
            $isr = (float) $parameters['isr'];
            if ($isr < 0) {
                $errors[] = 'El ISR no puede ser negativo';
            }
        }

        if (isset($parameters['cancelacion_hipoteca'])) {
            $cancelacion = (float) $parameters['cancelacion_hipoteca'];
            if ($cancelacion < 0) {
                $errors[] = 'La cancelación de hipoteca no puede ser negativa';
            }
        }

        if (isset($parameters['monto_credito'])) {
            $credito = (float) $parameters['monto_credito'];
            if ($credito < 0) {
                $errors[] = 'El monto de crédito no puede ser negativo';
            }
        }

        return $errors;
    }

    /**
     * Precarga una propuesta por IDxante del cliente
     * 
     * @param string $idxante ID Xante del cliente
     * @return array|null Datos de la propuesta o null si no existe
     */
    public function preloadProposalByIdxante(string $idxante): ?array
    {
        $proposal = \App\Models\Proposal::where('idxante', $idxante)
            ->where('linked', true)
            ->latest()
            ->first();

        return $proposal ? $proposal->data : null;
    }

    /**
     * Calcula el resumen financiero para mostrar en reportes
     * 
     * @param array $calculations Resultado de calculateAllFinancials()
     * @return array Resumen formateado
     */
    public function getFinancialSummary(array $calculations): array
    {
        if (empty($calculations)) {
            return [];
        }

        $valorConvenio = $calculations['parametros_utilizados']['valor_convenio'] ?? 0;
        $gananciaFinal = $calculations['ganancia_final'] ?? 0;
        $comisionTotal = $calculations['comision_total_pagar'] ?? 0;

        // Calcular porcentaje de ganancia
        $porcentajeGanancia = $valorConvenio > 0 ? ($gananciaFinal / $valorConvenio) * 100 : 0;

        return [
            'valor_convenio_formatted' => '$' . number_format($valorConvenio, 2),
            'ganancia_final_formatted' => '$' . number_format($gananciaFinal, 2),
            'comision_total_formatted' => '$' . number_format($comisionTotal, 2),
            'porcentaje_ganancia' => round($porcentajeGanancia, 2),
            'es_rentable' => $gananciaFinal > 0,
        ];
    }
}
