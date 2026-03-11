<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$file = $argv[1] ?? 'delivery_orders_import_template.xlsx'; // default if no argument
echo "Importing: $file\n";

$import = new \App\Imports\SalesOrderImport(true); // pass true for overwrite
\Maatwebsite\Excel\Facades\Excel::import($import, $file);

echo "Imported: " . $import->importedCount . "\n";
echo "Updated: " . $import->updatedCount . "\n";
echo "Skipped: " . $import->skippedCount . "\n";
echo "Errors: \n";
print_r($import->errors);
