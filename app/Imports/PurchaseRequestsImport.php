<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseRequestsImport implements ToCollection, WithHeadingRow
{
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        // Group rows. If pr_number is filled, group by pr_number to overwrite.
        // If empty, group by unique PR identifier (Date + Department + Requester)
        $grouped = $rows->groupBy(function ($row) {
            $prNumber = trim($row['pr_number'] ?? '');
            if ($prNumber !== '') {
                return 'PR_OVERWRITE:' . strtolower($prNumber);
            }
            
            $date = $this->parseDate($row['date']);
            return 'PR_CREATE:' . $date . '|' . strtolower(trim($row['department'])) . '|' . strtolower(trim($row['requester']));
        });

        foreach ($grouped as $key => $items) {
            $firstRow = $items->first();
            $isOverwrite = str_starts_with($key, 'PR_OVERWRITE:');
            
            try {
                DB::transaction(function () use ($firstRow, $items, $isOverwrite) {
                    $requestDate = $this->parseDate($firstRow['date']);
                    $pr = null;

                    if ($isOverwrite) {
                        $prNumber = trim($firstRow['pr_number']);
                        $pr = PurchaseRequest::where('pr_number', $prNumber)->first();

                        if ($pr) {
                            if (!$this->overwrite) {
                                Log::info("PurchaseRequestsImport: Skipping PR {$prNumber} because overwrite flag is false.");
                                return; // Stop transaction block silently
                            }
                            if ($pr->status !== 'draft') {
                                Log::warning("PurchaseRequestsImport: Cannot overwrite PR {$prNumber} because status is {$pr->status}. Only draft PRs can be amended via import.");
                                return; // Stop transaction block silently
                            }
                            
                            // Valid overwrite scenario. Update Headers, Clear existing items
                            $pr->update([
                                'request_date' => $requestDate,
                                'department' => $firstRow['department'],
                                'requester' => $firstRow['requester'],
                                'notes' => $firstRow['notes'] ?? null,
                            ]);
                            $pr->items()->delete();
                        } else {
                            Log::warning("PurchaseRequestsImport: PR {$prNumber} not found in DB. Skipping.");
                            return; // Stop transaction block silently
                        }
                    } else {
                        // Create completely new PR
                        $pr = PurchaseRequest::create([
                            'pr_number' => PurchaseRequest::generatePrNumber(),
                            'request_date' => $requestDate,
                            'department' => $firstRow['department'],
                            'requester' => $firstRow['requester'],
                            'status' => 'draft',
                            'notes' => $firstRow['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }

                    // Map all rows targeting this distinct PR to its items
                    foreach ($items as $item) {
                        if (empty($item['product_code']) || empty($item['quantity'])) continue;

                        $product = Product::where('sku', $item['product_code'])->orWhere('code', $item['product_code'])->first();
                        
                        if ($product) {
                            $pr->items()->create([
                                'product_id' => $product->id,
                                'qty' => $item['quantity'],
                                'description' => $item['item_description'] ?? null,
                            ]);
                        } else {
                            Log::warning("PurchaseRequestsImport: Product code [{$item['product_code']}] not found.");
                        }
                    }
                });
            } catch (\Exception $e) {
                Log::error("PurchaseRequestsImport Error on key [{$key}]: " . $e->getMessage());
                continue;
            }
        }
    }

    private function parseDate($value)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $ex) {
                return now()->format('Y-m-d');
            }
        }
    }
}
