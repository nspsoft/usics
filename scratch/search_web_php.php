<?php
$content = file_get_contents('routes/web.php');
$lines = explode("\n", $content);
$keywords = ['finance', 'reconciliation', 'command', 'FinancialCommand'];

foreach ($lines as $idx => $line) {
    foreach ($keywords as $kw) {
        if (stripos($line, $kw) !== false) {
            echo "Line " . ($idx + 1) . " [$kw]: " . trim($line) . "\n";
        }
    }
}
