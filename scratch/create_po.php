<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// 1. Find PT Krakatau Steel (Persero) Tbk
$supplier = Supplier::where('name', 'like', '%Krakatau Steel%')->first();
if (!$supplier) {
    echo "Error: PT Krakatau Steel (Persero) Tbk not found!\n";
    exit(1);
}
echo "Found Supplier: " . $supplier->name . " (Code: " . $supplier->code . ")\n";

// 2. Find Products
$sphc = Product::where('sku', 'COIL-HR-SPHC-2.0')->first();
$spcc = Product::where('sku', 'COIL-CR-SPCC-1.2')->first();

if (!$sphc || !$spcc) {
    echo "Error: Coils not found in database!\n";
    exit(1);
}

// 3. Find default warehouse
$warehouse = Warehouse::first() ?? Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH01']);

// 4. Generate PO Number
$orderDate = Carbon::now();
$expectedDate = Carbon::now()->addMonths(3); // September / October arrival

$poNumber = app(DocumentNumberService::class)->generate(
    'purchase_order',
    ['SUPP_CODE' => $supplier->code ?? 'KS'],
    $orderDate
);

DB::beginTransaction();
try {
    // 5. Create PO
    $po = PurchaseOrder::create([
        'po_number' => $poNumber,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => $orderDate,
        'expected_date' => $expectedDate,
        'status' => PurchaseOrder::STATUS_ORDERED, // Make it active/ordered so it counts as outstanding PO
        'currency' => 'IDR',
        'exchange_rate' => 1.0,
        'discount_percent' => 0.0,
        'tax_percent' => 11.0,
        'notes' => 'MAPU Import Planner Test PO for Steel Coils',
        'created_by' => 1, // System admin
    ]);

    // Item 1: SPHC (120 Ton / 120,000 kg)
    $sphcQty = 120000.00;
    $sphcPrice = $sphc->cost_price ?? 11500.00;
    $sphcSubtotal = $sphcQty * $sphcPrice;
    
    $po->items()->create([
        'product_id' => $sphc->id,
        'qty' => $sphcQty,
        'qty_received' => 0.00,
        'unit_id' => $sphc->unit_id,
        'unit_price' => $sphcPrice,
        'discount_percent' => 0.0,
        'subtotal' => $sphcSubtotal,
        'notes' => 'Imported SPHC Coil 2.0mm',
    ]);

    // Item 2: SPCC (80 Ton / 80,000 kg)
    $spccQty = 80000.00;
    $spccPrice = $spcc->cost_price ?? 13200.00;
    $spccSubtotal = $spccQty * $spccPrice;

    $po->items()->create([
        'product_id' => $spcc->id,
        'qty' => $spccQty,
        'qty_received' => 0.00,
        'unit_id' => $spcc->unit_id,
        'unit_price' => $spccPrice,
        'discount_percent' => 0.0,
        'subtotal' => $spccSubtotal,
        'notes' => 'Imported SPCC Coil 1.2mm',
    ]);

    // Update PO Totals
    $subtotal = $sphcSubtotal + $spccSubtotal;
    $taxAmount = $subtotal * 0.11;
    $total = $subtotal + $taxAmount;

    $po->update([
        'subtotal' => $subtotal,
        'tax_amount' => $taxAmount,
        'total' => $total
    ]);

    DB::commit();

    echo "Successfully created Purchase Order: " . $po->po_number . "\n";
    echo "Vendor: " . $supplier->name . "\n";
    echo "Item 1: " . $sphc->name . " -> Qty: " . number_format($sphcQty) . " kg\n";
    echo "Item 2: " . $spcc->name . " -> Qty: " . number_format($spccQty) . " kg\n";
    echo "Expected Date: " . $expectedDate->format('Y-m-d') . "\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error creating PO: " . $e->getMessage() . "\n";
}
