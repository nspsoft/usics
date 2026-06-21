<?php
use App\Models\PurchaseInvoice;
use App\Models\Supplier;

$allCount = PurchaseInvoice::count();
$statusCounts = PurchaseInvoice::select('status', \DB::raw('count(*) as count'))->groupBy('status')->get();
$firstFew = PurchaseInvoice::with('supplier')->take(5)->get()->map(function($inv) {
    return [
        'id' => $inv->id,
        'invoice_number' => $inv->invoice_number,
        'status' => $inv->status,
        'total_amount' => $inv->total_amount,
        'paid_amount' => $inv->paid_amount,
        'supplier' => $inv->supplier->name ?? null
    ];
});

echo json_encode([
    'total_count' => $allCount,
    'status_counts' => $statusCounts,
    'first_few' => $firstFew,
    'suppliers' => Supplier::take(3)->get(['id', 'name', 'code'])
], JSON_PRETTY_PRINT);
