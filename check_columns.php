<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Illuminate\Support\Facades\Schema::getColumnListing('agreements');
echo "Columns in agreements table:\n";
foreach ($columns as $column) {
    echo "- $column\n";
}
