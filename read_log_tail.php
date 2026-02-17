<?php
$file = 'c:/laragon/www/ERP/storage/logs/laravel.log';
if (!file_exists($file)) {
    echo "Log file not found.";
    exit;
}
$lines = [];
$fp = fopen($file, "r");
fseek($fp, -500000, SEEK_END); // Go back 500KB
$data = fread($fp, 500000);
fclose($fp);

// Simple clean of null bytes if any
$data = str_replace("\0", "", $data);

echo "--- RAW LOG TAIL ---\n";
echo $data;
echo "--- END RAW LOG TAIL ---\n";
