<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Inventory\ProductPartner;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ProductAliasImport implements ToCollection, WithHeadingRow
{
    private $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                // 1. Find Product by SKU
                if (empty($row['product_sku'])) continue;
                
                $product = Product::where('sku', $row['product_sku'])->first();
                if (!$product) {
                    Log::warning("ProductAliasImport: Product SKU [{$row['product_sku']}] not found.");
                    continue;
                }

                // 2. Determine Partner Type and ID
                $partnerType = strtolower($row['partner_type'] ?? '');
                $partnerName = $row['partner_name'] ?? '';
                $partnerId = null;
                $morphType = null;

                if ($partnerType === 'customer') {
                    $partner = Customer::where('name', $partnerName)->first();
                    if ($partner) {
                        $partnerId = $partner->id;
                        $morphType = Customer::class;
                    }
                } elseif ($partnerType === 'supplier') {
                    $partner = Supplier::where('name', $partnerName)->first();
                    if ($partner) {
                        $partnerId = $partner->id;
                        $morphType = Supplier::class;
                    }
                }

                if (!$partnerId) {
                    Log::warning("ProductAliasImport: Partner [{$partnerName}] of type [{$partnerType}] not found.");
                    continue;
                }

                // 3. Create or Update Alias
                $existingAlias = ProductPartner::where('product_id', $product->id)
                    ->where('partner_type', $morphType)
                    ->where('partner_id', $partnerId)
                    ->first();

                if ($existingAlias) {
                    if ($this->overwrite) {
                        $existingAlias->update([
                            'alias_sku' => $row['alias_sku'] ?? null,
                            'alias_name' => $row['alias_name'] ?? null,
                        ]);
                    } else {
                        Log::info("ProductAliasImport: Skipping existing Alias for Product [{$product->sku}] and Partner [{$partnerName}] because Overwrite is disabled.");
                    }
                } else {
                    ProductPartner::create([
                        'product_id' => $product->id,
                        'partner_type' => $morphType,
                        'partner_id' => $partnerId,
                        'alias_sku' => $row['alias_sku'] ?? null,
                        'alias_name' => $row['alias_name'] ?? null,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("ProductAliasImport: Error processing row " . json_encode($row) . ": " . $e->getMessage());
            }
        }
    }
}
