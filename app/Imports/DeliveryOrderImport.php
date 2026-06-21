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
            // Handle Indonesian date dot format: DD.MM.YYYY
            $valueStr = strval($value);
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $valueStr, $matches)) {
                return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            }
            return null;
        }
    }

    /**
     * Parse float from string, handling both Indonesian (comma) and English format.
     */
    protected function parseFloat($value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return floatval($value);

        $str = trim(strval($value));
        if ($str === '') return null;

        // Check for decimal comma vs decimal dot
        if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
            if (strpos($str, '.') < strpos($str, ',')) {
                // ID format: 3.040,80 -> remove dot, replace comma with dot
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            } else {
                // EN format: 3,040.80 -> remove comma
                $str = str_replace(',', '', $str);
            }
        } elseif (strpos($str, ',') !== false) {
            // Only comma: 48,6 -> replace with dot
            $str = str_replace(',', '.', $str);
        }

        $str = preg_replace('/[^\d\.\-]/', '', $str);

        return is_numeric($str) ? floatval($str) : null;
    }

    public function collection(Collection $rows)
    {
        // Group by DO Number if it exists, otherwise use SO Number.
        $grouped = $rows->groupBy(function ($item) {
            $do = trim($item['no_do'] ?? $item['do_number'] ?? '');
            if ($do !== '') {
                return 'DO__' . $do; // Prefix to distinguish
            }
            $so = trim($item['no_so'] ?? $item['so_number'] ?? '');
            return 'SO__' . $so;
        });

        foreach ($grouped as $groupKey => $items) {
            try {
                if ($items->isEmpty()) continue;
                $firstRow = $items->first();
                $soNumber = trim($firstRow['no_so'] ?? $firstRow['so_number'] ?? '');
                
                $isUpdate = str_starts_with($groupKey, 'DO__');
                $doNumberFromExcel = $isUpdate ? substr($groupKey, 4) : null;

                $so = null;
                if ($soNumber) {
                    $so = SalesOrder::where('so_number', $soNumber)
                        ->whereIn('status', ['confirmed', 'processing', 'partial'])
                        ->first();
                }

                if (!$isUpdate && !$so) {
                    $this->errors[] = "SO [{$soNumber}] not found or not in a deliverable status. Skipping.";
                    $this->skippedCount += $items->count();
                    continue;
                }

                DB::transaction(function () use ($so, $items, $isUpdate, $doNumberFromExcel, $soNumber) {
                    $firstRow = $items->first();
                    $deliveryDate = now()->toDateString();
                    $doNumber = $doNumberFromExcel;
                    $currentSo = $so; 
                    $shipmentNumber = trim($firstRow['no_shipment'] ?? $firstRow['shipment_number'] ?? '');

                    try {
                        $rawDate = $firstRow['gi_date'] ?? $firstRow['delivery_date'] ?? null;
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
                                $this->errors[] = "DO [{$doNumberFromExcel}] already exists. Overwrite not enabled. Skipping.";
                                $this->skippedCount += $items->count();
                                return; // skip
                            }
                            if (!in_array($existingDO->status, ['draft', 'picking', 'packed'])) {
                                $this->errors[] = "Cannot overwrite DO {$existingDO->do_number} because status is {$existingDO->status}.";
                                $this->skippedCount += $items->count();
                                return; // skip
                            }
                            // Overwrite: Update headers, delete old items
                            $updateData = [
                                'delivery_date' => $deliveryDate,
                            ];
                            if ($shipmentNumber !== '') {
                                $updateData['shipment_number'] = $shipmentNumber;
                            }
                            $existingDO->update($updateData);
                            $existingDO->items()->delete();
                            $existingDO = $existingDO->fresh(); // Reload relations
                            $currentSo = $existingDO->salesOrder; // Use SO from existing DO
                            $isUpdating = true;
                        } else {
                            if (!$currentSo) {
                                $this->errors[] = "DO [{$doNumberFromExcel}] not found and SO [{$soNumber}] is invalid. Skipping.";
                                $this->skippedCount += $items->count();
                                return;
                            }
                            $isUpdate = false; 
                        }
                    }

                    if (!$existingDO) {
                        // Create New
                        if (!$currentSo) {
                            $this->errors[] = "Cannot create DO without a valid SO. Skipping.";
                            $this->skippedCount += $items->count();
                            return;
                        }

                        if (!$doNumber) {
                            $lastDO = DeliveryOrder::orderBy('id', 'desc')->first();
                            $doNumber = 'DO/' . date('Ymd') . '/' . str_pad(($lastDO ? $lastDO->id : 0) + 1, 4, '0', STR_PAD_LEFT);
                        }
                        
                        $existingDO = DeliveryOrder::create([
                            'do_number' => $doNumber,
                            'sales_order_id' => $currentSo->id,
                            'customer_id' => $currentSo->customer_id,
                            'warehouse_id' => $currentSo->warehouse_id,
                            'delivery_date' => $deliveryDate,
                            'shipment_number' => $shipmentNumber !== '' ? $shipmentNumber : null,
                            'driver_name' => 'Imported',
                            'vehicle_number' => '-',
                            'shipping_address' => $currentSo->shipping_address,
                            'status' => 'draft',
                        ]);
                    }

                    foreach ($items as $row) {
                        $sku = trim($row['material'] ?? $row['product_code'] ?? '');
                        if (empty($sku)) continue;

                        $product = Product::where('sku', $sku)->first();
                        if (!$product) {
                            $this->errors[] = "Product [{$sku}] not found. Skipping item.";
                            $this->skippedCount++;
                            continue;
                        }

                        // Find matching SO item
                        $soItem = SalesOrderItem::where('sales_order_id', $currentSo->id)
                            ->where('product_id', $product->id)
                            ->first();

                        if (!$soItem) {
                            $this->errors[] = "Product [{$sku}] not found in SO [{$currentSo->so_number}]. Skipping item.";
                            $this->skippedCount++;
                            continue;
                        }

                        // Parse spec columns
                        $inchi = isset($row['inchi']) ? trim(strval($row['inchi'])) : null;
                        $od = $this->parseFloat($row['od'] ?? $row['tr'] ?? null);
                        $tebal = $this->parseFloat($row['tebal'] ?? null);
                        $panjang = $this->parseFloat($row['panjang'] ?? null);
                        
                        $qtyDelivered = $this->parseFloat($row['qty_do'] ?? $row['qty_delivered'] ?? 0);
                        $kgDelivered = $this->parseFloat($row['kg_do'] ?? $row['kg_delivered'] ?? null);

                        $existingDO->items()->create([
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $product->id,
                            'qty_ordered' => $soItem->qty,
                            'qty_delivered' => $qtyDelivered,
                            'unit_id' => $soItem->unit_id,
                            'batch_number' => $row['batch_number'] ?? null,
                            'notes' => $row['notes'] ?? null,
                            'inchi' => $inchi,
                            'od' => $od,
                            'tebal' => $tebal,
                            'panjang' => $panjang,
                            'kg_delivered' => $kgDelivered,
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
