<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

try {
    echo "Attempting to generate a simple PDF...\n";
    $pdf = Pdf::loadHTML('<h1>Test PDF</h1>');
    $output = $pdf->output();
    echo "PDF generated successfully. Size: " . strlen($output) . " bytes\n";
} catch (\Exception $e) {
    echo "ERROR generating PDF: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
