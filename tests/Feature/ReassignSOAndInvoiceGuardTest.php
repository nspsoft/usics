<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Company;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReassignSOAndInvoiceGuardTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $company;
    private $customer;
    private $warehouse;
    private $unit;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Company::firstOrCreate([
            'code' => 'COMP-001',
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '0812345678',
            'address' => 'Jl. Test',
            'code' => 'CUST-001',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-001',
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
        ]);

        $this->unit = Unit::firstOrCreate([
            'code' => 'PCS',
            'name' => 'Pieces',
            'company_id' => $this->company->id,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TEST-SKU',
            'unit_id' => $this->unit->id,
            'is_sold' => true,
            'selling_price' => 100000,
            'cost_price' => 50000,
        ]);
    }

    public function test_create_invoice_guard_for_waiting_po_do()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'waiting_po', // waiting_po status
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $do = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $so->id,
            'do_number' => 'DO-001',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        $response = $this->post(route('sales.deliveries.create-invoice', $do->id));

        $response->assertSessionHas('error', 'Gagal membuat Invoice. Sales Order terkait belum memiliki Nomor PO resmi.');
    }

    public function test_bulk_invoice_filter_excludes_waiting_po()
    {
        // SO A: waiting_po
        $soA = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-A',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'waiting_po',
        ]);
        $soItemA = $soA->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $doA = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soA->id,
            'do_number' => 'DO-A',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItemA = DeliveryOrderItem::create([
            'delivery_order_id' => $doA->id,
            'sales_order_item_id' => $soItemA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // SO B: confirmed (has PO)
        $soB = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-B',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'customer_po_number' => 'PO-SO-B',
        ]);
        $soItemB = $soB->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $doB = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soB->id,
            'do_number' => 'DO-B',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItemB = DeliveryOrderItem::create([
            'delivery_order_id' => $doB->id,
            'sales_order_item_id' => $soItemB->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // Hit preview bulk invoice
        $response = $this->post(route('sales.deliveries.bulk-invoice-preview'), [
            'select_all' => true,
            'filters' => []
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        // DO B should be included, DO A should be excluded
        $this->assertEquals(1, $data['total_dos']);
        $this->assertEquals('DO-B', $data['preview'][0]['do_numbers']);
    }

    public function test_reassign_so_form()
    {
        $soA = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-A',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'waiting_po',
        ]);
        $soItemA = $soA->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $do = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soA->id,
            'do_number' => 'DO-001',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItemA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // Target SO B (confirmed, same customer & warehouse, same product, sufficient qty)
        $soB = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-B',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'customer_po_number' => 'PO-SO-B',
        ]);
        $soItemB = $soB->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'qty_delivered' => 0,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->get(route('sales.deliveries.reassign-so', $do->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Sales/Deliveries/ReassignSO')
            ->has('deliveryOrder')
            ->has('salesOrders', 1)
            ->where('salesOrders.0.id', $soB->id)
        );
    }

    public function test_reassign_so_success()
    {
        $soA = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-A',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'delivered', // Old SO status
        ]);
        $soItemA = $soA->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $do = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soA->id,
            'do_number' => 'DO-001',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItemA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // Target SO B
        $soB = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-B',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'customer_po_number' => 'PO-SO-B',
        ]);
        $soItemB = $soB->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'qty_delivered' => 0,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->post(route('sales.deliveries.reassign-so.update', $do->id), [
            'sales_order_id' => $soB->id,
        ]);

        $response->assertRedirect(route('sales.deliveries.show', $do->id));
        $response->assertSessionHas('success');

        // Verify DO is re-linked
        $do->refresh();
        $this->assertEquals($soB->id, $do->sales_order_id);

        // Verify DO item is re-linked
        $doItem->refresh();
        $this->assertEquals($soItemB->id, $doItem->sales_order_item_id);

        // Verify Old SO is updated
        $soItemA->refresh();
        $this->assertEquals(0.0, $soItemA->qty_delivered);
        $soA->refresh();
        $this->assertEquals('waiting_po', $soA->status); // Reverted since no items are delivered

        // Verify New SO is updated
        $soItemB->refresh();
        $this->assertEquals(5.0, $soItemB->qty_delivered);
        $soB->refresh();
        $this->assertEquals('processing', $soB->status); // Now partially delivered
    }

    public function test_po_status_filter_works()
    {
        // DO A (SO A is waiting_po)
        $soA = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-A',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'waiting_po',
        ]);
        $soItemA = $soA->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $doA = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soA->id,
            'do_number' => 'DO-A',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItemA = DeliveryOrderItem::create([
            'delivery_order_id' => $doA->id,
            'sales_order_item_id' => $soItemA->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // DO B (SO B is confirmed / has PO)
        $soB = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-B',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'customer_po_number' => 'PO-SO-B',
        ]);
        $soItemB = $soB->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $doB = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $soB->id,
            'do_number' => 'DO-B',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItemB = DeliveryOrderItem::create([
            'delivery_order_id' => $doB->id,
            'sales_order_item_id' => $soItemB->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        // 1. Filter: waiting_po
        $response1 = $this->get(route('sales.deliveries.index', ['po_status' => 'waiting_po']));
        $response1->assertStatus(200);
        $response1->assertInertia(fn ($page) => $page
            ->component('Sales/Deliveries/Index')
            ->has('deliveryOrders.data', 1)
            ->where('deliveryOrders.data.0.do_number', 'DO-A')
        );

        // 2. Filter: has_po
        $response2 = $this->get(route('sales.deliveries.index', ['po_status' => 'has_po']));
        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->component('Sales/Deliveries/Index')
            ->has('deliveryOrders.data', 1)
            ->where('deliveryOrders.data.0.do_number', 'DO-B')
        );
    }

    public function test_print_labels_successful()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-PRINT-LABELS',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'customer_po_number' => 'PO-PRINT-LABELS',
        ]);
        $soItem = $so->items()->create([
            'product_id' => $this->product->id,
            'qty' => 5,
            'qty_delivered' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);
        $do = DeliveryOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'sales_order_id' => $so->id,
            'do_number' => 'DO-PRINT-LABELS',
            'warehouse_id' => $this->warehouse->id,
            'delivery_date' => now()->toDateString(),
            'status' => 'completed',
        ]);
        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->product->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        $labelData = [
            [
                'item_id' => $doItem->id,
                'qty_per_label' => 5,
                'label_count' => 2,
                'lot_number' => 'LOT-001',
                'spk' => 'SPK-001',
                'note' => 'TEST NOTE',
                'size' => '1200 mm x 400 mm x 2 mm',
                'specification' => 'TEST SPEC',
            ]
        ];

        $response = $this->post(route('sales.deliveries.print-labels', $do->id), [
            'label_data' => json_encode($labelData),
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('print.product-labels');
        $response->assertViewHas('labels');
        
        $labels = $response->viewData('labels');
        $this->assertCount(2, $labels);
        $this->assertEquals('Test Customer', $labels[0]['customer_name']);
        $this->assertEquals('Test Product', $labels[0]['product_name']);
        $this->assertEquals('TEST-SKU', $labels[0]['sku']);
        $this->assertEquals('TEST SPEC', $labels[0]['specification']);
        $this->assertEquals('1200 mm x 400 mm x 2 mm', $labels[0]['size']);
        $this->assertEquals('5 Pcs', $labels[0]['qty']);
        $this->assertEquals('LOT-001', $labels[0]['lot_number']);
        $this->assertEquals('SPK-001', $labels[0]['spk']);
        $this->assertEquals('TEST NOTE', $labels[0]['note']);
    }
}
