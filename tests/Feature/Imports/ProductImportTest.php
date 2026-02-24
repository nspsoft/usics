<?php

namespace Tests\Feature\Imports;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Imports\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_product_with_partner_names()
    {
        // 1. Create Customer and Supplier
        $customer = Customer::create(['name' => 'PT. Customer A', 'code' => 'CUST001']);
        $supplier = Supplier::create(['name' => 'CV. Supplier B', 'code' => 'SUPP001']);

        // 2. Prepare mock row data (simulating Excel row)
        $row = [
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'barcode' => '1234567890123',
            'category' => 'Test Category',
            'unit' => 'pcs',
            'item_type' => 'product',
            'product_type' => 'finished_good',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'min_stock' => 10,
            'reorder_point' => 15,
            'reorder_qty' => 20,
            'max_stock' => 50,
            'lead_time_days' => 5,
            'weight' => 1,
            'weight_unit' => 'kg',
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'dimension_unit' => 'cm',
            'is_manufactured' => 'No',
            'is_purchased' => 'Yes',
            'is_sold' => 'Yes',
            'track_serial' => 'No',
            'track_batch' => 'No',
            'track_expiry' => 'No',
            'customer_name' => 'PT. Customer A',
            'supplier_name' => 'CV. Supplier B',
        ];

        // 3. Import
        $import = new ProductImport();
        $product = $import->model($row);
        $product->save();

        // 4. Assert
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-SKU-001',
            'customer_id' => $customer->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertEquals($customer->id, $product->customer_id);
        $this->assertEquals($supplier->id, $product->supplier_id);
    }
}
