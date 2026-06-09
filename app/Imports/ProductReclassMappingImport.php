<?php

namespace App\Imports;

use App\Models\Inventory\ProductReclassMapping;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductReclassMappingImport implements ToCollection, WithHeadingRow
{
    protected bool $overwrite;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        $processedSourceIds = [];

        DB::transaction(function () use ($rows, &$processedSourceIds) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // 1-indexed header + row index
                $normalized = $this->normalizeKeys($row->toArray());

                $sourceSku = isset($normalized['source_product_sku']) ? trim($normalized['source_product_sku']) : '';
                $targetSku = isset($normalized['target_product_sku']) ? trim($normalized['target_product_sku']) : '';

                if ($sourceSku === '' || $targetSku === '') {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowNum}: SKU Source atau Target tidak boleh kosong.";
                    continue;
                }

                // Find source product
                $sourceProduct = Product::where('sku', $sourceSku)->first();
                if (!$sourceProduct) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowNum}: Produk Source dengan SKU '{$sourceSku}' tidak ditemukan.";
                    continue;
                }

                // Find target product
                $targetProduct = Product::where('sku', $targetSku)->first();
                if (!$targetProduct) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowNum}: Produk Target dengan SKU '{$targetSku}' tidak ditemukan.";
                    continue;
                }

                $sourceId = $sourceProduct->id;
                $targetId = $targetProduct->id;

                if ($sourceId === $targetId) {
                    $this->skippedCount++;
                    $this->errors[] = "Baris {$rowNum}: SKU Source dan Target tidak boleh sama.";
                    continue;
                }

                $isActive = $this->parseBoolean($normalized['is_active'] ?? 'Yes');
                $isDefault = $this->parseBoolean($normalized['is_default'] ?? 'No');
                $notes = isset($normalized['notes']) ? trim($normalized['notes']) : null;

                $processedSourceIds[] = $sourceId;

                // Check if mapping exists
                $existing = ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->where('target_product_id', $targetId)
                    ->first();

                if ($existing) {
                    if (!$this->overwrite) {
                        $this->skippedCount++;
                        $this->errors[] = "Baris {$rowNum}: Mapping dari '{$sourceSku}' ke '{$targetSku}' sudah ada (lewati karena opsi overwrite tidak aktif).";
                        continue;
                    }

                    // Overwrite
                    $existing->update([
                        'is_active' => $isActive,
                        'is_default' => $isDefault,
                        'notes' => $notes,
                        'updated_by' => auth()->id(),
                    ]);

                    if ($isDefault) {
                        // Unset default on other targets of this source
                        ProductReclassMapping::query()
                            ->whereNull('deleted_at')
                            ->where('source_product_id', $sourceId)
                            ->where('id', '!=', $existing->id)
                            ->update(['is_default' => false]);
                    }

                    $this->updatedCount++;
                } else {
                    // Create New
                    $hasAny = ProductReclassMapping::query()
                        ->whereNull('deleted_at')
                        ->where('source_product_id', $sourceId)
                        ->exists();

                    // If it's the first mapping for this source, set as default
                    if (!$hasAny) {
                        $isDefault = true;
                    }

                    $newMapping = ProductReclassMapping::create([
                        'source_product_id' => $sourceId,
                        'target_product_id' => $targetId,
                        'is_active' => $isActive,
                        'is_default' => $isDefault,
                        'notes' => $notes,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    if ($isDefault) {
                        // Unset default on other targets of this source
                        ProductReclassMapping::query()
                            ->whereNull('deleted_at')
                            ->where('source_product_id', $sourceId)
                            ->where('id', '!=', $newMapping->id)
                            ->update(['is_default' => false]);
                    }

                    $this->importedCount++;
                }
            }

            // Post-import verification for defaults per source
            $uniqueSources = array_unique($processedSourceIds);
            foreach ($uniqueSources as $sourceId) {
                $activeCount = ProductReclassMapping::query()
                    ->whereNull('deleted_at')
                    ->where('source_product_id', $sourceId)
                    ->where('is_active', true)
                    ->count();

                if ($activeCount > 0) {
                    $defaultCount = ProductReclassMapping::query()
                        ->whereNull('deleted_at')
                        ->where('source_product_id', $sourceId)
                        ->where('is_active', true)
                        ->where('is_default', true)
                        ->count();

                    if ($defaultCount === 0) {
                        $fallbackId = ProductReclassMapping::query()
                            ->whereNull('deleted_at')
                            ->where('source_product_id', $sourceId)
                            ->where('is_active', true)
                            ->orderBy('id')
                            ->value('id');

                        if ($fallbackId) {
                            ProductReclassMapping::query()
                                ->where('id', $fallbackId)
                                ->update(['is_default' => true]);
                        }
                    }
                }
            }
        });
    }

    protected function normalizeKeys(array $row): array
    {
        $mapping = [
            'source_product_sku' => 'source_product_sku',
            'source_sku'         => 'source_product_sku',
            'source'             => 'source_product_sku',
            'target_product_sku' => 'target_product_sku',
            'target_sku'         => 'target_product_sku',
            'target'             => 'target_product_sku',
            'is_active'          => 'is_active',
            'active'             => 'is_active',
            'is_default'         => 'is_default',
            'default'            => 'is_default',
            'notes'              => 'notes',
            'note'               => 'notes',
        ];

        $normalized = [];
        foreach ($row as $key => $value) {
            $cleanKey = strtolower(str_replace([' ', '_', '-'], '_', trim($key)));
            $normalizedKey = $mapping[$cleanKey] ?? $cleanKey;
            
            if (!isset($normalized[$normalizedKey])) {
                $normalized[$normalizedKey] = $value;
            }
        }

        return $normalized;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return $value > 0;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['yes', 'true', '1', 'y', 'ya', 'aktif', 'active']);
        }
        return false;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
