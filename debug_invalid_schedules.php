<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliverySchedule;

$invalidSchedules = DeliverySchedule::whereDoesntHave('product')
    ->orWhereNull('product_id')
    ->get(['id', 'product_id', 'customer_id', 'delivery_date']);

echo "Total invalid schedules found: " . $invalidSchedules->count() . "\n";
foreach ($invalidSchedules as $sch) {
    echo "ID: {$sch->id}, Product ID: " . ($sch->product_id ?? 'NULL') . ", Customer ID: {$sch->customer_id}, Date: {$sch->delivery_date}\n";
}
