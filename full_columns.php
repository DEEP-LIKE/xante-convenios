<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM agreements');
echo "COLUMNS IN 'agreements':\n";
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}
