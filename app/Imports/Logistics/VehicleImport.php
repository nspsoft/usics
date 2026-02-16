<?php

namespace App\Imports\Logistics;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;

class VehicleImport implements OnEachRow, WithHeadingRow, WithValidation, WithMapping
{
    /**
     * Map the row to a standard format before validation and processing.
     * This allows us to handle Indonesian headers (No Polisi, Tipe, dll) or English headers.
     */
    public function map($row): array
    {
        return [
            'plate_number'    => $row['plate_number'] ?? $row['no_polisi'] ?? $row['nopol'] ?? $row['plate_no'] ?? null,
            'type'            => $row['type'] ?? $row['tipe'] ?? $row['jenis'] ?? $row['vehicle_type'] ?? null,
            'brand'           => $row['brand'] ?? $row['merek'] ?? $row['merk'] ?? null,
            'model'           => $row['model'] ?? null,
            'year'            => $row['year'] ?? $row['tahun'] ?? null,
            'chassis_number'  => $row['chassis_number'] ?? $row['no_rangka'] ?? null,
            'engine_number'   => $row['engine_number'] ?? $row['no_mesin'] ?? null,
            'fuel_type'       => $row['fuel_type'] ?? $row['jenis_bbm'] ?? $row['bahan_bakar'] ?? null,
            'status'          => $row['status'] ?? null,
            'ownership'       => $row['ownership'] ?? $row['kepemilikan'] ?? 'Owned',
            'driver_name'     => $row['driver_name'] ?? $row['nama_supir'] ?? $row['supir'] ?? null,
            'capacity_weight' => $row['capacity_weight'] ?? $row['berat'] ?? $row['kapasitas_berat'] ?? null,
            'capacity_volume' => $row['capacity_volume'] ?? $row['volume'] ?? $row['kapasitas_volume'] ?? null,
            'stnk_number'     => $row['stnk_number'] ?? $row['no_stnk'] ?? null,
            'stnk_expiry_yyyy_mm_dd' => $row['stnk_expiry_yyyy_mm_dd'] ?? $row['stnk_expiry'] ?? $row['tgl_stnk'] ?? null,
            'kir_number'      => $row['kir_number'] ?? $row['no_kir'] ?? null,
            'kir_expiry_yyyy_mm_dd'  => $row['kir_expiry_yyyy_mm_dd'] ?? $row['kir_expiry'] ?? $row['tgl_kir'] ?? null,
            'purchase_date_yyyy_mm_dd' => $row['purchase_date_yyyy_mm_dd'] ?? $row['purchase_date'] ?? $row['tgl_beli'] ?? null,
            'purchase_price'  => $row['purchase_price'] ?? $row['harga_beli'] ?? null,
            'notes'           => $row['notes'] ?? $row['keterangan'] ?? $row['catatan'] ?? null,
        ];
    }

    public function onRow(Row $row)
    {
        // When using WithMapping, validation runs on the MAPPED array.
        // However, onRow(Row $row) normally gives access to the original row.
        // But since we need the mapped & validated data, we can just use the map() logic again 
        // OR rely on the fact that if it passed validation, the required fields exist.
        // To be safe and consistent, let's reuse the mapping logic by calling map() explicitly
        // or just re-extracting from the row (since row index might be needed).
        
        $originalArray = $row->toArray();
        $mapped = $this->map($originalArray);
        
        if (empty($mapped['plate_number'])) return;

        // Normalize status
        $rawStatus = isset($mapped['status']) ? strtolower(trim($mapped['status'])) : 'available';
        $validStatuses = ['available', 'maintenance', 'busy'];
        $status = in_array($rawStatus, $validStatuses) ? $rawStatus : 'available';

        \App\Models\Vehicle::updateOrCreate(
            ['license_plate' => trim($mapped['plate_number'])],
            [
                'vehicle_type'    => $mapped['type'],
                'brand'           => $mapped['brand'],
                'model'           => $mapped['model'],
                'year'            => $mapped['year'],
                'chassis_number'  => $mapped['chassis_number'],
                'engine_number'   => $mapped['engine_number'],
                'fuel_type'       => $mapped['fuel_type'],
                'status'          => $status,
                'ownership'       => $mapped['ownership'],
                'driver_name'     => $mapped['driver_name'],
                'capacity_weight' => $mapped['capacity_weight'],
                'capacity_volume' => $mapped['capacity_volume'],
                'stnk_number'     => $mapped['stnk_number'],
                'stnk_expiry'     => $this->transformDate($mapped['stnk_expiry_yyyy_mm_dd']),
                'kir_number'      => $mapped['kir_number'],
                'kir_expiry'      => $this->transformDate($mapped['kir_expiry_yyyy_mm_dd']),
                'purchase_date'   => $this->transformDate($mapped['purchase_date_yyyy_mm_dd']),
                'purchase_price'  => $mapped['purchase_price'],
                'notes'           => $mapped['notes'],
            ]
        );
    }

    public function rules(): array
    {
        return [
            'plate_number'    => 'required',
            'type'            => 'required',
            'brand'           => 'required',
            'capacity_weight' => 'nullable|numeric',
            'capacity_volume' => 'nullable|numeric',
            'year'            => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
        ];
    }

    private function transformDate($value, $format = 'Y-m-d')
    {
        try {
            if (empty($value)) return null;
            
            // Handle numeric Excel date
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format($format);
            }
            
            // Handle d/m/Y format (DD/MM/YYYY) commonly used in ID/Excel
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->format($format);
            }

            // Fallback to Y-m-d
            return Carbon::createFromFormat($format, $value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
}
