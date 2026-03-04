<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuotationImport implements ToCollection, WithHeadingRow
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        // Group rows by Customer Code + Quotation Date
        $grouped = $rows->filter(function ($row) {
            return !empty($row['customer_code']) && !empty($row['quotation_date']) && !empty($row['product_code']);
        })->groupBy(function ($row) {
            return $row['customer_code'] . '|' . $row['quotation_date'];
        });

        foreach ($grouped as $key => $items) {
            try {
                $firstRow = $items->first();

                $customer = Customer::where('code', trim($firstRow['customer_code']))->first();
                if (!$customer) {
                    $this->errors[] = "Customer not found: {$firstRow['customer_code']}";
                    $this->skippedCount += $items->count();
                    continue;
                }

                DB::transaction(function () use ($firstRow, $items, $customer, $key) {
                    $existingQuotation = Quotation::where('customer_id', $customer->id)
                        ->whereDate('quotation_date', $firstRow['quotation_date'])
                        ->first();

                    if ($existingQuotation) {
                        if (!$this->overwrite) {
                            $this->skippedCount += $items->count();
                            return; // Skip group
                        }

                        if (in_array($existingQuotation->status, ['accepted', 'converted'])) {
                            $this->errors[] = "Cannot overwrite Accepted/Converted Quotation {$existingQuotation->number} for {$key}";
                            $this->skippedCount += $items->count();
                            return;
                        }

                        // Overwrite: Update header and recreate items
                        $existingQuotation->update([
                            'valid_until'    => $firstRow['valid_until'] ?? now()->addDays(30)->format('Y-m-d'),
                            'notes'          => $firstRow['notes'] ?? null,
                            'updated_by'     => auth()->id(), // Assuming you have updated_by, otherwise omit or use created_by
                        ]);
                        $existingQuotation->items()->delete();
                        $quotation = $existingQuotation;
                    } else {
                        // Create new
                        $quotation = Quotation::create([
                            'number'         => Quotation::generateNumber(),
                            'customer_id'    => $customer->id,
                            'quotation_date' => $firstRow['quotation_date'],
                            'valid_until'    => $firstRow['valid_until'] ?? now()->addDays(30)->format('Y-m-d'),
                            'status'         => 'draft',
                            'notes'          => $firstRow['notes'] ?? null,
                            'created_by'     => auth()->id(),
                        ]);
                    }

                    foreach ($items as $row) {
                        $product = Product::where('sku', trim($row['product_code']))->first();
                        if (!$product) {
                            $this->errors[] = "Product not found: {$row['product_code']} (Quotation: {$quotation->number})";
                            $this->skippedCount++;
                            continue;
                        }

                        $qty = floatval($row['qty'] ?? 0);
                        $unitPrice = floatval($row['unit_price'] ?? 0);

                        $quotation->items()->create([
                            'product_id'  => $product->id,
                            'qty'         => $qty,
                            'unit_price'  => $unitPrice,
                            'total_price' => $qty * $unitPrice,
                        ]);
                    }

                    $quotation->calculateTotal();
                    $this->importedCount++;
                });
            } catch (\Exception $e) {
                Log::error("QuotationImport error for group {$key}: " . $e->getMessage());
                $this->errors[] = "Error importing group {$key}: " . $e->getMessage();
                $this->skippedCount += $items->count();
            }
        }
    }
}
