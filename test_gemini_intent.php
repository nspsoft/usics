<?php

use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- START TEST ---\n";

// Test Logging
Log::info('Test Log Entry from Script ' . date('H:i:s'));
echo "Log entry written.\n";

// Instantiate Service
try {
    $gemini = app(GeminiService::class);
    echo "GeminiService Instantiated.\n";
} catch (\Exception $e) {
    echo "GeminiService Instantiation Failed: " . $e->getMessage() . "\n";
    exit;
}

// Test Intent Analysis
$message = "apa yg kamu bisa bantu ?";
echo "\nTesting Intent for message: \"$message\"\n";

$context = [
    'name' => 'Test User',
    'is_registered' => true,
    'has_orders' => false
];

$intent = $gemini->analyzeCustomerIntent($message, $context);
print_r($intent);

// Test FAQ Response if intent is unknown
echo "\nIntent: " . ($intent['intent'] ?? 'unknown') . "\n";

// Always test FAQ generation to verify API
echo "Testing FAQ Generation...\n";
$faq = $gemini->generateFAQResponse($message);
echo "FAQ Response: $faq\n";

echo "--- END TEST ---\n";
