<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Product;
use App\Models\InventoryLot;
use App\Models\ProductStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockReclassification;
use App\Models\StockReclassificationItem;
use App\Models\Inventory\ProductReclassMapping;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Get dynamic admin user ID
        $adminUser = User::where('email', 'admin@usc-indonesia.co.id')->first() ?? User::first();
        $adminUserId = $adminUser ? $adminUser->id : 1;

        // 2. Clean existing locations, lots, transfers, and reclass records to avoid constraints errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        InventoryLot::query()->delete();
        Location::query()->delete();
        ProductReclassMapping::query()->delete();
        StockTransferItem::query()->delete();
        StockTransfer::query()->delete();
        StockReclassificationItem::query()->delete();
        StockReclassification::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
        $coilWip3 = Product::where('sku', 'SLIT-CR-SPCC-1.2-120')->first() ?? Product::first();

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

        // 8. Seed Product Reclass Mappings
        ProductReclassMapping::create([
            'source_product_id' => $coilRm1->id,
            'target_product_id' => $coilWip2->id,
            'is_active' => true,
            'is_default' => true,
            'notes' => 'Mapping otomatis canai panas HRC ke Slitted Strip SPHC 2.0x150.',
            'created_by' => $adminUserId
        ]);

        ProductReclassMapping::create([
            'source_product_id' => $coilRm2->id,
            'target_product_id' => $coilWip1->id,
            'is_active' => true,
            'is_default' => true,
            'notes' => 'Mapping otomatis canai dingin CRC ke Slitted Strip SPCC 1.2x300.',
            'created_by' => $adminUserId
        ]);

        // 9. Seed Stock Transfers
        if ($whRm) {
            // A. Draft Stock Transfer
            $st1 = StockTransfer::create([
                'transfer_number' => 'TRF-' . Carbon::now()->format('ym') . '-0001',
                'source_warehouse_id' => $whRm->id,
                'destination_warehouse_id' => $whFg->id,
                'transfer_date' => Carbon::now()->toDateString(),
                'status' => 'draft',
                'notes' => 'Permintaan pemindahan material coil Hot Rolled ke Gudang Finish Goods.',
                'created_by' => $adminUserId
            ]);
            StockTransferItem::create([
                'stock_transfer_id' => $st1->id,
                'product_id' => $coilRm1->id,
                'qty_requested' => 44000.00,
                'qty_sent' => 0.00,
                'qty_received' => 0.00
            ]);

            // B. Shipped / In-Transit Stock Transfer
            $st2 = StockTransfer::create([
                'transfer_number' => 'TRF-' . Carbon::now()->format('ym') . '-0002',
                'source_warehouse_id' => $whRm->id,
                'destination_warehouse_id' => $whFg->id,
                'transfer_date' => Carbon::now()->subDay()->toDateString(),
                'status' => 'in_transit',
                'notes' => 'Pengiriman coil canai dingin ke Gudang FG untuk persiapan order Toyota.',
                'created_by' => $adminUserId,
                'shipped_at' => Carbon::now()->subDay()->toDateTimeString()
            ]);
            StockTransferItem::create([
                'stock_transfer_id' => $st2->id,
                'product_id' => $coilRm2->id,
                'qty_requested' => 18000.00,
                'qty_sent' => 18000.00,
                'qty_received' => 0.00
            ]);

            // C. Received Stock Transfer
            $st3 = StockTransfer::create([
                'transfer_number' => 'TRF-' . Carbon::now()->format('ym') . '-0003',
                'source_warehouse_id' => $whRm->id,
                'destination_warehouse_id' => $whFg->id,
                'transfer_date' => Carbon::now()->subDays(5)->toDateString(),
                'status' => 'received',
                'notes' => 'Pemindahan coil canai panas selesai diterima oleh tim Gudang FG.',
                'created_by' => $adminUserId,
                'shipped_at' => Carbon::now()->subDays(5)->toDateTimeString(),
                'received_at' => Carbon::now()->subDays(4)->toDateTimeString(),
                'received_by' => $adminUserId
            ]);
            StockTransferItem::create([
                'stock_transfer_id' => $st3->id,
                'product_id' => $coilRm1->id,
                'qty_requested' => 22000.00,
                'qty_sent' => 22000.00,
                'qty_received' => 22000.00
            ]);
        }

        // 10. Seed Stock Reclassifications
        // A. Draft Stock Reclassification
        $sr1 = StockReclassification::create([
            'reclass_number' => 'REC-' . Carbon::now()->format('ym') . '-0001',
            'warehouse_id' => $whFg->id,
            'reclass_date' => Carbon::now()->toDateString(),
            'status' => 'draft',
            'reason' => 'Penyesuaian grade slitting lebar 300 ke 120',
            'notes' => 'Draft reclassification untuk kebutuhan pengerjaan bracket Honda.',
            'total_qty' => 5000.00,
            'total_value' => 69000000.00,
            'created_by' => $adminUserId
        ]);
        StockReclassificationItem::create([
            'stock_reclassification_id' => $sr1->id,
            'source_product_id' => $coilWip1->id,
            'target_product_id' => $coilWip3->id,
            'unit_id' => $coilWip1->unit_id,
            'qty' => 5000.00,
            'cost_per_unit' => $coilWip1->cost_price ?? 13800.00,
            'total_cost' => 69000000.00,
            'notes' => 'Reclass untuk material pendukung'
        ]);

        // B. Posted Stock Reclassification
        $sr2 = StockReclassification::create([
            'reclass_number' => 'REC-' . Carbon::now()->format('ym') . '-0002',
            'warehouse_id' => $whFg->id,
            'reclass_date' => Carbon::now()->subDays(2)->toDateString(),
            'status' => 'posted',
            'reason' => 'Realisasi Hasil Slitting HRC ke Strip SPHC 150',
            'notes' => 'Reclass selesai dilakukan setelah pengerjaan di Slitter SA.',
            'total_qty' => 22000.00,
            'total_value' => 253000000.00,
            'created_by' => $adminUserId,
            'posted_at' => Carbon::now()->subDays(2)->toDateTimeString(),
            'posted_by' => $adminUserId
        ]);
        StockReclassificationItem::create([
            'stock_reclassification_id' => $sr2->id,
            'source_product_id' => $coilRm1->id,
            'target_product_id' => $coilWip2->id,
            'unit_id' => $coilRm1->unit_id,
            'qty' => 22000.00,
            'cost_per_unit' => $coilRm1->cost_price ?? 11500.00,
            'total_cost' => 253000000.00,
            'notes' => 'Reclass coil utuh ke slitted strip'
        ]);

        $this->command->info('PT United Steel Center storage locations, inventory lots, transfers, and reclassifications seeded successfully!');
    }
}
