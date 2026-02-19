<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliverySchedule;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    // Check for missing products
    $noProductSchedules = DeliverySchedule::whereDoesntHave('product')
        ->orWhereNull('product_id');
    $countProd = $noProductSchedules->count();
    $noProductSchedules->delete();

    // Check for missing customers
    $noCustomerSchedules = DeliverySchedule::whereDoesntHave('customer')
        ->orWhereNull('customer_id');
    $countCust = $noCustomerSchedules->count();
    $noCustomerSchedules->delete();
    
    DB::commit();
    echo "Successfully deleted {$countProd} schedules with missing products.\n";
    echo "Successfully deleted {$countCust} schedules with missing customers.\n";
    echo "Total deleted: " . ($countProd + $countCust) . ".\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
