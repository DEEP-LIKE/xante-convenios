<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Agreement;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$agreement = Agreement::find(21);
if (!$agreement) {
    echo "Agreement 21 not found.\n";
    // Try to find by index if ID 21 is not the ID but some other field
    $agreement = Agreement::orderBy('id', 'desc')->first();
    echo "Latest Agreement ID: " . ($agreement ? $agreement->id : 'none') . "\n";
}

if ($agreement) {
    echo "ID: " . $agreement->id . "\n";
    echo "Status: " . $agreement->status . "\n";
    echo "Valor Convenio Column: " . $agreement->valor_convenio . "\n";
    echo "Wizard Data [valor_convenio]: " . ($agreement->wizard_data['valor_convenio'] ?? 'MISSING') . "\n";
    echo "Current Financials: " . json_encode($agreement->current_financials) . "\n";
    
    echo "\nRecalculations:\n";
    foreach ($agreement->recalculations as $recalc) {
        echo "- #{$recalc->recalculation_number}: Val: {$recalc->agreement_value}, Data: " . json_encode($recalc->calculation_data) . "\n";
    }
}
