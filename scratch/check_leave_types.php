<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HR\LeaveType;
use App\Models\HR\LeaveBalance;

echo "Leave Types Count: " . LeaveType::count() . "\n";
foreach (LeaveType::all() as $lt) {
    echo "ID: {$lt->id} | Name: {$lt->name} | Max Days: {$lt->max_days}\n";
}

echo "\nLeave Balances Count: " . LeaveBalance::count() . "\n";
