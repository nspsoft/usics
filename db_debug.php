<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$msg = \App\Models\WhatsappMessage::latest()->first();
if ($msg) {
    print_r($msg->toArray());
} else {
    echo "No messages found.\n";
}
