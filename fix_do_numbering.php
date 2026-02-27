<?php

use App\Models\DeliveryOrder;
use App\Models\DocumentNumbering;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Finding max DO number...\n";

// Logic from controller to find max number
$maxNumber = 0;

// Try to find max number matching the pattern {number}/DO/JRI...
// Pattern: ^[0-9]+/DO/JRI-
if (DB::connection()->getDriverName() === 'mysql') {
    $lastDO = DeliveryOrder::where('do_number', 'REGEXP', '^[0-9]+/DO/JRI-')
        ->orderByRaw('CAST(SUBSTRING_INDEX(do_number, "/", 1) AS UNSIGNED) DESC')
        ->first();
} else {
    // Fallback for sqlite/others
    $lastDO = DeliveryOrder::where('do_number', 'like', '%/DO/JRI-%')
        ->orderByDesc('id') // Not perfect but approximation
        ->first();
}

if ($lastDO) {
    echo "Last DO found: " . $lastDO->do_number . "\n";
    $parts = explode('/', $lastDO->do_number, 2);
    $maxNumber = ctype_digit($parts[0]) ? (int) $parts[0] : 0;
} else {
    echo "No matching DOs found.\n";
}

echo "Max Number: $maxNumber\n";

$config = DocumentNumbering::where('code', 'delivery_order')->first();
if ($config) {
    $config->current_number = $maxNumber;
    $config->save();
    echo "Updated delivery_order current_number to $maxNumber\n";
} else {
    echo "Error: delivery_order config not found!\n";
}
