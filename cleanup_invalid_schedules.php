<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliverySchedule;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $invalidSchedules = DeliverySchedule::whereDoesntHave('product')
        ->orWhereNull('product_id');
    
    $count = $invalidSchedules->count();
    $invalidSchedules->delete();
    
    DB::commit();
    echo "Successfully deleted {$count} invalid delivery schedules.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
