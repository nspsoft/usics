<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppSetting;
use App\Models\PayrollSetting;

$appSettings = AppSetting::first();
echo "App Settings:\n";
print_r($appSettings ? $appSettings->toArray() : 'NULL');

$payrollSettings = PayrollSetting::first();
echo "\nPayroll Settings:\n";
print_r($payrollSettings ? $payrollSettings->toArray() : 'NULL');
