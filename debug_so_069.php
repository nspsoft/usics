<?php

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrderItem;

$soNumber = 'SO/26/02/069';
$so = SalesOrder::where('so_number', $soNumber)->first();

if (!$so) {
    echo "SO Not Found: $soNumber\n";
    exit;
}

echo "SO ID: {$so->id}\n";
echo "SO Number: {$so->so_number}\n";
echo "--------------------------------------------------\n";

foreach ($so->items as $item) {
    echo "Item ID: {$item->id} | Product: {$item->product->name} ({$item->product->sku})\n";
    echo "  Qty Ordered:   " . number_format($item->qty, 2) . "\n";
    echo "  Qty Delivered (SO Item Cache): " . number_format($item->qty_delivered, 2) . "\n";
    echo "  Qty Reserved:  " . number_format($item->reserved_qty, 2) . "\n";
    echo "  Qty Remaining: " . number_format($item->remaining_qty, 2) . "\n";
    
    echo "  --- Linked Delivery Order Items ---\n";
    
    $doItems = DeliveryOrderItem::where('sales_order_item_id', $item->id)
        ->with('deliveryOrder')
        ->get();
        
    $sumDo = 0;
    foreach ($doItems as $doi) {
        $status = $doi->deliveryOrder->status;
        $doNum = $doi->deliveryOrder->do_number;
        echo "    DO: $doNum | Status: $status | Qty: " . number_format($doi->qty_delivered, 2) . "\n";
        
        if ($status !== 'cancelled') {
            $sumDo += $doi->qty_delivered;
        }
    }
    
    echo "  --- Summary ---\n";
    echo "  Sum of Valid DOs: " . number_format($sumDo, 2) . "\n";
    
    if (abs($sumDo - $item->qty_delivered) > 0.001) {
        echo "  [MISMATCH DETECTED] SO Cache ($item->qty_delivered) != DO Sum ($sumDo)\n";
    } else {
        echo "  [MATCH] Data is consistent.\n";
    }
    echo "==================================================\n";
}
