<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM agreements');
$output = "COLUMNS IN 'agreements':\n";
foreach ($columns as $col) {
    $output .= "- {$col->Field} ({$col->Type})\n";
}

file_put_contents('agreements_columns.txt', $output);
echo "Written to agreements_columns.txt\n";
