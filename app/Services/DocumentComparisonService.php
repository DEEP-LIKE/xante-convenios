<?php

namespace App\Services;

/**
 * Servicio para realizar cálculos de comparación de valores
 * 
 * Responsabilidades:
 * - Calcular diferencias entre valores
 * - Calcular porcentajes de diferencia
 * - Determinar si la diferencia es positiva o negativa
 */
class DocumentComparisonService
{
    /**
     * Calcula la diferencia entre dos valores y retorna información completa
     */
    public function calculateDifference(float $original, float $final): array
    {
        $difference = $final - $original;
        $percentage = $this->getPercentageDifference($original, $final);
        $isPositive = $this->isPositiveDifference($difference);

        return [
            'original' => $original,
            'final' => $final,
            'difference' => $difference,
            'percentage' => $percentage,
            'is_positive' => $isPositive,
        ];
    }

    /**
     * Calcula el porcentaje de diferencia entre dos valores
     */
    public function getPercentageDifference(float $original, float $final): float
    {
        if ($original == 0) {
            return 0;
        }

        $difference = $final - $original;
        return ($difference / $original) * 100;
    }

    /**
     * Determina si la diferencia es positiva
     */
    public function isPositiveDifference(float $difference): bool
    {
        return $difference >= 0;
    }

    /**
     * Formatea un valor monetario
     */
    public function formatCurrency(float $value): string
    {
        return '$' . number_format($value, 2);
    }

    /**
     * Parsea un valor monetario (elimina símbolos y comas)
     */
    public function parseCurrency(string $value): float
    {
        return (float) str_replace(['$', ','], '', $value);
    }
}
