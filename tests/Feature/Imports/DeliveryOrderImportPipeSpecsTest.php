<?php

namespace Tests\Feature\Imports;

use App\Imports\DeliveryOrderImport;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeliveryOrderImportPipeSpecsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_sap_delivery_orders_with_pipe_specs()
    {
        // 1. Create dependencies
        $customer = Customer::create(['name' => 'PT. DHARMA POLIMETAL TBK.', 'code' => 'DPM']);
        $warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH01']);
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'Pcs', 'code' => 'PCS']);

        $product1 = Product::create([
            'sku' => 'XAB01BOK0220+60400',
            'name' => 'Pipe 1',
            'unit_id' => $unit->id,
            'is_sold' => true,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'sku' => 'UAB12BAA0120+00150',
            'name' => 'Pipe 2',
            'unit_id' => $unit->id,
            'is_sold' => true,
            'is_active' => true,
        ]);

        // Create Sales Order 1
        $so = SalesOrder::create([
            'so_number' => '2010301085',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => '2026-06-13',
            'status' => 'confirmed',
            'subtotal' => 0,
            'total' => 0,
        ]);

        $soItem1 = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_id' => $product1->id,
            'qty' => 500,
            'unit_id' => $unit->id,
            'unit_price' => 10000,
            'subtotal' => 5000000,
        ]);

        // Create Sales Order 2
        $so2 = SalesOrder::create([
            'so_number' => '2010301090',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => '2026-06-13',
            'status' => 'confirmed',
            'subtotal' => 0,
            'total' => 0,
        ]);

        $soItem2 = SalesOrderItem::create([
            'sales_order_id' => $so2->id,
            'product_id' => $product2->id,
            'qty' => 1000,
            'unit_id' => $unit->id,
            'unit_price' => 5000,
            'subtotal' => 5000000,
        ]);

        // 2. Prepare mock rows simulating SAP spreadsheet structure (slugified headers)
        $rows = collect([
            [
                'gi_date' => '13.06.2026',
                'no_shipment' => '6010280946',
                'no_do' => '8010715954',
                'no_so' => '2010301085',
                'so_item' => '11',
                'customer' => 'PT. DHARMA POLIMETAL TBK.',
                'material' => 'XAB01BOK0220+60400',
                'inchi' => '',
                'tr' => '',
                'od' => '48,6',
                'tebal' => '2,3',
                'panjang' => '6040',
                'qty_do' => '200',
                'kg_do' => '3,040.80',
            ],
            [
                'gi_date' => '13.06.2026',
                'no_shipment' => '6010280947',
                'no_do' => '8010715955',
                'no_so' => '2010301090',
                'so_item' => '15',
                'customer' => 'PT. DHARMA POLIMETAL TBK.',
                'material' => 'UAB12BAA0120+00150',
                'inchi' => '1"',
                'tr' => '',
                'od' => '25,4',
                'tebal' => '1,2',
                'panjang' => '15',
                'qty_do' => '800',
                'kg_do' => '8.80',
            ]
        ]);

        // 3. Run import
        $import = new DeliveryOrderImport(false);
        $import->collection($rows);

        // 4. Assertions
        $this->assertEmpty($import->errors, 'Import generated unexpected errors: ' . implode(', ', $import->errors));

        // Check DO 1
        $this->assertDatabaseHas('delivery_orders', [
            'do_number' => '8010715954',
            'shipment_number' => '6010280946',
            'delivery_date' => '2026-06-13',
            'status' => 'draft',
        ]);

        $do1 = DeliveryOrder::where('do_number', '8010715954')->first();
        $this->assertNotNull($do1);
        $this->assertCount(1, $do1->items);

        $doItem1 = $do1->items->first();
        $this->assertEquals($product1->id, $doItem1->product_id);
        $this->assertEquals(200.0, $doItem1->qty_delivered);
        $this->assertEquals('', $doItem1->inchi);
        $this->assertEquals(48.6, $doItem1->od);
        $this->assertEquals(2.3, $doItem1->tebal);
        $this->assertEquals(6040.0, $doItem1->panjang);
        $this->assertEquals(3040.8, $doItem1->kg_delivered);

        // Check DO 2
        $this->assertDatabaseHas('delivery_orders', [
            'do_number' => '8010715955',
            'shipment_number' => '6010280947',
            'delivery_date' => '2026-06-13',
            'status' => 'draft',
        ]);

        $do2 = DeliveryOrder::where('do_number', '8010715955')->first();
        $this->assertNotNull($do2);
        $this->assertCount(1, $do2->items);

        $doItem2 = $do2->items->first();
        $this->assertEquals($product2->id, $doItem2->product_id);
        $this->assertEquals(800.0, $doItem2->qty_delivered);
        $this->assertEquals('1"', $doItem2->inchi);
        $this->assertEquals(25.4, $doItem2->od);
        $this->assertEquals(1.2, $doItem2->tebal);
        $this->assertEquals(15.0, $doItem2->panjang);
        $this->assertEquals(8.8, $doItem2->kg_delivered);
    }
}
