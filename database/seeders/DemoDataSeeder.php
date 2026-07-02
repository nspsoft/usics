<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Company
        $company = Company::firstOrCreate(
            ['code' => 'MFG-001'],
            [
                'name' => 'PT Manufacturing Indonesia',
                'legal_name' => 'PT Manufacturing Indonesia Tbk',
                'address' => 'Jl. Industri Raya No. 123',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'postal_code' => '12345',
                'country' => 'ID',
                'phone' => '021-12345678',
                'email' => 'info@manufacturing.id',
                'tax_id' => '01.234.567.8-012.000',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]
        );

        // Create Admin User
        $admin = User::first();
        if ($admin) {
            $admin->update([
                'company_id' => $company->id,
                'name' => 'Administrator',
            ]);
        }

        // Create Units
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'pcs'],
            ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'kg'],
            ['code' => 'MTR', 'name' => 'Meter', 'symbol' => 'm'],
            ['code' => 'LTR', 'name' => 'Liter', 'symbol' => 'L'],
            ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'box'],
            ['code' => 'SET', 'name' => 'Set', 'symbol' => 'set'],
            ['code' => 'ROLL', 'name' => 'Roll', 'symbol' => 'roll'],
            ['code' => 'SHT', 'name' => 'Sheet', 'symbol' => 'sht'],
        ];

        foreach ($units as $unit) {
            Unit::create([
                'company_id' => $company->id,
                ...$unit,
                'is_active' => true,
            ]);
        }

        // Create Categories
        $categories = [
            ['code' => 'RM', 'name' => 'Raw Materials', 'type' => 'product'],
            ['code' => 'WIP', 'name' => 'Work in Progress', 'type' => 'product'],
            ['code' => 'FG', 'name' => 'Finished Goods', 'type' => 'product'],
            ['code' => 'SP', 'name' => 'Spare Parts', 'type' => 'product'],
            ['code' => 'PKG', 'name' => 'Packaging', 'type' => 'product'],
            ['code' => 'CONS', 'name' => 'Consumables', 'type' => 'product'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'company_id' => $company->id,
                ...$category,
                'is_active' => true,
            ]);
        }

        // Create Warehouses
        $warehouses = [
            [
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'address' => 'Jl. Industri Raya No. 123',
                'city' => 'Jakarta',
                'type' => 'warehouse',
                'is_default' => true,
            ],
            [
                'code' => 'WH-RM',
                'name' => 'Raw Material Warehouse',
                'address' => 'Jl. Industri Raya No. 123-A',
                'city' => 'Jakarta',
                'type' => 'warehouse',
                'is_default' => false,
            ],
            [
                'code' => 'WH-PROD',
                'name' => 'Production Area',
                'address' => 'Jl. Industri Raya No. 123',
                'city' => 'Jakarta',
                'type' => 'production',
                'is_default' => false,
            ],
            [
                'code' => 'WH-FG',
                'name' => 'Finished Goods Warehouse',
                'address' => 'Jl. Industri Raya No. 123-B',
                'city' => 'Jakarta',
                'type' => 'warehouse',
                'is_default' => false,
            ],
        ];

        $createdWarehouses = [];
        foreach ($warehouses as $warehouse) {
            $createdWarehouses[] = Warehouse::create([
                'company_id' => $company->id,
                ...$warehouse,
                'is_active' => true,
            ]);
        }

        // Get units and categories for products
        $pcsUnit = Unit::where('code', 'PCS')->first();
        $kgUnit = Unit::where('code', 'KG')->first();
        $mtrUnit = Unit::where('code', 'MTR')->first();
        
        $rmCategory = Category::where('code', 'RM')->first();
        $fgCategory = Category::where('code', 'FG')->first();
        $spCategory = Category::where('code', 'SP')->first();

        // Create Products
        $products = [
            // Raw Materials
            [
                'sku' => 'RM-STEEL-001',
                'name' => 'Steel Plate 2mm',
                'category_id' => $rmCategory->id,
                'product_type' => 'raw_material',
                'unit_id' => $kgUnit->id,
                'cost_price' => 15000,
                'selling_price' => 0,
                'min_stock' => 500,
                'reorder_point' => 1000,
                'reorder_qty' => 2000,
                'is_purchased' => true,
                'is_sold' => false,
                'is_manufactured' => false,
            ],
            [
                'sku' => 'RM-STEEL-002',
                'name' => 'Steel Plate 4mm',
                'category_id' => $rmCategory->id,
                'product_type' => 'raw_material',
                'unit_id' => $kgUnit->id,
                'cost_price' => 16000,
                'selling_price' => 0,
                'min_stock' => 300,
                'reorder_point' => 500,
                'reorder_qty' => 1000,
                'is_purchased' => true,
                'is_sold' => false,
                'is_manufactured' => false,
            ],
            [
                'sku' => 'RM-PIPE-001',
                'name' => 'Steel Pipe 2 inch',
                'category_id' => $rmCategory->id,
                'product_type' => 'raw_material',
                'unit_id' => $mtrUnit->id,
                'cost_price' => 85000,
                'selling_price' => 0,
                'min_stock' => 100,
                'reorder_point' => 200,
                'reorder_qty' => 500,
                'is_purchased' => true,
                'is_sold' => false,
                'is_manufactured' => false,
            ],
            // Finished Goods
            [
                'sku' => 'FG-FRAME-001',
                'name' => 'Steel Frame Assembly A',
                'category_id' => $fgCategory->id,
                'product_type' => 'finished_good',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 250000,
                'selling_price' => 350000,
                'min_stock' => 20,
                'reorder_point' => 50,
                'reorder_qty' => 100,
                'is_purchased' => false,
                'is_sold' => true,
                'is_manufactured' => true,
            ],
            [
                'sku' => 'FG-FRAME-002',
                'name' => 'Steel Frame Assembly B',
                'category_id' => $fgCategory->id,
                'product_type' => 'finished_good',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 450000,
                'selling_price' => 600000,
                'min_stock' => 10,
                'reorder_point' => 25,
                'reorder_qty' => 50,
                'is_purchased' => false,
                'is_sold' => true,
                'is_manufactured' => true,
            ],
            [
                'sku' => 'FG-BRACKET-001',
                'name' => 'Mounting Bracket Standard',
                'category_id' => $fgCategory->id,
                'product_type' => 'finished_good',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 45000,
                'selling_price' => 75000,
                'min_stock' => 50,
                'reorder_point' => 100,
                'reorder_qty' => 200,
                'is_purchased' => false,
                'is_sold' => true,
                'is_manufactured' => true,
            ],
            // Spare Parts
            [
                'sku' => 'SP-BOLT-001',
                'name' => 'Hex Bolt M10x50',
                'category_id' => $spCategory->id,
                'product_type' => 'spare_part',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 500,
                'selling_price' => 1000,
                'min_stock' => 500,
                'reorder_point' => 1000,
                'reorder_qty' => 5000,
                'is_purchased' => true,
                'is_sold' => true,
                'is_manufactured' => false,
            ],
            [
                'sku' => 'SP-NUT-001',
                'name' => 'Hex Nut M10',
                'category_id' => $spCategory->id,
                'product_type' => 'spare_part',
                'unit_id' => $pcsUnit->id,
                'cost_price' => 200,
                'selling_price' => 500,
                'min_stock' => 500,
                'reorder_point' => 1000,
                'reorder_qty' => 5000,
                'is_purchased' => true,
                'is_sold' => true,
                'is_manufactured' => false,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create([
                'company_id' => $company->id,
                ...$productData,
                'is_active' => true,
            ]);

            // Create initial stock in main warehouse
            $initialQty = rand(50, 500);
            ProductStock::create([
                'product_id' => $product->id,
                'warehouse_id' => $createdWarehouses[0]->id,
                'qty_on_hand' => $initialQty,
                'qty_reserved' => 0,
                'qty_incoming' => 0,
                'qty_outgoing' => 0,
                'avg_cost' => $productData['cost_price'],
            ]);
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Created: 1 Company, ' . count($units) . ' Units, ' . count($categories) . ' Categories, ' . count($warehouses) . ' Warehouses, ' . count($products) . ' Products');
    }
}
