<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $user = User::first();
        $customers = Customer::all();
        $warehouse = Warehouse::first();

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Please run SalesSeeder.');
            return;
        }

        // 1. Cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DeliveryOrder::truncate();
        Vehicle::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Create Vehicles
        $vehicles = [
            ['license_plate' => 'B 9102 SPA', 'vehicle_type' => 'Flatbed Truck', 'brand' => 'Hino', 'status' => 'on road', 'driver_name' => 'Budi Santoso'],
            ['license_plate' => 'B 8234 SPB', 'vehicle_type' => 'Crane Truck', 'brand' => 'Mitsubishi', 'status' => 'available', 'driver_name' => 'Agus Wijaya'],
            ['license_plate' => 'B 1122 SPC', 'vehicle_type' => 'Flatbed Truck', 'brand' => 'Isuzu', 'status' => 'on road', 'driver_name' => 'Dedi Kurniawan'],
            ['license_plate' => 'B 4455 SPD', 'vehicle_type' => 'Wingbox', 'brand' => 'Hino', 'status' => 'available', 'driver_name' => 'Eko Prasetyo'],
            ['license_plate' => 'B 6677 SPE', 'vehicle_type' => 'Small Pick-up', 'brand' => 'Suzuki', 'status' => 'maintenance', 'driver_name' => 'Fajar Shidiq'],
            ['license_plate' => 'B 8899 SPF', 'vehicle_type' => 'Flatbed Truck', 'brand' => 'Hino', 'status' => 'available', 'driver_name' => 'Guntur Tri'],
        ];

        foreach ($vehicles as $v) {
            Vehicle::create(array_merge($v, [
                'company_id' => $company->id ?? 1,
                'is_active' => true,
                'capacity_weight' => 15000,
            ]));
        }
        $vehicleModels = Vehicle::all();

        // 3. Create Delivery Orders (Last 14 Days)
        $statuses = ['draft', 'picking', 'packed', 'shipped', 'delivered'];
        $salesOrders = SalesOrder::all();
        
        if ($salesOrders->isEmpty()) {
            $this->command->error('No sales orders found. Please run SalesSeeder.');
            return;
        }

        for ($i = 0; $i < 40; $i++) {
            $date = Carbon::now()->subDays(rand(0, 14));
            $so = $salesOrders->random();
            $status = $statuses[array_rand($statuses)];
            $vehicle = $vehicleModels->random();

            $do = DeliveryOrder::create([
                'company_id' => $company->id ?? 1,
                'do_number' => 'DO-' . $date->format('ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'sales_order_id' => $so->id,
                'customer_id' => $so->customer_id,
                'vehicle_id' => $vehicle->id,
                'vehicle_number' => $vehicle->license_plate,
                'driver_name' => $vehicle->driver_name,
                'warehouse_id' => $so->warehouse_id ?? ($warehouse->id ?? 1),
                'delivery_date' => $date,
                'status' => $status,
                'shipping_name' => $so->customer->name ?? 'Customer Name',
                'shipping_address' => $so->shipping_address ?? 'Factory Yard Area 1',
                'prepared_by' => $user->id,
                'delivered_at' => $status === 'delivered' ? $date->copy()->addHours(rand(2, 8)) : null,
            ]);

            // Add items to the DO
            $itemsToDeliver = $so->items->take(rand(1, $so->items->count()));
            foreach ($itemsToDeliver as $soItem) {
                // Calculate remaining quantity that can be delivered
                $alreadyDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $soItem->id)
                    ->whereHas('deliveryOrder', function ($q) {
                        $q->where('status', '!=', 'cancelled');
                    })
                    ->sum('qty_delivered');

                $remaining = $soItem->qty - $alreadyDelivered;

                // Skip if this item has been fully delivered
                if ($remaining <= 0.001) {
                    continue;
                }

                $qtyToDeliver = $remaining;
                if ($status !== 'delivered' && $status !== 'completed') {
                    $qtyToDeliver = rand(1, max(1, (int)$remaining));
                    if ($qtyToDeliver > $remaining) {
                        $qtyToDeliver = $remaining;
                    }
                }

                \App\Models\DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $soItem->product_id,
                    'qty_ordered' => $soItem->qty,
                    'qty_delivered' => $qtyToDeliver,
                    'unit_id' => $soItem->unit_id,
                ]);
            }
        }

        $this->command->info('Logistics Intelligence data seeded successfully.');
    }
}
