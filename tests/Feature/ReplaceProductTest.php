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
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReplaceProductTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $company;
    private $customer;
    private $warehouse;
    private $unit;
    private $productA;
    private $productB;

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

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product A',
            'sku' => 'SKU-A',
            'unit_id' => $this->unit->id,
            'is_sold' => true,
            'selling_price' => 100000,
            'cost_price' => 50000,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Product B',
            'sku' => 'SKU-B',
            'unit_id' => $this->unit->id,
            'is_sold' => true,
            'selling_price' => 150000,
            'cost_price' => 70000,
        ]);
    }

    public function test_replace_product_success()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productB->id,
            'new_unit_price' => 150000,
            'new_unit_id' => $this->unit->id,
            'reason' => 'Wrong product selection',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $soItem->refresh();
        $this->assertEquals($this->productB->id, $soItem->product_id);
        $this->assertEquals(150000, $soItem->unit_price);

        // Check if Sales Order totals were recalculated
        $so->refresh();
        $this->assertEquals(750000, $so->subtotal); // 5 * 150000
    }

    public function test_cannot_replace_product_if_same_product()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productA->id,
            'reason' => 'No change',
        ]);

        $response->assertSessionHas('error', 'Produk yang dipilih sama dengan produk saat ini.');
        
        $soItem->refresh();
        $this->assertEquals($this->productA->id, $soItem->product_id);
    }

    public function test_cannot_replace_product_if_qty_delivered_greater_than_zero()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'processing',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
            'qty_delivered' => 2,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productB->id,
            'reason' => 'Change item',
        ]);

        $response->assertSessionHas('error', 'Tidak bisa ganti produk: item ini sudah pernah dikirim (qty_delivered > 0).');
        
        $soItem->refresh();
        $this->assertEquals($this->productA->id, $soItem->product_id);
    }

    public function test_cannot_replace_product_if_qty_invoiced_greater_than_zero()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'processing',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
            'qty_invoiced' => 1,
            'unit_price' => 100000,
            'discount_percent' => 0,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productB->id,
            'reason' => 'Change item',
        ]);

        $response->assertSessionHas('error', 'Tidak bisa ganti produk: item ini sudah diinvoice.');
        
        $soItem->refresh();
        $this->assertEquals($this->productA->id, $soItem->product_id);
    }

    public function test_cannot_replace_product_if_active_delivery_order_exists()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'processing',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
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
            'status' => 'draft', // Active DO
        ]);

        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productB->id,
            'reason' => 'Change item',
        ]);

        $response->assertSessionHas('error', 'Tidak bisa ganti produk: item ini sudah terdaftar di Delivery Order aktif.');
        
        $soItem->refresh();
        $this->assertEquals($this->productA->id, $soItem->product_id);
    }

    public function test_can_replace_product_if_only_cancelled_delivery_order_exists()
    {
        $so = SalesOrder::create([
            'company_id' => $this->company->id,
            'so_number' => 'SO-001',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);

        $soItem = $so->items()->create([
            'product_id' => $this->productA->id,
            'qty' => 5,
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
            'status' => 'cancelled', // Cancelled DO (should not block replacement)
        ]);

        $doItem = DeliveryOrderItem::create([
            'delivery_order_id' => $do->id,
            'sales_order_item_id' => $soItem->id,
            'product_id' => $this->productA->id,
            'qty_ordered' => 5,
            'qty_delivered' => 5,
        ]);

        $response = $this->put(route('sales.orders.replace-item-product', $soItem->id), [
            'new_product_id' => $this->productB->id,
            'new_unit_price' => 150000,
            'new_unit_id' => $this->unit->id,
            'reason' => 'Wrong product selection',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $soItem->refresh();
        $this->assertEquals($this->productB->id, $soItem->product_id);
    }
}
