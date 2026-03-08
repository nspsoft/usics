<?php

namespace App\Imports;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DeliveryOrderImport implements ToCollection, WithHeadingRow
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

    /**
     * Parse date from Excel - handles both serial numbers and string formats.
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value) && $value > 25569) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function collection(Collection $rows)
    {
        // Group by DO Number if it exists, otherwise use SO Number.
        // We use a custom grouping closure so new DOs and existing DOs are grouped correctly.
        $grouped = $rows->groupBy(function ($item) {
            $do = trim($item['do_number'] ?? '');
            if ($do !== '') {
                return 'DO__' . $do; // Prefix to distinguish
            }
            return 'SO__' . trim($item['so_number'] ?? '');
        });

        foreach ($grouped as $groupKey => $items) {
            try {
                if ($items->isEmpty()) continue;
                $firstRow = $items->first();
                $soNumber = trim($firstRow['so_number'] ?? '');
                
                $isUpdate = str_starts_with($groupKey, 'DO__');
                $doNumberFromExcel = $isUpdate ? substr($groupKey, 4) : null;

                $so = null;
                if (!$isUpdate) { // Only check SO if we are potentially creating a new DO
                    $so = SalesOrder::where('so_number', $soNumber)
                        ->whereIn('status', ['confirmed', 'processing', 'partial'])
                        ->first();

                    if (!$so) {
                        $this->errors[] = "SO [{$soNumber}] not found or not in a deliverable status. Skipping.";
                        $this->skippedCount += $items->count();
                        continue;
                    }
                }


                DB::transaction(function () use ($so, $items, $isUpdate, $doNumberFromExcel) {
                    $firstRow = $items->first();
                    $deliveryDate = now()->toDateString();
                    $doNumber = $doNumberFromExcel;
                    $currentSo = $so; // Use a local variable for SO that might be updated

                    try {
                        $rawDate = $firstRow['delivery_date'] ?? null;
                        $parsedDate = $this->parseDate($rawDate);
                        if ($parsedDate) {
                            $deliveryDate = $parsedDate;
                        }
                    } catch (\Exception $e) {
                        Log::warning("DeliveryOrderImport: Invalid delivery_date, using today(): " . $e->getMessage());
                    }

                    $existingDO = null;
                    $isUpdating = false;
                    if ($isUpdate) {
                        $existingDO = DeliveryOrder::where('do_number', $doNumberFromExcel)->first();
                        if ($existingDO) {
                            if (!$this->overwrite) {
                                $this->skippedCount += $items->count();
                                return; // skip
                            }
                            if (!in_array($existingDO->status, ['draft', 'picking', 'packed'])) {
                                $this->errors[] = "Cannot overwrite DO {$existingDO->do_number} because status is {$existingDO->status}.";
                                $this->skippedCount += $items->count();
                                return; // skip
                            }
                            // Overwrite: Update headers, delete old items
                            $existingDO->update([
                                'delivery_date' => $deliveryDate,
                            ]);
                            $existingDO->items()->delete();
                            $do = $existingDO;
                            $currentSo = $existingDO->salesOrder; // Use SO from existing DO
                            $isUpdating = true;
                        } else {
                            // Even if prefix DO__ is there, if DO doesn't exist, we fall back to creating it if SO is valid
                            if (!$currentSo) {
                                $this->errors[] = "DO [{$doNumberFromExcel}] and SO [{$firstRow['so_number']}] not valid. Skipping.";
                                $this->skippedCount += $items->count();
                                return;
                            }
                            $isUpdate = false; 
                        }
                    }

                    if (!$existingDO) {
                        // Create New
                        if (!$doNumber) {
                            $lastDO = DeliveryOrder::orderBy('id', 'desc')->first();
                            $doNumber = 'DO/' . date('Ymd') . '/' . str_pad(($lastDO ? $lastDO->id : 0) + 1, 4, '0', STR_PAD_LEFT);
                        }
                        
                        $do = DeliveryOrder::create([
                            'do_number' => $doNumber,
                            'sales_order_id' => $currentSo->id,
                            'customer_id' => $currentSo->customer_id,
                            'warehouse_id' => $currentSo->warehouse_id,
                            'delivery_date' => $deliveryDate,
                            'driver_name' => 'Imported',
                            'vehicle_number' => '-',
                            'shipping_address' => $currentSo->shipping_address,
                            'status' => 'draft',
                        ]);
                    }

                    foreach ($items as $row) {
                        if (empty($row['product_code'])) continue;

                        $product = Product::where('sku', trim($row['product_code']))->first();
                        if (!$product) {
                            $this->errors[] = "Product [{$row['product_code']}] not found. Skipping item.";
                            $this->skippedCount++;
                            continue;
                        }

                        // Find matching SO item
                        $soItem = SalesOrderItem::where('sales_order_id', $currentSo->id)
                            ->where('product_id', $product->id)
                            ->first();

                        if (!$soItem) {
                            $this->errors[] = "Product [{$row['product_code']}] not found in SO [{$currentSo->so_number}]. Skipping item.";
                            $this->skippedCount++;
                            continue;
                        }

                        $do->items()->create([
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $product->id,
                            'qty_ordered' => $soItem->qty,
                            'qty_delivered' => floatval($row['qty_delivered'] ?? 0),
                            'unit_id' => $soItem->unit_id,
                            'batch_number' => $row['batch_number'] ?? null,
                            'notes' => $row['notes'] ?? null,
                        ]);
                    }
                    
                    if ($isUpdating) {
                        $this->updatedCount++;
                    } else {
                        $this->importedCount++;
                    }
                });
            } catch (\Exception $e) {
                $this->errors[] = "Error processing row: " . $e->getMessage();
            }
        }
    }
}
