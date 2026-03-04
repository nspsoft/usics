<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SalesOrderImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        // Group rows by Customer Code + Customer PO + Order Date to create one SO per group
        $grouped = $rows->filter(function ($row) {
            return !empty($row['customer_code']) && !empty($row['order_date']) && !empty($row['product_code']);
        })->groupBy(function ($row) {
            return $row['customer_code'] . '|' . ($row['customer_po'] ?? '') . '|' . $row['order_date'];
        });

        foreach ($grouped as $key => $items) {
            try {
                $firstRow = $items->first();

                // Lookup Customer
                $customer = Customer::where('code', trim($firstRow['customer_code']))->first();
                if (!$customer) {
                    $this->errors[] = "Customer not found: {$firstRow['customer_code']}";
                    $this->skippedCount += $items->count();
                    continue;
                }

                // Lookup Warehouse
                $warehouseCode = trim($firstRow['warehouse_code'] ?? '');
                $warehouse = Warehouse::where('code', $warehouseCode)
                    ->orWhere('name', $warehouseCode)
                    ->first();
                if (!$warehouse) {
                    $this->errors[] = "Warehouse not found: {$warehouseCode}";
                    $this->skippedCount += $items->count();
                    continue;
                }

                DB::transaction(function () use ($firstRow, $items, $customer, $warehouse, $key) {
                    $existingSO = SalesOrder::where('customer_id', $customer->id)
                        ->where('customer_po_number', $firstRow['customer_po'] ?? null)
                        ->whereDate('order_date', $firstRow['order_date'])
                        ->first();

                    if ($existingSO) {
                        if (!$this->overwrite) {
                            $this->skippedCount += $items->count();
                            return; // Skip group
                        }

                        if (!in_array($existingSO->status, ['draft', 'waiting_po'])) {
                            $this->errors[] = "Cannot overwrite SO {$existingSO->so_number} because status is {$existingSO->status} for group {$key}";
                            $this->skippedCount += $items->count();
                            return;
                        }

                        // Overwrite: Update headers, delete old items
                        $existingSO->update([
                            'warehouse_id'     => $warehouse->id,
                            'notes'            => $firstRow['notes'] ?? null,
                            'updated_by'       => auth()->id(), // assuming updated_by exists or fallback
                        ]);
                        $existingSO->items()->delete();
                        $so = $existingSO;
                    } else {
                        // Create New
                        $so = SalesOrder::create([
                            'so_number'          => SalesOrder::generateSoNumber(),
                            'customer_po_number' => $firstRow['customer_po'] ?? null,
                            'customer_id'        => $customer->id,
                            'warehouse_id'       => $warehouse->id,
                            'order_date'         => $firstRow['order_date'],
                            'status'             => 'draft',
                            'discount_percent'   => 0,
                            'tax_percent'        => 11,
                            'notes'              => $firstRow['notes'] ?? null,
                            'created_by'         => auth()->id(),
                        ]);
                    }

                    foreach ($items as $row) {
                        $product = Product::where('sku', trim($row['product_code']))->first();
                        if (!$product) {
                            $this->errors[] = "Product not found: {$row['product_code']} (SO: {$so->so_number})";
                            $this->skippedCount++;
                            continue;
                        }

                        // Lookup unit by code or name, fallback to product default
                        $unitId = $product->unit_id;
                        if (!empty($row['unit_code'])) {
                            $unit = Unit::where('code', trim($row['unit_code']))
                                ->orWhere('name', trim($row['unit_code']))
                                ->first();
                            if ($unit) {
                                $unitId = $unit->id;
                            }
                        }

                        $so->items()->create([
                            'product_id'       => $product->id,
                            'qty'              => floatval($row['qty'] ?? 0),
                            'unit_id'          => $unitId,
                            'unit_price'       => floatval($row['unit_price'] ?? 0),
                            'discount_percent' => floatval($row['discount'] ?? $row['discount_percent'] ?? 0),
                        ]);
                    }

                    $so->calculateTotals();
                    $this->importedCount++;
                });
            } catch (\Exception $e) {
                Log::error("SalesOrderImport error for group {$key}: " . $e->getMessage());
                $this->errors[] = "Error importing group {$key}: " . $e->getMessage();
                $this->skippedCount += $items->count();
            }
        }
    }
}
