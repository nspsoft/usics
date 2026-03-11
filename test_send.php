<?php
try {
    $sentEmail = App\Models\EmailMessage::create([
        'message_id' => '<' . time() . '.' . uniqid() . '@' . request()->getHost() . '>',
        'subject' => 'Test Subject',
        'from_address' => 'admin@jidoka.co.id',
        'from_name' => 'Admin',
        'to_address' => 'tester@example.com',
        'body_text' => 'Test body text',
        'body_html' => '<p>Test body html</p>',
        'status' => 'sent',
        'intent' => 'outgoing',
        'email_date' => now(),
    ]);
    echo "SUCCESS: Email Message created with ID: " . $sentEmail->id . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
