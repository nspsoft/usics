<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$so = \App\Models\SalesOrder::where('so_number', 'SO/26/03/043')->first();
if (!$so) {
    echo "SO not found\n";
    exit;
}

echo "SO Found: " . $so->so_number . "\n";
foreach ($so->items as $item) {
    echo "- " . $item->product->name . " -> Ordered: {$item->qty}, Delivered: {$item->qty_delivered}, Invoiced: {$item->qty_invoiced}\n";
}

echo "\nInvoices linked to this SO by items:\n";
$invoices = \App\Models\SalesInvoice::whereHas('items', function($q) use ($so) {
    $q->whereIn('sales_order_item_id', $so->items->pluck('id'));
})->with('items')->get();

foreach ($invoices as $inv) {
    echo "Invoice #{$inv->invoice_number} (Status: {$inv->status}, Date: {$inv->invoice_date})\n";
    foreach ($inv->items as $invItem) {
        // filter out other SO's items if this invoice mixes things
        if (in_array($invItem->sales_order_item_id, $so->items->pluck('id')->toArray())) {
            echo "   -> Billed: {$invItem->qty} of {$invItem->description}\n";
        }
    }
}
