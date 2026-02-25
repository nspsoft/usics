<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\WorkOrder;
use App\Models\StockMovement;
use App\Models\Category;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardSeeder extends Seeder
{
    public function run()
    {
        $company = Company::first();
        if (!$company) {
            $this->command->error('Run DemoDataSeeder first!');
            return;
        }

        $user = User::first();
        $mainWarehouse = Warehouse::where('code', 'WH-MAIN')->first();

        // 1. Create Customers & Suppliers
        $this->command->info('Creating Customers & Suppliers...');
        
        $customers = [];
        $customerNames = ['PT Konstruksi Jaya', 'CV Maju Mundur', 'PT Sejahtera Abadi', 'Bengkel Las Barokah', 'Mitra Teknik Sentosa'];
        $i = 1;
        foreach ($customerNames as $name) {
            $customers[] = Customer::create([
                'company_id' => $company->id,
                'code' => 'CUST-' . str_pad($i++, 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'phone' => '081' . rand(10000000, 99999999),
                'address' => 'Jl. Customer Address No. ' . rand(1, 100),
                'is_active' => true,
            ]);
        }

        $suppliers = [];
        $supplierNames = ['PT Baja Utama', 'CV Supplier Besi', 'Global Steel Corp', 'Indo Tools Supply', 'Sentra Material Teknik'];
        $j = 1;
        foreach ($supplierNames as $name) {
            $suppliers[] = Supplier::create([
                'company_id' => $company->id,
                'code' => 'SUPP-' . str_pad($j++, 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@provider.com',
                'phone' => '021' . rand(1000000, 9999999),
                'address' => 'Jl. Supplier Address No. ' . rand(1, 100),
                'is_active' => true,
            ]);
        }

        // 2. Adjust Product Stocks for "Low Stock" & "Current Stock" charts
        $this->command->info('Adjusting Product Stocks...');
        
        // Low Stock Scenario
        $lowStockProduct = Product::where('sku', 'RM-STEEL-001')->first();
        if ($lowStockProduct) {
             $stock = ProductStock::where('product_id', $lowStockProduct->id)->first();
             if ($stock) {
                 $stock->update(['qty_on_hand' => 100]); // Min is 500
             }
        }

        // Add some WIP items if not enough
        $wipCategory = Category::where('code', 'WIP')->first();
        $pcsUnit = Unit::where('code', 'PCS')->first();
        
        if (Product::where('product_type', 'wip')->count() < 2) {
            Product::create([
                'company_id' => $company->id,
                'sku' => 'WIP-FRAME-A-001',
                'name' => 'Half-Welded Frame A',
                'category_id' => $wipCategory->id,
                'product_type' => 'wip',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 150000,
                'selling_price' => 0,
                'min_stock' => 0,
                'is_manufactured' => true,
                'is_active' => true
            ]);
        }

        // 3. Create Purchase Orders (Pie Chart Data)
        $this->command->info('Creating Purchase Orders...');
        $poStatuses = [
            'draft' => 2,
            'submitted' => 3, 
            'approved' => 3,
            'ordered' => 2,
            'received' => 5
        ];

        foreach ($poStatuses as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $supplier = $suppliers[array_rand($suppliers)];
                $poDate = Carbon::now()->subDays(rand(1, 60));
                
                $po = PurchaseOrder::create([
                    'company_id' => $company->id,
                    'po_number' => PurchaseOrder::generatePoNumber($supplier, $poDate),
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $mainWarehouse->id,
                    'order_date' => $poDate,
                    'expected_date' => $poDate->copy()->addDays(7),
                    'status' => $status,
                    'currency' => 'IDR',
                    'exchange_rate' => 1,
                    'subtotal' => 0,
                    'total' => 0,
                    'created_by' => $user->id,
                ]);

                // Add Items
                $products = Product::inRandomOrder()->take(rand(1, 3))->get();
                $subtotal = 0;
                foreach ($products as $product) {
                    $qty = rand(10, 100);
                    $price = $product->cost_price;
                    $lineTotal = $qty * $price;
                    
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $product->id,
                        'description' => $product->name,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'subtotal' => $lineTotal,
                        'unit_id' => $product->unit_id,
                    ]);
                    $subtotal += $lineTotal;
                }
                
                $po->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal, // Simplified tax
                ]);
            }
        }

        // 4. Create Sales Orders (Sales Trend Chart)
        $this->command->info('Creating Sales Orders...');
        
        $startDate = Carbon::now()->subDays(30);
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // Random variance: some days have high sales, some low
            $orderCount = rand(0, 3);
            if (rand(1, 10) > 8) $orderCount = rand(3, 6); // Spike

            for ($k = 0; $k < $orderCount; $k++) {
                $customer = $customers[array_rand($customers)];
                $so = SalesOrder::create([
                    'company_id' => $company->id,
                    'so_number' => SalesOrder::generateSoNumber(),
                    'customer_id' => $customer->id,
                    'warehouse_id' => $mainWarehouse->id,
                    'order_date' => $date,
                    'status' => 'delivered', // Assuming past sales
                    'currency' => 'IDR',
                    'exchange_rate' => 1,
                    'subtotal' => 0,
                    'total' => 0,
                    'created_by' => $user->id,
                ]);

                $products = Product::where('is_sold', true)->inRandomOrder()->take(rand(1, 5))->get();
                $subtotal = 0;
                foreach ($products as $product) {
                    $qty = rand(1, 20);
                    $price = $product->selling_price > 0 ? $product->selling_price : $product->cost_price * 1.5;
                    $lineTotal = $qty * $price;

                    SalesOrderItem::create([
                        'sales_order_id' => $so->id,
                        'product_id' => $product->id,
                        'description' => $product->name,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'discount_percent' => 0,
                        'subtotal' => $lineTotal,
                        'unit_id' => $product->unit_id,
                    ]);
                    $subtotal += $lineTotal;
                }

                $so->update([
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                ]);
            }
        }

        // 5. Create Work Orders (WIP)
        $this->command->info('Creating Work Orders...');
        $fgProduct = Product::where('product_type', 'finished_good')->first();
        if ($fgProduct) {
             // Create a dummy BOM first
             $bom = \App\Models\Bom::create([
                'company_id' => $company->id,
                'code' => 'BOM-' . $fgProduct->sku,
                'name' => 'BOM for ' . $fgProduct->name,
                'product_id' => $fgProduct->id,
                'qty' => 1,
                'unit_id' => $fgProduct->unit_id,
                'status' => 'active',
             ]);

            for ($i = 0; $i < 5; $i++) {
                 WorkOrder::create([
                    'company_id' => $company->id,
                    'wo_number' => WorkOrder::generateWoNumber(),
                    'bom_id' => $bom->id,
                    'product_id' => $fgProduct->id,
                    'warehouse_id' => $mainWarehouse->id,
                    'qty_planned' => rand(10, 50),
                    'qty_produced' => rand(0, 5),
                    'status' => 'in_progress',
                    'priority' => ['normal', 'high'][rand(0, 1)],
                    'planned_start' => Carbon::now()->subDays(rand(1, 5)),
                    'planned_end' => Carbon::now()->addDays(rand(1, 5)),
                    'created_by' => $user->id,
                ]);
            }
        }

        // 6. Create Stock Movements (Line Area Chart)
        $this->command->info('Creating Stock Movements...');
        $movementTypes = [
            'in' => ['po_receive', 'production_in'],
            'out' => ['so_delivery', 'production_out']
        ];
        
        $randomPoId = PurchaseOrder::inRandomOrder()->value('id');
        $randomSoId = SalesOrder::inRandomOrder()->value('id');

        for ($i = 6; $i >= 0; $i--) {
             $date = Carbon::now()->subDays($i);
             
             // Incoming
             $inCount = rand(1, 5);
             if ($i == 3) $inCount = 10; // Spike
             
             for ($j = 0; $j < $inCount; $j++) {
                 $product = Product::inRandomOrder()->first();
                 StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $mainWarehouse->id,
                    'qty' => rand(10, 100),
                    'type' => 'in',
                    'reference_type' => 'App\Models\PurchaseOrder',
                    'reference_id' => $randomPoId ?? 1,
                    'created_at' => $date->copy()->addHours(rand(8, 16)),
                 ]);
             }

             // Outgoing
             $outCount = rand(2, 6);
             for ($j = 0; $j < $outCount; $j++) {
                 $product = Product::inRandomOrder()->first();
                 StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $mainWarehouse->id,
                    'qty' => rand(5, 50),
                    'type' => 'out',
                    'reference_type' => 'App\Models\SalesOrder',
                    'reference_id' => $randomSoId ?? 1,
                    'created_at' => $date->copy()->addHours(rand(9, 17)),
                 ]);
             }
        }

        $this->command->info('Dashboard Data Seeded Successfully!');
    }
}
