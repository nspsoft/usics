<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\HR\JobPosting;
use App\Models\HR\OkrObjective;

echo "Job Postings Count: " . JobPosting::count() . "\n";
echo "OKR Objectives Count: " . OkrObjective::count() . "\n";
