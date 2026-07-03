<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Product;
use App\Models\InventoryLot;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UscStorageLocationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get USC Warehouses
        $whFg = Warehouse::where('code', 'WH-FG')->first();
        $whRm = Warehouse::where('code', 'WH-RM')->first();
        $whMain = Warehouse::where('code', 'WH-MAIN')->first();

        if (!$whFg) {
            $this->command->error('Warehouse WH-FG not found. Please run DemoDataSeeder first.');
            return;
        }

        // 2. Clean existing locations & lots to avoid duplication/constraint errors
        InventoryLot::query()->delete();
        Location::query()->delete();

        // 3. Seed locations for Finished Goods Warehouse (WH-FG)
        // Create Row A, B, C with 4 slots each = 12 slots
        $fgLocations = [];
        $rows = ['A', 'B', 'C'];
        foreach ($rows as $row) {
            for ($col = 1; $col <= 4; $col++) {
                $code = "FG-{$row}{$col}";
                $name = "FG Rack {$row}{$col}";
                $fgLocations[] = Location::create([
                    'warehouse_id' => $whFg->id,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'rack',
                    'level' => 1,
                    'is_active' => true,
                    'capacity' => 10,
                    'pos_x' => $col - 1,
                    'pos_y' => ord($row) - ord('A'),
                    'width' => 1,
                    'height' => 1,
                    'color' => '#6366f1' // indigo
                ]);
            }
        }

        // 4. Seed locations for Raw Material Warehouse (WH-RM)
        $rmLocations = [];
        if ($whRm) {
            foreach ($rows as $row) {
                for ($col = 1; $col <= 4; $col++) {
                    $code = "RM-{$row}{$col}";
                    $name = "RM Rack {$row}{$col}";
                    $rmLocations[] = Location::create([
                        'warehouse_id' => $whRm->id,
                        'code' => $code,
                        'name' => $name,
                        'type' => 'rack',
                        'level' => 1,
                        'is_active' => true,
                        'capacity' => 10,
                        'pos_x' => $col - 1,
                        'pos_y' => ord($row) - ord('A'),
                        'width' => 1,
                        'height' => 1,
                        'color' => '#06b6d4' // cyan
                    ]);
                }
            }
        }

        // 5. Fetch some Products for lots (fallback to first product if not found)
        $coilRm1 = Product::where('sku', 'COIL-HR-SPHC-2.0')->first() ?? Product::first();
        $coilRm2 = Product::where('sku', 'COIL-CR-SPCC-1.2')->first() ?? Product::first();
        
        $coilWip1 = Product::where('sku', 'SLIT-CR-SPCC-1.2-300')->first() ?? Product::first();
        $coilWip2 = Product::where('sku', 'SLIT-HR-SPHC-2.0-150')->first() ?? Product::first();

        // 6. Seed Inventory Lots for Finished Goods Warehouse (WH-FG)
        // Active coils in dock (location_id => null)
        $dockLotsFg = [
            [
                'product_id' => $coilWip1->id,
                'coil_number' => 'C-USC-FG001',
                'heat_number' => 'H-82191',
                'thickness' => 1.20,
                'width' => 300.0,
                'length' => 1200.0,
                'weight' => 5400.0,
                'notes' => 'Coil Slitted SPCC 1.2 x 300'
            ],
            [
                'product_id' => $coilWip2->id,
                'coil_number' => 'C-USC-FG002',
                'heat_number' => 'H-82192',
                'thickness' => 2.00,
                'width' => 150.0,
                'length' => 1500.0,
                'weight' => 4800.0,
                'notes' => 'Coil Slitted SPHC 2.0 x 150'
            ],
            [
                'product_id' => $coilWip1->id,
                'coil_number' => 'C-USC-FG003',
                'heat_number' => 'H-82193',
                'thickness' => 1.20,
                'width' => 300.0,
                'length' => 1000.0,
                'weight' => 4500.0,
                'notes' => 'Coil Slitted SPCC 1.2 x 300'
            ]
        ];

        foreach ($dockLotsFg as $lotData) {
            InventoryLot::create(array_merge($lotData, [
                'warehouse_id' => $whFg->id,
                'location_id' => null, // in receiving/WIP dock
                'qty' => 1,
                'status' => 'available'
            ]));
        }

        // Coils already on racks
        $rackLotsFg = [
            [
                'product_id' => $coilWip1->id,
                'location_id' => $fgLocations[0]->id, // FG-A1
                'coil_number' => 'C-USC-FG101',
                'heat_number' => 'H-82181',
                'thickness' => 1.20,
                'width' => 300.0,
                'length' => 1200.0,
                'weight' => 5400.0,
                'notes' => 'Coil Slitted SPCC 1.2 x 300'
            ],
            [
                'product_id' => $coilWip2->id,
                'location_id' => $fgLocations[5]->id, // FG-B2
                'coil_number' => 'C-USC-FG102',
                'heat_number' => 'H-82182',
                'thickness' => 2.00,
                'width' => 150.0,
                'length' => 1500.0,
                'weight' => 4800.0,
                'notes' => 'Coil Slitted SPHC 2.0 x 150'
            ]
        ];

        foreach ($rackLotsFg as $lotData) {
            $lot = InventoryLot::create(array_merge($lotData, [
                'warehouse_id' => $whFg->id,
                'qty' => 1,
                'status' => 'available'
            ]));

            // Create stock records in product_stocks table for this location
            ProductStock::create([
                'product_id' => $lot->product_id,
                'warehouse_id' => $whFg->id,
                'location_id' => $lot->location_id,
                'qty_on_hand' => $lot->qty,
                'qty_reserved' => 0,
                'qty_incoming' => 0,
                'qty_outgoing' => 0,
                'avg_cost' => $lot->product->cost_price ?? 12000
            ]);
        }

        // 7. Seed Inventory Lots for Raw Material Warehouse (WH-RM)
        if ($whRm) {
            $dockLotsRm = [
                [
                    'product_id' => $coilRm1->id,
                    'coil_number' => 'C-USC-RM001',
                    'heat_number' => 'H-90181',
                    'thickness' => 2.00,
                    'width' => 1219.0,
                    'length' => 2000.0,
                    'weight' => 22000.0,
                    'notes' => 'Hot Rolled Coil SPHC 2.0 x 1219'
                ],
                [
                    'product_id' => $coilRm2->id,
                    'coil_number' => 'C-USC-RM002',
                    'heat_number' => 'H-90182',
                    'thickness' => 1.20,
                    'width' => 1219.0,
                    'length' => 2000.0,
                    'weight' => 18000.0,
                    'notes' => 'Cold Rolled Coil SPCC-SD 1.2 x 1219'
                ]
            ];

            foreach ($dockLotsRm as $lotData) {
                InventoryLot::create(array_merge($lotData, [
                    'warehouse_id' => $whRm->id,
                    'location_id' => null, // in receiving/WIP dock
                    'qty' => 1,
                    'status' => 'available'
                ]));
            }

            $rackLotsRm = [
                [
                    'product_id' => $coilRm1->id,
                    'location_id' => $rmLocations[2]->id, // RM-A3
                    'coil_number' => 'C-USC-RM101',
                    'heat_number' => 'H-90171',
                    'thickness' => 2.00,
                    'width' => 1219.0,
                    'length' => 2000.0,
                    'weight' => 22000.0,
                    'notes' => 'Hot Rolled Coil SPHC 2.0 x 1219'
                ]
            ];

            foreach ($rackLotsRm as $lotData) {
                $lot = InventoryLot::create(array_merge($lotData, [
                    'warehouse_id' => $whRm->id,
                    'qty' => 1,
                    'status' => 'available'
                ]));

                // Create stock records in product_stocks table
                ProductStock::create([
                    'product_id' => $lot->product_id,
                    'warehouse_id' => $whRm->id,
                    'location_id' => $lot->location_id,
                    'qty_on_hand' => $lot->qty,
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => $lot->product->cost_price ?? 11500
                ]);
            }
        }

        $this->command->info('PT United Steel Center storage locations and inventory lots seeded successfully!');
    }
}
