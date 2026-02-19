<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EmailMessage;
use Carbon\Carbon;

echo "--- CREATING DUMMY PO EMAIL ---\n";

try {
    $email = EmailMessage::create([
        'message_id' => uniqid('test_') . '@example.com',
        'subject' => 'Purchase Order PO-TEST-001',
        'from_address' => 'buyer@client.com',
        'from_name' => 'Client Procurement',
        'body_text' => "Dear Team,\n\nPlease process the attached Purchase Order PO-TEST-001.\nWe need the goods by next week.\n\nRegards,\nProcurement Team",
        'body_html' => "<p>Dear Team,</p><p>Please process the attached Purchase Order <strong>PO-TEST-001</strong>.</p><p>We need the goods by next week.</p><p>Regards,<br>Procurement Team</p>",
        'email_date' => Carbon::now(),
        'status' => 'unread'
    ]);
    
    echo "SUCCESS: Created dummy email with ID: {$email->id}\n";
    echo "Subject: {$email->subject}\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
