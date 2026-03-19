<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM agreements');
echo "Column | Type | Null | Key | Default | Extra\n";
echo str_repeat("-", 80) . "\n";
foreach ($columns as $col) {
    if ($col->Field === 'status') {
        echo "{$col->Field} | {$col->Type} | {$col->Null} | {$col->Key} | {$col->Default} | {$col->Extra}\n";
    }
}

$columns2 = DB::select('SHOW COLUMNS FROM quote_validations');
foreach ($columns2 as $col) {
    if ($col->Field === 'status') {
        echo "\nQuoteValidation status:\n";
        echo "{$col->Field} | {$col->Type} | {$col->Null} | {$col->Key} | {$col->Default} | {$col->Extra}\n";
    }
}
