<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

echo "--- TABLE STRUCTURE ---\n";
$columns = DB::select('DESCRIBE agreements');
foreach ($columns as $col) {
    echo "{$col->Field} | {$col->Type} | {$col->Default}\n";
}

echo "\n--- SAMPLE DATA ---\n";
$agreements = DB::table('agreements')->select('id', 'status')->orderBy('id', 'desc')->limit(10)->get();
foreach ($agreements as $ag) {
    echo "ID {$ag->id}: {$ag->status}\n";
}
