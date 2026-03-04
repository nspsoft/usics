<?php

namespace App\Imports\Purchasing;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GoodsReceiptsImport implements ToCollection, WithHeadingRow
{
    private $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        // Group rows. If GRN Number is provided, group by it for overwrite. Otherwise group by Supplier+Warehouse+DN
        $groups = $rows->groupBy(function ($row) {
            if (!empty($row['grn_number'])) {
                return 'GRN_OVERWRITE:' . $row['grn_number'];
            }
            return 'GRN_CREATE:' . $row['supplier_code'] . '|' . $row['warehouse_name'] . '|' . $row['delivery_note_number'];
        });

        foreach ($groups as $groupKey => $items) {
            DB::transaction(function () use ($groupKey, $items) {
                // Determine logic path
                if (str_starts_with($groupKey, 'GRN_OVERWRITE:')) {
                    $grnNumber = substr($groupKey, 14);
                    $this->processOverwrite($grnNumber, $items);
                } else {
                    $this->processCreate($items);
                }
            });
        }
    }

    private function processOverwrite(string $grnNumber, Collection $items)
    {
        if (!$this->overwrite) {
            // If overwrite flag is false, fall back to creating a new GRN
            $this->processCreate($items);
            return;
        }

        $receipt = GoodsReceipt::where('grn_number', $grnNumber)->first();
        if (!$receipt || $receipt->status !== 'draft') {
            // Cannot overwrite non-existent or non-draft receipts. Fall back to create a new one.
            $this->processCreate($items);
            return;
        }

        $firstRow = $items->first();

        if (!empty($firstRow['receipt_date_yyyy_mm_dd'])) {
             $receipt->receipt_date = $this->parseDate($firstRow['receipt_date_yyyy_mm_dd']);
        }
        if (!empty($firstRow['delivery_note_number'])) {
             $receipt->delivery_note_number = $firstRow['delivery_note_number'];
        }

        $po = null;
        if (!empty($firstRow['po_number'])) {
            $po = PurchaseOrder::where('po_number', $firstRow['po_number'])->first();
            if ($po) {
                $receipt->purchase_order_id = $po->id;
            }
        }
        
        $receipt->save();

        // Delete existing items
        $receipt->items()->delete();

        // Repopulate
        foreach ($items as $item) {
            $product = Product::where('code', $item['product_code'])->first();
            if ($product) {
                $poItemId = null;
                $poForPrice = $po ?: $receipt->purchaseOrder;
                if ($poForPrice) {
                    $poItem = $poForPrice->items()->where('product_id', $product->id)->first();
                    if ($poItem) {
                        $poItemId = $poItem->id;
                    }
                }

                $receipt->items()->create([
                    'product_id' => $product->id,
                    'purchase_order_item_id' => $poItemId,
                    'qty_received' => $item['qty_received'],
                    'qty_ordered' => 0,
                    'unit_cost' => isset($poItem) && $poItem ? $poItem->unit_price : ($product->buying_price ?? 0),
                ]);
            }
        }
    }

    private function processCreate(Collection $items)
    {
        $firstRow = $items->first();
                
        $supplier = Supplier::where('code', $firstRow['supplier_code'])->first();
        $warehouse = Warehouse::where('name', $firstRow['warehouse_name'])->first();
        
        if (!$supplier || !$warehouse) {
            return;
        }

        $po = null;
        if (!empty($firstRow['po_number'])) {
            $po = PurchaseOrder::where('po_number', $firstRow['po_number'])->first();
        }

        $receipt = GoodsReceipt::create([
            'grn_number' => GoodsReceipt::generateGrnNumber(),
            'receipt_date' => $this->parseDate($firstRow['receipt_date_yyyy_mm_dd']),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchase_order_id' => $po ? $po->id : null,
            'delivery_note_number' => $firstRow['delivery_note_number'],
            'status' => 'draft',
            'received_by' => auth()->id(),
        ]);

        foreach ($items as $item) {
            $product = Product::where('code', $item['product_code'])->first();
            
            if ($product) {
                $poItemId = null;
                if ($po) {
                    $poItem = $po->items()->where('product_id', $product->id)->first();
                    if ($poItem) {
                        $poItemId = $poItem->id;
                    }
                }

                $receipt->items()->create([
                    'product_id' => $product->id,
                    'purchase_order_item_id' => $poItemId,
                    'qty_received' => $item['qty_received'],
                    'qty_ordered' => 0,
                    'unit_cost' => isset($poItem) && $poItem ? $poItem->unit_price : ($product->buying_price ?? 0),
                ]);
            }
        }
    }

    private function parseDate($date)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                return now();
            }
        }
    }
}
