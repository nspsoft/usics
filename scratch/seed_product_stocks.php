<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;

$warehouse = Warehouse::where('code', 'WH-FG')->first() ?? Warehouse::first();
$products = Product::whereDoesntHave('stocks')->get();

echo "Seeding stocks for " . $products->count() . " products...\n";

foreach ($products as $product) {
    ProductStock::create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'qty_on_hand' => rand(100, 1000),
        'qty_reserved' => 0,
        'qty_incoming' => 0,
        'qty_outgoing' => 0,
        'avg_cost' => $product->cost_price > 0 ? $product->cost_price : rand(10000, 50000),
    ]);
    echo "Seeded stock for product: " . $product->sku . "\n";
}

echo "Seeding completed!\n";
