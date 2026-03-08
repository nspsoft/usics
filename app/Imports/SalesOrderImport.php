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
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        // Separate rows: those with SO Number (update mode) vs without (create mode)
        $updateRows = $rows->filter(fn($row) => !empty($row['so_number']));
        $createRows = $rows->filter(fn($row) => empty($row['so_number']));

        // ── UPDATE MODE: Update existing SOs by SO Number ──
        if ($updateRows->isNotEmpty()) {
            $grouped = $updateRows->groupBy('so_number');

            foreach ($grouped as $soNumber => $items) {
                try {
                    $so = SalesOrder::withCount('invoices')->where('so_number', trim($soNumber))->first();
                    if (!$so) {
                        $this->errors[] = "SO not found: {$soNumber}";
                        $this->skippedCount += $items->count();
                        continue;
                    }

                    if ($so->status === 'cancelled') {
                        $this->errors[] = "Cannot update cancelled SO: {$soNumber}";
                        $this->skippedCount += $items->count();
                        continue;
                    }

                    $hasInvoices = $so->invoices_count > 0;

                    DB::transaction(function () use ($so, $items, $hasInvoices) {
                        $firstRow = $items->first();

                        // Update SO-level fields if provided
                        $soUpdates = [];
                        if (!empty($firstRow['order_date'])) {
                            $soUpdates['order_date'] = $firstRow['order_date'];
                        }
                        if (!empty($firstRow['delivery_date'])) {
                            $soUpdates['delivery_date'] = $firstRow['delivery_date'];
                        }
                        if (!empty($firstRow['notes'])) {
                            $soUpdates['notes'] = $firstRow['notes'];
                        }
                        if (!empty($firstRow['customer_po'])) {
                            $soUpdates['customer_po_number'] = $firstRow['customer_po'];
                        }
                        if (!empty($soUpdates)) {
                            $so->update($soUpdates);
                        }

                        // Update item-level fields (match by product SKU)
                        // If SO has invoices, block price/qty changes to prevent inconsistency
                        if ($hasInvoices) {
                            $this->errors[] = "SO {$so->so_number} sudah memiliki Invoice. Hanya Order Date, Delivery Date, PO, dan Notes yang diupdate. Harga & qty tidak diubah.";
                        }

                        foreach ($items as $row) {
                            if (empty($row['product_code'])) continue;
                            if ($hasInvoices) continue; // Skip item updates if has invoices

                            $product = Product::where('sku', trim($row['product_code']))->first();
                            if (!$product) {
                                $this->errors[] = "Product not found: {$row['product_code']} (SO: {$so->so_number})";
                                $this->skippedCount++;
                                continue;
                            }

                            $soItem = $so->items()->where('product_id', $product->id)->first();
                            if ($soItem) {
                                // Update existing item
                                $itemUpdates = [];
                                if (isset($row['unit_price']) && $row['unit_price'] !== '' && $row['unit_price'] !== null) {
                                    $itemUpdates['unit_price'] = floatval($row['unit_price']);
                                }
                                if (isset($row['qty']) && $row['qty'] !== '' && $row['qty'] !== null) {
                                    $itemUpdates['qty'] = floatval($row['qty']);
                                }
                                if (isset($row['discount']) && $row['discount'] !== '' && $row['discount'] !== null) {
                                    $itemUpdates['discount_percent'] = floatval($row['discount']);
                                } elseif (isset($row['discount_percent']) && $row['discount_percent'] !== '' && $row['discount_percent'] !== null) {
                                    $itemUpdates['discount_percent'] = floatval($row['discount_percent']);
                                }
                                if (!empty($row['unit_code'])) {
                                    $unit = Unit::where('code', trim($row['unit_code']))
                                        ->orWhere('name', trim($row['unit_code']))
                                        ->first();
                                    if ($unit) {
                                        $itemUpdates['unit_id'] = $unit->id;
                                    }
                                }
                                if (!empty($itemUpdates)) {
                                    $soItem->update($itemUpdates);
                                }
                            } else {
                                // Item doesn't exist yet in this SO - add it
                                $unitId = $product->unit_id;
                                if (!empty($row['unit_code'])) {
                                    $unit = Unit::where('code', trim($row['unit_code']))
                                        ->orWhere('name', trim($row['unit_code']))
                                        ->first();
                                    if ($unit) $unitId = $unit->id;
                                }
                                $so->items()->create([
                                    'product_id'       => $product->id,
                                    'qty'              => floatval($row['qty'] ?? 0),
                                    'unit_id'          => $unitId,
                                    'unit_price'       => floatval($row['unit_price'] ?? 0),
                                    'discount_percent' => floatval($row['discount'] ?? $row['discount_percent'] ?? 0),
                                ]);
                            }
                        }

                        $so->calculateTotals();
                        $this->updatedCount++;
                    });
                } catch (\Exception $e) {
                    Log::error("SalesOrderImport update error for SO {$soNumber}: " . $e->getMessage());
                    $this->errors[] = "Error updating SO {$soNumber}: " . $e->getMessage();
                    $this->skippedCount += $items->count();
                }
            }
        }

        // ── CREATE MODE: Group by Customer + PO + Date (existing logic) ──
        if ($createRows->isNotEmpty()) {
            $grouped = $createRows->filter(function ($row) {
                return !empty($row['customer_code']) && !empty($row['order_date']) && !empty($row['product_code']);
            })->groupBy(function ($row) {
                return $row['customer_code'] . '|' . ($row['customer_po'] ?? '') . '|' . $row['order_date'];
            });

            foreach ($grouped as $key => $items) {
                try {
                    $firstRow = $items->first();

                    $customer = Customer::where('code', trim($firstRow['customer_code']))->first();
                    if (!$customer) {
                        $this->errors[] = "Customer not found: {$firstRow['customer_code']}";
                        $this->skippedCount += $items->count();
                        continue;
                    }

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
                                return;
                            }

                            if (!in_array($existingSO->status, ['draft', 'waiting_po'])) {
                                $this->errors[] = "Cannot overwrite SO {$existingSO->so_number} (status: {$existingSO->status}). Gunakan kolom 'SO Number' untuk update order confirmed.";
                                $this->skippedCount += $items->count();
                                return;
                            }

                            $existingSO->update([
                                'warehouse_id'  => $warehouse->id,
                                'delivery_date' => $firstRow['delivery_date'] ?? null,
                                'notes'         => $firstRow['notes'] ?? null,
                            ]);
                            $existingSO->items()->delete();
                            $so = $existingSO;
                        } else {
                            $so = SalesOrder::create([
                                'so_number'          => SalesOrder::generateSoNumber(),
                                'customer_po_number' => $firstRow['customer_po'] ?? null,
                                'customer_id'        => $customer->id,
                                'warehouse_id'       => $warehouse->id,
                                'order_date'         => $firstRow['order_date'],
                                'delivery_date'      => $firstRow['delivery_date'] ?? null,
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

                            $unitId = $product->unit_id;
                            if (!empty($row['unit_code'])) {
                                $unit = Unit::where('code', trim($row['unit_code']))
                                    ->orWhere('name', trim($row['unit_code']))
                                    ->first();
                                if ($unit) $unitId = $unit->id;
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
}
