<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use App\Models\Product;
use App\Models\Company;
use App\Models\Unit;
use App\Models\Customer;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PriceCleanerTest extends TestCase
{
    use RefreshDatabase;

    private GeminiService $service;
    private $company;
    private $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeminiService();

        $this->company = Company::create([
            'code' => 'TEST-COMP',
            'name' => 'Test Company',
            'email' => 'test@comp.com',
        ]);

        $this->unit = Unit::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * Test cleanAndParsePrice with different formats for IDR currency.
     */
    public function test_clean_and_parse_price_idr()
    {
        // Indonesian thousand dot separators
        $this->assertEquals(137170.0, $this->service->cleanAndParsePrice("137.170", "IDR"));
        $this->assertEquals(548680.0, $this->service->cleanAndParsePrice("548.680", "IDR"));
        $this->assertEquals(1500000.0, $this->service->cleanAndParsePrice("1.500.000", "IDR"));
        
        // Commas as thousand separator
        $this->assertEquals(137170.0, $this->service->cleanAndParsePrice("137,170", "IDR"));
        
        // Truncated decimal float values from AI
        $this->assertEquals(137170.0, $this->service->cleanAndParsePrice(137.17, "IDR"));
        $this->assertEquals(548680.0, $this->service->cleanAndParsePrice(548.68, "IDR"));
        $this->assertEquals(1500.0, $this->service->cleanAndParsePrice(1.5, "IDR"));
        $this->assertEquals(1000.0, $this->service->cleanAndParsePrice(1.0, "IDR"));
        
        // Small values that are whole integers (should remain unchanged)
        $this->assertEquals(495.0, $this->service->cleanAndParsePrice(495, "IDR"));
        $this->assertEquals(932.0, $this->service->cleanAndParsePrice(932, "IDR"));
        $this->assertEquals(235.0, $this->service->cleanAndParsePrice(235, "IDR"));
        
        // Decimals with comma as decimal separator
        $this->assertEquals(137170.0, $this->service->cleanAndParsePrice("137.170,00", "IDR"));
    }

    /**
     * Test cleanAndParsePrice with USD currency rules (decimals are preserved, thousands are NOT parsed as small integers).
     */
    public function test_clean_and_parse_price_usd()
    {
        $this->assertEquals(137.17, $this->service->cleanAndParsePrice("137.17", "USD"));
        $this->assertEquals(1.50, $this->service->cleanAndParsePrice("1.50", "USD"));
        $this->assertEquals(1500.00, $this->service->cleanAndParsePrice("1,500.00", "USD"));
        $this->assertEquals(1500.00, $this->service->cleanAndParsePrice("1.500,00", "USD")); // European notation
    }

    /**
     * Test SKU and product type (finished_good) matching logic.
     */
    public function test_sku_and_finished_good_matching()
    {
        // 1. Create a product of type finished_good
        $fgProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad FG',
            'sku' => 'DPD32A',
            'selling_price' => 1188,
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'finished_good',
            'is_active' => true,
            'is_sold' => true,
            'is_purchased' => true,
        ]);

        // 2. Create another product of type raw_material with a similar/same name
        $rmProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad RM',
            'sku' => 'DPD32A-RAW',
            'selling_price' => 500,
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'raw_material',
            'is_active' => true,
            'is_sold' => true,
            'is_purchased' => true,
        ]);

        // Mock request context to call autoMatchData in POImportController
        $controller = new \App\Http\Controllers\Sales\POImportController($this->service);
        
        // Call autoMatchData via reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('autoMatchData');
        $method->setAccessible(true);

        $inputData = [
            'customer_name' => 'Test Customer',
            'items' => [
                [
                    'material_number' => 'DPD32A',
                    'description' => 'Cardboard pad',
                    'unit_price' => '1.188'
                ],
                [
                    'material_number' => 'DPD32A-RAW', // this is a raw material SKU, should NOT match
                    'description' => 'Cardboard pad Raw',
                    'unit_price' => '500'
                ]
            ]
        ];

        $result = $method->invokeArgs($controller, [$inputData]);

        // First item should match the finished good product
        $this->assertEquals($fgProduct->id, $result['items'][0]['matched_product_id']);
        $this->assertEquals('MATCHED', $result['items'][0]['match_status']);

        // Second item should NOT match because it is a raw material, even though the SKU matches
        $this->assertNull($result['items'][1]['matched_product_id']);
        $this->assertEquals('NO_MATCH', $result['items'][1]['match_status']);
    }

    /**
     * Test SKU and product type (finished_good) matching logic in DeliveryScheduleController.
     */
    public function test_delivery_schedule_finished_good_matching()
    {
        // 1. Create a product of type finished_good
        $fgProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad FG',
            'sku' => 'DPD32A',
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'finished_good',
            'is_active' => true,
            'is_sold' => true,
            'is_purchased' => true,
        ]);

        // 2. Create another product of type raw_material with the same SKU
        $rmProduct = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad RM',
            'sku' => 'DPD65-RAW',
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'raw_material',
            'is_active' => true,
            'is_sold' => true,
            'is_purchased' => true,
        ]);

        $controller = new \App\Http\Controllers\Sales\Planning\DeliveryScheduleController($this->service);
        
        // Call autoMatchProducts via reflection
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('autoMatchProducts');
        $method->setAccessible(true);

        $inputItems = [
            [
                'product_code' => 'DPD32A', // Should match because it's finished_good
                'supplier_name' => 'PT Test Customer',
            ],
            [
                'product_code' => 'DPD65-RAW', // Should NOT match because it's raw_material
                'supplier_name' => 'PT Test Customer',
            ],
            [
                'product_code' => 'DPD32', // SKU partial match, should NOT match since partial match is disabled
                'supplier_name' => 'PT Test Customer',
            ]
        ];

        $result = $method->invokeArgs($controller, [$inputItems]);

        // First item should match
        $this->assertEquals($fgProduct->id, $result[0]['product_id']);
        $this->assertEquals('MATCHED', $result[0]['match_status']);

        // Second item should NOT match (raw material)
        $this->assertNull($result[1]['product_id']);
        $this->assertEquals('NO_MATCH', $result[1]['match_status']);

        // Third item should NOT match (partial match disabled)
        $this->assertNull($result[2]['product_id']);
        $this->assertEquals('NO_MATCH', $result[2]['match_status']);
    }

    /**
     * Test Delivery Schedule Exports column count and customer matching behavior.
     */
    public function test_delivery_schedule_exports_and_customer_matching()
    {
        // Test DeliveryScheduleTemplateExport columns count
        $templateExport = new \App\Exports\DeliveryScheduleTemplateExport();
        $this->assertCount(6, $templateExport->headings());

        // Create a test customer
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'PT. Indotama Sukses',
            'code' => 'CUST-IND-01',
        ]);

        $items = [
            [
                'product_code' => 'DPD32A',
                'qty' => 100,
                'date' => '2026-06-14',
                'match_status' => 'MATCHED',
                'product_sku' => 'DPD32A',
                'product_name' => 'Cardboard pad FG',
                'supplier_name' => 'Unknown customer',
            ]
        ];

        // Test without customer_id
        $exportWithoutCustomer = new \App\Exports\AiMatrixExtractionExport($items, 'June 2026');
        $this->assertCount(6, $exportWithoutCustomer->headings());
        $rowsWithout = $exportWithoutCustomer->array();
        $this->assertEquals('', $rowsWithout[0][0]); // Customer Code should be empty
        $this->assertEquals('Unknown customer', $rowsWithout[0][1]); // Supplier name fallback

        // Test with customer_id parameter
        $exportWithCustomer = new \App\Exports\AiMatrixExtractionExport($items, 'June 2026', $customer->id);
        $rowsWith = $exportWithCustomer->array();
        $this->assertEquals('CUST-IND-01', $rowsWith[0][0]); // Customer Code should be mapped
        $this->assertEquals('PT. Indotama Sukses', $rowsWith[0][1]); // Customer Name should be mapped
    }

    /**
     * Test Delivery Schedule resetData endpoint.
     */
    public function test_delivery_schedule_reset_data()
    {
        // Create a schedule
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'PT. Indotama Sukses',
            'code' => 'CUST-IND-01',
        ]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad FG',
            'sku' => 'DPD32A',
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'finished_good',
            'is_active' => true,
        ]);
        
        $schedule = \App\Models\DeliverySchedule::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'delivery_date' => '2026-06-14',
            'qty_scheduled' => 100,
        ]);

        $this->assertEquals(1, \App\Models\DeliverySchedule::count());

        $controller = new \App\Http\Controllers\Sales\Planning\DeliveryScheduleController($this->service);
        $request = new \Illuminate\Http\Request();
        $response = $controller->resetData($request);

        $this->assertEquals(0, \App\Models\DeliverySchedule::count());
    }

    public function test_delivery_schedule_comparison_chart_unknown_customer()
    {
        // 1. Create a user, customer, product, and warehouse
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'testuser@comp.com',
            'password' => bcrypt('password'),
        ]);
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'PT. Indotama Sukses',
            'code' => 'CUST-IND-01',
        ]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Cardboard pad FG',
            'sku' => 'DPD32A',
            'unit_id' => $this->unit->id,
            'type' => 'product',
            'product_type' => 'finished_good',
            'is_active' => true,
        ]);
        $warehouse = \App\Models\Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
        ]);

        // 2. Create actual Delivery Order (Without planned Delivery Schedule)
        $so = \App\Models\SalesOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'so_number' => 'SO-0001',
            'order_date' => '2026-06-14',
            'status' => 'approved',
            'created_by' => $user->id,
        ]);
        $soItem = $so->items()->create([
            'product_id' => $product->id,
            'qty' => 50,
            'unit_price' => 1000,
        ]);
        $do = \App\Models\DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'sales_order_id' => $so->id,
            'warehouse_id' => $warehouse->id,
            'do_number' => 'DO-0001',
            'delivery_date' => '2026-06-14',
            'status' => \App\Models\DeliveryOrder::STATUS_SHIPPED,
            'created_by' => $user->id,
        ]);
        \App\Models\DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $product->id,
            'qty_ordered' => 50,
            'qty_shipped' => 50,
            'qty_delivered' => 50,
        ]);

        $controller = new \App\Http\Controllers\Sales\Planning\DeliveryScheduleController($this->service);
        $request = new \Illuminate\Http\Request([
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'level' => 'summary',
        ]);
        $response = $controller->comparisonChart($request);
        $data = $response->getData(true);

        // Should return customer name instead of "Unknown"
        $this->assertEquals('PT. Indotama Sukses', $data['data'][0]['name']);
    }
}
