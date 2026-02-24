<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseOrdersImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy(function ($row) {
            $date = $this->parseDate($row['order_date']);
            return strtolower(trim($row['supplier_code'])) . '|' . strtolower(trim($row['warehouse_name'])) . '|' . $date;
        });

        foreach ($grouped as $key => $items) {
            $firstRow = $items->first();
            
            try {
                DB::transaction(function () use ($firstRow, $items) {
                    $supplier = Supplier::where('code', $firstRow['supplier_code'])->first();
                    $warehouse = Warehouse::where('name', $firstRow['warehouse_name'])->first();

                    if (!$supplier || !$warehouse) return; // Skip if invalid supplier or warehouse

                    $orderDate = $this->parseDate($firstRow['order_date']);
                    $expectedDate = !empty($firstRow['expected_date']) ? $this->parseDate($firstRow['expected_date']) : null;

                    $rawPoNumber = $firstRow['po_number'] ?? null;
                    if ($rawPoNumber && trim($rawPoNumber) !== '') {
                        $poNumber = trim($rawPoNumber);
                    } else {
                        $poNumber = PurchaseOrder::generatePoNumber();
                    }

                    $po = PurchaseOrder::create([
                        'po_number' => $poNumber,
                        'supplier_id' => $supplier->id,
                        'warehouse_id' => $warehouse->id,
                        'order_date' => $orderDate,
                        'expected_date' => $expectedDate,
                        'status' => 'draft',
                        'notes' => $firstRow['notes'] ?? null,
                        'created_by' => auth()->id(),
                        'currency' => 'IDR', // Default
                        'exchange_rate' => 1,
                        'tax_percent' => 11, // Default PPN
                    ]);

                    foreach ($items as $item) {
                        if (empty($item['product_code']) || empty($item['quantity']) || empty($item['unit_price'])) continue;

                        $product = Product::where('code', $item['product_code'])->first();
                        
                        if ($product) {
                            $qty = (float) $item['quantity'];
                            $price = (float) $item['unit_price'];
                            $discount = isset($item['discount']) ? (float) $item['discount'] : 0;

                            $po->items()->create([
                                'product_id' => $product->id,
                                'qty' => $qty,
                                'unit_id' => $product->unit_id,
                                'unit_price' => $price,
                                'discount_percent' => $discount,
                            ]);
                        }
                    }

                    $po->calculateTotals();
                });
            } catch (\Exception $e) {
                // Log error or continue
                continue;
            }
        }
    }

    private function parseDate($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $ex) {
                return now()->format('Y-m-d');
            }
        }
    }
}
