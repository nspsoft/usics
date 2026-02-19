<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\EmailService;
use App\Models\Company;

echo "--- DEBUG EMAIL CONNECTION ---\n";

$company = Company::first();
$settings = $company->settings['email'] ?? null;

echo "Settings from DB:\n";
print_r($settings);

if (!$settings) {
    echo "ERROR: No settings found in DB.\n";
    exit(1);
}

try {
    echo "Attempting to fetch emails...\n";
    $service = app(EmailService::class);
    $count = $service->fetchEmails();
    echo "SUCCESS: Fetched $count emails.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
