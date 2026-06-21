<?php
use Illuminate\Support\Facades\DB;

echo "--- Direct DB Counts ---\n";
echo "journals count: " . DB::table('journals')->count() . "\n";
echo "journal_items count: " . DB::table('journal_items')->count() . "\n";
echo "coas count: " . DB::table('coas')->count() . "\n";

echo "\n--- Columns in journals ---\n";
try {
    $cols = DB::select("SHOW COLUMNS FROM journals");
    foreach ($cols as $col) {
        echo "{$col->Field} - {$col->Type}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Columns in journal_items ---\n";
try {
    $cols = DB::select("SHOW COLUMNS FROM journal_items");
    foreach ($cols as $col) {
        echo "{$col->Field} - {$col->Type}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Sample Journal (first 1 if exists) ---\n";
echo json_encode(DB::table('journals')->first(), JSON_PRETTY_PRINT) . "\n";

echo "\n--- Sample Journal Item (first 1) ---\n";
echo json_encode(DB::table('journal_items')->first(), JSON_PRETTY_PRINT) . "\n";
