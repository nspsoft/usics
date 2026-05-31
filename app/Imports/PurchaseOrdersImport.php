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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseOrdersImport implements ToCollection, WithHeadingRow
{
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        $grouped = $rows->groupBy(function ($row) {
            $poNumber = trim($row['po_number'] ?? '');
            if ($poNumber !== '') {
                return 'PO_OVERWRITE:' . strtolower($poNumber);
            }

            $date = $this->parseDate($row['order_date']);
            return 'PO_CREATE:' . strtolower(trim($row['supplier_code'])) . '|' . strtolower(trim($row['warehouse_name'])) . '|' . $date;
        });

        foreach ($grouped as $key => $items) {
            $firstRow = $items->first();
            $isOverwrite = str_starts_with($key, 'PO_OVERWRITE:');
            
            try {
                DB::transaction(function () use ($firstRow, $items, $isOverwrite) {
                    $supplier = Supplier::where('code', $firstRow['supplier_code'])->first();
                    $warehouse = Warehouse::where('name', $firstRow['warehouse_name'])->first();

                    if (!$supplier || !$warehouse) return; // Skip if invalid supplier or warehouse

                    $orderDate = $this->parseDate($firstRow['order_date']);
                    $expectedDate = !empty($firstRow['expected_date']) ? $this->parseDate($firstRow['expected_date']) : null;
                    $status = $this->normalizeStatus($firstRow['status'] ?? null);
                    
                    $po = null;

                    if ($isOverwrite) {
                        $poNumber = trim($firstRow['po_number']);
                        $po = PurchaseOrder::where('po_number', $poNumber)->first();

                        if ($po) {
                            if (!$this->overwrite) {
                                Log::info("PurchaseOrdersImport: Skipping PO {$poNumber} because overwrite flag is false.");
                                return; // Stop transaction silently
                            }
                            if ($po->status !== 'draft') {
                                Log::warning("PurchaseOrdersImport: Cannot overwrite PO {$poNumber} because status is {$po->status}. Only draft POs can be amended via import.");
                                return; // Stop transaction silently
                            }

                            // Valid Overwrite Update Headings
                            $po->update([
                                'supplier_id' => $supplier->id,
                                'warehouse_id' => $warehouse->id,
                                'order_date' => $orderDate,
                                'expected_date' => $expectedDate,
                                'notes' => $firstRow['notes'] ?? null,
                            ]);
                            $po->items()->delete();
                        } else {
                            // PO Number provided but not found in DB - Fallback to Create behavior assigning requested PO Number
                            $po = PurchaseOrder::create([
                                'po_number' => $poNumber,
                                'supplier_id' => $supplier->id,
                                'warehouse_id' => $warehouse->id,
                                'order_date' => $orderDate,
                                'expected_date' => $expectedDate,
                                'status' => $status,
                                'notes' => $firstRow['notes'] ?? null,
                                'created_by' => auth()->id(),
                                'currency' => 'IDR', // Default
                                'exchange_rate' => 1,
                                'tax_percent' => 11, // Default PPN
                            ]);
                        }
                    } else {
                        $poNumber = PurchaseOrder::generatePoNumber($supplier, $orderDate);
                        $po = PurchaseOrder::create([
                            'po_number' => $poNumber,
                            'supplier_id' => $supplier->id,
                            'warehouse_id' => $warehouse->id,
                            'order_date' => $orderDate,
                            'expected_date' => $expectedDate,
                            'status' => $status,
                            'notes' => $firstRow['notes'] ?? null,
                            'created_by' => auth()->id(),
                            'currency' => 'IDR', // Default
                            'exchange_rate' => 1,
                            'tax_percent' => 11, // Default PPN
                        ]);
                    }

                    foreach ($items as $item) {
                        if (empty($item['product_code']) || empty($item['quantity']) || empty($item['unit_price'])) continue;

                        $product = Product::where('sku', $item['product_code'])->orWhere('code', $item['product_code'])->first();
                        
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

                    if ($po->status === 'ordered' && empty($po->approved_at)) {
                        $po->update([
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }
                });
            } catch (\Exception $e) {
                Log::error("PurchaseOrdersImport Error on key [{$key}]: " . $e->getMessage());
                continue;
            }
        }
    }

    private function normalizeStatus($value): string
    {
        $val = strtolower(trim((string) $value));
        if ($val === '') {
            return 'draft';
        }

        return in_array($val, ['draft', 'submitted', 'approved', 'ordered'], true) ? $val : 'draft';
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
