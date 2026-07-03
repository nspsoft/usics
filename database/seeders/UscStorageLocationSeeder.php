<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Location;
use App\Models\WarehouseArea;
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
        WarehouseArea::query()->delete();
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

        // 5. Seed Warehouse Areas (Storage Locations / SLoc)
        $slocs = [
            [
                'warehouse_id' => $whFg->id,
                'code' => 'FG-BAY-A',
                'name' => 'Finished Goods Bay A',
                'name_key' => 'finished goods bay a',
                'is_active' => true
            ],
            [
                'warehouse_id' => $whFg->id,
                'code' => 'FG-BAY-B',
                'name' => 'Finished Goods Bay B',
                'name_key' => 'finished goods bay b',
                'is_active' => true
            ],
            [
                'warehouse_id' => $whFg->id,
                'code' => 'FG-BAY-C',
                'name' => 'Finished Goods Bay C',
                'name_key' => 'finished goods bay c',
                'is_active' => true
            ],
            [
                'warehouse_id' => $whFg->id,
                'code' => 'FG-BAY-D',
                'name' => 'Finished Goods Bay D',
                'name_key' => 'finished goods bay d',
                'is_active' => true
            ],
        ];

        if ($whRm) {
            $slocs[] = [
                'warehouse_id' => $whRm->id,
                'code' => 'RM-BAY-A',
                'name' => 'Raw Material Bay A',
                'name_key' => 'raw material bay a',
                'is_active' => true
            ];
            $slocs[] = [
                'warehouse_id' => $whRm->id,
                'code' => 'RM-BAY-B',
                'name' => 'Raw Material Bay B',
                'name_key' => 'raw material bay b',
                'is_active' => true
            ];
            $slocs[] = [
                'warehouse_id' => $whRm->id,
                'code' => 'RM-BAY-C',
                'name' => 'Raw Material Bay C',
                'name_key' => 'raw material bay c',
                'is_active' => true
            ];
        }

        if ($whMain) {
            $slocs[] = [
                'warehouse_id' => $whMain->id,
                'code' => 'MAIN-STK',
                'name' => 'Main Storage Area',
                'name_key' => 'main storage area',
                'is_active' => true
            ];
            $slocs[] = [
                'warehouse_id' => $whMain->id,
                'code' => 'MAIN-SHF',
                'name' => 'Main Shelf Area',
                'name_key' => 'main shelf area',
                'is_active' => true
            ];
        }

        foreach ($slocs as $slocData) {
            WarehouseArea::create($slocData);
        }

        // 6. Fetch some Products for lots (fallback to first product if not found)
        $coilRm1 = Product::where('sku', 'COIL-HR-SPHC-2.0')->first() ?? Product::first();
        $coilRm2 = Product::where('sku', 'COIL-CR-SPCC-1.2')->first() ?? Product::first();
        
        $coilWip1 = Product::where('sku', 'SLIT-CR-SPCC-1.2-300')->first() ?? Product::first();
        $coilWip2 = Product::where('sku', 'SLIT-HR-SPHC-2.0-150')->first() ?? Product::first();
        $coilWip3 = Product::where('sku', 'SLIT-CR-SPCC-1.2-120')->first() ?? Product::first();

        // 7. Seed Inventory Lots for Finished Goods Warehouse (WH-FG)
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

        // 8. Seed Inventory Lots for Raw Material Warehouse (WH-RM)
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

        // 9. Seed Product Reclass Mappings
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

        // 10. Seed Stock Transfers (15 records, varied statuses, products, dates)
        $whProd = Warehouse::where('code', 'WH-PROD')->first();

        $coilRm3 = Product::where('sku', 'COIL-GA-SGCC-0.8')->first() ?? Product::first();
        $coilRm4 = Product::where('sku', 'COIL-HR-SPHC-3.0')->first() ?? Product::first();
        $coilRm5 = Product::where('sku', 'COIL-HR-SPHC-4.0')->first() ?? Product::first();
        $coilRm6 = Product::where('sku', 'COIL-CR-SPCC-0.8')->first() ?? Product::first();
        $coilRm7 = Product::where('sku', 'COIL-CR-SPCC-1.0')->first() ?? Product::first();
        $slitWip3 = Product::where('sku', 'SLIT-HR-SPHC-2.0-200')->first() ?? Product::first();
        $slitWip4 = Product::where('sku', 'SLIT-GA-SGCC-0.8-250')->first() ?? Product::first();

        $prefix = 'TRF-' . Carbon::now()->format('ym') . '-';
        $seqNo = 1;

        $transferData = [
            // --- DRAFT transfers ---
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now(),
                'status' => 'draft',
                'notes' => 'Permintaan pemindahan coil HRC 2.0 ke Gudang FG untuk order Toyota.',
                'items' => [
                    ['product' => $coilRm1, 'requested' => 44000, 'sent' => 0, 'received' => 0],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now(),
                'status' => 'draft',
                'notes' => 'Permintaan material CRC 1.2 untuk proses slitting minggu ini.',
                'items' => [
                    ['product' => $coilRm2, 'requested' => 18000, 'sent' => 0, 'received' => 0],
                    ['product' => $coilRm6, 'requested' => 12000, 'sent' => 0, 'received' => 0],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now()->subDay(),
                'status' => 'draft',
                'notes' => 'Draft transfer GA coil 0.8mm ke FG Bay untuk order Daihatsu.',
                'items' => [
                    ['product' => $coilRm3, 'requested' => 15000, 'sent' => 0, 'received' => 0],
                ]
            ],

            // --- IN-TRANSIT transfers ---
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now()->subDay(),
                'status' => 'in_transit',
                'notes' => 'Pengiriman CRC 1.2 ke Gudang FG untuk persiapan order Toyota.',
                'shipped_at' => Carbon::now()->subDay(),
                'items' => [
                    ['product' => $coilRm2, 'requested' => 18000, 'sent' => 18000, 'received' => 0],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now()->subDays(2),
                'status' => 'in_transit',
                'notes' => 'Pengiriman HRC 3.0 ke area produksi untuk proses shearing.',
                'shipped_at' => Carbon::now()->subDays(2),
                'items' => [
                    ['product' => $coilRm4, 'requested' => 25000, 'sent' => 25000, 'received' => 0],
                ]
            ],
            [
                'src' => $whFg, 'dst' => $whMain,
                'date' => Carbon::now()->subDays(1),
                'status' => 'in_transit',
                'notes' => 'Relokasi strip SPCC 1.2x300 ke gudang utama untuk dispatching.',
                'shipped_at' => Carbon::now()->subDays(1),
                'items' => [
                    ['product' => $coilWip1, 'requested' => 8000, 'sent' => 8000, 'received' => 0],
                    ['product' => $coilWip2, 'requested' => 5500, 'sent' => 5500, 'received' => 0],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now()->subDays(3),
                'status' => 'in_transit',
                'notes' => 'Pengiriman HRC 4.0 untuk proses blanking disc brake Suzuki.',
                'shipped_at' => Carbon::now()->subDays(3),
                'items' => [
                    ['product' => $coilRm5, 'requested' => 30000, 'sent' => 30000, 'received' => 0],
                ]
            ],

            // --- RECEIVED transfers ---
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now()->subDays(5),
                'status' => 'received',
                'notes' => 'Pemindahan HRC 2.0 selesai diterima oleh tim Gudang FG.',
                'shipped_at' => Carbon::now()->subDays(5),
                'received_at' => Carbon::now()->subDays(4),
                'items' => [
                    ['product' => $coilRm1, 'requested' => 22000, 'sent' => 22000, 'received' => 22000],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now()->subDays(7),
                'status' => 'received',
                'notes' => 'Transfer CRC 0.8 ke area produksi untuk blanking Hood Inner (Daihatsu).',
                'shipped_at' => Carbon::now()->subDays(7),
                'received_at' => Carbon::now()->subDays(6),
                'items' => [
                    ['product' => $coilRm6, 'requested' => 10000, 'sent' => 10000, 'received' => 10000],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now()->subDays(10),
                'status' => 'received',
                'notes' => 'Transfer GA coil 0.8mm sudah diterima dan disimpan di FG Bay C.',
                'shipped_at' => Carbon::now()->subDays(10),
                'received_at' => Carbon::now()->subDays(9),
                'items' => [
                    ['product' => $coilRm3, 'requested' => 14000, 'sent' => 14000, 'received' => 14000],
                ]
            ],
            [
                'src' => $whProd, 'dst' => $whFg,
                'date' => Carbon::now()->subDays(8),
                'status' => 'received',
                'notes' => 'Hasil slitting strip SPHC 2.0x200 dipindahkan dari produksi ke FG.',
                'shipped_at' => Carbon::now()->subDays(8),
                'received_at' => Carbon::now()->subDays(7),
                'items' => [
                    ['product' => $slitWip3, 'requested' => 20000, 'sent' => 20000, 'received' => 20000],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now()->subDays(14),
                'status' => 'received',
                'notes' => 'Pengiriman CRC 1.0 ke area produksi untuk TWB Line.',
                'shipped_at' => Carbon::now()->subDays(14),
                'received_at' => Carbon::now()->subDays(13),
                'items' => [
                    ['product' => $coilRm7, 'requested' => 16000, 'sent' => 16000, 'received' => 16000],
                    ['product' => $coilRm2, 'requested' => 12000, 'sent' => 12000, 'received' => 12000],
                ]
            ],

            // --- CANCELLED transfers ---
            [
                'src' => $whRm, 'dst' => $whFg,
                'date' => Carbon::now()->subDays(12),
                'status' => 'cancelled',
                'notes' => 'Dibatalkan: Order customer batal, material tidak jadi dipindahkan.',
                'items' => [
                    ['product' => $coilRm5, 'requested' => 35000, 'sent' => 0, 'received' => 0],
                ]
            ],
            [
                'src' => $whFg, 'dst' => $whMain,
                'date' => Carbon::now()->subDays(20),
                'status' => 'cancelled',
                'notes' => 'Dibatalkan: Gudang utama penuh, relokasi strip GA ditunda.',
                'items' => [
                    ['product' => $slitWip4, 'requested' => 9000, 'sent' => 0, 'received' => 0],
                ]
            ],
            [
                'src' => $whRm, 'dst' => $whProd,
                'date' => Carbon::now()->subDays(15),
                'status' => 'cancelled',
                'notes' => 'Dibatalkan: Jadwal produksi berubah, HRC 3.0 tidak diperlukan.',
                'items' => [
                    ['product' => $coilRm4, 'requested' => 20000, 'sent' => 0, 'received' => 0],
                ]
            ],
        ];

        foreach ($transferData as $td) {
            if (!$td['src'] || !$td['dst']) continue;

            $stData = [
                'transfer_number' => $prefix . str_pad($seqNo++, 4, '0', STR_PAD_LEFT),
                'source_warehouse_id' => $td['src']->id,
                'destination_warehouse_id' => $td['dst']->id,
                'transfer_date' => $td['date']->toDateString(),
                'status' => $td['status'],
                'notes' => $td['notes'],
                'created_by' => $adminUserId
            ];
            if (isset($td['shipped_at'])) $stData['shipped_at'] = $td['shipped_at']->toDateTimeString();
            if (isset($td['received_at'])) {
                $stData['received_at'] = $td['received_at']->toDateTimeString();
                $stData['received_by'] = $adminUserId;
            }

            $st = StockTransfer::create($stData);

            foreach ($td['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $st->id,
                    'product_id' => $item['product']->id,
                    'qty_requested' => $item['requested'],
                    'qty_sent' => $item['sent'],
                    'qty_received' => $item['received'],
                ]);
            }
        }

        // 11. Seed Stock Reclassifications
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

        $this->command->info('PT United Steel Center storage locations, SLocs, inventory lots, transfers, and reclassifications seeded successfully!');
    }
}
