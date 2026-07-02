<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\BomComponent;
use App\Models\BomOperation;
use App\Models\BomOutput;
use App\Models\Company;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductionEntry;
use App\Models\MaterialConsumption;
use App\Models\Shift;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\WorkOrder;
use App\Models\WorkOrderComponent;
use App\Models\WorkOrderOutput;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionIntelligenceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command->error('No company found. Please run DemoDataSeeder first.');
            return;
        }

        $user = User::first();
        if (!$user) {
            $this->command->error('No user found. Please run DemoDataSeeder first.');
            return;
        }

        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        if (!$warehouse) {
            $this->command->error('No warehouse found. Please run DemoDataSeeder first.');
            return;
        }

        // 1. Cleanup existing manufacturing data
        $this->command->info("Cleaning up old manufacturing data...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MaterialConsumption::truncate();
        ProductionEntry::truncate();
        WorkOrderOutput::truncate();
        WorkOrderComponent::truncate();
        WorkOrder::truncate();
        BomOutput::truncate();
        BomOperation::truncate();
        BomComponent::truncate();
        Bom::truncate();
        Shift::truncate();
        // Clear stock movements that are related to manufacturing
        StockMovement::whereIn('type', ['production_in', 'production_out'])
            ->orWhere('reference_type', WorkOrder::class)
            ->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Ensure machines exist
        if (Machine::count() === 0) {
            $this->command->info("Machines table is empty. Running UscMachineSeeder...");
            $this->call(UscMachineSeeder::class);
        } else {
            $this->command->info("Using existing " . Machine::count() . " machines from UscMachineSeeder.");
        }

        // 3. Create Shift Schedules
        $this->command->info("Seeding shift schedules...");
        $shifts = [
            ['name' => 'Shift 1', 'start_time' => '07:00:00', 'end_time' => '15:00:00'],
            ['name' => 'Shift 2', 'start_time' => '15:00:00', 'end_time' => '23:00:00'],
            ['name' => 'Shift 3', 'start_time' => '23:00:00', 'end_time' => '07:00:00'],
        ];

        foreach ($shifts as $s) {
            Shift::create(array_merge($s, ['is_active' => true]));
        }
        $shiftModels = Shift::all();

        // 4. Initialize Raw Material Stocks (Coils & Consumables)
        // Since we are running production entries and material consumption,
        // we must make sure there is enough raw material in stock!
        $this->command->info("Initializing raw material stocks in warehouse...");
        $kgUnit = Unit::where('code', 'KG')->first() ?? Unit::first();
        $pcsUnit = Unit::where('code', 'PCS')->first() ?? Unit::first();

        $startingStocks = [
            'COIL-HR-SPHC-2.0' => ['qty' => 500000.0, 'cost' => 11500.0, 'unit' => $kgUnit],
            'COIL-CR-SPCC-1.2' => ['qty' => 400000.0, 'cost' => 13200.0, 'unit' => $kgUnit],
            'COIL-GA-SGCC-0.8' => ['qty' => 300000.0, 'cost' => 15000.0, 'unit' => $kgUnit],
            'SC-HRC-TRIAL' => ['qty' => 100000.0, 'cost' => 11000.0, 'unit' => $kgUnit],
            'SP-SLITKNIFE-SKD11-300' => ['qty' => 200.0, 'cost' => 4500000.0, 'unit' => $pcsUnit],
            'SP-SHEARBLADE-2500' => ['qty' => 10.0, 'cost' => 18500000.0, 'unit' => $pcsUnit],
            'SP-RUBBERING-120' => ['qty' => 1000.0, 'cost' => 150000.0, 'unit' => $pcsUnit],
        ];

        foreach ($startingStocks as $sku => $stockData) {
            $product = Product::where('sku', $sku)->first();
            if (!$product) continue;

            $stock = ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'qty_on_hand' => $stockData['qty'],
                    'qty_reserved' => 0.0,
                    'qty_incoming' => 0.0,
                    'qty_outgoing' => 0.0,
                    'avg_cost' => $stockData['cost'],
                ]
            );

            // Log stock movement as starting inventory
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'qty' => $stockData['qty'],
                'balance_before' => 0.0,
                'balance_after' => $stockData['qty'],
                'type' => StockMovement::TYPE_PO_RECEIVE, // Seeded as PO receive
                'notes' => 'Starting inventory for manufacturing simulation',
                'created_by' => $user->id,
            ]);
        }

        // 5. Seed 5 BOMs & Routing Steps (BOM Operations)
        $this->command->info("Seeding Bills of Materials (BOM) & Routings...");

        $bomDefinitions = [
            // BOM 1
            [
                'code' => 'BOM-SLIT-CR12-300',
                'name' => 'BOM Slitting CR SPCC 1.2mm x 300mm',
                'product_sku' => 'SLIT-CR-SPCC-1.2-300',
                'qty' => 1.0, // base qty
                'unit' => $kgUnit,
                'description' => 'BOM untuk pemrosesan slitting coil SPCC 1.2mm menjadi strip lebar 300mm.',
                'components' => [
                    ['sku' => 'COIL-CR-SPCC-1.2', 'qty' => 1.02, 'scrap_rate' => 2.0], // 1.02kg raw coil to produce 1kg slitted strip
                ],
                'operations' => [
                    ['name' => 'Coil Loading & Setup', 'seq' => 1, 'setup' => 15, 'process' => 5, 'labor' => 50000, 'machine' => 150000, 'desc' => 'Pemasangan coil canai dingin ke decoiler slitting machine.'],
                    ['name' => 'Slitting & Rewinding', 'seq' => 2, 'setup' => 5, 'process' => 30, 'labor' => 100000, 'machine' => 500000, 'desc' => 'Proses pemotongan coil menjadi strips memanjang dan penggulungan ulang.'],
                    ['name' => 'Strip Banding & Unloading', 'seq' => 3, 'setup' => 0, 'process' => 10, 'labor' => 30000, 'machine' => 50000, 'desc' => 'Pengikatan strip hasil slitting dan penurunan dari recoiler.']
                ],
                'outputs' => [
                    ['sku' => 'SLIT-CR-SPCC-1.2-300', 'slit_count' => 4, 'ratio' => 0.245], // 4 strips of 300mm = 1200mm (~98% of 1219mm width)
                    ['sku' => 'SLIT-CR-SPCC-1.2-120', 'slit_count' => 0, 'ratio' => 0.0] // Side scrap / other width (optional)
                ]
            ],
            // BOM 2
            [
                'code' => 'BOM-SLIT-HR20-150',
                'name' => 'BOM Slitting HR SPHC 2.0mm x 150mm',
                'product_sku' => 'SLIT-HR-SPHC-2.0-150',
                'qty' => 1.0,
                'unit' => $kgUnit,
                'description' => 'BOM untuk slitting coil SPHC 2.0mm menjadi strip lebar 150mm.',
                'components' => [
                    ['sku' => 'COIL-HR-SPHC-2.0', 'qty' => 1.03, 'scrap_rate' => 3.0],
                ],
                'operations' => [
                    ['name' => 'Coil Loading & Setup', 'seq' => 1, 'setup' => 20, 'process' => 5, 'labor' => 50000, 'machine' => 150000, 'desc' => 'Loading coil canai panas berat ke slitting line.'],
                    ['name' => 'Slitting & Rewinding', 'seq' => 2, 'setup' => 5, 'process' => 35, 'labor' => 100000, 'machine' => 450000, 'desc' => 'Slitting coil SPHC dengan pisau putar tebal.'],
                    ['name' => 'Tension Adjust & Banding', 'seq' => 3, 'setup' => 0, 'process' => 15, 'labor' => 30000, 'machine' => 50000, 'desc' => 'Penyusunan coil strip dan pemikatan akhir.']
                ],
                'outputs' => [
                    ['sku' => 'SLIT-HR-SPHC-2.0-150', 'slit_count' => 6, 'ratio' => 0.122], // 6 strips of 150mm = 900mm
                    ['sku' => 'SLIT-HR-SPHC-2.0-200', 'slit_count' => 1, 'ratio' => 0.163] // 1 strip of 200mm = 200mm (Total 1100mm out of 1219mm)
                ]
            ],
            // BOM 3
            [
                'code' => 'BOM-SHT-HR20-1219',
                'name' => 'BOM Shearing HR Sheet 1219mm x 2438mm',
                'product_sku' => 'SHT-HR-SPHC-2.0-1219x2438',
                'qty' => 1.0,
                'unit' => $kgUnit,
                'description' => 'BOM untuk meratakan coil canai panas dan memotong menjadi lembaran (shearing sheet).',
                'components' => [
                    ['sku' => 'COIL-HR-SPHC-2.0', 'qty' => 1.01, 'scrap_rate' => 1.0],
                ],
                'operations' => [
                    ['name' => 'Coil Loading & Leveller Feed', 'seq' => 1, 'setup' => 15, 'process' => 5, 'labor' => 50000, 'machine' => 100000, 'desc' => 'Loading coil ke levelling line untuk meratakan lekukan plat.'],
                    ['name' => 'Decoil & Continuous Shearing', 'seq' => 2, 'setup' => 5, 'process' => 25, 'labor' => 80000, 'machine' => 300000, 'desc' => 'Pemotongan lembaran baja dengan panjang konstan 2438mm.'],
                    ['name' => 'Sheet Stacking & Washing', 'seq' => 3, 'setup' => 0, 'process' => 10, 'labor' => 30000, 'machine' => 50000, 'desc' => 'Pembersihan oli permukaan dan penumpukan rapi di pallet.']
                ],
                'outputs' => []
            ],
            // BOM 4
            [
                'code' => 'BOM-FG-FENDER',
                'name' => 'BOM Blanking Fender Panel',
                'product_sku' => 'FG-BLNK-FENDER-CR12',
                'qty' => 1.0,
                'unit' => $pcsUnit,
                'description' => 'BOM untuk mencetak (blanking) panel fender luar mobil dari strip canai dingin.',
                'components' => [
                    ['sku' => 'SLIT-CR-SPCC-1.2-300', 'qty' => 1.05, 'scrap_rate' => 5.0],
                ],
                'operations' => [
                    ['name' => 'Press Setup & Nesting Die Alignment', 'seq' => 1, 'setup' => 30, 'process' => 0, 'labor' => 80000, 'machine' => 200000, 'desc' => 'Pemasangan cetakan (dies) fender pada mesin press blanking.'],
                    ['name' => 'Press Blanking', 'seq' => 2, 'setup' => 5, 'process' => 15, 'labor' => 50000, 'machine' => 400000, 'desc' => 'Proses press blanking continuous strip baja.'],
                    ['name' => 'Deburring & Visual QC', 'seq' => 3, 'setup' => 0, 'process' => 10, 'labor' => 30000, 'machine' => 20000, 'desc' => 'Pembersihan sisa tajam (burr) dan inspeksi visual permukaan.']
                ],
                'outputs' => []
            ],
            // BOM 5
            [
                'code' => 'BOM-FG-TWB-DOOR',
                'name' => 'BOM Tailor Welded Blank (TWB) Door Panel',
                'product_sku' => 'FG-TWB-DOORPANEL-01',
                'qty' => 1.0,
                'unit' => $pcsUnit,
                'description' => 'BOM untuk mengelas laser dua ketebalan lembar baja berbeda untuk pintu samping mobil.',
                'components' => [
                    ['sku' => 'SLIT-CR-SPCC-1.2-120', 'qty' => 0.40, 'scrap_rate' => 1.0], // 0.4 kg of 1.2mm strip
                    ['sku' => 'SLIT-GA-SGCC-0.8-250', 'qty' => 0.60, 'scrap_rate' => 1.0], // 0.6 kg of 0.8mm strip
                ],
                'operations' => [
                    ['name' => 'Laser Welder Setup & Fixture Alignment', 'seq' => 1, 'setup' => 45, 'process' => 0, 'labor' => 100000, 'machine' => 300000, 'desc' => 'Pengaturan koordinat robot las dan penjepit lembaran baja.'],
                    ['name' => 'Robotic Laser Welding', 'seq' => 2, 'setup' => 5, 'process' => 20, 'labor' => 50000, 'machine' => 600000, 'desc' => 'Pengelasan seam presisi dengan sinar laser.'],
                    ['name' => 'Weld Seam Testing & Bending', 'seq' => 3, 'setup' => 0, 'process' => 10, 'labor' => 40000, 'machine' => 50000, 'desc' => 'Uji tarik/QC sambungan las laser (Tailor Welded Blank).']
                ],
                'outputs' => []
            ]
        ];

        $bomModels = [];

        foreach ($bomDefinitions as $bomDef) {
            $product = Product::where('sku', $bomDef['product_sku'])->first();
            if (!$product) continue;

            $bom = Bom::create([
                'company_id' => $company->id,
                'code' => $bomDef['code'],
                'name' => $bomDef['name'],
                'product_id' => $product->id,
                'qty' => $bomDef['qty'],
                'unit_id' => $bomDef['unit']->id,
                'version' => '1.0',
                'status' => Bom::STATUS_ACTIVE,
                'description' => $bomDef['description'],
                'lead_time_days' => rand(1, 3),
                'is_active' => true,
            ]);

            // Add Components
            foreach ($bomDef['components'] as $seqIndex => $compDef) {
                $compProduct = Product::where('sku', $compDef['sku'])->first();
                if (!$compProduct) continue;

                BomComponent::create([
                    'bom_id' => $bom->id,
                    'product_id' => $compProduct->id,
                    'qty' => $compDef['qty'],
                    'unit_id' => $compProduct->unit_id,
                    'scrap_rate' => $compDef['scrap_rate'],
                    'type' => 'material',
                    'sequence' => $seqIndex + 1,
                    'notes' => 'Raw material component',
                ]);
            }

            // Add Operations (Routing)
            foreach ($bomDef['operations'] as $opDef) {
                BomOperation::create([
                    'bom_id' => $bom->id,
                    'name' => $opDef['name'],
                    'sequence' => $opDef['seq'],
                    'setup_time_mins' => $opDef['setup'],
                    'processing_time_mins' => $opDef['process'],
                    'labor_cost' => $opDef['labor'],
                    'machine_cost' => $opDef['machine'],
                    'description' => $opDef['desc'],
                ]);
            }

            // Add Outputs (For multiple outputs in slitting)
            if (!empty($bomDef['outputs'])) {
                foreach ($bomDef['outputs'] as $outDef) {
                    $outProduct = Product::where('sku', $outDef['sku'])->first();
                    if (!$outProduct) continue;

                    BomOutput::create([
                        'bom_id' => $bom->id,
                        'product_id' => $outProduct->id,
                        'qty_ratio' => $outDef['ratio'],
                        'slit_count' => $outDef['slit_count'],
                        'unit_id' => $outProduct->unit_id,
                        'notes' => 'Slitted output strip product',
                    ]);
                }
            }

            $bomModels[] = $bom;
        }

        // 6. Seed Work Orders, Production Logs, and Consumptions
        $this->command->info("Seeding Work Orders, Production Logs, and Material Consumptions...");

        $woStatuses = [
            WorkOrder::STATUS_DRAFT,
            WorkOrder::STATUS_CONFIRMED,
            WorkOrder::STATUS_IN_PROGRESS,
            WorkOrder::STATUS_COMPLETED,
            WorkOrder::STATUS_CANCELLED,
        ];

        // Fetch realistic machines to assign
        $slitterMachines = Machine::where('type', 'Slitting')->get();
        $blankingMachines = Machine::where('type', 'Blanking')->get();
        $weldingMachines = Machine::where('type', 'Welding')->get();
        $shearingMachines = Machine::where('type', 'Guillotine Shear')->get();
        $levellingMachines = Machine::where('type', 'Levelling')->get();

        // Specs for the 3 active Work Orders matching the screenshot
        $activeSpecs = [
            [
                'wo_number' => 'WO-202606-0002',
                'bom_code' => 'BOM-FG-TWB-DOOR',
                'qty_planned' => 348.0,
                'qty_produced_target' => 307.0,
                'planned_start' => Carbon::parse('2026-06-28'),
                'planned_end' => Carbon::parse('2026-06-29'),
                'status' => WorkOrder::STATUS_IN_PROGRESS,
            ],
            [
                'wo_number' => 'WO-202606-0013',
                'bom_code' => 'BOM-SLIT-HR20-150',
                'qty_planned' => 11661.0,
                'qty_produced_target' => 8804.0,
                'planned_start' => Carbon::parse('2026-06-30'),
                'planned_end' => Carbon::parse('2026-07-01'),
                'status' => WorkOrder::STATUS_IN_PROGRESS,
            ],
            [
                'wo_number' => 'WO-202607-0017',
                'bom_code' => 'BOM-SLIT-HR20-150',
                'qty_planned' => 29599.0,
                'qty_produced_target' => 20423.0,
                'planned_start' => Carbon::parse('2026-07-01'),
                'planned_end' => Carbon::parse('2026-07-03'),
                'status' => WorkOrder::STATUS_IN_PROGRESS,
            ]
        ];

        // Create 35 Work Orders spread across the last 30 days
        for ($i = 0; $i < 35; $i++) {
            $isActiveSpec = isset($activeSpecs[$i]);
            if ($isActiveSpec) {
                $spec = $activeSpecs[$i];
                $bom = collect($bomModels)->first(fn($b) => $b->code === $spec['bom_code']);
                if (!$bom) {
                    $bom = $bomModels[array_rand($bomModels)];
                }
                $date = $spec['planned_start'];
                $status = $spec['status'];
                $qtyPlanned = $spec['qty_planned'];
                $woNumber = $spec['wo_number'];
                $plannedEnd = $spec['planned_end'];
            } else {
                $bom = $bomModels[array_rand($bomModels)];
                $date = Carbon::now()->subDays(rand(1, 30));
                
                // Older orders are mostly completed
                if ($date->lt(Carbon::now()->subDays(5))) {
                    $status = rand(0, 10) > 1 ? WorkOrder::STATUS_COMPLETED : WorkOrder::STATUS_CANCELLED;
                } else {
                    $status = $woStatuses[array_rand($woStatuses)];
                    // Prevent random in_progress to keep only the 3 specific ones active
                    if ($status === WorkOrder::STATUS_IN_PROGRESS) {
                        $status = WorkOrder::STATUS_CONFIRMED;
                    }
                }
                $qtyPlanned = $bom->unit->code === 'KG' ? rand(5000, 30000) : rand(100, 2000);
                $woNumber = 'WO-' . $date->format('Ym') . '-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT);
                $plannedEnd = $date->copy()->addDays(rand(1, 3));
            }

            $wo = WorkOrder::create([
                'company_id' => $company->id,
                'wo_number' => $woNumber,
                'bom_id' => $bom->id,
                'product_id' => $bom->product_id,
                'warehouse_id' => $warehouse->id,
                'material_warehouse_id' => $warehouse->id, // Added material warehouse
                'qty_planned' => $qtyPlanned,
                'qty_produced' => 0.0,
                'qty_rejected' => 0.0,
                'planned_start' => $date,
                'planned_end' => $plannedEnd,
                'actual_start' => null,
                'actual_end' => null,
                'status' => $status,
                'priority' => ['low', 'normal', 'high', 'urgent'][rand(0, 3)],
                'notes' => 'Work order simulated by ProductionIntelligenceSeeder',
                'created_by' => $user->id,
                'production_type' => 'regular',
            ]);

            // Initialize components and outputs from BOM
            $wo->initializeFromBom();

            // Set up appropriate machine line based on BOM type
            if ($bom->code === 'BOM-SLIT-CR12-300') {
                $targetMachine = $slitterMachines->isNotEmpty() ? $slitterMachines->random() : null;
            } elseif ($bom->code === 'BOM-SLIT-HR20-150') {
                $targetMachine = $slitterMachines->isNotEmpty() ? $slitterMachines->random() : null;
            } elseif ($bom->code === 'BOM-SHT-HR20-1219') {
                $targetMachine = $levellingMachines->isNotEmpty() ? $levellingMachines->random() : ($shearingMachines->isNotEmpty() ? $shearingMachines->random() : null);
            } elseif ($bom->code === 'BOM-FG-FENDER') {
                $targetMachine = $blankingMachines->isNotEmpty() ? $blankingMachines->random() : null;
            } else {
                $targetMachine = $weldingMachines->isNotEmpty() ? $weldingMachines->random() : null;
            }
            $machineName = $targetMachine ? $targetMachine->name : 'LINE-1';

            if (in_array($status, [WorkOrder::STATUS_IN_PROGRESS, WorkOrder::STATUS_COMPLETED])) {
                // Set start date
                $wo->update([
                    'status' => WorkOrder::STATUS_IN_PROGRESS,
                    'actual_start' => $date->copy()->addHours(8),
                ]);

                // Create 1 to 3 production entry logs
                $numEntries = $isActiveSpec ? $date->diffInDays($plannedEnd) + 1 : rand(1, 3);
                if ($numEntries <= 0) {
                    $numEntries = 1;
                }
                $totalQtyProduced = 0.0;
                $totalQtyRejected = 0.0;

                for ($entryIdx = 0; $entryIdx < $numEntries; $entryIdx++) {
                    $entryDate = $date->copy()->addDays($entryIdx);
                    if ($entryDate->gt(Carbon::now())) {
                        $entryDate = Carbon::now();
                    }

                    if ($isActiveSpec) {
                        $qtyStep = $spec['qty_produced_target'] / $numEntries;
                    } else {
                        $qtyStep = ($qtyPlanned / $numEntries);
                        if ($status === WorkOrder::STATUS_IN_PROGRESS && $entryIdx === $numEntries - 1) {
                            // Current day in-progress WO is only partially produced today
                            $qtyStep = $qtyStep * (rand(30, 70) / 100);
                        }
                    }

                    // Divide the daily qtyStep among the active shifts (Shift 1, 2, 3)
                    $shiftsToProcess = $shiftModels;
                    $numShifts = $shiftsToProcess->count();
                    $qtyPerShiftBase = $qtyStep / $numShifts;

                    foreach ($shiftsToProcess as $shift) {
                        // Skip if it's in the future (e.g. today's later shifts)
                        $isFutureShift = false;
                        if ($entryDate->isToday()) {
                            $currentHour = Carbon::now()->hour;
                            if ($shift->name === 'Shift 2' && $currentHour < 15) {
                                $isFutureShift = true;
                            } elseif ($shift->name === 'Shift 3' && $currentHour < 23) {
                                $isFutureShift = true;
                            }
                        }
                        if ($isFutureShift) continue;

                        // Qty with slight variance per shift (+/- 10%)
                        $shiftQtyPlanned = $qtyPerShiftBase * (1 + rand(-10, 10) / 100);
                        
                        // Simulated QC scrap rejects per shift
                        $rejects = (rand(1, 100) <= 12) ? rand(1, max(1, floor($shiftQtyPlanned * 0.015))) : 0.0;
                        $goodQty = max(0, $shiftQtyPlanned - $rejects);

                        // Create Production Log Entry
                        $entry = ProductionEntry::create([
                            'work_order_id' => $wo->id,
                            'production_date' => $entryDate,
                            'shift' => (string) $shift->id,
                            'qty_produced' => $goodQty,
                            'qty_rejected' => $rejects,
                            'defect_category' => $rejects > 0 ? ['dimensional', 'surface', 'material', 'assembly'][rand(0, 3)] : null,
                            'downtime_minutes' => (rand(1, 100) <= 15) ? rand(15, 90) : 0,
                            'start_time' => $shift->start_time,
                            'end_time' => $shift->end_time,
                            'machine_line' => $machineName,
                            'notes' => "Laporan produksi harian {$shift->name}.",
                            'produced_by' => $user->id,
                            'entry_user_id' => $user->id,
                        ]);

                        $totalQtyProduced += $goodQty;
                        $totalQtyRejected += $rejects;

                        // Seed corresponding Material Consumption per shift
                        foreach ($wo->components as $woComp) {
                            // Scale material consumption based on the ratio of actual raw material required
                            $multiplier = $shiftQtyPlanned / $qtyPlanned;
                            $rawConsQty = $woComp->qty_required * $multiplier * (1 + rand(-2, 3) / 100);

                            MaterialConsumption::create([
                                'work_order_id' => $wo->id,
                                'work_order_component_id' => $woComp->id,
                                'product_id' => $woComp->product_id,
                                'warehouse_id' => $warehouse->id,
                                'qty' => $rawConsQty,
                                'unit_id' => $woComp->unit_id,
                                'consumption_date' => $entryDate,
                                'consumed_by' => $user->id,
                                'batch_number' => 'BATCH-' . $entryDate->format('Ymd') . '-' . rand(100, 999),
                            ]);
                        }
                    }
                }

                // If completed, finalize the work order and trigger stock adjustment for finished goods
                if ($status === WorkOrder::STATUS_COMPLETED) {
                    $wo->refresh();

                    // If multiple outputs exist (Slitting BOMs), allocate outputs
                    if ($wo->outputs()->count() > 0) {
                        $totalWeightProduced = $totalQtyProduced * 1.0; // Assume 1:1 kg conversion ratio
                        $outputSum = $wo->outputs()->sum('qty_planned');
                        
                        foreach ($wo->outputs as $woOutput) {
                            // Scale produced outputs based on planned ratio
                            $ratio = $outputSum > 0 ? $woOutput->qty_planned / $outputSum : 1.0;
                            $outProduced = $totalQtyProduced * $ratio;
                            
                            $woOutput->update([
                                'qty_produced' => $outProduced,
                                'weight_produced' => $outProduced,
                            ]);
                        }
                    }

                    // Complete the work order
                    // This adjusts ProductStock and creates StockMovements of type TYPE_PRODUCTION_OUT!
                    $wo->complete();

                    // Post stock posted timestamp on entries
                    $wo->productionEntries()->update(['stock_posted_at' => $date->copy()->addDays($numEntries)]);
                } else {
                    // Update WO totals manually for in-progress WO
                    $wo->update([
                        'qty_produced' => $totalQtyProduced,
                        'qty_rejected' => $totalQtyRejected,
                    ]);
                }
            }
        }

        $this->command->info("Production Intelligence data seeded successfully!");
    }
}
