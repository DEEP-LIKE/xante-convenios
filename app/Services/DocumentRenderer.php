<?php

namespace App\Services;

use App\Models\Agreement;

/**
 * Servicio para renderizar HTML de comparaciones y resúmenes financieros
 *
 * Responsabilidades:
 * - Renderizar comparación de valores
 * - Renderizar valores originales del wizard
 * - Formatear valores monetarios
 */
class DocumentRenderer
{
    public function __construct(
        protected DocumentComparisonService $comparisonService
    ) {}

    /**
     * Renderiza el valor CompraVenta original del wizard
     */
    public function renderOriginalValorCompraventa(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $valorCompraventa = $wizardData['valor_compraventa'] ?? $wizardData['valor_convenio'] ?? null;

        if ($valorCompraventa) {
            $valorCompraventa = (float) str_replace(['$', ','], '', $valorCompraventa);

            return '<span style="color: #059669; font-weight: 600; font-size: 16px;">$'.number_format($valorCompraventa, 2).'</span>';
        }

        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Renderiza la comisión total original del wizard
     */
    public function renderOriginalComisionTotal(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $comisionTotal = $wizardData['comision_total_pagar'] ?? null;

        if ($comisionTotal) {
            $comisionTotal = (float) str_replace(['$', ','], '', $comisionTotal);

            return '<span style="color: #DC2626; font-weight: 600; font-size: 16px;">$'.number_format($comisionTotal, 2).'</span>';
        }

        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Renderiza la ganancia final original del wizard
     */
    public function renderOriginalGananciaFinal(Agreement $agreement): string
    {
        $wizardData = $agreement->wizard_data ?? [];
        $gananciaFinal = $wizardData['ganancia_final'] ?? null;

        if ($gananciaFinal) {
            $gananciaFinal = (float) str_replace(['$', ','], '', $gananciaFinal);

            return '<span style="color: #7C3AED; font-weight: 600; font-size: 16px;">$'.number_format($gananciaFinal, 2).'</span>';
        }

        return '<span style="color: #6B7280;">No disponible</span>';
    }

    /**
     * Renderiza la comparación entre valores originales y finales
     */
    public function renderValueComparison(Agreement $agreement): string
    {
        if (! $agreement->proposal_value) {
            return '';
        }

        $wizardData = $agreement->wizard_data ?? [];
        $valorOriginal = $wizardData['valor_compraventa'] ?? $wizardData['valor_convenio'] ?? 0;
        $valorFinal = $agreement->proposal_value;

        // Convertir a números
        $valorOriginal = is_numeric($valorOriginal) ? (float) $valorOriginal : 0;
        $valorFinal = is_numeric($valorFinal) ? (float) $valorFinal : 0;

        if ($valorOriginal <= 0 || $valorFinal <= 0) {
            return '<div style="padding: 12px; background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; color: #92400E;">
                        <strong>⚠️ Advertencia:</strong> No se encontraron valores válidos para comparar
                    </div>';
        }

        // Calcular diferencia usando el servicio
        $comparison = $this->comparisonService->calculateDifference($valorOriginal, $valorFinal);

        $colorDiferencia = $comparison['is_positive'] ? '#059669' : '#DC2626';
        $iconoDiferencia = $comparison['is_positive'] ? '📈' : '📉';
        $textoDiferencia = $comparison['is_positive'] ? 'Mayor' : 'Menor';
        $contexto = $comparison['is_positive'] ? '(Contraoferta exitosa)' : '(Descuento aplicado)';

        return '<div style="padding: 16px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">VALOR ORIGINAL</div>
                            <div style="font-size: 18px; font-weight: 600; color: #374151;">$'.number_format($valorOriginal, 2).'</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">VALOR FINAL</div>
                            <div style="font-size: 18px; font-weight: 600; color: #374151;">$'.number_format($valorFinal, 2).'</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #6B7280; margin-bottom: 4px;">DIFERENCIA</div>
                            <div style="font-size: 18px; font-weight: 600; color: '.$colorDiferencia.';">'.$iconoDiferencia.' $'.number_format(abs($comparison['difference']), 2).'</div>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 8px; background: white; border-radius: 8px; border: 1px solid #E5E7EB;">
                        <span style="color: '.$colorDiferencia.'; font-weight: 600;">'.$textoDiferencia.' en '.number_format(abs($comparison['percentage']), 2).'%</span>
                        '.$contexto.'
                    </div>
                </div>';
    }
}
