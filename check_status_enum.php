<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM agreements LIKE "status"');
echo "Status column details:\n";
print_r($columns);

$agreements = DB::table('agreements')->select('id', 'status')->orderBy('id', 'desc')->limit(5)->get();
echo "\nLast 5 agreements status:\n";
print_r($agreements);
