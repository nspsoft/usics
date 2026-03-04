<?php

namespace App\Imports;

use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class SupplierImport implements ToCollection, WithHeadingRow
{
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue;
            }

            $code = trim($row['code'] ?? '');
            
            if ($code !== '') {
                // Try to find existing supplier by code
                $existingSupplier = Supplier::where('code', $code)->first();
                
                if ($existingSupplier) {
                    if (!$this->overwrite) {
                        Log::info("SupplierImport: Skipping existing supplier [{$code}] because overwrite is false.");
                        continue;
                    }

                    // Overwrite Existing
                    $existingSupplier->update([
                        'name'    => $row['name'],
                        'email'   => $row['email'],
                        'phone'   => $row['phone'],
                        'fax'     => $row['fax'] ?? null,
                        'address' => $row['address'],
                        'tax_id'  => $row['tax_id'],
                        'npwp'    => $row['npwp'] ?? null,
                        'payment_terms' => $row['payment_terms'] ?? 'Cash',
                        'payment_days'  => intval($row['payment_days'] ?? 0),
                    ]);
                    
                    continue; // Done with this row
                }
            } else {
                // Generate a new code if empty
                $code = 'SUP-' . strtoupper(bin2hex(random_bytes(3)));
            }

            // Create New Supplier
            Supplier::create([
                'name'    => $row['name'],
                'code'    => $code,
                'email'   => $row['email'],
                'phone'   => $row['phone'],
                'fax'     => $row['fax'] ?? null,
                'address' => $row['address'],
                'tax_id'  => $row['tax_id'],
                'npwp'    => $row['npwp'] ?? null,
                'payment_terms' => $row['payment_terms'] ?? 'Cash',
                'payment_days'  => intval($row['payment_days'] ?? 0),
            ]);
        }
    }
}
