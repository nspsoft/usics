<?php

namespace App\Imports;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesInvoice;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesInvoiceImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Group rows by SO Number
        $grouped = $rows->groupBy('so_number');

        foreach ($grouped as $soNumber => $items) {
            try {
                if (empty($soNumber)) continue;

                $so = SalesOrder::with('customer')
                    ->where('so_number', $soNumber)
                    ->first();

                if (!$so) {
                    Log::warning("SalesInvoiceImport: SO [{$soNumber}] not found. Skipping.");
                    continue;
                }

                DB::transaction(function () use ($so, $items) {
                    $firstRow = $items->first();

                    $invoice = SalesInvoice::create([
                        'invoice_number' => SalesInvoice::generateInvoiceNumber($so->customer, $firstRow['invoice_date'] ?? null),
                        'sales_order_id' => $so->id,
                        'customer_id' => $so->customer_id,
                        'invoice_date' => $firstRow['invoice_date'] ?? now()->toDateString(),
                        'due_date' => $firstRow['due_date'] ?? now()->addDays(30)->toDateString(),
                        'status' => 'draft',
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'discount_amount' => 0,
                        'total' => 0,
                        'paid_amount' => 0,
                        'balance' => 0,
                        'notes' => $firstRow['notes'] ?? null,
                    ]);

                    foreach ($items as $row) {
                        $product = Product::where('sku', $row['product_code'])->first();
                        if (!$product) {
                            Log::warning("SalesInvoiceImport: Product [{$row['product_code']}] not found. Skipping item.");
                            continue;
                        }

                        // Find matching SO item
                        $soItem = SalesOrderItem::where('sales_order_id', $so->id)
                            ->where('product_id', $product->id)
                            ->first();

                        $invoice->items()->create([
                            'sales_order_item_id' => $soItem?->id,
                            'product_id' => $product->id,
                            'qty' => floatval($row['qty'] ?? 0),
                            'unit_id' => $soItem?->unit_id ?? $product->unit_id,
                            'unit_price' => floatval($row['unit_price'] ?? $soItem?->unit_price ?? 0),
                            'discount_percent' => floatval($row['discount'] ?? 0),
                        ]);
                    }

                    // Recalculate totals
                    $invoice->calculateTotals();
                });
            } catch (\Exception $e) {
                Log::error("SalesInvoiceImport: Error processing SO [{$soNumber}]: " . $e->getMessage());
            }
        }
    }
}
