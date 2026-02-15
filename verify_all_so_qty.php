<?php

use App\Models\SalesOrderItem;
use App\Models\DeliveryOrderItem;

echo "VERIFICATION REPORT: Sales Order Delivery Quantities\n";
echo "--------------------------------------------------\n";

$items = SalesOrderItem::with('deliveryOrderItems.deliveryOrder', 'product', 'salesOrder')->get();
$mismatches = 0;
$checked = 0;

foreach ($items as $item) {
    if (!$item->salesOrder) continue; // Skip orphaned items
    
    $checked++;
    
    // Calculate distinct delivered qty from VALID delivery orders
    $actualDelivered = $item->deliveryOrderItems
        ->filter(function ($doItem) {
            return $doItem->deliveryOrder && 
                   in_array($doItem->deliveryOrder->status, ['shipped', 'delivered', 'completed']);
        })
        ->sum('qty_delivered');
        
    // Compare with stored value
    // Use slightly larger epsilon/precision handling just in case, but strict enough
    if (abs($item->qty_delivered - $actualDelivered) > 0.0001) {
        $mismatches++;
        echo "MISMATCH FOUND!\n";
        echo "SO: {$item->salesOrder->so_number} | Item: {$item->product->name}\n";
        echo "Stored Qty: {$item->qty_delivered} | Actual Item Sum: $actualDelivered\n";
        echo "--------------------------------------------------\n";
    }
}

echo "Verification Complete.\n";
echo "Total Items Checked: $checked\n";
echo "Mismatches Found: $mismatches\n";

if ($mismatches === 0) {
    echo "STATUS: ALL DATA IS CONSISTENT.\n";
} else {
    echo "STATUS: WARNING - DATA INCONSISTENCIES REMAIN.\n";
}
