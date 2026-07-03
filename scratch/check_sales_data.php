<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Customers count: " . \App\Models\Customer::count() . "\n";
echo "Sales Orders count: " . \App\Models\SalesOrder::count() . "\n";
echo "Quotations count: " . \App\Models\Quotation::count() . "\n";
echo "Sales Forecasts count: " . \DB::table('sales_forecasts')->count() . "\n";
