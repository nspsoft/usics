<?php

echo "Bootstrapping Laravel...\n";

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use App\Imports\Logistics\VehicleImport;
use App\Models\Vehicle;

class DebugVehicleExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return new Collection([
            [
                'B 9999 DEBUG', // Plate Number
                'Truck',        // Type
                'Isuzu',        // Brand
                'Giga',         // Model
                2023,           // Year
                'CH-9999',      // Chassis
                'EN-9999',      // Engine
                'Diesel',       // Fuel
                'available',    // Status
                'Owned',        // Ownership
                'Debug Driver', // Driver
                10000,          // Cap Weight
                30,             // Cap Vol
                'STNK-DBG',     // STNK
                '2026-12-31',   // STNK Exp
                'KIR-DBG',      // KIR
                '2026-06-30',   // KIR Exp
                '2023-01-01',   // Purchase Date
                500000000,      // Price
                'Debug Note'    // Notes
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Plate Number',
            'Type',
            'Brand',
            'Model',
            'Year',
            'Chassis Number',
            'Engine Number',
            'Fuel Type',
            'Status',
            'Ownership',
            'Driver Name',
            'Capacity Weight',
            'Capacity Volume',
            'STNK Number',
            'STNK Expiry (YYYY-MM-DD)',
            'KIR Number',
            'KIR Expiry (YYYY-MM-DD)',
            'Purchase Date (YYYY-MM-DD)',
            'Purchase Price',
            'Notes',
        ];
    }
}

echo "1. Creating Debug Excel File...\n";
$stored = Excel::store(new DebugVehicleExport, 'debug_fleet.xlsx', 'public');
if ($stored) {
    echo "Store returned TRUE.\n";
} else {
    echo "Store returned FALSE.\n";
}

$path = storage_path('app/public/debug_fleet.xlsx');
echo "File expected at: $path\n";

if (!file_exists($path)) {
    echo "ERROR: File not found at $path\n";
    echo "Local Root: " . config('filesystems.disks.local.root') . "\n";
    echo "Public Root: " . config('filesystems.disks.public.root') . "\n";
    exit(1);
}

echo "2. Importing File...\n";
try {
    Excel::import(new VehicleImport, $path);
    echo "Import Helper Claaaaassss Success!\n";
} catch (\Exception $e) {
    echo "IMPORT ERROR: " . $e->getMessage() . "\n";
    if (method_exists($e, 'failures')) {
        foreach ($e->failures() as $failure) {
             echo "Row " . $failure->row() . ": " . implode(', ', $failure->errors()) . "\n";
        }
    }
}

echo "3. Verifying Data...\n";
$v = Vehicle::where('license_plate', 'B 9999 DEBUG')->first();
if ($v) {
    echo "SUCCESS! Vehicle found: " . $v->driver_name . "\n";
    // Clean up
    $v->forceDelete();
    echo "Cleaned up debug data.\n";
} else {
    echo "FAILED! Vehicle not found in DB.\n";
}
