<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkOrdersImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value) && $value > 25569) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeString($value): string
    {
        return trim((string) ($value ?? ''));
    }

    public function collection(Collection $rows)
    {
        $defaultMaterialWarehouseId = Warehouse::query()
            ->where('code', 'WH-RM')
            ->orWhereRaw('LOWER(name) LIKE ?', ['%raw material%'])
            ->orderByRaw("CASE WHEN code = 'WH-RM' THEN 0 ELSE 1 END")
            ->value('id');

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;

            $bomName = $this->normalizeString($row['bom_name'] ?? null);
            $warehouseName = $this->normalizeString($row['warehouse_name'] ?? null);
            $priority = strtolower($this->normalizeString($row['priority'] ?? null));
            $productionType = strtolower($this->normalizeString($row['production_type'] ?? null));
            $supplierName = $this->normalizeString($row['supplier_name'] ?? null);
            $notes = $this->normalizeString($row['notes'] ?? null);

            $qtyPlanned = $row['qty_planned'] ?? null;
            $qtyPlanned = is_numeric($qtyPlanned) ? (float) $qtyPlanned : null;

            $plannedStart = $this->parseDate($row['planned_start'] ?? null);
            $plannedEnd = $this->parseDate($row['planned_end'] ?? null);

            if ($bomName === '') {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: BOM Name wajib diisi.";
                continue;
            }

            if ($warehouseName === '') {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Warehouse Name wajib diisi.";
                continue;
            }

            if ($qtyPlanned === null || $qtyPlanned <= 0) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Qty Planned harus angka > 0.";
                continue;
            }

            if (!$plannedStart || !$plannedEnd) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Planned Start dan Planned End wajib (format YYYY-MM-DD atau tanggal Excel).";
                continue;
            }

            if ($plannedEnd < $plannedStart) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Planned End harus >= Planned Start.";
                continue;
            }

            if (!in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Priority tidak valid (low/normal/high/urgent).";
                continue;
            }

            if (!in_array($productionType, ['internal', 'subcontract'], true)) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Production Type tidak valid (internal/subcontract).";
                continue;
            }

            $bom = Bom::with('components')
                ->whereRaw('LOWER(name) = ?', [strtolower($bomName)])
                ->first();

            if (!$bom) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: BOM tidak ditemukan: {$bomName}.";
                continue;
            }

            $warehouse = Warehouse::whereRaw('LOWER(name) = ?', [strtolower($warehouseName)])->first();

            if (!$warehouse) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Warehouse tidak ditemukan: {$warehouseName}.";
                continue;
            }

            $supplier = null;
            if ($productionType === 'subcontract') {
                if ($supplierName === '') {
                    $this->skippedCount++;
                    $this->errors[] = "Row {$rowNumber}: Supplier Name wajib untuk subcontract.";
                    continue;
                }

                $supplier = Supplier::whereRaw('LOWER(name) = ?', [strtolower($supplierName)])->first();
                if (!$supplier) {
                    $this->skippedCount++;
                    $this->errors[] = "Row {$rowNumber}: Supplier tidak ditemukan: {$supplierName}.";
                    continue;
                }
            }

            try {
                DB::transaction(function () use ($bom, $warehouse, $qtyPlanned, $plannedStart, $plannedEnd, $priority, $productionType, $supplier, $notes, $defaultMaterialWarehouseId) {
                    $wo = WorkOrder::create([
                        'company_id' => session('company_id'),
                        'wo_number' => WorkOrder::generateWoNumber(),
                        'bom_id' => $bom->id,
                        'product_id' => $bom->product_id,
                        'warehouse_id' => $warehouse->id,
                        'material_warehouse_id' => $defaultMaterialWarehouseId,
                        'qty_planned' => $qtyPlanned,
                        'planned_start' => $plannedStart,
                        'planned_end' => $plannedEnd,
                        'status' => 'confirmed',
                        'priority' => $priority,
                        'notes' => $notes !== '' ? $notes : null,
                        'created_by' => auth()->id(),
                        'production_type' => $productionType,
                        'supplier_id' => $supplier?->id,
                    ]);

                    $wo->initializeFromBom();

                    if ($wo->production_type === 'subcontract' && !$wo->subcontractOrders()->exists()) {
                        $orderNumber = 'SCO-' . date('Ymd') . '-' . str_pad($wo->id, 4, '0', STR_PAD_LEFT);
                        $wo->subcontractOrders()->create([
                            'supplier_id' => $wo->supplier_id,
                            'order_number' => $orderNumber,
                            'status' => 'draft',
                            'service_fee' => 0,
                        ]);
                    }
                });

                $this->importedCount++;
            } catch (\Throwable $e) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }
    }
}
