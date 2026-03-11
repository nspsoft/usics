<?php
try {
    $emailRecord = App\Models\EmailMessage::create([
        'message_id' => '<test-'.time().'@example.com>',
        'subject' => 'URGENT: Request for 100 Pipes',
        'from_address' => 'buyer@client.com',
        'from_name' => 'Budi Santoso',
        'to_address' => 'admin@jidoka.co.id',
        'body_html' => '<p>Selamat siang Tim Spindo,</p><p>Apakah ada stok untuk pipa baja 6 inch sebanyak 100 batang? Mohon dikirimkan segera besok ya karena proyek harus segera jalan.</p><p>Terima kasih.</p>',
        'body_text' => 'Selamat siang Tim Spindo,\nApakah ada stok untuk pipa baja 6 inch sebanyak 100 batang? Mohon dikirimkan segera besok ya karena proyek harus segera jalan.\nTerima kasih.',
        'status' => 'unread',
        'email_date' => now(),
    ]);

    // Test Gemini Analysis
    app(App\Services\EmailService::class)->analyzeEmail($emailRecord);
    dump("Created Test Email Record ID: " . $emailRecord->id);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
