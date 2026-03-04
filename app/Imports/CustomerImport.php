<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class CustomerImport implements ToModel, WithHeadingRow, WithUpserts
{
    protected bool $overwrite;

    public function __construct(bool $overwrite = false)
    {
        $this->overwrite = $overwrite;
    }

    public function uniqueBy()
    {
        return 'code';
    }

    public function model(array $row)
    {
        if (!isset($row['name'])) {
            return null;
        }

        $code = $row['code'] ?? 'CUST-' . strtoupper(bin2hex(random_bytes(3)));

        if ($this->overwrite) {
            $existing = Customer::where('code', $code)->first();
            if ($existing) {
                $existing->update([
                    'name'           => $row['name'],
                    'contact_person' => $row['contact_person'] ?? $existing->contact_person,
                    'email'          => $row['email'] ?? $existing->email,
                    'phone'          => $row['phone'] ?? $existing->phone,
                    'address'        => $row['address'] ?? $existing->address,
                    'city'           => $row['city'] ?? $existing->city,
                    'tax_id'         => $row['tax_id'] ?? $existing->tax_id,
                    'customer_type'  => $row['type'] ?? $existing->customer_type,
                    'payment_terms'  => $row['payment_terms'] ?? $existing->payment_terms,
                    'payment_days'   => $row['payment_days'] ?? $existing->payment_days,
                ]);
                return null; // Already updated, don't create
            }
        } else {
            // Skip if code already exists
            if (Customer::where('code', $code)->exists()) {
                return null;
            }
        }

        return new Customer([
            'name'           => $row['name'],
            'code'           => $code,
            'contact_person' => $row['contact_person'] ?? null,
            'email'          => $row['email'] ?? null,
            'phone'          => $row['phone'] ?? null,
            'address'        => $row['address'] ?? null,
            'city'           => $row['city'] ?? null,
            'tax_id'         => $row['tax_id'] ?? null,
            'customer_type'  => $row['type'] ?? 'regular',
            'payment_terms'  => $row['payment_terms'] ?? 'COD',
            'payment_days'   => $row['payment_days'] ?? 0,
            'is_active'      => true,
        ]);
    }
}
