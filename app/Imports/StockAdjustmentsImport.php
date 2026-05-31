<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockAdjustmentsImport implements ToCollection, WithHeadingRow
{
    public int $createdAdjustmentId = 0;
    public int $rowCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    protected int $warehouseId;
    protected string $adjustmentDate;
    protected string $reason;
    protected ?string $notes;
    private ?bool $productsHasCodeColumn = null;

    public function __construct(int $warehouseId, string $adjustmentDate, string $reason, ?string $notes = null)
    {
        $this->warehouseId = $warehouseId;
        $this->adjustmentDate = $adjustmentDate;
        $this->reason = $reason;
        $this->notes = $notes;
    }

    private function productsHasCodeColumn(): bool
    {
        if ($this->productsHasCodeColumn !== null) {
            return $this->productsHasCodeColumn;
        }

        $this->productsHasCodeColumn = Schema::hasColumn('products', 'code');
        return $this->productsHasCodeColumn;
    }

    private function findProduct(string $code): ?Product
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $q = Product::query()->where('sku', $code);
        if ($this->productsHasCodeColumn()) {
            $q->orWhere('code', $code);
        }
        $q->orWhereRaw('LOWER(name) = ?', [strtolower($code)]);

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
            $productCode = trim((string) ($this->getValue($row, ['product_code', 'product', 'sku', 'code'], '') ?? ''));
            $qtyActual = $this->getValue($row, ['qty_actual', 'actual', 'qty'], null);
            return !($productCode === '' && (string) $qtyActual === '');
        })->values();

        $this->rowCount = $rows->count();

        if ($this->rowCount === 0) {
            $this->errors[] = 'File kosong / tidak ada baris data.';
            return;
        }

        $items = [];
        $notesLines = [];

        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;
            $productCode = trim((string) ($this->getValue($row, ['product_code', 'product', 'sku', 'code'], '') ?? ''));
            $qtyActual = $this->getValue($row, ['qty_actual', 'actual', 'qty'], null);
            $rowNotes = trim((string) ($this->getValue($row, ['notes', 'note'], '') ?? ''));

            if ($productCode === '') {
                $this->skippedCount++;
                continue;
            }

            if (!is_numeric($qtyActual)) {
                $this->errors[] = "Row {$rowNo}: Qty Actual tidak valid untuk {$productCode}";
                continue;
            }

            $qtyActual = (float) $qtyActual;
            if ($qtyActual < 0) {
                $this->errors[] = "Row {$rowNo}: Qty Actual tidak boleh negatif untuk {$productCode}";
                continue;
            }

            $product = $this->findProduct($productCode);
            if (!$product) {
                $this->errors[] = "Row {$rowNo}: Product tidak ditemukan: {$productCode}";
                continue;
            }

            $items[] = [
                'product' => $product,
                'qty_actual' => $qtyActual,
                'row_notes' => $rowNotes,
            ];

            if ($rowNotes !== '') {
                $notesLines[] = ($product->sku ?? $productCode) . ': ' . $rowNotes;
            }
        }

        if (count($items) === 0) {
            if (empty($this->errors)) {
                $this->errors[] = 'Tidak ada item valid untuk di-import.';
            }
            return;
        }

        DB::transaction(function () use ($items, $notesLines) {
            $adjustmentNotes = $this->notes;
            if (!empty($notesLines)) {
                $append = implode("\n", $notesLines);
                $adjustmentNotes = $adjustmentNotes ? trim($adjustmentNotes) . "\n" . $append : $append;
            }

            $adjustment = StockAdjustment::create([
                'warehouse_id' => $this->warehouseId,
                'adjustment_number' => StockAdjustment::generateNumber(),
                'adjustment_date' => $this->adjustmentDate,
                'status' => StockAdjustment::STATUS_DRAFT,
                'reason' => $this->reason,
                'notes' => $adjustmentNotes,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $row) {
                $product = $row['product'];
                $qtySystem = (float) ProductStock::where('product_id', $product->id)
                    ->where('warehouse_id', $this->warehouseId)
                    ->sum('qty_on_hand');

                $qtyActual = (float) $row['qty_actual'];
                $difference = $qtyActual - $qtySystem;

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'qty_system' => $qtySystem,
                    'qty_actual' => $qtyActual,
                    'qty_difference' => $difference,
                ]);
            }

            $this->createdAdjustmentId = $adjustment->id;
        });
    }
}

