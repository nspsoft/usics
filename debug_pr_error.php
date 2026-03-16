<?php

$logPath = __DIR__ . '/storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "Log file not found.\n";
    exit;
}

$lines = file($logPath);
$errors = [];
foreach ($lines as $i => $line) {
    if (strpos($line, 'local.ERROR:') !== false) {
        // Collect this line and the next 15 lines for context
        $errorBlock = array_slice($lines, $i, 15);
        $errors[] = implode("", $errorBlock);
    }
}

// Print the last 2 errors
if (empty($errors)) {
    echo "No errors found.\n";
} else {
    $lastErrors = array_slice($errors, -2);
    foreach ($lastErrors as $err) {
        echo "----------------------------------------\n";
        echo $err;
        echo "----------------------------------------\n";
    }
}
