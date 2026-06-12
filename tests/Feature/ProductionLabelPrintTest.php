<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionLabelPrintTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $user;
    protected $unit;
    protected $warehouse;
    protected $product;
    protected $customer;
    protected $bom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Jidoka Company',
            'code' => 'JRI01'
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->unit = Unit::create(['name' => 'Piece', 'code' => 'PCS', 'type' => 'Unit']);
        $this->warehouse = Warehouse::create([
            'name' => 'Production WH',
            'code' => 'PWH01',
            'company_id' => $this->company->id
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Finished Goods Product',
            'sku' => 'FG-PRODUCT-001',
            'unit_id' => $this->unit->id,
            'type' => 'finished_goods',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'company_id' => $this->company->id,
            'length' => 10,
            'width' => 12,
            'height' => 14,
            'dimension_unit' => 'cm',
            'description' => 'Test description specs'
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Label Customer',
            'code' => 'CUST002',
            'email' => 'customer-label@test.com',
            'phone' => '12345678',
            'address' => 'Customer address',
            'term_of_payment' => 30,
            'company_id' => $this->company->id,
        ]);

        $this->bom = \App\Models\Bom::create([
            'product_id' => $this->product->id,
            'name' => 'BOM Test Name',
            'code' => 'BOM-TEST-01',
            'qty' => 1,
            'unit_id' => $this->unit->id,
            'is_active' => true,
            'company_id' => $this->company->id
        ]);
    }

    public function test_print_production_labels_successful()
    {
        // 1. Create Sales Order
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-123',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'delivery_date' => now()->addDays(3),
            'status' => 'confirmed',
            'created_by' => $this->user->id,
            'total' => 2000,
            'subtotal' => 2000,
            'customer_po_number' => 'PO-CUST-999'
        ]);

        // 2. Create Work Order
        $wo = WorkOrder::create([
            'company_id' => $this->company->id,
            'wo_number' => 'WO-2026-0001',
            'bom_id' => $this->bom->id,
            'product_id' => $this->product->id,
            'sales_order_id' => $so->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_planned' => 100,
            'status' => 'confirmed',
            'planned_start' => now(),
            'planned_end' => now()->addDays(2),
            'created_by' => $this->user->id
        ]);

        // 3. Create Production Entry
        $entry = ProductionEntry::create([
            'work_order_id' => $wo->id,
            'production_date' => now(),
            'shift' => '1',
            'qty_produced' => 80,
            'qty_rejected' => 2,
            'entry_user_id' => $this->user->id
        ]);

        // 4. Print request payload
        $labelData = [
            [
                'item_id' => $entry->id,
                'qty_per_label' => 40,
                'label_count' => 2,
                'lot_number' => 'PO-CUST-999',
                'spk' => 'SO-TEST-123',
                'note' => 'Shift 1 - Operator A',
                'size' => '10 cm x 12 cm x 14 cm',
                'specification' => 'Test description specs'
            ]
        ];

        $response = $this->post(route('manufacturing.production-reports.print-labels', $entry->id), [
            'label_data' => json_encode($labelData)
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('print.product-labels');
        $response->assertSee('Test Label Customer');
        $response->assertSee('Test Finished Goods Product');
        $response->assertSee('FG-PRODUCT-001');
        $response->assertSee('40 Pcs');
        $response->assertSee('PO-CUST-999');
        $response->assertSee('SO-TEST-123');
        $response->assertSee('Shift 1 - Operator A');
        $response->assertSee('10 cm x 12 cm x 14 cm');
    }

    public function test_print_production_labels_fallback_to_stock_wo()
    {
        // 1. Create Work Order without Sales Order (Internal Stock WO)
        $wo = WorkOrder::create([
            'company_id' => $this->company->id,
            'wo_number' => 'WO-STOCK-0002',
            'bom_id' => $this->bom->id,
            'product_id' => $this->product->id,
            'sales_order_id' => null, // No SO
            'warehouse_id' => $this->warehouse->id,
            'qty_planned' => 50,
            'status' => 'confirmed',
            'planned_start' => now(),
            'planned_end' => now()->addDays(2),
            'created_by' => $this->user->id
        ]);

        // 2. Create Production Entry
        $entry = ProductionEntry::create([
            'work_order_id' => $wo->id,
            'production_date' => now(),
            'shift' => '2',
            'qty_produced' => 50,
            'qty_rejected' => 0,
            'entry_user_id' => $this->user->id
        ]);

        // 3. Print request payload
        $labelData = [
            [
                'item_id' => $entry->id,
                'qty_per_label' => 50,
                'label_count' => 1,
                'lot_number' => '', // Empty lot
                'spk' => 'WO-STOCK-0002', // Fallback to WO number
                'note' => 'Shift 2 - Operator B',
                'size' => '10 cm x 12 cm x 14 cm',
                'specification' => 'Test description specs'
            ]
        ];

        $response = $this->post(route('manufacturing.production-reports.print-labels', $entry->id), [
            'label_data' => json_encode($labelData)
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('print.product-labels');
        $response->assertSee('STOCK'); // Customer name defaults to STOCK
        $response->assertSee('WO-STOCK-0002');
        $response->assertSee('Shift 2 - Operator B');
    }
}
