<?php
$file = 'c:/laragon/www/ERP/storage/logs/laravel.log';
if (!file_exists($file)) {
    echo "Log file not found.";
    exit;
}
$size = filesize($file);
$offset = max(0, $size - 10000); // Read last 10KB
$content = file_get_contents($file, false, null, $offset);

// Handle encoding if needed (e.g. UTF-16LE to UTF-8)
if (mb_detect_encoding($content, 'UTF-16LE', true)) {
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
}

echo "--- LOG START ---\n";
// Filter for Webhook or Error related to Whatsapp
$lines = explode("\n", $content);
foreach ($lines as $line) {
    if (str_contains($line, 'Webhook') || str_contains($line, 'Whatsapp') || str_contains($line, 'Wablas')) {
        echo $line . "\n";
    }
}
echo "--- LOG END ---\n";
