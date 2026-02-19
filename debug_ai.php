<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\EmailService;
use App\Models\EmailMessage;

echo "--- DEBUG AI ANALYSIS ---\n";

$email = EmailMessage::latest()->first();

if (!$email) {
    echo "ERROR: No email found in DB to analyze.\n";
    exit(1);
}

echo "Analyzing Email: {$email->subject} (ID: {$email->id})\n";

try {
    app(EmailService::class)->analyzeEmail($email);
    echo "Analysis Complete.\n";
    
    $email->refresh();
    echo "Intent: " . $email->intent . "\n";
    echo "Sentiment: " . $email->sentiment . "\n";
    echo "Metadata: " . json_encode($email->ai_metadata) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
