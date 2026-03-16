<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pr = \App\Models\PurchaseRequest::first();
if (!$pr) {
    echo "No PR found. Exception unknown.";
    exit;
}

try {
    \Illuminate\Support\Facades\DB::transaction(function () use ($pr) {
        $pr->update([
            'notes' => 'Test update',
        ]);
        $pr->items()->delete();
        $pr->items()->create([
            'product_id' => \App\Models\Product::first()->id,
            'qty' => 1,
            'description' => 'Test',
        ]);
        throw new \Exception("Simulated exception just in case it passes. It passed!");
    });
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), "Simulated exception") === 0) {
        echo "The DB transaction code runs FINE. The issue is likely request validation or frontend mapping.";
    } else {
        echo "Exception Caught:\n";
        echo $e->getMessage() . "\n";
        echo $e->getTraceAsString();
    }
}
