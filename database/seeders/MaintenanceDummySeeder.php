<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use App\Models\Sparepart;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaintenanceDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Machines Exist
        $machines = Machine::all();
        if ($machines->isEmpty()) {
            // Create default machines if empty
            $defaultMachines = [
                ['name' => 'MILL-X1', 'code' => 'M001'],
                ['name' => 'MILL-X2', 'code' => 'M002'],
                ['name' => 'CUT-V7', 'code' => 'M003'],
                ['name' => 'SLIT-S5', 'code' => 'M004'],
                ['name' => 'PACK-P1', 'code' => 'M005'],
            ];
            foreach ($defaultMachines as $m) {
                Machine::create(array_merge($m, ['is_active' => true]));
            }
            $machines = Machine::all();
        }

        // 2. Update all machines with advanced details
        foreach ($machines as $index => $machine) {
            $machine->update([
                'qr_code_uuid' => $machine->qr_code_uuid ?? (string) Str::uuid(),
                'purchase_date' => Carbon::now()->subMonths(12 + ($index * 3)),
                'runtime_hours' => 200 + ($index * 150) + rand(10, 50),
                'purchase_price' => (50 + ($index * 50)) * 1000000, // IDR 50.000.000 to IDR 250.000.000
            ]);
        }

        // 3. Seed Spareparts (16 realistic industrial items)
        $parts = [
            // === AMAN (Good stock) ===
            ['name' => 'Hydraulic Filter', 'part_number' => 'HYD-001', 'stock' => 15, 'min_stock' => 5, 'location' => 'Shelf A-1', 'unit_cost' => 150000],
            ['name' => 'Conveyor Belt 2m', 'part_number' => 'CNV-200', 'stock' => 8, 'min_stock' => 2, 'location' => 'Shelf C-1', 'unit_cost' => 350000],
            ['name' => 'Ball Bearing 6205-2RS', 'part_number' => 'BRG-6205', 'stock' => 25, 'min_stock' => 10, 'location' => 'Shelf A-3', 'unit_cost' => 85000],
            ['name' => 'V-Belt A68', 'part_number' => 'VBL-A68', 'stock' => 12, 'min_stock' => 4, 'location' => 'Shelf C-2', 'unit_cost' => 120000],
            ['name' => 'Lubricant Grease 1kg', 'part_number' => 'LUB-GRS1', 'stock' => 20, 'min_stock' => 5, 'location' => 'Chemical Store', 'unit_cost' => 75000],
            ['name' => 'Pneumatic Cylinder 50mm', 'part_number' => 'PNC-050', 'stock' => 6, 'min_stock' => 2, 'location' => 'Shelf D-1', 'unit_cost' => 1850000],

            // === WARNING (Low stock approaching minimum) ===
            ['name' => 'Proximity Sensor NPN', 'part_number' => 'SNS-PXN01', 'stock' => 4, 'min_stock' => 3, 'location' => 'Shelf B-1', 'unit_cost' => 275000],
            ['name' => 'Rubber Gasket Set DN50', 'part_number' => 'GSK-DN50', 'stock' => 6, 'min_stock' => 5, 'location' => 'Shelf A-4', 'unit_cost' => 45000],
            ['name' => 'Cooling Fan 220V AC', 'part_number' => 'FAN-220AC', 'stock' => 3, 'min_stock' => 2, 'location' => 'Shelf E-2', 'unit_cost' => 185000],

            // === KRITIS (Critical - stock at or below minimum) ===
            ['name' => 'Cutting Blade 500mm', 'part_number' => 'BLD-500', 'stock' => 2, 'min_stock' => 5, 'location' => 'Shelf B-2', 'unit_cost' => 450000],
            ['name' => 'Servo Motor Driver', 'part_number' => 'SRV-009', 'stock' => 1, 'min_stock' => 2, 'location' => 'Secure Cabinet', 'unit_cost' => 2500000],
            ['name' => 'Fuse Cartridge 30A', 'part_number' => 'FUS-30A', 'stock' => 2, 'min_stock' => 10, 'location' => 'Electrical Panel', 'unit_cost' => 25000],
            ['name' => 'Solenoid Valve 24V DC', 'part_number' => 'SOL-24DC', 'stock' => 1, 'min_stock' => 3, 'location' => 'Shelf D-2', 'unit_cost' => 780000],
            ['name' => 'Spray Nozzle 0.5mm', 'part_number' => 'NZL-05MM', 'stock' => 3, 'min_stock' => 8, 'location' => 'Shelf B-3', 'unit_cost' => 95000],

            // === HABIS (Out of stock) ===
            ['name' => 'Encoder Rotary 1024PPR', 'part_number' => 'ENC-1024', 'stock' => 0, 'min_stock' => 2, 'location' => 'Secure Cabinet', 'unit_cost' => 3200000],
            ['name' => 'Spur Gear Module 2 Z30', 'part_number' => 'GER-M2Z30', 'stock' => 0, 'min_stock' => 3, 'location' => 'Shelf F-1', 'unit_cost' => 650000],
        ];

        foreach ($parts as $part) {
            Sparepart::updateOrCreate(
                ['part_number' => $part['part_number']],
                $part
            );
        }

        $allParts = Sparepart::all();

        // 4. Cleanup old logs/schedules to avoid duplicates
        MaintenanceSchedule::query()->delete();
        MaintenanceLog::query()->delete();

        // 5. Create Schedules & Logs for each machine
        $breakdownDescriptions = [
            'Overheating motor and driver replacement', 
            'Conveyor snappage and belt replacement', 
            'Sensor misalignment and recalibration', 
            'Hydraulic pressure loss and leak fix'
        ];

        foreach ($machines as $index => $machine) {
            // A. Create PM Schedules
            // Lubrication Schedule
            MaintenanceSchedule::create([
                'machine_id' => $machine->id,
                'task_name' => 'Monthly Lubrication Check',
                'description' => 'Check oil levels and lubricate moving parts.',
                'frequency_days' => 30,
                'last_performed_at' => Carbon::now()->subDays(25),
                'next_due_date' => $index === 0 
                    ? Carbon::now()->subDays(5) // Overdue for MILL-X1 to test health decrease
                    : Carbon::now()->addDays(5 + ($index * 5)),
                'status' => 'active',
            ]);

            // Calibration Schedule
            MaintenanceSchedule::create([
                'machine_id' => $machine->id,
                'task_name' => 'Quarterly Calibration',
                'description' => 'Calibrate sensors and alignment.',
                'frequency_days' => 90,
                'last_performed_at' => Carbon::now()->subDays(80),
                'next_due_date' => Carbon::now()->addDays(10 + ($index * 15)),
                'status' => 'active',
            ]);

            // B. Create Historical Breakdown Logs (For MTBF calculations)
            // Breakdown 1: 50 days ago -> 49 days ago (Duration: 1 day)
            $log1 = MaintenanceLog::create([
                'machine_id' => $machine->id,
                'type' => 'breakdown',
                'description' => '[Tingkat: HIGH] ' . $breakdownDescriptions[($index) % 4] . ' (Malfungsi Awal)',
                'started_at' => Carbon::now()->subDays(50),
                'finished_at' => Carbon::now()->subDays(49),
                'technician_name' => 'Budi Santoso',
                'status' => 'resolved',
            ]);
            $log1->spareparts()->attach($allParts->random()->id, ['qty_used' => 1]);

            // Breakdown 2: 30 days ago -> 29 days ago (Duration: 1 day, Interval from B1 = 20 days)
            $log2 = MaintenanceLog::create([
                'machine_id' => $machine->id,
                'type' => 'breakdown',
                'description' => '[Tingkat: MEDIUM] ' . $breakdownDescriptions[($index + 1) % 4] . ' (Pemeriksaan berkala)',
                'started_at' => Carbon::now()->subDays(30),
                'finished_at' => Carbon::now()->subDays(29),
                'technician_name' => 'Agus Setiawan',
                'status' => 'resolved',
            ]);
            $log2->spareparts()->attach($allParts->random()->id, ['qty_used' => 2]);

            // Breakdown 3: 10 days ago -> 9 days ago (Duration: 1 day, Interval from B2 = 20 days)
            // Total intervals: 20 days, 20 days. MTBF will be exactly 20.0 days!
            $log3 = MaintenanceLog::create([
                'machine_id' => $machine->id,
                'type' => 'breakdown',
                'description' => '[Tingkat: HIGH] ' . $breakdownDescriptions[($index + 2) % 4] . ' (Insiden kritis)',
                'started_at' => Carbon::now()->subDays(10),
                'finished_at' => Carbon::now()->subDays(9),
                'technician_name' => 'Budi Santoso',
                'status' => 'resolved',
            ]);
            $log3->spareparts()->attach($allParts->random()->id, ['qty_used' => 1]);

            // C. Create Active Breakdown Log for some machines to showcase red status pulsing
            if ($index === 1) { // MILL-X2 is currently breakdown
                MaintenanceLog::create([
                    'machine_id' => $machine->id,
                    'type' => 'breakdown',
                    'description' => '[Tingkat: HIGH] Overheating motor coil detected on lines.',
                    'started_at' => Carbon::now()->subHours(5),
                    'finished_at' => null,
                    'technician_name' => 'Agus Setiawan',
                    'status' => 'in_progress',
                ]);
            }
        }
    }
}
