<?php

$logPath = __DIR__ . '/storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "Log file not found.\n";
    exit;
}

// Read whole file or last piece
$contents = file_get_contents($logPath);

// Find all occurrences of local.ERROR
preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] local\.ERROR:.*?(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/s', $contents, $matches);

if (!empty($matches[0])) {
    $lastError = end($matches[0]);
    echo "LATEST ERROR:\n\n";
    echo $lastError;
} else {
    echo "No errors found.";
}
