<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Agreement;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$id = 21; // El convenio que el usuario reportó
$agreement = Agreement::find($id);

if (!$agreement) {
    // Buscar el último si el 21 no existe en este ambiente
    $agreement = Agreement::orderBy('id', 'desc')->first();
    echo "Agreement $id not found. Using latest ID: " . ($agreement ? $agreement->id : 'none') . "\n";
}

if ($agreement) {
    echo "Fixing Agreement #{$agreement->id}...\n";
    
    $toFloat = function ($value) {
        if (is_numeric($value)) return (float) $value;
        if (is_string($value)) {
            return (float) str_replace([',', '$', ' ', 'MXN'], '', $value);
        }
        return (float) ($value ?? 0);
    };

    $data = $agreement->wizard_data ?? [];
    
    $updates = [
        'valor_convenio' => $toFloat($data['valor_convenio'] ?? 0),
        'precio_promocion' => $toFloat($data['precio_promocion'] ?? 0),
        'comision_total' => $toFloat($data['comision_total_pagar'] ?? 0),
        'ganancia_final' => $toFloat($data['ganancia_final'] ?? 0),
        'monto_credito' => $toFloat($data['monto_credito'] ?? 0),
    ];

    // Si hay recálculos, usar el último para las columnas
    $latest = $agreement->recalculations()->latest()->first();
    if ($latest) {
        echo "Found recalculation #{$latest->recalculation_number}. Using its values.\n";
        $updates['valor_convenio'] = $latest->agreement_value;
        $updates['precio_promocion'] = $latest->proposal_value;
        $updates['comision_total'] = $latest->commission_total;
        $updates['ganancia_final'] = $latest->final_profit;
        
        // También asegurar que el recálculo tenga el valor correcto si estaba en 0
        if ($latest->agreement_value <= 0) {
            $latestVal = $latest->calculation_data['valor_convenio'] ?? 0;
            $latest->update(['agreement_value' => $toFloat($latestVal)]);
            echo "Updated recalculation record: $latestVal\n";
            $updates['valor_convenio'] = $latest->agreement_value;
        }
    }

    echo "Updating with: " . json_encode($updates) . "\n";
    $agreement->update($updates);
    echo "SUCCESS: Agreement updated.\n";
}
