<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliverySchedule;

$noProduct = DeliverySchedule::whereDoesntHave('product')->count();
$noCustomer = DeliverySchedule::whereDoesntHave('customer')->count();
$nullProductId = DeliverySchedule::whereNull('product_id')->count();
$nullCustomerId = DeliverySchedule::whereNull('customer_id')->count();

echo "Missing Product records: $noProduct\n";
echo "Null product_id records: $nullProductId\n";
echo "Missing Customer records: $noCustomer\n";
echo "Null customer_id records: $nullCustomerId\n";

if ($noCustomer > 0) {
    $samples = DeliverySchedule::whereDoesntHave('customer')->take(5)->get();
    foreach ($samples as $s) {
        echo "ID: {$s->id}, Customer ID: " . ($s->customer_id ?? 'NULL') . ", Product ID: {$s->product_id}\n";
    }
}
