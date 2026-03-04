<?php
$output = file_get_contents('C:\tmp\hubspot-check-output.txt');
$allProperties = [];

// Try to parse the property names from the output
// The output has lines like:   📋 property_name
if (preg_match_all('/📋\s+([a-z0-9_]+)/u', $output, $matches)) {
    $allProperties = $matches[1];
}

$keywords = ['amount', 'monto', 'valor', 'precio', 'convenio', 'deal', 'total'];
foreach ($allProperties as $prop) {
    foreach ($keywords as $kw) {
        if (stripos($prop, $kw) !== false) {
            echo $prop . "\n";
            break;
        }
    }
}
