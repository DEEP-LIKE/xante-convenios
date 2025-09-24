<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Agreement;

echo "Limpiando Agreement 77 para testing...\n";

$agreement = Agreement::find(77);
if ($agreement) {
    $agreement->update([
        'wizard_data' => [],
        'current_step' => 1
    ]);
    echo "✅ Agreement 77 limpiado - wizard_data: [] - current_step: 1\n";
} else {
    echo "❌ Agreement 77 no encontrado\n";
}
