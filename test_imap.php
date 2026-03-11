<?php
try {
    $svc = app(App\Services\EmailService::class);
    $start = microtime(true);
    $count = $svc->fetchEmails();
    echo "SUCCESS: Fetched {$count} emails (took " . round(microtime(true) - $start, 2) . "s)\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
