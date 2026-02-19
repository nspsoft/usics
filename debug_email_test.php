<?php

use App\Models\EmailMessage;
use App\Models\Company;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- START DEBUGGING ---\n";

// 1. Check Company Settings
$company = Company::first();
if (!$company) {
    die("Error: No Company found.\n");
}
echo "Company settings found.\n";
print_r($company->settings['email'] ?? 'No email settings');

// 2. Try creating a DB record manually (to test schema)
try {
    echo "\nAttempting to create EmailMessage record...\n";
    $email = EmailMessage::create([
        'message_id' => '<test.' . time() . '@jidoka.co.id>',
        'subject' => 'Debug Test ' . time(),
        'from_address' => 'test@jidoka.co.id',
        'from_name' => 'Tester',
        'to_address' => 'recipient@example.com',
        'body_text' => 'This is a test.',
        'body_html' => '<p>This is a test.</p>',
        'status' => 'sent',
        'intent' => 'outgoing',
        'email_date' => now(),
    ]);
    echo "Success! Created Email ID: " . $email->id . "\n";
} catch (\Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}

// 3. Test EmailService Configuration (Mocking send)
try {
    echo "\nchecking EmailService...\n";
    $service = $app->make(EmailService::class);
    // We won't actually send to avoid spamming real emails unless necessary, 
    // but we can check if the config loading works.
    
    // Use reflection or just check if fetch works to verify IMAP at least
    echo "Attempting fetch (to verify IMAP connection)...\n";
    $count = $service->fetchEmails();
    echo "Fetch result: " . $count . " emails processed.\n";
    
} catch (\Exception $e) {
    echo "EmailService Error: " . $e->getMessage() . "\n";
}

echo "--- END DEBUGGING ---\n";
