<?php

namespace App\Services;

use App\Models\ConfigurationCalculator;
use Illuminate\Support\Facades\Cache;

class CalculationService
{
    /**
     * Calcula todos los valores financieros basados en el precio de promoción o valor de convenio
     */
    public function calculateFinancialValues(array $input): array
    {
        // Obtener configuraciones con cache
        $config = $this->getConfigurations();
        
        // Determinar el valor base
        $valorConvenio = $this->determineBaseValue($input, $config);
        
        // Realizar todos los cálculos
        $calculations = [
            'valor_convenio' => $valorConvenio,
            'precio_promocion' => $this->calculatePrecioPromocion($valorConvenio, $config),
            'monto_comision_sin_iva' => $this->calculateMontoComisionSinIva($valorConvenio, $input, $config),
            'comision_total_pagar' => 0, // Se calculará después
            'valor_compraventa' => $valorConvenio,
            'comision_total' => 0, // Se calculará después
            'total_gastos_fi' => $this->calculateTotalGastosFi($input, $config),
            'ganancia_final' => 0, // Se calculará al final
        ];
        
        // Calcular comisión total
        $calculations['comision_total_pagar'] = $this->calculateComisionTotal($calculations['monto_comision_sin_iva'], $config);
        $calculations['comision_total'] = $calculations['comision_total_pagar'];
        
        // Calcular ganancia final
        $calculations['ganancia_final'] = $this->calculateGananciaFinal($calculations, $input);
        
        return $calculations;
    }

    /**
     * Determina el valor base (valor convenio) basado en la entrada
     */
    private function determineBaseValue(array $input, array $config): float
    {
        // Si se proporciona precio_promocion, calcular valor_convenio
        if (!empty($input['precio_promocion'])) {
            return $input['precio_promocion'] * $config['precio_promocion_multiplier'];
        }
        
        // Si se proporciona valor_convenio directamente
        if (!empty($input['valor_convenio'])) {
            return (float) $input['valor_convenio'];
        }
        
        return 0.0;
    }

    /**
     * Calcula el precio de promoción basado en el valor de convenio
     */
    private function calculatePrecioPromocion(float $valorConvenio, array $config): float
    {
        if ($valorConvenio <= 0) {
            return 0.0;
        }
        
        return $valorConvenio / $config['precio_promocion_multiplier'];
    }

    /**
     * Calcula el monto de comisión sin IVA
     */
    private function calculateMontoComisionSinIva(float $valorConvenio, array $input, array $config): float
    {
        if ($valorConvenio <= 0) {
            return 0.0;
        }
        
        $porcentajeComision = $input['porcentaje_comision_sin_iva'] ?? $config['comision_sin_iva_default'];
        
        return ($porcentajeComision * $valorConvenio) / 100;
    }

    /**
     * Calcula la comisión total (con IVA)
     */
    private function calculateComisionTotal(float $montoComisionSinIva, array $config): float
    {
        return $montoComisionSinIva * $config['iva_multiplier'];
    }

    /**
     * Calcula el total de gastos FI
     */
    private function calculateTotalGastosFi(array $input, array $config): float
    {
        $isr = $input['isr'] ?? $config['isr_default'];
        $cancelacionHipoteca = $input['cancelacion_hipoteca'] ?? $config['cancelacion_hipoteca_default'];
        
        return $isr + $cancelacionHipoteca;
    }

    /**
     * Calcula la ganancia final
     */
    private function calculateGananciaFinal(array $calculations, array $input): float
    {
        $valorConvenio = $calculations['valor_convenio'];
        $comisionTotal = $calculations['comision_total_pagar'];
        $isr = $input['isr'] ?? 0;
        $cancelacionHipoteca = $input['cancelacion_hipoteca'] ?? 0;
        $montoCredito = $input['monto_credito'] ?? 0;
        
        return $valorConvenio - $comisionTotal - $isr - $cancelacionHipoteca - $montoCredito;
    }

    /**
     * Aplica descuentos a un cálculo
     */
    public function applyDiscounts(array $calculation, array $discounts): array
    {
        $discountedCalculation = $calculation;
        
        foreach ($discounts as $discount) {
            if ($discount['type'] === 'percentage') {
                $discountAmount = ($calculation['valor_convenio'] * $discount['value']) / 100;
            } else {
                $discountAmount = $discount['value'];
            }
            
            $discountedCalculation['valor_convenio'] -= $discountAmount;
            $discountedCalculation['descuentos_aplicados'][] = [
                'name' => $discount['name'],
                'amount' => $discountAmount,
                'type' => $discount['type']
            ];
        }
        
        // Recalcular con el nuevo valor
        return $this->calculateFinancialValues([
            'valor_convenio' => $discountedCalculation['valor_convenio'],
            'porcentaje_comision_sin_iva' => $calculation['porcentaje_comision_sin_iva'] ?? null,
            'isr' => $calculation['isr'] ?? null,
            'cancelacion_hipoteca' => $calculation['cancelacion_hipoteca'] ?? null,
            'monto_credito' => $calculation['monto_credito'] ?? null,
        ]);
    }

    /**
     * Genera comparación con convenios similares
     */
    public function generateComparison(array $calculation, array $similarAgreements): array
    {
        $comparisons = [];
        
        foreach ($similarAgreements as $agreement) {
            $comparisons[] = [
                'agreement_id' => $agreement['id'],
                'client_name' => $agreement['client_name'],
                'valor_convenio' => $agreement['valor_convenio'],
                'ganancia_final' => $agreement['ganancia_final'],
                'difference_valor' => $calculation['valor_convenio'] - $agreement['valor_convenio'],
                'difference_ganancia' => $calculation['ganancia_final'] - $agreement['ganancia_final'],
                'percentage_difference' => $agreement['valor_convenio'] > 0 
                    ? (($calculation['valor_convenio'] - $agreement['valor_convenio']) / $agreement['valor_convenio']) * 100 
                    : 0,
            ];
        }
        
        return $comparisons;
    }

    /**
     * Calcula escenarios "¿qué pasaría si...?"
     */
    public function calculateScenarios(array $baseCalculation, array $scenarios): array
    {
        $results = [];
        
        foreach ($scenarios as $scenarioName => $changes) {
            $scenarioInput = array_merge($baseCalculation, $changes);
            $scenarioResult = $this->calculateFinancialValues($scenarioInput);
            
            $results[$scenarioName] = [
                'calculation' => $scenarioResult,
                'changes' => $changes,
                'impact' => [
                    'valor_convenio_change' => $scenarioResult['valor_convenio'] - $baseCalculation['valor_convenio'],
                    'ganancia_final_change' => $scenarioResult['ganancia_final'] - $baseCalculation['ganancia_final'],
                ]
            ];
        }
        
        return $results;
    }

    /**
     * Valida que los valores de entrada sean correctos
     */
    public function validateInput(array $input): array
    {
        $errors = [];
        
        // Validar precio promoción
        if (isset($input['precio_promocion']) && $input['precio_promocion'] < 0) {
            $errors['precio_promocion'] = 'El precio de promoción no puede ser negativo';
        }
        
        // Validar valor convenio
        if (isset($input['valor_convenio']) && $input['valor_convenio'] < 0) {
            $errors['valor_convenio'] = 'El valor del convenio no puede ser negativo';
        }
        
        // Validar porcentaje de comisión
        if (isset($input['porcentaje_comision_sin_iva'])) {
            if ($input['porcentaje_comision_sin_iva'] < 0 || $input['porcentaje_comision_sin_iva'] > 100) {
                $errors['porcentaje_comision_sin_iva'] = 'El porcentaje de comisión debe estar entre 0 y 100';
            }
        }
        
        // Validar que al menos uno de los valores base esté presente
        if (empty($input['precio_promocion']) && empty($input['valor_convenio'])) {
            $errors['base_value'] = 'Debe proporcionar al menos el precio de promoción o el valor del convenio';
        }
        
        return $errors;
    }

    /**
     * Obtiene las configuraciones con cache
     */
    private function getConfigurations(): array
    {
        return Cache::remember('calculator_configurations', 3600, function () {
            return [
                'comision_sin_iva_default' => ConfigurationCalculator::get('comision_sin_iva_default', 6.50),
                'iva_multiplier' => ConfigurationCalculator::get('iva_multiplier', 1.16),
                'precio_promocion_multiplier' => ConfigurationCalculator::get('precio_promocion_multiplier', 1.09),
                'isr_default' => ConfigurationCalculator::get('isr_default', 0),
                'cancelacion_hipoteca_default' => ConfigurationCalculator::get('cancelacion_hipoteca_default', 20000),
                'monto_credito_default' => ConfigurationCalculator::get('monto_credito_default', 800000),
                'tipo_credito_default' => ConfigurationCalculator::get('tipo_credito_default', 'BANCARIO'),
                'otro_banco_default' => ConfigurationCalculator::get('otro_banco_default', 'NO APLICA'),
            ];
        });
    }

    /**
     * Limpia el cache de configuraciones
     */
    public function clearConfigurationCache(): void
    {
        Cache::forget('calculator_configurations');
    }

    /**
     * Formatea valores monetarios para mostrar
     */
    public function formatMoney(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }

    /**
     * Redondea valores monetarios
     */
    public function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
