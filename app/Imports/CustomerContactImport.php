<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerContactImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $data = $row instanceof Collection ? $row->toArray() : (array) $row;

            $customerCode = isset($data['customer_code']) ? trim((string) $data['customer_code']) : '';
            $name = isset($data['pic_name']) ? trim((string) $data['pic_name']) : '';
            if ($customerCode === '' || $name === '') {
                continue;
            }

            $customer = Customer::query()->where('code', $customerCode)->first();
            if (!$customer) {
                continue;
            }

            $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : null;
            if ($email === '') {
                $email = null;
            }

            $phone = isset($data['phone']) ? preg_replace('/\D+/', '', (string) $data['phone']) : null;
            if ($phone === '') {
                $phone = null;
            }

            $position = isset($data['position']) ? trim((string) $data['position']) : null;
            if ($position === '') {
                $position = null;
            }

            $match = ['customer_id' => $customer->id];
            if ($email) {
                $match['email'] = $email;
            } elseif ($phone) {
                $match['phone'] = $phone;
            } else {
                $match['name'] = $name;
                $match['position'] = $position;
            }

            CustomerContact::query()->updateOrCreate($match, [
                'customer_id' => $customer->id,
                'name' => $name,
                'position' => $position,
                'phone' => $phone,
                'email' => $email,
            ]);
        }
    }
}
