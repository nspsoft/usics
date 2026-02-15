<?php

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Http\Controllers\Sales\DeliveryOrderController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

$soNumber = 'SO/26/02/066';
$so = SalesOrder::where('so_number', $soNumber)->first();

if (!$so) {
    echo "SO not found.\n";
    exit;
}

$item = $so->items->first();
if (!$item) {
    echo "No items in SO.\n";
    exit;
}

echo "Testing Item: {$item->product->name}\n";
echo "Initial State:\n";
echo "  Qty: {$item->qty}\n";
echo "  Qty Delivered (Stored): {$item->qty_delivered}\n";
echo "  Reserved Qty (Attribute): {$item->reserved_qty}\n";
echo "  Remaining Qty (Attribute): {$item->remaining_qty}\n";

$initialRemaining = $item->remaining_qty;

// Create a dummy Draft DO
DB::beginTransaction();

try {
    echo "\n[1] Creating Draft DO (Qty=3)...\n";
    $do = DeliveryOrder::create([
        'do_number' => 'DUMMY-SHIP-TEST',
        'sales_order_id' => $so->id,
        'customer_id' => $so->customer_id,
        'warehouse_id' => $so->warehouse_id,
        'delivery_date' => now(),
        'status' => 'draft',
        'company_id' => 1,
        'shipping_address' => 'Test',
        'vehicle_number' => 'TEST',
        'driver_name' => 'TEST'
    ]);

    $doItem = DeliveryOrderItem::create([
        'delivery_order_id' => $do->id,
        'sales_order_item_id' => $item->id,
        'product_id' => $item->product_id,
        'unit_id' => $item->unit_id,
        'qty_ordered' => $item->qty,
        'qty_delivered' => 3, 
        'company_id' => 1
    ]);

    // Check State after Draft
    $item->load('deliveryOrderItems.deliveryOrder');
    $item->refresh(); // Refresh model from DB
    
    echo "  Status: {$do->status}\n";
    echo "  Qty Delivered (Stored): {$item->qty_delivered}\n";
    echo "  Reserved Qty: {$item->reserved_qty}\n";
    echo "  Remaining Qty: {$item->remaining_qty}\n";
    
    if ($item->remaining_qty == ($initialRemaining - 3)) {
        echo "  -> OK: Reduced by 3 (Reserved).\n";
    } else {
        echo "  -> FAIL: Not reduced by 3.\n";
    }

    echo "\n[2] Updating Status to SHIPPED...\n";
    
    // Simulate Controller Update
    $controller = new DeliveryOrderController();
    $request = new Request();
    $request->merge(['status' => 'shipped']);
    $request->validate(['status' => 'required']); // Mock validation

    // We can't call updateStatus directly due to RedirectResponse, so we mimic logic or use a helper
    // Let's call the logic directly to be sure
    // Logic from Controller:
    $oldStatus = $do->status;
    $newStatus = 'shipped';
    
    // deductStock equivalent
    // Check if deducted
    $wasDeducted = \App\Models\StockMovement::where('reference_type', get_class($do))
        ->where('reference_id', $do->id)
        ->where('type', \App\Models\StockMovement::TYPE_SO_DELIVERY)
        ->exists();

    if (!$wasDeducted) {
        foreach ($do->items as $dItem) {
            $sItem = $dItem->salesOrderItem;
            if ($sItem) {
                $sItem->increment('qty_delivered', $dItem->qty_delivered);
                echo "  -> Incrementing qty_delivered by {$dItem->qty_delivered}\n";
            }
            // Skip stock movement creation for this test to avoid complexity, focus on SO Item
        }
    }
    
    $do->status = 'shipped';
    $do->save();
    
    // Check State after Shipped
    $item->refresh();
    $item->load('deliveryOrderItems.deliveryOrder');
    
    echo "  Status: {$do->status}\n";
    echo "  Qty Delivered (Stored): {$item->qty_delivered}\n";
    echo "  Reserved Qty: {$item->reserved_qty}\n";
    echo "  Remaining Qty: {$item->remaining_qty}\n";

    $expectedRemaining = $initialRemaining - 3;
    if (abs($item->remaining_qty - $expectedRemaining) < 0.001) {
        if ($item->qty_delivered > 0 && $item->reserved_qty == 0) {
             echo "  -> OK: Reduced by 3 (Delivered).\n";
        } else {
             echo "  -> FAIL: Math correct but logic wrong (Reserved: {$item->reserved_qty}).\n";
        }
    } else {
        echo "  -> FAIL: Incorrect Remaining Qty.\n";
    }

    DB::rollBack();
    echo "\nRolled back dummy data.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
