<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cols = \Illuminate\Support\Facades\Schema::getColumnListing('sales_invoices');
print_r($cols);
$col = \Illuminate\Support\Facades\Schema::getConnection()->getDoctrineColumn('sales_invoices', 'sales_order_id');
echo 'Nullable: ' . ($col->getNotnull() ? 'False' : 'True') . "\n";
