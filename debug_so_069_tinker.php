<?php
$so = App\Models\SalesOrder::where('so_number', 'SO/26/02/069')->first();
if (!$so) { echo "SO Not Found\n"; exit; }

foreach ($so->items as $item) {
    echo "ITEM ID: " . $item->id . "\n";
    echo "ITEM: " . $item->product->name . "\n";
    echo "CACHE_DELIVERED: " . $item->qty_delivered . "\n";
    echo "CACHE_RESERVED: " . $item->reserved_qty . "\n";
    echo "CACHE_REMAINING: " . $item->remaining_qty . " (Accessor)\n";
    
    $dos = App\Models\DeliveryOrderItem::where('sales_order_item_id', $item->id)
        ->whereHas('deliveryOrder', function($q) {
            $q->where('status', '!=', 'cancelled');
        })->get();
        
    $sum = 0;
    foreach($dos as $d) {
        echo "DO_ITEM: " . $d->deliveryOrder->do_number . " (Status: " . json_encode($d->deliveryOrder->status) . ") QTY: " . $d->qty_delivered . "\n";
        $sum += $d->qty_delivered;
    }
    
    echo "REAL_SUM: " . $sum . "\n";
    echo "MISMATCH: " . ($item->qty_delivered != $sum ? 'YES' : 'NO') . "\n";
}
