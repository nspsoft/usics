<?php
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\PurchaseOrder;

$po = PurchaseOrder::first();
$poId = $po ? $po->id : null;

$suppliers = Supplier::whereIn('id', [2, 3])->get();

foreach ($suppliers as $supplier) {
    $invoiceNumber = 'DEMO-PINV-' . ($supplier->id == 2 ? '308' : '309');
    $amount = $supplier->id == 2 ? 24500000 : 8620000;
    try {
        $invoice = PurchaseInvoice::create([
            'company_id' => 1,
            'invoice_number' => $invoiceNumber,
            'purchase_order_id' => $poId,
            'supplier_id' => $supplier->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'unpaid',
            'currency' => 'IDR',
            'exchange_rate' => 1.0,
            'subtotal' => $amount / 1.11,
            'tax_percent' => 11,
            'tax_amount' => $amount - ($amount / 1.11),
            'discount_total' => 0,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'created_by' => 1,
            'notes' => 'Purchase Invoice Dummy untuk Simulasi Rekonsiliasi Supplier',
        ]);
        echo "Berhasil membuat Purchase Invoice: {$invoice->invoice_number} senilai {$invoice->total_amount} untuk {$supplier->name}\n";
    } catch (\Exception $e) {
        echo "Gagal membuat Purchase Invoice: " . $e->getMessage() . "\n";
    }
}
