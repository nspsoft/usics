<?php

namespace App\Imports;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class SupplierContactImport implements ToCollection, WithHeadingRow
{
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!isset($row['supplier_code']) || !isset($row['pic_name'])) {
                continue;
            }

            $supplierCode = trim($row['supplier_code']);
            $supplier = Supplier::where('code', $supplierCode)->first();

            if (!$supplier) {
                Log::warning("SupplierContactImport: Supplier [{$supplierCode}] not found. Skipping contact.");
                continue; 
            }

            $picName = trim($row['pic_name']);
            // Header mapped to snake_case automatically: internal_id_do_not_change
            $internalId = isset($row['internal_id_do_not_change']) && trim($row['internal_id_do_not_change']) !== '' 
                          ? trim($row['internal_id_do_not_change']) 
                          : null;
            
            $existingContact = null;

            // 1. Try matching by internal ID first (most accurate)
            if ($internalId) {
                $existingContact = SupplierContact::where('id', $internalId)
                                     ->where('supplier_id', $supplier->id)
                                     ->first();
            }

            // 2. Fallback matching by PIC Name (if ID wasn't provided or wasn't found)
            if (!$existingContact) {
                $existingContact = SupplierContact::where('supplier_id', $supplier->id)
                                     ->where('name', $picName)
                                     ->first();
            }

            if ($existingContact) {
                if (!$this->overwrite) {
                    Log::info("SupplierContactImport: Skipping existing contact [{$picName}] because overwrite is false.");
                    continue;
                }

                // Overwrite Existing Contact
                $existingContact->update([
                    'name'     => $picName,
                    'position' => $row['position'] ?? null,
                    'phone'    => $row['phone'] ?? null,
                    'email'    => $row['email'] ?? null,
                ]);

                continue; // Done with this row
            }

            // Create New Contact
            SupplierContact::create([
                'supplier_id' => $supplier->id,
                'name'        => $picName,
                'position'    => $row['position'] ?? null,
                'phone'       => $row['phone'] ?? null,
                'email'       => $row['email'] ?? null,
            ]);
        }
    }
}
