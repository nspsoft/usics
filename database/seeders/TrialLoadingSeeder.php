<?php

namespace Database\Seeders;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Unit;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrialLoadingSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1; // Default company id

        // 1. Ensure basic master data exists using firstOrCreate
        $unit = Unit::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'PCS'],
            ['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]
        );

        $category = Category::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'FG'],
            ['name' => 'Finished Goods', 'type' => 'product', 'is_active' => true]
        );

        $customer = Customer::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'CUST-TRIAL'],
            [
                'name' => 'PT Baja Unggul Indonesia (TRIAL)',
                'address' => 'Kawasan Industri Jababeka Blok C-12, Cikarang',
                'email' => 'trial@bajaunggul.co.id',
                'phone' => '021-8901234',
                'payment_terms' => 'NET30',
                'currency' => 'IDR',
                'is_active' => true
            ]
        );

        $product = Product::firstOrCreate(
            ['company_id' => $companyId, 'sku' => 'SC-HRC-TRIAL'],
            [
                'name' => 'Slit Coil HRC 1.2mm (TRIAL)',
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'type' => 'product',
                'product_type' => 'finished_good',
                'cost_price' => 12000,
                'selling_price' => 15000,
                'is_active' => true
            ]
        );

        // Fetch or create default warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'WH-TRIAL'],
            [
                'name' => 'Gudang Utama Finish Goods (TRIAL)',
                'address' => 'Pabrik Sentral Blok A',
                'type' => 'warehouse',
                'is_active' => true
            ]
        );

        // Create 6 locations for Crane Map
        $locCodes = ['SLOT-A1', 'SLOT-A2', 'SLOT-B1', 'SLOT-B2', 'SLOT-C1', 'SLOT-C2'];
        $locNames = ['Slot A1 (Slitting)', 'Slot A2 (Slitting)', 'Slot B1 (Coil)', 'Slot B2 (Coil)', 'Slot C1 (Pipe)', 'Slot C2 (Pipe)'];
        $locations = [];
        foreach ($locCodes as $idx => $code) {
            $locations[] = \App\Models\Location::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => $code],
                [
                    'name' => $locNames[$idx],
                    'type' => 'rack',
                    'level' => 1,
                    'is_active' => true,
                    'capacity' => 10,
                ]
            );
        }

        // Clean previous trial lots to prevent duplicate key error
        \App\Models\InventoryLot::where('coil_number', 'like', 'C-TRIAL-%')->delete();

        // Create 3 inventory lots
        \App\Models\InventoryLot::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'location_id' => null, // initially in WIP dock
            'coil_number' => 'C-TRIAL-001',
            'heat_number' => 'H-90181',
            'thickness' => 1.2000,
            'width' => 45.0000,
            'length' => 1200.0000,
            'weight' => 4500.0000,
            'qty' => 1,
            'status' => 'available',
            'notes' => 'Trial Coil HRC 1'
        ]);

        \App\Models\InventoryLot::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'location_id' => null, // initially in WIP dock
            'coil_number' => 'C-TRIAL-002',
            'heat_number' => 'H-90182',
            'thickness' => 1.2000,
            'width' => 60.0000,
            'length' => 1500.0000,
            'weight' => 6200.0000,
            'qty' => 1,
            'status' => 'available',
            'notes' => 'Trial Coil HRC 2'
        ]);

        \App\Models\InventoryLot::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'location_id' => $locations[2]->id, // initially placed in Slot B1
            'coil_number' => 'C-TRIAL-003',
            'heat_number' => 'H-90183',
            'thickness' => 1.2000,
            'width' => 90.0000,
            'length' => 1800.0000,
            'weight' => 8100.0000,
            'qty' => 1,
            'status' => 'available',
            'notes' => 'Trial Coil HRC 3'
        ]);

        // Get or create test vehicles
        $vehicles = Vehicle::limit(3)->get();
        if ($vehicles->count() < 3) {
            $dummyPlates = ['B 9182 ABC', 'L 4432 JO', 'B 7781 XY'];
            $dummyDrivers = ['Slamet Supir', 'Joko Driver', 'Mulyono Fuso'];
            for ($i = $vehicles->count(); $i < 3; $i++) {
                Vehicle::create([
                    'company_id' => $companyId,
                    'license_plate' => $dummyPlates[$i],
                    'vehicle_type' => 'Truk Fuso',
                    'brand' => 'Mitsubishi',
                    'capacity_weight' => 8000,
                    'capacity_volume' => 30,
                    'driver_name' => $dummyDrivers[$i],
                    'status' => 'available',
                    'is_active' => true,
                    'usage_type' => 'both'
                ]);
            }
            $vehicles = Vehicle::limit(3)->get();
        }

        $today = Carbon::today();
        $prefix = "DO-TRIAL-";

        // Clean previous trial data to prevent key conflicts
        $oldTrialDOs = DeliveryOrder::where('do_number', 'like', "{$prefix}%")->get();
        foreach ($oldTrialDOs as $oldDo) {
            $oldDo->items()->delete();
            $oldDo->forceDelete();
        }

        // Create Draft DO (Upcoming Queue 1)
        $do1 = DeliveryOrder::create([
            'company_id' => $companyId,
            'do_number' => $prefix . '001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => $today,
            'status' => 'draft',
            'vehicle_id' => $vehicles[0]->id,
            'vehicle_number' => $vehicles[0]->license_plate,
            'driver_name' => $vehicles[0]->driver_name,
            'shipping_name' => $customer->name,
            'shipping_address' => $customer->address ?? 'Alamat Kirim',
            'notes' => 'Trial Antrian Logistik 1'
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $do1->id,
            'product_id' => $product->id,
            'qty_ordered' => 10,
            'qty_delivered' => 0,
            'is_loaded' => false,
            'unit_id' => $unit->id
        ]);

        // Create Draft DO (Upcoming Queue 2)
        $do2 = DeliveryOrder::create([
            'company_id' => $companyId,
            'do_number' => $prefix . '002',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => $today,
            'status' => 'draft',
            'vehicle_id' => $vehicles[1]->id,
            'vehicle_number' => $vehicles[1]->license_plate,
            'driver_name' => $vehicles[1]->driver_name,
            'shipping_name' => $customer->name,
            'shipping_address' => $customer->address ?? 'Alamat Kirim',
            'notes' => 'Trial Antrian Logistik 2'
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $do2->id,
            'product_id' => $product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 0,
            'is_loaded' => false,
            'unit_id' => $unit->id
        ]);

        // Create Picking DO (Active Call / Loading in Progress)
        $do3 = DeliveryOrder::create([
            'company_id' => $companyId,
            'do_number' => $prefix . '003',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => $today,
            'status' => 'picking',
            'vehicle_id' => $vehicles[2]->id,
            'vehicle_number' => $vehicles[2]->license_plate,
            'driver_name' => $vehicles[2]->driver_name,
            'shipping_name' => $customer->name,
            'shipping_address' => $customer->address ?? 'Alamat Kirim',
            'loading_bay' => 'Bay 2 Slitting',
            'called_at' => Carbon::now()->subMinutes(10),
            'notes' => 'Trial Sedang Loading'
        ]);
        
        DeliveryOrderItem::create([
            'delivery_order_id' => $do3->id,
            'product_id' => $product->id,
            'qty_ordered' => 6,
            'qty_delivered' => 6,
            'is_loaded' => true,
            'unit_id' => $unit->id
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $do3->id,
            'product_id' => $product->id,
            'qty_ordered' => 4,
            'qty_delivered' => 0,
            'is_loaded' => false,
            'unit_id' => $unit->id
        ]);

        // Create Packed DO (Recently Completed Loading)
        $do4 = DeliveryOrder::create([
            'company_id' => $companyId,
            'do_number' => $prefix . '004',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'delivery_date' => $today,
            'status' => 'packed',
            'vehicle_number' => 'B 9931 FQ',
            'driver_name' => 'Anto Prasetyo',
            'shipping_name' => $customer->name,
            'shipping_address' => $customer->address ?? 'Alamat Kirim',
            'loading_bay' => 'Bay 1 Slitting',
            'called_at' => Carbon::now()->subMinutes(45),
            'updated_at' => Carbon::now()->subMinutes(15),
            'notes' => 'Trial Selesai Loading'
        ]);
        DeliveryOrderItem::create([
            'delivery_order_id' => $do4->id,
            'product_id' => $product->id,
            'qty_ordered' => 12,
            'qty_delivered' => 12,
            'is_loaded' => true,
            'unit_id' => $unit->id
        ]);
    }
}
