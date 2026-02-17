<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory\ProductPartner;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAliasPrintTest extends TestCase
{
    use RefreshDatabase;

    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = \App\Models\Company::create([
            'name' => 'Test Company',
            'code' => 'CO001'
        ]);
        
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
        
        $this->unit = Unit::create(['name' => 'Piece', 'code' => 'PCS', 'type' => 'Unit']);
        $this->warehouse = Warehouse::create(['name' => 'Main Warehouse', 'code' => 'WH01', 'company_id' => $this->company->id]);

        $this->product = Product::factory()->create([
            'name' => 'Internal Product Name',
            'sku' => 'INTERNAL-SKU',
            'unit_id' => $this->unit->id,
            'type' => 'finished_goods',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'company_id' => $this->company->id,
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST001',
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'address' => 'Test Address',
            'term_of_payment' => 30,
            'company_id' => $this->company->id,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'code' => 'SUPP001',
            'email' => 'supplier@test.com',
            'phone' => '0987654321',
            'address' => 'Supplier Address',
            'term_of_payment' => 30,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_sales_order_print_uses_customer_alias()
    {
        // Create Alias
        ProductPartner::create([
            'product_id' => $this->product->id,
            'partner_type' => Customer::class,
            'partner_id' => $this->customer->id,
            'alias_name' => 'Customer Product Name',
            'alias_sku' => 'CUST-SKU-001'
        ]);

        // Create SO
        $so = SalesOrder::create([
            'so_number' => 'SO-TEST-' . uniqid(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'delivery_date' => now()->addDays(3),
            'status' => 'confirmed',
            'created_by' => $this->user->id,
            'total' => 100000, // Minimal fields
            'subtotal' => 100000,
        ]);

        $so->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_id' => $this->unit->id,
            'unit_price' => 10000,
            'subtotal' => 100000,
        ]);

        // Request Print
        $response = $this->get(route('sales.orders.print', $so->id));

        $response->assertStatus(200);
        $response->assertSee('Customer Product Name');
        $response->assertSee('CUST-SKU-001');
        $response->assertDontSee('Internal Product Name');
    }

    public function test_purchase_order_print_uses_supplier_alias()
    {
        // Create Alias
        ProductPartner::create([
            'product_id' => $this->product->id,
            'partner_type' => Supplier::class,
            'partner_id' => $this->supplier->id,
            'alias_name' => 'Supplier Product Name',
            'alias_sku' => 'SUPP-SKU-001'
        ]);

        // Create PO
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-' . uniqid(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'delivery_date' => now()->addDays(7),
            'status' => 'approved',
            'created_by' => $this->user->id,
            'total' => 50000,
            'subtotal' => 50000,
        ]);

        $po->items()->create([
            'product_id' => $this->product->id,
            'qty' => 10,
            'unit_id' => $this->unit->id,
            'unit_price' => 5000,
            'subtotal' => 50000,
        ]);

        // Request Print
        $response = $this->get(route('purchasing.orders.print', $po->id));

        $response->assertStatus(200);
        $response->assertSee('Supplier Product Name');
        $response->assertSee('SUPP-SKU-001');
    }

    public function test_invoice_print_uses_customer_alias()
    {
        // Create Alias
        ProductPartner::create([
            'product_id' => $this->product->id,
            'partner_type' => Customer::class,
            'partner_id' => $this->customer->id,
            'alias_name' => 'Customer Product Name',
            'alias_sku' => 'CUST-SKU-001'
        ]);

        // Create SO for Invoice
        $so = SalesOrder::create([
            'so_number' => 'SO-INV-TEST-' . uniqid(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now(),
            'delivery_date' => now()->addDays(3),
            'status' => 'confirmed',
            'created_by' => $this->user->id,
            'total' => 60000,
            'subtotal' => 60000,
        ]);

        // Create Invoice
        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'sales_order_id' => $so->id, // Linked SO
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'invoice_date' => now(),
            'due_date' => now(),
            'status' => 'issued',
            'created_by' => $this->user->id,
            'total' => 60000,
            'subtotal' => 60000,
        ]);

        $invoice->items()->create([
            'product_id' => $this->product->id,
            'description' => 'Original Description',
            'qty' => 5,
            'unit_id' => $this->unit->id,
            'unit_price' => 12000,
            'subtotal' => 60000,
        ]);

        // Request Print
        $response = $this->get(route('sales.invoices.print', $invoice->id));

        $response->assertStatus(200);
        $response->assertSee('Customer Product Name');
        $response->assertSee('CUST-SKU-001');
    }
}
