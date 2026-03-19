<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Agreement;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$id = 21; // El convenio que el usuario reportó
$agreement = Agreement::find($id);

if (!$agreement) {
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
    
    // USAR NOMBRES DE COLUMNA CORRECTOS (INGLÉS)
    $updates = [
        'agreement_value' => $toFloat($data['valor_convenio'] ?? 0),
        'proposal_value' => $toFloat($data['precio_promocion'] ?? 0),
        'commission_total' => $toFloat($data['comision_total_pagar'] ?? 0),
        'final_profit' => $toFloat($data['ganancia_final'] ?? 0),
    ];

    $latest = $agreement->recalculations()->latest()->first();
    if ($latest) {
        echo "Found recalculation #{$latest->recalculation_number}.\n";
        $updates['agreement_value'] = $latest->agreement_value;
        $updates['proposal_value'] = $latest->proposal_value;
        $updates['commission_total'] = $latest->commission_total;
        $updates['final_profit'] = $latest->final_profit;
    }

    echo "Updating Agreement #{$agreement->id} with: " . json_encode($updates) . "\n";
    $success = $agreement->update($updates);
    
    if ($success) {
        echo "SUCCESS: Agreement updated.\n";
        echo "Current values: " . json_encode($agreement->fresh()->only(['agreement_value', 'proposal_value', 'commission_total', 'final_profit'])) . "\n";
    } else {
        echo "FAILED: Could not update agreement.\n";
    }
}
