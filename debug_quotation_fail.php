<?php

use App\Services\WhatsappBotService;
use App\Models\Customer;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = $app->make(WhatsappBotService::class);

// The exact message from user screenshot
$message = "Minta penawaran *CARTON BOX K-69*";
$phone = "6285888883258"; // From screenshot

echo "Testing message: \"{$message}\" from {$phone}\n";

// Use Reflection for protected methods
$reflection = new ReflectionClass($service);

// 1. Find Customer
$findCustomer = $reflection->getMethod('findCustomerByPhone');
$findCustomer->setAccessible(true);
$customer = $findCustomer->invoke($service, $phone);

if (!$customer) {
    echo "WARNING: Customer not found for this phone number.\n";
    $customer = Customer::first();
}

if ($customer) {
    echo "Using Customer: {$customer->name} (ID: {$customer->id})\n";
}

// 2. Test Intent Analysis
$gemini = $app->make(App\Services\GeminiService::class);
$context = [
    'name' => $customer ? $customer->name : 'Test User',
    'is_registered' => (bool) $customer,
    'has_orders' => false,
];

echo "\n--- Step 1: Intent Analysis ---\n";
$intent = $gemini->analyzeCustomerIntent($message, $context);
echo "Resulting Intent: " . json_encode($intent, JSON_PRETTY_PRINT) . "\n";

// 3. Test handleRequestQuotation Logic
echo "\n--- Step 2: Handle Request Quotation ---\n";
$handleQuotation = $reflection->getMethod('handleRequestQuotation');
$handleQuotation->setAccessible(true);

try {
    $response = $handleQuotation->invoke($service, $customer, $intent['parameters'] ?? []);
    echo "Bot Response: \"{$response}\"\n";
} catch (\Exception $e) {
    echo "ERROR in handleRequestQuotation: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
