<?php
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\PurchaseOrder;

echo "Memulai seeding Purchase Invoice dummy...\n";

$supplier = Supplier::first();
if (!$supplier) {
    echo "Supplier tidak ditemukan!\n";
    exit(1);
}

// Find first PurchaseOrder or create a dummy if needed
$po = PurchaseOrder::first();
$poId = $po ? $po->id : null;

if (!$poId) {
    echo "Peringatan: Tidak ada Purchase Order di database, mencoba insert dengan purchase_order_id = null...\n";
}

try {
    $invoiceNumber = 'DEMO-PINV-' . rand(100, 999);
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
        'subtotal' => 12500000,
        'tax_percent' => 11,
        'tax_amount' => 1375000,
        'discount_total' => 0,
        'total_amount' => 13875000,
        'paid_amount' => 0,
        'created_by' => 1,
        'notes' => 'Purchase Invoice Dummy untuk Simulasi Rekonsiliasi Supplier',
    ]);
    echo "Berhasil membuat Purchase Invoice: {$invoice->invoice_number} senilai {$invoice->total_amount}\n";
} catch (\Exception $e) {
    echo "Gagal membuat Purchase Invoice: " . $e->getMessage() . "\n";
}
