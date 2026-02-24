<?php

namespace App\Imports;

use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\Product;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class BomImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected array $bomCache = [];
    protected array $skippedBomCodes = [];

    public function model(array $row)
    {
        $bomCode = trim((string) ($row['bom_code'] ?? ''));
        $productCode = trim((string) ($row['product_code'] ?? ''));
        $componentCode = trim((string) ($row['component_code'] ?? ''));
        $componentQty = $row['component_qty'] ?? null;

        if ($bomCode === '' || $productCode === '' || $componentCode === '' || $componentQty === null) {
            $this->skippedCount++;
            $this->errors[] = 'Missing required fields for row with BOM Code: ' . ($bomCode !== '' ? $bomCode : '-');
            return null;
        }

        if (isset($this->skippedBomCodes[$bomCode])) {
            $this->skippedCount++;
            return null;
        }

        if (!isset($this->bomCache[$bomCode])) {
            if (Bom::where('code', $bomCode)->exists()) {
                $this->skippedBomCodes[$bomCode] = true;
                $this->skippedCount++;
                $this->errors[] = 'BOM with code ' . $bomCode . ' already exists';
                return null;
            }

            $product = Product::where('sku', $productCode)->first();

            if (!$product) {
                $this->skippedBomCodes[$bomCode] = true;
                $this->skippedCount++;
                $this->errors[] = 'Product with code ' . $productCode . ' not found for BOM ' . $bomCode;
                return null;
            }

            $version = trim((string) ($row['version'] ?? '1.0'));
            if ($version === '') {
                $version = '1.0';
            }

            $qty = $this->parseNumeric($row['output_qty'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }

            $unitSymbol = trim((string) ($row['output_unit'] ?? ''));
            $unitId = null;

            if ($unitSymbol !== '') {
                $unit = Unit::where('symbol', $unitSymbol)->first();
                if ($unit) {
                    $unitId = $unit->id;
                }
            }

            if ($unitId === null) {
                $unitId = $product->unit_id;
            }

            $status = $this->parseStatus($row['status'] ?? null);

            $bom = Bom::create([
                'code' => $bomCode,
                'name' => $row['bom_name'] ?? $product->name,
                'product_id' => $product->id,
                'qty' => $qty,
                'unit_id' => $unitId,
                'version' => $version,
                'status' => $status,
                'description' => $row['description'] ?? null,
            ]);

            $this->bomCache[$bomCode] = $bom;
        } else {
            $bom = $this->bomCache[$bomCode];
        }

        $componentProduct = Product::where('sku', $componentCode)->first();

        if (!$componentProduct) {
            $this->skippedCount++;
            $this->errors[] = 'Component product with code ' . $componentCode . ' not found for BOM ' . $bomCode;
            return null;
        }

        $componentQtyValue = $this->parseNumeric($componentQty);
        if ($componentQtyValue <= 0) {
            $this->skippedCount++;
            $this->errors[] = 'Component quantity must be greater than 0 for BOM ' . $bomCode . ' and component ' . $componentCode;
            return null;
        }

        $componentUnitSymbol = trim((string) ($row['component_unit'] ?? ''));
        $componentUnitId = null;

        if ($componentUnitSymbol !== '') {
            $componentUnit = Unit::where('symbol', $componentUnitSymbol)->first();
            if ($componentUnit) {
                $componentUnitId = $componentUnit->id;
            }
        }

        if ($componentUnitId === null) {
            $componentUnitId = $componentProduct->unit_id;
        }

        $scrap = $this->parseNumeric($row['scrap'] ?? 0);
        if ($scrap < 0) {
            $scrap = 0;
        }

        $sequence = $bom->components()->max('sequence');
        $nextSequence = $sequence ? $sequence + 1 : 1;

        BomComponent::create([
            'bom_id' => $bom->id,
            'product_id' => $componentProduct->id,
            'qty' => $componentQtyValue,
            'unit_id' => $componentUnitId,
            'scrap_rate' => $scrap,
            'sequence' => $nextSequence,
        ]);

        $this->importedCount++;

        return null;
    }

    protected function parseNumeric($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function parseStatus($value): string
    {
        $status = strtolower((string) $value);
        if (in_array($status, [Bom::STATUS_DRAFT, Bom::STATUS_ACTIVE, Bom::STATUS_ARCHIVED], true)) {
            return $status;
        }

        return Bom::STATUS_DRAFT;
    }
}

