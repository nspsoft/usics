<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseOrdersImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    protected bool $overwrite;
    private ?bool $productsHasCodeColumn = null;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    private function productsHasCodeColumn(): bool
    {
        if ($this->productsHasCodeColumn !== null) {
            return $this->productsHasCodeColumn;
        }

        $this->productsHasCodeColumn = Schema::hasColumn('products', 'code');
        return $this->productsHasCodeColumn;
    }

    private function findProductByCodeOrName(string $productCode): ?Product
    {
        $productCode = trim($productCode);
        if ($productCode === '') {
            return null;
        }

        $q = Product::query()->where('sku', $productCode);
        if ($this->productsHasCodeColumn()) {
            $q->orWhere('code', $productCode);
        }
        $q->orWhereRaw('LOWER(name) = ?', [strtolower($productCode)]);

        return $q->first();
    }

    private function getValue($row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if ($row instanceof \Illuminate\Support\Collection) {
                if ($row->has($key)) {
                    return $row->get($key);
                }
            } elseif (is_array($row)) {
                if (array_key_exists($key, $row)) {
                    return $row[$key];
                }
            } elseif ($row instanceof \ArrayAccess) {
                try {
                    if ($row->offsetExists($key)) {
                        return $row[$key];
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        if ($row instanceof \Illuminate\Support\Collection) {
            $normalizedMap = [];
            foreach ($row->keys() as $k) {
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $k));
                if ($norm !== '') {
                    $normalizedMap[$norm] = $k;
                }
            }

            foreach ($keys as $key) {
                $normKey = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $key));
                if ($normKey !== '' && isset($normalizedMap[$normKey])) {
                    return $row->get($normalizedMap[$normKey]);
                }
            }
        } elseif (is_array($row)) {
            $normalizedMap = [];
            foreach (array_keys($row) as $k) {
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $k));
                if ($norm !== '') {
                    $normalizedMap[$norm] = $k;
                }
            }

            foreach ($keys as $key) {
                $normKey = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $key));
                if ($normKey !== '' && isset($normalizedMap[$normKey])) {
                    return $row[$normalizedMap[$normKey]];
                }
            }
        }

        return $default;
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->filter(function ($row) {
            $poNumber = trim((string) ($this->getValue($row, ['po_number', 'po'], '') ?? ''));
            $supplierCode = trim((string) ($this->getValue($row, ['supplier_code', 'supplier'], '') ?? ''));
            $warehouseName = trim((string) ($this->getValue($row, ['warehouse_name', 'warehouse'], '') ?? ''));
            $productCode = trim((string) ($this->getValue($row, ['product_code', 'product', 'sku', 'code'], '') ?? ''));
            $quantity = $this->getValue($row, ['quantity', 'qty'], null);
            $unitPrice = $this->getValue($row, ['unit_price', 'price'], null);

            return !($poNumber === '' && $supplierCode === '' && $warehouseName === '' && $productCode === '' && empty($quantity) && empty($unitPrice));
        })->values();

        $grouped = $rows->groupBy(function ($row) {
            $poNumber = trim((string) ($this->getValue($row, ['po_number', 'po'], '') ?? ''));
            if ($poNumber !== '') {
                return 'PO_OVERWRITE:' . strtolower($poNumber);
            }

            $orderDateRaw = $this->getValue($row, ['order_date', 'orderdate', 'tanggal_po', 'tgl_po', 'tanggal'], null);
            $date = $this->parseDate($orderDateRaw);
            $supplierCode = trim((string) ($this->getValue($row, ['supplier_code', 'supplier'], '') ?? ''));
            $warehouseName = trim((string) ($this->getValue($row, ['warehouse_name', 'warehouse'], '') ?? ''));
            return 'PO_CREATE:' . strtolower($supplierCode) . '|' . strtolower($warehouseName) . '|' . $date;
        });

        foreach ($grouped as $key => $items) {
            $firstRow = $items->first();
            $isOverwrite = str_starts_with($key, 'PO_OVERWRITE:');
            
            try {
                DB::transaction(function () use ($firstRow, $items, $isOverwrite) {
                    $supplierCode = trim((string) ($this->getValue($firstRow, ['supplier_code', 'supplier'], '') ?? ''));
                    $warehouseName = trim((string) ($this->getValue($firstRow, ['warehouse_name', 'warehouse'], '') ?? ''));

                    if ($supplierCode === '') {
                        throw new \RuntimeException('Supplier Code kosong (cek kolom Supplier Code).');
                    }
                    if ($warehouseName === '') {
                        throw new \RuntimeException('Warehouse Name kosong (cek kolom Warehouse Name).');
                    }

                    $supplier = Supplier::whereRaw('TRIM(LOWER(code)) = ?', [strtolower($supplierCode)])
                        ->orWhereRaw('TRIM(LOWER(name)) = ?', [strtolower($supplierCode)])
                        ->first();
                    $warehouse = Warehouse::whereRaw('TRIM(LOWER(name)) = ?', [strtolower($warehouseName)])->first();

                    if (!$supplier) {
                        throw new \RuntimeException("Supplier tidak ditemukan: {$supplierCode}");
                    }
                    if (!$warehouse) {
                        throw new \RuntimeException("Warehouse tidak ditemukan: {$warehouseName}");
                    }

                    $orderDateRaw = $this->getValue($firstRow, ['order_date', 'orderdate', 'tanggal_po', 'tgl_po', 'tanggal'], null);
                    $orderDate = $this->parseDate($orderDateRaw);

                    $expectedDateRaw = $this->getValue($firstRow, ['expected_date', 'expecteddate', 'tanggal_estimasi', 'tgl_estimasi'], null);
                    $expectedDate = !empty($expectedDateRaw) ? $this->parseDate($expectedDateRaw) : null;

                    $status = $this->normalizeStatus($this->getValue($firstRow, ['status'], null));
                    
                    $po = null;
                    $isCreated = false;

                    if ($isOverwrite) {
                        $poNumber = trim((string) ($this->getValue($firstRow, ['po_number', 'po'], '') ?? ''));
                        $po = PurchaseOrder::where('po_number', $poNumber)->first();

                        if ($po) {
                            if (!$this->overwrite) {
                                throw new \RuntimeException("PO {$poNumber} sudah ada. Centang Overwrite jika ingin menimpa.");
                            }
                            if ($po->status !== 'draft') {
                                throw new \RuntimeException("PO {$poNumber} tidak bisa dioverwrite karena status {$po->status}. Hanya draft yang bisa dioverwrite.");
                            }

                            // Valid Overwrite Update Headings
                            $po->update([
                                'company_id' => $po->company_id ?? session('company_id'),
                                'supplier_id' => $supplier->id,
                                'warehouse_id' => $warehouse->id,
                                'order_date' => $orderDate,
                                'expected_date' => $expectedDate,
                                'notes' => $firstRow['notes'] ?? null,
                            ]);
                            $po->items()->delete();
                            $this->updatedCount++;
                        } else {
                            // PO Number provided but not found in DB - Fallback to Create behavior assigning requested PO Number
                            $po = PurchaseOrder::create([
                                'company_id' => session('company_id'),
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
                            $isCreated = true;
                        }
                    } else {
                        $poNumber = PurchaseOrder::generatePoNumber($supplier, $orderDate);
                        $po = PurchaseOrder::create([
                            'company_id' => session('company_id'),
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
                        $isCreated = true;
                    }

                    $createdItems = 0;
                    foreach ($items as $item) {
                        $productCode = $this->getValue($item, ['product_code', 'product', 'sku', 'code'], null);
                        $quantity = $this->getValue($item, ['quantity', 'qty'], null);
                        $unitPrice = $this->getValue($item, ['unit_price', 'price'], null);

                        if (empty($productCode) || empty($quantity) || empty($unitPrice)) continue;

                        $product = $this->findProductByCodeOrName((string) $productCode);
                        
                        if ($product) {
                            $qty = (float) $quantity;
                            $price = (float) $unitPrice;
                            $discountRaw = $this->getValue($item, ['discount', 'discount_percent', 'discount_%'], null);
                            $discount = isset($discountRaw) && $discountRaw !== '' ? (float) $discountRaw : 0;

                            $po->items()->create([
                                'product_id' => $product->id,
                                'qty' => $qty,
                                'unit_id' => $product->unit_id,
                                'unit_price' => $price,
                                'discount_percent' => $discount,
                            ]);
                            $createdItems++;
                        }
                    }

                    if ($createdItems === 0) {
                        throw new \RuntimeException('Tidak ada item valid (Product Code tidak ditemukan / Qty / Price kosong).');
                    }

                    $po->calculateTotals();

                    if ($po->status === 'ordered' && empty($po->approved_at)) {
                        $po->update([
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);
                    }

                    if ($isCreated) {
                        $this->importedCount++;
                    }
                });
            } catch (\Throwable $e) {
                $this->skippedCount += $items->count();
                $this->errors[] = "Import PO gagal [{$key}]: " . $e->getMessage();
                Log::error("PurchaseOrdersImport Error on key [{$key}]: " . $e->getMessage());
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
        if (empty($value)) {
            return now()->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 25569) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value))
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d');
        }
    }
}
