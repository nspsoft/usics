<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockOpnamesFromExportImport implements ToCollection, WithHeadingRow
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedRowCount = 0;
    public array $errors = [];

    protected bool $overwriteExisting;
    private ?bool $productsHasCodeColumn = null;
    private array $userCacheByKey = [];

    public function __construct(bool $overwriteExisting = true)
    {
        $this->overwriteExisting = $overwriteExisting;
    }

    private function productsHasCodeColumn(): bool
    {
        if ($this->productsHasCodeColumn !== null) {
            return $this->productsHasCodeColumn;
        }

        $this->productsHasCodeColumn = Schema::hasColumn('products', 'code');
        return $this->productsHasCodeColumn;
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

    private function findWarehouseByName(string $name): ?Warehouse
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return Warehouse::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();
    }

    private function findProduct(string $codeOrName): ?Product
    {
        $val = trim($codeOrName);
        if ($val === '') {
            return null;
        }

        $q = Product::query()->where('sku', $val);
        if ($this->productsHasCodeColumn()) {
            $q->orWhere('code', $val);
        }
        $q->orWhereRaw('LOWER(name) = ?', [strtolower($val)]);

        return $q->first();
    }

    private function findUserId(?string $nameOrEmail): ?int
    {
        $val = trim((string) $nameOrEmail);
        if ($val === '') {
            return null;
        }

        $key = strtolower($val);
        if (array_key_exists($key, $this->userCacheByKey)) {
            return $this->userCacheByKey[$key];
        }

        $q = User::query();
        if (str_contains($val, '@')) {
            $q->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($val)]);
        } else {
            $q->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($val)]);
        }

        $userId = $q->value('id');
        if (!$userId && !str_contains($val, '@')) {
            $prefixIds = User::query()
                ->whereRaw('LOWER(TRIM(name)) LIKE ?', [strtolower($val) . '%'])
                ->pluck('id');
            if ($prefixIds->count() === 1) {
                $userId = (int) $prefixIds->first();
            } elseif ($prefixIds->count() === 0) {
                $containsIds = User::query()
                    ->whereRaw('LOWER(TRIM(name)) LIKE ?', ['%' . strtolower($val) . '%'])
                    ->pluck('id');
                if ($containsIds->count() === 1) {
                    $userId = (int) $containsIds->first();
                }
            }
        }
        $this->userCacheByKey[$key] = $userId ? (int) $userId : null;
        return $this->userCacheByKey[$key];
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            try {
                return \Carbon\Carbon::instance($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (is_numeric($value)) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return \Carbon\Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->filter(function ($row) {
            $opnameNumber = trim((string) ($this->getValue($row, ['opname_number', 'opname num', 'opname number'], '') ?? ''));
            $warehouseName = trim((string) ($this->getValue($row, ['warehouse', 'warehouse_name', 'warehouse name'], '') ?? ''));
            $productCode = trim((string) ($this->getValue($row, ['product_code', 'product code', 'sku', 'code'], '') ?? ''));
            $physical = $this->getValue($row, ['physical_qty', 'physical qty', 'qty_physic', 'qty physical'], null);

            $isEmpty = ($opnameNumber === '' && $warehouseName === '' && $productCode === '' && (string) $physical === '');
            return !$isEmpty;
        })->values();

        if ($rows->count() === 0) {
            $this->errors[] = 'File kosong / tidak ada baris data.';
            return;
        }

        $grouped = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $opnameNumber = trim((string) ($this->getValue($row, ['opname_number', 'opname number'], '') ?? ''));
            $opnameDate = $this->normalizeDate($this->getValue($row, ['opname_date', 'opname date', 'date'], null));
            $warehouseName = trim((string) ($this->getValue($row, ['warehouse', 'warehouse_name', 'warehouse name'], '') ?? ''));
            $location = trim((string) ($this->getValue($row, ['location', 'lokasi'], '') ?? ''));
            $productCode = trim((string) ($this->getValue($row, ['product_code', 'product code', 'sku', 'code'], '') ?? ''));
            $productName = trim((string) ($this->getValue($row, ['product_name', 'product name', 'name'], '') ?? ''));
            $physical = $this->getValue($row, ['physical_qty', 'physical qty', 'qty_physic', 'qty physical'], null);
            $system = $this->getValue($row, ['system_qty', 'system qty', 'qty_system', 'qty system'], null);
            $rowNotes = trim((string) ($this->getValue($row, ['notes', 'note'], '') ?? ''));
            $createdBy = trim((string) ($this->getValue($row, ['created_by', 'created by', 'created'], '') ?? ''));
            $createdById = $this->findUserId($createdBy);

            if ($opnameNumber === '') {
                $this->errors[] = "Row {$rowNo}: Opname Number kosong";
                continue;
            }
            if (!$opnameDate) {
                $this->errors[] = "Row {$rowNo}: Opname Date tidak valid untuk {$opnameNumber}";
                continue;
            }
            if ($warehouseName === '') {
                $this->errors[] = "Row {$rowNo}: Warehouse kosong untuk {$opnameNumber}";
                continue;
            }
            if ($location === '') {
                $this->errors[] = "Row {$rowNo}: Location kosong untuk {$opnameNumber}";
                continue;
            }

            if ($productCode === '' && $productName !== '') {
                $productCode = $productName;
            }
            if ($productCode === '') {
                $this->skippedRowCount++;
                continue;
            }
            if (!is_numeric($physical)) {
                $this->errors[] = "Row {$rowNo}: Physical Qty tidak valid untuk {$opnameNumber} / {$productCode}";
                continue;
            }
            $physical = (float) $physical;
            if ($physical < 0) {
                $this->errors[] = "Row {$rowNo}: Physical Qty tidak boleh negatif untuk {$opnameNumber} / {$productCode}";
                continue;
            }

            $warehouse = $this->findWarehouseByName($warehouseName);
            if (!$warehouse) {
                $this->errors[] = "Row {$rowNo}: Warehouse tidak ditemukan: {$warehouseName}";
                continue;
            }

            $product = $this->findProduct($productCode);
            if (!$product && $productName !== '' && $productName !== $productCode) {
                $product = $this->findProduct($productName);
            }
            if (!$product) {
                $this->errors[] = "Row {$rowNo}: Product tidak ditemukan: {$productCode}";
                continue;
            }

            $systemQty = null;
            if (is_numeric($system)) {
                $systemQty = (float) $system;
            }

            $key = $opnameNumber;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'opname_number' => $opnameNumber,
                    'opname_date' => $opnameDate,
                    'warehouse_id' => $warehouse->id,
                    'location' => $location,
                    'created_by_name' => $createdBy,
                    'created_by_id' => $createdById,
                    'items' => [],
                ];
            } else {
                if (empty($grouped[$key]['created_by_id']) && $createdById) {
                    $grouped[$key]['created_by_id'] = $createdById;
                }
                if ($createdBy !== '' && empty($grouped[$key]['created_by_name'])) {
                    $grouped[$key]['created_by_name'] = $createdBy;
                }
            }

            $itemKey = (string) $product->id;
            if (!isset($grouped[$key]['items'][$itemKey])) {
                $grouped[$key]['items'][$itemKey] = [
                    'product_id' => $product->id,
                    'qty_system' => $systemQty,
                    'qty_physic' => $physical,
                    'notes' => $rowNotes,
                ];
            } else {
                $grouped[$key]['items'][$itemKey]['qty_physic'] += $physical;
                if ($grouped[$key]['items'][$itemKey]['qty_system'] === null && $systemQty !== null) {
                    $grouped[$key]['items'][$itemKey]['qty_system'] = $systemQty;
                }
                if ($rowNotes !== '') {
                    $existingNotes = $grouped[$key]['items'][$itemKey]['notes'] ?? '';
                    $grouped[$key]['items'][$itemKey]['notes'] = $existingNotes ? trim($existingNotes) . "\n" . $rowNotes : $rowNotes;
                }
            }
        }

        if (empty($grouped)) {
            if (empty($this->errors)) {
                $this->errors[] = 'Tidak ada data valid untuk di-import.';
            }
            return;
        }

        foreach ($grouped as $session) {
            DB::transaction(function () use ($session) {
                $existing = StockOpname::withTrashed()
                    ->where('opname_number', $session['opname_number'])
                    ->first();

                if ($existing && !$this->overwriteExisting) {
                    return;
                }

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->update([
                        'warehouse_id' => $session['warehouse_id'],
                        'opname_date' => $session['opname_date'],
                        'location' => $session['location'],
                        'count_mode' => 'partial_input',
                        'status' => StockOpname::STATUS_IN_PROGRESS,
                        'notes' => trim('IMPORTED from Excel export' . ($session['created_by_name'] ? "\nOriginal created by: {$session['created_by_name']}" : '')),
                        'created_by' => $session['created_by_id'] ?: auth()->id(),
                    ]);

                    $existing->items()->delete();
                    $this->updatedCount++;
                    $opname = $existing;
                } else {
                    $opname = StockOpname::create([
                        'opname_number' => $session['opname_number'],
                        'warehouse_id' => $session['warehouse_id'],
                        'opname_date' => $session['opname_date'],
                        'location' => $session['location'],
                        'count_mode' => 'partial_input',
                        'status' => StockOpname::STATUS_IN_PROGRESS,
                        'notes' => trim('IMPORTED from Excel export' . ($session['created_by_name'] ? "\nOriginal created by: {$session['created_by_name']}" : '')),
                        'created_by' => $session['created_by_id'] ?: auth()->id(),
                    ]);
                    $this->createdCount++;
                }

                $items = array_values($session['items']);
                foreach ($items as $itemData) {
                    $qtySystem = $itemData['qty_system'] ?? 0;
                    if ($qtySystem === null) {
                        $qtySystem = 0;
                    }
                    $qtyPhysic = (float) $itemData['qty_physic'];
                    $opname->items()->create([
                        'product_id' => $itemData['product_id'],
                        'qty_system' => (float) $qtySystem,
                        'qty_physic' => $qtyPhysic,
                        'qty_difference' => $qtyPhysic - (float) $qtySystem,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }
            });
        }
    }
}
